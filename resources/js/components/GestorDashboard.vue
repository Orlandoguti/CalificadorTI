<template>
    <div class="admin-dashboard">
        <header class="admin-header">
            <div class="header-content">
                <div class="logo">
                    <i class="fas fa-university"></i>
                    <h1>UNIFRANZ</h1>
                    <span v-if="sedeNombre && sedeNombre !== 'Cargando...' && sedeNombre !== 'Sede no asignada'" class="sede-badge">{{ sedeNombre }}</span>
                    <span v-else class="sede-badge" style="background: #ef4444;">Sede no asignada</span>
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
                                <div class="role-badge gestor">Gestor</div>
                                <div v-if="sedeNombre && sedeNombre !== 'Cargando...' && sedeNombre !== 'Sede no asignada'" class="sede-info-dropdown">
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
                                    <!-- Solo mostrar "Todas" si NO tiene áreas asignadas específicas -->
                                    <option v-if="!tieneAreasAsignadasEspecificas" value="">Todas</option>
                                    <option v-for="area in areasAgrupadas" :key="area.id" :value="area.id">
                                        {{ area.nombre }}
                                    </option>
                                </select>
                            </div>
                            <div class="filter-actions" style="justify-self: end;">
                                <button @click="cargarEstadisticas" class="btn-primary">
                                    <i class="fas fa-sync-alt"></i> Actualizar
                                </button>
                                <button @click="exportarCalificaciones" class="btn-secondary" :disabled="exportandoCalificaciones">
                                    <i class="fas fa-download"></i> {{ exportandoCalificaciones ? 'Exportando...' : 'Exportar Calificaciones' }}
                                </button>
                            </div>
                        </div>
                    </div>


                    <!-- Botones de Selección de Indicador -->
                    <div class="indicador-selector">
                        <!-- Mostrar "Todos" si hay 2 o más indicadores disponibles -->
                        <button 
                            v-if="indicadoresDisponibles.length >= 2"
                            @click="filters.tipoCalificacion = ''; cargarEstadisticas()"
                            :class="['btn-indicador', { active: !filters.tipoCalificacion }]"
                        >
                            Todos
                        </button>
                        <button 
                            v-if="indicadoresDisponibles.includes('csat')"
                            @click="filters.tipoCalificacion = 'csat'; cargarEstadisticas()"
                            :class="['btn-indicador', { active: filters.tipoCalificacion === 'csat' }]"
                        >
                            CSAT
                        </button>
                        <button 
                            v-if="indicadoresDisponibles.includes('fcr')"
                            @click="filters.tipoCalificacion = 'fcr'; cargarEstadisticas()"
                            :class="['btn-indicador', { active: filters.tipoCalificacion === 'fcr' }]"
                        >
                            FCR
                        </button>
                        <!--
                        <button 
                            v-if="indicadoresDisponibles.includes('nps')"
                            @click="filters.tipoCalificacion = 'nps'; cargarEstadisticas()"
                            :class="['btn-indicador', { active: filters.tipoCalificacion === 'nps' }]"
                        >
                            NPS
                        </button>
                        -->
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
                    <div class="section-header">
                        <h2>Indicadores de Desempeño</h2>
                        <p>Estado actual basado en niveles de satisfacción</p>
                            </div>
                    <div class="polar-charts-section">
                        
                        <div class="polar-chart-item" v-if="mostrarIndicador('csat')">                            
                            <div class="indicador-header">
                                    <div class="indicador-icon">
                                        <i class="fas fa-smile"></i>
                            </div>
                                    <div class="indicador-titulo">
                                        <h3>CSAT</h3>
                                        <p>Satisfacción del Cliente</p>
                                    </div>
                                </div>
                            <div class="polar-chart-canvas-wrapper">
                                <canvas ref="polarChartCSAT"></canvas>
                            </div>
                           <!-- <div class="polar-chart-value">{{ getValorIndicador('csat') }}%</div>-->                            
                        <!-- Indicador CSAT -->
                            <div class="estado-indicador-card" v-if="mostrarIndicador('csat')">                                
                                <div class="indicador-content">
                                    <div class="indicador-valor-container">
                                        <div class="indicador-valor" :class="getClaseValor('csat')">
                                            {{ getValorIndicador('csat') }}%
                                        </div>
                                        <div class="indicador-estado" :class="getClaseEstado('csat')">
                                            <i :class="getIconoEstado('csat')"></i>
                                            {{ getTextoEstado('csat') }}
                            </div>
                        </div>

                                    <div class="indicador-progress">
                                        <div class="progress-labels">
                                            <span>Crítico</span>
                                            <span>Regular</span>
                                            <span>Óptimo</span>
                            </div>
                                        <div class="progress-bar">
                                            <div class="progress-segment segment-rojo" :class="{ active: getEstadoSemaforo('csat') === 'rojo' }"></div>
                                            <div class="progress-segment segment-amarillo" :class="{ active: getEstadoSemaforo('csat') === 'amarillo' }"></div>
                                            <div class="progress-segment segment-verde" :class="{ active: getEstadoSemaforo('csat') === 'verde' }"></div>
                                            <div class="progress-indicator" :style="getPosicionIndicador('csat')">
                                                <div class="indicator-dot"></div>
                            </div>
                        </div>
                                        <div class="progress-ranges">
                                            <span>&lt;40%</span>
                                            <span>40-64%</span>
                                            <span>65%+</span>
                                        </div>
                                        <div class="polar-chart-responses">Nº de Respuestas: {{ getTotalRespuestas('csat') }}</div>
                                    </div>
                                </div>
                    </div>
                </div>

                        <div class="polar-chart-item" v-if="mostrarIndicador('fcr')">                            
                            <div class="indicador-header">
                                    <div class="indicador-icon">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <div class="indicador-titulo">
                                        <h3>FCR</h3>
                                        <p>Resolución en Primer Contacto</p>
                                    </div>
                                </div>
                            <div class="polar-chart-canvas-wrapper">
                                <canvas ref="polarChartFCR"></canvas>
                            </div>
                            <!--<div class="polar-chart-value">{{ getValorIndicador('fcr') }}%</div>-->                            
                            <!-- Indicador FCR -->
                            <div class="estado-indicador-card" v-if="mostrarIndicador('fcr')">
                                
                                <div class="indicador-content">
                                    <div class="indicador-valor-container">
                                        <div class="indicador-valor" :class="getClaseValor('fcr')">
                                            {{ getValorIndicador('fcr') }}%
                                        </div>
                                        <div class="indicador-estado" :class="getClaseEstado('fcr')">
                                            <i :class="getIconoEstado('fcr')"></i>
                                            {{ getTextoEstado('fcr') }}
                                        </div>
                    </div>

                                    <div class="indicador-progress">
                                        <div class="progress-labels">
                                            <span>Crítico</span>
                                            <span>Regular</span>
                                            <span>Óptimo</span>
                        </div>
                                        <div class="progress-bar">
                                            <div class="progress-segment segment-rojo" :class="{ active: getEstadoSemaforo('fcr') === 'rojo' }"></div>
                                            <div class="progress-segment segment-amarillo" :class="{ active: getEstadoSemaforo('fcr') === 'amarillo' }"></div>
                                            <div class="progress-segment segment-verde" :class="{ active: getEstadoSemaforo('fcr') === 'verde' }"></div>
                                            <div class="progress-indicator" :style="getPosicionIndicador('fcr')">
                                                <div class="indicator-dot"></div>
                                            </div>
                                        </div>
                                        <div class="progress-ranges">
                                            <span>&lt;50%</span>
                                            <span>50-69%</span>
                                            <span>70%+</span>
                                        </div>
                                        <div class="polar-chart-responses">Nº de Respuestas: {{ getTotalRespuestas('fcr') }}</div>
                                    </div>     
                        </div>
                    </div>
                </div>

                        <!--
                        <div class="polar-chart-item" v-if="mostrarIndicador('nps')">
                            <div class="polar-chart-title">NPS</div>
                            <div class="polar-chart-subtitle">Net Promoter Score</div>
                            <div class="polar-chart-canvas-wrapper">
                                <canvas ref="polarChartNPS"></canvas>
                    </div>
                            <div class="polar-chart-value">{{ getValorIndicador('nps') }}%</div>
                            <div class="polar-chart-responses">Nº de Respuestas: {{ getTotalRespuestas('nps') }}</div>
                        </div>
                        -->
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

                    <!-- Distribución NPS (COMENTADA) -->
                    <!--
                    <div class="chart-card">
                        <div class="chart-header" style="justify-items: center;">
                            <h3>Distribución NPS</h3>
                            <p>Promotores, Pasivos y Detractores</p>
                        </div>

                        <div class="chart-content-flex" style="display: flex;">
                            <div class="chart-container">
                                <canvas ref="distribucionNPSChart"></canvas>
                            </div>

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
                    -->

					<!-- Cantidad de encuestas por día por tipo (CSAT, NPS, FCR) 
					<div class="charts-grid">
						<div class="chart-card full-width">
							<div class="chart-header" style="justify-items: center;">
								<h3>CSAT, NPS y FCR por día</h3>
							</div>
							<div class="chart-container chart-container-full-width">
								<canvas ref="encuestasPorDiaTiposChart"></canvas>
							</div>
						</div>
                        
					</div>-->
                    
					<!-- CSAT: Tablas y gráficos por nivel de calificación -->
					<div v-if="mostrarIndicador('csat')" class="csat-niveles-section">
						<h2 class="section-title">CSAT - Análisis por Nivel de Calificación</h2>
						
						<div class="csat-niveles-grid">
							<!-- Muy Satisfechos (Nivel 1) -->
							<div class="csat-nivel-card">
								<div class="csat-nivel-header nivel-1">
									<h3>Muy Satisfechos</h3>
								</div>
								<div class="csat-nivel-content">
									<div class="chart-container-small">
										<canvas :ref="'csatNivel1Chart'"></canvas>
									</div>
									<div class="table-container-small">
										<table class="data-table-small">
											<thead>
												<tr>
													<th>CSAT</th>
													<th v-for="dimension in getDimensionesUnicasCSAT(1)" :key="dimension">
														{{ dimension }}
													</th>
												</tr>
											</thead>
											<tbody>
												<tr>
													<td class="nivel-label nivel-1">Muy Satisfechos</td>
													<td v-for="dimension in getDimensionesUnicasCSAT(1)" :key="dimension">
														{{ getCantidadDimensionCSAT(1, dimension) }}
													</td>
												</tr>
											</tbody>
										</table>
									</div>
								</div>
							</div>

							<!-- Satisfechos (Nivel 2) -->
							<div class="csat-nivel-card">
								<div class="csat-nivel-header nivel-2">
									<h3>Satisfechos</h3>
								</div>
								<div class="csat-nivel-content">
									<div class="chart-container-small">
										<canvas :ref="'csatNivel2Chart'"></canvas>
									</div>
									<div class="table-container-small">
										<table class="data-table-small">
											<thead>
												<tr>
													<th>CSAT</th>
													<th v-for="dimension in getDimensionesUnicasCSAT(2)" :key="dimension">
														{{ dimension }}
													</th>
												</tr>
											</thead>
											<tbody>
												<tr>
													<td class="nivel-label nivel-2">Satisfechos</td>
													<td v-for="dimension in getDimensionesUnicasCSAT(2)" :key="dimension">
														{{ getCantidadDimensionCSAT(2, dimension) }}
													</td>
												</tr>
											</tbody>
										</table>
									</div>
								</div>
							</div>

							<!-- Insatisfechos (Nivel 3) -->
							<div class="csat-nivel-card">
								<div class="csat-nivel-header nivel-3">
									<h3>Insatisfechos</h3>
								</div>
								<div class="csat-nivel-content">
									<div class="chart-container-small">
										<canvas :ref="'csatNivel3Chart'"></canvas>
									</div>
									<div class="table-container-small">
										<table class="data-table-small">
											<thead>
												<tr>
													<th>CSAT</th>
													<th v-for="dimension in getDimensionesUnicasCSAT(3)" :key="dimension">
														{{ dimension }}
													</th>
												</tr>
											</thead>
											<tbody>
												<tr>
													<td class="nivel-label nivel-3">Insatisfechos</td>
													<td v-for="dimension in getDimensionesUnicasCSAT(3)" :key="dimension">
														{{ getCantidadDimensionCSAT(3, dimension) }}
													</td>
												</tr>
											</tbody>
										</table>
									</div>
								</div>
							</div>

							<!-- Muy Insatisfechos (Nivel 4) -->
							<div class="csat-nivel-card">
								<div class="csat-nivel-header nivel-4">
									<h3>Muy Insatisfechos</h3>
								</div>
								<div class="csat-nivel-content">
									<div class="chart-container-small">
										<canvas :ref="'csatNivel4Chart'"></canvas>
									</div>
									<div class="table-container-small">
										<table class="data-table-small">
											<thead>
												<tr>
													<th>CSAT</th>
													<th v-for="dimension in getDimensionesUnicasCSAT(4)" :key="dimension">
														{{ dimension }}
													</th>
												</tr>
											</thead>
											<tbody>
												<tr>
													<td class="nivel-label nivel-4">Muy Insatisfechos</td>
													<td v-for="dimension in getDimensionesUnicasCSAT(4)" :key="dimension">
														{{ getCantidadDimensionCSAT(4, dimension) }}
													</td>
												</tr>
											</tbody>
										</table>
									</div>
								</div>
							</div>
						</div>
					</div>

					<!-- Tabla: últimos 5 días con distribución por tipo -->
					<div class="table-card">
						<div class="table-header" style="justify-items: center;">
							<h3>Últimos 5 días - Cantidad y distribución por tipo</h3>
						</div>
						
						<!-- Gráfico combinado: Barras para total y líneas para CSAT, NPS, FCR -->
						<div class="chart-container" style="margin-bottom: 1.5rem;">
							<canvas ref="ultimos5DiasComboChart"></canvas>
						</div>
						
						<div class="table-container" >
							<table class="data-table" style="text-align-last: center;">
								<thead>
									<tr>
										<th>Fecha</th>
										<th>CSAT</th>
										<th>% CSAT</th>
										<th>FCR</th>
										<th>% FCR</th>
										<!--
										<th>NPS</th>
										<th>% NPS</th>
										-->
										<th>Total día</th>
									</tr>
								</thead>
								<tbody>
									<tr v-for="row in tablaUltimosDias" :key="row.fecha">
										<td>{{ formatearFecha(row.fecha) }}</td>
										<td>{{ row.csat }}</td>
										<td>{{ row.csatPorcentaje || 0 }}%</td>
										<td>{{ row.fcr }}</td>
										<td>{{ row.fcrPorcentaje || 0 }}%</td>
										<!--
										<td>{{ row.nps }}</td>
										<td>{{ row.npsPorcentaje || 0 }}%</td>
										-->
										<td>{{ row.total }}</td>
									</tr>
									<tr v-if="tablaUltimosDias.length === 0">
										<td colspan="6">Sin datos para el rango seleccionado</td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>

					<!-- Ranking de Áreas: Satisfacción e Insatisfacción -->
					<!-- Solo mostrar ranking si NO tiene áreas asignadas específicas -->
					<div v-if="!tieneAreasAsignadasEspecificas" class="chart-card full-width" style="margin-top: 2rem;">
						<div class="chart-header" style="justify-items: center;">
							<h3>Ranking de Áreas</h3>
							<p>Áreas más valoradas y menos valoradas según las calificaciones recibidas</p>
						</div>
						<div class="ranking-areas-dual-container">
							<!-- Ranking por Satisfacción -->
							<div class="ranking-section center-text">
								<h4 class="ranking-section-title satisfaccion-title" style="justify-content: center;">
									<i class="fas fa-thumbs-up"></i>
									Satisfacción
								</h4>
								<div class="ranking-areas-container">
									<div 
										v-for="(area, index) in rankingAreas" 
										:key="'satisfaccion-' + area.area_id" 
										class="ranking-area-item"
										:class="{ 'top-1': index === 0, 'top-2': index === 1, 'top-3': index === 2 }"
									>
										<div class="ranking-position">
											<span class="position-number">#{{ index + 1 }}</span>
										</div>
										<div class="ranking-area-info">
											<h4 class="area-nombre">{{ area.area_nombre }}</h4>
											<div class="estrellas-container">
												<span 
													v-for="n in 5" 
													:key="n"
													class="estrella"
													:class="{
														'completa': n <= Math.floor(area.promedio_estrellas),
														'media': n === Math.ceil(area.promedio_estrellas) && area.promedio_estrellas % 1 >= 0.5 && area.promedio_estrellas % 1 < 1,
														'vacia': n > Math.ceil(area.promedio_estrellas) || (n === Math.ceil(area.promedio_estrellas) && area.promedio_estrellas % 1 < 0.5)
													}"
												>
													★
												</span>
												<span class="promedio-texto">{{ area.promedio_estrellas.toFixed(1) }}</span>
											</div>
											<div class="ranking-stats">
												<span class="stat-item">
													<i class="fas fa-chart-bar"></i>
													{{ area.total_calificaciones }} calificaciones
												</span>
											</div>
										</div>
									</div>
									<div v-if="rankingAreas.length === 0" class="no-data-message">
										<p>No hay datos de calificaciones</p>
									</div>
								</div>
							</div>

							<!-- Ranking por Insatisfacción -->
							<div class="ranking-section">
								<h4 class="ranking-section-title insatisfaccion-title" style="justify-content: center;">
									<i class="fas fa-thumbs-down"></i>
									Insatisfacción
								</h4>
								<div class="ranking-areas-container">
									<div 
										v-for="(area, index) in rankingAreasInsatisfaccion" 
										:key="'insatisfaccion-' + area.area_id" 
										class="ranking-area-item insatisfaccion-item"
										:class="{ 'worst-1': index === 0, 'worst-2': index === 1, 'worst-3': index === 2 }"
									>
										<div class="ranking-position">
											<span class="position-number">#{{ index + 1 }}</span>
										</div>
										<div class="ranking-area-info">
											<h4 class="area-nombre">{{ area.area_nombre }}</h4>
											<div class="estrellas-container">
												<span 
													v-for="n in 5" 
													:key="n"
													class="estrella"
													:class="{
														'completa': n <= Math.floor(area.promedio_estrellas),
														'media': n === Math.ceil(area.promedio_estrellas) && area.promedio_estrellas % 1 >= 0.5 && area.promedio_estrellas % 1 < 1,
														'vacia': n > Math.ceil(area.promedio_estrellas) || (n === Math.ceil(area.promedio_estrellas) && area.promedio_estrellas % 1 < 0.5)
													}"
												>
													★
												</span>
												<span class="promedio-texto">{{ area.promedio_estrellas.toFixed(1) }}</span>
											</div>
											<div class="ranking-stats">
												<span class="stat-item">
													<i class="fas fa-chart-bar"></i>
													{{ area.total_calificaciones }} calificaciones
												</span>
											</div>
										</div>
									</div>
									<div v-if="rankingAreasInsatisfaccion.length === 0" class="no-data-message">
										<p>No hay datos de calificaciones</p>
									</div>
								</div>
							</div>
						</div>
					</div>

					<!-- Textos más anotados en "Otros" (filtrado por sede del gestor vía API) -->
					<div class="charts-grid" style="margin-top: 2rem;">
						<div class="chart-card">
							<div class="chart-header">
								<h3>Textos más anotados en "Otros" - CSAT</h3>
								<p>Respuestas de texto libre más frecuentes (tu sede)</p>
							</div>
							<div class="chart-container">
								<canvas ref="textosCSATChart"></canvas>
							</div>
							<div class="table-container" style="margin-top: 1rem; max-height: 400px; overflow-y: auto;">
								<table class="data-table">
									<thead>
										<tr>
											<th>#</th>
											<th>Texto</th>
											<th>Cantidad</th>
											<th>Última respuesta</th>
										</tr>
									</thead>
									<tbody>
										<tr v-for="(item, idx) in (estadisticas?.textosMasAnotados?.csat || [])" :key="'csat-' + idx">
											<td>{{ idx + 1 }}</td>
											<td style="text-align: left; max-width: 400px; word-wrap: break-word;">{{ item.texto }}</td>
											<td>{{ item.cantidad }}</td>
											<td style="white-space: nowrap;">{{ formatearFechaHora(item.ultima_fecha) }}</td>
										</tr>
										<tr v-if="!estadisticas?.textosMasAnotados?.csat || estadisticas.textosMasAnotados.csat.length === 0">
											<td colspan="4">No hay textos registrados</td>
										</tr>
									</tbody>
								</table>
							</div>
						</div>

						<div class="chart-card">
							<div class="chart-header">
								<h3>Textos más anotados en "Otros" - FCR</h3>
								<p>Respuestas de texto libre más frecuentes (tu sede)</p>
							</div>
							<div class="chart-container">
								<canvas ref="textosFCRChart"></canvas>
							</div>
							<div class="table-container" style="margin-top: 1rem; max-height: 400px; overflow-y: auto;">
								<table class="data-table">
									<thead>
										<tr>
											<th>#</th>
											<th>Texto</th>
											<th>Cantidad</th>
											<th>Última respuesta</th>
										</tr>
									</thead>
									<tbody>
										<tr v-for="(item, idx) in (estadisticas?.textosMasAnotados?.fcr || [])" :key="'fcr-' + idx">
											<td>{{ idx + 1 }}</td>
											<td style="text-align: left; max-width: 400px; word-wrap: break-word;">{{ item.texto }}</td>
											<td>{{ item.cantidad }}</td>
											<td style="white-space: nowrap;">{{ formatearFechaHora(item.ultima_fecha) }}</td>
										</tr>
										<tr v-if="!estadisticas?.textosMasAnotados?.fcr || estadisticas.textosMasAnotados.fcr.length === 0">
											<td colspan="4">No hay textos registrados</td>
										</tr>
									</tbody>
								</table>
							</div>
						</div>
					</div>

					<div class="charts-grid" style="margin-top: 2rem;">
						<div class="chart-card full-width">
							<div class="chart-header">
								<h3>Textos anotados en "Otros" por Área</h3>
								<p>Respuestas de texto libre agrupadas por área y tipo de calificación (tu sede)</p>
							</div>
							<div class="table-container" style="margin-top: 1rem; max-height: 600px; overflow-y: auto;">
								<table class="data-table">
									<thead>
										<tr>
											<th>Área</th>
											<th>Tipo</th>
											<th>Texto</th>
											<th>Cantidad</th>
											<th>Última respuesta</th>
										</tr>
									</thead>
									<tbody>
										<template v-for="(textos, areaNombre) in (estadisticas?.textosMasAnotados?.porArea || {})" :key="areaNombre">
											<tr v-for="(item, idx) in textos" :key="`${areaNombre}-${item.tipo}-${idx}`">
												<td v-if="idx === 0" :rowspan="textos.length" style="vertical-align: top; font-weight: 600;">{{ areaNombre }}</td>
												<td>
													<span :class="item.tipo === 'csat' ? 'badge-csat' : 'badge-fcr'">
														{{ item.tipo.toUpperCase() }}
													</span>
												</td>
												<td style="text-align: left; max-width: 500px; word-wrap: break-word;">{{ item.texto }}</td>
												<td>{{ item.cantidad }}</td>
												<td style="white-space: nowrap;">{{ formatearFechaHora(item.ultima_fecha) }}</td>
											</tr>
										</template>
										<tr v-if="!estadisticas?.textosMasAnotados?.porArea || Object.keys(estadisticas.textosMasAnotados.porArea).length === 0">
											<td colspan="5">No hay textos registrados</td>
										</tr>
									</tbody>
								</table>
							</div>
						</div>
					</div>
                </div>               

					<!-- Top 10 FCR - Dimensiones más respondidas -->
					<!-- Top 10 combinado (CSAT, FCR, NPS) - Horizontal + Tabla 
					<div class="charts-grid" v-if="activeTab === 'dashboard'" style="margin-top: 1rem;">
						<div class="chart-card full-width">
							<div class="chart-header" style="justify-items: center;">
								<h3>Top 10 - Preguntas más respondidas</h3>
								<p>CSAT y FCR combinados</p>
							</div>
							<div class="chart-container chart-container-full-width">
								<canvas ref="top10AllDimChart"></canvas>
							</div>

							<div class="table-container" style="margin-top: 1rem;">
								<table class="data-table" style="text-align-last: center;">
									<thead>
										<tr>
											<th>#</th>
											<th>Tipo</th>
											<th>Pregunta / Dimensión</th>
											<th>Cantidad</th>
										</tr>
									</thead>
									<tbody>
										<tr v-for="(item, idx) in getTop10DimensionesAll()" :key="item.tipo + item.dimension + idx">
											<td>{{ idx + 1 }}</td>
											<td>{{ item.tipo.toUpperCase() }}</td>
											<td>{{ item.dimension }}</td>
											<td>{{ item.count }}</td>
										</tr>
										<tr v-if="getTop10DimensionesAll().length === 0">
											<td colspan="4">Sin datos</td>
										</tr>
									</tbody>
								</table>
							</div>
						</div>
					</div>
