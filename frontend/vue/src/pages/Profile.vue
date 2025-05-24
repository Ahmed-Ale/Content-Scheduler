<template>
    <div class="container mx-auto p-4">
        <h1 class="text-2xl font-bold mb-4">Your Profile</h1>
        <div v-if="isLoading" class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
        <div v-else-if="error" class="alert alert-danger">
            {{ error }}
        </div>
        <div v-else>
            <form @submit.prevent="update" class="card p-4 shadow-sm">
                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        class="form-control"
                        :class="{ 'is-invalid': validationErrors.name }"
                        required
                    />
                    <div v-if="validationErrors.name" class="invalid-feedback">
                        {{ validationErrors.name.join(' ') }}
                    </div>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        class="form-control"
                        :class="{ 'is-invalid': validationErrors.email }"
                        required
                    />
                    <div v-if="validationErrors.email" class="invalid-feedback">
                        {{ validationErrors.email.join(' ') }}
                    </div>
                </div>
                <div class="mb-3">
                    <label for="old_password" class="form-label">Current Password</label>
                    <input
                        id="old_password"
                        v-model="form.old_password"
                        type="password"
                        class="form-control"
                        :class="{ 'is-invalid': validationErrors.old_password }"
                        placeholder="Enter current password (required for password change)"
                    />
                    <div v-if="validationErrors.old_password" class="invalid-feedback">
                        {{ validationErrors.old_password.join(' ') }}
                    </div>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">New Password</label>
                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
                        class="form-control"
                        :class="{ 'is-invalid': validationErrors.password }"
                        placeholder="Enter new password (optional)"
                    />
                    <div v-if="validationErrors.password" class="invalid-feedback">
                        {{ validationErrors.password.join(' ') }}
                    </div>
                </div>
                <div class="mb-3">
                    <label for="password_confirmation" class="form-label">Confirm New Password</label>
                    <input
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        type="password"
                        class="form-control"
                        :class="{ 'is-invalid': validationErrors.password_confirmation }"
                        placeholder="Confirm new password"
                    />
                    <div v-if="validationErrors.password_confirmation" class="invalid-feedback">
                        {{ validationErrors.password_confirmation.join(' ') }}
                    </div>
                </div>
                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                    <button type="button" class="btn btn-danger" @click="showDeleteModal">Delete Account</button>
                </div>
                <div v-if="success" class="alert alert-success mt-3">{{ success }}</div>
            </form>
        </div>
        <div class="mt-4">
            <router-link to="/dashboard" class="btn btn-primary">Back to Dashboard</router-link>
        </div>
        <!-- Delete Account Confirmation Modal -->
        <div
            class="modal fade"
            id="deleteAccountModal"
            tabindex="-1"
            aria-labelledby="deleteAccountModalLabel"
            aria-hidden="true"
        >
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteAccountModalLabel">Confirm Account Deletion</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Are you sure you want to delete your account? This action cannot be undone.
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger" @click="deleteAccount">Delete</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import api from '../services/api';
import { useAuthStore } from '../stores/auth';
import { Modal } from 'bootstrap';

export default {
    name: 'Profile',
    setup() {
        const authStore = useAuthStore();
        return { authStore };
    },
    data() {
        return {
            form: {
                name: '',
                email: '',
                old_password: '',
                password: '',
                password_confirmation: '',
            },
            isLoading: false,
            error: null,
            success: null,
            validationErrors: {},
            deleteModal: null,
        };
    },
    mounted() {
        this.fetchProfile();
        this.deleteModal = new Modal(document.getElementById('deleteAccountModal'), {
            backdrop: 'static',
            keyboard: false,
        });
    },
    methods: {
        async fetchProfile() {
            this.isLoading = true;
            this.error = null;
            try {
                const profile = await api.getProfile();
                this.form.name = profile.name;
                this.form.email = profile.email;
            } catch (err) {
                this.error = err.message || 'Failed to load profile';
            } finally {
                this.isLoading = false;
            }
        },
        validateForm() {
            this.validationErrors = {};
            let isValid = true;

            if (this.form.password) {
                if (!this.form.old_password) {
                    this.validationErrors.old_password = ['Current password is required to set a new password.'];
                    isValid = false;
                }
                if (this.form.password.length < 8) {
                    this.validationErrors.password = ['New password must be at least 8 characters.'];
                    isValid = false;
                }
                if (this.form.password !== this.form.password_confirmation) {
                    this.validationErrors.password_confirmation = ['Passwords do not match.'];
                    isValid = false;
                }
            }

            return isValid;
        },
        async update() {
            this.error = null;
            this.success = null;
            this.validationErrors = {};

            if (!this.validateForm()) {
                return;
            }

            try {
                const data = {
                    name: this.form.name,
                    email: this.form.email,
                };
                if (this.form.password) {
                    data.old_password = this.form.old_password;
                    data.password = this.form.password;
                    data.password_confirmation = this.form.password_confirmation;
                }
                await api.updateProfile(data);
                this.success = 'Profile updated successfully';
                this.form.old_password = '';
                this.form.password = '';
                this.form.password_confirmation = '';
            } catch (err) {
                this.error = err.message || 'Failed to update profile';
                if (err.status === 422 && err.errors) {
                    this.validationErrors = Object.keys(err.errors).reduce((acc, key) => ({
                        ...acc,
                        [key]: Array.isArray(err.errors[key]) ? err.errors[key] : [err.errors[key]],
                    }), {});
                    this.error = Object.values(err.errors).flat().join(' ');
                } else if (err.status === 401) {
                    this.error = 'Unauthorized: Please log in again.';
                    this.$router.push('/login');
                }
            }
        },
        async logout() {
            try {
                await api.post('/auth/logout');
            } catch (err) {
            }
            this.authStore.setToken(null);
            this.$router.push('/login');
        },
        async deleteAccount() {
            try {
                await api.deleteProfile();
                await this.logout();
                this.deleteModal.hide();
            } catch (err) {
                this.error = err.message || 'Failed to delete account';
                this.deleteModal.hide();
            }
        },
        showDeleteModal() {
            this.error = null;
            this.success = null;
            this.deleteModal.show();
        },
    },
};
</script>

<style scoped>
.card {
    max-width: 500px;
    margin: 0 auto;
}
.is-invalid {
    border-color: #dc3545;
}
.invalid-feedback {
    color: #dc3545;
    font-size: 0.875em;
}
</style>