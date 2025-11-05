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

                    <!-- Filtros -->
                    <div v-if="user.sede_id" class="filters-section">
                        <div class="filters-header">
                            <h3>
                                <i class="fas fa-filter"></i>
                                Filtros
                            </h3>
                            <button @click="resetFilters" class="btn-reset-filters" v-if="hasActiveFilters">
                                <i class="fas fa-times"></i>
                                Limpiar Filtros
                            </button>
                        </div>
                        <div class="filters-grid">
                            <div class="filter-group">
                                <label>
                                    <i class="fas fa-map-marker-alt"></i>
                                    Sede
                                </label>
                                <select v-model="filters.sede_id" @change="applyFilters" class="filter-select">
                                    <option :value="user.sede_id">{{ sedeNombre }}</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label>
                                    <i class="fas fa-th-large"></i>
                                    Área
                                </label>
                                <select v-model="filters.area_id" @change="applyFilters" class="filter-select">
                                    <option value="">Todas las Áreas</option>
                                    <option v-for="area in areas" :key="area.id" :value="area.id">
                                        {{ area.nombre }}
                                    </option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label>
                                    <i class="fas fa-calendar-alt"></i>
                                    Rango de Fechas
                                </label>
                                <div class="date-range">
                                    <input 
                                        type="date" 
                                        v-model="filters.fecha_inicio" 
                                        @change="applyFilters"
                                        class="filter-date"
                                    >
                                    <span class="date-separator">hasta</span>
                                    <input 
                                        type="date" 
                                        v-model="filters.fecha_fin" 
                                        @change="applyFilters"
                                        class="filter-date"
                                    >
                                </div>
                            </div>
                            <div class="filter-group">
                                <label>
                                    <i class="fas fa-tag"></i>
                                    Tipo de Calificación
                                </label>
                                <select v-model="filters.tipo_calificacion" @change="applyFilters" class="filter-select">
                                    <option value="">Todos los Tipos</option>
                                    <option value="csat">CSAT</option>
                                    <option value="nps">NPS</option>
                                    <option value="fcr">FCR</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Loading State -->
                    <div v-if="loading && user.sede_id" class="loading-container">
                        <div class="spinner"></div>
                        <p>Cargando estadísticas...</p>
                    </div>

                    <!-- Stats Grid - Tres Tarjetas -->
                    <div v-if="user.sede_id && !loading" class="stats-grid">
                        <div class="stat-card" :class="{ 'animate-in': !loading }">
                            <div class="stat-icon total-califications">
                                <i class="fas fa-chart-bar"></i>
                            </div>
                            <div class="stat-info">
                                <h3>{{ formatNumber(stats.totalCalificaciones) }}</h3>
                                <p>Total Calificaciones</p>
                                <small v-if="hasActiveFilters">Con filtros aplicados</small>
                                <small v-else>En esta sede</small>
                            </div>
                        </div>
                        <div class="stat-card" :class="{ 'animate-in': !loading }">
                            <div class="stat-icon areas-evaluated">
                                <i class="fas fa-th-large"></i>
                            </div>
                            <div class="stat-info">
                                <h3>{{ stats.areasEvaluadas || 0 }}</h3>
                                <p>Áreas Evaluadas</p>
                                <small v-if="hasActiveFilters">Con filtros aplicados</small>
                                <small v-else>Áreas distintas evaluadas</small>
                            </div>
                        </div>
                        <div class="stat-card" :class="{ 'animate-in': !loading }">
                            <div class="stat-icon percentage-general">
                                <i class="fas fa-percentage"></i>
                            </div>
                            <div class="stat-info">
                                <h3>{{ formatPercentage(stats.porcentajeGeneral) }}%</h3>
                                <p>Porcentaje General</p>
                                <small v-if="filters.tipo_calificacion">
                                    {{ getTipoCalificacionLabel(filters.tipo_calificacion) }}
                                </small>
                                <small v-else>Promedio general</small>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Section -->
                    <div v-if="user.sede_id && !loading" class="charts-section">
                        <!-- Calificaciones por Área Chart -->
                        <div class="chart-section">
                            <div class="chart-header">
                                <h3>
                                    <i class="fas fa-chart-bar"></i>
                                    Calificaciones por Área
                                </h3>
                                <button @click="refreshChart" class="btn-refresh" :disabled="refreshingChart">
                                    <i class="fas fa-sync-alt" :class="{ 'spinning': refreshingChart }"></i>
                                    Actualizar
                                </button>
                            </div>
                            <div class="chart-wrapper">
                                <canvas ref="areaChart"></canvas>
                            </div>
                            <div v-if="calificacionesPorArea.length === 0" class="empty-state">
                                <i class="fas fa-chart-bar"></i>
                                <p>No hay datos disponibles para mostrar</p>
                            </div>
                        </div>

                        <!-- Promedio por Área Chart -->
                        <div v-if="calificacionesPorArea.length > 0" class="chart-section">
                            <div class="chart-header">
                                <h3>
                                    <i class="fas fa-star"></i>
                                    Promedio de Calificación por Área
                                </h3>
                            </div>
                            <div class="chart-wrapper">
                                <canvas ref="promedioChart"></canvas>
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
import { Chart, registerables } from 'chart.js';
import GestorAreasManagement from './GestorAreasManagement.vue';
import GestorPreguntasManagement from './GestorPreguntasManagement.vue';

