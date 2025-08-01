<?php

namespace craft\feedme\fields;

use Cake\Utility\Hash;
use Craft;
use craft\base\Element as BaseElement;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\Entry;
use craft\elements\Entry as EntryElement;
use craft\errors\ElementNotFoundException;
use craft\feedme\base\Field;
use craft\feedme\base\FieldInterface;
use craft\feedme\helpers\DataHelper;
use craft\feedme\helpers\FieldHelper;
use craft\feedme\models\FeedModel;
use craft\feedme\Plugin;
use craft\fields\BaseRelationField;
use craft\fields\Entries as EntriesField;
use craft\helpers\Db;
use craft\helpers\ElementHelper;
use craft\helpers\Json;
use craft\services\ElementSources;
use Illuminate\Support\Collection;
use Throwable;
use yii\base\Exception;

/**
 *
 * @property-read string $mappingTemplate
 */
class Entries extends Field implements FieldInterface
{
    // Properties
    // =========================================================================

    /**
     * @var string
     */
    public static string $name = 'Entries';

    /**
     * @var string
     */
    public static string $class = EntriesField::class;

    /**
     * @var string
     */
    public static string $elementType = EntryElement::class;

    // Templates
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function getMappingTemplate(): string
    {
        return 'feed-me/_includes/fields/entries';
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

        $sources = Hash::get($this->field, 'settings.sources');
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

        $sectionIds = [];
        $customSources = [];

        if (is_array($sources)) {
            foreach ($sources as $source) {
                // When singles is selected as the only option to search in, it doesn't contain any ids...
                if ($source == 'singles') {
                    foreach (Craft::$app->getEntries()->getAllSections() as $section) {
                        $sectionIds[] = ($section->type == 'single') ? $section->id : '';
                    }
                } else {
                    // if the source starts with "custom:", it's a custom source, and we can't treat it like a section
                    if (str_starts_with($source, 'custom:')) {
                        $customSources[] = ElementHelper::findSource(EntryElement::class, $source, ElementSources::CONTEXT_FIELD);
                    } else {
                        [, $uid] = explode(':', $source);
                        $sectionIds[] = Db::idByUid('{{%sections}}', $uid);
                    }
                }
            }

            // if there's only one source, and it's a custom source, make sure $create is nullified;
            // we don't want to create entries for custom sources because of ensuring all the conditions are met
            if (count($sources) == 1 && !empty($customSources)) {
                $create = null;
            }
        } elseif ($sources === '*') {
            $sectionIds = null;
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

            $query = EntryElement::find();

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

            $criteria['status'] = null;
            $criteria['limit'] = $limit;
            $criteria[$match] = Db::escapeParam($dataValue);

            Craft::configure($query, $criteria);

            // if we have any custom sources, we want to modify the query to account for those
            if (!empty($customSources)) {
                $conditionsService = Craft::$app->getConditions();
                foreach ($customSources as $customSource) {
                    /** @var ElementConditionInterface $sourceCondition */
                    $sourceCondition = $conditionsService->createCondition($customSource['condition']);
                    $sourceCondition->modifyQuery($query);
                }
            }

            if (!empty($sectionIds)) {
                // now that the custom sources have been accounted for,
                // we can adjust the section id to include any regular, section sources (section ids)
                $query->sectionId = array_merge($query->sectionId ?? [], $sectionIds);
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

            Plugin::info('Search for existing entry with query `{i}`', ['i' => Json::encode($showCriteria)]);

            $ids = $query->ids();

            $foundElements = array_merge($foundElements, $ids);

            Plugin::info('Found `{i}` existing entries: `{j}`', ['i' => count($foundElements), 'j' => Json::encode($foundElements)]);

            // Check if we should create the element. But only if title is provided (for the moment)
            if ((count($ids) == 0) && $create && $match === 'title') {
                $foundElements[] = $this->_createElement($dataValue);
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
            // get elements by IDs
            $elements = EntryElement::find()->id($foundElements)->all();
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
     * Returns an array of custom fields that can be used when querying for matching entries.
     *
     * If a field is passed, use the field layouts linked to the sources allowed by the Entries field.
     * If all the sources are native (sections), then only fields from all those sections entry types field layouts will be returned.
     * If there's at least one custom source in the mix, the above list will be followed by a list of all the fields.
     * If only custom sources are selected, return all the fields in the installation.
     *
     * @param FeedModel $feed
     * @param BaseRelationField|null $field
     * @return array
     */
    public static function getMatchFields(FeedModel $feed, ?BaseRelationField $field = null): array
    {
        // The field will be null e.g. when importing into a structure section and there's the option to select a parent
        // the parent is serviced by the entries field markup too, but it doesn't tie into a custom field per se;
        if ($field === null) {
            $entryType = Craft::$app->getEntries()->getEntryTypeById($feed->elementGroup[Entry::class]['entryType']);
            if (!$entryType) {
                return FieldHelper::getAllUniqueIdFields();
            }

            $fieldLayout = Craft::$app->getFields()->getLayoutById($entryType->fieldLayoutId);
            if (!$fieldLayout) {
                return FieldHelper::getAllUniqueIdFields();
            }

            return array_filter(
                $fieldLayout->getCustomFields(),
                fn($field) => FieldHelper::fieldCanBeUniqueId($field)
            );
        } else {
            // if the Entries field has only custom sources - we have no choice but return all the field
            if (FieldHelper::fieldHasOnlyCustomSources($field)) {
                return FieldHelper::getAllUniqueIdFields();
            }

            // deal with the native sources - sections
            $sections = FieldHelper::getEntrySourcesByField($field);
            $entryTypes = [];
            foreach ($sections as $section) {
                $entryTypes = [...$entryTypes, ...$section->getEntryTypes()];
            }

            $allowedFields = [];
            $entryTypes = Collection::make($entryTypes)->keyBy('id');

            foreach ($entryTypes as $entryType) {
                $fieldLayout = Craft::$app->getFields()->getLayoutById($entryType->fieldLayoutId);
                $allowedFields = [...$allowedFields, ...$fieldLayout->getCustomFields()];
            }

            // if there's a custom source in the mix, we should add all the fields too
            $customSources = [];
            if (is_array($field['sources'])) {
                $customSources = array_filter($field['sources'], (fn(string $source) => str_starts_with($source, 'custom:')));
            }

            if (!empty($customSources)) {
                $allowedFields = [...$allowedFields, ...Craft::$app->getFields()->getAllFields()];
            }

            return array_filter($allowedFields, fn($field) => FieldHelper::fieldCanBeUniqueId($field));
        }
    }


    // Private Methods
    // =========================================================================

    /**
     * @param $dataValue
     * @return int|null
     * @throws Throwable
     * @throws ElementNotFoundException
     * @throws Exception
     */
    private function _createElement($dataValue): ?int
    {
        $sectionId = Hash::get($this->fieldInfo, 'options.group.sectionId');
        $typeId = Hash::get($this->fieldInfo, 'options.group.typeId');

        // Bit of backwards-compatibility here, if not explicitly set, grab the first globally
        if (!$sectionId) {
            $sectionId = Craft::$app->getEntries()->getAllSectionIds()[0];
        }

        if (!$typeId) {
            $typeId = Craft::$app->getEntries()->getEntryTypesBySectionId($sectionId)[0]->id;
        }

        $element = new EntryElement();
        $element->title = $dataValue;
        $element->sectionId = $sectionId;
        $element->typeId = $typeId;

        $siteId = Hash::get($this->feed, 'siteId');
        $section = Craft::$app->getEntries()->getSectionById($element->sectionId);

        if ($siteId) {
            $element->siteId = $siteId;

            // Set the default site status based on the section's settings
            foreach ($section->getSiteSettings() as $siteSettings) {
                if ($siteSettings->siteId == $siteId) {
                    $element->enabledForSite = $siteSettings->enabledByDefault;
                    break;
                }
            }
        } else {
            // Set the default entry status based on the section's settings
            foreach ($section->getSiteSettings() as $siteSettings) {
                if (!$siteSettings->enabledByDefault) {
                    $element->enabled = false;
                }

                break;
            }
        }

        $element->setScenario(BaseElement::SCENARIO_ESSENTIALS);

        if (!Craft::$app->getElements()->saveElement($element, true, true, Hash::get($this->feed, 'updateSearchIndexes'))) {
            Plugin::error('`{handle}` - Entry error: Could not create - `{e}`.', ['e' => Json::encode($element->getErrors()), 'handle' => $this->field->handle]);
        } else {
            Plugin::info('`{handle}` - Entry `#{id}` added.', ['id' => $element->id, 'handle' => $this->field->handle]);
        }

        return $element->id;
    }
}
