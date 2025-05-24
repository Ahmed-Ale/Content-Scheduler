import './assets/main.css'
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap';
import 'v-calendar/style.css';
import { createApp } from 'vue'
import { createPinia } from 'pinia';
import App from './App.vue'
import router from './router'
import VC from 'v-calendar';

const app = createApp(App)
app.use(createPinia());
app.use(router)
app.use(VC);

app.mount('#app')
