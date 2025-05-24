<template>
    <div class="container mt-5">
        <h2 class="text-center mb-4">Dashboard</h2>
        <div class="row">
            <!-- Calendar View -->
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h3 class="card-title mb-3">Scheduled Posts Calendar</h3>
                        <v-calendar
                            expanded
                            :attributes="calendarAttributes"
                            :masks="{ weekdays: 'WW' }"
                            class="custom-calendar"
                            @dayclick="onDayClick"
                        />
                    </div>
                </div>
            </div>
            <!-- List View and Filters -->
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h3 class="card-title mb-3">Post List</h3>
                        <!-- Filters -->
                        <form class="mb-3" @submit.prevent>
                            <div class="row g-3">
                                <div class="col">
                                    <label for="statusFilter" class="form-label">Status</label>
                                    <select
                                        id="statusFilter"
                                        v-model="filters.status"
                                        class="form-select"
                                        @change="fetchPosts"
                                    >
                                        <option value="">All</option>
                                        <option value="scheduled">Scheduled</option>
                                        <option value="published">Published</option>
                                        <option value="failed">Failed</option>
                                    </select>
                                </div>
                                <div class="col">
                                    <label for="dateFilter" class="form-label">Date</label>
                                    <input
                                        type="date"
                                        id="dateFilter"
                                        v-model="filters.date"
                                        class="form-control"
                                        @change="fetchPosts"
                                    />
                                </div>
                            </div>
                            <div class="mt-2">
                                <button
                                    type="button"
                                    class="btn btn-outline-secondary btn-sm"
                                    @click="clearFilters"
                                >
                                    Clear Filters
                                </button>
                            </div>
                        </form>
                        <!-- Post List -->
                        <div v-if="isLoading" class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                        <div v-else-if="error" class="alert alert-danger">
                            {{ error }}
                        </div>
                        <div v-else-if="posts.length === 0" class="alert alert-info">
                            No posts match the selected filters.
                        </div>
                        <div v-else class="list-group">
                            <div
                                v-for="post in posts"
                                :key="post.id"
                                class="list-group-item d-flex align-items-start"
                            >
                                <div v-if="post.image_url" class="me-3">
                                    <img
                                        :src="getImageUrl(post.image_url)"
                                        alt="Post Image"
                                        class="rounded"
                                        style="width: 50px; height: 50px; object-fit: cover;"
                                        loading="lazy"
                                        @error="handleImageError(post)"
                                    />
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="mb-1">{{ post.title }}</h5>
                                    <p class="mb-1 text-muted">{{ truncateContent(post.content) }}</p>
                                    <small class="text-muted">
                                        Scheduled: {{ formatDate(post.scheduled_time) }}
                                    </small>
                                    <br />
                                    <small class="text-muted">
                                        Platforms: {{ post.platforms.map(p => p.name).join(', ') || 'None' }}
                                    </small>
                                </div>
                                <div class="d-flex align-items-center">
                                    <span
                                        :class="{
                                            'badge bg-warning': post.status === 'scheduled',
                                            'badge bg-success': post.status === 'published',
                                            'badge bg-danger': post.status === 'failed',
                                        }"
                                    >
                                        {{ capitalize(post.status) }}
                                    </span>
                                    <router-link
                                        :to="`/edit/${post.id}`"
                                        class="btn btn-sm btn-outline-primary ms-2"
                                        aria-label="Edit post"
                                    >
                                        Edit
                                    </router-link>
                                    <button
                                        class="btn btn-sm btn-outline-danger ms-2"
                                        @click="showDeleteModal(post.id, post.title)"
                                        aria-label="Delete post"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="text-center mt-3">
            <router-link to="/create" class="btn btn-primary">Create New Post</router-link>
        </div>
        <!-- Delete Confirmation Modal -->
        <div
            class="modal fade"
            id="deleteModal"
            tabindex="-1"
            aria-labelledby="deleteModalLabel"
            aria-hidden="true"
        >
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Are you sure you want to delete the post "<strong>{{ deletePostTitle }}</strong>"? This action cannot be undone.
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger" @click="deletePost">Delete</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import api from '../services/api';
import { format } from 'date-fns';
import { Modal } from 'bootstrap';

export default {
    name: 'Dashboard',
    data() {
        return {
            posts: [],
            allPosts: [],
            filters: {
                status: '',
                date: '',
            },
            isLoading: false,
            error: null,
            deletePostId: null,
            deletePostTitle: '',
            deleteModal: null,
        };
    },
    computed: {
        calendarAttributes() {
            return this.allPosts
                .filter(post => post.scheduled_time)
                .map(post => ({
                    key: post.id,
                    highlight: {
                        class: post.status === 'scheduled' ? 'bg-warning' :
                            post.status === 'published' ? 'bg-success' :
                                'bg-danger',
                    },
                    dates: new Date(post.scheduled_time),
                    popover: {
                        label: post.title,
                    },
                }));
        },
    },
    mounted() {
        this.fetchPosts();
        // Initialize Bootstrap modal
        this.deleteModal = new Modal(document.getElementById('deleteModal'), {
            backdrop: 'static',
            keyboard: false,
        });
    },
    methods: {
        async fetchPosts() {
            this.isLoading = true;
            this.error = null;
            try {
                const filters = {};
                if (this.filters.status) filters.status = this.filters.status;
                if (this.filters.date) filters.date = this.filters.date;
                const fetchedPosts = await api.getPosts(filters);
                this.posts = fetchedPosts || [];
                if (!filters.status && !filters.date) {
                    this.allPosts = [...this.posts];
                }
            } catch (err) {
                this.error = 'Failed to load posts. Please try again.';
                this.posts = [];
            } finally {
                this.isLoading = false;
            }
        },
        showDeleteModal(postId, postTitle) {
            this.deletePostId = postId;
            this.deletePostTitle = postTitle;
            this.deleteModal.show();
        },
        async deletePost() {
            try {
                await api.deletePost(this.deletePostId);
                this.posts = this.posts.filter(post => post.id !== this.deletePostId);
                this.allPosts = this.allPosts.filter(post => post.id !== this.deletePostId);
                this.error = null;
                this.deleteModal.hide();
            } catch (err) {
                this.error = err.status === 403
                    ? 'You are not authorized to delete this post.'
                    : 'Failed to delete post. Please try again.';
                this.deleteModal.hide();
            }
        },
        formatDate(date) {
            return date ? format(new Date(date), 'MMM d, yyyy HH:mm') : 'N/A';
        },
        truncateContent(content) {
            if (!content) return 'No content';
            return content.length > 50 ? content.substring(0, 47) + '...' : content;
        },
        onDayClick(day) {
            this.filters.date = format(new Date(day.date), 'yyyy-MM-dd');
            this.fetchPosts();
        },
        clearFilters() {
            this.filters.status = '';
            this.filters.date = '';
            this.fetchPosts();
        },
        capitalize(value) {
            if (!value) return '';
            return value.charAt(0).toUpperCase() + value.slice(1);
        },
        getImageUrl(imageUrl) {
            return imageUrl ? `${api.assetBaseUrl}/storage/${imageUrl}` : null;
        },
        handleImageError(post) {
            post.image_url = null;
        },
    },
};
</script>

<style scoped>
.list-group-item {
    padding: 1rem;
}
img {
    display: block;
}
</style>