/**
 * Manejador de funcionalidad offline
 * Gestiona el estado de conexión y la cola de sincronización
 */

class OfflineHandler {
  constructor() {
    if (window.__offlineHandlerInstance) {
      return window.__offlineHandlerInstance;
    }
    this.isOnline = navigator.onLine;
    this.syncQueue = [];
    this.syncInProgress = false;
    this.localSyncInProgress = false;
    this.healthcheckInterval = null;
    this.init();
    window.__offlineHandlerInstance = this;
  }

  init() {
    // Escuchar cambios en el estado de conexión
    window.addEventListener('online', () => {
      console.log('✅ Conexión restaurada');
      this.isOnline = true;
      this.syncPendingQueue();
      this.sincronizarCalificacionesLocalStorage(); // 🔥 IMPORTANTE: Sincronizar calificaciones cuando vuelve la conexión
      this.notifyOnline();
    });

    window.addEventListener('offline', () => {
      console.log('⚠️ Sin conexión a internet');
      this.isOnline = false;
      this.notifyOffline();
    });

    // Cargar cola de sincronización desde localStorage
    this.loadSyncQueue();

    // Intentar sincronizar al iniciar si hay conexión
    if (this.isOnline) {
      this.syncPendingQueue();
      this.sincronizarCalificacionesLocalStorage(); // 🔥 IMPORTANTE: Sincronizar calificaciones al iniciar si hay conexión
    }

    // 🔥 NUEVO: Verificación periódica para Fully Kiosk Browser
    // Algunos navegadores kiosk no detectan correctamente los eventos online/offline
    if (this.healthcheckInterval) {
      clearInterval(this.healthcheckInterval);
    }

    this.healthcheckInterval = setInterval(() => {
      const wasOnline = this.isOnline;
      this.isOnline = navigator.onLine;
      
      // Si cambió de offline a online, sincronizar
      if (!wasOnline && this.isOnline) {
        console.log('✅ Conexión detectada (verificación periódica)');
        this.syncPendingQueue();
        this.sincronizarCalificacionesLocalStorage(); // Sincronizar calificaciones guardadas en localStorage
        this.notifyOnline();
      } else if (wasOnline && !this.isOnline) {
        console.log('⚠️ Sin conexión detectado (verificación periódica)');
        this.notifyOffline();
      }
      
      // Si hay conexión y hay peticiones pendientes, intentar sincronizar
      if (this.isOnline && this.syncQueue.length > 0) {
        this.syncPendingQueue();
      }
    }, 5000); // Verificar cada 5 segundos
  }

  /**
   * Agregar petición a la cola de sincronización
   */
  addToSyncQueue(url, method, data, headers = {}) {
    const request = {
      id: Date.now() + Math.random(),
      url,
      method,
      data,
      headers,
      timestamp: new Date().toISOString(),
      retries: 0
    };

    this.syncQueue.push(request);
    this.saveSyncQueue();
    
    console.log('📦 Petición agregada a cola de sincronización:', request);
    
    // Si hay conexión, intentar sincronizar inmediatamente
    if (this.isOnline) {
      this.syncPendingQueue();
    }

    return request.id;
  }

