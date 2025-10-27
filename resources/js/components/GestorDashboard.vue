<template>
    <div class="gestor-dashboard">
        <!-- Header -->
        <header class="gestor-header">
            <div class="header-content">
                <div class="logo">
                    <i class="fas fa-university"></i>
                    <h1>UNIFRANZ - Panel Gestor</h1>
                    <span class="sede-badge">{{ sedeNombre }}</span>
                </div>
                <div class="user-info">
                    <div class="user-dropdown">
                        <button class="user-toggle" @click="toggleUserMenu">
                            <img :src="user.avatar || '/images/default-avatar.png'" :alt="user.name" class="user-avatar">
                            <span class="user-name">{{ user.name }}</span>
                            <i class="fas fa-chevron-down" :class="{ 'rotate': showUserMenu }"></i>
                        </button>
                        
                        <div v-if="showUserMenu" class="dropdown-menu">
                            <div class="dropdown-header">
                                <strong>{{ user.name }}</strong>
                                <br>
                                <small>{{ user.email }}</small>
                                <div class="role-badge gestor">Gestor</div>
                                <div class="sede-info-dropdown">
                                    <i class="fas fa-map-marker-alt"></i>
                                    {{ sedeNombre }}
                                </div>
                            </div>
                            <div class="dropdown-divider"></div>
                            <button @click="logout" class="dropdown-item logout-item">
                                <i class="fas fa-sign-out-alt"></i>
                                Cerrar Sesión
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="gestor-main">
            <!-- Sidebar -->
            <nav class="gestor-sidebar">
                <div class="sidebar-menu">
                    <button 
                        v-for="tab in tabs" 
                        :key="tab.id"
                        @click="activeTab = tab.id"
                        :class="['menu-item', { active: activeTab === tab.id }]"
                    >
                        <i :class="tab.icon"></i>
                        <span>{{ tab.name }}</span>
                    </button>
                </div>
            </nav>

            <!-- Content Area -->
            <div class="gestor-content">
                <!-- Dashboard Overview -->
                <div v-if="activeTab === 'dashboard'" class="tab-content">
                    <div class="welcome-section">
                        <h2>Dashboard de Sede - {{ sedeNombre }}</h2>
                        <p>Estadísticas y reportes de tu sede universitaria</p>
                        
                        <!-- 🔥 NUEVO: Alerta si no tiene sede asignada -->
                        <div v-if="!user.sede_id" class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>No tienes una sede asignada</strong>
                            <p>Contacta al administrador para que te asigne una sede.</p>
                        </div>
                    </div>

                    <div v-if="user.sede_id" class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon total-califications">
                                <i class="fas fa-chart-bar"></i>
                            </div>
                            <div class="stat-info">
                                <h3>{{ stats.totalCalificaciones }}</h3>
                                <p>Total Calificaciones</p>
                                <small>En esta sede</small>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon avg-rating">
                                <i class="fas fa-star"></i>
                            </div>
                            <div class="stat-info">
                                <h3>{{ stats.promedioCalificacion }}/5</h3>
                                <p>Calificación Promedio</p>
                                <small>En esta sede</small>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon total-areas">
                                <i class="fas fa-th-large"></i>
                            </div>
                            <div class="stat-info">
                                <h3>{{ stats.totalAreas }}</h3>
                                <p>Áreas Activas</p>
                                <small>En el sistema</small>
                            </div>
                        </div>
                    </div>

                    <!-- 🔥 MODIFICADO: Solo mostrar si tiene sede -->
                    <div v-if="user.sede_id" class="chart-section">
                        <h3>Calificaciones por Área</h3>
                        <div class="chart-container">
                            <div v-for="item in calificacionesPorArea" :key="item.area" class="chart-item">
                                <div class="chart-bar" :style="{ height: (item.total / maxCalificaciones) * 100 + '%' }">
                                    <span class="chart-value">{{ item.total }}</span>
                                </div>
                                <div class="chart-label">{{ item.area }}</div>
                                <div class="chart-average">{{ item.promedio }}/5</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reportes -->
                <div v-if="activeTab === 'reportes'" class="tab-content">
                    <h2>Reportes de {{ sedeNombre }}</h2>
                    
                    <!-- 🔥 NUEVO: Alerta si no tiene sede -->
                    <div v-if="!user.sede_id" class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>No puedes generar reportes</strong>
                        <p>Necesitas tener una sede asignada para acceder a los reportes.</p>
                    </div>

                    <div v-else class="reports-grid">
                        <div class="report-card">
                            <i class="fas fa-download"></i>
                            <h3>Reporte General</h3>
                            <p>Descarga el reporte completo de calificaciones</p>
                            <button class="btn-primary">Generar PDF</button>
                        </div>
                        <div class="report-card">
                            <i class="fas fa-calendar"></i>
                            <h3>Reporte por Fecha</h3>
                            <p>Calificaciones filtradas por rango de fechas</p>
                            <button class="btn-primary">Configurar</button>
                        </div>
                    </div>
                </div>

                <!-- Areas Management para Gestor -->
                <div v-if="activeTab === 'areas'" class="tab-content">
                    <div v-if="!user.sede_id" class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>No puedes gestionar áreas</strong>
                        <p>Necesitas tener una sede asignada para gestionar áreas.</p>
                    </div>
                    <GestorAreasManagement v-else />
                </div>

                <!-- Preguntas Management para Gestor -->
                <div v-if="activeTab === 'preguntas'" class="tab-content">
                    <div v-if="!user.sede_id" class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>No puedes gestionar preguntas</strong>
                        <p>Necesitas tener una sede asignada para gestionar preguntas.</p>
                    </div>
                    <GestorPreguntasManagement v-else />
                </div>  
            </div>
        </main>

        <!-- User Menu Overlay -->
        <div v-if="showUserMenu" class="overlay" @click="showUserMenu = false"></div>
    </div>