Chart.register(...registerables);

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
            filters: {
                sede_id: null,
                area_id: '',
                fecha_inicio: '',
                fecha_fin: '',
                tipo_calificacion: ''
            },
            areas: [],
            stats: {
                totalCalificaciones: 0,
                areasEvaluadas: 0,
                porcentajeGeneral: 0
            },
            calificacionesPorArea: [],
            showUserMenu: false,
            loading: true,
            refreshingChart: false,
            areaChart: null,
            promedioChart: null
        }
    },
    computed: {
        maxCalificaciones() {
            return Math.max(...this.calificacionesPorArea.map(item => item.total), 1);
        },
        hasActiveFilters() {
            return !!(this.filters.area_id || this.filters.fecha_inicio || this.filters.fecha_fin || this.filters.tipo_calificacion);
        }
    },
    watch: {
        activeTab(newTab) {
            if (newTab === 'dashboard' && this.user.sede_id && this.calificacionesPorArea.length > 0) {
                this.$nextTick(() => {
                    this.renderCharts();
                });
            }
        },
        calificacionesPorArea() {
            if (this.activeTab === 'dashboard' && this.user.sede_id) {
                this.$nextTick(() => {
                    this.renderCharts();
                });
            }
        }
    },
    async mounted() {
        await this.loadUserData();
        if (this.user.sede_id) {
            this.filters.sede_id = this.user.sede_id;
            await this.loadAreas();
            await this.loadStats();
            await this.loadCalificacionesPorArea();
        }
    },
    beforeUnmount() {
        if (this.areaChart) {
            this.areaChart.destroy();
        }
        if (this.promedioChart) {
            this.promedioChart.destroy();
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

        async loadAreas() {
            try {
                const response = await fetch(`/api/areas?sede_id=${this.user.sede_id}`, {
                    credentials: 'include'
                });
                if (response.ok) {
                    this.areas = await response.json();
                } else {
                    this.areas = [];
                }
            } catch (error) {
                console.error('Error cargando áreas:', error);
                this.areas = [];
            }
        },

        async loadStats() {
            try {
                this.loading = true;
                const params = new URLSearchParams();
                
                if (this.filters.sede_id) {
                    params.append('sede_id', this.filters.sede_id);
                }
                if (this.filters.area_id) {
                    params.append('area_id', this.filters.area_id);
                }
                if (this.filters.fecha_inicio) {
                    params.append('fecha_inicio', this.filters.fecha_inicio);
                }
                if (this.filters.fecha_fin) {
                    params.append('fecha_fin', this.filters.fecha_fin);
                }
                if (this.filters.tipo_calificacion) {
                    params.append('tipo_calificacion', this.filters.tipo_calificacion);
                }

                const response = await fetch(`/api/gestor/stats?${params.toString()}`, {
                    credentials: 'include'
                });
                if (response.ok) {
                    this.stats = await response.json();
                } else {
                    this.stats = {
                        totalCalificaciones: 0,
                        areasEvaluadas: 0,
                        porcentajeGeneral: 0
                    };
                }
            } catch (error) {
                console.error('Error cargando stats:', error);
                this.stats = {
                    totalCalificaciones: 0,
                    areasEvaluadas: 0,
                    porcentajeGeneral: 0
                };
            } finally {
                this.loading = false;
            }
        },

        async applyFilters() {
            await this.loadStats();
            await this.loadCalificacionesPorArea();
        },

        resetFilters() {
            this.filters = {
                sede_id: this.user.sede_id,
                area_id: '',
                fecha_inicio: '',
                fecha_fin: '',
                tipo_calificacion: ''
            };
            this.applyFilters();
        },

        formatPercentage(value) {
            if (value === null || value === undefined) return '0.00';
            return parseFloat(value).toFixed(2);
        },

        getTipoCalificacionLabel(tipo) {
            const labels = {
                'csat': 'CSAT',
                'nps': 'NPS',
                'fcr': 'FCR'
            };
            return labels[tipo] || tipo;
        },

        async loadCalificacionesPorArea() {
            try {
                const params = new URLSearchParams();
                
                if (this.filters.sede_id) {
                    params.append('sede_id', this.filters.sede_id);
                }
                if (this.filters.area_id) {
                    params.append('area_id', this.filters.area_id);
                }
                if (this.filters.fecha_inicio) {
                    params.append('fecha_inicio', this.filters.fecha_inicio);
                }
                if (this.filters.fecha_fin) {
                    params.append('fecha_fin', this.filters.fecha_fin);
                }
                if (this.filters.tipo_calificacion) {
                    params.append('tipo_calificacion', this.filters.tipo_calificacion);
                }

                const response = await fetch(`/api/gestor/calificaciones-por-area?${params.toString()}`, {
                    credentials: 'include'
                });
                if (response.ok) {
                    this.calificacionesPorArea = await response.json();
                    this.$nextTick(() => {
                        this.renderCharts();
                    });
                } else {
                    this.calificacionesPorArea = [];
                }
            } catch (error) {
                console.error('Error cargando calificaciones por área:', error);
                this.calificacionesPorArea = [];
            }
        },

        renderCharts() {
            if (!this.$refs.areaChart || this.calificacionesPorArea.length === 0) {
                return;
            }

            // Destruir gráficos anteriores si existen
            if (this.areaChart) {
                this.areaChart.destroy();
            }
            if (this.promedioChart) {
                this.promedioChart.destroy();
            }

            // Gráfico de Calificaciones por Área
            const areaCtx = this.$refs.areaChart;
            if (areaCtx) {
                const labels = this.calificacionesPorArea.map(item => item.area);
                const data = this.calificacionesPorArea.map(item => item.total);

                this.areaChart = new Chart(areaCtx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Total de Calificaciones',
                            data: data,
                            backgroundColor: 'rgba(79, 70, 229, 0.8)',
                            borderColor: 'rgba(79, 70, 229, 1)',
                            borderWidth: 2,
                            borderRadius: 8,
                            borderSkipped: false,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                padding: 12,
                                titleFont: {
                                    size: 14,
                                    weight: 'bold'
                                },
                                bodyFont: {
                                    size: 13
                                },
                                callbacks: {
                                    afterLabel: (context) => {
                                        const index = context.dataIndex;
                                        const item = this.calificacionesPorArea[index];
                                        return `Promedio: ${item.promedio}/5`;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0,
                                    font: {
                                        size: 12
                                    }
                                },
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)'
                                }
                            },
                            x: {
                                ticks: {
                                    font: {
                                        size: 11
                                    }
                                },
                                grid: {
                                    display: false
                                }
                            }
                        },
                        animation: {
                            duration: 1000,
                            easing: 'easeOutQuart'
                        }
                    }
                });
            }

            // Gráfico de Promedio por Área
            const promedioCtx = this.$refs.promedioChart;
            if (promedioCtx) {
                const labels = this.calificacionesPorArea.map(item => item.area);
                const data = this.calificacionesPorArea.map(item => parseFloat(item.promedio) || 0);

                this.promedioChart = new Chart(promedioCtx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Promedio de Calificación',
                            data: data,
                            backgroundColor: 'rgba(5, 150, 105, 0.1)',
                            borderColor: 'rgba(5, 150, 105, 1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: 'rgba(5, 150, 105, 1)',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 6,
                            pointHoverRadius: 8
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                padding: 12,
                                titleFont: {
                                    size: 14,
                                    weight: 'bold'
                                },
                                bodyFont: {
                                    size: 13
                                },
                                callbacks: {
                                    label: (context) => {
                                        return `Promedio: ${context.parsed.y.toFixed(2)}/5`;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 5,
                                ticks: {
                                    stepSize: 0.5,
                                    font: {
                                        size: 12
                                    },
                                    callback: function(value) {
                                        return value + '/5';
                                    }
                                },
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)'
                                }
                            },
                            x: {
                                ticks: {
                                    font: {
                                        size: 11
                                    }
                                },
                                grid: {
                                    display: false
                                }
                            }
                        },
                        animation: {
                            duration: 1000,
                            easing: 'easeOutQuart'
                        }
                    }
                });
            }
        },

        async refreshChart() {
            this.refreshingChart = true;
            try {
                await this.loadCalificacionesPorArea();
                await this.loadStats();
            } finally {
                this.refreshingChart = false;
            }
        },

        formatNumber(num) {
            if (num === null || num === undefined) return '0';
            return new Intl.NumberFormat('es-ES').format(num);
        },

        formatDecimal(num) {
            if (num === null || num === undefined) return '0.00';
            return parseFloat(num).toFixed(2);
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

/* Loading State */
.loading-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 2rem;
    gap: 1rem;
}

