<template>
    <div class="areas-container">
        <!-- Header -->
        <header class="areas-header">
            <div class="header-content">
                <div class="logo">
                    <i class="fas fa-university"></i>
                    <h1>UNIFRANZ - Sistema de Calificación</h1>
                </div>
                <div class="user-info">
                    <span class="sede-info">
                        <i class="fas fa-map-marker-alt"></i>
                        {{ sedeNombre }}
                    </span>
                    <button @click="logout" class="logout-btn" v-if="isAuthenticated">
                        <i class="fas fa-sign-out-alt"></i> Salir
                    </button>
                    <button @click="goToLogin" class="login-btn" v-else>
                        <i class="fas fa-user"></i> Acceso Admin
                    </button>
                </div>
            </div>
            
            <!-- Breadcrumb -->
            <nav class="breadcrumb">
                <span class="breadcrumb-item active">
                    <i class="fas fa-map-marker-alt"></i> {{ sedeNombre }}
                </span>
                <span class="breadcrumb-separator">></span>
                <span class="breadcrumb-item active">
                    <i class="fas fa-th-large"></i> Áreas
                </span>
            </nav>
        </header>

        <!-- Main Content -->
        <main class="areas-main">
            <div class="welcome-section">
                <h2>Selecciona el Área a Calificar</h2>
                <p>Elige el área de la universidad que deseas evaluar</p>
            </div>

            <div class="areas-grid">
                <div 
                    v-for="area in areas" 
                    :key="area.id"
                    class="area-card"
                    @click="seleccionarArea(area)"
                >
                    <div class="area-icon" :style="{ backgroundColor: area.color }">
                        <i :class="area.icono"></i>
                    </div>
                    <div class="area-info">
                        <h3>{{ area.nombre }}</h3>
                        <p>{{ area.descripcion }}</p>
                        <span class="area-code">{{ area.codigo }}</span>
                    </div>
                    <div class="area-arrow">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </div>
            </div>

            <div v-if="areas.length === 0 && !cargando" class="no-areas-message">
                <i class="fas fa-info-circle"></i>
                <h3>No hay áreas disponibles</h3>
                <p>No se encontraron áreas configuradas para la sede <strong>{{ sedeNombre }}</strong>.</p>
                <p>Por favor, contacta al administrador del sistema.</p>
            </div>

            <!-- Estadísticas rápidas -->
            <div class="quick-stats">
                <div class="stat-item">
                    <i class="fas fa-clock"></i>
                    <span>La calificación toma 2-3 minutos</span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-shield-alt"></i>
                    <span>Tus respuestas son anónimas</span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-chart-line"></i>
                    <span>Ayudas a mejorar nuestros servicios</span>
                </div>
            </div>

            <!-- Modal de Autenticación -->
            <div v-if="mostrarModalAuth" class="modal-overlay" @click="cerrarModal">
                <div class="modal-container auth-modal" @click.stop>
                    <div class="auth-content">
                        <div class="auth-header">
                            <i class="fas fa-lock auth-icon"></i>
                            <h2 class="auth-titulo">Acceso Requerido</h2>
                            <p class="auth-descripcion">
                                Para acceder al área <strong>{{ areaSeleccionadaTemp.nombre }}</strong>, 
                                ingresa la contraseña
                            </p>
                        </div>

                        <div class="auth-form">
                            <div class="input-group">
                                <label for="password">Contraseña:</label>
                                <input 
                                    type="password" 
                                    id="password"
                                    v-model="password"
                                    placeholder="Ingresa la contraseña"
                                    class="password-input"
                                    @keyup.enter="verificarPassword"
                                    :class="{ 'error': errorPassword }"
                                />
                                <div v-if="errorPassword" class="error-message">
                                    <i class="fas fa-exclamation-circle"></i>
                                    {{ errorPassword }}
                                </div>
                            </div>

                            <div class="auth-actions">
                                <button @click="cerrarModal" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancelar
                                </button>
                                <button @click="verificarPassword" class="btn btn-primary" :disabled="!password">
                                    <i class="fas fa-unlock-alt"></i> Acceder
                                </button>
                            </div>
                        </div>

                        <div class="auth-info">
                            <i class="fas fa-info-circle"></i>
                            <p>Si no conoces la contraseña, contacta al administrador.</p>
                        </div>
                    </div>
                </div>
            </div>

            

            <!-- Loading -->
            <div v-if="cargando" class="loading-overlay">
                <div class="spinner"></div>
                <p>Verificando acceso...</p>
            </div>
        </main>
    </div>
