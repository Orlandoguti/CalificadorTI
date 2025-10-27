class SedeStore {
    constructor() {
        this._sedeActual = null;
        this._suscribers = [];
    }

    get sedeActual() {
        return this._sedeActual;
    }

    setSede(sede) {
        console.log('🏢 STORE: Actualizando sede:', sede ? `${sede.nombre} (ID: ${sede.id})` : 'Todas las sedes');
        this._sedeActual = sede;
        
        // Notificar a todos los suscriptores
        this._suscribers.forEach(callback => {
            try {
                callback(sede);
            } catch (error) {
                console.error('Error en suscriptor:', error);
            }
        });

        // Emitir evento global
        if (window.EventBus) {
            window.EventBus.emit('sede-cambiada', sede);
        }
    }

    subscribe(callback) {
        this._suscribers.push(callback);
        
        // Devolver función para desuscribirse
        return () => {
            this._suscribers = this._suscribers.filter(cb => cb !== callback);
        };
    }

    getSedeId() {
        return this._sedeActual ? this._sedeActual.id : null;
    }
}

// Crear instancia global
window.SedeStore = new SedeStore();
export default window.SedeStore;