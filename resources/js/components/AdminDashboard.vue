<template>
    <div class="admin-dashboard">
        <header class="admin-header">
            <div class="header-content">
                <div class="logo">
                    <i class="fas fa-university"></i>
                    <h1>UNIFRANZ</h1>
                    <sede-selector 
                        v-model="sedeSeleccionada"
                        @cambio-sede="onCambioSede"
                        class="sede-selector-compact"
                    />
                </div>
                <div class="admin-info">
                    <div class="user-dropdown">
                        <button class="user-toggle" @click="toggleUserMenu">
                            <img :src="user?.avatar || '/images/default-avatar.png'" :alt="user?.name" class="user-avatar">
                            <span class="user-name">{{ user?.name || 'Cargando...' }}</span>
                            <i class="fas fa-chevron-down" :class="{ 'rotate': showUserMenu }"></i>
                        </button>
                        
                        <div v-if="showUserMenu" class="dropdown-menu">
                            <div class="dropdown-header">
                                <strong>{{ user?.name || 'Usuario' }}</strong>
                                <br>
                                <small>{{ user?.email || '' }}</small>
                                <div class="role-badge admin">Administrador</div>
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

        <main class="admin-main">
            <!-- Sidebar -->
            <nav class="admin-sidebar">
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
            <div class="admin-content">
                <!-- Dashboard -->
                <div v-if="activeTab === 'dashboard'" class="tab-content">
                    <div class="dashboard-header">
                        <h1>Dashboard</h1>
                        <p>{{ getMensajeSede() }}</p>
                    </div>

                    <!-- Filtros -->
                    <div class="filters-section">
                        <div class="filter-grid">
                            <div class="filter-group">
                                <label><i class="fas fa-calendar"></i> Rango de Fechas</label>
                                <div class="date-range">
                                    <input type="date" v-model="filters.fechaInicio" class="form-input">
                                    <span>a</span>
                                    <input type="date" v-model="filters.fechaFin" class="form-input">
                                </div>
                            </div>
                            <div class="filter-group">
                                <label><i class="fas fa-layer-group"></i> Nivel</label>
                                <select v-model="filters.nivelId" class="form-select">
                                    <option value="">Todos los niveles</option>
                                    <option v-for="nivel in nivelesCalificacion" :key="nivel.id" :value="nivel.id">
                                        {{ nivel.nombre }}
                                    </option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label><i class="fas fa-building"></i> Área</label>
                                <select v-model="filters.areaId" class="form-select">
                                    <option value="">Todas</option>
                                    <option v-for="area in areasAgrupadas" :key="area.id" :value="area.id">
                                        {{ area.nombre }}
                                    </option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label><i class="fas fa-chart-line"></i> Indicador</label>
                                <select v-model="filters.tipoCalificacion" class="form-select">
                                    <option value="">Todos los indicadores</option>
                                    <option value="fcr">FCR</option>
                                    <option value="csat">CSAT</option>
                                    <option value="nps">NPS</option>
                                </select>
                            </div>
                            <div class="filter-actions">
                                <button @click="cargarEstadisticas" class="btn-primary">
                                    <i class="fas fa-sync-alt"></i> Actualizar
                                </button>
                                <button @click="exportarReporte" class="btn-secondary">
                                    <i class="fas fa-download"></i> Exportar
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Estadísticas Principales -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon total-calificaciones">
                                <i class="fas fa-clipboard-list"></i>
                            </div>
                            <div class="stat-info">
                                <h3>{{ estadisticas.totales.encuestasRespondidas || 0 }}</h3>
                                <p>Encuestas Respondidas{{ filters.tipoCalificacion ? ` (${filters.tipoCalificacion.toUpperCase()})` : '' }}</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon total-areas">
                                <i class="fas fa-th-large"></i>
                            </div>
                            <div class="stat-info">
                                <h3>{{ estadisticas.totales.areas || 0 }}</h3>
                                <p>Áreas Evaluadas</p>
                            </div>
                        </div>
                        <div class="stat-card" v-if="filters.tipoCalificacion && estadisticas.totales.valorIndicador !== null">
                            <div class="stat-icon avg-rating">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="stat-info">
                                <h3>{{ estadisticas.totales.valorIndicador || '0.0' }}%</h3>
                                <p>{{ obtenerNombreIndicador(filters.tipoCalificacion) }}</p>
                            </div>
                        </div>
                        <div class="stat-card" v-else>
                            <div class="stat-icon avg-rating">
                                <i class="fas fa-star"></i>
                            </div>
                            <div class="stat-info">
                                <h3>{{ estadisticas.totales.promedioGeneral || '0.0' }}</h3>
                                <p>Promedio General</p>
                            </div>
                        </div>
                    </div>

                    <!-- Gráficos Principales -->
                    <div class="charts-grid">
                        <!-- Distribución por Niveles -->
                        <div class="chart-card">
                            <div class="chart-header">
                                <h3>Distribución por Niveles de Satisfacción</h3>
                                <p>Porcentaje de respuestas por nivel</p>
                            </div>
                            <div class="chart-container">
                                <canvas ref="nivelesChart"></canvas>
                            </div>
                        </div>

                        <!-- Calificaciones por Área -->
                        <div class="chart-card">
                            <div class="chart-header">
                                <h3>Calificaciones por Área</h3>
                                <p>Promedio de calificaciones por área</p>
                            </div>
                            <div class="chart-container">
                                <canvas ref="areasChart"></canvas>
                            </div>
                        </div>

                        <!-- Preguntas Más Respondidas -->
                        <div class="chart-card full-width">
                            <div class="chart-header">
                                <h3>Preguntas Más Respondidas</h3>
                                <p>Top 10 preguntas con más respuestas</p>
                            </div>
                            <div class="chart-container">
                                <canvas ref="preguntasChart"></canvas>
                            </div>
                        </div>

                        <!-- Evolución Temporal -->
                        <div class="chart-card full-width">
                            <div class="chart-header">
                                <h3>Evolución Temporal</h3>
                                <p>Calificaciones por semana</p>
                            </div>
                            <div class="chart-container">
                                <canvas ref="evolucionChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Tablas Detalladas -->
                    <div class="tables-grid">
                        <!-- Top Áreas -->
                        <div class="table-card">
                            <div class="table-header">
                                <h3>Top 5 Áreas Mejor Calificadas</h3>
                            </div>
                            <div class="table-container">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Área</th>
                                            <th>Promedio</th>
                                            <th>Total Respuestas</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="area in estadisticas.topAreas" :key="area.id">
                                            <td>
                                                <div class="area-info">
                                                    <strong>{{ area.nombre }}</strong>
                                                    <small>{{ area.codigo }}</small>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="rating-badge" :class="getRatingClass(area.promedio)">
                                                    {{ area.promedio.toFixed(1) }}
                                                </span>
                                            </td>
                                            <td>{{ area.total_respuestas }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Distribución Respuestas -->
                        <div class="table-card">
                            <div class="table-header">
                                <h3>Distribución de Respuestas</h3>
                            </div>
                            <div class="table-container">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Tipo Pregunta</th>
                                            <th>Cantidad</th>
                                            <th>Porcentaje</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="tipo in estadisticas.distribucionTipos" :key="tipo.tipo">
                                            <td>
                                                <span class="tipo-badge" :class="getTipoBadgeClass(tipo.tipo)">
                                                    {{ getTipoTexto(tipo.tipo) }}
                                                </span>
                                            </td>
                                            <td>{{ tipo.cantidad }}</td>
                                            <td>{{ tipo.porcentaje }}%</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Preguntas Management -->
                <div v-if="activeTab === 'preguntas'" class="tab-content">
                    <div class="module-header">
                        <h2>Gestión de Preguntas</h2>
                        <p>{{ getMensajeSede() }}</p>
                    </div>
                    <PreguntasManagement />
                </div>

                <!-- Áreas Management -->
                <div v-if="activeTab === 'areas'" class="tab-content">
                    <div class="module-header">
                        <h2>Gestión de Áreas</h2>
                        <p>{{ getMensajeSede() }}</p>
                    </div>
                    <AreasManagement />
                </div>

                <!-- Usuarios Management -->
                <div v-if="activeTab === 'usuarios'" class="tab-content">
                    <div class="module-header">
                        <h2>Gestión de Usuarios</h2>
                        <p>Administra usuarios y asigna sedes a gestores</p>
                    </div>
                    <UsersManagement />
                </div>

                <!-- Reportes -->
                <div v-if="activeTab === 'reportes'" class="tab-content">
                    <h2>Reportes Detallados</h2>
                    <p>{{ getMensajeSede() }}</p>
                    <!-- Contenido de reportes extendidos -->
                </div>
            </div>
        </main>

        <!-- User Menu Overlay -->
        <div v-if="showUserMenu" class="overlay" @click="showUserMenu = false"></div>

        <!-- Loading Overlay -->
        <div v-if="cargando" class="loading-overlay">
            <div class="loading-content">
                <div class="loading-spinner-large"></div>
                <p>Cargando estadísticas...</p>
            </div>
        </div>
    </div>
</template>

<script>
import Swal from 'sweetalert2';
import PreguntasManagement from './PreguntasManagement.vue';
import AreasManagement from './AreasManagement.vue';
import SedeSelector from './SedeSelector.vue';
import UsersManagement from './UsersManagement.vue';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

export default {
    name: 'AdminDashboard',
    components: {
        UsersManagement,
        PreguntasManagement,
        AreasManagement,
        SedeSelector
    },
    data() {
        return {
            user: null,
            sedeSeleccionada: null,
            activeTab: 'dashboard',
            // En el data() del AdminDashboard, actualiza las tabs:
            tabs: [
                { id: 'dashboard', name: 'Dashboard', icon: 'fas fa-tachometer-alt' },
                { id: 'usuarios', name: 'Usuarios', icon: 'fas fa-users' },
                { id: 'preguntas', name: 'Preguntas', icon: 'fas fa-question-circle' },
                { id: 'areas', name: 'Áreas', icon: 'fas fa-th-large' },
            ],
            showUserMenu: false,
            cargando: false,
            
            // Filtros
            filters: {
                fechaInicio: this.getFechaInicioMes(),
                fechaFin: this.getFechaHoy(),
                areaId: '',
                nivelId: '',
                tipoCalificacion: '' // FCR, CSAT, NPS
            },
            
            // Datos
            areas: [],
            nivelesCalificacion: [],
            estadisticas: {
                totales: {},
                distribucionNiveles: [],
                calificacionesAreas: [],
                preguntasPopulares: [],
                evolucionTemporal: [],
                topAreas: [],
                distribucionTipos: []
            },
            
            // Charts
            nivelesChart: null,
            areasChart: null,
            preguntasChart: null,
            evolucionChart: null
        }
    },
    computed: {
        getMensajeSede() {
            return () => {
                const sede = window.SedeStore ? window.SedeStore.sedeActual : null;
                return sede 
                    ? `Mostrando contenido de la sede ${sede.nombre}`
                    : 'Mostrando todo el contenido del sistema';
            };
        },
        // 🔥 NUEVO: Agrupar áreas por nombre (eliminar duplicados) y filtrar por sede
        areasAgrupadas() {
            const sedeId = this.sedeSeleccionada ? this.sedeSeleccionada.id : null;
            
            // Filtrar áreas por sede si está seleccionada
            let areasFiltradas = this.areas;
            if (sedeId) {
                areasFiltradas = this.areas.filter(area => area.sede_id === sedeId);
            }
            
            // Agrupar por nombre para eliminar duplicados
            const areasUnicas = [];
            const nombresVistos = new Set();
            
            areasFiltradas.forEach(area => {
                const nombreNormalizado = area.nombre.trim().toLowerCase();
                
                // Si no hemos visto este nombre antes, agregarlo
                if (!nombresVistos.has(nombreNormalizado)) {
                    nombresVistos.add(nombreNormalizado);
                    areasUnicas.push(area);
                }
            });
            
            // Ordenar alfabéticamente por nombre
            return areasUnicas.sort((a, b) => {
                return a.nombre.localeCompare(b.nombre);
            });
        }
    },
    async mounted() {
        await this.loadUserData();
        await this.cargarDatosBase();
        await this.cargarEstadisticas();
    },
    methods: {
        async loadUserData() {
            try {
                const response = await fetch('/api/user');
                if (response.ok) {
                    this.user = await response.json();
                } else {
                    this.$router.push('/login');
                }
            } catch (error) {
                console.error('Error cargando usuario:', error);
                this.$router.push('/login');
            }
        },

        async cargarDatosBase() {
            try {
                // Cargar áreas (sin filtrar por sede aquí, el filtro se aplica en computed)
                const areasResponse = await fetch('/api/areas');
                if (areasResponse.ok) {
                    this.areas = await areasResponse.json();
                }

                // Cargar niveles de calificación
                const nivelesResponse = await fetch('/api/niveles-calificacion');
                if (nivelesResponse.ok) {
                    this.nivelesCalificacion = await nivelesResponse.json();
                }
            } catch (error) {
                console.error('Error cargando datos base:', error);
            }
        },

        async cargarEstadisticas() {
            this.cargando = true;
            try {
                const params = new URLSearchParams();
                
                if (this.filters.fechaInicio) params.append('fecha_inicio', this.filters.fechaInicio);
                if (this.filters.fechaFin) params.append('fecha_fin', this.filters.fechaFin);
                if (this.filters.areaId) params.append('area_id', this.filters.areaId);
                if (this.filters.nivelId) params.append('nivel_id', this.filters.nivelId);
                if (this.filters.tipoCalificacion) params.append('tipo_calificacion', this.filters.tipoCalificacion);
                
                // Filtrar por sede si está seleccionada
                const sedeId = this.sedeSeleccionada ? this.sedeSeleccionada.id : null;
                if (sedeId) {
                    params.append('sede_id', sedeId);
                }

                const response = await fetch(`/api/estadisticas?${params.toString()}`);
                if (response.ok) {
                    this.estadisticas = await response.json();
                    this.$nextTick(() => {
                        this.renderizarGraficos();
                    });
                } else {
                    throw new Error('Error al cargar estadísticas');
                }
            } catch (error) {
                console.error('Error cargando estadísticas:', error);
                this.mostrarMensaje('Error al cargar las estadísticas', 'error');
            } finally {
                this.cargando = false;
            }
        },

        obtenerNombreIndicador(tipo) {
            const nombres = {
                'fcr': 'FCR',
                'csat': 'CSAT',
                'nps': 'NPS'
            };
            return nombres[tipo] || tipo.toUpperCase();
        },

        renderizarGraficos() {
            this.destruirGraficos();
            
            // Gráfico de distribución por niveles
            this.renderizarGraficoNiveles();
            
            // Gráfico de calificaciones por área
            this.renderizarGraficoAreas();
            
            // Gráfico de preguntas más respondidas
            this.renderizarGraficoPreguntas();
            
            // Gráfico de evolución temporal
            this.renderizarGraficoEvolucion();
        },

        renderizarGraficoNiveles() {
            const ctx = this.$refs.nivelesChart;
            if (!ctx) return;

            const data = this.estadisticas.distribucionNiveles || [];
            
            this.nivelesChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: data.map(item => item.nivel),
                    datasets: [{
                        data: data.map(item => item.cantidad),
                        backgroundColor: [
                            '#ef4444', '#f59e0b', '#10b981', '#3b82f6', '#8b5cf6'
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        },

        renderizarGraficoAreas() {
            const ctx = this.$refs.areasChart;
            if (!ctx) return;

            const data = this.estadisticas.calificacionesAreas || [];
            
            this.areasChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.map(item => item.area_nombre),
                    datasets: [{
                        label: 'Promedio de Calificación',
                        data: data.map(item => item.promedio),
                        backgroundColor: '#4f46e5',
                        borderColor: '#3730a3',
                        borderWidth: 1,
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 10,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `Promedio: ${context.raw.toFixed(1)}`;
                                }
                            }
                        }
                    }
                }
            });
        },

        renderizarGraficoPreguntas() {
            const ctx = this.$refs.preguntasChart;
            if (!ctx) return;

            const data = this.estadisticas.preguntasPopulares || [];
            
            this.preguntasChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.map(item => this.acortarTexto(item.pregunta_texto, 40)),
                    datasets: [{
                        label: 'Respuestas',
                        data: data.map(item => item.total_respuestas),
                        backgroundColor: '#10b981',
                        borderColor: '#059669',
                        borderWidth: 1,
                        borderRadius: 4
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                title: (context) => {
                                    const index = context[0].dataIndex;
                                    return data[index].pregunta_texto;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true
                        }
                    }
                }
            });
        },

        renderizarGraficoEvolucion() {
            const ctx = this.$refs.evolucionChart;
            if (!ctx) return;

            const data = this.estadisticas.evolucionTemporal || [];
            
            this.evolucionChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.map(item => item.fecha),
                    datasets: [{
                        label: 'Promedio de Calificaciones',
                        data: data.map(item => item.promedio),
                        borderColor: '#8b5cf6',
                        backgroundColor: 'rgba(139, 92, 246, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 10,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `Promedio: ${context.raw.toFixed(1)}`;
                                }
                            }
                        }
                    }
                }
            });
        },

        destruirGraficos() {
            if (this.nivelesChart) {
                this.nivelesChart.destroy();
                this.nivelesChart = null;
            }
            if (this.areasChart) {
                this.areasChart.destroy();
                this.areasChart = null;
            }
            if (this.preguntasChart) {
                this.preguntasChart.destroy();
                this.preguntasChart = null;
            }
            if (this.evolucionChart) {
                this.evolucionChart.destroy();
                this.evolucionChart = null;
            }
        },

        // Métodos utilitarios
        getFechaInicioMes() {
            const date = new Date();
            date.setDate(1);
            return date.toISOString().split('T')[0];
        },

        getFechaHoy() {
            return new Date().toISOString().split('T')[0];
        },

        acortarTexto(texto, longitud) {
            if (!texto) return '';
            return texto.length > longitud ? texto.substring(0, longitud) + '...' : texto;
        },

        getTipoTexto(tipo) {
            const tipos = {
                'opcion_unica': 'Opción Única',
                'opcion_multiple': 'Opción Múltiple',
                'texto_libre': 'Texto Libre',
                'indicador_0_10': 'Indicador 0-10',
                'opcion_unica_texto_libre': 'Opción Única + Texto'
            };
            return tipos[tipo] || tipo;
        },

        getTipoBadgeClass(tipo) {
            const clases = {
                'opcion_unica': 'primary',
                'opcion_multiple': 'info',
                'texto_libre': 'secondary',
                'indicador_0_10': 'warning',
                'opcion_unica_texto_libre': 'success'
            };
            return clases[tipo] || 'secondary';
        },

        getRatingClass(promedio) {
            if (promedio >= 8) return 'excelent';
            if (promedio >= 6) return 'good';
            if (promedio >= 4) return 'regular';
            return 'poor';
        },

        async exportarReporte() {
            try {
                const params = new URLSearchParams();
                
                if (this.filters.fechaInicio) params.append('fecha_inicio', this.filters.fechaInicio);
                if (this.filters.fechaFin) params.append('fecha_fin', this.filters.fechaFin);
                if (this.filters.areaId) params.append('area_id', this.filters.areaId);
                if (this.filters.nivelId) params.append('nivel_id', this.filters.nivelId);
                
                const sedeId = this.sedeSeleccionada ? this.sedeSeleccionada.id : null;
                if (sedeId) params.append('sede_id', sedeId);

                const response = await fetch(`/api/estadisticas/exportar?${params.toString()}`);
                if (response.ok) {
                    const blob = await response.blob();
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `reporte-estadisticas-${new Date().toISOString().split('T')[0]}.xlsx`;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                } else {
                    throw new Error('Error al exportar reporte');
                }
            } catch (error) {
                console.error('Error exportando reporte:', error);
                this.mostrarMensaje('Error al exportar el reporte', 'error');
            }
        },

        onCambioSede(sede) {
            console.log('🎯 AdminDashboard: Sede seleccionada:', sede);
            this.sedeSeleccionada = sede;
            
            // 🔥 CORRECCIÓN: Resetear filtro de área cuando cambia la sede
            // Esto evita que quede seleccionada un área de otra sede
            this.filters.areaId = '';
            
            // Recargar estadísticas con la nueva sede
            this.cargarEstadisticas();
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
        },

        mostrarMensaje(mensaje, tipo) {
            Swal.fire({
                icon: tipo === 'success' ? 'success' : 'error',
                title: tipo === 'success' ? '¡Éxito!' : 'Error',
                text: mensaje,
                timer: tipo === 'success' ? 2000 : 3000,
                showConfirmButton: tipo !== 'success',
                confirmButtonColor: tipo === 'success' ? '#10b981' : '#ef4444'
            });
        }
    },

    beforeUnmount() {
        this.destruirGraficos();
    }
}
</script>

