<?php

namespace craft\feedme\elements;

use Cake\Utility\Hash;
use Carbon\Carbon;
use Craft;
use craft\base\ElementInterface;
use craft\commerce\collections\UpdateInventoryLevelCollection;
use craft\commerce\elements\Product;
use craft\commerce\elements\Product as ProductElement;
use craft\commerce\elements\Variant as VariantElement;
use craft\commerce\models\inventory\UpdateInventoryLevel;
use craft\commerce\models\InventoryLevel;
use craft\commerce\Plugin as Commerce;
use craft\commerce\queue\jobs\CatalogPricing;
use craft\db\Query;
use craft\feedme\base\Element;
use craft\feedme\events\FeedProcessEvent;
use craft\feedme\helpers\BaseHelper;
use craft\feedme\helpers\DataHelper;
use craft\feedme\Plugin;
use craft\feedme\services\Process;
use craft\fields\Matrix;
use craft\fields\Table;
use craft\helpers\ArrayHelper;
use craft\helpers\ElementHelper;
use craft\helpers\Json;
use DateTime;
use Exception;
use yii\base\Event;
use yii\queue\PushEvent;
use yii\queue\Queue;

/**
 *
 * @property-read string $mappingTemplate
 * @property-read mixed $groups
 * @property-write mixed $model
 * @property-read string $groupsTemplate
 * @property-read string $columnTemplate
 */
class CommerceProduct extends Element
{
    // Properties
    // =========================================================================

    /**
     * @var string
     */
    public static string $name = 'Commerce Product';

    /**
     * @var string
     */
    public static string $class = ProductElement::class;

    /**
     * @var bool
     * @since x.x.x
     */
    private bool $_runCatalogPricingJob = false;

    // Templates
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function getGroupsTemplate(): string
    {
        return 'feed-me/_includes/elements/commerce-products/groups';
    }

    /**
     * @inheritDoc
     */
    public function getColumnTemplate(): string
    {
        return 'feed-me/_includes/elements/commerce-products/column';
    }