</template>

<script>
export default {
    name: 'Areas',
    data() {
    return {
        sedeNombre: 'Cargando...',
        isAuthenticated: false,
        cargando: false,
        areas: [], // Ahora se cargará desde la API
        
        // Autenticación
        mostrarModalAuth: false,
        areaSeleccionadaTemp: null,
        password: '',
        errorPassword: '',
    }
},
    async mounted() {
        await this.loadUserData();
        await this.loadSede();
        await this.loadAreas();
    },
    methods: {
        async loadUserData() {
            try {
                const response = await fetch('/api/user');
                if (response.ok) {
                    const userData = await response.json();
                    this.isAuthenticated = true;
                    if (userData.sede) {
                        this.sedeNombre = userData.sede.nombre;
                    }
                }
            } catch (error) {
                console.log('Usuario no autenticado - modo invitado');
                this.isAuthenticated = false;
            }
        },

        async loadSede() {
    try {
        const sedeGuardada = localStorage.getItem('sede_seleccionada');
        let sedeId = localStorage.getItem('sede_id');
        
        if (sedeGuardada) {
            this.sedeNombre = sedeGuardada;
            
            // 🔥 SI NO HAY sede_id, BUSCARLO POR EL NOMBRE
            if (!sedeId) {
                console.warn('⚠️ No hay sede_id en localStorage, buscando por nombre...');
                sedeId = await this.buscarSedeIdPorNombre(sedeGuardada);
                if (sedeId) {
                    localStorage.setItem('sede_id', sedeId.toString());
                    console.log('✅ Sede ID encontrado y guardado:', sedeId);
                }
            }
            
            console.log('📍 Sede actual:', this.sedeNombre, 'ID:', sedeId);
        } else {
            console.warn('⚠️ No hay sede seleccionada, redirigiendo a ubicación');
            this.$router.push('/ubicacion');
        }
    } catch (error) {
        console.error('Error cargando sede:', error);
        this.$router.push('/ubicacion');
    }
},

// 🔥 MÉTODO NUEVO: Buscar sede_id por nombre
async buscarSedeIdPorNombre(nombreSede) {
    try {
        const response = await fetch(`/api/sedes/buscar?nombre=${encodeURIComponent(nombreSede)}`);
        if (response.ok) {
            const sedeData = await response.json();
            return sedeData.id;
        }
    } catch (error) {
        console.error('Error buscando sede por nombre:', error);
    }
    return null;
},

        // En tu componente Areas.vue o donde seleccionas el área
    // En Areas.vue
seleccionarArea(area) {
    console.log('📍 Área seleccionada:', area);
    
    // Verificar que el área tenga los datos necesarios
    if (!area) {
        console.error('❌ Área no definida');
        return;
    }
    
    // 🔥 CORRECIÓN: Mostrar modal de autenticación en lugar de redirigir directamente
    this.areaSeleccionadaTemp = area;
    this.password = '';
    this.errorPassword = '';
    this.mostrarModalAuth = true;
    
    console.log('🔐 Mostrando modal de autenticación para:', area.nombre);
},

        async verificarPassword() {
    if (!this.password.trim()) {
        this.errorPassword = 'Por favor ingresa la contraseña';
        return;
    }

    this.cargando = true;
    this.errorPassword = '';

    try {
        console.log('Verificando contraseña para área:', this.areaSeleccionadaTemp);
        console.log('Contraseña ingresada:', this.password);
        console.log('Contraseña del área:', this.areaSeleccionadaTemp.password);

        // Verificar contra la contraseña del área
        if (this.password === this.areaSeleccionadaTemp.password) {
            console.log('✅ Contraseña correcta');
            await this.procesarAreaSeleccionada();
        } else {
            console.log('❌ Contraseña incorrecta');
            this.errorPassword = 'Contraseña incorrecta. Intenta nuevamente.';
        }

    } catch (error) {
        console.error('Error verificando contraseña:', error);
        this.errorPassword = 'Error al verificar la contraseña';
    } finally {
        this.cargando = false;
    }
},

       async loadAreas() {
    try {
        console.log('Cargando áreas desde API...');
        
        // 🔥 Obtener el ID de sede actual para filtrar
        let sedeId = localStorage.getItem('sede_id');
        
        // Si no hay sede_id, intentar obtenerlo del área seleccionada anteriormente
        if (!sedeId) {
            const areaGuardada = localStorage.getItem('area_seleccionada');
            if (areaGuardada) {
                const areaData = JSON.parse(areaGuardada);
                sedeId = areaData.sede_id;
                if (sedeId) {
                    localStorage.setItem('sede_id', sedeId.toString());
                    console.log('✅ Sede ID obtenido del área guardada:', sedeId);
                }
            }
        }
        
        // 🔥 DECIDIR QUÉ RUTA USAR SEGÚN SI ESTÁ AUTENTICADO O NO
        let url = '';
        
        // Primero verificar si está autenticado
        try {
            const userResponse = await fetch('/api/user');
            if (userResponse.ok) {
                const userData = await userResponse.json();
                if (userData.authenticated) {
                    // 🔥 USUARIO AUTENTICADO: usar ruta protegida
                    url = '/api/areas';
                    console.log('🔐 Usuario autenticado, usando ruta protegida');
                } else {
                    // 🔥 MODO INVITADO: usar ruta pública
                    url = '/api/areas/public';
                    console.log('👤 Modo invitado, usando ruta pública');
                }
            } else {
                // 🔥 MODO INVITADO: usar ruta pública
                url = '/api/areas/public';
                console.log('👤 Modo invitado (error en /api/user), usando ruta pública');
            }
        } catch (error) {
            // 🔥 MODO INVITADO: usar ruta pública
            url = '/api/areas/public';
            console.log('👤 Modo invitado (excepción), usando ruta pública');
        }
        
        // Agregar parámetro de sede si existe
        if (sedeId) {
            url += `?sede_id=${sedeId}`;
            console.log('🔍 Filtrando áreas por sede_id:', sedeId);
        } else {
            console.warn('⚠️ No hay sede_id disponible, cargando todas las áreas');
        }
        
        console.log('🌐 URL final para cargar áreas:', url);
        
        const response = await fetch(url);
        
        if (response.ok) {
            const areasData = await response.json();
            console.log('📋 Áreas cargadas desde API:', areasData);
            
            // Mapear los datos de la API al formato que espera el componente
            this.areas = areasData.map(area => ({
                id: area.id,
                nombre: area.nombre,
                codigo: area.codigo,
                descripcion: area.descripcion || this.getDescripcionPorDefecto(area.codigo),
                icono: area.icono || this.getIconoPorDefecto(area.codigo),
                color: area.color || this.getColorPorDefecto(area.codigo),
                password: area.password,
                sede_id: area.sede_id,
                sede: area.sede
            }));
            
            console.log('✅ Áreas procesadas:', this.areas.length, 'áreas para sede:', this.sedeNombre);
            
            // Mostrar mensaje si no hay áreas para esta sede
            if (this.areas.length === 0) {
                console.warn('⚠️ No se encontraron áreas para la sede:', this.sedeNombre);
            }
            
        } else {
            console.error('Error cargando áreas:', response.status);
            throw new Error('Error al cargar las áreas desde la API');
        }
    } catch (error) {
        console.error('Error cargando áreas:', error);
        // Fallback a áreas estáticas si la API falla
        this.areas = this.getAreasEstaticas();
    }
},

// Agregar estos métodos auxiliares
getDescripcionPorDefecto(codigo) {
    const descripciones = {
        'ARCA': 'Área Académica - Evaluación de docentes, materias y procesos académicos',
        'CAJAS': 'Área de Pagos - Evaluación de procesos de pago y atención en cajas',
        'SES': 'Servicios Estudiantiles - Biblioteca, bienestar estudiantil y soporte',
        'TI': 'Tecnologías de Información - Plataformas, wifi y soporte técnico',
        'GY': 'Área de evaluación universitaria', // Para tu nueva área
        'PRUB': 'Área de prueba - Evaluación de servicios de prueba'
    };
    return descripciones[codigo] || 'Área de evaluación universitaria';
},

getIconoPorDefecto(codigo) {
    const iconos = {
        'ARCA': 'fas fa-graduation-cap',
        'CAJAS': 'fas fa-cash-register',
        'SES': 'fas fa-hands-helping', 
        'TI': 'fas fa-laptop-code',
        'GY': 'fas fa-building',
        'PRUB': 'fas fa-building'
    };
    return iconos[codigo] || 'fas fa-building';
},

getColorPorDefecto(codigo) {
    const colores = {
        'ARCA': '#4f46e5',
        'CAJAS': '#059669',
        'SES': '#dc2626',
        'TI': '#7c3aed',
        'GY': '#f59e0b',
        'PRUB': '#8b5cf6'
    };
    return colores[codigo] || '#6b7280';
},

    getAreasEstaticas() {
        // Obtener sede_id actual para las áreas estáticas
        const sedeId = localStorage.getItem('sede_id');
        const sedeNombre = localStorage.getItem('sede_seleccionada') || 'Sede Actual';
        
        console.log('🔄 Usando áreas estáticas para sede:', sedeNombre, 'ID:', sedeId);
        
        // Definir áreas por sede
        const areasPorSede = {
            // Sede La Paz (ID: 1)
            1: [
                {
                    id: 1,
                    nombre: 'ARCA',
                    codigo: 'ARCA',
                    descripcion: 'Área Académica - Evaluación de docentes, materias y procesos académicos',
                    icono: 'fas fa-graduation-cap',
                    color: '#4f46e5',
                    password: 'arca2025',
                    sede_id: 1
                },
                {
                    id: 2,
                    nombre: 'CAJAS',
                    codigo: 'CAJAS',
                    descripcion: 'Área de Pagos - Evaluación de procesos de pago y atención en cajas',
                    icono: 'fas fa-cash-register',
                    color: '#059669',
                    password: 'cajas2025',
                    sede_id: 1
                },
                {
                    id: 3,
                    nombre: 'SES',
                    codigo: 'SES',
                    descripcion: 'Servicios Estudiantiles - Biblioteca, bienestar estudiantil y soporte',
                    icono: 'fas fa-hands-helping',
                    color: '#dc2626',
                    password: 'ses2025',
                    sede_id: 1
                },
                {
                    id: 4,
                    nombre: 'TI',
                    codigo: 'TI',
                    descripcion: 'Tecnologías de Información - Plataformas, wifi y soporte técnico',
                    icono: 'fas fa-laptop-code',
                    color: '#7c3aed',
                    password: 'ti2025',
                    sede_id: 1
                }
            ],
            // Sede El Alto (ID: 2)
            2: [
                {
                    id: 5,
                    nombre: 'ARCA El Alto',
                    codigo: 'ARCA-EA',
                    descripcion: 'Área Académica - Evaluación de docentes, materias y procesos académicos',
                    icono: 'fas fa-graduation-cap',
                    color: '#4f46e5',
                    password: 'arca2025',
                    sede_id: 2
                },
                {
                    id: 6,
                    nombre: 'CAJAS El Alto',
                    codigo: 'CAJAS-EA',
                    descripcion: 'Área de Pagos - Evaluación de procesos de pago y atención en cajas',
                    icono: 'fas fa-cash-register',
                    color: '#059669',
                    password: 'cajas2025',
                    sede_id: 2
                },
                {
                    id: 8,
                    nombre: 'VAMO',
                    codigo: 'VA',
                    descripcion: 'Área de evaluación universitaria - Sede El Alto',
                    icono: 'fas fa-building',
                    color: '#f59e0b',
                    password: '1234',
                    sede_id: 2
                }
            ],
            // Sede Santa Cruz (ID: 3)
            3: [
                {
                    id: 9,
                    nombre: 'AREA SCS',
                    codigo: 'SCS',
                    descripcion: 'Área de evaluación - Sede Santa Cruz',
                    icono: 'fas fa-building',
                    color: '#8b5cf6',
                    password: '1234',
                    sede_id: 3
                }
            ],
            // Sede Cochabamba (ID: 4)
            4: [
                {
                    id: 7,
                    nombre: 'ARCA Cochabamba',
                    codigo: 'ARCA-CBBA',
                    descripcion: 'Área Académica - Evaluación de docentes, materias y procesos académicos',
                    icono: 'fas fa-graduation-cap',
                    color: '#4f46e5',
                    password: 'arca2025',
                    sede_id: 4
                }
            ]
        };
        
        // Si tenemos sede_id y existe en el mapeo, usar esas áreas
        if (sedeId && areasPorSede[sedeId]) {
            return areasPorSede[sedeId];
        }
        
        // Si no hay sede_id o no existe en el mapeo, usar todas las áreas
        console.warn('⚠️ No se encontraron áreas específicas para sede_id:', sedeId, '- Mostrando todas las áreas');
        return Object.values(areasPorSede).flat();
    },

        async procesarAreaSeleccionada() {
    try {
        const area = this.areaSeleccionadaTemp;
        
        // 🔥 CORRECIÓN: Guardar datos completos en localStorage
        const areaData = {
            id: area.id,
            nombre: area.nombre,
            codigo: area.codigo,
            sede_id: area.sede_id,
            // No guardar la contraseña por seguridad
        };
        
        localStorage.setItem('area_seleccionada', JSON.stringify(areaData));
        
        // 🔥🔥🔥 AGREGAR ESTA LÍNEA CRÍTICA - Activar modo kiosko
        localStorage.setItem('primera_vez_area_seleccionada', 'true');
        
        // Manejar la sede de forma segura
        let sedeNombre = 'Sede no disponible';
        let sedeId = area.sede_id;
        
        if (area.sede && area.sede.nombre) {
            sedeNombre = area.sede.nombre;
            sedeId = area.sede.id;
        } else if (area.sede_id) {
            sedeNombre = `Sede ${area.sede_id}`;
        }
        
        console.log('🏢 Sede detectada:', { sedeNombre, sedeId });
        
        localStorage.setItem('sede_seleccionada', sedeNombre);
        if (sedeId) {
            localStorage.setItem('sede_id', sedeId.toString());
        }
        
        console.log('✅ Modo kiosko activado. Redirigiendo a calificar...');
        
        // 🔥 CORRECIÓN: Redirigir a calificar sin el ID en la URL
        this.$router.push('/calificar');
        
    } catch (error) {
        console.error('Error procesando área:', error);
        alert('Error al procesar la selección: ' + error.message);
    } finally {
        // Cerrar el modal después de procesar
        this.cerrarModal();
    }
},

        cerrarModal() {
            this.mostrarModalAuth = false;
            this.areaSeleccionadaTemp = null;
            this.password = '';
            this.errorPassword = '';
        },

        logout() {
            window.location.href = '/logout';
        },

        goToLogin() {
            this.$router.push('/login');
        }
    }
}
</script>

