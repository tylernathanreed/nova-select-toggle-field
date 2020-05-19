# Nova Select Toggle Field
[![Latest Stable Version](https://poser.pugx.org/reedware/nova-select-toggle-field/v/stable)](https://packagist.org/packages/reedware/nova-select-toggle-field)
[![Total Downloads](https://poser.pugx.org/reedware/nova-select-toggle-field/downloads)](https://packagist.org/packages/reedware/nova-select-toggle-field)

This package a Laravel Nova select field whose value will vary on the contents of another select field.

## Introduction

While Laravel Nova offers select drop-downs, and even the ability to search within them, sometimes I find that a different user experience is more suitable for my needs. Select boxes can sometimes be very long, and if you're unfamiliar with the resource that you're working with, you may not know where to begin with searching.

There's also some more advanced approaches that I've needed to take in the past, which involve dynamically generating the drop-down options. When the list grows in size, the computation time begins to wear down on the performance of the form.

One solution to both of these problems is something that this package attempts to offer. A "Select Toggle" is essentially a select drop-down whose options can vary based off of the contents of another drop-down.

Here's what that looks like in action:

![Example](https://github.com/tylernathanreed/nova-select-toggle-field/blob/master/docs/example-1.gif)

## Installation

Install this package using Composer within a Laravel Nova application:

```
composer require reedware/nova-select-toggle-field
```

You'll want to include the following field in any resource that you plan to use the field in:

```
Reedware\NovaSelectToggleField\SelectToggle
```

Or you can install my [Field Manager Package](https://github.com/tylernathanreed/nova-field-manager) which aims to help reduce the plethera of field includes at the top of each resource file.

## Usage

Since a Select Toggle field depends upon another field, you'll need to define at least two fields (one being the _target_ field, and the other being the _toggle_ field).

### Abstract Example

Here's the general setup:

```php
public function fields(Request $request)
{
  return [
        Select::make('Target Field', 'target_field')
            ->options([
                /* ... values => labels ... */
            ]),

        SelectToggle::make('Toggle Field', 'toggle_field')
            ->target('target_field')
            ->options(function($targetValue) {
              /**
               * $targetValue is the in-flight form value from the "target_field" field.
               * Use this value to return your dynamically generated list. The value
               * will be the value from the target, not the label within the UI.
               */
              return [
                  /* ... values => labels ... */
              ];
            })
  ];
}
```
### Concrete Example

Here's how you could recreate the introduction example within your project:

```php
public function fields(Request $request)
{
  return [
        Select::make('Group', 'group_name')
            ->help('The group containing the resource.')
            ->options(
                collect(Nova::$resources)->mapWithKeys(function($resource) {
                    return [$resource::$group => str_replace('.', ' > ', $resource::$group)];
                })->unique()->sort()
            ),

        SelectToggle::make('Resource', 'resource_name')
            ->help('The resource within the group.')
            ->target('group_name')
            ->options(function($targetValue) {
                return collect(Nova::$resources)->filter(function($resource) use ($targetValue) {
                    return $resource::$group == $targetValue;
                })->mapWithKeys(function($resource) {
                    return [$resource => $resource::label()];
                })->sort()->toArray();
            })
  ];
}
```

### Complex Example

This section contains a complex example of something that I'm actually using in one of my projects. This is part of my "Permission" resource, where the user can create a new permission, and tie it to a policy method. The "target" drop-down contains the list of resources in my application, and these have been grouped by their resource group. The "toggle" drop-down contains the list of permissable methods from the policy (i.e. "View Any", "Create", etc.), and it only shows the options that relate to the resource specified by the "target" drop-down.

I'm making use of two other packages here, which are optional for this example:

* My [Field Manager Package](https://github.com/tylernathanreed/nova-field-manager), which allows me to use `Field::select(...)` instead of `Select::make(...)` (this is to only have the one `Field` include in my resources).
* My [Value Toggle Field](https://github.com/tylernathanreed/nova-value-toggle), which allows me to only show certain fields based on the content of other fields. I'm using this to hide the Select Toggle field until a target option has been specified.

Here's the code:

```php
/**
 * Returns the fields displayed by the resource.
 *
 * @param  \Illuminate\Http\Request  $request
 *
 * @return array
 */
public function fields(Request $request)
{
  return [
  
        // "Resource" field
        Field::select(__('Resource'), 'resource_name')
            ->help('The resource tied to this permission.')
            ->required()
            ->options($this->getPermissionResourceOptions())
            ->displayUsingLabels(),

        // "Ability" (on Create form)
        Field::selectToggle(__('Ability'), 'ability_name')
            ->onlyOnForms()
            ->hideWhenUpdating()
            ->help('The ability being granted to the resource.')
            ->target('resource_name')
            ->options(function($targetValue) {
                return $this->getPolicyMethodOptions($targetValue);
            })
            ->displayUsing(function($value) {
                return static::getLabelForAbility($value);
            })
            ->valueToggle(function($toggle) {
                return $toggle->whereNotNull('resource_name');
            }),

        // "Ability" (on Update form)
        Field::text(__('Ability'), 'ability_name')
            ->onlyOnForms()
            ->hideWhenCreating()
            ->help('The ability name of this permission.')
            ->readonly()
            ->resolveUsing(function($value) {
                return static::getLabelForAbility($value);
            }),

        // "Ability" (on Display & Index)
        Field::text(__('Ability'), 'ability_name')
            ->exceptOnForms()
            ->displayUsing(function($value) {
                return static::getLabelForAbility($value);
            })

  ];
}

/**
 * Returns the permission resource options.
 *
 * @return array
 */
public function getPermissionResourceOptions()
{
    // Determine all of the resources
    $resources = collect(Nova::$resources);

    // Filter to only resources that have policies
    $resources = $resources->filter(function($resource) {
        return !is_null(Gate::getPolicyFor($resource::$model));
    });

    // Convert the resources into selection options
    $options = $resources->map(function($resource) {

        return [
            'label' => __($resource::label()),
            'value' => $resource,
            'group' => str_replace('.', ' > ', $resource::$group)
        ];

    });

    // Sort the options
    $options = $options->sortBy(function($option) {
        return str_pad($option['group'], 255) . '.' . str_pad($option['label'], 255);
    });

    // Exclude the resources that won't have any selectable abilities
    $options = $options->filter(function($option) {
        return !empty($this->getPolicyMethodOptions($option['value']));
    });

    // Return the options
    return $options->all();
}

/**
 * Returns the policy method options for the specified resource.
 *
 * @param  string  $resource
 *
 * @return array
 */
public function getPolicyMethodOptions($resource)
{
    // Determine the model from the resource
    $model = $resource::$model;

    // Determine the policy for the model
    $policy = Gate::getPolicyFor($model);

    // Determine the policy methods
    $methods = $policy::getPermissableMethods();

    // Determine the existing options
    $existing = static::newModel()->newQuery()->where('resource_name', $resource)->pluck('ability_name')->toArray();

    // Filter out the existing options
    $remaining = array_filter($methods, function($method) use ($existing) {
        return !in_array($method, $existing);
    });

    // Include the current option
    if($this->exists && $resource == $this->resource_name) {
        $options[] = $this->ability_name;
    }

    // Determine the method options
    $options = collect($remaining)->mapWithKeys(function($ability) {
        return [$ability => static::getLabelForAbility($ability)];
    });

    // Return the options
    return $options->all();
}

/**
 * Returns the label for the specified ability.
 *
 * @param  string  $ability
 *
 * @return string
 */
public static function getLabelForAbility($ability)
{
    return Str::title(Str::snake($ability, ' '));
}
```
