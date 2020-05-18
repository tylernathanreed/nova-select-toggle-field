<?php

namespace Reedware\NovaSelectToggleField;

use Laravel\Nova\Nova;
use Laravel\Nova\Tool as BaseTool;

class Tool extends BaseTool
{
    /**
     * Returns the options for the specified criteria.
     *
     * @param  string  $resourceName
     * @param  string  $fieldAttribute
     * @param  string  $targetAttrigle
     * @param  mixed   $targetValue
     *
     * @return array
     */
    public static function getOptions($resourceName, $fieldAttribute, $targetAttribute, $targetValue)
    {
        // Make sure a target value has been provided
        if(is_null($targetValue)) {
            return [];
        }

        // Create a new instance of the resource
        $instance = Nova::resourceInstanceForKey($resourceName);

        // Determine the resource fields
        $fields = $instance->fields(request());

        // Find the specified field being toggled
        $field = head(array_filter($fields, function($field) use ($fieldAttribute) {
            return $field->attribute == $fieldAttribute;
        }));

        // Return the options from the field
        return $field->callOptions($targetAttribute, $targetValue);
    }
}