  /**
   * Sincronizar cola de peticiones pendientes
   */
  async syncPendingQueue() {
    if (!this.isOnline || this.syncQueue.length === 0 || this.syncInProgress) {
      return;
    }
    this.syncInProgress = true;
    try {
      console.log(`🔄 Sincronizando ${this.syncQueue.length} peticiones pendientes...`);

      const successful = [];
      
      for (let i = this.syncQueue.length - 1; i >= 0; i--) {
        const request = this.syncQueue[i];
        
        try {
          const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
          
          const response = await fetch(request.url, {
            method: request.method,
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': csrfToken,
              ...request.headers
            },
            body: JSON.stringify(request.data),
            credentials: 'include' // 🔥 IMPORTANTE: Incluir cookies para autenticación en producción
          });

          if (response.ok) {
            console.log('✅ Petición sincronizada:', request.url);
            successful.push(i);
          } else {
            // Si falla, incrementar reintentos
            request.retries++;
            if (request.retries >= 3) {
              console.warn('⚠️ Petición fallida después de 3 intentos, eliminando:', request);
              successful.push(i);
            }
          }
        } catch (error) {
          console.error('❌ Error sincronizando petición:', error);
          request.retries++;
          if (request.retries >= 3) {
            console.warn('⚠️ Petición fallida después de 3 intentos, eliminando:', request);
            successful.push(i);
          }
        }
      }

      // Eliminar peticiones exitosas o con demasiados reintentos
      successful.forEach(index => {
        this.syncQueue.splice(index, 1);
      });

      this.saveSyncQueue();
    } finally {
      this.syncInProgress = false;
    }
  }

  /**
   * Guardar cola en localStorage
   */
  saveSyncQueue() {
    try {
      localStorage.setItem('sync_queue', JSON.stringify(this.syncQueue));
    } catch (error) {
      console.error('Error guardando cola de sincronización:', error);
    }
  }

  /**
   * Cargar cola desde localStorage
   */
  loadSyncQueue() {
    try {
      const saved = localStorage.getItem('sync_queue');
      if (saved) {
        this.syncQueue = JSON.parse(saved);
        console.log(`📦 Cola de sincronización cargada: ${this.syncQueue.length} peticiones`);
      }
    } catch (error) {
      console.error('Error cargando cola de sincronización:', error);
      this.syncQueue = [];
    }
  }

  /**
   * Notificar que hay conexión
   */
  notifyOnline() {
    // Emitir evento personalizado
    window.dispatchEvent(new CustomEvent('connection-status', {
      detail: { online: true }
    }));

    // Mostrar notificación si está disponible
    if ('Notification' in window && Notification.permission === 'granted') {
      new Notification('Conexión restaurada', {
        body: 'Los datos pendientes se sincronizarán automáticamente',
        icon: '/favicon.ico'
      });
    }
  }

  /**
   * Notificar que no hay conexión
   */
  notifyOffline() {
    // Emitir evento personalizado
    window.dispatchEvent(new CustomEvent('connection-status', {
      detail: { online: false }
    }));
  }

  /**
   * Verificar si hay conexión
   */
  checkConnection() {
    return navigator.onLine;
  }

  /**
   * Obtener estado de la cola
   */
  getQueueStatus() {
    return {
      pending: this.syncQueue.length,
      isOnline: this.isOnline
    };
  }

  /**
   * 🔥 NUEVO: Sincronizar calificaciones guardadas en localStorage como respaldo
   */
  async sincronizarCalificacionesLocalStorage() {
    if (this.localSyncInProgress) {
      return;
    }
    this.localSyncInProgress = true;

    try {
      const offlineCalificaciones = JSON.parse(localStorage.getItem('offline_calificaciones') || '[]');
      
      if (offlineCalificaciones.length === 0) {
        return;
      }

      console.log(`🔄 Sincronizando ${offlineCalificaciones.length} calificaciones desde localStorage...`);

      const successful = [];
      
      for (let i = offlineCalificaciones.length - 1; i >= 0; i--) {
        const calificacion = offlineCalificaciones[i];
        
        try {
          const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
          
          const response = await fetch(calificacion.url, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': csrfToken,
              ...(calificacion.headers || {})
            },
            body: JSON.stringify(calificacion.data),
            credentials: 'include' // 🔥 IMPORTANTE: Incluir cookies para autenticación
          });

          if (response.ok) {
            const result = await response.json();
            console.log('✅ Calificación sincronizada desde localStorage:', calificacion.url, result);
            successful.push(i);
          } else {
            const errorText = await response.text();
            console.warn('⚠️ Error sincronizando calificación desde localStorage:', response.status, errorText);
            // Si es un error 401/403, puede ser problema de autenticación, no eliminar
            if (response.status === 401 || response.status === 403) {
              console.warn('⚠️ Error de autenticación, manteniendo calificación en cola');
            }
          }
        } catch (error) {
          console.error('❌ Error sincronizando calificación desde localStorage:', error);
          // Si hay error de red, mantener en la cola
          if (!navigator.onLine) {
            console.log('⚠️ Sin conexión, manteniendo calificación en cola');
          }
        }
      }

      // Eliminar calificaciones sincronizadas exitosamente
      successful.forEach(index => {
        offlineCalificaciones.splice(index, 1);
      });

      if (successful.length > 0) {
        if (offlineCalificaciones.length > 0) {
          localStorage.setItem('offline_calificaciones', JSON.stringify(offlineCalificaciones));
        } else {
          localStorage.removeItem('offline_calificaciones');
        }
        console.log(`✅ ${successful.length} calificaciones sincronizadas desde localStorage`);
        
        // 🔥 NUEVO: Emitir evento para que los componentes puedan escuchar
        window.dispatchEvent(new CustomEvent('calificaciones-sincronizadas', {
          detail: { 
            sincronizadas: successful.length,
            pendientes: offlineCalificaciones.length
          }
        }));
      }
    } catch (error) {
      console.error('❌ Error sincronizando calificaciones desde localStorage:', error);
    } finally {
      this.localSyncInProgress = false;
    }
  }
}

// Inicialización singleton (evita múltiples instancias y eventos duplicados)
if (!window.offlineHandler) {
  window.offlineHandler = new OfflineHandler();
  console.log('✅ OfflineHandler inicializado (singleton)');
}

export default window.offlineHandler;
