import './bootstrap';
import { createApp } from 'vue';
import router from './router';
import App from './components/App.vue';
// Importar EventBus y Store
import './event-bus';
import './stores/sede-store';


const app = createApp(App);
app.use(router);
app.mount('#app');