<style scoped>
/* Estilos existentes del AdminDashboard */

.admin-dashboard {
    min-height: 100vh;
    background: #f8fafc;
    position: relative;
}

.admin-header {
    background: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    position: sticky;
    top: 0;
    z-index: 100;
}

.header-content {
    max-width: 1400px;
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
    color: #4f46e5;
}

.logo h1 {
    font-size: 1.5rem;
    color: #1f2937;
    margin: 0;
}

.admin-info {
    display: flex;
    align-items: center;
}

/* User Dropdown Styles */
.user-dropdown {
    position: relative;
}

.user-toggle {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    background: none;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    cursor: pointer;
    transition: background-color 0.3s;
}

.user-toggle:hover {
    background: #f3f4f6;
}

.user-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
}

.user-name {
    font-weight: 500;
    color: #374151;
}

.user-toggle i {
    font-size: 0.875rem;
    color: #6b7280;
    transition: transform 0.3s;
}

.user-toggle i.rotate {
    transform: rotate(180deg);
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    margin-top: 0.5rem;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    min-width: 200px;
    z-index: 110;
}

.dropdown-header {
    padding: 1rem;
    border-bottom: 1px solid #f3f4f6;
}

.dropdown-header strong {
    display: block;
    color: #1f2937;
    margin-bottom: 0.25rem;
}

