<template>
    <div class="container mt-5 d-flex justify-content-center">
        <div class="w-100" style="max-width: 600px;">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="card-title text-center mb-4">Edit Post</h2>
                    <form @submit.prevent="submitPost" novalidate>
                        <div class="mb-3">
                            <label for="title" class="form-label">Title</label>
                            <input
                                id="title"
                                v-model="form.title"
                                type="text"
                                required
                                class="form-control"
                                :class="{ 'is-invalid': formErrors.title }"
                                placeholder="Enter post title"
                            />
                            <div v-if="formErrors.title" class="invalid-feedback">
                                {{ formErrors.title[0] }}
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="content" class="form-label">Content</label>
                            <textarea
                                id="content"
                                v-model="form.content"
                                required
                                rows="5"
                                class="form-control"
                                :class="{ 'is-invalid': formErrors.content }"
                                placeholder="Enter post content"
                            ></textarea>
                            <div v-if="formErrors.content" class="invalid-feedback">
                                {{ formErrors.content[0] }}
                            </div>
                            <div class="form-text mt-1">Characters: {{ form.content.length }}</div>
                        </div>
                        <div class="mb-3">
                            <label for="image" class="form-label">Image</label>
                            <input
                                id="image"
                                type="file"
                                accept="image/*"
                                class="form-control"
                                :class="{ 'is-invalid': formErrors.image }"
                                @change="handleImageUpload"
                            />
                            <div v-if="formErrors.image" class="invalid-feedback">
                                {{ formErrors.image[0] }}
                            </div>
                            <div v-if="imagePreview" class="mt-2">
                                <img :src="imagePreview" alt="Image Preview" class="img-fluid rounded" style="max-height: 200px;" />
                            </div>
                            <div v-else-if="form.image_url" class="mt-2">
                                <img :src="form.image_url" alt="Current Image" class="img-fluid rounded" style="max-height: 200px;" />
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="scheduled_time" class="form-label">Scheduled Time (EEST)</label>
                            <input
                                id="scheduled_time"
                                type="datetime-local"
                                v-model="form.scheduled_time"
                                required
                                class="form-control"
                                :class="{ 'is-invalid': formErrors.scheduled_time }"
                            />
                            <div v-if="formErrors.scheduled_time" class="invalid-feedback">
                                {{ formErrors.scheduled_time[0] }}
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Select Platforms</label>
                            <div v-if="activePlatforms.length === 0" class="text-danger">
                                No active platforms. Please enable platforms in
                                <router-link to="/settings">Settings</router-link>.
                            </div>
                            <div v-else v-for="platform in activePlatforms" :key="platform.id" class="form-check">
                                <input
                                    :id="'platform-' + platform.id"
                                    type="checkbox"
                                    v-model="form.platforms"
                                    :value="platform.id"
                                    class="form-check-input"
                                    :class="{ 'is-invalid': formErrors.platforms }"
                                />
                                <label :for="'platform-' + platform.id" class="form-check-label">{{ platform.name }}</label>
                            </div>
                            <div v-if="formErrors.platforms" class="invalid-feedback d-block">
                                {{ formErrors.platforms[0] }}
                            </div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary">Update Post</button>
                            <router-link to="/dashboard" class="btn btn-secondary">Cancel</router-link>
                        </div>
                        <div v-if="error" class="alert alert-danger mt-3">{{ error }}</div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import api from '../services/api';
import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';
import timezone from 'dayjs/plugin/timezone';

dayjs.extend(utc);
dayjs.extend(timezone);

export default {
    name: 'PostEditor',
    data() {
        return {
            form: {
                title: '',
                content: '',
                image: null,
                image_url: null,
                scheduled_time: '',
                platforms: [],
            },
            platforms: [],
            imagePreview: null,
            error: null,
            formErrors: {},
        };
    },
    computed: {
        activePlatforms() {
            return this.platforms.filter(platform => platform.active);
        },
    },
    async created() {
        try {
            this.platforms = await api.getPlatforms();
            await this.loadPost();
        } catch (err) {
            this.error = 'Failed to load data';
        }
    },
    methods: {
        async loadPost() {
            try {
                const post = await api.getPost(this.$route.params.id);
                this.form.title = post.title || '';
                this.form.content = post.content || '';
                this.form.image_url = post.image_url ? `${api.assetBaseUrl}/storage/${post.image_url}` : null;
                this.form.scheduled_time = post.scheduled_time
                    ? dayjs.utc(post.scheduled_time).tz('Europe/Tallinn').format('YYYY-MM-DDTHH:mm')
                    : '';
                this.form.platforms = post.platforms ? post.platforms.map(p => p.id) : [];
            } catch (err) {
                this.error = 'Failed to load post';
            }
        },
        handleImageUpload(event) {
            const file = event.target.files[0];
            this.form.image = file;
            if (file) {
                this.imagePreview = URL.createObjectURL(file);
            } else {
                this.imagePreview = null;
            }
        },
        async submitPost() {
            this.error = null;
            this.formErrors = {};
            try {
                const formData = new FormData();
                formData.append('title', this.form.title.trim() || '');
                formData.append('content', this.form.content.trim() || '');
                if (this.form.image instanceof File) {
                    formData.append('image', this.form.image);
                }
                formData.append('scheduled_time', this.form.scheduled_time
                    ? dayjs(this.form.scheduled_time).tz('Europe/Tallinn', true).format('YYYY-MM-DD HH:mm:ss')
                    : '');
                this.form.platforms.forEach((id, index) => formData.append(`platforms[${index}]`, id));

                await api.updatePost(this.$route.params.id, formData);
                this.$router.push('/dashboard');
            } catch (err) {
                if (err.status === 429) {
                    this.error = 'You have reached the daily post limit.';
                } else if (err.status === 422 && err.errors) {
                    this.formErrors = err.errors;
                    this.error = 'Please correct the errors in the form.';
                } else {
                    this.error = err.message || 'Failed to update post';
                }
            }
        },
    },
};
</script>