    /**
     * @inheritDoc
     */
    public function getMappingTemplate(): string
    {
        return 'feed-me/_includes/elements/commerce-products/map';
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function init(): void
    {
        parent::init();

        // Hook into the process service on each step - we need to re-arrange the feed mapping
        Event::on(Process::class, Process::EVENT_STEP_BEFORE_ELEMENT_MATCH, function(FeedProcessEvent $event) {
            if ($event->feed['elementType'] === ProductElement::class) {
                $this->_checkForVariantMatches($event);
            }
        });

        Event::on(Process::class, Process::EVENT_STEP_BEFORE_PARSE_CONTENT, function(FeedProcessEvent $event) {
            if ($event->feed['elementType'] === ProductElement::class) {
                // at this point we've matched existing elements;
                // if $event->element->id is null then we haven't found a match so create an unsaved draft of the product
                // so that the variants can get saved right
                if (!$event->element->id) {
                    $originalScenario = $event->element->getScenario();
                    $event->element->setScenario(\craft\base\Element::SCENARIO_ESSENTIALS);
                    if (!Craft::$app->getDrafts()->saveElementAsDraft($event->element, null, null, null, false)) {
                        throw new Exception('Unable to create product element as unsaved');
                    }
                    $event->element->setScenario($originalScenario);
                }
                $this->_preParseVariants($event);
            }
        });

        // Hook into the before element save event, because we need to do lots to prepare variant data
        Event::on(Process::class, Process::EVENT_STEP_BEFORE_ELEMENT_SAVE, function(FeedProcessEvent $event) {
            if ($event->feed['elementType'] === ProductElement::class) {
                $this->_parseVariants($event);
            }
        });

        // We can only update stock after the purchasable elements have been saved
        Event::on(Process::class, Process::EVENT_STEP_AFTER_ELEMENT_SAVE, function(FeedProcessEvent $event) {
            if ($event->feed['elementType'] === ProductElement::class) {
                $this->_inventoryUpdate($event);
            }
        });

        // While imports are happening don't process any catalog pricing jobs
        Event::on(Queue::class, Queue::EVENT_BEFORE_PUSH, function(PushEvent $event) {
            if ($event->job instanceof CatalogPricing && !$this->_runCatalogPricingJob) {
                $event->handled = true;
            }
        });

        // After the feed has run, create a catalog pricing job to update the pricing
        Event::on(Process::class, Process::EVENT_AFTER_PROCESS_FEED, function(FeedProcessEvent $event) {
            if (Craft::$app->getPlugins()->isPluginEnabled('commerce') && $this->_runCatalogPricingJob = true) {
                Commerce::getInstance()->getCatalogPricing()->createCatalogPricingJob();
            }
        });
    }

    /**
     * @inheritDoc
     */
    public function getGroups(): array
    {
        if (Commerce::getInstance()) {
            return Commerce::getInstance()->getProductTypes()->getEditableProductTypes();
        }

        return [];
    }

    /**
     * @inheritDoc
     */
    public function getQuery($settings, array $params = []): mixed
    {
        $targetSiteId = Hash::get($settings, 'siteId') ?: Craft::$app->getSites()->getPrimarySite()->id;
        if ($this->element !== null) {
            $productType = $this->element->getType();
        }

        $query = ProductElement::find()
            ->status(null)
            ->typeId($settings['elementGroup'][ProductElement::class]);

        if (isset($productType) && $productType->propagationMethod === \craft\enums\PropagationMethod::Custom) {
            $query->site('*')
                ->preferSites([$targetSiteId])
                ->unique();
        } else {
            $query->siteId($targetSiteId);
        }

        Craft::configure($query, $params);
        return $query;
    }

    /**
     * @inheritDoc
     */
    public function setModel($settings): ElementInterface
    {
        $this->element = new ProductElement();
        $this->element->typeId = $settings['elementGroup'][ProductElement::class];

        $siteId = Hash::get($settings, 'siteId');

        if ($siteId) {
            $this->element->siteId = $siteId;
        }

        /* @var \craft\commerce\models\ProductType $productType */
        $productType = Commerce::getInstance()->getProductTypes()->getProductTypeById($this->element->typeId);

        // Set the default site status based on the section's settings
        $enabledForSite = [];
        foreach ($productType->getSiteSettings() as $siteSettings) {
            if (
                $productType->propagationMethod !== \craft\enums\PropagationMethod::Custom ||
                $siteSettings->siteId == $siteId
            ) {
                $enabledForSite[$siteSettings->siteId] = $siteSettings->enabledByDefault;
            }
        }
        $this->element->setEnabledForSite($enabledForSite);

        return $this->element;
    }

    /**
     * Checks if $existingElement should be propagated to the target site.
     *
     * @param $existingElement
     * @param array $feed
     * @return ElementInterface|null
     * @throws \yii\base\Exception
     * @throws \craft\errors\SiteNotFoundException
     * @throws \craft\errors\UnsupportedSiteException
     * @since 5.1.3
     */
    public function checkPropagation($existingElement, array $feed)
    {
        $targetSiteId = Hash::get($feed, 'siteId') ?: Craft::$app->getSites()->getPrimarySite()->id;

        // Did the product come back in a different site?
        if ($existingElement->siteId != $targetSiteId) {
            // Skip it if its product type doesn't use the `custom` propagation method
            if ($existingElement->getType()->propagationMethod !== \craft\enums\PropagationMethod::Custom) {
                return $existingElement;
            }

            // Give the product a status for the import's target site
            // (This is how the `custom` propagation method knows which sites the product should support.)
            $siteStatuses = ElementHelper::siteStatusesForElement($existingElement);
            $siteStatuses[$targetSiteId] = $existingElement->getEnabledForSite();
            $existingElement->setEnabledForSite($siteStatuses);

            // Propagate the product, and swap it with the propagated copy
            $propagatedElement = Craft::$app->getElements()->propagateElement($existingElement, $targetSiteId);

            // we need this so that the variants get propagated too
            $propagatedElement->setVariants($existingElement->getVariants());
            $propagatedElement->newSiteIds = [$targetSiteId];
            $propagatedElement->afterPropagate(false);

            // we're done propagating now
            $propagatedElement->propagating = false;
            $propagatedElement->propagatingFrom = null;

            return $propagatedElement;
        }

        return $existingElement;
    }

    /**
     * @inheritDoc
     */
    public function save($element, $settings): bool
    {
        $this->beforeSave($element, $settings);

        if ($this->element->getIsDraft()) {
            $this->element->markAsDirty();
            $this->element = Craft::$app->getDrafts()->applyDraft($this->element);
            $this->element->propagateAll = true;
        }

        if (!Craft::$app->getElements()->saveElement($this->element, true, true, Hash::get($this->feed, 'updateSearchIndexes'))) {
            $errors = [$this->element->getErrors()];

            if ($this->element->getErrors()) {
                foreach ($this->element->getVariants() as $variant) {
                    if ($variant->getErrors()) {
                        $errors[] = $variant->getErrors();
                    }
                }

                throw new Exception(Json::encode($errors));
            }

            return false;
        }

        return true;
    }


    // Private Methods
    // =========================================================================

    /**
     * @param $event
     */
    private function _preParseVariants($event): void
    {
        $feed = $event->feed;

        // We need to re-arrange the feed-mapping from using variant-* to putting all these in a
        // variants[] array for easy management later. If we don't do this, it'll start processing
        // attributes and fields based on the top-level product, which is incorrect..
        foreach ($feed['fieldMapping'] as $fieldHandle => $fieldInfo) {
            if (str_contains($fieldHandle, 'variant-')) {
                // Add it to variants[]
                $attribute = str_replace('variant-', '', $fieldHandle);
                $feed['fieldMapping']['variants'][$attribute] = $fieldInfo;

                // Remove it from top-level mapping
                unset($feed['fieldMapping'][$fieldHandle]);
            }
        }

        // Save all our changes back to the event model
        $event->feed = $feed;
    }

    /**
     * @param $event
     */
    private function _checkForVariantMatches($event): void
    {
        $feed = $event->feed;
        $feedData = $event->feedData;
        $contentData = $event->contentData;

        // If we're trying to match an existing product element on a variant's content, we're not going to have much
        // luck. So instead, in here, we look up the parent product (if any), and return that. We directly modify the
        // unique content array $contentData, so we don't have to deal with any other shenanigans in core code.
        foreach ($contentData as $handle => $value) {
            if (str_contains($handle, 'variant-')) {
                $sku = null;

                $fieldInfo = Hash::get($feed, 'fieldMapping.variant-sku');
                $node = Hash::get($fieldInfo, 'node');

                // Because we're trying to find the parent product from a child variant, we just need to get the first
                // match - then we've got an SKU for a variant that belongs to the product we want.
                foreach ($feedData as $nodePath => $innerValue) {
                    $feedPath = preg_replace('/(\/\d+\/)/', '/', $nodePath);
                    $feedPath = preg_replace('/^(\d+\/)|(\/\d+)/', '', $feedPath);

                    if ($feedPath === $node) {
                        $sku = $innerValue;
                        break;
                    }
                }

                if (!$sku) {
                    continue;
                }

                $variant = $this->_getVariantBySku($sku);

                // Now, we want to directly modify the unique fields to instead of using the variant SKU, use the
                // product id. Note that we want to force this, even if we haven't found a variant, because trying to import
                // using variant-sku as the unique identifier won't go down so well - it won't create the products like it should
                $feed['fieldUnique']['id'] = '1';
                $contentData['id'] = $variant->productId ?? 0;

                // Cleanup
                unset($feed['fieldUnique'][$handle], $contentData[$handle]);
            }
        }

        // Save all our changes back to the event model
        $event->feed = $feed;
        $event->feedData = $feedData;
        $event->contentData = $contentData;
    }

    /**
     * @param $event
     */
    private function _parseVariants($event): void
    {
        $feed = $event->feed;
        $feedData = $event->feedData;
        $contentData = $event->contentData;
        /** @var Product $element */
        $element = $event->element;

        $variantMapping = Hash::get($feed, 'fieldMapping.variants');

        // Check to see if there are any variants at all (there really should be...)
        if (!$variantMapping) {
            return;
        }

        $variantData = [];
        $variants = [];
        $complexFields = [];

        // Fetch any existing variants on the product, indexes by their SKU
        if (!empty($element->variants[0]['sku'])) {
            foreach ($element->variants as $value) {
                $variants[$value['sku']] = $value;
            }
        }

        // Weed out any non-variant mapped field
        $variantFieldsByNode = [];

        foreach (Hash::flatten($variantMapping) as $key => $value) {
            if (str_contains($key, 'node') && $value !== 'noimport' && $value !== 'usedefault') {
                $variantFieldsByNode[] = $value;
            }
        }

        // Now we need to find out how many variants we're importing - can even be one, and it's all a little tricky...
        foreach ($feedData as $nodePath => $value) {
            foreach ($variantMapping as $fieldHandle => $fieldInfo) {
                $node = Hash::get($fieldInfo, 'node');

                $feedPath = preg_replace('/(\/\d+\/)/', '/', $nodePath);
                $feedPath = preg_replace('/^(\d+\/)|(\/\d+)/', '', $feedPath);

                if (!in_array($feedPath, $variantFieldsByNode, true)) {
                    continue;
                }

                // Try and determine the index. We need to always be dealing with an array of variant data
                $nodePathSegments = explode('/', $nodePath);
                $variantIndex = Hash::get($nodePathSegments, 1);

                if (!is_numeric($variantIndex)) {
                    // Try to check if its only one-level deep (only importing one block type)
                    // which is particularly common for JSON.
                    $variantIndex = Hash::get($nodePathSegments, 2);

                    if (!is_numeric($variantIndex)) {
                        $variantIndex = 0;
                    }
                }

                $isNestedField = (in_array(Hash::get($fieldInfo, 'field'), [Matrix::class, Table::class]));

                if ($isNestedField === true) {
                    $complexFields[$variantIndex][$fieldHandle]['info'] = $fieldInfo;
                    $complexFields[$variantIndex][$fieldHandle]['data'][$nodePath] = $value;
                    continue;
                }

                // Find the node in the feed (stripped of indexes) that matches what's stored in field mapping
                if ($feedPath === $node) {
                    // Store this information, so we can parse the field data later
                    if (!isset($variantData[$variantIndex][$fieldHandle])) {
                        $variantData[$variantIndex][$fieldHandle] = $fieldInfo;
                    }

                    $variantData[$variantIndex][$fieldHandle]['data'][$nodePath] = $value;
                }
            }
        }

        // A separate loop to sort out any defaults we might have (they need to be applied to each variant)
        // even though the data supplied for them is only provided once.
        foreach ($variantMapping as $fieldHandle => $fieldInfo) {
            foreach ($variantData as $variantNumber => $variantContent) {
                $node = Hash::get($fieldInfo, 'node');
                $default = Hash::get($fieldInfo, 'default');

                if ($node === 'usedefault') {
                    $variantData[$variantNumber][$fieldHandle] = $fieldInfo;
                    $variantData[$variantNumber][$fieldHandle]['data'][$fieldHandle] = $default;
                }
            }
        }

        foreach ($complexFields as $variantNumber => $complexInfo) {
            foreach ($complexInfo as $fieldHandle => $fieldInfo) {
                $variantNodePathKey = null;

                // Refrain from looking at the whole nodepath, really just want to find the first bits
                foreach ($fieldInfo['data'] as $nodePath => $value) {
                    $nodePathExcerpt = implode('/', array_slice(explode('/', $nodePath), 0, 3));

                    preg_match('/^(.*)\d+\//U', $nodePathExcerpt, $matches);

                    $variantNodePathKey = Hash::get($matches, '1');

                    if ($variantNodePathKey) {
                        break;
                    }
                }

                // Likely, we've only got a single variant in our import, so we'll assume `variants/variant`
                if (!$variantNodePathKey) {
                    foreach ($fieldInfo['data'] as $nodePath => $value) {
                        $variantNodePathKey = implode('/', array_slice(explode('/', $nodePath), 0, 2)) . '/';
                        break;
                    }
                }

                $alteredData = [];

                foreach (Hash::flatten($fieldInfo) as $key => $value) {
                    $key = str_replace([$variantNodePathKey . $variantNumber . '/', $variantNodePathKey], '', $key);

                    $value = str_replace([$variantNodePathKey . $variantNumber . '/', $variantNodePathKey], '', $value);

                    $alteredData[$key] = $value;
                }

                $fieldInfo = Hash::expand($alteredData);

                $variantData[$variantNumber][$fieldHandle] = $fieldInfo['info'];
                $variantData[$variantNumber][$fieldHandle]['data'] = $fieldInfo['data'];
            }
        }

        $parseTwig = Plugin::$plugin->service->getConfig('parseTwig', $feed['id']);

        foreach ($variantData as $variantContent) {
            $attributeData = [];
            $fieldData = [];

            // Parse just the element attributes first. We use these in our field contexts, and need a fully-prepped element
            foreach ($variantContent as $fieldHandle => $fieldInfo) {
                if (Hash::get($fieldInfo, 'attribute')) {
                    $attributeValue = DataHelper::fetchValue(Hash::get($fieldInfo, 'data'), $fieldInfo, $this->feed);

                    $attributeData[$fieldHandle] = $parseTwig ? DataHelper::parseFieldDataForElement($attributeValue, $this->element) : $attributeValue;
                }
            }

            // If there's no SKU in the feed to process, we can't go any further, because we can very likely produce
            // errors if we try to import a variant that already has an SKU - instead we need to grab and edit it
            $sku = Hash::get($attributeData, 'sku');

            if (!$sku) {
                continue;
            }

            // Create a new variant, or find an existing one to edit
            if (!isset($variants[$sku])) {
                $variants[$sku] = new VariantElement();
                $variants[$sku]->setOwner($element);
            }

            // We are going to handle stock after the product and variants save
            $stock = null;
            if (isset($attributeData['stock'])) {
                $stock = $attributeData['stock'];
                unset($attributeData['stock']);
            }

            // Set the attributes for the element
            $variants[$sku]->setAttributes($attributeData, false);

            // Restore it to attribute data
            if ($stock !== null) {
                $attributeData['stock'] = $stock;
            }

            // Then, do the same for custom fields. Again, this should be done after populating the element attributes
            foreach ($variantContent as $fieldHandle => $fieldInfo) {
                if (Hash::get($fieldInfo, 'field')) {
                    $data = Hash::get($fieldInfo, 'data');

                    $fieldValue = Plugin::$plugin->fields->parseField($feed, $variants[$sku], $data, $fieldHandle, $fieldInfo);

                    if ($fieldValue !== null) {
                        $fieldData[$fieldHandle] = $fieldValue;
                    }
                }
            }

            // Do the same with our custom field data
            $variants[$sku]->setFieldValues($fieldData);

            // Add to our contentData variable for debugging
            $contentData['variants'][] = $attributeData + $fieldData;
        }

        // Set the products variants
        $element->setVariants($variants);

        // Save all our changes back to the event model
        $event->feed = $feed;
        $event->feedData = $feedData;
        $event->contentData = $contentData;
        $event->element = $element;
    }

    private function _inventoryUpdate($event): void
    {
        /** @var Product $product */
        $product = $event->element;

        // Index variants by SKU for lookup:
        $variantsBySku = ArrayHelper::index($event->contentData['variants'], 'sku');

        /** @var Commerce $commercePlugin */
        $commercePlugin = Commerce::getInstance();
        $variants = $product->getVariants();

        // Queue up a changeset:
        $updateInventoryLevels = UpdateInventoryLevelCollection::make();

        foreach ($variants as $variant) {
            // Is this SKU even present in our import data?
            if (!isset($variantsBySku[$variant->sku])) {
                continue;
            }

            if (!$variant->inventoryTracked) {
                Plugin::info(sprintf('Variant %s is not configured to track stock.', $variant->sku));

                continue;
            }

            $stock = $variantsBySku[$variant->sku]['stock'] ?? null;

            // What if the `stock` key wasn't in the import data?
            if (is_null($stock)) {
                Plugin::error(sprintf('No stock value was present in the import data for %s.', $variant->sku));

                continue;
            }

            // Load InventoryItem model:
            $inventoryItem = $commercePlugin->getInventory()->getInventoryItemByPurchasable($variant);

            /** @var InventoryLevel $firstInventoryLevel */
            $level = $commercePlugin->getInventory()->getInventoryLevelsForPurchasable($variant)->first();
            $location = $level->getInventoryLocation();

            if (!$level || !$location) {
                // Again, looks like there's nothing to track…
                continue;
            }

            $update = new UpdateInventoryLevel([
                'type' => \craft\commerce\enums\InventoryTransactionType::AVAILABLE->value,
                'updateAction' => \craft\commerce\enums\InventoryUpdateQuantityType::SET,
                'inventoryItem' => $inventoryItem,
                'inventoryLocation' => $location,
                'quantity' => $stock,
                'note' => sprintf('Imported via feed ID #%s', $event->feed['id']),
            ]);

            $updateInventoryLevels->push($update);

            Plugin::info(sprintf('Updating stock for the default inventory location for %s to %s.', $variant->sku, $stock));
        }

        if ($updateInventoryLevels->count() > 0) {
            Commerce::getInstance()->getInventory()->executeUpdateInventoryLevels($updateInventoryLevels);
        }
    }

    /**
     * @param $sku
     * @param null $siteId
     * @return VariantElement
     */
    private function _getVariantBySku($sku, $siteId = null): VariantElement
    {
        $variant = VariantElement::find()
            ->sku($sku)
            ->status(null)
            ->limit(null)
            ->typeId($this->element->typeId)
            ->siteId($siteId)
            ->one();

        if ($variant) {
            return $variant;
        }

        return new VariantElement();
    }


    // Protected Methods
    // =========================================================================

    /**
     * @param $feedData
     * @param $fieldInfo
     * @return array|Carbon|DateTime|false|string|null
     * @throws Exception
     */
    protected function parsePostDate($feedData, $fieldInfo): DateTime|bool|array|Carbon|string|null
    {
        $value = $this->fetchSimpleValue($feedData, $fieldInfo);
        $formatting = Hash::get($fieldInfo, 'options.match');

        return $this->parseDateAttribute($value, $formatting);
    }

    /**
     * @param $feedData
     * @param $fieldInfo
     * @return array|Carbon|DateTime|false|string|null
     * @throws Exception
     */
    protected function parseExpiryDate($feedData, $fieldInfo): DateTime|bool|array|Carbon|string|null
    {
        $value = $this->fetchSimpleValue($feedData, $fieldInfo);
        $formatting = Hash::get($fieldInfo, 'options.match');

        return $this->parseDateAttribute($value, $formatting);
    }

    /**
     * @param $feedData
     * @param $fieldInfo
     * @return bool|mixed|void
     */
    protected function parseAvailableForPurchase($feedData, $fieldInfo)
    {
        $value = $this->fetchSimpleValue($feedData, $fieldInfo);

        return BaseHelper::parseBoolean($value);
    }

    /**
     * @param $feedData
     * @param $fieldInfo
     * @return bool|mixed|void
     */
    protected function parseFreeShipping($feedData, $fieldInfo)
    {
        $value = $this->fetchSimpleValue($feedData, $fieldInfo);

        return BaseHelper::parseBoolean($value);
    }

    /**
     * @param $feedData
     * @param $fieldInfo
     * @return bool|mixed|void
     */
    protected function parsePromotable($feedData, $fieldInfo)
    {
        $value = $this->fetchSimpleValue($feedData, $fieldInfo);

        return BaseHelper::parseBoolean($value);
    }

    /**
     * @param $feedData
     * @param $fieldInfo
     * @return false|mixed
     */
    protected function parseTaxCategoryId($feedData, $fieldInfo): mixed
    {
        $value = $this->fetchSimpleValue($feedData, $fieldInfo);

        $query = (new Query())
            ->select(['*'])
            ->from(['{{%commerce_taxcategories}}']);

        // Find by ID
        $result = $query->where(['id' => $value])->one();

        // Find by Name
        if (!$result) {
            $result = $query->where(['name' => $value])->one();
        }

        // Find by Handle
        if (!$result) {
            $result = $query->where(['handle' => $value])->one();
        }

        if ($result) {
            return $result['id'];
        }

        return false;
    }

    /**
     * @param $feedData
     * @param $fieldInfo
     * @return false|mixed
     */
    protected function parseShippingCategoryId($feedData, $fieldInfo): mixed
    {
        $value = $this->fetchSimpleValue($feedData, $fieldInfo);

        $query = (new Query())
            ->select(['*'])
            ->from(['{{%commerce_shippingcategories}}']);

        // Find by ID
        $result = $query->where(['id' => $value])->one();

        // Find by Name
        if (!$result) {
            $result = $query->where(['name' => $value])->one();
        }

        // Find by Handle
        if (!$result) {
            $result = $query->where(['handle' => $value])->one();
        }

        if ($result) {
            return $result['id'];
        }

        return false;
    }
}
