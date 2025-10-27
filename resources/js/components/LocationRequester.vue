<template>
    <div class="location-requester">
        <div class="modal-overlay">
            <div class="modal-content">
                <div class="header">
                    <i class="fas fa-map-marker-alt"></i>
                    <h2>Selección de Sede</h2>
                </div>
                
                <p>Para continuar, necesitamos conocer tu ubicación para asignarte a la sede correcta.</p>
                
                <div v-if="error" class="error-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    {{ error }}
                </div>

                <!-- Modal de error cuando no está en sede -->
                <div v-if="showNoSedeModal" class="no-sede-modal">
                    <div class="modal-inner">
                        <div class="error-icon">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                        <h3>Ubicación fuera del alcance</h3>
                        <p>No estás dentro del área de ninguna sede de UNIFRANZ.</p>
                        <p class="modal-detail" v-if="errorDistancia">
                            Estás a <strong>{{ errorDistancia }} km</strong> de la sede más cercana.
                        </p>
                        <p class="modal-detail">Debes estar físicamente en una de las sedes para utilizar el sistema de calificación.</p>
                        
                        <details style="margin-top: 20px; text-align: left; cursor: pointer;">
                            <summary style="padding: 10px; background: #f3f4f6; border-radius: 8px; margin-bottom: 10px;">
                                <strong>Ver coordenadas y debug info</strong>
                            </summary>
                            <div style="padding: 15px; background: #f9fafb; border-radius: 8px; font-size: 0.85rem; color: #4b5563;">
                                <p><strong>Coordenadas detectadas:</strong></p>
                                <p v-if="debugCoords">
                                    Lat: {{ debugCoords.lat }}, Lng: {{ debugCoords.lng }}
                                </p>
                            </div>
                        </details>
                        
                        <button @click="closeModal" class="btn-close-modal">Cerrar</button>
                    </div>
                </div>

                

                <div class="actions">
                    <button @click="getLocation" class="btn-primary" :disabled="isLoading" style="margin-top: 5%;">
                        <i class="fas fa-satellite-dish"></i>
                        <span v-if="isLoading">Detectando...</span>
                        <span v-else>Detectar Automáticamente</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    name: 'LocationRequester',
    data() {
        return {
            sedes: [],
            error: null,
            isLoading: false,
            showNoSedeModal: false,
            errorDistancia: null,
            debugCoords: null
        }
    },
    async mounted() {
        await this.loadSedes();
    },
    methods: {
        async loadSedes() {
            try {
                const response = await axios.get('/api/sedes');
                this.sedes = response.data;
                console.log('📋 Sedes cargadas:', this.sedes);
            } catch (error) {
                this.error = 'Error al cargar las sedes';
                console.error('Error cargando sedes:', error);
            }
        },

        async getLocation() {
            this.isLoading = true;
            this.error = null;

            if (!navigator.geolocation) {
                this.error = 'Tu navegador no soporta geolocalización';
                this.isLoading = false;
                return;
            }

            navigator.geolocation.getCurrentPosition(
                async (position) => {
                    try {
                        console.log('📍 Coordenadas obtenidas:', {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude
                        });

                        const response = await axios.post('/api/detect-sede', {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude
                        });

                        console.log('📡 Respuesta del servidor:', response.data);

                        if (response.data.sede) {
                            await this.assignSede(response.data.sede);
                        } else {
                            // 🔥 NUEVO: Mostrar modal si no está en ninguna sede
                            this.showNoSedeModal = true;
                            this.errorDistancia = response.data.distancia;
                            this.debugCoords = {
                                lat: position.coords.latitude,
                                lng: position.coords.longitude
                            };
                            this.error = `Estás a ${response.data.distancia} km de la sede más cercana. Debes estar en una sede UNIFRANZ para usar el sistema.`;
                        }
                    } catch (error) {
                        console.error('Error al detectar la sede:', error);
                        // 🔥 NUEVO: Manejar el caso específico de no estar en sede
                        if (error.response && error.response.status === 400 && error.response.data.sede === null) {
                            this.showNoSedeModal = true;
                            this.errorDistancia = error.response.data.distancia;
                            this.debugCoords = {
                                lat: position.coords.latitude,
                                lng: position.coords.longitude
                            };
                            this.error = error.response.data.mensaje || 'No estás dentro del área de ninguna sede de UNIFRANZ.';
                        } else {
                            this.error = 'Error al detectar la sede. Por favor selecciona manualmente.';
                        }
                    }
                    this.isLoading = false;
                },
                (error) => {
                    console.error('Error de geolocalización:', error);
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            this.error = 'Permiso de ubicación denegado. Por favor selecciona tu sede manualmente.';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            this.error = 'Información de ubicación no disponible. Por favor selecciona tu sede manualmente.';
                            break;
                        case error.TIMEOUT:
                            this.error = 'Tiempo de espera agotado. Por favor selecciona tu sede manualmente.';
                            break;
                        default:
                            this.error = 'Error de geolocalización. Por favor selecciona tu sede manualmente.';
                    }
                    this.isLoading = false;
                },
                {
                    timeout: 10000,
                    enableHighAccuracy: true
                }
            );
        },

        async selectSede(sede) {
            this.isLoading = true;
            try {
                console.log('📍 Seleccionando sede:', sede);
                
                localStorage.setItem('sede_seleccionada', sede.nombre);
                localStorage.setItem('sede_id', sede.id.toString());
                
                console.log('✅ Sede guardada:', {
                    nombre: sede.nombre,
                    id: sede.id
                });
                
                // Usar la misma lógica que assignSede
                await this.assignSede(sede);
                
            } catch (error) {
                console.error('Error asignando sede:', error);
                this.error = 'Error al asignar la sede';
            } finally {
                this.isLoading = false;
            }
        },

        async assignSede(sede) {
    console.log('📍 Asignando sede detectada:', sede);
    
    // Guardar en localStorage para modo invitado
    localStorage.setItem('sede_seleccionada', sede.nombre);
    localStorage.setItem('sede_id', sede.id.toString());
    
    console.log('✅ Sede detectada guardada:', {
        nombre: sede.nombre,
        id: sede.id
    });
    
    // 🔥 CORREGIDO: Verificar si el usuario está autenticado antes de hacer la llamada
    try {
        // Primero intentar obtener datos del usuario
        const userResponse = await fetch('/api/user', {
            credentials: 'include',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });
        
        if (userResponse.ok) {
            const userData = await userResponse.json();
            console.log('👤 Usuario autenticado:', userData);
            
            if (userData.role === 'gestor') {
                console.log('🔍 Gestor detectado, asignando sede...');
                
                // Asignar sede al gestor
                const assignResponse = await fetch('/api/user/sede', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    credentials: 'include',
                    body: JSON.stringify({ sede_id: sede.id })
                });
                
                if (assignResponse.ok) {
                    const result = await assignResponse.json();
                    console.log('✅ Sede asignada al gestor:', result);
                    localStorage.setItem('user_sede_id', sede.id.toString());
                } else {
                    console.error('❌ Error asignando sede al gestor:', assignResponse.status);
                    // Continuar de todos modos
                }
            }
        } else {
            console.log('👤 Usuario no autenticado o error en /api/user');
        }
    } catch (error) {
        console.error('❌ Error en verificación de usuario:', error);
        // Continuar de todos modos para usuarios no autenticados
    }
    
    // Redirigir según la lógica
    await this.redirectAfterSedeSelection();
},