-->
                <!-- Preguntas Management -->
                <div v-if="activeTab === 'preguntas'" class="tab-content">
                    <div class="module-header">
                        <h2>Gestión de Preguntas</h2>
                        <p>{{ getMensajeSede() }}</p>
                    </div>
                    <GestorPreguntasManagement />
                </div>

                <!-- Áreas Management -->
                <div v-if="activeTab === 'areas'" class="tab-content">
                    <div class="module-header">
                        <h2>Gestión de Áreas</h2>
                        <p>{{ getMensajeSede() }}</p>
                    </div>
                    <GestorAreasManagement />
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
import GestorPreguntasManagement from './GestorPreguntasManagement.vue';
import GestorAreasManagement from './GestorAreasManagement.vue';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

export default {
    name: 'GestorDashboard',
    components: {
        GestorPreguntasManagement,
        GestorAreasManagement
    },
    data() {
        return {
            user: null,
            sedeNombre: 'Cargando...',
            activeTab: 'dashboard',
            tabs: [
                { id: 'dashboard', name: 'Dashboard', icon: 'fas fa-tachometer-alt' },
                { id: 'preguntas', name: 'Preguntas', icon: 'fas fa-question-circle' },
                { id: 'areas', name: 'Áreas', icon: 'fas fa-th-large' },
            ],
            showUserMenu: false,
            cargando: false,
            exportandoCalificaciones: false,
            
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
                textosMasAnotados: { csat: [], fcr: [], porArea: {} },
                indicadoresDimensiones: {},
                // distribucionNPS: {}, // COMENTADO
                distribucionCSAT: {},
                distribucionFCR: {}
            },
            
            // Charts
            encuestasAreaChart: null,
            // distribucionNPSChart: null, // COMENTADO
            polarChartCSAT: null,
            polarChartFCR: null,
            // polarChartNPS: null, // COMENTADO
            relacionChartRefs: {},
            dimensionesChartRefs: {},
            encuestasPorDiaTiposChart: null,
            top10FCRDimensionesChart: null,
            top10DimChartRefs: {},
            top10AllDimChart: null,
            textosCSATChart: null,
            textosFCRChart: null,
            csatNivelCharts: {}, // Gráficos por nivel CSAT
            ultimos5DiasComboChart: null // Gráfico combinado últimos 5 días
        }
    },
    computed: {
        getMensajeSede() {
            return () => {
                if (!this.sedeNombre || this.sedeNombre === 'Cargando...' || this.sedeNombre === 'Sede no asignada') {
                    return 'No tienes una sede asignada';
                }
                
                // Si hay un área seleccionada, agregarla al mensaje
                if (this.filters.areaId) {
                    const areaSeleccionada = this.areasAgrupadas.find(a => a.id == this.filters.areaId);
                    if (areaSeleccionada) {
                        return `Mostrando contenido de la sede ${this.sedeNombre} - ${areaSeleccionada.nombre}`;
                    }
                }
                
                return `Mostrando contenido de la sede ${this.sedeNombre}`;
            };
        },
        tieneAreasAsignadasEspecificas() {
            // Verificar si el gestor tiene áreas asignadas específicamente
            const areasAsignadas = this.user?.areas || [];
            return areasAsignadas.length > 0;
        },
        areasAgrupadas() {
            const sedeId = this.user?.sede_id || null;
            const areasAsignadas = this.user?.areas || [];
            
            let areasFiltradas = this.areas;
            if (sedeId) {
                areasFiltradas = this.areas.filter(area => area.sede_id === sedeId);
            }
            
            // Si el gestor tiene áreas asignadas específicamente, filtrar solo esas
            if (areasAsignadas.length > 0) {
                const areaIdsAsignadas = areasAsignadas.map(a => a.id);
                areasFiltradas = areasFiltradas.filter(area => areaIdsAsignadas.includes(area.id));
            }
            
            const areasUnicas = [];
            const nombresVistos = new Set();
            
            areasFiltradas.forEach(area => {
                const nombreNormalizado = area.nombre.trim().toLowerCase();
                
                if (!nombresVistos.has(nombreNormalizado)) {
                    nombresVistos.add(nombreNormalizado);
                    areasUnicas.push(area);
                }
            });
            
            return areasUnicas.sort((a, b) => {
                return a.nombre.localeCompare(b.nombre);
            });
        },
        indicadoresDisponibles() {
            // Si no tiene áreas asignadas específicas, mostrar todos los indicadores
            if (!this.tieneAreasAsignadasEspecificas) {
                return ['csat', 'fcr'];
            }
            
            const indicadores = new Set();
            
            // Si hay un área seleccionada específicamente, solo verificar esa área
            if (this.filters.areaId) {
                const areaSeleccionada = this.areas.find(a => a.id == this.filters.areaId);
                if (areaSeleccionada) {
                    if (areaSeleccionada.permite_csat) indicadores.add('csat');
                    if (areaSeleccionada.permite_fcr) indicadores.add('fcr');
                }
            } else {
                // Si no hay área seleccionada (está en "Todas"), verificar todas las áreas asignadas
                const areasAsignadas = this.user?.areas || [];
                areasAsignadas.forEach(areaAsignada => {
                    const areaCompleta = this.areas.find(a => a.id === areaAsignada.id);
                    if (areaCompleta) {
                        if (areaCompleta.permite_csat) indicadores.add('csat');
                        if (areaCompleta.permite_fcr) indicadores.add('fcr');
                    }
                });
            }
            
            // Si no hay indicadores habilitados, mostrar todos por defecto
            return indicadores.size > 0 ? Array.from(indicadores) : ['csat', 'fcr'];
        },
        // Tipos de indicadores activos según filtro
        tiposIndicadoresActivos() {
            if (this.filters.tipoCalificacion) {
                return [this.filters.tipoCalificacion];
            }
            return ['csat', 'fcr']; // 'nps' removido
        },

        // Tabla: últimos 5 días con totales por tipo y distribución
        rankingAreas() {
            try {
                const ranking = this.estadisticas?.rankingAreas || [];
                return ranking;
            } catch (error) {
                console.error('Error al obtener ranking de áreas:', error);
                return [];
            }
        },

        rankingAreasInsatisfaccion() {
            try {
                const ranking = this.estadisticas?.rankingAreasInsatisfaccion || [];
                return ranking;
            } catch (error) {
                console.error('Error al obtener ranking de áreas por insatisfacción:', error);
                return [];
        }
    },

        tablaUltimosDias() {
            const porTipo = this.estadisticas?.relacionNivelEncuestas || {};
            const tipos = ['csat', 'fcr']; // 'nps' removido
            const fechasSet = new Set();
            tipos.forEach(t => {
                (porTipo[t] || []).forEach(item => fechasSet.add(item.fecha));
            });
            const fechas = Array.from(fechasSet).sort();
            const ultimas = fechas.slice(Math.max(0, fechas.length - 5));

            const mapPorTipo = {};
            const mapPorcentajes = {};
            tipos.forEach(t => {
                mapPorTipo[t] = {};
                mapPorcentajes[t] = {};
                (porTipo[t] || []).forEach(item => {
                    mapPorTipo[t][item.fecha] = item.cantidad_encuestas || 0;
                    // Usar el porcentaje del backend, que ya está calculado correctamente
                    mapPorcentajes[t][item.fecha] = item.porcentaje !== undefined && item.porcentaje !== null ? parseFloat(item.porcentaje) : 0;
                });
            });

            return ultimas.map(f => {
                const csat = mapPorTipo.csat[f] || 0;
                const fcr = mapPorTipo.fcr[f] || 0;
                let csatPorcentaje = mapPorcentajes.csat[f];
                let fcrPorcentaje = mapPorcentajes.fcr[f];
                
                // Si el porcentaje no viene del backend o es 0, calcularlo localmente
                // pero solo si tenemos datos
                if ((!csatPorcentaje || csatPorcentaje === 0) && csat > 0) {
                    // Esto no debería pasar si el backend está funcionando correctamente
                    console.warn(`⚠️ CSAT porcentaje no disponible para fecha ${f}, usando 0`);
                    csatPorcentaje = 0;
                }
                if ((!fcrPorcentaje || fcrPorcentaje === 0) && fcr > 0) {
                    console.warn(`⚠️ FCR porcentaje no disponible para fecha ${f}, usando 0`);
                    fcrPorcentaje = 0;
            }
                
                // Asegurar que los porcentajes sean números válidos
                csatPorcentaje = csatPorcentaje !== undefined && csatPorcentaje !== null ? parseFloat(csatPorcentaje) : 0;
                fcrPorcentaje = fcrPorcentaje !== undefined && fcrPorcentaje !== null ? parseFloat(fcrPorcentaje) : 0;
                
                // const nps = mapPorTipo.nps[f] || 0; // COMENTADO
                const total = csat + fcr; // nps removido
                
                const resultado = { 
                    fecha: f, 
                    csat, 
                    fcr, 
                    total,
                    csatPorcentaje: Math.round(csatPorcentaje),
                    fcrPorcentaje: Math.round(fcrPorcentaje)
                };
                
                // Debug temporal - mostrar todos los datos
                console.log(`📊 Fecha: ${f}`, {
                    csat: csat,
                    fcr: fcr,
                    csatPorcentaje: csatPorcentaje,
                    fcrPorcentaje: fcrPorcentaje,
                    total: total,
                    porcentajesBackend: {
                        csat: mapPorcentajes.csat[f],
                        fcr: mapPorcentajes.fcr[f]
                    }
                });
                
                return resultado; // nps removido
            });
        },

        tieneDimensionesDisponibles() {
            const dims = this.estadisticas?.indicadoresDimensiones || {};
            return ['csat', 'fcr'].some(t => Array.isArray(dims[t]) && dims[t].length > 0); // 'nps' removido
        }
    },
    async mounted() {
        // Registrar componentes de Chart.js
        Chart.register(...registerables);
        
        await this.loadUserData();
        if (this.user?.sede_id) {
            await this.cargarDatosBase();
            await this.cargarEstadisticas();
        }
    },
    watch: {
        async 'filters.areaId'(newAreaId, oldAreaId) {
            // Si cambia el área seleccionada y tiene áreas asignadas específicas
            if (this.tieneAreasAsignadasEspecificas && newAreaId !== oldAreaId && newAreaId) {
                // Esperar a que Vue actualice los computed properties
                await this.$nextTick();
                
                // Obtener los indicadores disponibles para la nueva área
                let indicadoresDisponibles = [];
                
                // Si hay un área seleccionada, verificar sus indicadores directamente
                const areaSeleccionada = this.areas.find(a => a.id == newAreaId);
                if (areaSeleccionada) {
                    if (areaSeleccionada.permite_csat) indicadoresDisponibles.push('csat');
                    if (areaSeleccionada.permite_fcr) indicadoresDisponibles.push('fcr');
                    if (areaSeleccionada.permite_nps) indicadoresDisponibles.push('nps');
                }
                
                // Si hay 2 o más indicadores disponibles, seleccionar "Todos" (limpiar filtro)
                if (indicadoresDisponibles.length >= 2) {
                    this.filters.tipoCalificacion = '';
                }
                // Si solo hay un indicador disponible, seleccionarlo automáticamente
                else if (indicadoresDisponibles.length === 1) {
                    this.filters.tipoCalificacion = indicadoresDisponibles[0];
                }
                // Si no hay indicadores disponibles o el indicador actual no está disponible, limpiar
                else if (!indicadoresDisponibles.includes(this.filters.tipoCalificacion)) {
                    this.filters.tipoCalificacion = '';
                }
                
                // Esperar otro tick para asegurar que los cambios se apliquen
                await this.$nextTick();
                
                // Recargar estadísticas con el nuevo filtro
                await this.cargarEstadisticas();
            }
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
                    // Obtener nombre de la sede del usuario
                    if (this.user.sede) {
                        this.sedeNombre = this.user.sede.nombre;
                    } else if (this.user.sede_id) {
                        // Si solo tenemos el ID, obtener el nombre de la sede
                        try {
                            const sedeResponse = await fetch(`/api/sedes/${this.user.sede_id}`);
                            if (sedeResponse.ok) {
                                const sede = await sedeResponse.json();
                                this.sedeNombre = sede.nombre;
                    } else {
                        this.sedeNombre = 'Sede no asignada';
                    }
                        } catch (error) {
                            console.error('Error obteniendo nombre de sede:', error);
                            this.sedeNombre = 'Sede no asignada';
                        }
                } else {
                        this.sedeNombre = 'Sede no asignada';
                    }
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
                // Cargar áreas filtradas por sede del gestor
                const sedeId = this.user?.sede_id;
                let areasUrl = '/api/areas';
                if (sedeId) {
                    areasUrl += `?sede_id=${sedeId}`;
                }
                const areasResponse = await fetch(areasUrl);
                if (areasResponse.ok) {
                    this.areas = await areasResponse.json();
                    
                    // Si el gestor tiene áreas asignadas y no hay área seleccionada, seleccionar la primera
                    const areasAsignadas = this.user?.areas || [];
                    if (areasAsignadas.length > 0 && !this.filters.areaId) {
                        // Esperar a que se actualice areasAgrupadas
                        await this.$nextTick();
                        if (this.areasAgrupadas.length > 0) {
                            this.filters.areaId = this.areasAgrupadas[0].id;
                            console.log('📍 Área seleccionada automáticamente:', this.areasAgrupadas[0].nombre);
                            
                            // Si solo hay un indicador disponible, seleccionarlo automáticamente
                            await this.$nextTick();
                            const indicadoresDisponibles = this.indicadoresDisponibles;
                            if (indicadoresDisponibles.length === 1 && !this.filters.tipoCalificacion) {
                                this.filters.tipoCalificacion = indicadoresDisponibles[0];
                                console.log('📍 Indicador seleccionado automáticamente:', indicadoresDisponibles[0]);
                            }
                        }
                    }
                }

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
                
                // SIEMPRE incluir sede_id del gestor
                const sedeId = this.user?.sede_id;
                if (sedeId) {
                    params.append('sede_id', sedeId);
                }
                
                if (this.filters.fechaInicio) params.append('fecha_inicio', this.filters.fechaInicio);
                if (this.filters.fechaFin) params.append('fecha_fin', this.filters.fechaFin);
                if (this.filters.areaId) params.append('area_id', this.filters.areaId);
                if (this.filters.nivelId) params.append('nivel_id', this.filters.nivelId);
                if (this.filters.tipoCalificacion) params.append('tipo_calificacion', this.filters.tipoCalificacion);

                const response = await fetch(`/api/estadisticas?${params.toString()}`);
                if (response.ok) {
                    const datos = await response.json();
                    if (!datos.textosMasAnotados) {
                        datos.textosMasAnotados = { csat: [], fcr: [], porArea: {} };
                    }
                    if (!datos.textosMasAnotados.porArea) {
                        datos.textosMasAnotados.porArea = {};
                    }
                    this.estadisticas = datos;
                    console.log('📊 Estadísticas recibidas (completo):', JSON.stringify(this.estadisticas, null, 2));
                    console.log('📊 Nivel Indicador:', this.estadisticas.nivelIndicador);
                    console.log('📊 CSAT:', this.estadisticas.nivelIndicador?.csat);
                    console.log('📊 FCR:', this.estadisticas.nivelIndicador?.fcr);
                    // console.log('📊 NPS:', this.estadisticas.nivelIndicador?.nps); // COMENTADO
                    
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
                    
                    // Esperar a que Vue actualice el DOM antes de renderizar gráficos
                    await this.$nextTick();
                    
                    // Renderizar gráficos inmediatamente (ya manejan la destrucción internamente)
                    // Usar setTimeout para asegurar que el DOM esté completamente listo
                        setTimeout(() => {
                                        this.renderizarGraficos();
                                }, 100);
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
                // 'nps': 'NPS' // COMENTADO
            };
            return nombres[tipo] || tipo.toUpperCase();
        },

        mostrarIndicador(tipo) {
            // Si hay un filtro de tipo de calificación específico, solo mostrar ese
            if (this.filters.tipoCalificacion) {
                return this.filters.tipoCalificacion === tipo;
            }
            
            // Si tiene áreas asignadas específicas, verificar si el indicador está disponible
            if (this.tieneAreasAsignadasEspecificas) {
                return this.indicadoresDisponibles.includes(tipo);
            }
            
            // Por defecto, mostrar todos si no hay áreas asignadas específicas
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
                    return isNaN(valor) ? 0 : Math.round(valor * 10) / 10;
                }
                
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

        // MÉTODOS NPS COMENTADOS
        /*
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
        */

        getCSATDistribucion() {
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
            const total = this.getTotalRespuestas('fcr');
            const si = Math.round(total * (this.getValorIndicador('fcr') / 100));
            const no = total - si;
            
            return {
                si: si,
                no: no,
                total: total
            };
        },

        // MÉTODO NPS COMENTADO
        /*
        getNPSDistribucion() {
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
        */

        // MÉTODO NPS COMENTADO
        /*
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
        */

        getTendenciaIndicador(tipo) {
            return null;
        },

        getSemaforoPath(valor) {
            const numValor = typeof valor === 'number' ? valor : parseFloat(valor) || 0;
            
            const centerX = 100;
            const centerY = 100;
            const radius = 85;
            const startAngle = Math.PI;
            const endAngle = 0;
            
            const clampedValor = Math.min(100, Math.max(0, numValor));
            const percentage = clampedValor / 100;
            
            const currentAngle = startAngle - (startAngle - endAngle) * percentage;
            
            const x1 = centerX + radius * Math.cos(startAngle);
            const y1 = centerY + radius * Math.sin(startAngle);
            
            const x2 = centerX + radius * Math.cos(currentAngle);
            const y2 = centerY + radius * Math.sin(currentAngle);
            
            const largeArc = percentage > 0.5 ? 1 : 0;
            
            return `M ${x1} ${y1} A ${radius} ${radius} 0 ${largeArc} 0 ${x2} ${y2} L ${centerX} ${centerY} Z`;
        },

        getSemaforoColor(valor) {
            const numValor = typeof valor === 'number' ? valor : parseFloat(valor) || 0;
            if (numValor >= 80) return '#10b981';
            if (numValor >= 50) return '#f59e0b';
            return '#ef4444';
        },

        renderizarGraficos() {
            try {
                console.log('🎨 Iniciando renderizado de gráficos...');
                
                // Destruir gráficos existentes primero para evitar conflictos
                this.destruirGraficos();
                
                // Esperar más tiempo para asegurar que Chart.js haya terminado completamente
                // y que el DOM esté completamente limpio
                setTimeout(() => {
                    // Verificar que los refs estén disponibles
                    this.$nextTick(() => {
                        // Esperar un momento adicional para que Vue haya actualizado completamente el DOM
                        setTimeout(async () => {
                            try {
                                // Verificar que todos los canvas existan antes de renderizar
                                const canvasRefs = ['polarChartCSAT', 'polarChartFCR', 'encuestasAreaChart'];
                                const missingCanvas = canvasRefs.some(ref => {
                                    const canvas = this.$refs[ref];
                                    return !canvas || !canvas.parentNode;
                                });
                                
                                if (missingCanvas) {
                                    console.warn('⚠️ Algunos canvas no están disponibles, esperando más tiempo...');
                                    setTimeout(async () => {
                                        await this.renderizarGraficosInterno();
                                    }, 200);
                                    return;
                                }
                                
                                await this.renderizarGraficosInterno();
                            } catch (error) {
                                console.error('❌ Error en renderizarGraficos:', error);
                            }
                        }, 150);
                    });
                }, 400); // Aumentar tiempo de espera después de destruir
            } catch (error) {
                console.error('❌ Error general en renderizarGraficos:', error);
            }
        },

        async renderizarGraficosInterno() {
            try {
                // Polar Area Charts para indicadores
                if (this.mostrarIndicador('csat')) {
                    try {
                        this.renderizarPolarChart('csat');
                                console.log('✅ Gráfico CSAT renderizado');
                    } catch (error) {
                                console.error('❌ Error renderizando polar chart CSAT:', error);
                    }
                }
                if (this.mostrarIndicador('fcr')) {
                    try {
                        this.renderizarPolarChart('fcr');
                                console.log('✅ Gráfico FCR renderizado');
                    } catch (error) {
                                console.error('❌ Error renderizando polar chart FCR:', error);
                }
                }
                
                // Gráfico de encuestas por área
                try {
                    await this.renderizarGraficoEncuestasArea();
                            console.log('✅ Gráfico encuestas por área renderizado');
                } catch (error) {
                            console.error('❌ Error renderizando gráfico encuestas por área:', error);
                }
                
                try {
                    this.renderizarGraficoEncuestasPorDiaTipos();
                            console.log('✅ Gráfico encuestas por día renderizado');
                } catch (error) {
                            console.error('❌ Error renderizando gráfico encuestas por día:', error);
                }
                
                // Gráficos de dimensiones
                this.tiposIndicadoresActivos.forEach(tipo => {
                    try {
                        this.renderizarGraficoDimensiones(tipo);
                                console.log(`✅ Gráfico dimensiones ${tipo} renderizado`);
                    } catch (error) {
                                console.error(`❌ Error renderizando gráfico dimensiones ${tipo}:`, error);
                    }
                });

                // Top 10 combinado (horizontal)
                try {
                    this.renderizarTop10DimensionesAll();
                            console.log('✅ Gráfico top 10 dimensiones renderizado');
                } catch (error) {
                            console.error('❌ Error renderizando top 10 dimensiones:', error);
                }

                // Gráficos CSAT por nivel
                if (this.mostrarIndicador('csat')) {
                    try {
                        this.renderizarGraficosCSATPorNivel();
                                console.log('✅ Gráficos CSAT por nivel renderizados');
            } catch (error) {
                                console.error('❌ Error renderizando gráficos CSAT por nivel:', error);
                    }
                }

                // Gráfico combinado últimos 5 días
                try {
                    this.renderizarGraficoUltimos5DiasCombo();
                    console.log('✅ Gráfico últimos 5 días renderizado');
                } catch (error) {
                    console.error('❌ Error renderizando gráfico últimos 5 días:', error);
                }

                try {
                    this.renderizarGraficoTextosCSAT();
                } catch (error) {
                    console.error('❌ Error renderizando gráfico textos CSAT:', error);
                }
                try {
                    this.renderizarGraficoTextosFCR();
                } catch (error) {
                    console.error('❌ Error renderizando gráfico textos FCR:', error);
                }
                        
                console.log('🎨 Renderizado de gráficos completado');
            } catch (error) {
                console.error('❌ Error general al renderizar gráficos:', error);
            }
        },

        renderizarPolarChart(tipo) {
            const refName = `polarChart${tipo.toUpperCase()}`;
            const canvas = this.$refs[refName];
            if (!canvas) {
                console.warn(`Canvas ${refName} no encontrado - puede que no esté visible`);
                return;
            }

            // Verificar que el canvas esté en el DOM
            if (!canvas.parentNode) {
                console.warn(`Canvas ${refName} no está en el DOM`);
                return;
            }

            const ctx = canvas.getContext('2d');
            if (!ctx) {
                console.warn(`No se pudo obtener contexto del canvas ${refName}`);
                return;
            }

            const chartName = `polarChart${tipo.toUpperCase()}`;
            
            // Destruir gráfico existente de forma segura
            try {
            if (this[chartName]) {
                    try {
                        // Detener cualquier animación en curso
                        if (this[chartName].animating) {
                            this[chartName].stop();
                        }
                        // Destruir el chart
                this[chartName].destroy();
                    } catch (destroyError) {
                        console.warn(`Error al destruir ${chartName}:`, destroyError);
                    }
                    this[chartName] = null;
                }
            } catch (e) {
                console.warn(`Error verificando ${chartName}:`, e);
                this[chartName] = null;
            }

            // Verificar nuevamente que el canvas y contexto aún existan antes de continuar
            if (!canvas || !canvas.parentNode || !ctx) {
                console.error(`Canvas ${refName} no está disponible después de destruir`);
                return;
            }

            // Continuar con la creación del chart
            this.crearPolarChartInterno(tipo, canvas, ctx, chartName, refName);
        },

        crearPolarChartInterno(tipo, canvas, ctx, chartName, refName) {
            // Verificar nuevamente que el canvas y contexto aún existan
            if (!canvas || !canvas.parentNode || !ctx) {
                console.error(`Canvas ${refName} no está disponible para crear el chart`);
                return;
            }

            // Asegurarse de que el canvas esté completamente limpio
            try {
                const width = canvas.width || canvas.offsetWidth || 300;
                const height = canvas.height || canvas.offsetHeight || 300;
                ctx.clearRect(0, 0, width, height);
            } catch (e) {
                console.warn(`Error limpiando canvas ${refName}:`, e);
            }

            let polarData = [];
            let polarColors = [];
            let labels = [];
            let fullLabels = [];
            let total = 0;

            // if (tipo === 'nps') { // COMENTADO
            //     const distribucion = this.getNPSDistribucion();
            //     total = distribucion.total || 0;
                
            //     if (total > 0) {
            //         const promotores = distribucion.promotores || 0;
            //         const pasivos = distribucion.pasivos || 0;
            //         const detractores = distribucion.detractores || 0;
                    
            //         const porcentajePromotores = Math.round((promotores / total) * 100);
            //         const porcentajePasivos = Math.round((pasivos / total) * 100);
            //         const porcentajeDetractores = Math.round((detractores / total) * 100);
                    
            //         const segmentos = [
            //             { valor: porcentajePromotores, color: '#10b981', label: `Promotores (9-10)\n${porcentajePromotores}%\n${promotores} respuestas`, count: promotores },
            //             { valor: porcentajePasivos, color: '#f59e0b', label: `Pasivos (7-8)\n${porcentajePasivos}%\n${pasivos} respuestas`, count: pasivos },
            //             { valor: porcentajeDetractores, color: '#ef4444', label: `Detractores (1-6)\n${porcentajeDetractores}%\n${detractores} respuestas`, count: detractores }
            //         ];
                    
            //         segmentos.sort((a, b) => b.valor - a.valor);
                    
            //         polarData = segmentos.map(s => s.valor);
            //         polarColors = segmentos.map(s => s.color);
            //         labels = segmentos.map(s => s.label.split('\n')[0]);
            //         fullLabels = segmentos.map(s => s.label);
            //     }
            // } else 
            if (tipo === 'csat') {
                const distribucion = this.getCSATDistribucion();
                total = distribucion.total || 0;
                
                if (total > 0) {
                    const satisfechos = distribucion.satisfechos || 0;
                    const noSatisfechos = distribucion.noSatisfechos || 0;
                    
                    const porcentajeSatisfechos = Math.round((satisfechos / total) * 100);
                    const porcentajeNoSatisfechos = Math.round((noSatisfechos / total) * 100);
                    
                    polarData = [porcentajeSatisfechos, porcentajeNoSatisfechos];
                    polarColors = ['#10b981', '#ef4444'];
                    fullLabels = [
                        `Satisfechos\n${porcentajeSatisfechos}%\n${satisfechos} respuestas`,
                        `No Satisfechos\n${porcentajeNoSatisfechos}%\n${noSatisfechos} respuestas`
                    ];
                    labels = fullLabels.map(l => l.split('\n')[0]);
                }
            } else if (tipo === 'fcr') {
                const distribucion = this.getFCRDistribucion();
                total = distribucion.total || 0;
                
                if (total > 0) {
                    const si = distribucion.si || 0;
                    const no = distribucion.no || 0;
                    
                    const porcentajeSi = Math.round((si / total) * 100);
                    const porcentajeNo = Math.round((no / total) * 100);
                    
                    polarData = [porcentajeSi, porcentajeNo];
                    polarColors = porcentajeSi >= porcentajeNo ? ['#10b981', '#ef4444'] : ['#10b981', '#ef4444'];
                    fullLabels = [
                        `Sí\n${porcentajeSi}%\n${si} respuestas`,
                        `No\n${porcentajeNo}%\n${no} respuestas`
                    ];
                    labels = fullLabels.map(l => l.split('\n')[0]);
                }
            }

            if (polarData.length === 0) {
                polarData = [0, 0, 0];
                polarColors = ['#10b981', '#f59e0b', '#ef4444'];
                labels = ['Sin datos', 'Sin datos', 'Sin datos'];
            }

            // Verificar nuevamente que el canvas y contexto aún existan antes de crear el chart
            if (!canvas || !canvas.parentNode || !ctx) {
                console.error(`Canvas ${refName} no está disponible para crear el chart`);
                return;
            }

            try {
                this[chartName] = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: labels.map(l => l.split('\n')[0]),
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
                                        return context.label + ': ' + context.parsed + '%';
                                    }
                                }
                            }
                        },
                        animation: false,
                        cutout: '40%'
                    }
                });
                console.log(`✅ Chart ${chartName} creado exitosamente`);
            } catch (error) {
                console.error(`❌ Error creando polar chart ${tipo}:`, error);
                console.error('Canvas:', canvas);
                console.error('Context:', ctx);
                this[chartName] = null;
            }
        },

        async renderizarGraficoEncuestasArea() {
            const canvas = this.$refs.encuestasAreaChart;
            if (!canvas) {
                console.warn('Canvas encuestasAreaChart no encontrado');
                return;
            }

            // Verificar que el canvas esté en el DOM
            if (!canvas.parentNode) {
                console.warn('Canvas encuestasAreaChart no está en el DOM');
                return;
            }

            const ctx = canvas.getContext('2d');
            if (!ctx) {
                console.warn('No se pudo obtener contexto del canvas encuestasAreaChart');
                return;
            }

            // Destruir gráfico existente de forma segura
            try {
                // Primero intentar destruir el gráfico usando Chart.getChart
                const existingChart = Chart.getChart(canvas);
                if (existingChart) {
                    try {
                        if (existingChart.animating) {
                            existingChart.stop();
                        }
                        existingChart.destroy();
                    } catch (destroyError) {
                        console.warn('Error al destruir gráfico existente del canvas:', destroyError);
                    }
                }
                
                // También destruir la referencia local si existe
                if (this.encuestasAreaChart) {
                    try {
                        if (this.encuestasAreaChart.animating) {
                            this.encuestasAreaChart.stop();
                        }
                        // Solo destruir si no es el mismo gráfico que acabamos de destruir
                        if (this.encuestasAreaChart !== existingChart) {
                            this.encuestasAreaChart.destroy();
                        }
                    } catch (destroyError) {
                        console.warn('Error al destruir encuestasAreaChart:', destroyError);
                    }
                    this.encuestasAreaChart = null;
                }
            } catch (e) {
                console.warn('Error verificando encuestasAreaChart:', e);
                this.encuestasAreaChart = null;
            }
            
            // Esperar un poco para que Chart.js termine de limpiar el canvas
            await new Promise(resolve => setTimeout(resolve, 50));

            // Limpiar el canvas
            try {
                const width = canvas.width || canvas.offsetWidth || 300;
                const height = canvas.height || canvas.offsetHeight || 300;
                ctx.clearRect(0, 0, width, height);
            } catch (e) {
                console.warn('Error limpiando canvas encuestasAreaChart:', e);
            }

            // Verificar nuevamente que el canvas esté disponible
            if (!canvas || !canvas.parentNode || !ctx) {
                console.error('Canvas encuestasAreaChart no está disponible después de destruir');
                return;
            }

            const data = this.estadisticas.encuestasPorArea || [];
            
            const areasMap = {};
            const tipos = ['csat', 'fcr']; // 'nps' removido
            
            data.forEach(item => {
                if (!areasMap[item.area_nombre]) {
                    areasMap[item.area_nombre] = { csat: 0, fcr: 0 }; // nps removido
                }
                if (item.tipo_calificacion && areasMap[item.area_nombre][item.tipo_calificacion] !== undefined) {
                    areasMap[item.area_nombre][item.tipo_calificacion] = item.cantidad_encuestas;
                                    }
            });

            let labels = Object.keys(areasMap);
            const datasets = tipos
                .filter(tipo => this.mostrarIndicador(tipo))
                .map((tipo, index) => {
                    const colors = ['#4f46e5', '#10b981']; // '#f59e0b' removido para nps
                    return {
                        label: tipo.toUpperCase(),
                        data: labels.length ? labels.map(area => areasMap[area][tipo] || 0) : [0],
                        backgroundColor: colors[index],
                        borderColor: colors[index],
                        borderWidth: 1,
                        borderRadius: 4
                    };
                });

            if (labels.length === 0) {
                labels = ['Sin datos'];
            }
            
            try {
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
                    },
                    animation: false
                }
                });
                console.log('✅ Chart encuestasAreaChart creado exitosamente');
            } catch (error) {
                console.error('❌ Error creando encuestasAreaChart:', error);
                this.encuestasAreaChart = null;
            }
        },

        // MÉTODO COMENTADO
        /*
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
        */

        renderizarGraficoEncuestasPorDiaTipos() {
            const canvas = this.$refs.encuestasPorDiaTiposChart;
            if (!canvas) {
                console.warn('Canvas encuestasPorDiaTiposChart no encontrado');
                return;
            }

            // Verificar que el canvas esté en el DOM
            if (!canvas.parentNode) {
                console.warn('Canvas encuestasPorDiaTiposChart no está en el DOM');
                return;
            }

            // Destruir gráfico existente de forma segura
            try {
                if (this.encuestasPorDiaTiposChart) {
                    try {
                        if (this.encuestasPorDiaTiposChart.animating) {
                            this.encuestasPorDiaTiposChart.stop();
                        }
                        this.encuestasPorDiaTiposChart.destroy();
                    } catch (destroyError) {
                        console.warn('Error al destruir encuestasPorDiaTiposChart:', destroyError);
                    }
                    this.encuestasPorDiaTiposChart = null;
                }
            } catch (e) {
                console.warn('Error verificando encuestasPorDiaTiposChart:', e);
                this.encuestasPorDiaTiposChart = null;
            }

            // Verificar nuevamente que el canvas esté disponible
            if (!canvas || !canvas.parentNode) {
                console.error('Canvas encuestasPorDiaTiposChart no está disponible después de destruir');
                return;
            }

            const dataPorTipo = this.estadisticas.relacionNivelEncuestas || {};
            const tipos = ['csat', 'fcr'].filter(t => this.mostrarIndicador(t)); // 'nps' removido
            const setFechas = new Set();
            tipos.forEach(t => {
                (dataPorTipo[t] || []).forEach(item => setFechas.add(item.fecha));
            });
            let labelsRaw = Array.from(setFechas).sort();
            let labels = labelsRaw.map(f => this.formatearFecha(f));

            const colorMap = { csat: '#4f46e5', fcr: '#10b981' }; // nps removido

            const datasets = tipos.map(tipo => {
                const mapPorFecha = {};
                (dataPorTipo[tipo] || []).forEach(item => {
                    mapPorFecha[item.fecha] = item.cantidad_encuestas || 0;
                });
                return {
                    label: tipo.toUpperCase(),
                    data: labelsRaw.length ? labelsRaw.map(f => mapPorFecha[f] || 0) : [0],
                    borderColor: colorMap[tipo],
                    backgroundColor: colorMap[tipo] + '1A',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: false
                };
            });

            if (labels.length === 0) {
                labelsRaw = [''];
                labels = ['Sin datos'];
            }

            try {
                this.encuestasPorDiaTiposChart = new Chart(canvas, {
                    type: 'line',
                    data: { labels, datasets },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                            title: { display: true, text: 'Cantidad de encuestas' }
                        }
                    },
                    plugins: {
                        legend: { position: 'top' }
                    }
                }
                });
                console.log('✅ Chart encuestasPorDiaTiposChart creado exitosamente');
            } catch (error) {
                console.error('❌ Error creando encuestasPorDiaTiposChart:', error);
                this.encuestasPorDiaTiposChart = null;
            }
        },

        renderizarGraficoRelacion(tipo) {
            // ... método sin cambios
        },

        renderizarGraficoDimensiones(tipo) {
            // ... método sin cambios
        },

        renderizarTop10Dimensiones(tipo) {
            // ... método sin cambios
        },

        getTop10Dimensiones(tipo) {
            // ... método sin cambios
        },

        getTop10DimensionesAll() {
            const dims = this.estadisticas?.indicadoresDimensiones || {};
            const tipos = ['csat', 'fcr']; // 'nps' removido
            const items = [];
            tipos.forEach(tipo => {
                const arr = dims[tipo] || [];
                arr.forEach(d => {
                    let count = 0;
                    if (d.tipo === 'opcion_unica' && Array.isArray(d.respuestas)) {
                        count = d.respuestas.reduce((acc, r) => acc + (r.cantidad || 0), 0);
                    } else {
                        count = d.total || 0;
                    }
                    items.push({ tipo, dimension: d.dimension, count });
                });
            });
            items.sort((a, b) => b.count - a.count);
            return items.slice(0, 10);
        },

        renderizarTop10DimensionesAll() {
            const canvas = this.$refs.top10AllDimChart;
            if (!canvas) {
                console.warn('Canvas top10AllDimChart no encontrado');
                return;
            }

            // Verificar que el canvas esté en el DOM
            if (!canvas.parentNode) {
                console.warn('Canvas top10AllDimChart no está en el DOM');
                return;
            }

            // Destruir gráfico existente de forma segura
            try {
                if (this.top10AllDimChart) {
                    try {
                        if (this.top10AllDimChart.animating) {
                            this.top10AllDimChart.stop();
                        }
                        this.top10AllDimChart.destroy();
                    } catch (destroyError) {
                        console.warn('Error al destruir top10AllDimChart:', destroyError);
                    }
                    this.top10AllDimChart = null;
                }
            } catch (e) {
                console.warn('Error verificando top10AllDimChart:', e);
                this.top10AllDimChart = null;
            }

            // Verificar nuevamente que el canvas esté disponible
            if (!canvas || !canvas.parentNode) {
                console.error('Canvas top10AllDimChart no está disponible después de destruir');
                return;
            }

            let top10 = this.getTop10DimensionesAll();
            const isEmpty = top10.length === 0;
            if (isEmpty) {
                top10 = [{ tipo: 'csat', dimension: 'Sin datos', count: 0 }];
            }

            const labels = top10.map(i => this.acortarTexto(i.dimension, 60));
            const data = top10.map(i => i.count);
            const colorMap = { csat: '#4f46e5', fcr: '#10b981' }; // nps removido
            const backgroundColors = top10.map(i => colorMap[i.tipo] || '#6b7280');
            const borderColors = top10.map(i => (colorMap[i.tipo] || '#6b7280'));

            try {
                this.top10AllDimChart = new Chart(canvas, {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: [{
                            label: 'Respuestas',
                            data,
                            backgroundColor: backgroundColors,
                            borderColor: borderColors,
                        borderWidth: 1,
                        borderRadius: 4
                    }]
                },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: false,
                        plugins: {
                            legend: { display: false },
                        tooltip: {
                            callbacks: {
                                title: (ctx) => ctx[0]?.label || '',
                                label: (ctx) => {
                                    const i = ctx.dataIndex;
                                    const item = top10[i];
                                    return `${item.tipo.toUpperCase()}: ${ctx.parsed.x}`;
                                }
                            }
                        }
                    },
                        scales: {
                            x: { beginAtZero: true, ticks: { stepSize: 1 } }
                        }
                    }
                });
                console.log('✅ Chart top10AllDimChart creado exitosamente');
            } catch (error) {
                console.error('❌ Error creando top10AllDimChart:', error);
                this.top10AllDimChart = null;
            }
        },

        formatearFecha(fecha) {
              if (!fecha) return '';

    // Caso YYYY-MM
    if (fecha.match(/^\d{4}-\d{2}$/)) {
        const [year, month] = fecha.split('-');
        const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        return `${meses[parseInt(month) - 1]} ${year}`;
    }

    // Caso YYYY-MM-DD sin restar días
    if (fecha.match(/^\d{4}-\d{2}-\d{2}$/)) {
        const [y, m, d] = fecha.split('-');
        const date = new Date(y, m - 1, d); // ← FIX aquí

        const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        return `${date.getDate()} ${meses[date.getMonth()]}`;
    }

    return fecha;

        },

        formatearFechaHora(valor) {
            if (!valor) return '—';
            const s = String(valor);
            const normalized = s.includes('T') || s.includes('Z') ? s : s.replace(' ', 'T');
            const d = new Date(normalized);
            if (Number.isNaN(d.getTime())) return s;
            return d.toLocaleString('es', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        },

        destruirGraficos() {
            try {
                if (this.encuestasAreaChart) {
                    try {
                        if (this.encuestasAreaChart.animating) {
                            this.encuestasAreaChart.stop();
                        }
                        this.encuestasAreaChart.destroy();
                    } catch (destroyError) {
                        console.warn('Error al destruir encuestasAreaChart:', destroyError);
                    }
                    this.encuestasAreaChart = null;
                }
            } catch (e) {
                console.warn('Error verificando encuestasAreaChart:', e);
                this.encuestasAreaChart = null;
            }
            // if (this.distribucionNPSChart) { // COMENTADO
            //     this.distribucionNPSChart.destroy();
            //     this.distribucionNPSChart = null;
            // }
            try {
            if (this.polarChartCSAT) {
                this.polarChartCSAT.destroy();
                this.polarChartCSAT = null;
            }
            } catch (e) {
                console.warn('Error destruyendo polarChartCSAT:', e);
                this.polarChartCSAT = null;
            }
            
            try {
            if (this.polarChartFCR) {
                this.polarChartFCR.destroy();
                    this.polarChartFCR = null;
                }
            } catch (e) {
                console.warn('Error destruyendo polarChartFCR:', e);
                this.polarChartFCR = null;
            }
            // if (this.polarChartNPS) { // COMENTADO
            //     try {
            //         this.polarChartNPS.destroy();
            //     } catch (e) {
            //         console.warn('Error destruyendo polarChartNPS:', e);
            //     }
            //     this.polarChartNPS = null;
            // }
            
            Object.keys(this.relacionChartRefs).forEach(key => {
                try {
                if (this.relacionChartRefs[key]) {
                    this.relacionChartRefs[key].destroy();
                    }
                } catch (e) {
                    console.warn(`Error destruyendo relacionChartRefs[${key}]:`, e);
                }
            });
            this.relacionChartRefs = {};
                
            try {
            if (this.encuestasPorDiaTiposChart) {
                this.encuestasPorDiaTiposChart.destroy();
                    this.encuestasPorDiaTiposChart = null;
                }
            } catch (e) {
                console.warn('Error destruyendo encuestasPorDiaTiposChart:', e);
                this.encuestasPorDiaTiposChart = null;
            }
                
            try {
            if (this.top10FCRDimensionesChart) {
                this.top10FCRDimensionesChart.destroy();
                    this.top10FCRDimensionesChart = null;
                }
            } catch (e) {
                console.warn('Error destruyendo top10FCRDimensionesChart:', e);
                this.top10FCRDimensionesChart = null;
            }
            
            Object.keys(this.dimensionesChartRefs).forEach(key => {
                try {
                if (this.dimensionesChartRefs[key]) {
                    this.dimensionesChartRefs[key].destroy();
                    }
                } catch (e) {
                    console.warn(`Error destruyendo dimensionesChartRefs[${key}]:`, e);
                }
            });
            this.dimensionesChartRefs = {};

            Object.keys(this.top10DimChartRefs).forEach(key => {
                try {
                if (this.top10DimChartRefs[key]) {
                    this.top10DimChartRefs[key].destroy();
                    }
                } catch (e) {
                    console.warn(`Error destruyendo top10DimChartRefs[${key}]:`, e);
                }
            });
            this.top10DimChartRefs = {};

            try {
            if (this.top10AllDimChart) {
                this.top10AllDimChart.destroy();
                    this.top10AllDimChart = null;
                }
            } catch (e) {
                console.warn('Error destruyendo top10AllDimChart:', e);
                this.top10AllDimChart = null;
            }

            Object.keys(this.csatNivelCharts).forEach(key => {
                try {
                if (this.csatNivelCharts[key]) {
                    this.csatNivelCharts[key].destroy();
                    }
                } catch (e) {
                    console.warn(`Error destruyendo csatNivelCharts[${key}]:`, e);
            }
            });
            this.csatNivelCharts = {};

            try {
            if (this.ultimos5DiasComboChart) {
                this.ultimos5DiasComboChart.destroy();
                    this.ultimos5DiasComboChart = null;
                }
            } catch (e) {
                console.warn('Error destruyendo ultimos5DiasComboChart:', e);
                this.ultimos5DiasComboChart = null;
            }

            if (this.textosCSATChart) {
                this.textosCSATChart.destroy();
                this.textosCSATChart = null;
            }
            if (this.textosFCRChart) {
                this.textosFCRChart.destroy();
                this.textosFCRChart = null;
            }
        },

        // Resto de métodos utilitarios sin cambios...
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
                
                // SIEMPRE incluir sede_id del gestor
                const sedeId = this.user?.sede_id;
                if (sedeId) params.append('sede_id', sedeId);
                
                if (this.filters.fechaInicio) params.append('fecha_inicio', this.filters.fechaInicio);
                if (this.filters.fechaFin) params.append('fecha_fin', this.filters.fechaFin);
                if (this.filters.areaId) params.append('area_id', this.filters.areaId);
                if (this.filters.nivelId) params.append('nivel_id', this.filters.nivelId);

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

        async exportarCalificaciones() {
            this.exportandoCalificaciones = true;
            try {
                const params = new URLSearchParams();

                // Forzar sede del gestor
                const sedeId = this.user?.sede_id;
                if (sedeId) params.append('sede_id', sedeId);

                if (this.filters.fechaInicio) params.append('fecha_inicio', this.filters.fechaInicio);
                if (this.filters.fechaFin) params.append('fecha_fin', this.filters.fechaFin);
                if (this.filters.areaId) params.append('area_id', this.filters.areaId);
                if (this.filters.nivelId) params.append('nivel_id', this.filters.nivelId);
                if (this.filters.tipoCalificacion) params.append('tipo_calificacion', this.filters.tipoCalificacion);

                const response = await fetch(`/api/calificaciones/exportar?${params.toString()}`);

                if (response.ok) {
                    const blob = await response.blob();
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;

                    const contentDisposition = response.headers.get('Content-Disposition');
                    let filename = `calificaciones-gestor-${new Date().toISOString().split('T')[0]}.xlsx`;
                    if (contentDisposition) {
                        const filenameMatch = contentDisposition.match(/filename="?(.+)"?/i);
                        if (filenameMatch) {
                            filename = filenameMatch[1];
                        }
                    }

                    a.download = filename;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);

                    this.mostrarMensaje('Calificaciones exportadas correctamente', 'success');
                } else {
                    let errorMessage = `Error HTTP ${response.status}: ${response.statusText}`;
                    try {
                        const errorText = await response.text();
                        if (errorText) {
                            try {
                                const errorData = JSON.parse(errorText);
                                errorMessage = errorData.error || errorData.message || errorText;
                            } catch {
                                errorMessage = errorText;
                            }
                        }
                    } catch (e) {
                        // dejar mensaje por defecto
                    }
                    throw new Error(errorMessage);
                }
            } catch (error) {
                console.error('Error exportando calificaciones (gestor):', error);
                this.mostrarMensaje('Error al exportar las calificaciones: ' + (error.message || 'Error desconocido'), 'error');
            } finally {
                this.exportandoCalificaciones = false;
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
        },

        // Métodos para CSAT por nivel
        getDimensionesUnicasCSAT(nivel) {
            // Solo obtener dimensiones del nivel específico, no de todos los niveles
            const datos = this.estadisticas?.csatDimensionesPorNivel?.[nivel] || [];
            const opcionesSet = new Set();
            
            datos.forEach(dim => {
                if (dim.dimension && dim.dimension.trim() !== '') {
                    opcionesSet.add(dim.dimension);
                }
            });
            
            return Array.from(opcionesSet).sort();
        },

        getCantidadDimensionCSAT(nivel, opcion) {
            const datos = this.estadisticas?.csatDimensionesPorNivel?.[nivel] || [];
            const dimData = datos.find(d => d.dimension === opcion);
            
            if (!dimData) return 0;
            
            return dimData.total || 0;
        },

        getDatosGraficoCSATNivel(nivel) {
            const datos = this.estadisticas?.csatDimensionesPorNivel?.[nivel] || [];
            const labels = [];
            const data = [];
            
            datos.forEach(dim => {
                const cantidad = dim.total || 0;
                
                if (cantidad > 0) {
                    labels.push(this.acortarTexto(dim.dimension, 30));
                    data.push(cantidad);
                }
            });
            
            return { labels, data };
        },

        renderizarGraficosCSATPorNivel() {
            [1, 2, 3, 4].forEach(nivel => {
                const refName = `csatNivel${nivel}Chart`;
                const canvas = this.$refs[refName];
                
                if (!canvas) return;
                
                if (this.csatNivelCharts[refName]) {
                    this.csatNivelCharts[refName].destroy();
                }
                
                const { labels, data } = this.getDatosGraficoCSATNivel(nivel);
                
                // Colores según estructura: 1=Muy Satisfecho, 2=Satisfecho, 3=Insatisfecho, 4=Muy Insatisfecho
                const colores = {
                    1: '#10b981', // Verde para Muy Satisfecho
                    2: '#3b82f6', // Azul para Satisfecho
                    3: '#f59e0b', // Naranja para Insatisfecho
                    4: '#dc2626'  // Rojo para Muy Insatisfecho
                };
                
                if (labels.length === 0) {
                    labels.push('Sin datos');
                    data.push(0);
                }
                
                this.csatNivelCharts[refName] = new Chart(canvas, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Cantidad',
                            data: data,
                            backgroundColor: colores[nivel],
                            borderColor: colores[nivel],
                            borderWidth: 1,
                            borderRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
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
                            },
                            tooltip: {
                                callbacks: {
                                    label: (context) => {
                                        return `${context.parsed.x} respuestas`;
                                    }
                                }
                            }
                        }
                    }
                });
            });
        },

        renderizarGraficoUltimos5DiasCombo() {
            const canvas = this.$refs.ultimos5DiasComboChart;
            if (!canvas) {
                console.warn('Canvas ultimos5DiasComboChart no encontrado');
                return;
            }

            // Verificar que el canvas esté en el DOM
            if (!canvas.parentNode) {
                console.warn('Canvas ultimos5DiasComboChart no está en el DOM');
                return;
            }

            // Destruir gráfico existente de forma segura
            try {
                if (this.ultimos5DiasComboChart) {
                    try {
                        if (this.ultimos5DiasComboChart.animating) {
                            this.ultimos5DiasComboChart.stop();
                        }
                        this.ultimos5DiasComboChart.destroy();
                    } catch (destroyError) {
                        console.warn('Error al destruir ultimos5DiasComboChart:', destroyError);
                    }
                    this.ultimos5DiasComboChart = null;
                }
            } catch (e) {
                console.warn('Error verificando ultimos5DiasComboChart:', e);
                this.ultimos5DiasComboChart = null;
            }

            // Verificar nuevamente que el canvas esté disponible
            if (!canvas || !canvas.parentNode) {
                console.error('Canvas ultimos5DiasComboChart no está disponible después de destruir');
                return;
            }

            const datos = this.tablaUltimosDias;
            if (!datos || datos.length === 0) return;

            const labels = datos.map(row => this.formatearFecha(row.fecha));
            const totalData = datos.map(row => row.total);
            // Usar porcentajes en lugar de cantidades
            const csatData = datos.map(row => row.csatPorcentaje || 0);
            const fcrData = datos.map(row => row.fcrPorcentaje || 0);
            // const npsData = datos.map(row => row.nps); // COMENTADO

            try {
                this.ultimos5DiasComboChart = new Chart(canvas, {
                    type: 'bar',
                    data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Total día',
                            data: totalData,
                            type: 'bar',
                            backgroundColor: 'rgba(99, 102, 241, 0.6)',
                            borderColor: 'rgba(99, 102, 241, 1)',
                            borderWidth: 1,
                            borderRadius: 4,
                            yAxisID: 'y',
                            order: 1
                        },
                        {
                            label: 'CSAT %',
                            data: csatData,
                            type: 'line',
                            borderColor: '#4f46e5',
                            backgroundColor: 'transparent',
                            borderWidth: 3,
                            tension: 0.3,
                            fill: false,
                            pointRadius: 5,
                            pointBackgroundColor: '#4f46e5',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            yAxisID: 'y1',
                            order: 0
                        },
                        {
                            label: 'FCR %',
                            data: fcrData,
                            type: 'line',
                            borderColor: '#10b981',
                            backgroundColor: 'transparent',
                            borderWidth: 3,
                            tension: 0.3,
                            fill: false,
                            pointRadius: 5,
                            pointBackgroundColor: '#10b981',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            yAxisID: 'y1',
                            order: 0
                        },
                        // DATASET NPS COMENTADO
                        // {
                        //     label: 'NPS',
                        //     data: npsData,
                        //     type: 'line',
                        //     borderColor: '#f59e0b',
                        //     backgroundColor: 'transparent',
                        //     borderWidth: 3,
                        //     tension: 0.3,
                        //     fill: false,
                        //     pointRadius: 5,
                        //     pointBackgroundColor: '#f59e0b',
                        //     pointBorderColor: '#ffffff',
                        //     pointBorderWidth: 2,
                        //     yAxisID: 'y1',
                        //     order: 0
                        // }
                    ]
                },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: false,
                        interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        },
                        tooltip: {
                            callbacks: {
                                label: (context) => {
                                    const label = context.dataset.label || '';
                                    const value = context.parsed.y || 0;
                                    const row = datos[context.dataIndex];
                                    
                                    if (context.datasetIndex === 0) {
                                        // Total día: mostrar cantidad y porcentaje de satisfacción
                                        const csatCantidad = row.csat || 0;
                                        const fcrCantidad = row.fcr || 0;
                                        // Usar los porcentajes de satisfacción, no de distribución
                                        const csatPorcentaje = (row.csatPorcentaje !== undefined && row.csatPorcentaje !== null) ? row.csatPorcentaje : 0;
                                        const fcrPorcentaje = (row.fcrPorcentaje !== undefined && row.fcrPorcentaje !== null) ? row.fcrPorcentaje : 0;
                                        
                                    } else {
                                        // CSAT o FCR: mostrar porcentaje de satisfacción
                                        return `${label}: ${value}%`;
                                    }
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Total del día'
                            },
                            grid: {
                                drawOnChartArea: false
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
                                text: '% Satisfacción (CSAT, FCR)' // NPS removido
                            },
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        }
                    },
                    animation: false
                }
                });
                console.log('✅ Chart ultimos5DiasComboChart creado exitosamente');
            } catch (error) {
                console.error('❌ Error creando ultimos5DiasComboChart:', error);
                this.ultimos5DiasComboChart = null;
            }
        },

        
        // MÉTODOS MEJORADOS PARA LOS INDICADORES DE ESTADO
        mostrarSemaforoEstado() {
            return this.mostrarIndicador('csat') || this.mostrarIndicador('fcr');
        },

        getEstadoSemaforo(tipo) {
            const valor = this.getValorIndicador(tipo);
            
            if (tipo === 'csat') {
                if (valor >= 65) return 'verde';
                if (valor >= 40) return 'amarillo';
                return 'rojo';
            } else if (tipo === 'fcr') {
                if (valor >= 70) return 'verde';
                if (valor >= 50) return 'amarillo';
                return 'rojo';
            }
            
            return 'rojo';
        },

        getClaseValor(tipo) {
            const estado = this.getEstadoSemaforo(tipo);
            return `valor-${estado}`;
        },

        getClaseEstado(tipo) {
            const estado = this.getEstadoSemaforo(tipo);
            return `estado-${estado}`;
        },

        getTextoEstado(tipo) {
            const estado = this.getEstadoSemaforo(tipo);
            const textos = {
                'verde': 'Óptimo',
                'amarillo': 'Regular', 
                'rojo': 'Crítico'
            };
            return textos[estado] || 'Sin Datos';
        },

        getIconoEstado(tipo) {
            const estado = this.getEstadoSemaforo(tipo);
            const iconos = {
                'verde': 'fas fa-check',
                'amarillo': 'fas fa-exclamation', 
                'rojo': 'fas fa-times'
            };
            return iconos[estado] || 'fas fa-question';
        },

        getPosicionIndicador(tipo) {
            const valor = this.getValorIndicador(tipo);
            let porcentaje = 0;
            
            if (tipo === 'csat') {
                // Para CSAT: 0-100% mapeado a la barra completa
                porcentaje = Math.min(Math.max(valor, 0), 100);
            } else if (tipo === 'fcr') {
                // Para FCR: 0-100% mapeado a la barra completa
                porcentaje = Math.min(Math.max(valor, 0), 100);
            }
            
            return { left: `calc(${porcentaje}% - 8px)` };
        },

        renderizarGraficoTextosCSAT() {
            const canvas = this.$refs.textosCSATChart;
            if (!canvas) return;

            const ctx = canvas.getContext('2d');
            if (!ctx) return;

            if (this.textosCSATChart) {
                this.textosCSATChart.destroy();
            }

            const datos = [...(this.estadisticas?.textosMasAnotados?.csat || [])].sort(
                (a, b) => (b.cantidad || 0) - (a.cantidad || 0)
            );
            const top10 = datos.slice(0, 10);

            if (top10.length === 0) {
                return;
            }

            const labels = top10.map((item, idx) => {
                const texto = item.texto.length > 30 ? item.texto.substring(0, 30) + '...' : item.texto;
                return `${idx + 1}. ${texto}`;
            });
            const cantidades = top10.map(item => item.cantidad);

            this.textosCSATChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Cantidad de veces',
                        data: cantidades,
                        backgroundColor: 'rgba(79, 70, 229, 0.6)',
                        borderColor: 'rgba(79, 70, 229, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                title: function(context) {
                                    const index = context[0].dataIndex;
                                    return top10[index].texto;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        },

        renderizarGraficoTextosFCR() {
            const canvas = this.$refs.textosFCRChart;
            if (!canvas) return;

            const ctx = canvas.getContext('2d');
            if (!ctx) return;

            if (this.textosFCRChart) {
                this.textosFCRChart.destroy();
            }

            const datos = [...(this.estadisticas?.textosMasAnotados?.fcr || [])].sort(
                (a, b) => (b.cantidad || 0) - (a.cantidad || 0)
            );
            const top10 = datos.slice(0, 10);

            if (top10.length === 0) {
                return;
            }

            const labels = top10.map((item, idx) => {
                const texto = item.texto.length > 30 ? item.texto.substring(0, 30) + '...' : item.texto;
                return `${idx + 1}. ${texto}`;
            });
            const cantidades = top10.map(item => item.cantidad);

            this.textosFCRChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Cantidad de veces',
                        data: cantidades,
                        backgroundColor: 'rgba(16, 185, 129, 0.6)',
                        borderColor: 'rgba(16, 185, 129, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                title: function(context) {
                                    const index = context[0].dataIndex;
                                    return top10[index].texto;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
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

/* Estilos para badge de sede */
.sede-badge {
    background: #059669;
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    margin-left: 1rem;
}

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

.role-badge.gestor {
    background: #059669;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
    margin-top: 0.5rem;
    display: inline-block;
}

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

/* CSAT Niveles Section */
.section-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 2rem;
    text-align: center;
}

