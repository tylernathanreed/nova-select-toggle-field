<?php

namespace Reedware\NovaSelectToggleField;

use Closure;
use RuntimeException;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Laravel\Nova\Fields\Field;

class SelectToggle extends Field
{
    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'select-toggle-field';

    /**
     * The options callback.
     *
     * @var \Closure
     */
    public $optionsCallback;

    /**
     * Set the options callback for the select menu.
     *
     * @param  \Closure
     *
     * @return $this
     */
    public function options(Closure $options)
    {
        $this->optionsCallback = $options;

        return $this;
    }

    /**
     * Calls the options callback using the specified target attribute and value.
     *
     * @param  string  $targetAttribute
     * @param  mixed   $targetValue
     *
     * @return array
     */
    public function callOptions($targetAttribute, $targetValue)
    {
        // Make sure the options callback is set
        if(is_null($callback = $this->optionsCallback)) {
            return [];
        }

        // Make sure the options callback is callable
        if(!is_callable($callback)) {
            return [];
        }

        // Call the options callback
        $results = $callback($targetValue, $targetAttribute);

        // If the results are arrayable, convert them
        if(is_object($results) && $results instanceof Arrayable) {
            $results = $results->toArray();
        }

        // Make sure the result is an array
        if(!is_array($results)) {
            throw new RuntimeException("Select Toggle for [{$this->attribute}] failed for target [{$targetAttribute}] with value [{$targetValue}].");
        }

        // If the result is in the correct format, return it
        if(is_array($first = head($results)) && isset($first['value']) && isset($first['label'])) {
            return $results;
        }

        // Format the results
        return collect($results)->keys()->map(function($key) use ($results) {
            return ['value' => $key, 'label' => $results[$key]];
        })->all();

    }

    /**
     * Sets the target attribute for this field.
     *
     * @param  string  $target
     *
     * @return $this
     */
    public function target($target)
    {
        return $this->withMeta([
            'targetAttribute' => $target,
        ]);
    }
}
