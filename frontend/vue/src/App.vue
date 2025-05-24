<template>
    <div>
        <Navbar v-if="authStore.isAuthenticated() && !isAuthRoute" />
        <router-view />
    </div>
</template>

<script>
import Navbar from './components/Navbar.vue';
import { useAuthStore } from './stores/auth';

export default {
    name: 'App',
    components: {
        Navbar,
    },
    setup() {
        const authStore = useAuthStore();
        return { authStore };
    },
    computed: {
        isAuthRoute() {
            return ['/login', '/register'].includes(this.$route.path);
        },
    },
};
</script>