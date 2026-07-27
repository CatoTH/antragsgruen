// @ts-check

// Provides JWTs for the REST API and authenticated fetch helpers around it.
//
// The initial token is read from the <meta name="user-jwt-config"> tag, which is rendered
// when a view sets $layout->provideJwt = true. Shortly before the token expires,
// it is transparently renewed via the (session-authenticated) token endpoint (/user/token).

const EXPIRY_SAFETY_MARGIN_SECONDS = 10;

/** @type {{token: string, exp: number, reload_uri: string}|null} */
let jwtConfig = null;

/** @type {Promise<string>|null} */
let runningRenewal = null;

function loadInitialConfig() {
    const meta = document.head.querySelector('meta[name=user-jwt-config]');
    if (!meta) {
        throw new Error('No user-jwt-config meta tag found - the view needs to set $layout->provideJwt');
    }
    return JSON.parse(meta.getAttribute('content') || '');
}

/**
 * @param {{token: string, exp: number, reload_uri: string}} config
 */
function isStillValid(config) {
    return (config.exp - EXPIRY_SAFETY_MARGIN_SECONDS) * 1000 > (new Date()).getTime();
}

/**
 * Resolves to a JWT that is still valid for at least a few seconds.
 *
 * @returns {Promise<string>}
 */
export function getToken() {
    if (jwtConfig === null) {
        jwtConfig = loadInitialConfig();
    }
    if (isStillValid(jwtConfig)) {
        return Promise.resolve(jwtConfig.token);
    }

    if (runningRenewal === null) {
        runningRenewal = fetch(jwtConfig.reload_uri)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Could not renew the JWT: HTTP status ' + response.status);
                }
                return response.json();
            })
            .then(config => {
                jwtConfig = config;
                return config.token;
            })
            .finally(() => {
                runningRenewal = null;
            });
    }

    return runningRenewal;
}

/**
 * Convenience wrapper around fetch() that sends the JWT as Bearer token.
 *
 * @param {string} url
 * @param {RequestInit} [options]
 * @returns {Promise<Response>}
 */
export function authorizedFetch(url, options = {}) {
    return getToken().then(token => fetch(url, {
        ...options,
        headers: {
            ...(options.headers || {}),
            'Authorization': 'Bearer ' + token,
        },
    }));
}

/**
 * Turns a fetch() Response into parsed JSON, throwing an Error carrying the server-provided
 * message (from the {success, message} error body, falling back to the raw text / status) on failure.
 *
 * @param {Response} response
 * @returns {Promise<any>}
 */
function parseJsonResponse(response) {
    if (response.ok) {
        return response.json();
    }
    return response.text().then(text => {
        let message = text;
        try {
            const parsed = JSON.parse(text);
            if (parsed && parsed.message) {
                message = parsed.message;
            }
        } catch (e) {
            // Not a JSON body - keep the raw text
        }
        throw new Error(message || ('HTTP status ' + response.status));
    });
}

/**
 * GETs the given URL (authenticated via JWT) and resolves to the parsed JSON response.
 *
 * @param {string} url
 * @returns {Promise<any>}
 */
export function getJson(url) {
    return authorizedFetch(url).then(parseJsonResponse);
}

/**
 * Sends the given object as application/json to the backend (authenticated via JWT)
 * and resolves to the parsed JSON response.
 *
 * @param {string} method
 * @param {string} url
 * @param {object} body
 * @returns {Promise<any>}
 */
export function sendJson(method, url, body) {
    return authorizedFetch(url, {
        method,
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(body),
    }).then(parseJsonResponse);
}

/**
 * @param {string} url
 * @param {object} body
 * @returns {Promise<any>}
 */
export function postJson(url, body) {
    return sendJson('POST', url, body);
}

/**
 * @param {string} url
 * @param {object} body
 * @returns {Promise<any>}
 */
export function putJson(url, body) {
    return sendJson('PUT', url, body);
}

/**
 * Sends a DELETE request to the backend (authenticated via JWT)
 * and resolves to the parsed JSON response.
 *
 * @param {string} url
 * @returns {Promise<any>}
 */
export function deleteJson(url) {
    return authorizedFetch(url, {
        method: 'DELETE',
    }).then(parseJsonResponse);
}