<style scoped>
.areas-container {
    min-height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.areas-header {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    padding: 1rem 0;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.header-content {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 2rem;
}

.logo {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.logo i {
    font-size: 2rem;
    color: #4f46e5;
}

.logo h1 {
    font-size: 1.25rem;
    color: #1f2937;
    margin: 0;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.sede-info {
    background: #4f46e5;
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.875rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.logout-btn, .login-btn {
    background: #ef4444;
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
}

.login-btn {
    background: #059669;
}

.logout-btn:hover {
    background: #dc2626;
}

.login-btn:hover {
    background: #047857;
}

.breadcrumb {
    max-width: 1200px;
    margin: 0 auto;
    padding: 1rem 2rem 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
}

.breadcrumb-item {
    color: #6b7280;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.breadcrumb-item.active {
    color: #4f46e5;
    font-weight: 500;
}

.breadcrumb-separator {
    color: #9ca3af;
}

.areas-main {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
    position: relative;
}

.welcome-section {
    text-align: center;
    color: white;
    margin-bottom: 3rem;
}

.welcome-section h2 {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    font-weight: 700;
}

.welcome-section p {
    font-size: 1.125rem;
    opacity: 0.9;
}

.areas-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 1.5rem;
    margin-bottom: 3rem;
}

.area-card {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    display: flex;
    align-items: center;
    gap: 1.5rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.area-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
}

.area-icon {
    width: 70px;
    height: 70px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.75rem;
}

.area-info {
    flex: 1;
}

.area-info h3 {
    font-size: 1.5rem;
    color: #1f2937;
    margin-bottom: 0.5rem;
}

.area-info p {
    color: #6b7280;
    margin-bottom: 0.5rem;
    line-height: 1.5;
}

.area-code {
    background: #f3f4f6;
    color: #4f46e5;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.area-arrow {
    color: #9ca3af;
    font-size: 1.25rem;
}

.quick-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    padding: 2rem;
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    color: white;
}

.stat-item i {
    font-size: 1.5rem;
    opacity: 0.9;
}

/* Modal de Autenticación */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.modal-container {
    background: white;
    border-radius: 20px;
    box-shadow: 0 25px 80px rgba(0, 0, 0, 0.4);
    max-width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    animation: slideUp 0.3s ease;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.auth-modal {
    width: 450px;
    max-width: 95vw;
}

.auth-content {
    padding: 3rem 2.5rem;
}

.auth-header {
    text-align: center;
    margin-bottom: 2.5rem;
}

.auth-icon {
    font-size: 3rem;
    color: #4f46e5;
    margin-bottom: 1rem;
}

.auth-titulo {
    font-size: 1.8rem;
    color: #1f2937;
    margin-bottom: 1rem;
    font-weight: 700;
}

.auth-descripcion {
    color: #6b7280;
    font-size: 1rem;
    line-height: 1.5;
    margin: 0;
}

.auth-descripcion strong {
    color: #4f46e5;
}

.auth-form {
    margin-bottom: 2rem;
}

.input-group {
    margin-bottom: 1.5rem;
}

.input-group label {
    display: block;
    color: #374151;
    font-weight: 600;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.password-input {
    width: 100%;
    padding: 1rem 1.25rem;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: #fafafa;
    font-family: inherit;
}

.password-input:focus {
    outline: none;
    border-color: #4f46e5;
    background: white;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.password-input.error {
    border-color: #dc2626;
    background: #fef2f2;
}

.error-message {
    color: #dc2626;
    font-size: 0.85rem;
    margin-top: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.auth-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
}

.btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-secondary {
    background: #6b7280;
    color: white;
}

.btn-secondary:hover:not(:disabled) {
    background: #4b5563;
}

.btn-primary {
    background: #4f46e5;
    color: white;
}

.btn-primary:hover:not(:disabled) {
    background: #4338ca;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
}

.auth-info {
    background: #f3f4f6;
    padding: 1rem 1.25rem;
    border-radius: 10px;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: #6b7280;
    font-size: 0.9rem;
}

.auth-info i {
    color: #4f46e5;
    flex-shrink: 0;
}

/* Loading */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    z-index: 2000;
    color: white;
}

.spinner {
    border: 3px solid rgba(255, 255, 255, 0.3);
    border-top: 3px solid white;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 1s linear infinite;
    margin-bottom: 1rem;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 768px) {
    .header-content {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .areas-grid {
        grid-template-columns: 1fr;
    }
    
    .area-card {
        flex-direction: column;
        text-align: center;
        padding: 1.5rem;
    }
    
    .welcome-section h2 {
        font-size: 2rem;
    }
    
    .auth-content {
        padding: 2rem 1.5rem;
    }
    
    .auth-actions {
        flex-direction: column;
    }
    
    .auth-actions .btn {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .titulo {
        font-size: 2rem;
    }
    
    .sede-info {
        font-size: 1rem;
        padding: 0.5rem 1rem;
    }
    
    .area-icon {
        width: 60px;
        height: 60px;
        font-size: 1.5rem;
    }
    
    .area-nombre {
        font-size: 1.3rem;
    }
}
.sede-indicator {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 12px 16px;
    margin-top: 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 0.9rem;
}

.sede-indicator i {
    color: #3b82f6;
}

.areas-count {
    margin-left: auto;
    background: #3b82f6;
    color: white;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 500;
}
</style>