.csat-niveles-section {
    margin: 3rem 0;
    padding: 2rem 0;
}

.csat-niveles-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 2rem;
    margin-top: 2rem;
}

.csat-nivel-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.csat-nivel-header {
    padding: 1.25rem 1.5rem;
    font-weight: 600;
    color: white;
    text-align: center;
}

/* Colores según estructura: 1=Muy Satisfecho, 2=Satisfecho, 3=Insatisfecho, 4=Muy Insatisfecho */
.csat-nivel-header.nivel-1 {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%); /* Verde para Muy Satisfecho */
}

.csat-nivel-header.nivel-2 {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); /* Azul para Satisfecho */
}

.csat-nivel-header.nivel-3 {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); /* Naranja para Insatisfecho */
}

.csat-nivel-header.nivel-4 {
    background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); /* Rojo para Muy Insatisfecho */
}

.csat-nivel-header h3 {
    margin: 0;
    font-size: 1.1rem;
    color: white;
}

.csat-nivel-content {
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.chart-container-small {
    position: relative;
    height: 250px;
    width: 100%;
}

.table-container-small {
    overflow-x: auto;
    max-width: 100%;
}

.data-table-small {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}

.data-table-small th,
.data-table-small td {
    padding: 0.75rem 0.5rem;
    text-align: center;
    border: 1px solid #e5e7eb;
}

.data-table-small th {
    background: #f8fafc;
    font-weight: 600;
    color: #374151;
    white-space: nowrap;
    font-size: 0.8rem;
}

.data-table-small td {
    color: #1f2937;
}

.nivel-label {
    font-weight: 600;
    white-space: nowrap;
}

/* Colores según estructura: 1=Muy Satisfecho, 2=Satisfecho, 3=Insatisfecho, 4=Muy Insatisfecho */
.nivel-label.nivel-1 {
    background: #d1fae5; /* Verde claro para Muy Satisfecho */
    color: #065f46;
}

.nivel-label.nivel-2 {
    background: #dbeafe; /* Azul claro para Satisfecho */
    color: #1e40af;
}

.nivel-label.nivel-3 {
    background: #fef3c7; /* Amarillo claro para Insatisfecho */
    color: #92400e;
}

.nivel-label.nivel-4 {
    background: #fee2e2; /* Rojo claro para Muy Insatisfecho */
    color: #991b1b;
}

@media (max-width: 1024px) {
    .csat-niveles-grid {
        grid-template-columns: 1fr;
    }
}

/* Ranking de Áreas por Satisfacción */
.ranking-areas-container {
    padding: 1.5rem 0;
}

.ranking-area-item {
    display: flex;
    align-items: center;
    padding: 1.25rem 1.5rem;
    margin-bottom: 1rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    border-left: 4px solid #e5e7eb;
}

.ranking-area-item:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
    transform: translateY(-2px);
}