async selectSede(sede) {
    this.isLoading = true;
    try {
        console.log('📍 Seleccionando sede:', sede);
        
        localStorage.setItem('sede_seleccionada', sede.nombre);
        localStorage.setItem('sede_id', sede.id.toString());
        
        console.log('✅ Sede guardada:', {
            nombre: sede.nombre,
            id: sede.id
        });
        
        // Usar la misma lógica que assignSede
        await this.assignSede(sede);
        
    } catch (error) {
        console.error('Error asignando sede:', error);
        this.error = 'Error al asignar la sede';
    } finally {
        this.isLoading = false;
    }
},

// 🔥 NUEVO MÉTODO: Redirección después de seleccionar sede
async redirectAfterSedeSelection() {
    try {
        const userResponse = await fetch('/api/user', {
            credentials: 'include',
            headers: {
                'Accept': 'application/json'
            }
        });
        
        if (userResponse.ok) {
            const userData = await userResponse.json();
            console.log('🔄 Redirigiendo según rol:', userData.role);
            
            if (userData.role === 'gestor') {
                this.$router.push('/gestor/dashboard');
            } else if (userData.role === 'admin') {
                this.$router.push('/admin/dashboard');
            } else {
                this.$router.push('/areas');
            }
        } else {
            // Usuario no autenticado - modo invitado
            this.$router.push('/areas');
        }
    } catch (error) {
        console.error('Error en redirección:', error);
        // Por defecto ir a áreas
        this.$router.push('/areas');
        }
    },

    closeModal() {
        this.showNoSedeModal = false;
        this.error = null;
    }
    }
}
</script>

