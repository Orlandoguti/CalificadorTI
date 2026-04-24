// Service Worker para Calificador UNIFRANZ - Funcionalidad Offline
const CACHE_NAME = 'calificador-unifranz-v2'; // Incrementar versión para limpiar cache antiguo
const RUNTIME_CACHE = 'calificador-runtime-v2';
const API_CACHE = 'calificador-api-v2';

// Recursos estáticos a cachear en la instalación
const STATIC_ASSETS = [
  '/',
  '/manifest.json',
  '/favicon.ico',
  // Los assets JS y CSS se agregarán dinámicamente desde el manifest de Vite
];

// Rutas API que se pueden cachear (solo GET)
// Se usarán patrones para coincidir con parámetros dinámicos
const CACHEABLE_API_PATTERNS = [
  /^\/api\/sedes/,                          // /api/sedes y /api/sedes/buscar
  /^\/api\/areas/,                          // /api/areas, /api/areas/public, /api/areas/{id}
  /^\/api\/niveles-calificacion/,          // /api/niveles-calificacion
  /^\/api\/preguntas/,                      // /api/preguntas con query params
  /^\/api\/subpreguntas\/\d+/,              // /api/subpreguntas/{id}
  /^\/api\/tipos-calificacion/,             // /api/tipos-calificacion
  /^\/api\/estadisticas/,                   // /api/estadisticas con query params
];

// Instalación del Service Worker
self.addEventListener('install', (event) => {
  console.log('[SW] Instalando Service Worker...');
  event.waitUntil(
    caches.open(CACHE_NAME).then(async (cache) => {
      console.log('[SW] Cacheando recursos estáticos');
      
      // Intentar cargar el manifest de Vite para cachear assets
      try {
        const manifestResponse = await fetch('/build/manifest.json');
        if (manifestResponse.ok) {
          const manifest = await manifestResponse.json();
          const assetsToCache = Object.values(manifest)
            .map(entry => entry.file)
            .filter(file => file.endsWith('.js') || file.endsWith('.css'))
            .map(file => `/build/${file}`);
          
          await cache.addAll([...STATIC_ASSETS, ...assetsToCache]);
        } else {
          await cache.addAll(STATIC_ASSETS);
        }
      } catch (error) {
        console.log('[SW] No se pudo cargar manifest de Vite, cacheando solo assets básicos');
        await cache.addAll(STATIC_ASSETS);
      }
    })
  );
  self.skipWaiting(); // Activar inmediatamente
});

// Activación del Service Worker
self.addEventListener('activate', (event) => {
  console.log('[SW] Activando Service Worker...');
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME && 
              cacheName !== RUNTIME_CACHE && 
              cacheName !== API_CACHE) {
            console.log('[SW] Eliminando cache antiguo:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
  return self.clients.claim(); // Tomar control de todas las páginas
});

// Interceptar peticiones
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Ignorar peticiones que no son GET
  if (request.method !== 'GET') {
    return;
  }

  // Estrategia para assets estáticos (JS, CSS, imágenes)
  if (url.pathname.startsWith('/build/') || 
      url.pathname.startsWith('/imagen/') ||
      url.pathname.endsWith('.js') ||
      url.pathname.endsWith('.css') ||
      url.pathname.endsWith('.png') ||
      url.pathname.endsWith('.jpg') ||
      url.pathname.endsWith('.gif') ||
      url.pathname.endsWith('.ico')) {
    event.respondWith(cacheFirst(request));
    return;
  }

  // Estrategia para rutas API
  if (url.pathname.startsWith('/api/')) {
    // Verificar si coincide con algún patrón cacheable
    const fullPath = url.pathname + (url.search || '');
    const isCacheable = CACHEABLE_API_PATTERNS.some(pattern => 
      pattern.test(fullPath)
    );
    
    if (isCacheable) {
      event.respondWith(networkFirstWithCache(request));
      return;
    }
  }

  // Estrategia para HTML (páginas)
  if (request.headers.get('accept')?.includes('text/html')) {
    event.respondWith(networkFirstWithCache(request));
    return;
  }

  // Para todo lo demás, intentar red y luego cache
  event.respondWith(networkFirstWithCache(request));
});

// Estrategia: Cache First (para assets estáticos)
async function cacheFirst(request) {
  const cache = await caches.open(CACHE_NAME);
  const cached = await cache.match(request);
  
  if (cached) {
    return cached;
  }

  try {
    const response = await fetch(request);
    if (response.ok) {
      cache.put(request, response.clone());
    }
    return response;
  } catch (error) {
    console.log('[SW] Error en cacheFirst:', error);
    // Si es una imagen, devolver una imagen placeholder
    if (request.url.match(/\.(png|jpg|gif|ico)$/)) {
      return new Response('', { status: 404 });
    }
    throw error;
  }
}