.dropdown-header small {
    color: #6b7280;
    font-size: 0.875rem;
}

.dropdown-divider {
    height: 1px;
    background: #e5e7eb;
    margin: 0.5rem 0;
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
    gap: 0.75rem;
    color: #374151;
    transition: background-color 0.3s;
}

.dropdown-item:hover {
    background: #f3f4f6;
}

.dropdown-item.logout-item {
    color: #dc2626;
}

.dropdown-item.logout-item:hover {
    background: #fef2f2;
    color: #b91c1c;
}

.overlay {
  pointer-events: none;
}

/* Main Content Styles */
.admin-main {
    display: flex;
    max-width: 1400px;
    margin: 0 auto;
    min-height: calc(100vh - 80px);
}

.admin-sidebar {
    width: 250px;
    background: white;
    box-shadow: 2px 0 4px rgba(0,0,0,0.1);
}

.sidebar-menu {
    padding: 1.5rem 0;
}

.menu-item {
    width: 100%;
    padding: 1rem 1.5rem;
    border: none;
    background: none;
    text-align: left;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: #6b7280;
    transition: all 0.3s;
}

.menu-item:hover {
    background: #f3f4f6;
    color: #4f46e5;
}

.menu-item.active {
    background: #4f46e5;
    color: white;
    border-right: 3px solid #3730a3;
}

