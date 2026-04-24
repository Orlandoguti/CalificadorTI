/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Interceptor para manejar peticiones offline
axios.interceptors.response.use(
  (response) => response,
  async (error) => {
    // Si es un error de red y hay un handler offline
    if (!navigator.onLine && error.config && window.offlineHandler) {
      // Si es una petición POST/PUT/DELETE, agregar a cola
      const method = error.config.method.toUpperCase();
      if (['POST', 'PUT', 'DELETE'].includes(method)) {
        const requestId = window.offlineHandler.addToSyncQueue(
          error.config.url,
          method,
          error.config.data ? JSON.parse(error.config.data) : {},
          error.config.headers
        );
        
        // Retornar una respuesta simulada indicando que se guardó offline
        return Promise.resolve({
          data: {
            success: true,
            offline: true,
            message: 'Datos guardados localmente. Se sincronizarán cuando haya conexión.',
            requestId
          },
          status: 202,
          statusText: 'Accepted',
          headers: {},
          config: error.config
        });
      }
    }
    
    return Promise.reject(error);
  }
);

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

// import Echo from 'laravel-echo';

// import Pusher from 'pusher-js';
// window.Pusher = Pusher;

// window.Echo = new Echo({
//     broadcaster: 'pusher',
//     key: import.meta.env.VITE_PUSHER_APP_KEY,
//     cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
//     wsHost: import.meta.env.VITE_PUSHER_HOST ? import.meta.env.VITE_PUSHER_HOST : `ws-${import.meta.env.VITE_PUSHER_APP_CLUSTER}.pusher.com`,
//     wsPort: import.meta.env.VITE_PUSHER_PORT ?? 80,
//     wssPort: import.meta.env.VITE_PUSHER_PORT ?? 443,
//     forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
//     enabledTransports: ['ws', 'wss'],
// });
