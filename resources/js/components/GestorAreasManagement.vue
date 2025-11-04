<template>
    <div class="gestor-areas-management">
        <div class="management-header">
            <h2>Gestión de Áreas - {{ sedeNombre }}</h2>
            <p>Administra las áreas disponibles para calificación en tu sede</p>
        </div>

        <div class="actions-bar">
            <button @click="mostrarModalCrear" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nueva Área
            </button>
            <div class="search-box">
                <input 
                    v-model="filtroBusqueda"
                    placeholder="Buscar áreas..."
                    class="search-input"
                >
                <i class="fas fa-search"></i>
            </div>
        </div>

        <!-- Tabla de áreas -->
        <div class="table-container">
            <table class="areas-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Código</th>
                        <th>Tipos de Calificación</th>
                        <th>Preguntas</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="area in areasFiltradas" :key="area.id">
                        <td>{{ area.nombre }}</td>
                        <td>{{ area.codigo }}</td>
                        <td>
                            <div style="display: flex; gap: 6px;">
                                <span v-if="area.permite_csat" style="padding: 2px 8px; background: #dbeafe; color: #1e40af; border-radius: 4px; font-size: 11px; font-weight: 500;">CSAT</span>
                                <span v-if="area.permite_nps" style="padding: 2px 8px; background: #fef3c7; color: #92400e; border-radius: 4px; font-size: 11px; font-weight: 500;">NPS</span>
                                <span v-if="area.permite_fcr" style="padding: 2px 8px; background: #fce7f3; color: #9f1239; border-radius: 4px; font-size: 11px; font-weight: 500;">FCR</span>
                                <span v-if="!area.permite_csat && !area.permite_nps && !area.permite_fcr" style="color: #9ca3af;">Ninguno</span>
                            </div>
                        </td>
                        <td>{{ area.preguntas_count }}</td>
                        <td>
                            <span :class="['status-badge', area.is_active ? 'active' : 'inactive']">
                                {{ area.is_active ? 'Activa' : 'Inactiva' }}
                            </span>
                        </td>
                        <td class="actions">
                            <button @click="editarArea(area)" class="btn-icon" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button 
                                @click="toggleEstadoArea(area)" 
                                :class="['btn-icon', area.is_active ? 'danger' : 'success']"
                                :title="area.is_active ? 'Desactivar' : 'Activar'"
                            >
                                <i :class="area.is_active ? 'fas fa-ban' : 'fas fa-check'"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal para crear/editar área -->
        <div v-if="mostrarModal" class="modal-overlay" @click="cerrarModal">
            <div class="modal-container" @click.stop>
                <div class="modal-header">
                    <h3>{{ esEdicion ? 'Editar Área' : 'Crear Nueva Área' }}</h3>
                    <button @click="cerrarModal" class="btn-close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <form @submit.prevent="guardarArea" class="modal-form">
                    <div class="form-group">
                        <label>Nombre del Área *</label>
                        <input 
                            v-model="areaForm.nombre"
                            type="text" 
                            required
                            placeholder="Ej: Área Académica"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label>Código *</label>
                        <input 
                            v-model="areaForm.codigo"
                            type="text" 
                            required
                            placeholder="Ej: ARCA"
                            maxlength="10"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label>Contraseña</label>
                        <input 
                            v-model="areaForm.password"
                            type="password"
                            placeholder="Dejar vacío para contraseña por defecto"
                        >
                        <small>Si se deja vacío, se generará automáticamente</small>
                    </div>

                    <div class="form-group">
                        <label>Sede</label>
                        <input 
                            :value="sedeNombre" 
                            type="text" 
                            disabled
                            class="disabled-input"
                        >
                        <input 
                            v-model="areaForm.sede_id" 
                            type="hidden"
                        >
                    </div>

                    <div class="form-group">
                        <label>Tipos de Calificación Permitidos</label>
                        <div style="display: flex; gap: 20px; margin-top: 10px;">
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input 
                                    v-model="areaForm.permite_csat" 
                                    type="checkbox"
                                >
                                <span>CSAT</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input 
                                    v-model="areaForm.permite_nps" 
                                    type="checkbox"
                                >
                                <span>NPS</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input 
                                    v-model="areaForm.permite_fcr" 
                                    type="checkbox"
                                >
                                <span>FCR</span>
                            </label>
                        </div>
                        <small>Selecciona los tipos de calificación permitidos para esta área</small>
                    </div>

                    <div class="form-actions">
                        <button type="button" @click="cerrarModal" class="btn btn-secondary">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="guardando" class="btn btn-primary">
                            <span v-if="guardando">
                                <i class="fas fa-spinner fa-spin"></i> Guardando...
                            </span>
                            <span v-else>
                                {{ esEdicion ? 'Actualizar' : 'Crear' }}
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
    name: 'GestorAreasManagement',
    data() {
        return {
            areas: [],
            filtroBusqueda: '',
            mostrarModal: false,
            esEdicion: false,
            guardando: false,
            areaForm: {
                id: null,
                nombre: '',
                codigo: '',
                password: '',
                sede_id: null,
                is_active: true,
                permite_csat: false,
                permite_nps: false,
                permite_fcr: false
            },
            sedeNombre: 'Cargando...'
        }
    },
    computed: {
        areasFiltradas() {
            if (!this.filtroBusqueda) return this.areas;
            
            const search = this.filtroBusqueda.toLowerCase();
            return this.areas.filter(area => 
                area.nombre.toLowerCase().includes(search) ||
                area.codigo.toLowerCase().includes(search)
            );
        }
    },
    async mounted() {
        await this.cargarDatosUsuario();
        await this.cargarAreas();
    },
    methods: {
        async cargarDatosUsuario() {
            try {
                const response = await fetch('/api/user');
                if (response.ok) {
                    const userData = await response.json();
                    this.sedeNombre = userData.sede ? userData.sede.nombre : `Sede ${userData.sede_id}`;
                    this.areaForm.sede_id = userData.sede_id;
                }
            } catch (error) {
                console.error('Error cargando datos usuario:', error);
            }
        },

        async cargarAreas() {
            try {
                const response = await fetch('/api/areas');
                if (response.ok) {
                    const todasLasAreas = await response.json();
                    // Filtrar solo las áreas de la sede del gestor
                    this.areas = todasLasAreas.filter(area => area.sede_id === this.areaForm.sede_id);
                }
            } catch (error) {
                console.error('Error cargando áreas:', error);
            }
        },

        mostrarModalCrear() {
            this.esEdicion = false;
            this.areaForm = {
                id: null,
                nombre: '',
                codigo: '',
                password: '',
                sede_id: this.areaForm.sede_id, // Mantener la sede del gestor
                is_active: true,
                permite_csat: false,
                permite_nps: false,
                permite_fcr: false
            };
            this.mostrarModal = true;
        },

        editarArea(area) {
            this.esEdicion = true;
            this.areaForm = {
                id: area.id,
                nombre: area.nombre,
                codigo: area.codigo,
                password: '', // No mostrar contraseña actual
                sede_id: area.sede_id,
                is_active: area.is_active,
                permite_csat: area.permite_csat || false,
                permite_nps: area.permite_nps || false,
                permite_fcr: area.permite_fcr || false
            };
            this.mostrarModal = true;
        },

        async guardarArea() {
    this.guardando = true;
    
    try {
        // Asegurar que sede_id sea un número
        const dataToSend = {
            ...this.areaForm,
            sede_id: parseInt(this.areaForm.sede_id)
        };
        
        console.log('📤 Enviando datos:', dataToSend);
        
        const url = this.esEdicion ? `/api/areas/${this.areaForm.id}` : '/api/areas';
        const method = this.esEdicion ? 'PUT' : 'POST';
        
        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(dataToSend)
        });
        
        if (response.ok) {
            await this.cargarAreas();
            this.cerrarModal();
            this.mostrarMensaje(
                `Área ${this.esEdicion ? 'actualizada' : 'creada'} correctamente`,
                'success'
            );
        } else {
            const errorData = await response.json();
            console.error('Error del servidor:', errorData);
            
            // Manejar errores de validación
            if (response.status === 422 && errorData.errors) {
                const errores = Object.values(errorData.errors).flat().join(', ');
                throw new Error(`Errores de validación: ${errores}`);
            } else {
                throw new Error(errorData.error || `Error ${response.status}: ${response.statusText}`);
            }
        }
    } catch (error) {
        console.error('Error guardando área:', error);
        this.mostrarMensaje(error.message, 'error');
    } finally {
        this.guardando = false;
    }
},

        async toggleEstadoArea(area) {
            try {
                const response = await fetch(`/api/areas/${area.id}/toggle`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                if (response.ok) {
                    await this.cargarAreas();
                    this.mostrarMensaje(
                        `Área ${area.is_active ? 'desactivada' : 'activada'} correctamente`,
                        'success'
                    );
                }
            } catch (error) {
                console.error('Error cambiando estado:', error);
                this.mostrarMensaje('Error al cambiar estado', 'error');
            }
        },

        cerrarModal() {
            this.mostrarModal = false;
            this.areaForm = {
                id: null,
                nombre: '',
                codigo: '',
                password: '',
                sede_id: this.areaForm.sede_id, // Mantener la sede
                is_active: true,
                permite_csat: false,
                permite_nps: false,
                permite_fcr: false
            };
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
    }
}
</script>