.spinner {
    width: 50px;
    height: 50px;
    border: 4px solid #e5e7eb;
    border-top-color: #4f46e5;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.loading-container p {
    color: #6b7280;
    font-size: 1rem;
}

/* Animations */
.animate-in {
    animation: fadeInUp 0.5s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.stat-card:nth-child(1) { animation-delay: 0.1s; }
.stat-card:nth-child(2) { animation-delay: 0.2s; }
.stat-card:nth-child(3) { animation-delay: 0.3s; }
.stat-card:nth-child(4) { animation-delay: 0.4s; }

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

/* Filters Section */
.filters-section {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}

.filters-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.filters-header h3 {
    margin: 0;
    color: #1f2937;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 1.1rem;
}

.filters-header h3 i {
    color: #4f46e5;
}

.btn-reset-filters {
    background: #fee2e2;
    color: #dc2626;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-reset-filters:hover {
    background: #fecaca;
}

.filters-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.filter-group label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.filter-group label i {
    color: #6b7280;
    font-size: 0.875rem;
}

.filter-select {
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    background: white;
    color: #1f2937;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.filter-select:hover {
    border-color: #9ca3af;
}

.filter-select:focus {
    outline: none;
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.date-range {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.filter-date {
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    background: white;
    color: #1f2937;
    font-size: 0.9rem;
    flex: 1;
    transition: all 0.3s ease;
}

.filter-date:hover {
    border-color: #9ca3af;
}

.filter-date:focus {
    outline: none;
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.date-separator {
    color: #6b7280;
    font-size: 0.875rem;
    white-space: nowrap;
}

.stat-icon.areas-evaluated {
    background: #7c3aed;
}

.stat-icon.percentage-general {
    background: #059669;
}

.stat-trend {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    margin-top: 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
}

.stat-trend.trend-up {
    background: #d1fae5;
    color: #059669;
}

.stat-trend.trend-down {
    background: #fee2e2;
    color: #dc2626;
}

.rating-stars {
    display: flex;
    gap: 0.25rem;
    margin-top: 0.5rem;
}

.rating-stars i {
    font-size: 0.9rem;
    color: #d1d5db;
}

.rating-stars i.filled {
    color: #fbbf24;
}

/* Charts Section */
.charts-section {
    display: flex;
    flex-direction: column;
    gap: 2rem;
    margin-top: 2rem;
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

.chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.chart-header h3 {
    margin: 0;
    color: #1f2937;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 1.25rem;
}

.chart-header h3 i {
    color: #4f46e5;
}

.btn-refresh {
    background: #f3f4f6;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    color: #374151;
    transition: all 0.3s ease;
}

.btn-refresh:hover:not(:disabled) {
    background: #e5e7eb;
    color: #1f2937;
}

.btn-refresh:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-refresh i.spinning {
    animation: spin 1s linear infinite;
}

.chart-wrapper {
    position: relative;
    height: 350px;
    margin-top: 1rem;
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem;
    color: #9ca3af;
    text-align: center;
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.empty-state p {
    margin: 0;
    font-size: 1rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 3rem;
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
    
    .filters-grid {
        grid-template-columns: 1fr;
    }
    
    .date-range {
        flex-direction: column;
        align-items: stretch;
    }
    
    .date-separator {
        text-align: center;
    }
    
    .filters-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .chart-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .chart-wrapper {
        height: 250px;
    }
    
    .charts-section {
        gap: 1.5rem;
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