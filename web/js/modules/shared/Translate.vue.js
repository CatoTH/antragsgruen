// @ts-check

const translations = {}

export default {
    mounted(el, binding) {
        applyTranslation(el, binding);
    },
    updated(el, binding) {
        applyTranslation(el, binding);
    },
    registerTranslation(category, messages) {
        translations[category] = messages
    }
};

function applyTranslation(el, binding) {
    const [category, messageId, html, replacements] = binding.value || [];
    const attr = binding.arg; // e.g. "title", "aria-label"

    if (!category || !messageId) {
        console.warn("v-translate requires [category, messageId]");
        return;
    }

    let text;
    if (translations[category] === undefined || translations[category][messageId] === undefined) {
        text = "UNKNOWN TRANSLATION";
    } else {
        text = translations[category][messageId];
    }

    if (typeof(replacements) === "object") {
        Object.keys(replacements).forEach(key => {
            text = text.replace(key, replacements[key]);
        })
    }

    if (attr) {
        el.setAttribute(attr, text);
    } else if (el instanceof HTMLTemplateElement) {
        // <template> elements are inert — replace with a plain text node
        const textNode = document.createTextNode(text);
        el.parentNode.replaceChild(textNode, el);
    } else if (html) {
        el.innerHTML = text;
    } else {
        el.textContent = text;
    }
}
