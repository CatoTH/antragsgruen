// @ts-check

import translate from "/js/vue/Translate.vue.js";

const getTitle = function (binding) {
    if (typeof binding.value === 'object') {
        return translate.getTranslation(binding.value[0], binding.value[1]);
    } else {
        return binding.value;
    }
};

export default {
    mounted(el, binding) {
        $(el).tooltip({
            title: getTitle(binding),
            placement: 'top',
            trigger: 'hover'
        })
    },
    updated(el, binding) {
        $(el).tooltip('destroy')
        $(el).tooltip({
            title: getTitle(binding),
            placement: 'top',
            trigger: 'hover'
        })
    },
    unmounted(el) {
        $(el).tooltip('destroy')   // Clean up to prevent memory leaks
    }
}
