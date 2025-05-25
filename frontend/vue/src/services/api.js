const baseUrl = 'http://localhost:8000/api';
const assetBaseUrl = 'http://localhost:8000';

async function getCsrf() {
    await fetch('http://localhost:8000/sanctum/csrf-cookie', {
        credentials: 'include'
    });
}

function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
}

async function request(method, endpoint, data = null, isMultipart = false) {
    const headers = { Accept: 'application/json' };
    const token = localStorage.getItem('auth_token');
    if (token) headers['Authorization'] = `Bearer ${token}`;
    if (['POST', 'PUT', 'PATCH', 'DELETE'].includes(method.toUpperCase())) {
        const xsrfToken = getCookie('XSRF-TOKEN');
        if (xsrfToken) headers['X-XSRF-TOKEN'] = decodeURIComponent(xsrfToken);
    }
    const options = { method, headers, credentials: 'include' };

    if (data) {
        if (isMultipart && data instanceof FormData) {
            if (method.toUpperCase() === 'PUT') {
                data.append('_method', 'PUT');
                options.method = 'POST';
            }
            options.body = data;
        } else {
            headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(data);
        }
    }

    const response = await fetch(`${baseUrl}${endpoint}`, options);
    const json = await response.json();
    if (!response.ok) {
        const error = new Error(json.message || 'Request failed');
        error.status = response.status;
        error.errors = json.errors || {};
        throw error;
    }
    return json.data;
}

async function post(endpoint, data, isMultipart = false) {
    await getCsrf();
    return request('POST', endpoint, data, isMultipart);
}

async function put(endpoint, data, isMultipart = false) {
    await getCsrf();
    return request('PUT', endpoint, data, isMultipart);
}

async function get(endpoint, params = {}) {
    const query = new URLSearchParams(params).toString();
    return request('GET', endpoint + (query ? `?${query}` : ''));
}

async function deleteRequest(endpoint) {
    await getCsrf();
    return request('DELETE', endpoint);
}

async function login(email, password) {
    const response = await post('/auth/login', { email, password });
    return { token: response.token };
}

async function register(name, email, password, password_confirmation) {
    const response = await post('/auth/register', { name, email, password, password_confirmation });
    return { token: response.token };
}

async function getPosts(filters = {}) {
    return get('/posts', filters);
}

async function getPost(id) {
    return get(`/posts/${id}`);
}

async function createPost(formData) {
    return post('/posts', formData, true);
}

async function updatePost(id, formData) {
    return put(`/posts/${id}`, formData, true);
}

async function deletePost(id) {
    return deleteRequest(`/posts/${id}`);
}

async function getPlatforms() {
    return get('/platforms');
}

async function togglePlatform(platformId, active) {
    return await post('/platforms/toggle', { platform_id: platformId, active });
}

async function getAnalytics() {
    return get('/analytics');
}

async function getProfile() {
    return get('/user');
}

async function updateProfile(data) {
    return put('/user', data);
}

async function deleteProfile() {
    return deleteRequest('/user');
}

export default {
    baseUrl,
    assetBaseUrl,
    login,
    register,
    post,
    getPosts,
    getPost,
    createPost,
    updatePost,
    deletePost,
    getPlatforms,
    togglePlatform,
    getAnalytics,
    getProfile,
    updateProfile,
    deleteProfile,
};