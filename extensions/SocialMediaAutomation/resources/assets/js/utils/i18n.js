/**
 * Translate a key using the translations passed from Laravel via window.__automationData.
 *
 * @param {string} key - Translation key (e.g. 'edit_actions')
 * @param {string} [fallback] - Fallback if key not found
 * @param {Object} [replacements] - Placeholder replacements (e.g. { type: 'Post' } for ':type')
 * @returns {string}
 */
export function t(key, fallback, replacements) {
    let value = window.__automationData?.translations?.[key] ?? fallback ?? key;

    if (replacements) {
        Object.entries(replacements).forEach(([k, v]) => {
            value = value.replace(`:${k}`, v);
        });
    }

    return value;
}