<style scoped>
/* Los mismos estilos que el AreasManagement original */
.gestor-areas-management {
    padding: 20px;
}

.management-header {
    margin-bottom: 30px;
}

.management-header h2 {
    color: #1f2937;
    margin-bottom: 8px;
}

.management-header p {
    color: #6b7280;
}

.actions-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    gap: 15px;
}

.search-box {
    position: relative;
    flex: 0 0 300px;
}

.search-input {
    width: 100%;
    padding: 10px 40px 10px 15px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
}

.search-box .fa-search {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #6b7280;
}

.table-container {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.areas-table {
    width: 100%;
    border-collapse: collapse;
}

.areas-table th,
.areas-table td {
    padding: 12px 16px;
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
}

.areas-table th {
    background: #f8fafc;
    font-weight: 600;
    color: #374151;
}

.actions {
    display: flex;
    gap: 8px;
}

.btn-icon {
    padding: 6px 8px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-icon:hover {
    transform: translateY(-1px);
}

.btn-icon.danger {
    background: #fef2f2;
    color: #dc2626;
}

.btn-icon.danger:hover {
    background: #fecaca;
}

.btn-icon.success {
    background: #f0fdf4;
    color: #16a34a;
}

.btn-icon.success:hover {
    background: #bbf7d0;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}

.status-badge.active {
    background: #d1fae5;
    color: #065f46;
}

.status-badge.inactive {
    background: #fef3c7;
    color: #92400e;
}

.disabled-input {
    background: #f3f4f6;
    color: #6b7280;
    cursor: not-allowed;
}

/* Estilos del modal */
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
}

.modal-container {
    background: white;
    border-radius: 12px;
    padding: 0;
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    padding: 20px 24px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    color: #1f2937;
}

.btn-close {
    background: none;
    border: none;
    font-size: 1.25rem;
    color: #6b7280;
    cursor: pointer;
    padding: 4px;
    border-radius: 4px;
}

.btn-close:hover {
    background: #f3f4f6;
}

.modal-form {
    padding: 24px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 6px;
    font-weight: 500;
    color: #374151;
}

.form-group input {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
}

.form-group input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-group small {
    color: #6b7280;
    font-size: 12px;
    margin-top: 4px;
    display: block;
}

.form-group label input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.form-group label span {
    user-select: none;
}

.form-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    margin-top: 24px;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-primary {
    background: #3b82f6;
    color: white;
}

.btn-primary:hover:not(:disabled) {
    background: #2563eb;
}

.btn-secondary {
    background: #f3f4f6;
    color: #374151;
}

.btn-secondary:hover {
    background: #e5e7eb;
}
</style>