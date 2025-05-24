<template>
    <div class="container mx-auto p-4">
        <h1 class="text-2xl font-bold mb-4">Settings</h1>
        <div v-if="isLoading" class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
        <div v-else-if="error" class="alert alert-danger">
            {{ error }}
        </div>
        <div v-else-if="platforms.length === 0" class="alert alert-info">
            No platforms available.
        </div>
        <div v-else>
            <h2 class="text-xl font-semibold mb-3">Manage Connected Platforms</h2>
            <div class="list-group">
                <div
                    v-for="platform in platforms"
                    :key="platform.id"
                    class="list-group-item d-flex justify-content-between align-items-center"
                >
                    <span>{{ platform.name }} ({{ platform.type }})</span>
                    <div class="form-check form-switch">
                        <input
                            :id="'platform-' + platform.id"
                            class="form-check-input"
                            type="checkbox"
                            :checked="platform.active"
                            @change="togglePlatform(platform.id, $event.target.checked)"
                        />
                        <label :for="'platform-' + platform.id" class="form-check-label ms-2">
                            {{ platform.active ? 'Active' : 'Inactive' }}
                        </label>
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-4">
            <router-link to="/dashboard" class="btn btn-primary">Back to Dashboard</router-link>
        </div>
    </div>
</template>

<script>
import api from '../services/api';

export default {
    name: 'Settings',
    data() {
        return {
            platforms: [],
            isLoading: false,
            error: null,
        };
    },
    async mounted() {
        await this.fetchPlatforms();
    },
    methods: {
        async fetchPlatforms() {
            this.isLoading = true;
            this.error = null;
            try {
                this.platforms = await api.getPlatforms();
            } catch (err) {
                this.error = err.message || 'Failed to load platforms';
                this.platforms = [];
            } finally {
                this.isLoading = false;
            }
        },
        async togglePlatform(platformId, active) {
            this.error = null;
            try {
                const response = await api.togglePlatform(platformId, active);
                const platform = this.platforms.find(p => p.id === platformId);
                if (platform) {
                    platform.active = active;
                }
            } catch (err) {
                this.error = err.message || 'Failed to toggle platform';
                if (err.status === 422 && err.errors) {
                    this.error = Object.values(err.errors).flat().join(' ');
                }
                await this.fetchPlatforms(); // Revert state
            }
        },
    },
};
</script>

<style scoped>
.list-group-item {
    font-size: 1rem;
}
.form-switch .form-check-input {
    width: 2.5em;
    height: 1.25em;
}
.form-check-label {
    margin-left: 0.5rem; /* Add spacing between toggle and label */
}
</style>