</template>

<script>
import GestorAreasManagement from './GestorAreasManagement.vue';
import GestorPreguntasManagement from './GestorPreguntasManagement.vue';

export default {
    name: 'GestorDashboard',
    components: {
        GestorAreasManagement,
        GestorPreguntasManagement
    },
    data() {
        return {
            user: {
                name: 'Cargando...',
                email: '',
                avatar: '',
                sede: null,
                sede_id: null,
                role: ''
            },
            sedeNombre: 'Cargando...',
            activeTab: 'dashboard',
            tabs: [
                { id: 'dashboard', name: 'Dashboard', icon: 'fas fa-tachometer-alt' },
                { id: 'areas', name: 'Gestión de Áreas', icon: 'fas fa-th-large' },
                { id: 'preguntas', name: 'Gestión de Preguntas', icon: 'fas fa-question-circle' },
                { id: 'reportes', name: 'Reportes', icon: 'fas fa-chart-bar' }
            ],
            stats: {
                totalCalificaciones: 0,
                promedioCalificacion: 0,
                totalAreas: 0
            },
            calificacionesPorArea: [],
            showUserMenu: false,
            loading: true
        }
    },
    computed: {
        maxCalificaciones() {
            return Math.max(...this.calificacionesPorArea.map(item => item.total), 1);
        }
    },
    async mounted() {
        await this.loadUserData();
        if (this.user.sede_id) {
            await this.loadStats();
            await this.loadCalificacionesPorArea();
        }
    },
    methods: {
        async loadUserData() {
            try {
                const response = await fetch('/api/user', {
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                
                if (response.ok) {
                    this.user = await response.json();
                    console.log('👤 Datos del gestor:', this.user);
                    
                    // 🔥 MODIFICADO: Usar solo la sede asignada por el administrador
                    if (this.user.sede) {
                        this.sedeNombre = this.user.sede.nombre;
                        console.log('📍 Sede asignada:', this.sedeNombre);
                    } else {
                        this.sedeNombre = 'Sede no asignada';
                        console.warn('⚠️ Gestor sin sede asignada');
                    }
                    
                } else {
                    console.error('❌ Error en /api/user:', response.status);
                    this.$router.push('/login');
                }
            } catch (error) {
                console.error('❌ Error cargando datos usuario:', error);
                this.$router.push('/login');
            }
        },

        async loadStats() {
            try {
                const response = await fetch('/api/gestor/stats', {
                    credentials: 'include'
                });
                if (response.ok) {
                    this.stats = await response.json();
                } else {
                    // Si hay error, usar datos de ejemplo
                    this.stats = {
                        totalCalificaciones: 0,
                        promedioCalificacion: 0,
                        totalAreas: 0
                    };
                }
            } catch (error) {
                console.error('Error cargando stats:', error);
                this.stats = {
                    totalCalificaciones: 0,
                    promedioCalificacion: 0,
                    totalAreas: 0
                };
            } finally {
                this.loading = false;
            }
        },

        async loadCalificacionesPorArea() {
            try {
                const response = await fetch('/api/gestor/calificaciones-por-area', {
                    credentials: 'include'
                });
                if (response.ok) {
                    this.calificacionesPorArea = await response.json();
                } else {
                    this.calificacionesPorArea = [];
                }
            } catch (error) {
                console.error('Error cargando calificaciones por área:', error);
                this.calificacionesPorArea = [];
            }
        },

        toggleUserMenu(event) {
            event.stopPropagation();
            this.showUserMenu = !this.showUserMenu;
        },

        async logout() {
            try {
                this.showUserMenu = false;
                
                const response = await fetch('/logout', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });

                if (response.ok) {
                    window.location.href = '/login';
                } else {
                    throw new Error('Error al cerrar sesión');
                }
            } catch (error) {
                window.location.href = '/logout';
            }
        }
    }
}
</script>

<style scoped>
.gestor-dashboard {
    min-height: 100vh;
    background: #f8fafc;
}

/* Header Styles */
.gestor-header {
    background: #ffffff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    position: sticky;
    top: 0;
    z-index: 100;
}

.header-content {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 2rem;
}

.logo {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.logo i {
    font-size: 2rem;
    color: #059669;
}

.logo h1 {
    font-size: 1.5rem;
    color: #1f2937;
    margin: 0;
}

.sede-badge {
    background: #059669;
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

/* User Info */
.user-dropdown {
    position: relative;
}

.user-toggle {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    background: none;
    border: none;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 8px;
    transition: background 0.3s ease;
}

.user-toggle:hover {
    background: #f3f4f6;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
}

.user-name {
    font-weight: 600;
    color: #374151;
}

.rotate {
    transform: rotate(180deg);
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    border-radius: 8px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    min-width: 200px;
    z-index: 1000;
    margin-top: 0.5rem;
}

.dropdown-header {
    padding: 1rem;
    border-bottom: 1px solid #e5e7eb;
}

.role-badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 600;
    margin-top: 0.5rem;
}

.role-badge.gestor {
    background: #dbeafe;
    color: #1e40af;
}

.dropdown-divider {
    height: 1px;
    background: #e5e7eb;
}

.dropdown-item {
    width: 100%;
    padding: 0.75rem 1rem;
    background: none;
    border: none;
    text-align: left;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: background 0.3s ease;
}

.dropdown-item:hover {
    background: #f3f4f6;
}

.logout-item {
    color: #dc2626;
}

/* Main Layout */
.gestor-main {
    display: flex;
    max-width: 1200px;
    margin: 0 auto;
    min-height: calc(100vh - 80px);
}

.gestor-sidebar {
    width: 250px;
    background: white;
    border-right: 1px solid #e5e7eb;
}

.sidebar-menu {
    padding: 1rem 0;
}

.menu-item {
    width: 100%;
    padding: 1rem 1.5rem;
    background: none;
    border: none;
    text-align: left;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    transition: all 0.3s ease;
    color: #6b7280;
}

.menu-item:hover {
    background: #f3f4f6;
    color: #374151;
}

.menu-item.active {
    background: #dbeafe;
    color: #1e40af;
    border-right: 3px solid #1e40af;
}

.menu-item i {
    width: 20px;
    text-align: center;
}

.gestor-content {
    flex: 1;
    padding: 2rem;
}

/* Content Styles */
.welcome-section {
    margin-bottom: 2rem;
}

.welcome-section h2 {
    font-size: 2rem;
    color: #1f2937;
    margin-bottom: 0.5rem;
}

.welcome-section p {
    color: #6b7280;
    font-size: 1.1rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 3rem;
}

.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 1rem;
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
}

.stat-icon.total-califications {
    background: #4f46e5;
}

.stat-icon.avg-rating {
    background: #059669;
}

.stat-icon.total-areas {
    background: #7c3aed;
}

.stat-info h3 {
    font-size: 2rem;
    color: #1f2937;
    margin: 0 0 0.25rem 0;
}

.stat-info p {
    color: #374151;
    margin: 0 0 0.25rem 0;
    font-weight: 600;
}

.stat-info small {
    color: #6b7280;
    font-size: 0.8rem;
}

/* Chart Styles */
.chart-section {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.chart-section h3 {
    margin: 0 0 2rem 0;
    color: #1f2937;
}

.chart-container {
    display: flex;
    align-items: end;
    gap: 2rem;
    height: 200px;
    padding: 1rem 0;
}

.chart-item {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
}

.chart-bar {
    background: #4f46e5;
    width: 40px;
    border-radius: 4px 4px 0 0;
    position: relative;
    transition: height 0.3s ease;
    min-height: 20px;
}

.chart-value {
    position: absolute;
    top: -25px;
    left: 50%;
    transform: translateX(-50%);
    background: #1f2937;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 600;
}

.chart-label {
    font-size: 0.8rem;
    color: #6b7280;
    text-align: center;
    font-weight: 600;
}

.chart-average {
    font-size: 0.7rem;
    color: #059669;
    font-weight: 600;
}

/* Reports Grid */
.reports-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
}

.report-card {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    text-align: center;
}

.report-card i {
    font-size: 3rem;
    color: #4f46e5;
    margin-bottom: 1rem;
}

.report-card h3 {
    color: #1f2937;
    margin-bottom: 1rem;
}

.report-card p {
    color: #6b7280;
    margin-bottom: 1.5rem;
}

.btn-primary {
    background: #4f46e5;
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: background 0.3s ease;
}

.btn-primary:hover {
    background: #4338ca;
}

/* Overlay */
.overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: transparent;
    z-index: 99;
}

