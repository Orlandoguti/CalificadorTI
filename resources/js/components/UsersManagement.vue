<template>
    <div class="users-management">
        <!-- Header Section -->
        <div class="header-section">
            <div class="header-content">
                <div class="header-info">
                    <h1 class="page-title">
                        <i class="fas fa-users"></i>
                        Gestión de Usuarios
                    </h1>
                    <p class="page-subtitle">
                        Administra usuarios del sistema y asigna sedes
                    </p>
                </div>
                <button @click="mostrarModalCrearUsuario" class="btn-primary">
                    <i class="fas fa-user-plus"></i>
                    Nuevo Usuario
                </button>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="filters-section">
            <div class="filters-container">
                <div class="filter-group" style="flex-direction: row;">
                    <label class="filter-label">
                        <i class="fas fa-user-tag"></i>
                        Rol
                    </label>
                    <select v-model="filters.role" @change="cargarUsuarios" class="filter-select">
                        <option value="">Todos los roles</option>
                        <option value="admin">Administradores</option>
                        <option value="gestor">Gestores</option>
                        <option value="user">Usuarios</option>
                    </select>
                </div>
                <div class="filter-group" style="flex-direction: row;">
                    <label class="filter-label">
                        <i class="fas fa-map-marker-alt"></i>
                        Sede
                    </label>
                    <select v-model="filters.sede_id" @change="cargarUsuarios" class="filter-select">
                        <option value="">Todas las sedes</option>
                        <option v-for="sede in sedes" :key="sede.id" :value="sede.id">
                            {{ sede.nombre }}
                        </option>
                    </select>
                </div>
                <div class="filter-stats">
                    <span class="stats-item">
                        <i class="fas fa-users"></i>
                        {{ usuarios.length }} usuarios
                    </span>
                </div>
            </div>
        </div>

        <!-- Content Section -->
        <div class="content-section">
            <!-- Loading State -->
            <div v-if="cargando" class="loading-container">
                <div class="loading-spinner"></div>
                <p class="loading-text">Cargando usuarios...</p>
            </div>

            <!-- Empty State -->
            <div v-else-if="usuarios.length === 0" class="empty-container">
                <div class="empty-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3 class="empty-title">No hay usuarios</h3>
                <p class="empty-description">
                    Comienza creando el primer usuario del sistema.
                </p>
                <button @click="mostrarModalCrearUsuario" class="btn-primary">
                    <i class="fas fa-user-plus"></i>
                    Crear Usuario
                </button>
            </div>

            <!-- Users Table -->
            <div v-else class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Sede Asignada</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="usuario in usuarios" :key="usuario.id">
                            <td>
                                <div class="user-info">
                                    <div class="user-avatar">
                                        <img v-if="usuario.avatar" :src="usuario.avatar" :alt="usuario.name" class="avatar-img">
                                        <div v-else class="avatar-initial">{{ getInitial(usuario.name) }}</div>
                                    </div>
                                    <div class="user-details">
                                        <strong>{{ usuario.name }}</strong>
                                        <small>ID: {{ usuario.id }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>{{ usuario.email }}</td>
                            <td>
                                <span :class="['role-badge', usuario.role]">
                                    {{ getRolTexto(usuario.role) }}
                                </span>
                            </td>
                            <td>
                                <span v-if="usuario.sede" class="sede-info">
                                    <i class="fas fa-map-marker-alt"></i>
                                    {{ usuario.sede.nombre }}
                                </span>
                                <span v-else class="no-sede">
                                    <i class="fas fa-times-circle"></i>
                                    No asignada
                                </span>
                            </td>
                            <td>
                                <span :class="['status-badge', usuario.email_verified_at ? 'active' : 'inactive']">
                                    <i :class="usuario.email_verified_at ? 'fas fa-check-circle' : 'fas fa-clock'"></i>
                                    {{ usuario.email_verified_at ? 'Verificado' : 'Pendiente' }}
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button @click="editarUsuario(usuario)" class="action-btn edit" title="Editar">
                                        <svg width="16" height="16" viewBox="0 0 512 512" fill="#007bff" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M410.3 231l11.3-11.3-33.9-33.9-62.1-62.1L291.7 89.8l-11.3 11.3-22.6 22.6L58.6 322.9c-10.4 10.4-18 23.3-22.2 37.4L1 480.1c-2.5 8.4-.2 17.5 6.1 23.7s15.3 8.5 23.7 6.1l119.8-35.8c14.1-4.2 27-11.8 37.4-22.2L410.3 231zm0 0l-62.1-62.1L291.7 89.8l62.1 62.1 56.5 56.5z"/>
                                        </svg>
                                    </button>
                                    <button @click="cambiarRol(usuario)" 
                                            class="action-btn role" 
                                            :title="'Cambiar rol a ' + (usuario.role === 'gestor' ? 'Usuario' : 'Gestor')">
                                        <svg width="16" height="16" viewBox="0 0 512 512" fill="#6b7280" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M399 384.2C376.9 345.8 335.4 320 288 320H224c-47.4 0-88.9 25.8-111 64.2c35.2 39.2 86.2 63.8 143 63.8s107.8-24.7 143-63.8zM0 256a256 256 0 1 1 512 0A256 256 0 1 1 0 256zm256 16a72 72 0 1 0 0-144 72 72 0 1 0 0 144z"/>
                                        </svg>
                                    </button>
                                    <button @click="eliminarUsuario(usuario)" 
                                            class="action-btn delete" 
                                            title="Eliminar">
                                        <svg width="16" height="16" viewBox="0 0 448 512" fill="#dc3545" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M135.2 17.7L128 32H32C14.3 32 0 46.3 0 64S14.3 96 32 96H416c17.7 0 32-14.3 32-32s-14.3-32-32-32H320l-7.2-14.3C307.4 6.8 296.3 0 288 0H160c-8.3 0-19.4 6.8-24.8 17.7zM32 128H416V448c0 35.3-28.7 64-64 64H96c-35.3 0-64-28.7-64-64V128zm96 64c-8.8 0-16 7.2-16 16V432c0 8.8 7.2 16 16 16s16-7.2 16-16V208c0-8.8-7.2-16-16-16zm96 0c-8.8 0-16 7.2-16 16V432c0 8.8 7.2 16 16 16s16-7.2 16-16V208c0-8.8-7.2-16-16-16zm96 0c-8.8 0-16 7.2-16 16V432c0 8.8 7.2 16 16 16s16-7.2 16-16V208c0-8.8-7.2-16-16-16z"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="usuarios.length > 0 && pagination.last > 1" class="pagination">
                <button :disabled="!pagination.prev" @click="cambiarPagina(pagination.current - 1)" class="pagination-btn">
                    <i class="fas fa-chevron-left"></i> Anterior
                </button>
                <span class="pagination-info">
                    Página {{ pagination.current }} de {{ pagination.last }}
                </span>
                <button :disabled="!pagination.next" @click="cambiarPagina(pagination.current + 1)" class="pagination-btn">
                    Siguiente <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>

        <!-- Modal Crear/Editar Usuario -->
        <div v-if="mostrarModalUsuario" class="modal-overlay" @click.self="cerrarModalUsuario">
            <div class="modal-container">
                <div class="modal-header">
                    <h2 class="modal-title">
                        <i :class="esEdicionUsuario ? 'fas fa-edit' : 'fas fa-user-plus'"></i>
                        {{ esEdicionUsuario ? 'Editar Usuario' : 'Nuevo Usuario' }}
                    </h2>
                    <button @click="cerrarModalUsuario" class="modal-close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form @submit.prevent="guardarUsuario" class="modal-form">
                    <div class="form-section">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-user"></i>
                                    Nombre Completo *
                                </label>
                                <input v-model="usuarioForm.name" 
                                       type="text" 
                                       required 
                                       class="form-input"
                                       placeholder="Ej: Juan Pérez">
                            </div>
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-envelope"></i>
                                    Email *
                                </label>
                                <input v-model="usuarioForm.email" 
                                       type="email" 
                                       required 
                                       class="form-input"
                                       placeholder="ejemplo@unifranz.edu.bo">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-user-tag"></i>
                                    Rol *
                                </label>
                                <select v-model="usuarioForm.role" required class="form-select">
                                    <option value="user">Usuario</option>
                                    <option value="gestor">Gestor</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-map-marker-alt"></i>
                                    Sede
                                </label>
                                <select v-model="usuarioForm.sede_id" class="form-select">
                                    <option value="">Sin sede asignada</option>
                                    <option v-for="sede in sedes" :key="sede.id" :value="sede.id">
                                        {{ sede.nombre }}
                                    </option>
                                </select>
                                <small class="form-help">
                                    Para gestores, es obligatorio asignar una sede
                                </small>
                            </div>
                        </div>

                        <div v-if="!esEdicionUsuario" class="form-group full-width">
                            <label class="form-label">
                                <i class="fas fa-lock"></i>
                                Contraseña *
                            </label>
                            <input v-model="usuarioForm.password" 
                                   type="password" 
                                   required 
                                   class="form-input"
                                   placeholder="Mínimo 8 caracteres"
                                   minlength="8">
                        </div>

                        <div class="form-group">
                            <label class="checkbox-container">
                                <input type="checkbox" v-model="usuarioForm.email_verified">
                                <span class="checkbox-checkmark"></span>
                                <span class="checkbox-label">Email verificado</span>
                            </label>
                        </div>
                    </div>

                    <div class="modal-actions">
                        <button type="button" @click="cerrarModalUsuario" class="btn-secondary">
                            <i class="fas fa-times"></i>
                            Cancelar
                        </button>
                        <button type="submit" :disabled="guardandoUsuario" class="btn-primary">
                            <span v-if="guardandoUsuario">
                                <i class="fas fa-spinner fa-spin"></i> Guardando...
                            </span>
                            <span v-else>
                                {{ esEdicionUsuario ? 'Actualizar' : 'Crear' }} Usuario
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
    name: 'UsersManagement',
    data() {
    return {
        usuarios: [],
        sedes: [],
        cargando: false,
        guardandoUsuario: false,
        mostrarModalUsuario: false,
        esEdicionUsuario: false,
       
        filters: {
            role: '',
            sede_id: ''
        },
        pagination: {
            current: 1,
            last: 1,
            prev: null,
            next: null
        },
        usuarioForm: {
            id: null,
            name: '',
            email: '',
            role: 'user',
            sede_id: '',
            password: '',
            email_verified: false
        }
    }
},
    async mounted() {
        await this.cargarSedes();
        await this.cargarUsuarios();
    },
    methods: {
        getInitial(name) {
            if (!name || typeof name !== 'string') return '?';
            return name.trim().charAt(0).toUpperCase();
        },

        async cargarSedes() {
            try {
                const response = await fetch('/api/sedes');
                if (response.ok) {
                    this.sedes = await response.json();
                }
            } catch (error) {
                console.error('Error cargando sedes:', error);
            }
        },

        async cargarUsuarios(pagina = 1) {
            this.cargando = true;
            try {
                const params = new URLSearchParams();
                params.append('page', pagina);
                
                if (this.filters.role) params.append('role', this.filters.role);
                if (this.filters.sede_id) params.append('sede_id', this.filters.sede_id);

                const response = await fetch(`/api/usuarios?${params.toString()}`);
                if (response.ok) {
                    const data = await response.json();
                    this.usuarios = data.data;
                    this.pagination = {
                        current: data.current_page,
                        last: data.last_page,
                        prev: data.prev_page_url,
                        next: data.next_page_url
                    };
                } else {
                    throw new Error('Error al cargar usuarios');
                }
            } catch (error) {
                console.error('Error cargando usuarios:', error);
                this.mostrarMensaje('Error al cargar los usuarios', 'error');
            } finally {
                this.cargando = false;
            }
        },

        cambiarPagina(pagina) {
            this.cargarUsuarios(pagina);
        },

        mostrarModalCrearUsuario() {
            this.esEdicionUsuario = false;
            this.usuarioForm = {
                id: null,
                name: '',
                email: '',
                role: 'user',
                sede_id: '',
                password: '',
                email_verified: false
            };
            this.mostrarModalUsuario = true;
        },

        editarUsuario(usuario) {
            this.esEdicionUsuario = true;
            this.usuarioForm = {
                id: usuario.id,
                name: usuario.name,
                email: usuario.email,
                role: usuario.role,
                sede_id: usuario.sede_id || '',
                password: '',
                email_verified: !!usuario.email_verified_at
            };
            this.mostrarModalUsuario = true;
        },

        async guardarUsuario() {
    // 🔥 CORRECIÓN: Validar que los gestores tengan sede
    if (this.usuarioForm.role === 'gestor' && !this.usuarioForm.sede_id) {
        this.mostrarMensaje('Los gestores deben tener una sede asignada', 'error');
        return;
    }

    this.guardandoUsuario = true;
    
    try {
        console.log('💾 Guardando usuario:', this.usuarioForm);
        
        const esEdicion = this.usuarioForm.id !== null && this.usuarioForm.id !== undefined;
        const url = esEdicion 
            ? `/api/usuarios/${this.usuarioForm.id}`
            : '/api/usuarios';
            
        const method = esEdicion ? 'PUT' : 'POST';
        
        const datosEnvio = {
            name: this.usuarioForm.name || '',
            email: this.usuarioForm.email || '',
            role: this.usuarioForm.role || 'user',
            sede_id: this.usuarioForm.sede_id || null,
            email_verified: this.usuarioForm.email_verified || false
        };
        
        // Solo incluir password si no está vacío y no es edición
        if (this.usuarioForm.password && this.usuarioForm.password.trim() !== '') {
            datosEnvio.password = this.usuarioForm.password;
        }
        
        console.log('📤 Datos a enviar:', datosEnvio);
        
        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(datosEnvio)
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            if (data.errors) {
                const errores = Object.values(data.errors).flat().join(', ');
                throw new Error(errores);
            }
            throw new Error(data.error || 'Error al guardar usuario');
        }
        
        this.mostrarModalUsuario = false;
        this.mostrarMensaje('Usuario guardado correctamente', 'success');
        await this.cargarUsuarios();
        
    } catch (error) {
        console.error('❌ Error guardando usuario:', error);
        this.mostrarMensaje(error.message, 'error');
    } finally {
        this.guardandoUsuario = false;
    }
},

        validarFormulario() {
            if (!this.usuarioForm.name.trim()) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Campo requerido',
                    text: 'Por favor ingresa el nombre del usuario',
                    confirmButtonColor: '#3b82f6'
                });
                return false;
            }
            if (!this.usuarioForm.email.trim()) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Campo requerido',
                    text: 'Por favor ingresa el email del usuario',
                    confirmButtonColor: '#3b82f6'
                });
                return false;
            }
            if (!this.esEdicionUsuario && !this.usuarioForm.password) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Campo requerido',
                    text: 'Por favor ingresa una contraseña',
                    confirmButtonColor: '#3b82f6'
                });
                return false;
            }
            if (this.usuarioForm.role === 'gestor' && !this.usuarioForm.sede_id) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Sede requerida',
                    text: 'Los gestores deben tener una sede asignada',
                    confirmButtonColor: '#3b82f6'
                });
                return false;
            }
            return true;
        },

        async cambiarRol(usuario) {
            const nuevoRol = usuario.role === 'gestor' ? 'user' : 'gestor';
            const result = await Swal.fire({
                title: '¿Estás seguro?',
                text: `¿Estás seguro de cambiar el rol de ${usuario.name} a ${this.getRolTexto(nuevoRol)}?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3b82f6',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Sí',
                cancelButtonText: 'Cancelar'
            });

            if (!result.isConfirmed) return;

            try {
                const response = await fetch(`/api/usuarios/${usuario.id}/rol`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        role: nuevoRol,
                        // Si se convierte en gestor sin sede, pedir asignar una
                        sede_id: nuevoRol === 'gestor' && !usuario.sede_id ? '' : usuario.sede_id
                    })
                });

                if (response.ok) {
                    await this.cargarUsuarios();
                    this.mostrarMensaje(`Rol cambiado a ${this.getRolTexto(nuevoRol)}`, 'success');
                } else {
                    throw new Error('Error al cambiar rol');
                }
            } catch (error) {
                console.error('Error cambiando rol:', error);
                this.mostrarMensaje('Error al cambiar el rol', 'error');
            }
        },

        async eliminarUsuario(usuario) {
            const result = await Swal.fire({
                title: '¿Estás seguro?',
                text: `¿Estás seguro de eliminar al usuario ${usuario.name}? Esta acción no se puede deshacer.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            });

            if (!result.isConfirmed) return;

            try {
                const response = await fetch(`/api/usuarios/${usuario.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });

                if (response.ok) {
                    await this.cargarUsuarios();
                    this.mostrarMensaje('Usuario eliminado correctamente', 'success');
                } else {
                    throw new Error('Error al eliminar usuario');
                }
            } catch (error) {
                console.error('Error eliminando usuario:', error);
                this.mostrarMensaje('Error al eliminar el usuario', 'error');
            }
        },

        getRolTexto(role) {
            const roles = {
                'admin': 'Admin',
                'gestor': 'Gestor',
                'user': 'Usuario'
            };
            return roles[role] || role;
        },

        cerrarModalUsuario() {
            this.mostrarModalUsuario = false;
            this.esEdicionUsuario = false;
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
/* ESTILOS GENERALES - TEMA ROJO */
.users-management {
    min-height: 100vh;
    background: #f8f9fa;
}

/* HEADER SECTION - ROJO */
.header-section {
    background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
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

/* BOTONES - ROJO */
.btn-primary {
    background: #dc2626;
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
    background: #b91c1c;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
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
    gap: 2rem;
    align-items: center;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.filter-label {
    font-weight: 600;
    color: #374151;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
}

.filter-select {
    padding: 0.5rem 1rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    background: white;
    font-size: 0.875rem;
    min-width: 200px;
}

.filter-select:focus {
    outline: none;
    border-color: #dc2626;
    box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
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
    border-top: 4px solid #dc2626;
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

/* TABLE STYLES */
.table-container {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th {
    background: #f8fafc;
    padding: 1rem 1.5rem;
    text-align: left;
    font-weight: 600;
    color: #374151;
    border-bottom: 1px solid #e5e7eb;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.data-table td {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #e5e7eb;
    font-size: 0.875rem;
}

.data-table tr:last-child td {
    border-bottom: none;
}

.data-table tr:hover {
    background: #f9fafb;
}

/* USER INFO */
.user-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #f3f4f6;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6b7280;
    flex: 0 0 40px; /* evita que se encoja en el contenedor flex */
    overflow: hidden; /* mantiene el círculo limpio */
}

.avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}

.avatar-initial {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: #0ea5a4; /* teal */
    color: #ffffff;
    font-weight: 700;
    font-size: 0.95rem;
}

.user-details {
    display: flex;
    flex-direction: column;
}

.user-details strong {
    color: #111827;
    font-size: 0.9rem;
}

.user-details small {
    color: #6b7280;
    font-size: 0.75rem;
}

/* BADGES */
.role-badge {
    padding: 0.375rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    display: inline-block;
}

.role-badge.admin {
    background: #fef2f2;
    color: #dc2626;
    border: 1px solid #fecaca;
}

.role-badge.gestor {
    background: #f0fdf4;
    color: #16a34a;
    border: 1px solid #bbf7d0;
}

.role-badge.user {
    background: #f3f4f6;
    color: #6b7280;
    border: 1px solid #e5e7eb;
}

.status-badge {
    padding: 0.375rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.375rem;
}

.status-badge.active {
    background: #d1fae5;
    color: #065f46;
}

.status-badge.inactive {
    background: #fef3c7;
    color: #92400e;
}

.sede-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #059669;
    font-weight: 500;
}

.no-sede {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #6b7280;
    font-style: italic;
}

/* ACTION BUTTONS */
.action-buttons {
    display: flex;
    gap: 0.5rem;
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

.action-btn.role:hover {
    background: #f0fdf4;
    color: #16a34a;
}

.action-btn.delete:hover {
    background: #fef2f2;
    color: #dc2626;
}

/* PAGINATION */
.pagination {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 2rem;
    padding: 1.5rem 0;
    border-top: 1px solid #e5e7eb;
}

.pagination-btn {
    padding: 0.75rem 1.5rem;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    background: white;
    color: #374151;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
    font-weight: 500;
}

.pagination-btn:hover:not(:disabled) {
    background: #f3f4f6;
    border-color: #9ca3af;
}

.pagination-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.pagination-info {
    color: #6b7280;
    font-size: 0.875rem;
    font-weight: 500;
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
    border-color: #dc2626;
    box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
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
    background: #dc2626;
    border-color: #dc2626;
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
    
    .filters-container {
        flex-direction: column;
        gap: 1rem;
    }
    
    .filter-stats {
        margin-left: 0;
        align-self: flex-start;
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
    
    .pagination {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .data-table {
        display: block;
        overflow-x: auto;
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

.data-table tr {
    animation: fadeIn 0.5s ease;
}
</style>