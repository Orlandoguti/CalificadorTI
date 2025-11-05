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
                                <label><i class="fas fa-building"></i> Área</label>
                                <select v-model="filters.areaId" class="form-select">
                                    <option value="">Todas</option>
                                    <option v-for="area in areasAgrupadas" :key="area.id" :value="area.id">
                                        {{ area.nombre }}
                                    </option>
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


                    <!-- Botones de Selección de Indicador -->
                    <div class="indicador-selector">
                        <button 
                            @click="filters.tipoCalificacion = ''; cargarEstadisticas()"
                            :class="['btn-indicador', { active: !filters.tipoCalificacion }]"
                        >
                            Todos
                        </button>
                        <button 
                            @click="filters.tipoCalificacion = 'csat'; cargarEstadisticas()"
                            :class="['btn-indicador', { active: filters.tipoCalificacion === 'csat' }]"
                        >
                            CSAT
                        </button>
                        <button 
                            @click="filters.tipoCalificacion = 'fcr'; cargarEstadisticas()"
                            :class="['btn-indicador', { active: filters.tipoCalificacion === 'fcr' }]"
                        >
                            FCR
                        </button>
                        <button 
                            @click="filters.tipoCalificacion = 'nps'; cargarEstadisticas()"
                            :class="['btn-indicador', { active: filters.tipoCalificacion === 'nps' }]"
                        >
                            NPS
                        </button>
                    </div>

                    <!-- Estadísticas Principales -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon total-calificaciones">
                                <i class="fas fa-clipboard-list"></i>
                            </div>
                            <div class="stat-info">
                                <h3>{{ estadisticas.totales.encuestasRespondidas || 0 }}</h3>
                                <p>Total Calificaciones{{ filters.tipoCalificacion ? ` (${filters.tipoCalificacion.toUpperCase()})` : '' }}</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon total-areas">
                                <i class="fas fa-th-large"></i>
                            </div>
                            <div class="stat-info">
                                <h3>{{ estadisticas.totales.areas || 0 }}</h3>
                                <p>Áreas Evaluadas{{ filters.tipoCalificacion ? ` (${filters.tipoCalificacion.toUpperCase()})` : '' }}</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon avg-rating">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div class="stat-info">
                                <h3 v-if="filters.tipoCalificacion && estadisticas.totales.valorIndicador !== null">
                                    {{ estadisticas.totales.valorIndicador || '0.0' }}%
                                </h3>
                                <h3 v-else>{{ estadisticas.totales.promedioGeneral || '0.0' }}%</h3>
                                <p v-if="filters.tipoCalificacion && estadisticas.totales.valorIndicador !== null">
                                    {{ obtenerNombreIndicador(filters.tipoCalificacion) }}
                                </p>
                                <p v-else>Porcentaje General</p>
                            </div>
                        </div>
                    </div>

                    <!-- Gráficos Principales -->
                    <!-- Nivel de Indicadores (Polar Area Charts) -->
                    <div class="polar-charts-section">
                        <div class="polar-chart-item" v-if="mostrarIndicador('csat')">
                            <div class="polar-chart-title">CSAT</div>
                            <div class="polar-chart-canvas-wrapper">
                                <canvas ref="polarChartCSAT"></canvas>
                            </div>
                            <div class="polar-chart-value">{{ getValorIndicador('csat') }}%</div>
                            <div class="polar-chart-responses">Nº de Respuestas: {{ getTotalRespuestas('csat') }}</div>
                        </div>

                        <div class="polar-chart-item" v-if="mostrarIndicador('fcr')">
                            <div class="polar-chart-title">FCR</div>
                            <div class="polar-chart-canvas-wrapper">
                                <canvas ref="polarChartFCR"></canvas>
                            </div>
                            <div class="polar-chart-value">{{ getValorIndicador('fcr') }}%</div>
                            <div class="polar-chart-responses">Nº de Respuestas: {{ getTotalRespuestas('fcr') }}</div>
                        </div>

                        <div class="polar-chart-item" v-if="mostrarIndicador('nps')">
                            <div class="polar-chart-title">NPS</div>
                            <div class="polar-chart-subtitle">Net Promoter Score</div>
                            <div class="polar-chart-canvas-wrapper">
                                <canvas ref="polarChartNPS"></canvas>
                            </div>
                            <div class="polar-chart-value">{{ getValorIndicador('nps') }}%</div>
                            <div class="polar-chart-responses">Nº de Respuestas: {{ getTotalRespuestas('nps') }}</div>
                        </div>
                    </div>
                    <!-- Cantidad de Respuestas por Área -->
                    <div class="charts-grid">
                        <div class="chart-card full-width">
                            <div class="chart-header" style="justify-items: center;">
                                <h3>Cantidad de Respuestas por Área</h3>
                                <p>Diferenciado por indicador</p>
                            </div>
                            <div class="chart-container chart-container-full-width">
                                <canvas ref="encuestasAreaChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Distribución NPS -->
                    <div class="chart-card">
                        <div class="chart-header" style="justify-items: center;">
                            <h3>Distribución NPS</h3>
                            <p>Promotores, Pasivos y Detractores</p>
                        </div>

                        <!-- Contenedor flexible: Chart a la izquierda + tarjetas a la derecha -->
                        <div class="chart-content-flex" style="display: flex;">
                            <div class="chart-container">
                                <canvas ref="distribucionNPSChart"></canvas>
                            </div>

                            <!-- Tarjetas de porcentajes ahora al lado -->
                            <div class="nps-distribution-cards">
                                <div class="nps-card promotores">
                                    <div class="nps-card-header">
                                        <span class="nps-label">Promotores</span>
                                        <span class="nps-range">(9-10)</span>
                                    </div>
                                    <div class="nps-percentage">{{ getNPSPorcentaje('promotores') }}%</div>
                                    <div class="nps-bar">
                                        <div class="nps-bar-fill promotores" :style="{ width: getNPSPorcentaje('promotores') + '%' }"></div>
                                    </div>
                                    <div class="nps-count">{{ getNPSCount('promotores') }} respuestas</div>
                                </div>

                                <div class="nps-card pasivos">
                                    <div class="nps-card-header">
                                        <span class="nps-label">Pasivos</span>
                                        <span class="nps-range">(7-8)</span>
                                    </div>
                                    <div class="nps-percentage">{{ getNPSPorcentaje('pasivos') }}%</div>
                                    <div class="nps-bar">
                                        <div class="nps-bar-fill pasivos" :style="{ width: getNPSPorcentaje('pasivos') + '%' }"></div>
                                    </div>
                                    <div class="nps-count">{{ getNPSCount('pasivos') }} respuestas</div>
                                </div>

                                <div class="nps-card detractores">
                                    <div class="nps-card-header">
                                        <span class="nps-label">Detractores</span>
                                        <span class="nps-range">(1-6)</span>
                                    </div>
                                    <div class="nps-percentage">{{ getNPSPorcentaje('detractores') }}%</div>
                                    <div class="nps-bar">
                                        <div class="nps-bar-fill detractores" :style="{ width: getNPSPorcentaje('detractores') + '%' }"></div>
                                    </div>
                                    <div class="nps-count">{{ getNPSCount('detractores') }} respuestas</div>
                                </div>
                            </div>
                        </div>
                    </div>


                    <!-- Relación Nivel vs Cantidad de Encuestas -->
                    <div class="charts-grid" v-for="tipo in tiposIndicadoresActivos" :key="tipo">
                        <div class="chart-card full-width">
                            <div class="chart-header" style="justify-items: center;">
                                <h3>Relación Nivel de {{ tipo.toUpperCase() }} vs Cantidad de Encuestas</h3>
                                <p>Por día o por mes según el rango seleccionado</p>
                            </div>
                            <div class="chart-container chart-container-full-width">
                                <canvas :ref="`relacionChart_${tipo}`"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Indicadores y Dimensiones -->
                    <div class="charts-grid" v-for="tipo in tiposIndicadoresActivos" :key="'dim-' + tipo">
                        <div class="chart-card full-width" v-if="estadisticas.indicadoresDimensiones && estadisticas.indicadoresDimensiones[tipo] && estadisticas.indicadoresDimensiones[tipo].length > 0">
                            <div class="chart-header" style="justify-items: center;">
                                <h3>{{ tipo.toUpperCase() }} - Dimensiones</h3>
                                <p>Distribución por dimensión</p>
                            </div>
                            <div class="chart-container chart-container-full-width">
                                <canvas :ref="`dimensionesChart_${tipo}`"></canvas>
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
                fechaInicio: '',
                fechaFin: '',
                areaId: '',
                nivelId: '',
                tipoCalificacion: '' // FCR, CSAT, NPS
            },
            
            // Datos
            areas: [],
            nivelesCalificacion: [],
            estadisticas: {
                totales: {},
                nivelIndicador: {},
                encuestasPorArea: [],
                relacionNivelEncuestas: {},
                indicadoresDimensiones: {},
                distribucionNPS: {},
                distribucionCSAT: {},
                distribucionFCR: {}
            },
            
            // Charts
            encuestasAreaChart: null,
            distribucionNPSChart: null,
            polarChartCSAT: null,
            polarChartFCR: null,
            polarChartNPS: null,
            relacionChartRefs: {},
            dimensionesChartRefs: {}
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
        },
        // Tipos de indicadores activos según filtro
        tiposIndicadoresActivos() {
            if (this.filters.tipoCalificacion) {
                return [this.filters.tipoCalificacion];
            }
            return ['csat', 'fcr', 'nps'];
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
                    console.log('📊 Estadísticas recibidas (completo):', JSON.stringify(this.estadisticas, null, 2));
                    console.log('📊 Nivel Indicador:', this.estadisticas.nivelIndicador);
                    console.log('📊 CSAT:', this.estadisticas.nivelIndicador?.csat);
                    console.log('📊 FCR:', this.estadisticas.nivelIndicador?.fcr);
                    console.log('📊 NPS:', this.estadisticas.nivelIndicador?.nps);
                    
                    // Verificar valores específicos
                    if (this.estadisticas.nivelIndicador) {
                        Object.keys(this.estadisticas.nivelIndicador).forEach(tipo => {
                            const indicador = this.estadisticas.nivelIndicador[tipo];
                            console.log(`📊 Indicador ${tipo}:`, {
                                valor: indicador?.valor,
                                totalRespuestas: indicador?.totalRespuestas,
                                tipo: typeof indicador,
                                esObjeto: typeof indicador === 'object'
                            });
                        });
                    }
                    
                    // Esperar a que el DOM esté completamente listo antes de renderizar gráficos
                    this.$nextTick(() => {
                        setTimeout(() => {
                            // Asegurar que los gráficos se destruyan antes de recrearlos
                            this.destruirGraficos();
                            // Renderizar gráficos después de un pequeño delay para asegurar que el DOM está listo
                            setTimeout(() => {
                                this.renderizarGraficos();
                            }, 50);
                        }, 100);
                    });
                } else {
                    const errorText = await response.text();
                    console.error('❌ Error en respuesta:', response.status, errorText);
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

        mostrarIndicador(tipo) {
            if (this.filters.tipoCalificacion) {
                return this.filters.tipoCalificacion === tipo;
            }
            return true;
        },

        getColorSemaforo(valor) {
            if (valor >= 70) return 'semaforo-verde';
            if (valor >= 50) return 'semaforo-amarillo';
            return 'semaforo-rojo';
        },

        getValorIndicador(tipo) {
            try {
                if (!this.estadisticas || !this.estadisticas.nivelIndicador) {
                    console.warn(`⚠️ nivelIndicador no existe en estadisticas para ${tipo}`);
                    return 0;
                }
                
                const indicador = this.estadisticas.nivelIndicador[tipo];
                console.log(`📊 getValorIndicador(${tipo}):`, indicador);
                console.log(`📊 Tipo de indicador:`, typeof indicador);
                
                if (typeof indicador === 'object' && indicador !== null) {
                    const valor = indicador.valor !== undefined && indicador.valor !== null 
                        ? parseFloat(indicador.valor) 
                        : 0;
                    console.log(`✅ Valor numérico para ${tipo}:`, valor);
                    return isNaN(valor) ? 0 : Math.round(valor * 10) / 10; // Redondear a 1 decimal
                }
                
                // Compatibilidad con formato anterior (valor directo)
                if (indicador !== undefined && indicador !== null) {
                    const valor = parseFloat(indicador);
                    return isNaN(valor) ? 0 : Math.round(valor * 10) / 10;
                }
                
                console.warn(`⚠️ No se encontró valor para ${tipo}`);
                return 0;
            } catch (error) {
                console.error(`❌ Error en getValorIndicador(${tipo}):`, error);
                return 0;
            }
        },

        getTotalRespuestas(tipo) {
            try {
                if (!this.estadisticas || !this.estadisticas.nivelIndicador) {
                    return 0;
                }
                
                const indicador = this.estadisticas.nivelIndicador[tipo];
                if (typeof indicador === 'object' && indicador !== null) {
                    const total = indicador.totalRespuestas !== undefined && indicador.totalRespuestas !== null
                        ? parseInt(indicador.totalRespuestas)
                        : 0;
                    return isNaN(total) ? 0 : total;
                }
                return 0;
            } catch (error) {
                console.error(`❌ Error en getTotalRespuestas(${tipo}):`, error);
                return 0;
            }
        },

        getNPSCount(tipo) {
            try {
                if (!this.estadisticas || !this.estadisticas.distribucionNPS) {
                    return 0;
                }
                const data = this.estadisticas.distribucionNPS;
                return data[tipo] || 0;
            } catch (error) {
                return 0;
            }
        },

        getCSATDistribucion() {
            // Necesitamos obtener datos de CSAT: Muy Satisfechos (4), Satisfechos (3), y el resto
            // Por ahora retornamos datos dummy, necesitaríamos una API para obtener estos datos
            // O podemos calcularlos desde estadisticas si están disponibles
            const total = this.getTotalRespuestas('csat');
            const satisfechos = Math.round(total * (this.getValorIndicador('csat') / 100));
            const noSatisfechos = total - satisfechos;
            
            return {
                satisfechos: satisfechos,
                noSatisfechos: noSatisfechos,
                total: total
            };
        },

        getFCRDistribucion() {
            // FCR: Sí (valor_principal = 0) y No (valor_principal = 1)
            const total = this.getTotalRespuestas('fcr');
            const si = Math.round(total * (this.getValorIndicador('fcr') / 100));
            const no = total - si;
            
            return {
                si: si,
                no: no,
                total: total
            };
        },

        getNPSDistribucion() {
            // NPS: Promotores (9-10), Pasivos (7-8), Detractores (1-6)
            if (!this.estadisticas || !this.estadisticas.distribucionNPS) {
                return {
                    promotores: 0,
                    pasivos: 0,
                    detractores: 0,
                    total: 0
                };
            }
            const data = this.estadisticas.distribucionNPS;
            return {
                promotores: data.promotores || 0,
                pasivos: data.pasivos || 0,
                detractores: data.detractores || 0,
                total: data.total || 0
            };
        },

        getNPSPorcentaje(tipo) {
            try {
                if (!this.estadisticas || !this.estadisticas.distribucionNPS) {
                    return 0;
                }
                const data = this.estadisticas.distribucionNPS;
                const total = data.total || 0;
                if (total === 0) return 0;
                const count = data[tipo] || 0;
                return Math.round((count / total) * 100);
            } catch (error) {
                return 0;
            }
        },

        getTendenciaIndicador(tipo) {
            // TODO: Implementar cálculo de tendencia comparando con período anterior
            // Por ahora retornamos null para no mostrar tendencia
            return null;
        },


        getSemaforoPath(valor) {
            // Asegurar que el valor sea un número válido
            const numValor = typeof valor === 'number' ? valor : parseFloat(valor) || 0;
            
            // Crear un arco semicircular basado en el valor (0-100)
            // El arco va de izquierda (0%) a derecha (100%)
            const centerX = 100;
            const centerY = 100;
            const radius = 85;
            const startAngle = Math.PI; // 180 grados (izquierda)
            const endAngle = 0; // 0 grados (derecha)
            
            // Calcular el porcentaje del arco basado en el valor (0-100)
            // Asegurar que el valor esté entre 0 y 100
            const clampedValor = Math.min(100, Math.max(0, numValor));
            const percentage = clampedValor / 100; // 0-100 -> 0-1
            
            // Calcular el ángulo actual basado en el porcentaje
            const currentAngle = startAngle - (startAngle - endAngle) * percentage;
            
            // Punto de inicio (extremo izquierdo)
            const x1 = centerX + radius * Math.cos(startAngle);
            const y1 = centerY + radius * Math.sin(startAngle);
            
            // Punto final basado en el porcentaje
            const x2 = centerX + radius * Math.cos(currentAngle);
            const y2 = centerY + radius * Math.sin(currentAngle);
            
            // Determinar si necesitamos un arco grande o pequeño
            const largeArc = percentage > 0.5 ? 1 : 0;
            
            // Crear el path: arco desde izquierda hasta el punto actual, luego línea al centro
            return `M ${x1} ${y1} A ${radius} ${radius} 0 ${largeArc} 0 ${x2} ${y2} L ${centerX} ${centerY} Z`;
        },

        getSemaforoColor(valor) {
            // Asegurar que el valor sea un número válido
            const numValor = typeof valor === 'number' ? valor : parseFloat(valor) || 0;
            // Verde cuando >= 80% (buenas calificaciones)
            if (numValor >= 80) return '#10b981'; // Verde
            if (numValor >= 50) return '#f59e0b'; // Amarillo
            return '#ef4444'; // Rojo
        },

        renderizarGraficos() {
            this.destruirGraficos();
            
            // Polar Area Charts para indicadores
            if (this.mostrarIndicador('csat')) {
                this.renderizarPolarChart('csat');
            }
            if (this.mostrarIndicador('fcr')) {
                this.renderizarPolarChart('fcr');
            }
            if (this.mostrarIndicador('nps')) {
                this.renderizarPolarChart('nps');
            }
            
            // Gráfico de encuestas por área
            this.renderizarGraficoEncuestasArea();
            
            // Gráfico de distribución NPS
            if (this.mostrarIndicador('nps')) {
                this.renderizarGraficoDistribucionNPS();
            }
            
            // Gráficos de relación nivel vs encuestas
            this.tiposIndicadoresActivos.forEach(tipo => {
                this.renderizarGraficoRelacion(tipo);
            });
            
            // Gráficos de dimensiones
            this.tiposIndicadoresActivos.forEach(tipo => {
                this.renderizarGraficoDimensiones(tipo);
            });
        },

        renderizarPolarChart(tipo) {
            const refName = `polarChart${tipo.toUpperCase()}`;
            const canvas = this.$refs[refName];
            if (!canvas) {
                console.warn(`Canvas ${refName} no encontrado`);
                return;
            }

            // Verificar que el canvas tenga contexto
            const ctx = canvas.getContext('2d');
            if (!ctx) {
                console.warn(`No se pudo obtener contexto del canvas ${refName}`);
                return;
            }

            // Destruir gráfico anterior si existe
            const chartName = `polarChart${tipo.toUpperCase()}`;
            if (this[chartName]) {
                this[chartName].destroy();
                this[chartName] = null;
            }

            let polarData = [];
            let polarColors = [];
            let labels = [];
            let fullLabels = []; // Labels completos con toda la información para tooltips
            let total = 0;

            if (tipo === 'nps') {
                // NPS: Promotores (verde), Pasivos (amarillo), Detractores (rojo)
                const distribucion = this.getNPSDistribucion();
                total = distribucion.total || 0;
                
                if (total > 0) {
                    const promotores = distribucion.promotores || 0;
                    const pasivos = distribucion.pasivos || 0;
                    const detractores = distribucion.detractores || 0;
                    
                    const porcentajePromotores = Math.round((promotores / total) * 100);
                    const porcentajePasivos = Math.round((pasivos / total) * 100);
                    const porcentajeDetractores = Math.round((detractores / total) * 100);
                    
                    // Ordenar de mayor a menor para que el más grande sea verde
                    const segmentos = [
                        { valor: porcentajePromotores, color: '#10b981', label: `Promotores (9-10)\n${porcentajePromotores}%\n${promotores} respuestas`, count: promotores },
                        { valor: porcentajePasivos, color: '#f59e0b', label: `Pasivos (7-8)\n${porcentajePasivos}%\n${pasivos} respuestas`, count: pasivos },
                        { valor: porcentajeDetractores, color: '#ef4444', label: `Detractores (1-6)\n${porcentajeDetractores}%\n${detractores} respuestas`, count: detractores }
                    ];
                    
                    // Ordenar por valor (mayor a menor)
                    segmentos.sort((a, b) => b.valor - a.valor);
                    
                    polarData = segmentos.map(s => s.valor);
                    polarColors = segmentos.map(s => s.color);
                    labels = segmentos.map(s => s.label.split('\n')[0]); // Solo el título para labels
                    fullLabels = segmentos.map(s => s.label); // Labels completos para tooltips
                }
            } else if (tipo === 'csat') {
                // CSAT: Satisfechos y Muy Satisfechos (verde), resto (rojo/amarillo)
                const distribucion = this.getCSATDistribucion();
                total = distribucion.total || 0;
                
                if (total > 0) {
                    const satisfechos = distribucion.satisfechos || 0;
                    const noSatisfechos = distribucion.noSatisfechos || 0;
                    
                    const porcentajeSatisfechos = Math.round((satisfechos / total) * 100);
                    const porcentajeNoSatisfechos = Math.round((noSatisfechos / total) * 100);
                    
                    // Verde para satisfechos (siempre el más grande), rojo para no satisfechos
                    polarData = [porcentajeSatisfechos, porcentajeNoSatisfechos];
                    polarColors = ['#10b981', '#ef4444'];
                    fullLabels = [
                        `Satisfechos\n${porcentajeSatisfechos}%\n${satisfechos} respuestas`,
                        `No Satisfechos\n${porcentajeNoSatisfechos}%\n${noSatisfechos} respuestas`
                    ];
                    labels = fullLabels.map(l => l.split('\n')[0]);
                }
            } else if (tipo === 'fcr') {
                // FCR: Sí (verde), No (rojo)
                const distribucion = this.getFCRDistribucion();
                total = distribucion.total || 0;
                
                if (total > 0) {
                    const si = distribucion.si || 0;
                    const no = distribucion.no || 0;
                    
                    const porcentajeSi = Math.round((si / total) * 100);
                    const porcentajeNo = Math.round((no / total) * 100);
                    
                    // Verde para Sí (siempre el más grande si es > 80%), rojo para No
                    polarData = [porcentajeSi, porcentajeNo];
                    polarColors = porcentajeSi >= porcentajeNo ? ['#10b981', '#ef4444'] : ['#ef4444', '#10b981'];
                    fullLabels = [
                        `Sí\n${porcentajeSi}%\n${si} respuestas`,
                        `No\n${porcentajeNo}%\n${no} respuestas`
                    ];
                    labels = fullLabels.map(l => l.split('\n')[0]);
                }
            }

            // Si no hay datos, crear segmentos vacíos
            if (polarData.length === 0) {
                polarData = [0, 0, 0];
                polarColors = ['#10b981', '#f59e0b', '#ef4444'];
                labels = ['Sin datos', 'Sin datos', 'Sin datos'];
            }

            try {
                this[chartName] = new Chart(ctx, {
                    type: 'polarArea',
                    data: {
                        labels: labels.map(l => l.split('\n')[0]), // Solo el título para labels
                        datasets: [{
                            data: polarData,
                            backgroundColor: polarColors,
                            borderWidth: 2,
                            borderColor: '#ffffff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                                labels: {
                                    padding: 15,
                                    usePointStyle: true,
                                    font: {
                                        size: 12
                                    },
                                    generateLabels: (chart) => {
                                        const data = chart.data;
                                        if (data.labels.length && data.datasets.length) {
                                            return data.labels.map((label, i) => {
                                                const value = data.datasets[0].data[i];
                                                const color = data.datasets[0].backgroundColor[i];
                                                return {
                                                    text: `${label}: ${value}%`,
                                                    fillStyle: color,
                                                    strokeStyle: color,
                                                    lineWidth: 2,
                                                    hidden: false,
                                                    index: i
                                                };
                                            });
                                        }
                                        return [];
                                    }
                                }
                            },
                            tooltip: {
                                enabled: true,
                                callbacks: {
                                    label: (context) => {
                                        const index = context.dataIndex;
                                        if (fullLabels[index]) {
                                            return fullLabels[index].split('\n');
                                        }
                                        return context.label + ': ' + context.parsed.r.toFixed(1) + '%';
                                    }
                                }
                            }
                        },
                        animation: {
                            animateRotate: true,
                            animateScale: true
                        },
                        scales: {
                            r: {
                                beginAtZero: true,
                                max: 100,
                                display: true,
                                ticks: {
                                    stepSize: 10,
                                    font: {
                                        size: 10
                                    },
                                    color: '#6b7280'
                                },
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.1)'
                                },
                                pointLabels: {
                                    display: true,
                                    font: {
                                        size: 11
                                    },
                                    color: '#1f2937'
                                }
                            }
                        }
                    }
                });
            } catch (error) {
                console.error(`Error creando polar chart ${tipo}:`, error);
            }
        },

        renderizarGraficoEncuestasArea() {
            const ctx = this.$refs.encuestasAreaChart;
            if (!ctx) return;

            const data = this.estadisticas.encuestasPorArea || [];
            
            // Agrupar por área y tipo
            const areasMap = {};
            const tipos = ['csat', 'fcr', 'nps'];
            
            data.forEach(item => {
                if (!areasMap[item.area_nombre]) {
                    areasMap[item.area_nombre] = { csat: 0, fcr: 0, nps: 0 };
                }
                if (item.tipo_calificacion && areasMap[item.area_nombre][item.tipo_calificacion] !== undefined) {
                    areasMap[item.area_nombre][item.tipo_calificacion] = item.cantidad_encuestas;
                }
            });

            const labels = Object.keys(areasMap);
            const datasets = tipos
                .filter(tipo => this.mostrarIndicador(tipo))
                .map((tipo, index) => {
                    const colors = ['#4f46e5', '#10b981', '#f59e0b'];
                    return {
                        label: tipo.toUpperCase(),
                        data: labels.map(area => areasMap[area][tipo] || 0),
                        backgroundColor: colors[index],
                        borderColor: colors[index],
                        borderWidth: 1,
                        borderRadius: 4
                    };
                });
            
            this.encuestasAreaChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    }
                }
            });
        },

        renderizarGraficoDistribucionNPS() {
            const ctx = this.$refs.distribucionNPSChart;
            if (!ctx) return;

            const data = this.estadisticas.distribucionNPS || {};
            
            this.distribucionNPSChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Promotores (9-10)', 'Pasivos (7-8)', 'Detractores (1-6)'],
                    datasets: [{
                        data: [
                            data.promotores || 0,
                            data.pasivos || 0,
                            data.detractores || 0
                        ],
                        backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? Math.round((context.raw / total) * 100) : 0;
                                    return `${context.label}: ${context.raw} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        },

        renderizarGraficoRelacion(tipo) {
            const refName = `relacionChart_${tipo}`;
            const refs = this.$refs[refName];
            if (!refs) return;
            
            const ctx = Array.isArray(refs) ? refs[0] : refs;
            if (!ctx) return;

            const data = this.estadisticas.relacionNivelEncuestas?.[tipo] || [];
            
            // Destruir gráfico anterior si existe
            if (this.relacionChartRefs[`_chart_${tipo}`]) {
                this.relacionChartRefs[`_chart_${tipo}`].destroy();
            }

            const labels = data.map(item => this.formatearFecha(item.fecha));
            const cantidadEncuestas = data.map(item => item.cantidad_encuestas || 0);
            const porcentajes = data.map(item => item.porcentaje || 0);

            this.relacionChartRefs[`_chart_${tipo}`] = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Cantidad de Encuestas',
                            data: cantidadEncuestas,
                            borderColor: '#4f46e5',
                            backgroundColor: 'rgba(79, 70, 229, 0.1)',
                            borderWidth: 2,
                            yAxisID: 'y',
                            tension: 0.4
                        },
                        {
                            label: `Porcentaje de ${tipo.toUpperCase()} (%)`,
                            data: porcentajes,
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            borderWidth: 2,
                            yAxisID: 'y1',
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Cantidad de Encuestas'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            beginAtZero: true,
                            max: 100,
                            title: {
                                display: true,
                                text: 'Porcentaje (%)'
                            },
                            grid: {
                                drawOnChartArea: false,
                            },
                        }
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    }
                }
            });
        },

        renderizarGraficoDimensiones(tipo) {
            const refName = `dimensionesChart_${tipo}`;
            const refs = this.$refs[refName];
            if (!refs) return;
            
            const ctx = Array.isArray(refs) ? refs[0] : refs;
            if (!ctx) return;

            const dimensiones = this.estadisticas.indicadoresDimensiones?.[tipo] || [];
            if (dimensiones.length === 0) return;

            // Destruir gráfico anterior si existe
            if (this.dimensionesChartRefs[`_chart_${tipo}`]) {
                this.dimensionesChartRefs[`_chart_${tipo}`].destroy();
            }

            // Preparar datos para el gráfico
            const labels = [];
            const dataValues = [];

            dimensiones.forEach(dim => {
                if (dim.tipo === 'opcion_unica' && dim.respuestas) {
                    dim.respuestas.forEach(resp => {
                        labels.push(`${dim.dimension} - ${resp.opcion_seleccionada}`);
                        dataValues.push(resp.cantidad);
                    });
                } else {
                    labels.push(dim.dimension);
                    dataValues.push(dim.total || 0);
                }
            });

            this.dimensionesChartRefs[`_chart_${tipo}`] = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Cantidad de Respuestas',
                        data: dataValues,
                        backgroundColor: '#8b5cf6',
                        borderColor: '#7c3aed',
                        borderWidth: 1,
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    indexAxis: 'y',
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        },

        formatearFecha(fecha) {
            if (!fecha) return '';
            // Si es formato YYYY-MM, formatear como "Oct 2024"
            if (fecha.match(/^\d{4}-\d{2}$/)) {
                const [year, month] = fecha.split('-');
                const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
                return `${meses[parseInt(month) - 1]} ${year}`;
            }
            // Si es formato YYYY-MM-DD, formatear como "1 Oct"
            if (fecha.match(/^\d{4}-\d{2}-\d{2}$/)) {
                const date = new Date(fecha);
                const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
                return `${date.getDate()} ${meses[date.getMonth()]}`;
            }
            return fecha;
        },

        destruirGraficos() {
            if (this.encuestasAreaChart) {
                this.encuestasAreaChart.destroy();
                this.encuestasAreaChart = null;
            }
            if (this.distribucionNPSChart) {
                this.distribucionNPSChart.destroy();
                this.distribucionNPSChart = null;
            }
            if (this.polarChartCSAT) {
                this.polarChartCSAT.destroy();
                this.polarChartCSAT = null;
            }
            if (this.polarChartFCR) {
                this.polarChartFCR.destroy();
                this.polarChartFCR = null;
            }
            if (this.polarChartNPS) {
                this.polarChartNPS.destroy();
                this.polarChartNPS = null;
            }
            
            // Destruir gráficos de relación
            Object.keys(this.relacionChartRefs).forEach(key => {
                if (this.relacionChartRefs[key]) {
                    this.relacionChartRefs[key].destroy();
                }
            });
            this.relacionChartRefs = {};
            
            // Destruir gráficos de dimensiones
            Object.keys(this.dimensionesChartRefs).forEach(key => {
                if (this.dimensionesChartRefs[key]) {
                    this.dimensionesChartRefs[key].destroy();
                }
            });
            this.dimensionesChartRefs = {};
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

.chart-container-full-width {
    width: 100%;
}

.chart-container-full-width canvas {
    width: 100% !important;
    max-width: 100%;
}

/* Gauge Charts */
.gauge-container {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 300px;
    padding: 1rem;
    position: relative;
}

.gauge-wrapper {
    position: relative;
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.gauge-svg {
    width: 100%;
    max-width: 300px;
    height: auto;
    display: block;
}

.gauge-needle {
    transition: transform 0.5s ease;
}

.gauge-value-container {
    position: absolute;
    top: 10px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 10;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.25rem;
}

.gauge-value {
    font-size: 3rem;
    font-weight: 700;
    color: #1f2937;
    line-height: 1;
    text-shadow: 0 2px 4px rgba(255, 255, 255, 0.8);
}

.gauge-tendencia {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.875rem;
    font-weight: 600;
    margin-top: 0.25rem;
}

.gauge-tendencia.tendencia-up {
    color: #10b981;
}

.gauge-tendencia.tendencia-down {
    color: #ef4444;
}

.gauge-tendencia i {
    font-size: 0.75rem;
}

.gauge-respuestas {
    margin-top: 1rem;
    font-size: 0.9rem;
    color: #6b7280;
    font-weight: 500;
    text-align: center;
}

.gauge-labels {
    position: absolute;
    bottom: 10px;
    left: 0;
    right: 0;
    display: flex;
    justify-content: space-between;
    padding: 0 10px;
    font-size: 0.75rem;
    color: #6b7280;
    font-weight: 500;
    z-index: 5;
}

.gauge-label-left {
    margin-left: 0;
}

.gauge-label-center-left {
    margin-left: calc(25% - 10px);
}

.gauge-label-center-right {
    margin-right: calc(25% - 10px);
}

.gauge-label-right {
    margin-right: 0;
}

/* Semáforo Semicircular (mantener para compatibilidad) */
.semaforo-container {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 300px;
    padding: 1rem;
}

.semaforo-semicircular-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
    width: 100%;
}

.semaforo-semicircular {
    position: relative;
    width: 200px;
    height: 100px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.semaforo-svg {
    width: 100%;
    height: 100%;
    overflow: visible;
}

.semaforo-valor {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -20%);
    font-size: 3rem;
    font-weight: 700;
    color: #1f2937;
    z-index: 10;
    text-shadow: 0 2px 4px rgba(255, 255, 255, 0.8);
}

.semaforo-valor-container {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -30%);
    z-index: 10;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.25rem;
}

.semaforo-valor-container .semaforo-valor {
    position: static;
    transform: none;
    font-size: 3rem;
    font-weight: 700;
    color: #1f2937;
    line-height: 1;
    text-shadow: 0 2px 4px rgba(255, 255, 255, 0.8);
}

.semaforo-tendencia {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.875rem;
    font-weight: 600;
    margin-top: 0.25rem;
}

.semaforo-tendencia.tendencia-up {
    color: #10b981;
}

.semaforo-tendencia.tendencia-down {
    color: #ef4444;
}

.semaforo-tendencia i {
    font-size: 0.75rem;
}

.semaforo-respuestas {
    font-size: 0.9rem;
    color: #6b7280;
    font-weight: 500;
    text-align: center;
}

/* Botones de Selección de Indicador */
.indicador-selector {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    margin: 2rem 0;
    flex-wrap: wrap;
}

.btn-indicador {
    padding: 0.75rem 1.5rem;
    border: 2px solid #e5e7eb;
    background: white;
    color: #6b7280;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 0.9rem;
}

.btn-indicador:hover {
    background: #f3f4f6;
    border-color: #4f46e5;
    color: #4f46e5;
}

.btn-indicador.active {
    background: #4f46e5;
    color: white;
    border-color: #4f46e5;
    box-shadow: 0 4px 6px rgba(79, 70, 229, 0.3);
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

/* NPS Distribution Cards */
.nps-distribution-cards {
    width: -webkit-fill-available;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: auto;
    padding-top: 1rem;
}

.nps-card {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    border: 2px solid #e5e7eb;
    transition: all 0.3s ease;
}

.nps-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.nps-card.promotores {
    border-color: #10b981;
    background: linear-gradient(135deg, #ffffff 0%, #f0fdf4 100%);
}

.nps-card.pasivos {
    border-color: #f59e0b;
    background: linear-gradient(135deg, #ffffff 0%, #fffbeb 100%);
}

.nps-card.detractores {
    border-color: #ef4444;
    background: linear-gradient(135deg, #ffffff 0%, #fef2f2 100%);
}

.nps-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.nps-label {
    font-weight: 600;
    font-size: 1rem;
    color: #1f2937;
}

.nps-card.promotores .nps-label {
    color: #059669;
}

.nps-card.pasivos .nps-label {
    color: #d97706;
}

.nps-card.detractores .nps-label {
    color: #dc2626;
}

.nps-range {
    font-size: 0.875rem;
    color: #6b7280;
    font-weight: 500;
}

.nps-percentage {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.75rem;
}

.nps-card.promotores .nps-percentage {
    color: #059669;
}

.nps-card.pasivos .nps-percentage {
    color: #d97706;
}

.nps-card.detractores .nps-percentage {
    color: #dc2626;
}

.nps-bar {
    width: 100%;
    height: 8px;
    background: #e5e7eb;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 0.75rem;
}

.nps-bar-fill {
    height: 100%;
    border-radius: 4px;
    transition: width 0.5s ease;
}

.nps-bar-fill.promotores {
    background: #10b981;
}

.nps-bar-fill.pasivos {
    background: #f59e0b;
}

.nps-bar-fill.detractores {
    background: #ef4444;
}

.nps-count {
    font-size: 0.875rem;
    color: #6b7280;
    font-weight: 500;
}

/* Polar Charts Section */
.polar-charts-section {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 2rem;
    margin-bottom: 2rem;
}

.polar-chart-item {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

.polar-chart-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 0.5rem;
}

.polar-chart-subtitle {
    font-size: 0.875rem;
    color: #6b7280;
    margin-bottom: 1rem;
}

.polar-chart-canvas-wrapper {
    width: 100%;
    height: 250px;
    position: relative;
    margin: 1rem 0;
}

.polar-chart-canvas-wrapper canvas {
    max-width: 100%;
    max-height: 100%;
}

.polar-chart-value {
    font-size: 2rem;
    font-weight: 700;
    color: #4f46e5;
    margin: 1rem 0 0.5rem;
}

.polar-chart-responses {
    font-size: 0.875rem;
    color: #6b7280;
    margin-top: 0.5rem;
}

@media (max-width: 768px) {
    .polar-charts-section {
        grid-template-columns: 1fr;
    }
    
    .polar-chart-canvas-wrapper {
        height: 200px;
    }
}
</style>