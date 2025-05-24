import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useAuthStore = defineStore('auth', () => {
    const authToken = ref(localStorage.getItem('auth_token') || null);

    const setToken = (token) => {
        authToken.value = token;
        if (token) {
            localStorage.setItem('auth_token', token);
        } else {
            localStorage.removeItem('auth_token');
        }
    };

    const isAuthenticated = () => !!authToken.value;

    return { authToken, setToken, isAuthenticated };
});