<?php

namespace craft\feedme\fields;

use Cake\Utility\Hash;
use Craft;
use craft\base\Element as BaseElement;
use craft\elements\Category;
use craft\elements\Category as CategoryElement;
use craft\elements\conditions\ElementConditionInterface;
use craft\feedme\base\Field;
use craft\feedme\base\FieldInterface;
use craft\feedme\helpers\DataHelper;
use craft\feedme\helpers\FieldHelper;
use craft\feedme\models\FeedModel;
use craft\feedme\Plugin;
use craft\fields\BaseRelationField;
use craft\fields\Categories as CategoriesField;
use craft\helpers\Db;
use craft\helpers\ElementHelper;
use craft\helpers\Json;
use craft\services\ElementSources;

/**
 *
 * @property-read string $mappingTemplate
 */
class Categories extends Field implements FieldInterface
{
    // Properties
    // =========================================================================

    /**
     * @var string
     */
    public static string $name = 'Categories';

    /**
     * @var string
     */
    public static string $class = CategoriesField::class;

    /**
     * @var string
     */
    public static string $elementType = CategoryElement::class;

    // Templates
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function getMappingTemplate(): string
    {
        return 'feed-me/_includes/fields/categories';
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function parseField(): mixed
    {
        $value = $this->fetchArrayValue();
        $default = $this->fetchDefaultArrayValue();

        // if the mapped value is not set in the feed
        if ($value === null) {
            return null;
        }

        $match = Hash::get($this->fieldInfo, 'options.match', 'title');
        $specialMatchCase = in_array($match, ['title', 'slug']);

        // if value from the feed is empty and default is not set
        // return an empty array; no point bothering further;
        // but we need to allow for zero as a string ("0") value if we're matching by title or slug
        if (empty($default) && DataHelper::isArrayValueEmpty($value, $specialMatchCase)) {
            return [];
        }

        $source = Hash::get($this->field, 'settings.source');
        $maintainHierarchy = Hash::get($this->field, 'settings.maintainHierarchy');
        if ($maintainHierarchy) {
            $limit = Hash::get($this->field, 'settings.branchLimit');
        } else {
            $limit = Hash::get($this->field, 'settings.maxRelations');
        }

        $targetSiteId = Hash::get($this->field, 'settings.targetSiteId');
        $feedSiteId = Hash::get($this->feed, 'siteId');
        $create = Hash::get($this->fieldInfo, 'options.create');
        $fields = Hash::get($this->fieldInfo, 'fields');
        $node = Hash::get($this->fieldInfo, 'node');
        $nodeKey = null;

        $groupId = null;
        $customSource = null;
        // Get source id's for connecting
        if (str_starts_with($source, 'custom:')) {
            $customSource = ElementHelper::findSource(CategoryElement::class, $source, ElementSources::CONTEXT_FIELD);
            // make sure $create is nullified; we don't want to create categories for custom sources
            // because of ensuring all the conditions are met
            // for example, if there's condition level == 2, then how do we ensure that and (more importantly) how do we choose a parent
            $create = null;
        } else {
            [, $groupUid] = explode(':', $source);
            $groupId = Db::idByUid('{{%categorygroups}}', $groupUid);
        }

        $foundElements = [];

        foreach ($value as $dataValue) {
            // Prevent empty or blank values (string or array), which match all elements
            // but sometimes allow for zeros
            if (empty($dataValue) && empty($default) && ($specialMatchCase && !is_numeric($dataValue))) {
                continue;
            }

            // If we're using the default value - skip, we've already got an id array
            if ($node === 'usedefault') {
                $foundElements = $value;
                break;
            }

            // special provision for falling back on default BaseRelationField value
            // https://github.com/craftcms/feed-me/issues/1195
            if (DataHelper::isArrayValueEmpty($value)) {
                $foundElements = $default;
                break;
            }

            $query = CategoryElement::find();

            // In multi-site, there's currently no way to query across all sites - we use the current site
            // See https://github.com/craftcms/cms/issues/2854
            if (Craft::$app->getIsMultiSite()) {
                if ($targetSiteId) {
                    $criteria['siteId'] = Craft::$app->getSites()->getSiteByUid($targetSiteId)->id;
                } elseif ($feedSiteId) {
                    $criteria['siteId'] = $feedSiteId;
                } else {
                    $criteria['siteId'] = Craft::$app->getSites()->getCurrentSite()->id;
                }
            }

            if ($groupId) {
                $criteria['groupId'] = $groupId;
            }

            $criteria['status'] = null;
            $criteria['limit'] = $limit;
            $criteria[$match] = Db::escapeParam($dataValue);

            Craft::configure($query, $criteria);

            if (!empty($customSource)) {
                $conditionsService = Craft::$app->getConditions();
                /** @var ElementConditionInterface $sourceCondition */
                $sourceCondition = $conditionsService->createCondition($customSource['condition']);
                $sourceCondition->modifyQuery($query);
            }

            // we're getting the criteria from conditions now too, so they are not included in the $criteria array;
            // so, we get all the query criteria, filter out any empty or boolean ones and only show the ones that look to be filled out
            $showCriteria = $criteria;
            $allCriteria = $query->getCriteria();
            foreach ($allCriteria as $key => $criterion) {
                if (!empty($criterion) && !is_bool($criterion)) {
                    $showCriteria[$key] = $criterion;
                }
            }

            Plugin::info('Search for existing category with query `{i}`', ['i' => Json::encode($showCriteria)]);

            $ids = $query->ids();

            $foundElements = array_merge($foundElements, $ids);

            Plugin::info('Found `{i}` existing categories: `{j}`', ['i' => count($foundElements), 'j' => Json::encode($foundElements)]);

            // Check if we should create the element. But only if title is provided (for the moment)
            if ((count($ids) == 0) && $create && $match === 'title') {
                $foundElements[] = $this->_createElement($dataValue, $groupId);
            }

            $nodeKey = $this->getArrayKeyFromNode($node);
        }

        // Check for field limit - only return the specified amount
        if ($foundElements && $limit) {
            $foundElements = array_chunk($foundElements, $limit)[0];
        }

        // Check for any sub-fields for the element
        if ($fields) {
            $this->populateElementFields($foundElements, $nodeKey);
        }

        $foundElements = array_unique($foundElements);

        // if the field has maintainHierarchy on, and we're supposed to compare content,
        // we need to fill in the gaps, so that we know if the content has truly changed
        // https://github.com/craftcms/feed-me/issues/1418
        if ($foundElements && $maintainHierarchy && Plugin::$plugin->service->getConfig('compareContent', $this->feed['id'])) {
            $elements = CategoryElement::find()->id($foundElements)->all();
            Craft::$app->getStructures()->fillGapsInElements($elements);
            $foundElements = array_map(fn($element) => $element->id, $elements);
        }

        // Protect against sending an empty array - removing any existing elements
        if (!$foundElements) {
            return null;
        }

        return $foundElements;
    }

    /**
     * Returns an array of custom fields that can be used when querying for matching categories.
     *
     * If a field is passed, use the field layout linked to the source (category group) selected in the Categories field's settings.
     * If the source is native (it's a category group), only return the custom fields from its layout.
     * If the source is custom, return all the fields in the installation.
     *
     * @param FeedModel $feed
     * @param BaseRelationField|null $field
     * @return array
     */
    public static function getMatchFields(FeedModel $feed, ?BaseRelationField $field = null): array
    {
        // The field will be null e.g. when importing into a categories group with maintain hierarchy turned on;
        // in that case, there's the option to select a parent;
        // the parent is serviced by the categories field markup too, but it doesn't tie into a custom field per se;
        if ($field === null) {
            $categoryGroup = Craft::$app->getCategories()->getGroupById($feed->elementGroup[Category::class]);
            if (!$categoryGroup) {
                return FieldHelper::getAllUniqueIdFields();
            }
            $fieldLayout = Craft::$app->getFields()->getLayoutById($categoryGroup->fieldLayoutId);
            if (!$fieldLayout) {
                return FieldHelper::getAllUniqueIdFields();
            }

            return array_filter($fieldLayout->getCustomFields(), fn($field) => FieldHelper::fieldCanBeUniqueId($field));
        } else {
            // if the Categories field has only custom source - we have no choice but return all the field
            if (FieldHelper::fieldHasOnlyCustomSources($field)) {
                return FieldHelper::getAllUniqueIdFields();
            }
            // otherwise get the layout for the group selected in the field's settings
            return array_filter(
                FieldHelper::getElementLayoutByField($field::class, $field) ?? [],
                fn($field) => FieldHelper::fieldCanBeUniqueId($field)
            );
        }
    }

    // Private Methods
    // =========================================================================

    private function _createElement($dataValue, $groupId): ?int
    {
        $element = new CategoryElement();
        $element->title = $dataValue;
        $element->groupId = $groupId;

        $siteId = Hash::get($this->feed, 'siteId');

        if ($siteId) {
            $element->siteId = $siteId;
        }

        $element->setScenario(BaseElement::SCENARIO_ESSENTIALS);

        if (!Craft::$app->getElements()->saveElement($element, true, true, Hash::get($this->feed, 'updateSearchIndexes'))) {
            Plugin::error('`{handle}` - Category error: Could not create - `{e}`.', ['e' => Json::encode($element->getErrors()), 'handle' => $this->field->handle]);
        } else {
            Plugin::info('`{handle}` - Category `#{id}` added.', ['id' => $element->id, 'handle' => $this->field->handle]);
        }

        return $element->id;
    }
}
