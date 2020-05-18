Nova.booting((Vue, router, store) => {
    Vue.component('index-select-toggle-field', require('./components/IndexField'))
    Vue.component('detail-select-toggle-field', require('./components/DetailField'))
    Vue.component('form-select-toggle-field', require('./components/FormField'))
})
