// @ts-check

// Provides JWTs for the REST API to JS modules / Vue components.
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

export default {
    /**
     * Resolves to a JWT that is still valid for at least a few seconds.
     *
     * @returns {Promise<string>}
     */
    getToken() {
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
    },

    /**
     * Convenience wrapper around fetch() that sends the JWT as Bearer token.
     *
     * @param {string} url
     * @param {RequestInit} [options]
     * @returns {Promise<Response>}
     */
    authorizedFetch(url, options = {}) {
        return this.getToken().then(token => fetch(url, {
            ...options,
            headers: {
                ...(options.headers || {}),
                'Authorization': 'Bearer ' + token,
            },
        }));
    },

    /**
     * Sends the given object as application/json to the backend (authenticated via JWT)
     * and resolves to the parsed JSON response.
     *
     * @param {string} method
     * @param {string} url
     * @param {object} body
     * @returns {Promise<any>}
     */
    sendJson(method, url, body) {
        return this.authorizedFetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(body),
        }).then(response => {
            if (!response.ok) {
                throw new Error('HTTP status ' + response.status);
            }
            return response.json();
        });
    },

    /**
     * @param {string} url
     * @param {object} body
     * @returns {Promise<any>}
     */
    postJson(url, body) {
        return this.sendJson('POST', url, body);
    },

    /**
     * @param {string} url
     * @param {object} body
     * @returns {Promise<any>}
     */
    putJson(url, body) {
        return this.sendJson('PUT', url, body);
    },
};
