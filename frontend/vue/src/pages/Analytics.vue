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
        <div v-else-if="!Object.keys(analyticsData.posts_per_platform).length && analyticsData.total === 0" class="alert alert-info">
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
                                v-for="(count, platform) in analyticsData.posts_per_platform"
                                :key="platform"
                                class="list-group-item d-flex justify-content-between"
                            >
                                <span>{{ platform }}</span>
                                <span>{{ count }} post{{ count !== 1 ? 's' : '' }}</span>
                            </li>
                            <li v-if="!Object.keys(analyticsData.posts_per_platform).length" class="list-group-item text-muted">
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
                            <p v-if="analyticsData.total > 0">
                                Success Rate: {{ analyticsData.success_rate }}% ({{ analyticsData.published_count }} Published, {{ analyticsData.failed_count }} Failed)
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
                                <span>{{ analyticsData.scheduled_count }} post{{ analyticsData.scheduled_count !== 1 ? 's' : '' }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Published</span>
                                <span>{{ analyticsData.published_count }} post{{ analyticsData.published_count !== 1 ? 's' : '' }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="text-center mt-3">
            <button @click="exportAnalytics" class="btn btn-secondary me-2" :disabled="isLoading || !Object.keys(analyticsData.posts_per_platform).length">Export as CSV</button>
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
            analyticsData: {
                posts_per_platform: {},
                success_rate: 0,
                scheduled_count: 0,
                published_count: 0,
                failed_count: 0,
                total: 0,
            },
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
        postsPerPlatformData() {
            const counts = this.analyticsData.posts_per_platform;
            return {
                labels: Object.keys(counts).length ? Object.keys(counts) : ['No Platforms'],
                datasets: [{
                    label: 'Posts',
                    data: Object.keys(counts).length ? Object.values(counts) : [0],
                    backgroundColor: ['#007bff', '#28a745', '#dc3545', '#ffc107', '#6f42c1'],
                }],
            };
        },
        successRateData() {
            return {
                labels: ['Published', 'Failed'],
                datasets: [{
                    label: 'Success Rate',
                    data: this.analyticsData.total ? [this.analyticsData.published_count, this.analyticsData.failed_count] : [0, 0],
                    backgroundColor: ['#28a745', '#dc3545'],
                    hoverOffset: 20,
                }],
            };
        },
        scheduledVsPublishedData() {
            return {
                labels: ['Scheduled', 'Published'],
                datasets: [{
                    label: 'Posts',
                    data: [this.analyticsData.scheduled_count, this.analyticsData.published_count],
                    backgroundColor: ['#ffc107', '#28a745'],
                }],
            };
        },
    },
    methods: {
        async fetchAnalytics() {
            this.isLoading = true;
            this.error = null;
            try {
                const response = await api.getAnalytics();
                if (!response || typeof response !== 'object') {
                    throw new Error('Invalid analytics response');
                }
                this.analyticsData = {
                    posts_per_platform: response.posts_per_platform || {},
                    success_rate: response.success_rate || 0,
                    scheduled_count: response.scheduled_count || 0,
                    published_count: response.published_count || 0,
                    failed_count: response.failed_count || 0,
                    total: (response.published_count || 0) + (response.failed_count || 0),
                };
            } catch (err) {
                this.error = err.message || 'Failed to load analytics data. Please try again.';
                this.analyticsData = {
                    posts_per_platform: {},
                    success_rate: 0,
                    scheduled_count: 0,
                    published_count: 0,
                    failed_count: 0,
                    total: 0,
                };
            } finally {
                this.isLoading = false;
            }
        },
        async exportAnalytics() {
            this.isLoading = true;
            this.error = null;
            try {
                const response = await fetch(`${api.baseUrl}/analytics/export`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'text/csv',
                        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
                    },
                    credentials: 'include',
                });

                if (!response.ok) {
                    const json = await response.json();
                    throw new Error(json.message || 'Failed to export analytics');
                }

                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.setAttribute('download', `analytics_export_${new Date().toISOString().replace(/[:.]/g, '-')}.csv`);
                document.body.appendChild(link);
                link.click();
                link.remove();
                window.URL.revokeObjectURL(url);
            } catch (err) {
                this.error = err.message || 'Failed to export analytics. Please try again.';
            } finally {
                this.isLoading = false;
            }
        },
    },
    mounted() {
        this.fetchAnalytics();
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