.ranking-area-item.top-1 {
    border-left-color: #fbbf24;
    background: linear-gradient(135deg, #fffbeb 0%, #ffffff 100%);
    box-shadow: 0 4px 16px rgba(251, 191, 36, 0.2);
}

.ranking-area-item.top-2 {
    border-left-color: #94a3b8;
    background: linear-gradient(135deg, #f1f5f9 0%, #ffffff 100%);
    box-shadow: 0 4px 16px rgba(148, 163, 184, 0.15);
}

.ranking-area-item.top-3 {
    border-left-color: #cd7f32;
    background: linear-gradient(135deg, #fef3c7 0%, #ffffff 100%);
    box-shadow: 0 4px 16px rgba(205, 127, 50, 0.15);
}

.ranking-position {
    min-width: 60px;
    text-align: center;
    margin-right: 1.5rem;
}

.position-number {
    display: inline-block;
    width: 40px;
    height: 40px;
    line-height: 40px;
    border-radius: 50%;
    background: #f3f4f6;
    color: #6b7280;
    font-weight: 700;
    font-size: 1.1rem;
}

.ranking-area-item.top-1 .position-number {
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(251, 191, 36, 0.4);
}

.ranking-area-item.top-2 .position-number {
    background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(148, 163, 184, 0.4);
}

.ranking-area-item.top-3 .position-number {
    background: linear-gradient(135deg, #cd7f32 0%, #b87333 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(205, 127, 50, 0.4);
}

.ranking-area-info {
    flex: 1;
}

.area-nombre {
    margin: 0 0 0.75rem 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: #1f2937;
}

.estrellas-container {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}

.estrella {
    font-size: 1.5rem;
    color: #d1d5db;
    transition: color 0.2s ease;
    line-height: 1;
}

.estrella.completa {
    color: #fbbf24;
    text-shadow: 0 0 4px rgba(251, 191, 36, 0.5);
}

.estrella.media {
    background: linear-gradient(90deg, #fbbf24 50%, #d1d5db 50%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    position: relative;
}

.estrella.media::after {
    content: '★';
    position: absolute;
    left: 0;
    width: 50%;
    overflow: hidden;
    color: #fbbf24;
    -webkit-text-fill-color: #fbbf24;
}

.promedio-texto {
    margin-left: 0.5rem;
    font-size: 1.1rem;
    font-weight: 600;
    color: #374151;
}

.ranking-stats {
    display: flex;
    gap: 1.5rem;
    margin-top: 0.5rem;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #6b7280;
}

.stat-item i {
    color: #9ca3af;
}

.no-data-message {
    text-align: center;
    padding: 3rem 1rem;
    color: #9ca3af;
}

/* Ranking de Áreas Dual (Satisfacción e Insatisfacción) */
.ranking-areas-dual-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    padding: 1.5rem 0;
}

.ranking-section {
    display: flex;
    flex-direction: column;
}

.ranking-section-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid #e5e7eb;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.ranking-section-title.satisfaccion-title {
    color: #10b981;
    border-bottom-color: #10b981;
}

.ranking-section-title.insatisfaccion-title {
    color: #ef4444;
    border-bottom-color: #ef4444;
}

.ranking-section-title i {
    font-size: 1.1rem;
}

/* Ranking de Áreas por Satisfacción */
.ranking-areas-container {
    padding: 0;
}

.ranking-area-item {
    display: flex;
    align-items: center;
    padding: 1.25rem 1.5rem;
    margin-bottom: 1rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    border-left: 4px solid #e5e7eb;
}

.ranking-area-item:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
    transform: translateY(-2px);
}

.ranking-area-item.top-1 {
    border-left-color: #fbbf24;
    background: linear-gradient(135deg, #fffbeb 0%, #ffffff 100%);
    box-shadow: 0 4px 16px rgba(251, 191, 36, 0.2);
}

.ranking-area-item.top-2 {
    border-left-color: #94a3b8;
    background: linear-gradient(135deg, #f1f5f9 0%, #ffffff 100%);
    box-shadow: 0 4px 16px rgba(148, 163, 184, 0.15);
}

.ranking-area-item.top-3 {
    border-left-color: #cd7f32;
    background: linear-gradient(135deg, #fef3c7 0%, #ffffff 100%);
    box-shadow: 0 4px 16px rgba(205, 127, 50, 0.15);
}

.ranking-position {
    min-width: 60px;
    text-align: center;
    margin-right: 1.5rem;
}

.position-number {
    display: inline-block;
    width: 40px;
    height: 40px;
    line-height: 40px;
    border-radius: 50%;
    background: #f3f4f6;
    color: #6b7280;
    font-weight: 700;
    font-size: 1.1rem;
}

.ranking-area-item.top-1 .position-number {
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(251, 191, 36, 0.4);
}

.ranking-area-item.top-2 .position-number {
    background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(148, 163, 184, 0.4);
}

.ranking-area-item.top-3 .position-number {
    background: linear-gradient(135deg, #cd7f32 0%, #b87333 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(205, 127, 50, 0.4);
}

.ranking-area-info {
    flex: 1;
}

.area-nombre {
    margin: 0 0 0.75rem 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: #1f2937;
}

.estrellas-container {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}

.estrella {
    font-size: 1.5rem;
    color: #d1d5db;
    transition: color 0.2s ease;
    line-height: 1;
}

.estrella.completa {
    color: #fbbf24;
    text-shadow: 0 0 4px rgba(251, 191, 36, 0.5);
}

.estrella.media {
    background: linear-gradient(90deg, #fbbf24 50%, #d1d5db 50%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    position: relative;
}

.estrella.media::after {
    content: '★';
    position: absolute;
    left: 0;
    width: 50%;
    overflow: hidden;
    color: #fbbf24;
    -webkit-text-fill-color: #fbbf24;
}

.promedio-texto {
    margin-left: 0.5rem;
    font-size: 1.1rem;
    font-weight: 600;
    color: #374151;
    }
    
.ranking-stats {
    display: flex;
    gap: 1.5rem;
    margin-top: 0.5rem;
    }
    
.stat-item {
        display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #6b7280;
}

.stat-item i {
    color: #9ca3af;
    }
    
.no-data-message {
    text-align: center;
    padding: 3rem 1rem;
    color: #9ca3af;
}

/* Estilos para ranking de insatisfacción */
.ranking-area-item.insatisfaccion-item.worst-1 {
    border-left-color: #ef4444;
    background: linear-gradient(135deg, #fef2f2 0%, #ffffff 100%);
    box-shadow: 0 4px 16px rgba(239, 68, 68, 0.2);
    }
    
.ranking-area-item.insatisfaccion-item.worst-2 {
    border-left-color: #f87171;
    background: linear-gradient(135deg, #fee2e2 0%, #ffffff 100%);
    box-shadow: 0 4px 16px rgba(248, 113, 113, 0.15);
    }
    
.ranking-area-item.insatisfaccion-item.worst-3 {
    border-left-color: #fb923c;
    background: linear-gradient(135deg, #fff7ed 0%, #ffffff 100%);
    box-shadow: 0 4px 16px rgba(251, 146, 60, 0.15);
    }
    
.ranking-area-item.insatisfaccion-item.worst-1 .position-number {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);
    }
    
.ranking-area-item.insatisfaccion-item.worst-2 .position-number {
    background: linear-gradient(135deg, #f87171 0%, #ef4444 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(248, 113, 113, 0.4);
    }
    
.ranking-area-item.insatisfaccion-item.worst-3 .position-number {
    background: linear-gradient(135deg, #fb923c 0%, #f97316 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(251, 146, 60, 0.4);
}

@media (max-width: 768px) {
    .csat-niveles-section {
        margin: 2rem 0;
        padding: 1rem 0;
    }
    
    .ranking-areas-dual-container {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
    
    .ranking-area-item {
        flex-direction: column;
        align-items: flex-start;
        padding: 1rem;
    }
    
    .ranking-position {
        margin-right: 0;
        margin-bottom: 1rem;
    }
    
    .area-nombre {
        font-size: 1.1rem;
    }
    
    .estrella {
        font-size: 1.25rem;
    }
    
    .csat-niveles-grid {
        gap: 1.5rem;
    }
    
    .chart-container-small {
        height: 200px;
    }
    
    .data-table-small {
        font-size: 0.75rem;
    }
    
    .data-table-small th,
    .data-table-small td {
        padding: 0.5rem 0.25rem;
    }
}

/* NUEVOS ESTILOS MEJORADOS PARA INDICADORES DE ESTADO */
.estado-indicadores-section {
    margin: 3rem 0;
    padding: 2rem 0;
}

.section-header {
    text-align: center;
    margin-bottom: 2.5rem;
}

.section-header h2 {
    font-size: 1.75rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 0.5rem;
}

.section-header p {
    color: #6b7280;
    font-size: 1rem;
    margin: 0;
}

.estado-indicadores-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 2rem;
}

.estado-indicador-card {
    width: -webkit-fill-available;
    background: white;
    padding: 2rem;
    border-radius: 16px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    border: 1px solid #e5e7eb;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.estado-indicador-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #ef4444, #f59e0b, #10b981);
}

.estado-indicador-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.indicador-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.indicador-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}

.indicador-titulo h3 {
    margin: 0 0 0.25rem 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: #1f2937;
}

.indicador-titulo p {
    margin: 0;
    color: #6b7280;
    font-size: 0.9rem;
}

.indicador-content {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.indicador-valor-container {
    text-align: center;
    padding: 1rem;
    background: #f8fafc;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
}

.indicador-valor {
    font-size: 3rem;
    font-weight: 800;
    line-height: 1;
    margin-bottom: 0.5rem;
    transition: all 0.3s ease;
}

.indicador-valor.valor-verde {
    color: #059669;
    text-shadow: 0 2px 4px rgba(5, 150, 105, 0.2);
}

.indicador-valor.valor-amarillo {
    color: #d97706;
    text-shadow: 0 2px 4px rgba(217, 119, 6, 0.2);
}

.indicador-valor.valor-rojo {
    color: #dc2626;
    text-shadow: 0 2px 4px rgba(220, 38, 38, 0.2);
}

.indicador-estado {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.indicador-estado.estado-verde {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #a7f3d0;
}

.indicador-estado.estado-amarillo {
    background: #fef3c7;
    color: #92400e;
    border: 1px solid #fde68a;
}

.indicador-estado.estado-rojo {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

.indicador-progress {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
}

.progress-labels {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
    font-size: 0.8rem;
    color: #6b7280;
    font-weight: 500;
}

.progress-bar {
    position: relative;
    height: 24px;
    background: #f3f4f6;
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 0.5rem;
    display: flex;
}

.progress-segment {
    flex: 1;
    height: 100%;
    transition: all 0.3s ease;
    opacity: 0.3;
}

.progress-segment.active {
    opacity: 1;
    box-shadow: inset 0 0 10px rgba(0, 0, 0, 0.1);
}

.segment-rojo {
    background: linear-gradient(90deg, #ef4444, #f87171);
}

.segment-amarillo {
    background: linear-gradient(90deg, #f59e0b, #fbbf24);
}

.segment-verde {
    background: linear-gradient(90deg, #10b981, #34d399);
}

.progress-indicator {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    transition: left 0.5s ease;
    z-index: 10;
}

.indicator-dot {
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: white;
    border: 3px solid #1f2937;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

.progress-ranges {
    display: flex;
    justify-content: space-between;
    font-size: 0.75rem;
    color: #9ca3af;
    font-weight: 500;
}

.badge-csat {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    background-color: rgba(79, 70, 229, 0.1);
    color: #4F46E5;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 600;
}

.badge-fcr {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    background-color: rgba(16, 185, 129, 0.1);
    color: #10b981;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 600;
}

/* Responsive */
@media (max-width: 768px) {
    .estado-indicadores-grid {
        grid-template-columns: 1fr;
    }
    
    .estado-indicador-card {
        padding: 1.5rem;
    }
    
    .indicador-valor {
        font-size: 2.5rem;
    }
    
    .indicador-header {
        flex-direction: column;
        text-align: center;
        gap: 0.75rem;
    }
    
    .indicador-icon {
        width: 50px;
        height: 50px;
        font-size: 1.25rem;
    }
}

@media (max-width: 480px) {
    .estado-indicadores-section {
        margin: 2rem 0;
        padding: 1rem 0;
    }
    
    .section-header h2 {
        font-size: 1.5rem;
    }
    
    .estado-indicador-card {
        padding: 1.25rem;
    }
    
    .indicador-valor {
        font-size: 2rem;
    }
    
    .progress-bar {
        height: 20px;
    }
    
    .indicator-dot {
        width: 14px;
        height: 14px;
    }
}
</style>