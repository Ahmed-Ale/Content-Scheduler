<template>
    <div class="container mt-5 d-flex justify-content-center">
        <div class="w-100" style="max-width: 400px;">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="card-title text-center mb-4">Login</h2>
                    <form @submit.prevent="login">
                        <div class="mb-3">
                            <label for="exampleInputEmail1" class="form-label">Email address</label>
                            <input
                                type="email"
                                class="form-control"
                                id="exampleInputEmail1"
                                aria-describedby="emailHelp"
                                v-model="email"
                                required
                            />
                            <div id="emailHelp" class="form-text">We'll never share your email with anyone else.</div>
                        </div>
                        <div class="mb-3">
                            <label for="exampleInputPassword1" class="form-label">Password</label>
                            <input
                                type="password"
                                class="form-control"
                                id="exampleInputPassword1"
                                v-model="password"
                                required
                            />
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                        <div v-if="error" class="alert alert-danger mt-3">{{ error }}</div>
                    </form>
                    <div class="text-center mt-3">
                        <p>Don't have an account? <router-link to="/register" class="text-primary">Register</router-link></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import api from '../services/api';
import { useAuthStore } from '../stores/auth';

export default {
    name: 'Login',
    setup() {
        const authStore = useAuthStore();
        return { authStore };
    },
    data() {
        return {
            email: '',
            password: '',
            error: null,
        };
    },
    methods: {
        async login() {
            this.error = null;
            try {
                const response = await api.login(this.email, this.password);
                if (!response.token) {
                    throw new Error('No token received from server');
                }
                this.authStore.setToken(response.token);
                this.$router.push('/dashboard');
            } catch (err) {
                this.error = err.message || 'Login failed';
            }
        },
    },
};
</script>