// Estrategia: Network First con fallback a cache (para APIs y HTML)
async function networkFirstWithCache(request) {
  const cache = await caches.open(API_CACHE);
  const url = new URL(request.url);
  
  // NO cachear rutas de autenticación/usuario
  const noCacheRoutes = [
    '/api/user',
    '/api/logout',
    '/login',
    '/logout'
  ];
  
  const shouldNotCache = noCacheRoutes.some(route => url.pathname.includes(route));
  
  // Crear clave de cache normalizada
  const cacheKey = new Request(request.url, { method: 'GET' });
  
  try {
    // Intentar red primero
    const response = await fetch(request);
    
    // Solo cachear respuestas exitosas (200-299) y que no sean rutas de auth
    if (response.ok && response.status >= 200 && response.status < 300 && !shouldNotCache) {
      // Verificar que sea JSON válido antes de cachear
      const contentType = response.headers.get('content-type');
      if (contentType && contentType.includes('application/json')) {
        // Cachear la respuesta exitosa (clonar porque el body solo se puede leer una vez)
        const responseClone = response.clone();
        cache.put(cacheKey, responseClone).catch(err => {
          console.log('[SW] Error guardando en cache:', err);
        });
      }
      return response;
    }
    
    // Si la respuesta no es OK o es una ruta de auth, NO usar cache
    if (shouldNotCache || response.status >= 400) {
      return response;
    }
    
    // Si la respuesta no es OK pero no es error de auth, intentar cache
    const cached = await cache.match(cacheKey);
    if (cached) {
      console.log('[SW] Respuesta no OK, usando cache:', request.url);
      return cached;
    }
    
    return response;
  } catch (error) {
    console.log('[SW] Sin conexión, buscando en cache:', request.url);
    
    // NO usar cache para rutas de autenticación/usuario cuando hay error
    if (shouldNotCache) {
      return new Response(
        JSON.stringify({ 
          error: 'Sin conexión a internet. Autenticación no disponible offline.',
          offline: true 
        }),
        {
          status: 503,
          headers: { 'Content-Type': 'application/json' }
        }
      );
    }
    
    // Sin conexión, buscar en cache con diferentes estrategias
    // 1. Buscar exacto (con query params)
    let cached = await cache.match(cacheKey);
    
    // 2. Si no encuentra, buscar por pathname (ignorar query params)
    if (!cached && url.pathname.startsWith('/api/')) {
      const pathOnlyKey = new Request(url.origin + url.pathname, { method: 'GET' });
      cached = await cache.match(pathOnlyKey);
    }
    
    // 3. Si aún no encuentra, buscar cualquier coincidencia del mismo endpoint
    if (!cached) {
      const allCached = await cache.keys();
      const matchingCache = allCached.find(cachedReq => {
        const cachedUrl = new URL(cachedReq.url);
        // Coincidir por pathname (útil para APIs con diferentes query params)
        return cachedUrl.pathname === url.pathname;
      });
      if (matchingCache) {
        cached = await cache.match(matchingCache);
        console.log('[SW] ✅ Cache encontrado por pathname:', request.url);
      }
    }
    
    if (cached) {
      // Verificar que el cache sea JSON válido
      const contentType = cached.headers.get('content-type');
      if (contentType && contentType.includes('application/json')) {
        // Verificar que no sea una respuesta de error cacheada incorrectamente
        const status = cached.status;
        if (status >= 200 && status < 300) {
          console.log('[SW] ✅ Cache encontrado para:', request.url);
          return cached;
        } else {
          // Eliminar cache incorrecto
          console.log('[SW] ⚠️ Cache con error, eliminando:', request.url);
          cache.delete(cacheKey).catch(() => {});
        }
      } else {
        console.log('[SW] ⚠️ Cache no es JSON válido, ignorando:', request.url);
        // Eliminar cache incorrecto
        cache.delete(cacheKey).catch(() => {});
      }
    }
    
    // Si no hay cache, devolver error apropiado
    if (request.url.includes('/api/')) {
      console.log('[SW] ❌ No hay cache disponible para:', request.url);
      return new Response(
        JSON.stringify({ 
          error: 'Sin conexión a internet. Datos no disponibles offline.',
          offline: true 
        }),
        {
          status: 503,
          headers: { 'Content-Type': 'application/json' }
        }
      );
    }
    
    // Para HTML, devolver la página principal desde cache
    const indexCache = await caches.open(CACHE_NAME);
    const indexCached = await indexCache.match('/');
    if (indexCached) {
      return indexCached;
    }
    
    throw error;
  }
}

// Escuchar mensajes del cliente para actualizar cache
self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
  
  if (event.data && event.data.type === 'CACHE_API') {
    const { url, data } = event.data;
    caches.open(API_CACHE).then((cache) => {
      cache.put(url, new Response(JSON.stringify(data), {
        headers: { 'Content-Type': 'application/json' }
      }));
    });
  }
});
