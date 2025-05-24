<template>
    <div class="container mt-5 d-flex justify-content-center">
        <div class="w-100" style="max-width: 400px;">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="card-title text-center mb-4">Register</h2>
                    <form @submit.prevent="register">
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                required
                                class="form-control"
                            />
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                required
                                class="form-control"
                                aria-describedby="emailHelp"
                            />
                            <div id="emailHelp" class="form-text">We'll never share your email with anyone else.</div>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input
                                id="password"
                                v-model="form.password"
                                type="password"
                                required
                                class="form-control"
                            />
                        </div>
                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">Confirm Password</label>
                            <input
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                type="password"
                                required
                                class="form-control"
                            />
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Register</button>
                        <div v-if="error" class="alert alert-danger mt-3">{{ error }}</div>
                    </form>
                    <div class="text-center mt-3">
                        <p>Already have an account? <router-link to="/login" class="text-primary">Login</router-link></p>
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
    name: 'Register',
    setup() {
        const authStore = useAuthStore();
        return { authStore };
    },
    data() {
        return {
            form: {
                name: '',
                email: '',
                password: '',
                password_confirmation: '',
            },
            error: null,
        };
    },
    methods: {
        async register() {
            this.error = null;
            try {
                const response = await api.register(this.form.name, this.form.email, this.form.password, this.form.password_confirmation);
                if (!response.token) {
                    throw new Error('No token received from server');
                }
                this.authStore.setToken(response.token);
                this.$router.push('/dashboard');
            } catch (err) {
                this.error = err.message || 'Registration failed';
            }
        },
    },
};
</script>