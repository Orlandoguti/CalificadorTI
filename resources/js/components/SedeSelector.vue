<template>
    <div class="sede-selector-compact">
        <!-- Selector Compacto -->
        <div class="compact-selector">
            <div class="selector-trigger" @click="toggleDropdown">
                <div class="trigger-content">
                    <i class="fas fa-map-marker-alt"></i>
                    <span class="trigger-text">
                        {{ sedeSeleccionada ? sedeSeleccionada.nombre : 'Todas las sedes' }}
                    </span>
                    <i class="fas fa-chevron-down" :class="{ 'rotate': showDropdown }"></i>
                </div>
            </div>

            <!-- Dropdown -->
            <div v-if="showDropdown" class="dropdown-menu">
                <div class="dropdown-header">
                    <h4>Seleccionar Sede</h4>
                    <button @click="limpiarSeleccion" class="btn-clear-small" title="Ver todas">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="dropdown-options">
                    <button
                        v-for="sede in sedes"
                        :key="sede.id"
                        @click="seleccionarSede(sede)"
                        :class="['dropdown-option', { active: sedeSeleccionada?.id === sede.id }]"
                    >
                        <div class="option-content">
                            <div class="option-name">{{ sede.nombre }}</div>
                            <div class="option-stats">
                                <span class="stat">
                                    <i class="fas fa-chart-bar"></i>
                                    {{ getTotalCalificaciones(sede.id) }}
                                </span>
                                <span class="stat">
                                    <i class="fas fa-star"></i>
                                    {{ getPromedioCalificacion(sede.id) }}/5
                                </span>
                            </div>
                        </div>
                        <i class="fas fa-check option-check" v-if="sedeSeleccionada?.id === sede.id"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Overlay para cerrar dropdown -->
        <div v-if="showDropdown" class="dropdown-overlay" @click="closeDropdown"></div>

        <!-- Loading -->
        <div v-if="cargando" class="loading-compact">
            <div class="spinner-tiny"></div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'SedeSelector',
    props: {
        value: {
            type: Object,
            default: null
        }
    },
    data() {
        return {
            sedes: [],
            sedeSeleccionada: null,
            estadisticasSedes: {},
            cargando: false,
            showDropdown: false
        }
    },
    watch: {
        value: {
            immediate: true,
            handler(newVal) {
                this.sedeSeleccionada = newVal;
            }
        }
    },
    async mounted() {
        await this.cargarSedes();
        await this.cargarEstadisticasSedes();
        
        // Cerrar dropdown al hacer clic fuera
        document.addEventListener('click', this.handleClickOutside);
    },
    beforeUnmount() {
        document.removeEventListener('click', this.handleClickOutside);
    },
    methods: {
        async cargarSedes() {
            try {
                this.cargando = true;
                const response = await fetch('/api/sedes');
                if (response.ok) {
                    this.sedes = await response.json();
                }
            } catch (error) {
                console.error('Error cargando sedes:', error);
            } finally {
                this.cargando = false;
            }
        },

        async cargarEstadisticasSedes() {
            try {
                for (const sede of this.sedes) {
                    const response = await fetch(`/api/admin/stats?sede_id=${sede.id}`);
                    if (response.ok) {
                        const stats = await response.json();
                        this.estadisticasSedes[sede.id] = stats;
                    }
                }
            } catch (error) {
                console.error('Error cargando estadísticas por sede:', error);
            }
        },

        getTotalCalificaciones(sedeId) {
            const stats = this.estadisticasSedes[sedeId];
            return stats ? stats.totalCalificaciones : 0;
        },

        getPromedioCalificacion(sedeId) {
            const stats = this.estadisticasSedes[sedeId];
            return stats ? parseFloat(stats.promedioCalificacion).toFixed(1) : '0.0';
        },

        toggleDropdown(event) {
            event.stopPropagation();
            this.showDropdown = !this.showDropdown;
        },

        closeDropdown() {
            this.showDropdown = false;
        },

        handleClickOutside(event) {
            if (!this.$el.contains(event.target)) {
                this.closeDropdown();
            }
        },

        seleccionarSede(sede) {
            console.log('🎯 Sede seleccionada (STORE):', sede);
            
            // ✅ ACTUALIZAR STORE GLOBAL
            window.SedeStore.setSede(sede);
            
            // También emitir para compatibilidad
            this.sedeSeleccionada = sede;
            this.$emit('input', sede);
            this.$emit('cambio-sede', sede);
            
            this.closeDropdown();
        },

        limpiarSeleccion() {
            console.log('🗑️ Limpiando selección de sede (STORE)');
            
            // ✅ ACTUALIZAR STORE GLOBAL
            window.SedeStore.setSede(null);
            
            // También emitir para compatibilidad
            this.sedeSeleccionada = null;
            this.$emit('input', null);
            this.$emit('cambio-sede', null);
            
            this.closeDropdown();
        }
    }
}
</script>