.menu-item i {
    width: 20px;
    text-align: center;
}

.admin-content {
    flex: 1;
    padding: 2rem;
    background: #f8fafc;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: grid;
    grid-template-columns: auto auto; /* icon + number ajustados */
    grid-template-rows: auto auto;
    align-items: center;
    gap: 0.5rem 0.5rem; /* menos separación */
    justify-content: center; /* center grid as a whole */
    justify-items: center;   /* center items in their cells */
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
    grid-column: 1;
    grid-row: 1;
}

.stat-icon.total-calificaciones { background: #4f46e5; }
.stat-icon.total-areas { background: #059669; }
.stat-icon.total-preguntas { background: #dc2626; }
.stat-icon.avg-rating { background: #d97706; }

.stat-info { display: contents; }

.stat-info h3 {
    font-size: 2rem;
    margin: 0;
    color: #1f2937;
    grid-column: 2;
    grid-row: 1;
    align-self: center;
    justify-self: center;
}

.stat-info p {
    margin: 0.25rem 0 0 0;
    color: #6b7280;
    font-size: 0.875rem;
    grid-column: 1 / -1;
    grid-row: 2;
    text-align: center; /* center the label under both */
}

/* Recent Activity */
.recent-activity {
    background: white;
    padding: 1.5rem;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.recent-activity h3 {
    margin-bottom: 1rem;
    color: #1f2937;
}

.activity-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 0;
    border-bottom: 1px solid #e5e7eb;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #f3f4f6;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #4f46e5;
}

.activity-content p {
    margin: 0;
    color: #1f2937;
}

.activity-content small {
    color: #6b7280;
}

.tab-content {
    min-height: 500px;
}

/* Responsive */
@media (max-width: 1024px) {
    .admin-main {
        flex-direction: column;
    }
    
    .admin-sidebar {
        width: 100%;
        order: 2;
    }
    
    .admin-content {
        order: 1;
    }
    
    .sidebar-menu {
        display: flex;
        overflow-x: auto;
        padding: 1rem;
    }
    
    .menu-item {
        white-space: nowrap;
        flex-shrink: 0;
    }
}

@media (max-width: 768px) {
    .header-content {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
        padding: 1rem;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .user-name {
        display: none;
    }
    
    .admin-content {
        padding: 1rem;
    }
}

@media (max-width: 480px) {
    .logo h1 {
        font-size: 1.25rem;
    }
    
    .dropdown-menu {
        min-width: 180px;
        right: -50%;
    }
}


/* Estilos específicos para el AdminDashboard actualizado */
.sede-selector-compact {
    margin-left: 2rem;
}

.view-indicator {
    margin-bottom: 2rem;
}

.view-badge {
    display: inline-flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 0.25rem;
    padding: 1rem 1.5rem;
    background: #f0f9ff;
    border: 2px solid #0ea5e9;
    border-radius: 12px;
    font-weight: 600;
    font-size: 1.1rem;
}

.view-badge.all-sedes {
    background: #dbeafe;
    border-color: #1e40af;
    color: #1e40af;
}

.view-badge:not(.all-sedes) {
    background: #f0f9ff;
    border-color: #0ea5e9;
    color: #0369a1;
}

.view-badge small {
    font-weight: normal;
    font-size: 0.9rem;
    opacity: 0.8;
}

.sede-selector-section {
    margin: 2rem 0;
}

.reports-section {
    margin-top: 2rem;
}

.report-actions {
    display: flex;
    gap: 1rem;
    margin-bottom: 2rem;
    flex-wrap: wrap;
}

.btn-report {
    background: #4f46e5;
    color: white;
    border: none;
    padding: 1rem 1.5rem;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    transition: background 0.3s ease;
}

.btn-report:hover {
    background: #4338ca;
}

.filters-section {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.filters-section h4 {
    margin: 0 0 1rem 0;
    color: #1f2937;
}

.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.filter-group {
    align-items: flex-start;
    flex-direction: column;
    gap: 0.5rem;
    display: flex;
}

.filter-group label {
    font-weight: 600;
    color: #374151;
    font-size: 0.9rem;
}

.filter-group input,
.filter-group select {
    padding: 0.5rem 0.75rem;
    border: 2px solid #e5e7eb;
    border-radius: 6px;
    font-size: 0.9rem;
}

.filter-group input:focus,
.filter-group select:focus {
    outline: none;
    border-color: #4f46e5;
}

/* Responsive */
@media (max-width: 768px) {
    .sede-selector-compact {
        margin-left: 0;
        margin-top: 1rem;
    }
    
    .report-actions {
        flex-direction: column;
    }
    
    .btn-report {
        justify-content: center;
    }
}

/* Estilos específicos para el Dashboard actualizado */
.module-header {
    margin-bottom: 2rem;
}

.module-header h2 {
    color: #1f2937;
    margin-bottom: 0.5rem;
}

.module-header p {
    color: #6b7280;
    font-size: 0.9rem;
}

.areas-stats-section {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin: 2rem 0;
}

.areas-stats-section h3 {
    margin: 0 0 1.5rem 0;
    color: #1f2937;
}

.areas-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1rem;
}

.area-stat-card {
    background: #f8fafc;
    padding: 1.5rem;
    border-radius: 8px;
    border-left: 4px solid #4f46e5;
}

.area-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.area-header h4 {
    margin: 0;
    color: #1f2937;
    font-size: 1.1rem;
}

.area-code {
    background: #4f46e5;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 600;
}

.area-stats {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.area-stat {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #6b7280;
    font-size: 0.9rem;
}

.area-stat i {
    width: 16px;
    color: #4f46e5;
}

.no-activity {
    text-align: center;
    padding: 3rem;
    color: #6b7280;
}

.no-activity i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

/* Nuevos estilos para el Dashboard Estadístico */
.dashboard-header {
    margin-bottom: 2rem;
}

.dashboard-header h1 {
    color: #1f2937;
    margin-bottom: 0.5rem;
    font-size: 2rem;
}

.dashboard-header p {
    color: #6b7280;
    font-size: 1.1rem;
}

/* Filtros */
.filters-section {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}

.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 1.5rem;
    align-items: end;
}

/* Por defecto el rango ocupa 2 columnas para dar espacio a los 2 inputs */
.filter-grid > .filter-group:first-child {
    grid-column: span 2;
}

/* Layout horizontal compacto en pantallas anchas: 2fr | 1fr | 1fr | auto */
@media (min-width: 1024px) {
    .filters-section .filter-grid {
        grid-template-columns: 2fr 1fr 1fr auto;
        gap: 1rem;
    }
    .filters-section .filter-grid > .filter-group:first-child {
        grid-column: auto; /* ya no ocupa dos filas */
    }
    .filters-section .filter-actions {
        align-self: end;
        justify-content: flex-start;
    }
}

.date-range {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: nowrap; /* evita que las fechas se apilen */
    width: 100%;
}

.date-range span {
    color: #6b7280;
    font-size: 0.9rem;
}

/* tamaños consistentes para los inputs de fecha en la fila */
.date-range .form-input,
.date-range input[type="date"] {
    flex: 0 0 180px;
    max-width: 200px;
}

.filter-actions {
    display: flex;
    gap: 0.75rem;
    align-self: end;
}

/* Botones para filtros */
.btn-primary {
    background: #4f46e5;
    color: #ffffff;
    border: none;
    padding: 0.6rem 1rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-primary:hover { background: #4338ca; }

.btn-secondary {
    background: #6b7280;
    color: #ffffff;
    border: none;
    padding: 0.6rem 1rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-secondary:hover { background: #4b5563; }

/* Gráficos */
.charts-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.chart-card {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.chart-card.full-width {
    grid-column: 1 / -1;
}

.chart-header {
    margin-bottom: 1.5rem;
}

.chart-header h3 {
    color: #1f2937;
    margin-bottom: 0.5rem;
    font-size: 1.25rem;
}

.chart-header p {
    color: #6b7280;
    font-size: 0.9rem;
    margin: 0;
}

.chart-container {
    position: relative;
    height: 300px;
}

/* Tablas */
.tables-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.table-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
}

.table-header {
    padding: 1.25rem 1.5rem;
    background: #f8fafc;
    border-bottom: 1px solid #e5e7eb;
}

.table-header h3 {
    margin: 0;
    color: #1f2937;
    font-size: 1.1rem;
}

.table-container {
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th,
.data-table td {
    padding: 1rem 1.5rem;
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
}

.data-table th {
    background: #f8fafc;
    font-weight: 600;
    color: #374151;
    font-size: 0.875rem;
}

.data-table tr:last-child td {
    border-bottom: none;
}

.data-table tr:hover {
    background: #f9fafb;
}

/* Badges y estados */
.area-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.area-info strong {
    color: #1f2937;
}

.area-info small {
    color: #6b7280;
    font-size: 0.8rem;
}

.rating-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    color: white;
}

.rating-badge.excelent { background: #10b981; }
.rating-badge.good { background: #f59e0b; }
.rating-badge.regular { background: #f97316; }
.rating-badge.poor { background: #ef4444; }

.tipo-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
}

.tipo-badge.primary { background: #e0e7ff; color: #3730a3; }
.tipo-badge.info { background: #dbeafe; color: #1e40af; }
.tipo-badge.secondary { background: #f3f4f6; color: #374151; }
.tipo-badge.warning { background: #fef3c7; color: #92400e; }
.tipo-badge.success { background: #d1fae5; color: #065f46; }

/* Loading Overlay */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.loading-content {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
}

.loading-spinner-large {
    width: 50px;
    height: 50px;
    border: 4px solid #e5e7eb;
    border-top: 4px solid #4f46e5;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 1rem;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 1024px) {
    .charts-grid {
        grid-template-columns: 1fr;
    }
    
    .tables-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .filter-grid {
        grid-template-columns: 1fr;
    }
    
    .filter-actions {
        justify-content: stretch;
    }
    
    .filter-actions button {
        flex: 1;
    }
    
    .chart-container {
        height: 250px;
    }
}

@media (max-width: 480px) {
    .dashboard-header h1 {
        font-size: 1.5rem;
    }
    
    .chart-card {
        padding: 1rem;
    }
    
    .data-table {
        font-size: 0.875rem;
    }
    
    .data-table th,
    .data-table td {
        padding: 0.75rem 1rem;
    }
}
</style>