/* Responsive */
@media (max-width: 768px) {
    .gestor-main {
        flex-direction: column;
    }
    
    .gestor-sidebar {
        width: 100%;
        border-right: none;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .sidebar-menu {
        display: flex;
        overflow-x: auto;
    }
    
    .menu-item {
        white-space: nowrap;
        border-right: none;
        border-bottom: 3px solid transparent;
    }
    
    .menu-item.active {
        border-right: none;
        border-bottom: 3px solid #1e40af;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .chart-container {
        flex-direction: column;
        height: auto;
        gap: 1rem;
    }
    
    .chart-item {
        flex-direction: row;
        align-items: center;
        gap: 1rem;
    }
    
    .chart-bar {
        width: 20px;
        height: 100px !important;
        border-radius: 4px;
    }
    
    .chart-value {
        top: 50%;
        left: -30px;
        transform: translateY(-50%);
    }
}

/* 🔥 NUEVOS ESTILOS PARA ALERTAS */
.alert {
    padding: 1rem 1.5rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: flex-start;
    gap: 1rem;
}

.alert-warning {
    background: #fef3c7;
    border: 1px solid #f59e0b;
    color: #92400e;
}

.alert i {
    font-size: 1.25rem;
    margin-top: 0.125rem;
}

.alert strong {
    display: block;
    margin-bottom: 0.25rem;
}

.alert p {
    margin: 0;
    font-size: 0.9rem;
    opacity: 0.9;
}

/* Estilo para la información de sede en el dropdown */
.sede-info-dropdown {
    margin-top: 0.5rem;
    padding: 0.5rem;
    background: #f0f9ff;
    border-radius: 4px;
    font-size: 0.8rem;
    color: #0369a1;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Responsive */
@media (max-width: 768px) {
    .alert {
        flex-direction: column;
        text-align: center;
    }
}
</style>