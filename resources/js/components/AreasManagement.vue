<template>
    <div class="areas-management">
        <!-- Header Section -->
        <div class="header-section">
            <div class="header-content">
                <div class="header-info">
                    <h1 class="page-title">
                        <i class="fas fa-building"></i>
                        Gestión de Áreas
                    </h1>
                    <p class="page-subtitle">
                        {{ sedeActual 
                            ? `Mostrando áreas de la sede ${sedeActual.nombre}` 
                            : 'Mostrando todas las áreas del sistema' 
                        }}
                    </p>
                </div>
                <button @click="showCreateModal" class="btn-primary">
                    <i class="fas fa-plus"></i>
                    Nueva Área
                </button>
            </div>
        </div>      

        <!-- Filters Section -->
                <div class="filters-section">
                    <div class="filters-container">
                        <div class="filter-group" style="flex-direction: row;">
                            <label class="filter-label">
                                <i class="fas fa-building"></i>
                                Área
                            </label>
                            <select v-model="filtroNombreArea" class="form-select">
                                <option value="">Todas</option>
                                <option v-for="item in nombresAreasUnicos" :key="item.nombre" :value="item.nombre">
                                    {{ item.display }}
                                </option>
                            </select>
                        </div>                        
                        <div class="filter-stats">
                            <span class="stats-item">
                                <i class="fas fa-list"></i>
                                {{ areasFiltradas.length }} áreas
                            </span>
                        </div>
                    </div>
                </div>

        <!-- Content Section -->
        <div class="content-section">
            <!-- Loading State -->
            <div v-if="loadingAreas" class="loading-container">
                <div class="loading-spinner"></div>
                <p class="loading-text">Cargando áreas...</p>
            </div>

            <!-- Empty State -->
            <div v-else-if="areas.length === 0" class="empty-container">
                <div class="empty-icon">
                    <i class="fas fa-building"></i>
                </div>
                <h3 class="empty-title">
                    {{ sedeActual ? 'No hay áreas en esta sede' : 'No hay áreas disponibles' }}
                </h3>
                <p class="empty-description">
                    {{ sedeActual 
                        ? `No se encontraron áreas para la sede ${sedeActual.nombre}` 
                        : 'Comienza creando la primera área del sistema.' 
                    }}
                </p>
                <button @click="showCreateModal" class="btn-primary">
                    <i class="fas fa-plus"></i>
                    Crear Área
                </button>
            </div>

            <!-- Areas Grid -->
            <div v-else class="areas-grid">
                <div v-for="area in areasFiltradas" :key="area.id" class="area-card">
                    <div class="card-header">
                        <div class="area-code">{{ area.codigo }}</div>
                        <div class="area-status">
                            <span :class="['status-badge', area.is_active ? 'active' : 'inactive']">
                                <i :class="area.is_active ? 'fas fa-check-circle' : 'fas fa-pause-circle'"></i>
                                {{ area.is_active ? 'Activa' : 'Inactiva' }}
                            </span>
                        </div>
                    </div>

                    <div class="card-content">                        
                        <div class="area-meta">
                            <div class="meta-item">
                                <i class="fas fa-book-open"></i>
                                <span> <strong>Nombre:</strong> {{ area.nombre }}</span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <span> <strong>Sede:</strong> {{ getSedeName(area.sede_id) }}</span>
                            </div>
                        </div>

                        <!-- 🔥 NUEVO: Badges de tipos de calificación -->
                        <div class="area-rating-types" v-if="area.permite_csat || area.permite_nps || area.permite_fcr">
                            <span v-if="area.permite_csat" class="rating-badge csat">
                                <i class="fas fa-smile"></i>
                                CSAT
                            </span>
                            <span v-if="area.permite_nps" class="rating-badge nps">
                                <i class="fas fa-chart-line"></i>
                                NPS
                            </span>
                            <span v-if="area.permite_fcr" class="rating-badge fcr">
                                <i class="fas fa-hand-peace"></i>
                                FCR
                            </span>
                        </div>

                        <div class="area-stats">
                            <span class="stat-item">
                                <i class="fas fa-question-circle"></i>
                                {{ area.preguntas_count || 0 }} preguntas
                            </span>
                        </div>
                    </div>

                    <div class="card-actions">
                        <button @click="editArea(area)" class="action-btn edit" title="Editar">
                            <svg width="16" height="16" viewBox="0 0 512 512" fill="#007bff" xmlns="http://www.w3.org/2000/svg">
                                <path d="M410.3 231l11.3-11.3-33.9-33.9-62.1-62.1L291.7 89.8l-11.3 11.3-22.6 22.6L58.6 322.9c-10.4 10.4-18 23.3-22.2 37.4L1 480.1c-2.5 8.4-.2 17.5 6.1 23.7s15.3 8.5 23.7 6.1l119.8-35.8c14.1-4.2 27-11.8 37.4-22.2L410.3 231zm0 0l-62.1-62.1L291.7 89.8l62.1 62.1 56.5 56.5z"/>
                            </svg>
                        </button>
                        <button @click="toggleAreaStatus(area)" 
                                :title="area.is_active ? 'Desactivar' : 'Activar'"
                                :class="['action-btn', area.is_active ? 'deactivate' : 'activate']">
                            <svg v-if="area.is_active" width="16" height="16" viewBox="0 0 384 512" fill="#ffc107" xmlns="http://www.w3.org/2000/svg">
                                <path d="M192 0C86 0 0 86 0 192v128c0 106 86 192 192 192s192-86 192-192V192C384 86 298 0 192 0z"/>
                            </svg>
                            <svg v-else width="16" height="16" viewBox="0 0 384 512" fill="#28a745" xmlns="http://www.w3.org/2000/svg">
                                <path d="M73 39c-14.8-9.1-33.4-9.4-48.5-.9S0 62.6 0 80V432c0 17.4 9.4 33.4 24.5 41.9s33.7 8.1 48.5-.9L361 297c14.3-8.7 23-24.2 23-41s-8.7-32.2-23-41L73 39z"/>
                            </svg>
                        </button>
                        <button @click="deleteArea(area)" class="action-btn delete" title="Eliminar">
                            <svg width="16" height="16" viewBox="0 0 448 512" fill="#dc3545" xmlns="http://www.w3.org/2000/svg">
                                <path d="M135.2 17.7L128 32H32C14.3 32 0 46.3 0 64S14.3 96 32 96H416c17.7 0 32-14.3 32-32s-14.3-32-32-32H320l-7.2-14.3C307.4 6.8 296.3 0 288 0H160c-8.3 0-19.4 6.8-24.8 17.7zM32 128H416V448c0 35.3-28.7 64-64 64H96c-35.3 0-64-28.7-64-64V128zm96 64c-8.8 0-16 7.2-16 16V432c0 8.8 7.2 16 16 16s16-7.2 16-16V208c0-8.8-7.2-16-16-16zm96 0c-8.8 0-16 7.2-16 16V432c0 8.8 7.2 16 16 16s16-7.2 16-16V208c0-8.8-7.2-16-16-16zm96 0c-8.8 0-16 7.2-16 16V432c0 8.8 7.2 16 16 16s16-7.2 16-16V208c0-8.8-7.2-16-16-16z"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
            <div class="modal-container">
                <div class="modal-header">
                    <h2 class="modal-title">
                        <i :class="editingArea ? 'fas fa-edit' : 'fas fa-plus'"></i>
                        {{ editingArea ? 'Editar Área' : 'Nueva Área' }}
                    </h2>
                    <button @click="closeModal" class="modal-close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form @submit.prevent="saveArea" class="modal-form">
                    <div class="form-section">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-signature"></i>
                                    Nombre del Área *
                                </label>
                                <input 
                                    type="text" 
                                    v-model="areaForm.nombre" 
                                    placeholder="Ej: Atención al Cliente"
                                    class="form-input"
                                    required
                                    maxlength="100"
                                >
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-code"></i>
                                    Código *
                                </label>
                                <input 
                                    type="text" 
                                    v-model="areaForm.codigo" 
                                    placeholder="Ej: ATENCION"
                                    class="form-input"
                                    required
                                    maxlength="10"
                                    style="text-transform: uppercase;"
                                >
                                <small class="form-help">Máximo 10 caracteres.</small>
                            </div>
                        </div>

                        <!-- 🔥 NUEVO: Selección múltiple de Sedes -->
                        <div class="form-group full-width">
                            <label class="form-label">
                                <i class="fas fa-map-marker-alt"></i>
                                Sedes para esta Área * <span class="text-sm">(selecciona una o más)</span>
                            </label>
                            <div class="checkbox-grid">
                                <label v-for="sede in sedes" :key="sede.id" class="checkbox-item" :class="{ 'disabled': editingArea }">
                                    <input 
                                        type="checkbox" 
                                        :value="sede.id"
                                        v-model="areaForm.sedesSeleccionadas"
                                        :disabled="editingArea"
                                    >
                                    <span class="checkbox-label">{{ sede.nombre }}</span>
                                </label>
                            </div>
                            <small v-if="editingArea" style="color: #6b7280; display: block; margin-top: 0.5rem;">
                                <i class="fas fa-info-circle"></i> No se puede cambiar la sede en modo edición
                            </small>
                        </div>

                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-lock"></i>
                                Contraseña
                            </label>
                            <input 
                                type="text" 
                                v-model="areaForm.password"
                                placeholder="Dejar vacío para generar automáticamente"
                                class="form-input"
                                maxlength="50"
                            >
                            <small class="form-help">
                                Si se deja vacío, se generará como: "código2025"
                            </small>
                        </div>

                        <!-- 🔥 NUEVO: Indicadores de Calificación -->
                        <div class="form-group full-width">
                            <label class="form-label">
                                <i class="fas fa-tags"></i>
                                Tipos de Calificación Disponibles
                            </label>
                            <small class="form-help" style="display: block; margin-bottom: 1rem; color: #6b7280;">
                                Selecciona qué tipos de calificación puede usar esta área
                            </small>
                            <div class="indicadores-grid">
                                <label class="indicador-checkbox">
                                    <input type="checkbox" v-model="areaForm.permite_csat">
                                    <div class="checkbox-custom">
                                        <i class="fas fa-smile"></i>
                                        <span class="checkbox-label">CSAT (Caritas)</span>
                                    </div>
                                </label>
                                
                                <label class="indicador-checkbox">
                                    <input type="checkbox" v-model="areaForm.permite_nps">
                                    <div class="checkbox-custom">
                                        <i class="fas fa-chart-line"></i>
                                        <span class="checkbox-label">NPS (0-10)</span>
                                    </div>
                                </label>
                                
                                <label class="indicador-checkbox">
                                    <input type="checkbox" v-model="areaForm.permite_fcr">
                                    <div class="checkbox-custom">
                                        <i class="fas fa-hand-peace"></i>
                                        <span class="checkbox-label">FCR (Manitas)</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="checkbox-container">
                                <input type="checkbox" v-model="areaForm.is_active">
                                <span class="checkbox-checkmark"></span>
                                <span class="checkbox-label">Área activa</span>
                            </label>
                        </div>
                    </div>

                    <div class="modal-actions">
                        <button type="button" @click="closeModal" class="btn-secondary">
                            <i class="fas fa-times"></i>
                            Cancelar
                        </button>
                        <button type="submit" :disabled="savingArea" class="btn-primary">
                            <span v-if="savingArea">
                                <i class="fas fa-spinner fa-spin"></i> Guardando...
                            </span>
                            <span v-else>
                                {{ editingArea ? 'Actualizar' : 'Crear' }} Área
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
import Swal from 'sweetalert2';
export default {
    name: 'AreasManagement',
    data() {
        return {
            sedes: [],
            areas: [],
            loadingAreas: false,
            showModal: false,
            editingArea: null,
            savingArea: false,
            sedeActual: null, // ✅ Sede actual desde el store
            // 🔎 Filtros
            filtroNombreArea: '',
            areaForm: {
                nombre: '',
                codigo: '',
                password: '',
                sede_id: '',
                sedesSeleccionadas: [], // 🔥 NUEVO: Array de sedes seleccionadas
                is_active: true,
                // 🔥 NUEVO: Indicadores de calificación
                permite_csat: false,
                permite_nps: false,
                permite_fcr: false
            }
        }
    },
    computed: {
        // Lista de nombres únicos de áreas (para el select)
        nombresAreasUnicos() {
            const areasPorNombre = {};
            this.areas.forEach(a => {
                if (!areasPorNombre[a.nombre]) {
                    areasPorNombre[a.nombre] = {
                        display: `${a.codigo} - ${a.nombre}`,
                        nombre: a.nombre
                    };
                }
            });
            return Object.values(areasPorNombre).sort((a, b) => a.display.localeCompare(b.display));
        },
        // Resultado final a mostrar en la grilla
        areasFiltradas() {
            // Filtro por nombre de área (exacto por opción seleccionada)
            if (this.filtroNombreArea) {
                return this.areas.filter(a => a.nombre === this.filtroNombreArea);
            }
            return this.areas;
        }
    },
    async mounted() {
        console.log('🚀 AreasManagement montado - INICIANDO SISTEMA DE EVENTOS');
        
        // ✅ CARGAR DATOS INICIALES
        await this.loadSedes();
        
        // ✅ SUSCRIBIRSE A CAMBIOS DE SEDE
        this.suscribirACambiosDeSede();
        
        // ✅ CARGAR ÁREAS INICIALES
        await this.loadAreas();
    },
    beforeUnmount() {
        // ✅ LIMPIAR SUSCRIPCIÓN AL DESTRUIR EL COMPONENTE
        if (this.unsubscribe) {
            this.unsubscribe();
        }
    },
    methods: {
        // ✅ SUSCRIBIRSE A CAMBIOS DE SEDE
        suscribirACambiosDeSede() {
            if (window.SedeStore) {
                // Suscribirse al store
                this.unsubscribe = window.SedeStore.subscribe((nuevaSede) => {
                    console.log('🔄 AreasManagement recibió cambio de sede:', nuevaSede);
                    this.sedeActual = nuevaSede;
                    this.loadAreas(); // Recargar áreas inmediatamente
                });
                
                // Establecer sede actual inicial
                this.sedeActual = window.SedeStore.sedeActual;
                console.log('📌 Sede actual inicial:', this.sedeActual);
            }
            
            // También suscribirse al event bus por si acaso
            if (window.EventBus) {
                window.EventBus.on('sede-cambiada', (sede) => {
                    console.log('📡 AreasManagement recibió evento de sede:', sede);
                    this.sedeActual = sede;
                    this.loadAreas();
                });
            }
        },

        async loadSedes() {
            try {
                console.log('📡 Cargando sedes...');
                const response = await fetch('/api/sedes');
                if (response.ok) {
                    this.sedes = await response.json();
                    console.log('✅ Sedes cargadas:', this.sedes.length);
                }
            } catch (error) {
                console.error('❌ Error cargando sedes:', error);
            }
        },

        async loadAreas() {
            this.loadingAreas = true;
            try {
                let url = '/api/areas';
                const sedeId = this.sedeActual ? this.sedeActual.id : null;
                
                if (sedeId) {
                    url += `?sede_id=${sedeId}`;
                    console.log('🎯 FILTRANDO áreas por sede_id:', sedeId);
                } else {
                    console.log('🌍 Mostrando TODAS las áreas');
                }
                
                console.log('📡 Cargando áreas - URL:', url);
                const response = await fetch(url);
                
                if (response.ok) {
                    const data = await response.json();
                    this.areas = data;
                    console.log('✅ Áreas cargadas:', this.areas.length, 'para sede:', sedeId);
                    
                    if (this.areas.length > 0) {
                        this.areas.forEach(a => {
                            console.log(`   - Área ${a.id}: "${a.nombre}" - Sede: ${a.sede_id}`);
                        });
                    } else {
                        console.log('ℹ️ No hay áreas para esta sede');
                    }
                } else {
                    console.error('❌ Error cargando áreas:', response.status);
                    throw new Error('Error al cargar las áreas');
                }
            } catch (error) {
                console.error('❌ Error loading areas:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al cargar las áreas: ' + error.message,
                    confirmButtonColor: '#ef4444'
                });
            } finally {
                this.loadingAreas = false;
            }
        },

        getSedeName(sedeId) {
            const sede = this.sedes.find(s => s.id === sedeId);
            return sede ? sede.nombre : 'N/A';
        },

        showCreateModal() {
            this.editingArea = null;
            this.areaForm = {
                nombre: '',
                codigo: '',
                password: '',
                sede_id: '',
                sedesSeleccionadas: this.sedeActual ? [this.sedeActual.id] : [], // 🔥 NUEVO: Pre-seleccionar sede actual
                is_active: true,
                // 🔥 NUEVO: Indicadores por defecto
                permite_csat: false,
                permite_nps: false,
                permite_fcr: false
            };
            this.showModal = true;
        },

        editArea(area) {
            this.editingArea = area;
            
            console.log(`📝 Editando área: ${area.nombre} (${area.codigo}) - Sede: ${area.sede_id}`);
            
            this.areaForm = {
                nombre: area.nombre,
                codigo: area.codigo,
                password: area.password || '',
                sede_id: area.sede_id || '',
                sedesSeleccionadas: [area.sede_id || ''], // 🔥 Solo la sede de ESTA área específica
                is_active: !!area.is_active,
                // 🔥 Cargar indicadores de ESTA instancia específica
                permite_csat: !!area.permite_csat,
                permite_nps: !!area.permite_nps,
                permite_fcr: !!area.permite_fcr
            };
            
            console.log('✅ Datos del formulario:', this.areaForm);
            this.showModal = true;
        },

        async saveArea() {
            if (!this.validateForm()) return;

            this.savingArea = true;
            console.log('💾 Guardando área - Modo:', this.editingArea ? 'EDICIÓN' : 'CREACIÓN');
            console.log('📍 Sedes seleccionadas:', this.areaForm.sedesSeleccionadas);
            
            try {
                // 🔥 NUEVO: Si es creación y hay múltiples sedes, crear área en cada sede
                if (!this.editingArea && this.areaForm.sedesSeleccionadas.length > 0) {
                    console.log(`🆕 Creando área en ${this.areaForm.sedesSeleccionadas.length} sede(s)`);
                    let areasCreadas = 0;
                    let primerArea = null;
                    
                    for (const sedeId of this.areaForm.sedesSeleccionadas) {
                        const dataToSend = {
                            nombre: this.areaForm.nombre.trim(),
                            codigo: this.areaForm.codigo.trim().toUpperCase(),
                            password: this.areaForm.password.trim(),
                            sede_id: sedeId,
                            is_active: !!this.areaForm.is_active,
                            permite_csat: !!this.areaForm.permite_csat,
                            permite_nps: !!this.areaForm.permite_nps,
                            permite_fcr: !!this.areaForm.permite_fcr
                        };
                        console.log(`📤 Creando área para sede ${sedeId}:`, dataToSend);

                        const response = await fetch('/api/areas', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Accept': 'application/json'
                            },
                            credentials: 'include',
                            body: JSON.stringify(dataToSend)
                        });

                        if (response.ok) {
                            const result = await response.json();
                            if (!primerArea) primerArea = result;
                            areasCreadas++;
                        } else {
                            const errorData = await response.json();
                            throw new Error(errorData?.error || errorData?.message || `Error ${response.status}`);
                        }
                    }

                    console.log('✅ Áreas creadas:', areasCreadas);
                    this.closeModal();
                    await this.loadAreas();
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: `Área creada en ${areasCreadas} sede(s) correctamente`,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    
                } else if (this.editingArea) {
                    // 🔥 MODO EDICIÓN: Solo actualizar el registro actual de esta área específica
                    console.log('✏️ Editando área existente (ID:', this.editingArea.id, ')');
                    
                    const dataToSend = {
                        nombre: this.areaForm.nombre.trim(),
                        codigo: this.areaForm.codigo.trim().toUpperCase(),
                        password: this.areaForm.password.trim(),
                        sede_id: this.editingArea.sede_id, // 🔥 IMPORTANTE: Mantener la sede original
                        is_active: !!this.areaForm.is_active,
                        permite_csat: !!this.areaForm.permite_csat,
                        permite_nps: !!this.areaForm.permite_nps,
                        permite_fcr: !!this.areaForm.permite_fcr
                    };
                    
                    console.log('📤 Enviando datos de actualización:', dataToSend);
                    
                    const response = await fetch(`/api/areas/${this.editingArea.id}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        credentials: 'include',
                        body: JSON.stringify(dataToSend)
                    });
                    
                    if (response.ok) {
                        const result = await response.json();
                        console.log('✅ Área actualizada:', result);
                        
                        this.closeModal();
                        await this.loadAreas();
                        Swal.fire({
                            title: 'Área actualizada correctamente',
                            icon: 'success',
                            timer: 1500
                        });
                    } else {
                        const errorData = await response.json();
                        throw new Error(errorData?.error || errorData?.message || `Error ${response.status}`);
                    }
                    
                } else {
                    // 🔥 MODO CREACIÓN (una sola sede)
                    const url = '/api/areas';
                    
                    const dataToSend = {
                        nombre: this.areaForm.nombre.trim(),
                        codigo: this.areaForm.codigo.trim().toUpperCase(),
                        password: this.areaForm.password.trim(),
                        sede_id: this.areaForm.sedesSeleccionadas[0],
                        is_active: !!this.areaForm.is_active,
                        permite_csat: !!this.areaForm.permite_csat,
                        permite_nps: !!this.areaForm.permite_nps,
                        permite_fcr: !!this.areaForm.permite_fcr
                    };
                    
                    console.log('📤 Enviando datos de creación:', dataToSend);

                    const response = await fetch('/api/areas', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        credentials: 'include',
                        body: JSON.stringify(dataToSend)
                    });

                    if (response.ok) {
                        const result = await response.json();
                        console.log('✅ Área creada:', result);
                        
                        this.closeModal();
                        await this.loadAreas();
                        Swal.fire({
                            icon: 'success',
                            title: '¡Éxito!',
                            text: 'Área creada correctamente',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        const errorData = await response.json();
                        throw new Error(errorData?.error || errorData?.message || `Error ${response.status}`);
                    }
                }
            } catch (error) {
                console.error('❌ Error guardando área:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error: ' + error.message,
                    confirmButtonColor: '#ef4444'
                });
            } finally {
                this.savingArea = false;
            }
        },

        validateForm() {
            if (!this.areaForm.nombre.trim()) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Campo requerido',
                    text: 'Por favor ingresa el nombre del área',
                    confirmButtonColor: '#3b82f6'
                });
                return false;
            }
            if (!this.areaForm.codigo.trim()) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Campo requerido',
                    text: 'Por favor ingresa el código del área',
                    confirmButtonColor: '#3b82f6'
                });
                return false;
            }
            // 🔥 NUEVO: Validar sedes seleccionadas
            if (!this.areaForm.sedesSeleccionadas || this.areaForm.sedesSeleccionadas.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Selección requerida',
                    text: 'Por favor selecciona al menos una sede',
                    confirmButtonColor: '#3b82f6'
                });
                return false;
            }
            return true;
        },

        async toggleAreaStatus(area) {
            const result = await Swal.fire({
                title: '¿Estás seguro?',
                text: `¿Estás seguro de ${area.is_active ? 'desactivar' : 'activar'} esta área?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3b82f6',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Sí',
                cancelButtonText: 'Cancelar'
            });

            if (!result.isConfirmed) return;

            try {
                const response = await fetch(`/api/areas/${area.id}/toggle`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });

                if (response.ok) {
                    await this.loadAreas();
                } else {
                    throw new Error('Error al cambiar estado');
                }
            } catch (error) {
                console.error('Error toggling area:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al cambiar el estado del área: ' + error.message,
                    confirmButtonColor: '#ef4444'
                });
            }
        },

        async deleteArea(area) {
            const result = await Swal.fire({
                title: '¿Estás seguro?',
                text: 'Esta acción no se puede deshacer.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            });

            if (!result.isConfirmed) return;

            try {
                const response = await fetch(`/api/areas/${area.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });

                if (response.ok) {
                    await this.loadAreas();
                    Swal.fire({
                        icon: 'success',
                        title: '¡Eliminado!',
                        text: 'Área eliminada correctamente',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    const errorData = await response.json();
                    throw new Error(errorData.error || errorData.message || 'Error al eliminar');
                }
            } catch (error) {
                console.error('Error deleting area:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al eliminar el área: ' + error.message,
                    confirmButtonColor: '#ef4444'
                });
            }
        },

        closeModal() {
            this.showModal = false;
            this.editingArea = null;
            this.areaForm = {
                nombre: '',
                codigo: '',
                password: '',
                sede_id: '',
                is_active: true
            };
        }
    }
}
</script>

<style scoped>
/* ESTILOS GENERALES - TEMA VERDE */
.areas-management {
    min-height: 100vh;
    background: #f8f9fa;
}

/* HEADER SECTION - VERDE */
.header-section {
    background: linear-gradient(135deg, #059669 0%, #10b981 100%);
    color: white;
    padding: 2rem 0;
}

.header-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.page-title {
    font-size: 2rem;
    font-weight: 700;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.page-subtitle {
    margin: 0.5rem 0 0 0;
    opacity: 0.9;
}

.sede-badge {
    background: rgba(255, 255, 255, 0.2);
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.9rem;
}

/* BOTONES - VERDE */
.btn-primary {
    background: #059669;
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    background: #047857;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
}

.btn-secondary {
    background: #6b7280;
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
}

.btn-secondary:hover {
    background: #4b5563;
}

/* FILTERS SECTION */
.filters-section {
    background: white;
    border-bottom: 1px solid #e5e7eb;
    padding: 1.5rem 0;
}

.filters-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 2rem;
    display: flex;
    justify-content: flex-end;
}

.filter-stats {
    margin-left: auto;
}

.stats-item {
    background: #f3f4f6;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* CONTENT SECTION */
.content-section {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}

/* LOADING STATES */
.loading-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 2rem;
    color: #6b7280;
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #e5e7eb;
    border-top: 4px solid #059669;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 1rem;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.loading-text {
    font-size: 1.125rem;
    font-weight: 500;
}

/* EMPTY STATE */
.empty-container {
    text-align: center;
    padding: 4rem 2rem;
    color: #6b7280;
}

.empty-icon {
    font-size: 4rem;
    color: #d1d5db;
    margin-bottom: 1rem;
}

.empty-title {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #374151;
}

.empty-description {
    font-size: 1.125rem;
    margin-bottom: 2rem;
}

/* AREAS GRID */
.areas-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 1.5rem;
}

.area-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.area-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    border-color: #059669;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    background: #f8fafc;
    border-bottom: 1px solid #e5e7eb;
}

.area-code {
    color: #059669;
    font-size: 0.875rem;
    font-weight: 700;
    background: rgba(5, 150, 105, 0.1);
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.status-badge.active {
    background: #d1fae5;
    color: #065f46;
}

.status-badge.inactive {
    background: #fef3c7;
    color: #92400e;
}

.card-content {
    padding: 1.5rem;
}

.area-name {
    font-size: 1.125rem;
    font-weight: 600;
    color: #111827;
    margin-bottom: 1rem;
    line-height: 1.4;
}

.area-meta {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #6b7280;
    font-size: 0.875rem;
}

.area-stats {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
    margin-top: 0.5rem;
}

.stat-item {
    background: #e0f2fe;
    color: #0369a1;
    padding: 0.375rem 0.75rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.375rem;
}

/* 🔥 NUEVO: Badges de tipos de calificación en las tarjetas */
.area-rating-types {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    margin-top: 0.75rem;
    margin-bottom: 1rem;
}

.rating-badge {
    padding: 0.375rem 0.625rem;
    border-radius: 6px;
    font-size: 0.6875rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
}

.rating-badge.csat {
    background: #dbeafe;
    color: #1e40af;
}

.rating-badge.nps {
    background: #fef3c7;
    color: #92400e;
}

.rating-badge.fcr {
    background: #fce7f3;
    color: #9f1239;
}

.rating-badge i {
    font-size: 0.75rem;
}

.card-actions {
    display: flex;
    gap: 0.5rem;
    padding: 1rem 1.5rem;
    background: #f8fafc;
    border-top: 1px solid #e5e7eb;
}

.action-btn {
    background: transparent;
    border: none;
    padding: 0.5rem;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6b7280;
}

.action-btn:hover {
    background: #f3f4f6;
    color: #374151;
}

.action-btn.edit:hover {
    background: #dbeafe;
    color: #1d4ed8;
}

.action-btn.deactivate:hover {
    background: #fef2f2;
    color: #dc2626;
}

.action-btn.activate:hover {
    background: #f0fdf4;
    color: #16a34a;
}

.action-btn.delete:hover {
    background: #fef2f2;
    color: #dc2626;
}

/* MODALES */
.modal-overlay {
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
    padding: 2rem;
}

.modal-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    max-width: 600px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem 2rem;
    border-bottom: 1px solid #e5e7eb;
}

.modal-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #111827;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.modal-close {
    background: transparent;
    border: none;
    padding: 0.5rem;
    border-radius: 6px;
    cursor: pointer;
    color: #6b7280;
    transition: all 0.3s ease;
}

.modal-close:hover {
    background: #f3f4f6;
    color: #374151;
}

.modal-form {
    padding: 2rem;
}

.form-section {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-label {
    font-weight: 600;
    color: #374151;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.form-input, .form-select {
    padding: 0.75rem 1rem;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.875rem;
    transition: all 0.3s ease;
    background: white;
}

.form-input:focus, .form-select:focus {
    outline: none;
    border-color: #059669;
    box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
}

.form-help {
    color: #6b7280;
    font-size: 0.75rem;
    margin-top: 0.25rem;
}

/* CHECKBOX */
.checkbox-container {
    justify-content: center;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    cursor: pointer;
    font-weight: 500;
}

.checkbox-container input[type="checkbox"] {
    display: none;
}

.checkbox-checkmark {
    width: 20px;
    height: 20px;
    border: 2px solid #d1d5db;
    border-radius: 4px;
    position: relative;
    transition: all 0.3s ease;
}

.checkbox-container input[type="checkbox"]:checked + .checkbox-checkmark {
    background: #059669;
    border-color: #059669;
}

.checkbox-container input[type="checkbox"]:checked + .checkbox-checkmark:after {
    content: '✓';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: white;
    font-size: 12px;
    font-weight: bold;
}

.checkbox-label {
    color: #374151;
}

.modal-actions {
    justify-self: center;
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    padding-top: 1.5rem;
    border-top: 1px solid #e5e7eb;
    margin-top: 1.5rem;
}

/* RESPONSIVE */
@media (max-width: 768px) {
    .header-content {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .areas-grid {
        grid-template-columns: 1fr;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .modal-container {
        margin: 1rem;
        max-height: calc(100vh - 2rem);
    }
    
    .modal-actions {
        flex-direction: column;
    }
}

/* ANIMACIONES */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.modal-container {
    animation: fadeIn 0.3s ease;
}

.area-card {
    animation: fadeIn 0.5s ease;
}

/* 🔥 NUEVO: Estilos para Indicadores de Calificación */
.indicadores-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-top: 1rem;
}

.indicador-checkbox {
    cursor: pointer;
}

.indicador-checkbox input[type="checkbox"] {
    display: none;
}

.checkbox-custom {
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 1.25rem;
    text-align: center;
    transition: all 0.3s ease;
    background: white;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
}

.checkbox-custom i {
    font-size: 2rem;
    color: #6366f1;
}

.checkbox-custom .checkbox-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
}

.indicador-checkbox input[type="checkbox"]:checked + .checkbox-custom {
    border-color: #6366f1;
    background: #f0f9ff;
}

.indicador-checkbox input[type="checkbox"]:checked + .checkbox-custom i {
    color: #6366f1;
}

.indicador-checkbox:hover .checkbox-custom {
    border-color: #6366f1;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.15);
}

@media (max-width: 768px) {
    .indicadores-grid {
        grid-template-columns: 1fr;
    }
}

/* 🔥 NUEVO: Checkbox Grid para Sedes y Áreas */
.checkbox-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 0.75rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
    max-height: 200px;
    overflow-y: auto;
}

.checkbox-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 6px;
    transition: background 0.2s;
}

.checkbox-item:hover {
    background: #e5e7eb;
}

.checkbox-item.disabled {
    cursor: not-allowed;
    opacity: 0.6;
}

.checkbox-item.disabled:hover {
    background: transparent;
}

.checkbox-item input[type="checkbox"]:disabled {
    cursor: not-allowed;
    opacity: 0.5;
}

.checkbox-item input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
    accent-color: #22c55e;
}

.text-sm {
    font-size: 0.75rem;
    color: #6b7280;
}

@media (max-width: 768px) {
    .checkbox-grid {
        grid-template-columns: 1fr;
    }
}
</style>