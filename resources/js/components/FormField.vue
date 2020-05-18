<template>
    <default-field :field="field" :errors="errors">
        <template slot="field">
            <select-control
                :id="field.attribute"
                :dusk="field.attribute"
                v-model="value"
                class="w-full form-control form-select"
                :class="errorClasses"
                :options="options"
                :disabled="isReadonly || loading"
            >
                <option value="" selected :disabled="!field.nullable">{{
                    placeholder
                }}</option>
            </select-control>
        </template>
    </default-field>
</template>

<script>
import _ from 'lodash'
import {
    Errors,
    FormField,
    HandlesValidationErrors,
    Minimum
} from 'laravel-nova'

export default {
    mixins: [HandlesValidationErrors, FormField],

    data: () => ({
        initialLoading: true,
        loading: true,
        originalValue: null,
        options: []
    }),

    /**
     * Mount the component and retrieve its initial data.
     */
    async created() {

        this.originalValue = this.field.value

        await this.getOptions()

        this.initialLoading = false

        this.$watch(
            () => {
                return (
                    this.targetValue
                )
            },
            () => {
                this.getOptions()
            }
        )

    },

    methods: {

        /**
         * Finds and sets the options based on the target field.
         *
         * @return {void}
         */
        getOptions() {

            let originalValue = this.initialLoading
                ? this.originalValue
                : this.value;

            this.loading = true;
            this.options = [];
            this.value = null;

            this.$nextTick(() => {

                return Minimum(
                    Nova.request({
                        method: 'post',
                        url: '/nova-vendor/select-toggle',
                        data: this.optionRequestQueryString
                    }), 300
                ).then(({ data }) => {

                    let selectedOption = _.find(data.options, (option) => { return option.value == originalValue });

                    this.loading = false;
                    this.options = data.options;
                    
                    this.$nextTick(() => {

                        this.value = selectedOption ? selectedOption.value : null;
                        this.$forceUpdate();

                    })

                    Nova.$emit('options-loaded')

                })
                .catch(error => {

                    if(!error.response) {

                        console.warn(error);
                        this.errors = new Errors(error.message);

                    }

                    else if(error.response.status == 422) {
                        this.errors = new Errors(error.response.data.errors);
                    }

                    this.loading = false;

                })

            })

        },

        /**
         * Fills the form data with the value from this field.
         *
         * @param  {FormData}
         *
         * @return {void}
         */
        fill(formData) {
            formData.append(this.field.attribute, this.value || '')
        },

        /**
         * Returns the field component with the specified form attribute.
         *
         * @param  {string}  "attribute"
         *
         * @return {VueComponent|null}
         */
        getFieldComponent(attribute) {

            // Determine the parent container
            let form = this.getParentContainer();

            // Determine the form fields
            let fields = form.$children;

            // Return the first matching result
            return _.head(fields.filter(function(field) {
                return field.fieldAttribute == attribute;
            }));

        },

        /**
         * Returns the parent form container for this component.
         *
         * @return {VueComponent|null}
         */
        getParentContainer() {

            // Initialize to the first parent
            let parent = this.$parent;

            // Walk up the parent tree until we find the component that we're looking for
            while(parent != null && parent.$options.name !== 'card') {
                parent = parent.$parent;
            }

            // Return the parent component
            return parent;

        }
    },

    computed: {

        /**
         * Returns the placeholder text for this field.
         *
         * @return {string}
         */
        placeholder() {
            return this.loading ? 'Loading...' : (this.field.placeholder || this.__('Choose an option'));
        },

        /**
         * Returns the target component for this field.
         *
         * @return {VueComponent|null}
         */
        targetComponent() {
            return this.getFieldComponent(this.field.targetAttribute);
        },

        /**
         * Returns the target value for this field.
         *
         * @return {mixed}
         */
        targetValue() {
            return this.targetComponent ? this.targetComponent.value : null;
        },

        /**
         * Returns the query string for the options request.
         *
         * @return {Object}
         */
        optionRequestQueryString() {

            return {
                resourceName: this.resourceName,
                fieldAttribute: this.fieldAttribute,
                targetAttribute: this.field.targetAttribute,
                targetValue: this.targetValue
            }

        }

    },
}
</script>
