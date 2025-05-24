<template>
    <div class="container mt-5">
        <h2 class="text-center mb-4">Post Analytics</h2>
        <div v-if="isLoading" class="text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
        <div v-else-if="error" class="alert alert-danger">
            {{ error }}
        </div>
        <div v-else-if="posts.length === 0" class="alert alert-info">
            No posts available for analytics. Create some posts to see insights.
        </div>
        <div v-else class="row">
            <!-- Posts Per Platform -->
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h3 class="card-title mb-3">Posts Per Platform</h3>
                        <BarChart
                            :chart-data="postsPerPlatformData"
                            :options="chartOptions"
                            :height="200"
                        />
                        <ul class="list-group list-group-flush mt-3">
                            <li
                                v-for="(count, platform) in platformCounts"
                                :key="platform"
                                class="list-group-item d-flex justify-content-between"
                            >
                                <span>{{ platform }}</span>
                                <span>{{ count }} post{{ count !== 1 ? 's' : '' }}</span>
                            </li>
                            <li v-if="!Object.keys(platformCounts).length" class="list-group-item text-muted">
                                No platforms assigned to posts.
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <!-- Publishing Success Rate -->
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h3 class="card-title mb-3">Publishing Success Rate</h3>
                        <PieChart
                            :chart-data="successRateData"
                            :options="chartOptions"
                            :height="200"
                        />
                        <div class="mt-3 text-center">
                            <p v-if="successRate.total > 0">
                                Success Rate: {{ successRate.percentage }}% ({{ successRate.published }} Published, {{ successRate.failed }} Failed)
                            </p>
                            <p v-else class="text-muted">
                                No published or failed posts.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Scheduled vs Published -->
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h3 class="card-title mb-3">Scheduled vs Published</h3>
                        <BarChart
                            :chart-data="scheduledVsPublishedData"
                            :options="chartOptions"
                            :height="200"
                        />
                        <ul class="list-group list-group-flush mt-3">
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Scheduled</span>
                                <span>{{ scheduledCount }} post{{ scheduledCount !== 1 ? 's' : '' }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Published</span>
                                <span>{{ publishedCount }} post{{ publishedCount !== 1 ? 's' : '' }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="text-center mt-3">
            <router-link to="/dashboard" class="btn btn-primary">Back to Dashboard</router-link>
        </div>
    </div>
</template>

<script>
import api from '../services/api';
import { Bar as BarChart, Pie as PieChart } from 'vue-chartjs';
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale,
    ArcElement,
} from 'chart.js';

// Register Chart.js components
ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement);

export default {
    name: 'Analytics',
    components: {
        BarChart,
        PieChart,
    },
    data() {
        return {
            posts: [],
            isLoading: false,
            error: null,
            chartOptions: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: context => {
                                const label = context.dataset.label || '';
                                const value = context.parsed || 0;
                                return `${label}: ${value} post${value !== 1 ? 's' : ''}`;
                            },
                        },
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                        },
                    },
                },
            },
        };
    },
    computed: {
        platformCounts() {
            const counts = {};
            this.posts.forEach(post => {
                post.platforms.forEach(platform => {
                    counts[platform.name] = (counts[platform.name] || 0) + 1;
                });
            });
            return counts;
        },
        postsPerPlatformData() {
            const counts = this.platformCounts;
            return {
                labels: Object.keys(counts).length ? Object.keys(counts) : ['No Platforms'],
                datasets: [{
                    label: 'Posts',
                    data: Object.keys(counts).length ? Object.values(counts) : [0],
                    backgroundColor: ['#007bff', '#28a745', '#dc3545', '#ffc107', '#6f42c1'],
                }],
            };
        },
        successRate() {
            const published = this.posts.filter(post => post.status === 'published').length;
            const failed = this.posts.filter(post => post.status === 'failed').length;
            const total = published + failed;
            const percentage = total ? Math.round((published / total) * 100) : 0;
            return { published, failed, total, percentage };
        },
        successRateData() {
            return {
                labels: ['Published', 'Failed'],
                datasets: [{
                    label: 'Success Rate',
                    data: this.successRate.total ? [this.successRate.published, this.successRate.failed] : [0, 0],
                    backgroundColor: ['#28a745', '#dc3545'],
                    hoverOffset: 20,
                }],
            };
        },
        scheduledCount() {
            return this.posts.filter(post => post.status === 'scheduled').length;
        },
        publishedCount() {
            return this.posts.filter(post => post.status === 'published').length;
        },
        scheduledVsPublishedData() {
            return {
                labels: ['Scheduled', 'Published'],
                datasets: [{
                    label: 'Posts',
                    data: [this.scheduledCount, this.publishedCount],
                    backgroundColor: ['#ffc107', '#28a745'],
                }],
            };
        },
    },
    methods: {
        async fetchPosts() {
            this.isLoading = true;
            this.error = null;
            try {
                const response = await api.getAnalytics();
                this.posts = response || [];
            } catch (err) {
                this.error = 'Failed to load analytics data. Please try again.';
                this.posts = [];
            } finally {
                this.isLoading = false;
            }
        },
    },
    mounted() {
        this.fetchPosts();
    },
};
</script>

<style scoped>
.card {
    padding: 1rem;
}
.chart-container {
    position: relative;
    height: 200px;
}
.list-group-item {
    font-size: 0.9rem;
}
</style>