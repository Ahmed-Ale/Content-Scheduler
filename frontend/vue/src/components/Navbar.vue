<template>
    <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
        <div class="container">
            <router-link class="navbar-brand" to="/">Content Scheduler</router-link>
            <button
                class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#navbarNav"
                aria-controls="navbarNav"
                aria-expanded="false"
                aria-label="Toggle navigation"
            >
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <router-link class="nav-link text-primary" to="/dashboard">Dashboard</router-link>
                    </li>
                    <li class="nav-item">
                        <router-link class="nav-link text-primary" to="/analytics">Analytics</router-link>
                    </li>
                    <li class="nav-item">
                        <router-link class="nav-link text-primary" to="/settings">Settings</router-link>
                    </li>
                    <li class="nav-item">
                        <router-link class="nav-link text-primary" to="/profile">Profile</router-link>
                    </li>
                    <li class="nav-item">
                        <button
                            class="nav-link text-danger"
                            @click="logout"
                            aria-label="Logout"
                        >
                            Logout
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</template>

<script>
import api from '../services/api';
import { useAuthStore } from '../stores/auth';

export default {
    name: 'Navbar',
    setup() {
        const authStore = useAuthStore();
        return { authStore };
    },
    methods: {
        async logout() {
            await api.post('/auth/logout');
            this.authStore.setToken(null);
            this.$router.push('/login');
        },
    },
};
</script>