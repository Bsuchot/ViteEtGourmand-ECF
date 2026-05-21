const BASE_URL = 'https://vite-et-gourmand-ecf-8adbd2933cc2.herokuapp.com/api';
let csrfToken = null;

const SAFE_METHODS = new Set(['GET', 'HEAD', 'OPTIONS']);

async function getCsrfToken(force = false) {
    if (csrfToken && !force) return csrfToken;

    const res = await fetch(`${BASE_URL}/csrf`, {
        credentials: 'include'
    });

    const data = await res.json();
    csrfToken = data.token;
    return csrfToken;
}

async function request(method, endpoint, body = null) {
    const options = {
        method,
        headers: {
            'Content-Type': 'application/json'
        },
        credentials: 'include'
    };

    if (!SAFE_METHODS.has(method)) {
        options.headers['X-CSRF-TOKEN'] = await getCsrfToken();
    }

    if (body) {
        options.body = JSON.stringify(body);
    }

    const response = await fetch(`${BASE_URL}${endpoint}`, options);
    const data = await response.json().catch(() => null);

    if (!response.ok) {
        const err = new Error(`HTTP ${response.status}`);
        err.status = response.status;
        err.data   = data;
        throw err;
    }

    return data;
}

export const api = {
    get: (e) => request('GET', e),
    post: (e, b) => request('POST', e, b),
    put: (e, b) => request('PUT', e, b),
    delete: (e) => request('DELETE', e)
};