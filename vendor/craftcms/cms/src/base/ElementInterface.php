<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use craft\behaviors\CustomFieldBehavior;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\db\EagerLoadPlan;
use craft\elements\db\ElementQueryInterface;
use craft\elements\ElementCollection;
use craft\elements\User;
use craft\enums\AttributeStatus;
use craft\errors\InvalidFieldException;
use craft\models\FieldLayout;
use craft\models\Site;
use GraphQL\Type\Definition\Type;
use Twig\Markup;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;
use yii\web\Response;

/**
 * ElementInterface defines the common interface to be implemented by element classes.
 * A class implementing this interface should also use [[ElementTrait]].
 *
 * @mixin ElementTrait
 * @mixin CustomFieldBehavior
 * @mixin Component
 * @phpstan-require-extends Element
 * @phpstan-type EagerLoadingMapItem array{elementType?:class-string<ElementInterface>,source:int,target:int}
 * @phpstan-type EagerLoadingMap array{elementType?:class-string<ElementInterface>,map:EagerLoadingMapItem[],criteria?:array,createElement?:callable}
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
interface ElementInterface extends
    ComponentInterface,
    Chippable,
    CpEditable,
    Thumbable,
    Statusable,
    Actionable
{
    /**
     * Returns the lowercase version of [[displayName()]].
     *
     * @return string
     * @since 3.3.17
     */
    public static function lowerDisplayName(): string;

    /**
     * Returns the plural version of [[displayName()]].
     *
     * @return string
     * @since 3.2.0
     */
    public static function pluralDisplayName(): string;

    /**
     * Returns the plural, lowercase version of [[displayName()]].
     *
     * @return string
     * @since 3.3.17
     */
    public static function pluralLowerDisplayName(): string;

    /**
     * Returns the handle that should be used to refer to this element type from reference tags.
     *
     * @return string|null The reference handle, or null if the element type doesn’t support reference tags
     */
    public static function refHandle(): ?string;

    /**
     * Returns whether element indexes should show the “Drafts” status option.
     *
     * @return bool
     * @since 5.0.0
     */
    public static function hasDrafts(): bool;

    /**
     * Returns whether Craft should keep track of attribute and custom field changes made to this element type,
     * including when the last time they were changed, and who was logged-in at the time.
     *
     * @return bool Whether to track changes made to elements of this type.
     * @see getDirtyAttributes()
     * @see getDirtyFields()
     * @since 3.4.0
     */
    public static function trackChanges(): bool;

    /**
     * Returns whether elements of this type have traditional titles.
     *
     * @return bool Whether elements of this type have traditional titles.
     */
    public static function hasTitles(): bool;

    /**
     * Returns whether element indexes should include a thumbnail view by default.
     *
     * @return bool
     * @since 5.0.0
     */
    public static function hasThumbs(): bool;

    /**
     * Returns whether elements of this type can have their own slugs and URIs.
     *
     * Note that individual elements must also return a URI format from [[getUriFormat()]] if they are to actually get a URI.
     *
     * @return bool Whether elements of this type can have their own slugs and URIs.
     * @see getUriFormat()
     */
    public static function hasUris(): bool;

    /**
     * Returns whether elements of this type store content on a per-site basis.
     *
     * If this returns `true`, the element’s [[getSupportedSites()]] method will
     * be responsible for defining which sites its content should be stored in.
     *
     * @return bool Whether elements of this type store data on a per-site basis.
     */
    public static function isLocalized(): bool;

    /**
     * Returns whether elements of this type have statuses.
     *
     * If this returns `true`, the element index template will show a Status menu by default, and your elements will
     * get status indicator icons next to them.
     * Use [[statuses()]] to customize which statuses the elements might have.
     *
     * @return bool Whether elements of this type have statuses.
     * @see statuses()
     */
    public static function hasStatuses(): bool;

    /**
     * Creates an [[ElementQueryInterface]] instance for query purpose.
     *
     * The returned [[ElementQueryInterface]] instance can be further customized by calling
     * methods defined in [[ElementQueryInterface]] before `one()` or `all()` is called to return
     * populated [[ElementInterface]] instances. For example,
     *
     * ```php
     * // Find the entry whose ID is 5
     * $entry = Entry::find()->id(5)->one();
     * // Find all assets and order them by their filename:
     * $assets = Asset::find()
     *     ->orderBy('filename')
     *     ->all();
     * ```
     *
     * If you want to define custom criteria parameters for your elements, you can do so by overriding
     * this method and returning a custom query class. For example,
     *
     * ```php
     * class Product extends Element
     * {
     *     public static function find(): ElementQueryInterface
     *     {
     *         // use ProductQuery instead of the default ElementQuery
     *         return new ProductQuery(get_called_class());
     *     }
     * }
     * ```
     *
     * You can also set default criteria parameters on the ElementQuery if you don’t have a need for
     * a custom query class. For example,
     *
     * ```php
     * class Customer extends ActiveRecord
     * {
     *     public static function find(): ElementQueryInterface
     *     {
     *         return parent::find()->limit(50);
     *     }
     * }
     * ```
     *
     * @return ElementQueryInterface The newly created [[ElementQueryInterface]] instance.
     */
    public static function find(): ElementQueryInterface;

    /**
     * Returns a single element instance by a primary key or a set of element criteria parameters.
     *
     * The method accepts:
     *
     *  - an int: query by a single ID value and return the corresponding element (or null if not found).
     *  - an array of name-value pairs: query by a set of parameter values and return the first element
     *    matching all of them (or null if not found).
     *
     * Note that this method will automatically call the `one()` method and return an
     * [[ElementInterface|\craft\base\Element]] instance. For example,
     *
     * ```php
     * // find a single entry whose ID is 10
     * $entry = Entry::findOne(10);
     * // the above code is equivalent to:
     * $entry = Entry::find->id(10)->one();
     * // find the first user whose email ends in "example.com"
     * $user = User::findOne(['email' => '*example.com']);
     * // the above code is equivalent to:
     * $user = User::find()->email('*example.com')->one();
     * ```
     *
     * @param mixed $criteria The element ID or a set of element criteria parameters
     * @return static|null Element instance matching the condition, or null if nothing matches.
     */
    public static function findOne(mixed $criteria = null): ?static;

    /**
     * Returns a list of elements that match the specified ID(s) or a set of element criteria parameters.
     *
     * The method accepts:
     *
     *  - an int: query by a single ID value and return an array containing the corresponding element
     *    (or an empty array if not found).
     *  - an array of integers: query by a list of ID values and return the corresponding elements (or an
     *    empty array if none was found).
     *    Note that an empty array will result in an empty result as it will be interpreted as a search for
     *    primary keys and not an empty set of element criteria parameters.
     *  - an array of name-value pairs: query by a set of parameter values and return an array of elements
     *    matching all of them (or an empty array if none was found).
     *
     * Note that this method will automatically call the `all()` method and return an array of
     * [[ElementInterface|\craft\base\Element]] instances. For example,
     *
     * ```php
     * // find the entries whose ID is 10
     * $entries = Entry::findAll(10);
     * // the above code is equivalent to:
     * $entries = Entry::find()->id(10)->all();
     * // find the entries whose ID is 10, 11 or 12.
     * $entries = Entry::findAll([10, 11, 12]);
     * // the above code is equivalent to:
     * $entries = Entry::find()->id([10, 11, 12]])->all();
     * // find users whose email ends in "example.com"
     * $users = User::findAll(['email' => '*example.com']);
     * // the above code is equivalent to:
     * $users = User::find()->email('*example.com')->all();
     * ```
     *
     * @param mixed $criteria The element ID, an array of IDs, or a set of element criteria parameters
     * @return static[] an array of Element instances, or an empty array if nothing matches.
     */
    public static function findAll(mixed $criteria = null): array;

    /**
     * Returns an element condition for the element type.
     *
     * @return ElementConditionInterface
     * @since 4.0.0
     */
    public static function createCondition(): ElementConditionInterface;

    /**
     * Returns the source definitions that elements of this type may belong to.
     *
     * This defines what will show up in the source list on element indexes and element selector modals.
     *
     * Each item in the array should be set to an array that has the following keys:
     * - **`key`** – The source’s key. This is the string that will be passed into the $source argument of [[actions()]],
     *   [[indexHtml()]], and [[defaultTableAttributes()]].
     * - **`label`** – The human-facing label of the source.
     * - **`status`** – The status color that should be shown beside the source label. Possible values include `green`,
     *   `orange`, `red`, `yellow`, `pink`, `purple`, `blue`, `turquoise`, `light`, `grey`, `black`, and `white`. (Optional)
     * - **`badgeCount`** – The badge count that should be displayed alongside the label. (Optional)
     * - **`badgeLabel`** – The badge count label that should be provided for screen readers. (Optional)
     * - **`sites`** – An array of site IDs or UUIDs that the source should be shown for, on multi-site element indexes.
     *   (Optional; by default the source will be shown for all sites.)
     * - **`criteria`** – An array of element criteria parameters that the source should use when the source is selected.
     *   (Optional)
     * - **`data`** – An array of `data-X` attributes that should be set on the source’s `<a>` tag in the source list’s,
     *   HTML, where each key is the name of the attribute (without the “data-” prefix), and each value is the value of
     *   the attribute. (Optional)
     * - **`defaultSort`** – A string identifying the sort attribute that should be selected by default, or an array where
     *   the first value identifies the sort attribute, and the second determines which direction to sort by. (Optional)
     * - **`defaultFilter`** – An element condition instance or config, which should be used by default when the source
     *   is first selected.
     * - **`hasThumbs`** – A bool that defines whether this source supports Thumbs View. (Use your element’s
     *   [[getThumbUrl()]] method to define your elements’ thumb URL.) (Optional)
     * - **`structureId`** – The ID of the Structure that contains the elements in this source. If set, Structure View
     *   will be available to this source. (Optional)
     * - **`newChildUrl`** – The URL that should be loaded when a user selects the “New child” menu option on an
     *   element in this source while it is in Structure View. (Optional)
     * - **`nested`** – An array of sources that are nested within this one. Each nested source can have the same keys
     *   as top-level sources.
     *
     * ::: tip
     * Element types that extend [[\craft\base\Element]] should override [[\craft\base\Element::defineSources()]]
     * instead of this method.
     * :::
     *
     * @param string $context The context ('index', 'modal', 'field', or 'settings').
     * @return array The sources.
     */
    public static function sources(string $context): array;

    /**
     * Returns a source definition by a given source key/path and context.
     *
     * @param string $sourceKey
     * @param string|null $context
     * @return array|null
     * @since 4.4.0
     */
    public static function findSource(string $sourceKey, ?string $context): ?array;

    /**
     * Returns the source path for a given source key, step key, and context.
     *
     * @param string $sourceKey
     * @param string $stepKey
     * @param string|null $context
     * @return array[]|null
     * @since 4.4.12
     */
    public static function sourcePath(string $sourceKey, string $stepKey, ?string $context): ?array;

    /**
     * Returns all the field layouts associated with elements from the given source.
     *
     * This is used to determine which custom fields should be included in the element index sort menu,
     * and other things.
     *
     * @param string|null $source The selected source’s key, or `null` if all known field layouts should be returned
     * @return FieldLayout[]
     * @since 3.5.0
     */
    public static function fieldLayouts(?string $source): array;

    /**
     * Modifies a custom source’s config, before it’s returned by [[craft\services\ElementSources::getSources()]]
     *
     * @param array $config
     * @return array
     * @since 4.5.0
     */
    public static function modifyCustomSource(array $config): array;

    /**
     * Returns the available [bulk element actions](https://craftcms.com/docs/5.x/extend/element-actions.html)
     * for a given source.
     *
     * The actions can be represented by their fully qualified class name, a config array with the class name
     * set to a `type` key, or by an instantiated element action object.
     *
     * ::: tip
     * Element types that extend [[\craft\base\Element]] should override [[\craft\base\Element::defineActions()]]
     * instead of this method.
     * :::
     *
     * @param string $source The selected source’s key.
     * @return array The available bulk element actions.
     * @phpstan-return array<ElementActionInterface|class-string<ElementActionInterface>|array{type:class-string<ElementActionInterface>}>
     */
    public static function actions(string $source): array;

    /**
     * Returns the available export options for a given source.
     *
     * The exporters can be represented by their fully qualified class name, a config array with the class name
     * set to a `type` key, or by an instantiated element exporter object.
     *
     * ::: tip
     * Element types that extend [[\craft\base\Element]] should override [[\craft\base\Element::defineExporters()]]
     * instead of this method.
     * :::
     *
     * @param string $source The selected source’s key.
     * @return array The available element exporters.
     * @since 3.4.0
     */
    public static function exporters(string $source): array;

    /**
     * Defines which element attributes should be searchable.
     *
     * This method should return an array of attribute names that can be accessed on your elements.
     * [[\craft\services\Search]] will call this method when it is indexing keywords for one of your elements,
     * and for each attribute it returns, it will fetch the corresponding property’s value on the element.
     * For example, if your elements have a “color” attribute which you want to be indexed, this method could return:
     *
     * ```php
     * return ['color'];
     * ```
     *
     * Not only will the “color” attribute’s values start getting indexed, but users will also be able to search
     * directly against that attribute’s values using this search syntax:
     *
     *     color:blue
     *
     * There is no need for this method to worry about the ‘title’ or ‘slug’ attributes, or custom field handles;
     * those are indexed automatically.
     *
     * ::: tip
     * Element types that extend [[\craft\base\Element]] should override
     * [[\craft\base\Element::defineSearchableAttributes()]] instead of this method.
     * :::
     *
     * @return string[] The element attributes that should be searchable
     */
    public static function searchableAttributes(): array;

    /**
     * Returns the base attributes that should be applied when bulk-duplicating elements of this type.
     *
     * @return array
     * @since 5.7.0
     */
    public static function baseBulkDuplicateAttributes(): array;

    /**
     * Returns the element index HTML.
     *
     * @param ElementQueryInterface $elementQuery
     * @param int[]|null $disabledElementIds
     * @param array $viewState
     * @param string|null $sourceKey
     * @param string|null $context
     * @param bool $includeContainer
     * @param bool $selectable
     * @param bool $sortable
     * @return string The element index HTML
     */
    public static function indexHtml(
        ElementQueryInterface $elementQuery,
        ?array $disabledElementIds,
        array $viewState,
        ?string $sourceKey,
        ?string $context,
        bool $includeContainer,
        bool $selectable,
        bool $sortable,
    ): string;

    /**
     * Returns the total number of elements that will be shown on an element index, for the given element query.
     *
     * @param ElementQueryInterface $elementQuery
     * @param string|null $sourceKey
     * @return int
     * @since 4.4.0
     */
    public static function indexElementCount(ElementQueryInterface $elementQuery, ?string $sourceKey): int;

    /**
     * Returns the sort options for the element type.
     *
     * This method should return an array, where each item is a sub-array with the following keys:
     *
     * - `label` – The sort option label
     * - `orderBy` – An array, comma-delimited string, or a callback function that defines the columns to order the query by. If set to a callback
     *   function, the function will be passed two arguments: `$dir` (either `SORT_ASC` or `SORT_DESC`) and `$db` (a [[\craft\db\Connection]] object),
     *   and it should return an array of column names or an [[\yii\db\ExpressionInterface]] object.
     * - `attribute` _(optional)_ – The [[tableAttributes()|table attribute]] name that this option is associated
     *   with (required if `orderBy` is an array or more than one column name)
     * - `defaultDir` _(optional)_ – The default sort direction that should be used when sorting by this option
     *   (set to either `asc` or `desc`). Defaults to `asc` if not specified.
     *
     * ```php
     * return [
     *     [
     *         'label' => Craft::t('app', 'Attribute Label'),
     *         'orderBy' => 'columnName',
     *         'attribute' => 'attributeName',
     *         'defaultDir' => 'asc',
     *     ],
     * ];
     * ```
     *
     * A shorthand syntax is also supported, if there is no corresponding table attribute, or the table attribute
     * has the exact same name as the column.
     *
     * ```php
     * return [
     *     'columnName' => Craft::t('app', 'Attribute Label'),
     * ];
     * ```
     *
     * Note that this method will only get called once for the entire index; not each time that a new source is
     * selected.
     *
     * @return array The attributes that elements can be sorted by
     */
    public static function sortOptions(): array;

    /**
     * Returns the view modes available for the element type.
     *
     * This method should return an array, where each item is a sub-array with the following keys:
     *
     * - `mode` – Name of the view mode
     * - `title` – How this mode should be described to the user
     * - `icon` – Icon representing this view mode
     * - `availableOnMobile` - Whether the view mode is available on mobile devices (defaults to `true`)
     * - `structuresOnly` – Whether the view mode should only be available for structured sources (defaults to `false`)
     *
     * ```php
     * return [
     *     [
     *         'mode' => 'table',
     *         'title' => Craft::t('app', 'Display in a table'),
     *         'icon' => 'list',
     *         'availableOnMobile' => false,
     *     ],
     * ];
     *  ```
     *
     * @return array The view modes.
     * @since 5.5.0
     */
    public static function indexViewModes(): array;

    /**
     * Defines all of the available columns that can be shown in table views.
     *
     * This method should return an array whose keys represent element attribute names, and whose values make
     * up the table’s column headers.
     *
     * @return array The table attributes.
     */
    public static function tableAttributes(): array;

    /**
     * Returns the list of table attribute keys that should be shown by default.
     *
     * This method should return an array where each element in the array maps to one of the keys of the array returned
     * by [[tableAttributes()]].
     *
     * @param string $source The selected source’s key
     * @return string[] The table attribute keys
     */
    public static function defaultTableAttributes(string $source): array;

    /**
     * Defines all the available attributes that can be shown in card views.
     *
     * This method should return an array whose keys represent element attribute names, and whose values make
     * up the table’s column headers.
     *
     * @return array The card attributes.
     * @since 5.5.0
     */
    public static function cardAttributes(): array;

    /**
     * Returns the list of card attribute keys that should be shown by default, if the field layout hasn't been customised.
     *
     * This method should return an array where each element in the array maps to one of the keys of the array returned
     * by [[cardAttributes()]].
     *
     * @return string[] The card attribute keys
     * @since 5.5.0
     */
    public static function defaultCardAttributes(): array;

    /**
     * Return HTML for the attribute in the card preview.
     *
     * @param array $attribute
     * @return mixed
     * @since 5.5.0
     */
    public static function attributePreviewHtml(array $attribute): mixed;

    /**
     * Returns an array that maps source-to-target element IDs based on the given sub-property handle.
     *
     * This method aids in the eager-loading of elements when performing an element query. The returned array should
     * contain the following keys:
     * - `map` – an array defining source-target element mappings
     * - `elementType` *(optional)* – the fully qualified class name of the element type that should be eager-loaded,
     *   if each target element is of the same element type
     * - `criteria` *(optional)* – any criteria parameters that should be applied to the element query when fetching the
     *   eager-loaded elements
     * - `createElement` *(optional)* - an element factory function, which will be passed the element query, the current
     *   query result data, and the first source element that the result was eager-loaded for
     *
     * Each mapping listed in `map` should be an array with the following keys:
     * - `source` – the source element ID
     * - `target` – the target element ID
     * - `elementType` *(optional)* – the target element type (only checked for if the top-level array doesn’t specify
     *   an `elementType` key)
     *
     * ```php
     * use craft\base\ElementInterface;
     * use craft\db\Query;
     *
     * public static function eagerLoadingMap(array $sourceElements, string $handle)
     * {
     *     switch ($handle) {
     *         case 'author':
     *             $bookIds = array_map(fn(ElementInterface $element) => $element->id, $sourceElements);
     *             $map = (new Query)
     *                 ->select(['source' => 'id', 'target' => 'authorId'])
     *                 ->from('{{%books}}')
     *                 ->where(['id' => $bookIds)
     *                 ->all();
     *             return [
     *                 'elementType' => \my\plugin\Author::class,
     *                 'map' => $map,
     *             ];
     *         case 'bookClubs':
     *             $bookIds = array_map(fn(ElementInterface $element) => $element->id, $sourceElements);
     *             $map = (new Query)
     *                 ->select(['source' => 'bookId', 'target' => 'clubId'])
     *                 ->from('{{%bookclub_books}}')
     *                 ->where(['bookId' => $bookIds)
     *                 ->all();
     *             return [
     *                 'elementType' => \my\plugin\BookClub::class,
     *                 'map' => $map,
     *             ];
     *         default:
     *             return parent::eagerLoadMap($sourceElements, $handle);
     *     }
     * }
     * ```
     *
     * Alternatively, the method can return an array of multiple sets of mappings, each with their own nested `map`,
     * `elementType`, `criteria`, and `createElement` keys.
     *
     * @param self[] $sourceElements An array of the source elements
     * @param string $handle The property handle used to identify which target elements should be included in the map
     * @return EagerLoadingMap|EagerLoadingMap[]|null|false The eager-loading element ID mappings, false if no mappings
     * exist, or null if the result should be ignored
     */
    public static function eagerLoadingMap(array $sourceElements, string $handle): array|null|false;

    /**
     * Returns the base GraphQL type name that represents elements of this type.
     *
     * @return Type
     * @since 5.7.0
     */
    public static function baseGqlType(): Type;

    /**
     * Returns the GraphQL scopes required by element’s context.
     *
     * @param mixed $context The element’s context, such as a volume, entry type or Matrix block type.
     * @return array
     * @since 3.3.0
     */
    public static function gqlScopesByContext(mixed $context): array;

    /**
     * Returns whether this is a draft.
     *
     * @return bool
     * @since 3.2.0
     */
    public function getIsDraft(): bool;

    /**
     * Returns whether this is a revision.
     *
     * @return bool
     * @since 3.2.0
     */
    public function getIsRevision(): bool;

    /**
     * Returns whether this is the canonical element.
     *
     * @return bool
     * @since 3.7.0
     */
    public function getIsCanonical(): bool;

    /**
     * Returns whether this is a derivative element, such as a draft or revision.
     *
     * @return bool
     * @since 3.7.0
     */
    public function getIsDerivative(): bool;

    /**
     * Returns the canonical version of the element.
     *
     * If this is a draft or revision, the canonical element will be returned.
     *
     * @param bool $anySite Whether the canonical element can be retrieved in any site
     * @return self
     * @since 3.7.0
     */
    public function getCanonical(bool $anySite = false): self;

    /**
     * Sets the canonical version of the element.
     *
     * @param self $element
     * @since 3.7.0
     */
    public function setCanonical(self $element): void;

    /**
     * Returns the element’s canonical ID.
     *
     * If this is a draft or revision, the canonical element’s ID will be returned.
     *
     * @return int|null
     * @since 3.7.0
     */
    public function getCanonicalId(): ?int;

    /**
     * Sets the element’s canonical ID.
     *
     * @param int|null $canonicalId
     * @since 3.7.0
     */
    public function setCanonicalId(?int $canonicalId): void;

    /**
     * Returns the element’s canonical UUID.
     *
     * If this is a draft or revision, the canonical element’s UUID will be returned.
     *
     * @return string|null
     * @since 3.7.11
     */
    public function getCanonicalUid(): ?string;

    /**
     * Returns whether the element is an unpublished draft.
     *
     * @return bool
     * @since 3.6.0
     */
    public function getIsUnpublishedDraft(): bool;

    /**
     * Merges changes from the canonical element into this one.
     *
     * @see \craft\services\Elements::mergeCanonicalChanges()
     * @since 3.7.0
     */
    public function mergeCanonicalChanges(): void;

    /**
     * Returns the field layout used by this element.
     *
     * @return FieldLayout|null
     */
    public function getFieldLayout(): ?FieldLayout;

    /**
     * Returns the site the element is associated with.
     *
     * @return Site
     */
    public function getSite(): Site;

    /**
     * Returns the language of the element.
     *
     * @return string
     * @since 3.5.0
     */
    public function getLanguage(): string;

    /**
     * Returns the sites this element is associated with.
     *
     * The function can either return an array of site IDs, or an array of sub-arrays,
     * each with the following keys:
     *
     * - `siteId` (integer) - The site ID
     * - `propagate` (boolean) – Whether the element should be propagated to this site on save (`true` by default)
     * - `enabledByDefault` (boolean) – Whether the element should be enabled in this site by default
     *   (`true` by default)
     *
     * @return array
     */
    public function getSupportedSites(): array;

    /**
     * Returns the URI format used to generate this element’s URI.
     *
     * Note that element types that can have URIs must return `true` from [[hasUris()]].
     *
     * @return string|null
     * @see hasUris()
     * @see getRoute()
     */
    public function getUriFormat(): ?string;

    /**
     * Returns the search keywords for a given search attribute.
     *
     * @param string $attribute
     * @return string
     */
    public function getSearchKeywords(string $attribute): string;

    /**
     * Returns the route that should be used when the element’s URI is requested.
     *
     * ::: tip
     * Element types that extend [[\craft\base\Element]] should override [[\craft\base\Element::route()]]
     * instead of this method.
     * :::
     *
     * @return mixed The route that the request should use, or null if no special action should be taken
     */
    public function getRoute(): mixed;

    /**
     * Returns whether this element represents the site homepage.
     *
     * @return bool
     * @since 3.3.6
     */
    public function getIsHomepage(): bool;

    /**
     * Returns the element’s full URL.
     *
     * @return string|null
     */
    public function getUrl(): ?string;

    /**
     * Returns an anchor pre-filled with this element’s URL and title.
     *
     * @return Markup|null
     */
    public function getLink(): ?Markup;

    /**
     * Returns the breadcrumbs that lead up to the element.
     *
     * @return array
     * @since 5.0.0
     */
    public function getCrumbs(): array;

    /**
     * Defines what the element should be called within the control panel.
     *
     * @param string|null $label
     * @since 3.6.3
     */
    public function setUiLabel(?string $label): void;

    /**
     * Returns any path segment labels that should be prepended to the element’s UI label.
     *
     * @return string[]
     * @since 4.4.0
     */
    public function getUiLabelPath(): array;

    /**
     * Defines any path segment labels that should be prepended to the element’s UI label.
     *
     * @param string[] $path
     * @since 4.4.0
     */
    public function setUiLabelPath(array $path): void;

    /**
     * Returns the label HTML for element chips.
     *
     * @return string
     * @since 5.0.0
     */
    public function getChipLabelHtml(): string;

    /**
     * Returns whether chips and cards for this element should include a status indicator.
     *
     * @return bool
     * @since 5.4.0
     */
    public function showStatusIndicator(): bool;

    /**
     * Returns the titlebar label for element cards.
     *
     * @return string|null
     * @since 5.7.0
     */
    public function getCardTitle(): ?string;

    /**
     * Returns the body HTML for element cards.
     *
     * @return string|null
     * @since 5.0.0
     */
    public function getCardBodyHtml(): ?string;

    /**
     * Returns the reference string to this element.
     *
     * @return string|null
     */
    public function getRef(): ?string;

    /**
     * Creates a new element (without saving it) based on this one.
     *
     * This will be called by the “Save and add another” action on the element’s edit page.
     *
     * Note that permissions don’t need to be considered here. The created element’s [[canSave()]] method will be called before saving.
     *
     * @return self|null
     */
    public function createAnother(): ?self;

    /**
     * Returns whether the given user is authorized to view this element’s edit page.
     *
     * If they can view but not [[canSave()|save]], the edit form will either render statically,
     * or be restricted to only saving changes as a draft, depending on [[canCreateDrafts()]].
     *
     * @param User $user
     * @return bool
     * @since 4.0.0
     */
    public function canView(User $user): bool;

    /**
     * Returns whether the given user is authorized to save this element in its current form.
     *
     * This will only be called if the element can be [[canView()|viewed]].
     *
     * @param User $user
     * @return bool
     * @since 4.0.0
     */
    public function canSave(User $user): bool;

    /**
     * Returns whether the given user is authorized to duplicate this element.
     *
     * This will only be called if the element can be [[canView()|viewed]] and/or [[canSave()|saved]].
     *
     * @param User $user
     * @return bool
     * @since 4.0.0
     */
    public function canDuplicate(User $user): bool;

    /**
     * Returns whether the given user is authorized to duplicate this element as an unpublished draft.
     *
     * @param User $user
     * @return bool
     * @since 5.0.0
     */
    public function canDuplicateAsDraft(User $user): bool;

    /**
     * Returns whether the given user is authorized to copy this element, to be duplicated elsewhere.
     *
     * @param User $user
     * @return bool
     * @since 5.7.0
     */
    public function canCopy(User $user): bool;

    /**
     * Returns whether the given user is authorized to delete this element.
     *
     * This will only be called if the element can be [[canView()|viewed]] and/or [[canSave()|saved]].
     *
     * @param User $user
     * @return bool
     * @since 4.0.0
     */
    public function canDelete(User $user): bool;

    /**
     * Returns whether the given user is authorized to delete this element for its current site.
     *
     * This will only be called if the element can be [[canView()|viewed]] and/or [[canSave()|saved]].
     *
     * @param User $user
     * @return bool
     * @since 4.0.0
     */
    public function canDeleteForSite(User $user): bool;

    /**
     * Returns whether the given user is authorized to create drafts for this element.
     *
     * This will only be called if the element can be [[canView()|viewed]] and/or [[canSave()|saved]].
     *
     * ::: tip
     * If this is going to return `true` under any circumstances, make sure [[trackChanges()]] is returning `true`,
     * so drafts can be automatically updated with upstream content changes.
     * :::
     *
     * @param User $user
     * @return bool
     * @since 4.0.0
     */
    public function canCreateDrafts(User $user): bool;

    /**
     * Returns whether revisions should be created when this element is saved.
     *
     * @return bool
     * @since 4.0.0
     */
    public function hasRevisions(): bool;

    /**
     * Prepares the response for the element’s Edit screen.
     *
     * @param Response $response The response being prepared
     * @param string $containerId The ID of the element editor’s container element
     * @since 4.0.0
     */
    public function prepareEditScreen(Response $response, string $containerId): void;

    /**
     * Returns the URL that users should be redirected to after editing the element.
     *
     * @return string|null
     * @since 4.0.0
     */
    public function getPostEditUrl(): ?string;

    /**
     * Returns the element’s revisions index URL in the control panel.
     *
     * @return string|null
     * @since 4.4.0
     */
    public function getCpRevisionsUrl(): ?string;

    /**
     * Returns additional buttons that should be shown at the top of the element’s edit page.
     *
     * @return string
     * @since 4.0.0
     */
    public function getAdditionalButtons(): string;

    /**
     * Returns alternative form actions for the element.
     *
     * See [[\craft\web\CpScreenResponseBehavior::altActions()]] for documentation on supported action properties.
     *
     * @return array
     * @since 5.6.0
     */
    public function getAltActions(): array;

    /**
     * Returns the additional locations that should be available for previewing the element, besides its primary [[getUrl()|URL]].
     *
     * Each target should be represented by a sub-array with the following keys:
     *
     * - `label` – What the preview target will be called in the control panel.
     * - `url` – The URL that the preview target should open.
     * - `refresh` – Whether preview frames should be automatically refreshed when content changes (`true` by default).
     *
     * ::: tip
     * Element types that extend [[\craft\base\Element]] should override [[\craft\base\Element::previewTargets()]]
     * instead of this method.
     * :::
     *
     * @return array
     * @since 3.2.0
     */
    public function getPreviewTargets(): array;

    /**
     * Returns whether the element is enabled for the current site.
     *
     * This can also be set to an array of site ID/site-enabled mappings.
     *
     * @param int|null $siteId The ID of the site to return for. If `null`, the current site status will be returned.
     * @return bool|null Whether the element is enabled for the given site. `null` will be returned if a `$siteId` was
     * passed, but that site’s status wasn’t provided via [[setEnabledForSite()]].
     * @since 3.4.0
     */
    public function getEnabledForSite(?int $siteId = null): ?bool;

    /**
     * Sets whether the element is enabled for the current site.
     *
     * This can also be set to an array of site ID/site-enabled mappings.
     *
     * @param bool|bool[] $enabledForSite
     * @since 3.4.0
     */
    public function setEnabledForSite(array|bool $enabledForSite): void;

    /**
     * Returns the root owner element.
     *
     * @return self
     * @since 5.4.0
     */
    public function getRootOwner(): self;

    /**
     * Returns the same element in other locales.
     *
     * @return ElementQueryInterface|ElementCollection
     */
    public function getLocalized(): ElementQueryInterface|ElementCollection;

    /**
     * Returns the next element relative to this one, from a given set of criteria.
     *
     * @param mixed $criteria
     * @return self|null
     */
    public function getNext(mixed $criteria = false): ?self;

    /**
     * Returns the previous element relative to this one, from a given set of criteria.
     *
     * @param mixed $criteria
     * @return self|null
     */
    public function getPrev(mixed $criteria = false): ?self;

    /**
     * Sets the default next element.
     *
     * @param self|false $element
     */
    public function setNext(self|false $element): void;

    /**
     * Sets the default previous element.
     *
     * @param self|false $element
     */
    public function setPrev(self|false $element): void;

    /**
     * Returns the element’s parent.
     *
     * @return self|null
     */
    public function getParent(): ?self;

    /**
     * Returns the parent element’s URI, if there is one.
     *
     * If the parent’s URI is `__home__` (the homepage URI), then `null` will be returned.
     *
     * @return string|null
     */
    public function getParentUri(): ?string;

    /**
     * Sets the element’s parent.
     *
     * @param self|null $parent
     */
    public function setParent(?self $parent): void;

    /**
     * Returns the element’s ancestors.
     *
     * @param int|null $dist
     * @return ElementQueryInterface|ElementCollection
     */
    public function getAncestors(?int $dist = null): ElementQueryInterface|ElementCollection;

    /**
     * Returns the element’s descendants.
     *
     * @param int|null $dist
     * @return ElementQueryInterface|ElementCollection
     */
    public function getDescendants(?int $dist = null): ElementQueryInterface|ElementCollection;

    /**
     * Returns the element’s children.
     *
     * @return ElementQueryInterface|ElementCollection
     */
    public function getChildren(): ElementQueryInterface|ElementCollection;

    /**
     * Returns all of the element’s siblings.
     *
     * @return ElementQueryInterface|ElementCollection
     */
    public function getSiblings(): ElementQueryInterface|ElementCollection;

    /**
     * Returns the element’s previous sibling.
     *
     * @return self|null
     */
    public function getPrevSibling(): ?self;

    /**
     * Returns the element’s next sibling.
     *
     * @return self|null
     */
    public function getNextSibling(): ?self;

    /**
     * Returns whether the element has descendants.
     *
     * @return bool
     */
    public function getHasDescendants(): bool;

    /**
     * Returns the total number of descendants that the element has.
     *
     * @return int
     */
    public function getTotalDescendants(): int;

    /**
     * Returns whether this element is an ancestor of another one.
     *
     * @param self $element
     * @return bool
     */
    public function isAncestorOf(self $element): bool;

    /**
     * Returns whether this element is a descendant of another one.
     *
     * @param self $element
     * @return bool
     */
    public function isDescendantOf(self $element): bool;

    /**
     * Returns whether this element is a direct parent of another one.
     *
     * @param self $element
     * @return bool
     */
    public function isParentOf(self $element): bool;

    /**
     * Returns whether this element is a direct child of another one.
     *
     * @param self $element
     * @return bool
     */
    public function isChildOf(self $element): bool;

    /**
     * Returns whether this element is a sibling of another one.
     *
     * @param self $element
     * @return bool
     */
    public function isSiblingOf(self $element): bool;

    /**
     * Returns whether this element is the direct previous sibling of another one.
     *
     * @param self $element
     * @return bool
     */
    public function isPrevSiblingOf(self $element): bool;

    /**
     * Returns whether this element is the direct next sibling of another one.
     *
     * @param self $element
     * @return bool
     */
    public function isNextSiblingOf(self $element): bool;

    /**
     * Treats custom fields as array offsets.
     *
     * @param string|int $offset
     * @return bool
     */
    public function offsetExists($offset): bool;

    /**
     * Sets the element’s attributes from an element editor submission.
     *
     * @param array $values The attribute values
     */
    public function setAttributesFromRequest(array $values): void;

    /**
     * Returns the status of a given attribute.
     *
     * @param string $attribute
     * @return array{0:AttributeStatus|value-of<AttributeStatus>,1:string}|null
     * @since 3.4.0
     */
    public function getAttributeStatus(string $attribute): ?array;

    /**
     * Returns the attribute names that have been updated on the canonical element since the last time it was
     * merged into this element.
     *
     * @return string[]
     * @since 3.7.0
     */
    public function getOutdatedAttributes(): array;

    /**
     * Returns whether an attribute value has fallen behind the canonical element’s value.
     *
     * @param string $name
     * @return bool
     * @since 3.7.0
     */
    public function isAttributeOutdated(string $name): bool;

    /**
     * Returns the attribute names that have changed for this element.
     *
     * @return string[]
     * @since 3.7.0
     */
    public function getModifiedAttributes(): array;

    /**
     * Returns whether an attribute value has changed for this element.
     *
     * @param string $name
     * @return bool
     * @since 3.7.0
     */
    public function isAttributeModified(string $name): bool;

    /**
     * Returns whether an attribute has changed since the element was first loaded.
     *
     * @param string $name
     * @return bool
     * @since 3.5.0
     */
    public function isAttributeDirty(string $name): bool;

    /**
     * Returns a list of attribute names that have changed since the element was first loaded.
     *
     * @return string[]
     * @since 3.4.0
     */
    public function getDirtyAttributes(): array;

    /**
     * Sets the list of dirty attribute names.
     *
     * @param string[] $names
     * @param bool $merge Whether these attributes should be merged with existing dirty attributes
     * @see getDirtyAttributes()
     * @since 3.5.0
     */
    public function setDirtyAttributes(array $names, bool $merge = true): void;

    /**
     * Returns whether the Title field should be shown as translatable in the UI.
     *
     * Note this method has no effect on whether titles will get copied over to other
     * sites when the element is actually getting saved. That is determined by [[getTitleTranslationKey()]].
     *
     * @return bool
     * @since 3.5.0
     */
    public function getIsTitleTranslatable(): bool;

    /**
     * Returns the description of the Title field’s translation support.
     *
     * @return string|null
     * @since 3.5.0
     */
    public function getTitleTranslationDescription(): ?string;

    /**
     * Returns the Title’s translation key.
     *
     * When saving an element on a multi-site Craft install, if `$propagate` is `true` for [[\craft\services\Elements::saveElement()]],
     * then `getTitleTranslationKey()` will be called for each site the element should be propagated to.
     * If the method returns the same value as it did for the initial site, then the initial site’s title will be copied over
     * to the target site.
     *
     * @return string The translation key
     * @since 3.5.0
     */
    public function getTitleTranslationKey(): string;

    /**
     * Returns whether the Slug field should be shown as translatable in the UI.
     *
     * Note this method has no effect on whether slugs will get copied over to other
     * sites when the element is actually getting saved. That is determined by [[getSlugTranslationKey()]].
     *
     * @return bool
     * @since 4.5.0
     */
    public function getIsSlugTranslatable(): bool;

    /**
     * Returns the description of the Slug field’s translation support.
     *
     * @return string|null
     * @since 4.5.0
     */
    public function getSlugTranslationDescription(): ?string;

    /**
     * Returns the Slug’s translation key.
     *
     * When saving an element on a multi-site Craft install, if `$propagate` is `true` for [[\craft\services\Elements::saveElement()]],
     * then `getSlugTranslationKey()` will be called for each site the element should be propagated to.
     * If the method returns the same value as it did for the initial site, then the initial site’s slug will be copied over
     * to the target site.
     *
     * @return string The translation key
     * @since 4.5.0
     */
    public function getSlugTranslationKey(): string;

    /**
     * Returns whether a field is empty.
     *
     * @param string $handle
     * @return bool
     */
    public function isFieldEmpty(string $handle): bool;

    /**
     * Returns the element’s normalized custom field values, indexed by their handles.
     *
     * @param string[]|null $fieldHandles The list of field handles whose values
     * need to be returned. Defaults to null, meaning all fields’ values will be
     * returned. If it is an array, only the fields in the array will be returned.
     * @return array The field values (handle => value)
     */
    public function getFieldValues(?array $fieldHandles = null): array;

    /**
     * Returns an array of the element’s serialized custom field values, indexed by their handles.
     *
     * @param string[]|null $fieldHandles The list of field handles whose values
     * need to be returned. Defaults to null, meaning all fields’ values will be
     * returned. If it is an array, only the fields in the array will be returned.
     * @return array
     */
    public function getSerializedFieldValues(?array $fieldHandles = null): array;

    /**
     * Returns an array of the element’s serialized custom field values, indexed by their handles,
     * for database storage.
     *
     * @param string[]|null $fieldHandles The list of field handles whose values
     * need to be returned. Defaults to null, meaning all fields’ values will be
     * returned. If it is an array, only the fields in the array will be returned.
     * @return array
     * @since 5.7.0
     */
    public function getSerializedFieldValuesForDb(?array $fieldHandles = null): array;

    /**
     * Sets the element’s custom field values.
     *
     * @param array $values The custom field values (handle => value)
     */
    public function setFieldValues(array $values): void;

    /**
     * Returns the value for a given field.
     *
     * @param string $fieldHandle The field handle whose value needs to be returned
     * @return mixed The field value
     * @throws InvalidFieldException if the element doesn’t have a field with the handle specified by `$fieldHandle`
     */
    public function getFieldValue(string $fieldHandle): mixed;

    /**
     * Sets the value for a given field.
     *
     * @param string $fieldHandle The field handle whose value needs to be set
     * @param mixed $value The value to set on the field
     */
    public function setFieldValue(string $fieldHandle, mixed $value): void;

    /**
     * Sets the value for a given field. The value should have originated from post data.
     *
     * @param string $fieldHandle The field handle whose value needs to be set
     * @param mixed $value The value to set on the field
     * @throws InvalidFieldException if `$fieldHandle` is an invalid field handle
     * @since 4.5.0
     */
    public function setFieldValueFromRequest(string $fieldHandle, mixed $value): void;

    /**
     * Returns the field handles that have been updated on the canonical element since the last time it was
     * merged into this element.
     *
     * @return string[]
     * @since 3.7.0
     */
    public function getOutdatedFields(): array;

    /**
     * Returns whether a field value has fallen behind the canonical element’s value.
     *
     * @param string $fieldHandle
     * @return bool
     * @since 3.7.0
     */
    public function isFieldOutdated(string $fieldHandle): bool;

    /**
     * Returns the field handles that have changed for this element.
     *
     * @param bool $anySite Whether to check for fields that have changed across any site
     * @return string[]
     * @since 3.7.0
     */
    public function getModifiedFields(bool $anySite = false): array;

    /**
     * Returns whether a field value has changed for this element.
     *
     * @param string $fieldHandle
     * @param bool $anySite Whether to check if the field has changed across any site
     * @return bool
     * @since 3.7.0
     */
    public function isFieldModified(string $fieldHandle, bool $anySite = false): bool;

    /**
     * Returns whether a custom field value has changed since the element was first loaded.
     *
     * @param string $fieldHandle
     * @return bool
     * @since 3.4.0
     */
    public function isFieldDirty(string $fieldHandle): bool;

    /**
     * Returns a list of custom field handles that have changed since the element was first loaded.
     *
     * @return string[]
     * @since 3.4.0
     */
    public function getDirtyFields(): array;

    /**
     * Sets the list of dirty field handles.
     *
     * @param string[] $fieldHandles
     * @param bool $merge Whether these fields should be merged with existing dirty fields
     * @see getDirtyFields()
     * @since 4.5.0
     */
    public function setDirtyFields(array $fieldHandles, bool $merge = true): void;

    /**
     * Marks all fields and attributes as dirty.
     *
     * @since 3.4.10
     */
    public function markAsDirty(): void;

    /**
     * Resets the record of dirty attributes and fields.
     *
     * @since 3.4.0
     */
    public function markAsClean(): void;

    /**
     * Returns the cache tags that should be cleared when this element is saved.
     *
     * @return string[]
     * @since 3.5.0
     */
    public function getCacheTags(): array;

    /**
     * Sets the element’s custom field values, when the values have come from post data.
     *
     * @param string $paramNamespace The field param namespace
     */
    public function setFieldValuesFromRequest(string $paramNamespace): void;

    /**
     * Returns the namespace used by custom field params on the request.
     *
     * @return string|null The field param namespace
     */
    public function getFieldParamNamespace(): ?string;

    /**
     * Sets the namespace used by custom field params on the request.
     *
     * @param string $namespace The field param namespace
     */
    public function setFieldParamNamespace(string $namespace): void;

    /**
     * Returns the field context this element’s content uses.
     *
     * @return string
     */
    public function getFieldContext(): string;

    /**
     * Returns the generated field values for the element, indexed by handle.
     *
     * @return array<string,string>
     * @since 5.8.0
     */
    public function getGeneratedFieldValues(): array;

    /**
     * Sets the generated field values for the element, indexed by handle.
     *
     * @param array<string,string|null> $values
     * @since 5.8.0
     */
    public function setGeneratedFieldValues(array $values): void;

    /**
     * Returns the element’s invalid nested element IDs.
     *
     * @return int[]
     * @since 5.3.0
     */
    public function getInvalidNestedElementIds(): array;

    /**
     * Registers invalid nested element IDs with the element, so an `error` class can be added on their cards.
     *
     * @param int[] $ids
     * @since 5.3.0
     */
    public function addInvalidNestedElementIds(array $ids): void;

    /**
     * Returns whether elements have been eager-loaded with a given handle.
     *
     * @param string $handle The handle of the eager-loaded elements
     * @return bool Whether elements have been eager-loaded with the given handle
     */
    public function hasEagerLoadedElements(string $handle): bool;

    /**
     * Returns the eager-loaded elements for a given handle.
     *
     * @param string $handle The handle of the eager-loaded elements
     * @return ElementCollection|null The eager-loaded elements, or null if they hadn't been eager-loaded
     */
    public function getEagerLoadedElements(string $handle): ?ElementCollection;

    /**
     * Sets some eager-loaded elements on a given handle.
     *
     * @param string $handle The handle that was used to eager-load the elements
     * @param self[] $elements The eager-loaded elements
     * @param EagerLoadPlan $plan The eager-loading plan
     */
    public function setEagerLoadedElements(string $handle, array $elements, EagerLoadPlan $plan): void;

    /**
     * Sets whether the given eager-loaded element handles were eager-loaded lazily.
     *
     * @param string $handle The handle that was used to eager-load the elements
     * @param bool $value
     */
    public function setLazyEagerLoadedElements(string $handle, bool $value = true): void;

    /**
     * Returns the count of eager-loaded elements for a given handle.
     *
     * @param string $handle The handle of the eager-loaded elements
     * @return int|null The eager-loaded element count, or null if it hadn't been eager-loaded
     * @since 3.4.0
     */
    public function getEagerLoadedElementCount(string $handle): ?int;

    /**
     * Sets the count of eager-loaded elements for a given handle.
     *
     * @param string $handle The handle to load the elements with in the future
     * @param int $count The eager-loaded element count
     * @since 3.4.0
     */
    public function setEagerLoadedElementCount(string $handle, int $count): void;

    /**
     * Returns whether the element is "fresh" (not yet explicitly saved, and without validation errors).
     *
     * @return bool
     * @since 3.7.14
     */
    public function getIsFresh(): bool;

    /**
     * Sets whether the element is "fresh" (not yet explicitly saved, and without validation errors).
     *
     * @param bool $isFresh
     * @since 3.7.14
     */
    public function setIsFresh(bool $isFresh = true): void;

    /**
     * Sets the revision creator ID to be saved.
     *
     * @param int|null $creatorId
     * @since 3.2.0
     */
    public function setRevisionCreatorId(?int $creatorId): void;

    /**
     * Sets the revision notes to be saved.
     *
     * @param string|null $notes
     * @since 3.2.0
     */
    public function setRevisionNotes(?string $notes): void;

    /**
     * Returns the element’s current revision, if one exists.
     *
     * @return self|null
     * @since 3.2.0
     */
    public function getCurrentRevision(): ?self;

    /**
     * Return if the element is copyable between sites.
     * Checks if it's a multisite installation, if user can edit the element in other sites,
     * and if the element actually exists in other sites.
     *
     * @return bool
     * @since 5.6.0
     */
    public function getIsCrossSiteCopyable(): bool;

    // Indexes, etc.
    // -------------------------------------------------------------------------

    /**
     * Returns any attributes that should be included in the element’s chips and cards.
     *
     * The attribute HTML will be rendered with [[\yii\helpers\BaseHtml::renderTagAttributes()]].
     *
     * ::: tip
     * Element types that extend [[\craft\base\Element]] should override [[\craft\base\Element::htmlAttributes()]]
     * instead of this method.
     * :::
     *
     * @param string $context The context that the element is being rendered in ('index', 'modal', 'field', or 'settings'.)
     * @return array
     */
    public function getHtmlAttributes(string $context): array;

    /**
     * Returns the HTML that should be shown for a given attribute in table and card views.
     *
     * ::: tip
     * Element types that extend [[\craft\base\Element]] should override [[\craft\base\Element::attributeHtml()]]
     * instead of this method.
     * :::
     *
     * @param string $attribute The attribute name.
     * @return string The HTML that should be shown for a given attribute in table and card views.
     * @since 5.0.0
     */
    public function getAttributeHtml(string $attribute): string;

    /**
     * Returns the HTML that should be shown for a given attribute's inline editing input.
     *
     * @param string $attribute The attribute name.
     * @return string The HTML that should be shown for the element input.
     * @since 5.0.0
     */
    public function getInlineAttributeInputHtml(string $attribute): string;

    /**
     * Returns the HTML for any fields/info that should be shown within the editor sidebar.
     *
     * @param bool $static Whether any fields within the sidebar should be static (non-interactive)
     * @return string
     * @since 3.7.0
     */
    public function getSidebarHtml(bool $static): string;

    /**
     * Returns element metadata that should be shown within the editor sidebar.
     *
     * @return array The data, with keys representing the labels. The values can either be strings or callables.
     * If a value is `false`, it will be omitted.
     * @since 3.7.0
     */
    public function getMetadata(): array;

    /**
     * Returns the GraphQL type name for this element type.
     *
     * @return string
     * @since 3.3.0
     */
    public function getGqlTypeName(): string;

    // Events
    // -------------------------------------------------------------------------

    /**
     * Performs actions before an element is saved.
     *
     * @param bool $isNew Whether the element is brand new
     * @return bool Whether the element should be saved
     */
    public function beforeSave(bool $isNew): bool;

    /**
     * Performs actions after an element is saved.
     *
     * @param bool $isNew Whether the element is brand new
     */
    public function afterSave(bool $isNew): void;

    /**
     * Performs actions after an element is fully saved and propagated to other sites.
     *
     * ::: tip
     * This will get called regardless of whether `$propagate` is `true` or `false` for [[\craft\services\Elements::saveElement()]].
     * :::
     *
     * @param bool $isNew Whether the element is brand new
     * @since 3.2.0
     */
    public function afterPropagate(bool $isNew): void;

    /**
     * Performs actions before an element is deleted.
     *
     * @return bool Whether the element should be deleted
     */
    public function beforeDelete(): bool;

    /**
     * Performs actions after an element is deleted.
     *
     */
    public function afterDelete(): void;

    /**
     * Performs actions before an element is deleted for a site.
     *
     * @return bool Whether the element should be deleted
     * @since 4.7.0
     */
    public function beforeDeleteForSite(): bool;

    /**
     * Performs actions after an element is deleted for a site.
     *
     * @since 4.7.0
     */
    public function afterDeleteForSite(): void;

    /**
     * Performs actions before an element is restored.
     *
     * @return bool Whether the element should be restored
     * @since 3.1.0
     */
    public function beforeRestore(): bool;

    /**
     * Performs actions after an element is restored.
     *
     * @since 3.1.0
     */
    public function afterRestore(): void;

    /**
     * Performs actions before an element is moved within a structure.
     *
     * @param int $structureId The structure ID
     * @return bool Whether the element should be moved within the structure
     * @deprecated in 4.5.0. [[\craft\services\Structures::EVENT_BEFORE_INSERT_ELEMENT]] or
     * [[\craft\services\Structures::EVENT_BEFORE_MOVE_ELEMENT|EVENT_BEFORE_MOVE_ELEMENT]]
     * should be used instead.
     */
    public function beforeMoveInStructure(int $structureId): bool;

    /**
     * Performs actions after an element is moved within a structure.
     *
     * @param int $structureId The structure ID
     * @deprecated in 4.5.0. [[\craft\services\Structures::EVENT_AFTER_INSERT_ELEMENT]] or
     * [[\craft\services\Structures::EVENT_AFTER_MOVE_ELEMENT|EVENT_AFTER_MOVE_ELEMENT]]
     * should be used instead.
     */
    public function afterMoveInStructure(int $structureId): void;

    /**
     * Returns the string representation of the element.
     */
    public function __toString(): string;

    /**
     * Renders the element using its partial template.
     *
     * If no partial template exists for the element, its string representation will be output instead.
     *
     * @param array $variables
     * @return Markup
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @since 5.8.0
     */
    public function render(array $variables = []): Markup;
}