<style scoped>
.location-requester {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-content {
    background: white;
    border-radius: 12px;
    padding: 30px;
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
}

.header i {
    font-size: 2rem;
    color: #3b82f6;
}

.header h2 {
    margin: 0;
    color: #1f2937;
}

.error-message {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #dc2626;
    padding: 12px 16px;
    border-radius: 8px;
    margin: 16px 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.sedes-list {
    margin: 24px 0;
}

.sedes-list h3 {
    color: #374151;
    margin-bottom: 16px;
    font-size: 1.1rem;
}

.sede-options {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.sede-btn {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    padding: 16px;
    text-align: left;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.sede-btn:hover {
    border-color: #3b82f6;
    background: #f8fafc;
}

.sede-name {
    font-weight: 600;
    color: #1f2937;
    font-size: 1rem;
}

.sede-coords {
    color: #6b7280;
    font-size: 0.8rem;
}

.divider {
    text-align: center;
    margin: 24px 0;
    position: relative;
}

.divider::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 1px;
    background: #e5e7eb;
}

.divider span {
    background: white;
    padding: 0 16px;
    color: #6b7280;
    font-size: 0.9rem;
}

.actions {
    display: flex;
    justify-content: center;
}

.btn-primary {
    background: #3b82f6;
    color: white;
    border: none;
    border-radius: 8px;
    padding: 12px 24px;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s;
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-primary:hover:not(:disabled) {
    background: #2563eb;
}

.btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.debug-info {
    margin-top: 20px;
    padding: 12px;
    background: #f3f4f6;
    border-radius: 6px;
    font-size: 0.8rem;
}

.debug-info summary {
    cursor: pointer;
    font-weight: 500;
}

.debug-info pre {
    margin-top: 8px;
    white-space: pre-wrap;
    word-wrap: break-word;
}

/* 🔥 NUEVO: Estilos para el modal de error cuando no está en sede */
.no-sede-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.95);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2000;
    animation: fadeIn 0.3s ease;
}

.no-sede-modal .modal-inner {
    background: white;
    border-radius: 16px;
    padding: 40px 30px;
    max-width: 500px;
    width: 90%;
    text-align: center;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
}

.no-sede-modal .error-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 20px;
    background: #fef2f2;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.no-sede-modal .error-icon i {
    font-size: 3rem;
    color: #dc2626;
}

.no-sede-modal h3 {
    margin: 0 0 12px;
    color: #1f2937;
    font-size: 1.5rem;
}

.no-sede-modal p {
    margin: 8px 0;
    color: #6b7280;
    line-height: 1.6;
}

.no-sede-modal .modal-detail {
    font-size: 0.95rem;
    margin-bottom: 24px;
    padding: 16px;
    background: #f9fafb;
    border-radius: 8px;
}

.no-sede-modal .sedes-info {
    margin: 24px 0;
    text-align: left;
}

.no-sede-modal .sedes-info p {
    margin-bottom: 8px;
    color: #374151;
    font-size: 0.95rem;
}

.no-sede-modal .sedes-info ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.no-sede-modal .sedes-info li {
    padding: 8px 12px;
    margin: 4px 0;
    background: #f3f4f6;
    border-radius: 6px;
    color: #4b5563;
    font-size: 0.9rem;
}

.no-sede-modal .sedes-info li i {
    margin-right: 8px;
    color: #3b82f6;
}

.btn-close-modal {
    margin-top: 24px;
    background: #dc2626;
    color: white;
    border: none;
    border-radius: 8px;
    padding: 12px 32px;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-close-modal:hover {
    background: #b91c1c;
    transform: translateY(-1px);
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}
</style>