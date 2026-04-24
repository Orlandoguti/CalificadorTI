<template>
  <div v-if="!isOnline" class="offline-indicator">
    <div class="offline-banner" style="justify-self: center;">
      <i class="fas fa-exclamation-triangle" style="color: black;"></i>     
      <span v-if="pendingSync > 0" class="pending-count">
        {{ pendingSync }} {{ pendingSync === 1 ? 'petición' : 'peticiones' }} pendientes
      </span>
    </div>
  </div>
  <div v-else-if="pendingSync > 0 && wasOffline" class="sync-indicator">
    <div class="sync-banner">
      <i class="fas fa-sync-alt fa-spin"></i>
      <span>Sincronizando {{ pendingSync }} {{ pendingSync === 1 ? 'petición' : 'peticiones' }} pendientes...</span>
    </div>
  </div>
</template>

<script>
export default {
  name: 'OfflineIndicator',
  data() {
    return {
      isOnline: navigator.onLine,
      pendingSync: 0,
      wasOffline: false
    };
  },
  mounted() {
    // Escuchar cambios en el estado de conexión
    window.addEventListener('online', this.handleOnline);
    window.addEventListener('offline', this.handleOffline);
    window.addEventListener('connection-status', this.handleConnectionStatus);
    
    // Verificar estado inicial
    this.updatePendingSync();
    
    // Actualizar cada 5 segundos
    this.syncInterval = setInterval(() => {
      this.updatePendingSync();
    }, 5000);
  },
  beforeUnmount() {
    window.removeEventListener('online', this.handleOnline);
    window.removeEventListener('offline', this.handleOffline);
    window.removeEventListener('connection-status', this.handleConnectionStatus);
    if (this.syncInterval) {
      clearInterval(this.syncInterval);
    }
  },
  methods: {
    handleOnline() {
      this.isOnline = true;
      this.updatePendingSync();
    },
    handleOffline() {
      this.isOnline = false;
      this.wasOffline = true;
      this.updatePendingSync();
    },
    handleConnectionStatus(event) {
      this.isOnline = event.detail.online;
      this.updatePendingSync();
    },
    updatePendingSync() {
      if (window.offlineHandler) {
        const status = window.offlineHandler.getQueueStatus();
        this.pendingSync = status.pending;
        this.isOnline = status.isOnline;
      }
    }
  }
};
</script>

<style scoped>
.offline-indicator {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 10000;
  animation: slideDown 0.3s ease-out;
}

.offline-banner {
  background: linear-gradient(135deg, #f59f0b00 0%, #d9770600 100%);
  color: white;
  padding: 12px 20px;
  display: flex;
  align-items: center;
  gap: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0);
  font-size: 14px;
}

.offline-banner i {
  font-size: 18px;
  flex-shrink: 0;
  min-width: 20px;
  display: inline-block;
  text-align: center;
}

.offline-banner span {
  flex: 1;
}

.pending-count {
  background: rgba(255, 255, 255, 0.2);
  padding: 4px 12px;
  border-radius: 12px;
  font-weight: 600;
  font-size: 12px;
  white-space: nowrap;
}

.sync-indicator {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 10000;
  animation: slideDown 0.3s ease-out;
}

.sync-banner {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
  padding: 12px 20px;
  display: flex;
  align-items: center;
  gap: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
  font-size: 14px;
}

.sync-banner i {
  font-size: 18px;
  flex-shrink: 0;
  min-width: 20px;
  display: inline-block;
  text-align: center;
}

.sync-banner span {
  flex: 1;
}

@keyframes slideDown {
  from {
    transform: translateY(-100%);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

@media (max-width: 768px) {
  .offline-banner,
  .sync-banner {
    font-size: 12px;
    padding: 10px 15px;
    flex-wrap: wrap;
  }
  
  .pending-count {
    width: 100%;
    margin-top: 4px;
    text-align: center;
  }
}
</style>
