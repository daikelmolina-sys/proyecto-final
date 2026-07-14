const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

export default async function apiFetch(endpoint, options = {}) {
    const token = localStorage.getItem('auth_token');
    
    const headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...(options.headers || {}),
    };
    
    if (token) {
        headers['Authorization'] = `Bearer ${token}`;
    }
    
    return fetch(`${API_URL}${endpoint}`, { ...options, headers });
}