<style scoped>
.sede-selector-compact {
    position: relative;
    min-width: 200px;
}

/* Selector Trigger */
.compact-selector {
    position: relative;
}

.selector-trigger {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    padding: 0.5rem 0.75rem;
    cursor: pointer;
    transition: all 0.3s ease;
    min-width: 180px;
}

.selector-trigger:hover {
    border-color: #4f46e5;
    background: #f8fafc;
}

.trigger-content {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    font-weight: 500;
    color: #374151;
}

.trigger-content i:first-child {
    color: #4f46e5;
}

.trigger-text {
    flex: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 120px;
}

.trigger-content .fa-chevron-down {
    font-size: 0.8rem;
    color: #6b7280;
    transition: transform 0.3s ease;
}

.trigger-content .fa-chevron-down.rotate {
    transform: rotate(180deg);
}

/* Dropdown Menu */
.dropdown-menu {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    z-index: 1000;
    margin-top: 0.5rem;
    min-width: 280px;
    max-height: 400px;
    overflow-y: auto;
}

.dropdown-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid #f3f4f6;
    background: #f8fafc;
}

.dropdown-header h4 {
    margin: 0;
    font-size: 0.9rem;
    font-weight: 600;
    color: #374151;
}

.btn-clear-small {
    background: #ef4444;
    color: white;
    border: none;
    border-radius: 4px;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 0.7rem;
    transition: background 0.3s ease;
}

.btn-clear-small:hover {
    background: #dc2626;
}

.dropdown-options {
    padding: 0.5rem;
}

.dropdown-option {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    border: 1px solid #f3f4f6;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
    margin-bottom: 0.25rem;
    background: white;
}

.dropdown-option:hover {
    border-color: #4f46e5;
    background: #f8fafc;
}

.dropdown-option.active {
    border-color: #4f46e5;
    background: #eef2ff;
}

.option-content {
    flex: 1;
}

.option-name {
    font-weight: 600;
    font-size: 0.85rem;
    color: #1f2937;
    margin-bottom: 0.25rem;
}

.option-stats {
    display: flex;
    gap: 0.75rem;
}

.stat {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.7rem;
    color: #6b7280;
}

.stat i {
    font-size: 0.6rem;
}

.option-check {
    color: #4f46e5;
    font-size: 0.8rem;
}

/* Overlay */
.dropdown-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 999;
}

/* Loading */
.loading-compact {
    position: absolute;
    top: 50%;
    right: 0.5rem;
    transform: translateY(-50%);
}

.spinner-tiny {
    border: 2px solid #e5e7eb;
    border-top: 2px solid #4f46e5;
    border-radius: 50%;
    width: 12px;
    height: 12px;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 768px) {
    .sede-selector-compact {
        min-width: 160px;
    }
    
    .trigger-text {
        max-width: 80px;
    }
    
    .dropdown-menu {
        min-width: 240px;
        right: 0;
        left: auto;
    }
    
    .option-stats {
        flex-direction: column;
        gap: 0.1rem;
    }
}

/* Estados especiales */
.selector-trigger:focus {
    outline: none;
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

/* Scrollbar personalizado */
.dropdown-menu::-webkit-scrollbar {
    width: 6px;
}

.dropdown-menu::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 3px;
}

.dropdown-menu::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.dropdown-menu::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>