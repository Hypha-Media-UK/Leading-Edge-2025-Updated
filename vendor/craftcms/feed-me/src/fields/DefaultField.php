<?php

namespace craft\feedme\fields;

use craft\feedme\base\Field;
use craft\feedme\base\FieldInterface;
use craft\helpers\Json;

/**
 *
 * @property-read string $mappingTemplate
 */
class DefaultField extends Field implements FieldInterface
{
    // Properties
    // =========================================================================

    /**
     * @var string
     */
    public static string $name = 'Default';

    /**
     * @var string
     */
    public static string $class = 'craft\fields\Default';

    // Templates
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function getMappingTemplate(): string
    {
        return 'feed-me/_includes/fields/default';
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function parseField(): mixed
    {
        $value = $this->fetchValue();

        if ($value === null) {
            return null;
        }

        // Default fields expect strings, if it's an array for an odd reason, serialise it
        if (is_array($value)) {
            if (empty($value)) {
                $value = '';
            } else {
                $value = Json::encode($value);
            }
        }

        // If it's exactly an empty string, that's okay and allowed. Normalising this will set it to
        // null, which means it won't get imported. Sometimes we want to have empty strings
        if ($value !== '') {
            $value = $this->field->normalizeValue($value, $this->element);
        }

        // if we're setting empty values and the value is empty - return an empty string
        // otherwise HtmlField will serialize it to null, and we setEmptyValues won't take effect
        // https://github.com/craftcms/feed-me/issues/1321
        // if the normalizeValue above returns null, which can happen for e.g. plain text field and a value of a single space
        // we also need to ensure that an empty string is returned, not the value as that can be null after normalization
        // https://github.com/craftcms/feed-me/issues/1560
        if ($this->feed['setEmptyValues'] === 1 && empty($value)) {
            return '';
        }

        // Lastly, get each field to prepare values how they should
        return $this->field->serializeValue($value, $this->element);
    }
}
