<template>
    <div class="gestor-preguntas-management">
        <div class="management-header">
            <h2>Gestión de Preguntas - {{ sedeNombre }}</h2>
            <p>Administra preguntas principales y sus subpreguntas</p>
        </div>

        <div class="actions-bar">
            <button @click="mostrarModalCrearPregunta" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nueva Pregunta Principal
            </button>
        </div>

        <!-- Lista de Preguntas Principales -->
        <div class="preguntas-container">
            <div v-for="pregunta in preguntas" :key="pregunta.id" class="pregunta-card">
                <div class="pregunta-header">
                    <div class="pregunta-info-main">
                        <h3>{{ pregunta.pregunta }}</h3>
                        <div class="pregunta-meta">
                            <span class="tipo-badge">{{ getTipoTexto(pregunta.tipo) }}</span>
                            <span class="area-badge">{{ pregunta.area.nombre }}</span>
                            <span class="nivel-badge">{{ pregunta.nivel_calificacion.nombre }}</span>
                        </div>
                    </div>
                    <div class="pregunta-actions">
                        <button @click="editarPregunta(pregunta)" class="btn-icon" title="Editar pregunta">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button @click="gestionarSubpreguntas(pregunta)" class="btn btn-outline">
                            <i class="fas fa-layer-group"></i> Subpreguntas
                        </button>
                        <button @click="toggleEstadoPregunta(pregunta)" 
                                :class="['btn-icon', pregunta.is_active ? 'danger' : 'success']"
                                :title="pregunta.is_active ? 'Desactivar' : 'Activar'">
                            <i :class="pregunta.is_active ? 'fas fa-ban' : 'fas fa-check'"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Opciones de la pregunta -->
                <div v-if="pregunta.opciones && pregunta.opciones.length" class="opciones-list">
                    <div v-for="opcion in pregunta.opciones" :key="opcion.id" 
                         class="opcion-item" :class="{ 'con-subpreguntas': opcion.tiene_subpreguntas }">
                        <div class="opcion-content">
                            <span class="opcion-text">{{ opcion.opcion }}</span>
                            <div class="opcion-status">
                                <span v-if="opcion.tiene_subpreguntas" class="subpreguntas-badge">
                                    <i class="fas fa-question-circle"></i> Tiene subpreguntas
                                </span>
                                <span v-else class="no-subpreguntas">
                                    Sin subpreguntas
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal para Pregunta Principal -->
        <div v-if="mostrarModalPregunta" class="modal-overlay" @click="cerrarModalPregunta">
            <div class="modal-container" @click.stop>
                <div class="modal-header">
                    <h3>{{ esEdicionPregunta ? 'Editar Pregunta' : 'Crear Pregunta Principal' }}</h3>
                    <button @click="cerrarModalPregunta" class="btn-close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <form @submit.prevent="guardarPregunta" class="modal-form">
                    <div class="form-group">
                        <label>Pregunta *</label>
                        <textarea 
                            v-model="preguntaForm.pregunta"
                            required
                            placeholder="Escribe la pregunta principal..."
                            rows="3"
                            class="form-textarea"
                        ></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Área *</label>
                            <select v-model="preguntaForm.area_id" required class="form-select">
                                <option value="">Seleccionar área</option>
                                <option v-for="area in areas" :key="area.id" :value="area.id">
                                    {{ area.nombre }}
                                </option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Tipo de Pregunta *</label>
                            <select v-model="preguntaForm.tipo" required class="form-select" @change="cambiarTipoPregunta">
                                <option value="opcion_unica">Opción Única</option>
                                <option value="opcion_multiple">Opción Múltiple</option>
                                <option value="indicador_0_10">Indicador 0-10</option>
                                <option value="opcion_unica_texto_libre">Opción Única + Texto Libre</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Nivel de Calificación *</label>
                            <select v-model="preguntaForm.niveles_calificacion_id" required class="form-select">
                                <option value="">Seleccionar nivel</option>
                                <option v-for="nivel in nivelesCalificacion" :key="nivel.id" :value="nivel.id">
                                    {{ nivel.nombre }}
                                </option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Sede</label>
                            <input 
                                :value="sedeNombre" 
                                type="text" 
                                disabled
                                class="form-input disabled"
                            >
                            <input 
                                v-model="preguntaForm.sede_id" 
                                type="hidden"
                            >
                        </div>
                    </div>

                    <!-- Opciones de la pregunta principal -->
                    <div v-if="mostrarOpcionesPregunta" class="form-group">
                        <label>Opciones de Respuesta *</label>
                        <div class="opciones-container">
                            <div v-for="(opcion, index) in preguntaForm.opciones" :key="index" class="opcion-input-item">
                                <input 
                                    v-model="opcion.texto"
                                    type="text"
                                    :placeholder="`Opción ${index + 1}`"
                                    required
                                    class="form-input opcion-input"
                                >
                                <button 
                                    type="button" 
                                    @click="eliminarOpcionPregunta(index)"
                                    class="btn-icon danger"
                                    :disabled="preguntaForm.opciones.length <= 2"
                                    title="Eliminar opción"
                                >
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <button type="button" @click="agregarOpcionPregunta" class="btn btn-outline">
                                <i class="fas fa-plus"></i> Agregar Opción
                            </button>
                        </div>
                    </div>

                    <!-- 🔥 NUEVA SECCIÓN: Configuración de Rangos para Indicador 0-10 -->
<div v-if="mostrandoConfiguracionRangos" class="configuracion-rangos">
    <div class="seccion-titulo">
        <h4>🔄 Configurar Preguntas por Rango</h4>
        <p>Define preguntas específicas según la puntuación del usuario</p>
    </div>

    <div v-for="(rango, rangoKey) in configuracionRangos" :key="rangoKey" class="rango-item">
        <div class="rango-header">
            <div class="rango-info">
                <h5>{{ getTextoRango(rangoKey) }}</h5>
                <span class="rango-valores">{{ rangoKey }}</span>
            </div>
            <div class="rango-toggle">
                <label class="toggle-label">
                    <input 
                        type="checkbox" 
                        v-model="rango.activo"
                        class="toggle-input"
                    >
                    <span class="toggle-slider"></span>
                    <span class="toggle-text">{{ rango.activo ? 'Activado' : 'Desactivado' }}</span>
                </label>
            </div>
        </div>

        <div v-if="rango.activo" class="rango-contenido">
            <div class="form-group">
                <label>Pregunta para este rango *</label>
                <textarea 
                    v-model="rango.pregunta_texto"
                    :placeholder="`Ej: ${getTextoEjemploRango(rangoKey)}`"
                    rows="2"
                    class="form-textarea"
                    required
                ></textarea>
            </div>

            <div class="form-group">
                <label>Tipo de Pregunta *</label>
                <select v-model="rango.tipo" required class="form-select" @change="cambiarTipoRango(rangoKey)">
                    <option value="opcion_unica">Opción Única</option>
                    <option value="opcion_multiple">Opción Múltiple</option>
                    <option value="texto_libre">Texto Libre</option>
                    <option value="opcion_unica_texto_libre">Opción Única + Texto</option>
                </select>
            </div>

            <!-- Opciones para el rango -->
            <div v-if="['opcion_unica', 'opcion_multiple', 'opcion_unica_texto_libre'].includes(rango.tipo)" 
                 class="form-group">
                <label>Opciones de Respuesta *</label>
                <div class="opciones-container">
                    <div v-for="(opcion, index) in rango.opciones" :key="index" class="opcion-input-item">
                        <input 
                            v-model="opcion.texto"
                            type="text"
                            :placeholder="`Opción ${index + 1}`"
                            required
                            class="form-input opcion-input"
                        >
                        <button 
                            type="button" 
                            @click="eliminarOpcionRango(rangoKey, index)"
                            class="btn-icon danger"
                            :disabled="rango.opciones.length <= 2"
                            title="Eliminar opción"
                        >
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <button type="button" @click="agregarOpcionRango(rangoKey)" class="btn btn-outline">
                        <i class="fas fa-plus"></i> Agregar Opción
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="rangos-info">
        <i class="fas fa-info-circle"></i>
        <span v-if="totalRangosActivos > 0">
            {{ totalRangosActivos }} de 3 rangos configurados
        </span>
        <span v-else>
            Ningún rango configurado - El indicador funcionará sin preguntas adicionales
        </span>
    </div>
</div>

                    <div class="form-actions">
                        <button type="button" @click="cerrarModalPregunta" class="btn btn-secondary">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="guardandoPregunta" class="btn btn-primary">
                            <span v-if="guardandoPregunta">
                                <i class="fas fa-spinner fa-spin"></i> Guardando...
                            </span>
                            <span v-else>
                                {{ esEdicionPregunta ? 'Actualizar' : 'Crear' }} Pregunta
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal para Gestionar Subpreguntas -->
        <div v-if="mostrarModalSubpreguntas" class="modal-overlay" @click="cerrarModalSubpreguntas">
            <div class="modal-container large-modal" @click.stop>
                <div class="modal-header">
                    <h3>Gestionar Subpreguntas</h3>
                    <p class="modal-subtitle">Pregunta: {{ preguntaSeleccionada?.pregunta }}</p>
                    <button @click="cerrarModalSubpreguntas" class="btn-close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="subpreguntas-content">
                    <div class="info-box">
                        <i class="fas fa-info-circle"></i>
                        <div>
                            <strong>¿Cómo funcionan las subpreguntas?</strong>
                            <p>Cuando el usuario seleccione una opción que tenga subpreguntas, se le mostrarán automáticamente después.</p>
                        </div>
                    </div>

                    <!-- Lista de opciones con sus subpreguntas -->
                    <div v-for="opcion in preguntaSeleccionada?.opciones || []" :key="opcion.id" class="opcion-subpreguntas-section">
                        <div class="opcion-header">
                            <h4>
                                <i class="fas fa-chevron-right"></i>
                                {{ opcion.opcion }}
                            </h4>
                            <button @click="agregarSubpregunta(opcion)" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Agregar Subpregunta
                            </button>
                        </div>
                        
                        <!-- Lista de subpreguntas de esta opción -->
                        <!-- Lista de subpreguntas de esta opción -->
                        <div v-if="opcion.subpreguntas && opcion.subpreguntas.length" class="subpreguntas-list">
                            <div v-for="subpregunta in opcion.subpreguntas" :key="subpregunta.id" class="subpregunta-item">
                                <div class="subpregunta-content">
                                    <div class="subpregunta-texto">
                                        <strong>{{ subpregunta.pregunta_texto }}</strong>
                                        <span class="tipo-badge small">{{ getTipoTexto(subpregunta.tipo) }}</span>
                                    </div>
                                    <div v-if="subpregunta.opciones && subpregunta.opciones.length" class="subpregunta-opciones">
                                        <small>Opciones: {{ Array.isArray(subpregunta.opciones) ? subpregunta.opciones.join(', ') : subpregunta.opciones }}</small>
                                    </div>
                                </div>
                                <div class="subpregunta-actions">
                                    <button @click="editarSubpregunta(subpregunta, opcion)" class="btn-icon" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button @click="eliminarSubpregunta(subpregunta)" class="btn-icon danger" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div v-else class="no-subpreguntas">
                            <p><i class="fas fa-info-circle"></i> Esta opción no tiene subpreguntas configuradas</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal para Agregar/Editar Subpregunta -->
        <div v-if="mostrarModalSubpregunta" class="modal-overlay" @click="cerrarModalSubpregunta">
            <div class="modal-container" @click.stop>
                <div class="modal-header">
                    <h3>{{ subpreguntaEditando ? 'Editar' : 'Agregar' }} Subpregunta</h3>
                    <p class="modal-subtitle">Para opción: {{ opcionSeleccionada?.opcion }}</p>
                    <button @click="cerrarModalSubpregunta" class="btn-close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form @submit.prevent="guardarSubpregunta" class="modal-form">
                    <div class="form-group">
                        <label>Subpregunta *</label>
                        <textarea 
                            v-model="subpreguntaForm.pregunta_texto"
                            required
                            placeholder="¿Qué pregunta quieres hacer cuando el usuario seleccione esta opción?"
                            rows="3"
                            class="form-textarea"
                        ></textarea>
                    </div>

                    <div class="form-group">
                        <label>Tipo de Subpregunta *</label>
                        <select v-model="subpreguntaForm.tipo" required class="form-select" @change="cambiarTipoSubpregunta">
                            <option value="opcion_unica">Opción Única</option>
                            <option value="opcion_multiple">Opción Múltiple</option>
                            <option value="texto_libre">Texto Libre</option>
                            <option value="indicador_0_10">Indicador 0-10</option>
                        </select>
                    </div>

                    <!-- Opciones para subpreguntas con opciones -->
                    <div v-if="['opcion_unica', 'opcion_multiple'].includes(subpreguntaForm.tipo)" class="form-group">
                        <label>Opciones *</label>
                        <div class="opciones-container">
                            <div v-for="(opcion, index) in subpreguntaForm.opciones" :key="index" class="opcion-input-item">
                                <input 
                                    v-model="opcion.texto"
                                    type="text"
                                    :placeholder="`Opción ${index + 1}`"
                                    required
                                    class="form-input opcion-input"
                                >
                                <button 
                                    type="button" 
                                    @click="eliminarOpcionSubpregunta(index)"
                                    class="btn-icon danger"
                                    :disabled="subpreguntaForm.opciones.length <= 2"
                                    title="Eliminar opción"
                                >
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <button type="button" @click="agregarOpcionSubpregunta" class="btn btn-outline">
                                <i class="fas fa-plus"></i> Agregar Opción
                            </button>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" @click="cerrarModalSubpregunta" class="btn btn-secondary">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="guardandoSubpregunta" class="btn btn-primary">
                            <span v-if="guardandoSubpregunta">
                                <i class="fas fa-spinner fa-spin"></i> Guardando...
                            </span>
                            <span v-else>
                                {{ subpreguntaEditando ? 'Actualizar' : 'Crear' }} Subpregunta
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
    name: 'GestorPreguntasManagement',
    data() {
        return {
            
            preguntas: [],
            areas: [],
            nivelesCalificacion: [],
            
            // Modales
            mostrarModalPregunta: false,
            mostrarModalSubpreguntas: false,
            mostrarModalSubpregunta: false,
            
            // Estados
            esEdicionPregunta: false,
            guardandoPregunta: false,
            guardandoSubpregunta: false,
            
            // Selecciones
            preguntaSeleccionada: null,
            opcionSeleccionada: null,
            subpreguntaEditando: null,
            
            // Formularios
            preguntaForm: {
                id: null,
                pregunta: '',
                tipo: 'opcion_unica',
                area_id: null,
                niveles_calificacion_id: null,
                sede_id: null,
                opciones: [
                    { texto: '' },
                    { texto: '' }
                ],
                is_active: true
            },
            subpreguntaForm: {
                id: null,
                pregunta_texto: '',
                tipo: 'opcion_unica',
                opciones: [
                    { texto: '' },
                    { texto: '' }
                ]
            },
            sedeNombre: 'Cargando...',
             // 🔥 NUEVO: Estados para rangos de indicador
        configuracionRangos: {
            '0-6': {
                activo: false,
                pregunta_texto: '',
                tipo: 'opcion_unica',
                opciones: [{ texto: '' }, { texto: '' }]
            },
            '7-8': {
                activo: false, 
                pregunta_texto: '',
                tipo: 'opcion_unica',
                opciones: [{ texto: '' }, { texto: '' }]
            },
            '9-10': {
                activo: false,
                pregunta_texto: '',
                tipo: 'opcion_unica', 
                opciones: [{ texto: '' }, { texto: '' }]
            }
        },
        mostrandoConfiguracionRangos: false,


        }

        
    },
    
    computed: {
        mostrarOpcionesPregunta() {
            return ['opcion_unica', 'opcion_multiple', 'opcion_unica_texto_libre'].includes(this.preguntaForm.tipo);
        },
            // 🔥 NUEVO: Verificar si es pregunta indicador
        esPreguntaIndicador() {
            return this.preguntaForm.tipo === 'indicador_0_10';
        },
        
        // 🔥 NUEVO: Contar rangos activos
        totalRangosActivos() {
            return Object.values(this.configuracionRangos).filter(rango => rango.activo).length;
        }
    },
    async mounted() {
        await this.cargarDatosUsuario();
        await this.cargarAreas();
        await this.cargarNivelesCalificacion();
        await this.cargarPreguntas();
    },
    methods: {
        async cargarDatosUsuario() {
    try {
        const response = await fetch('/api/user');
        if (response.ok) {
            const userData = await response.json();
            
            // 🔥 MODIFICADO: Usar solo la sede asignada por el administrador
            this.sedeNombre = userData.sede ? userData.sede.nombre : 'Sede no asignada';
            this.preguntaForm.sede_id = userData.sede_id;
            
            // Si el gestor no tiene sede asignada, mostrar error y deshabilitar funciones
            if (!userData.sede_id) {
                console.warn('⚠️ Gestor sin sede asignada - Funciones limitadas');
                this.mostrarMensaje('No tienes una sede asignada. Contacta al administrador.', 'warning');
            }
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
                    this.areas = todasLasAreas.filter(area => area.sede_id === this.preguntaForm.sede_id);
                }
            } catch (error) {
                console.error('Error cargando áreas:', error);
            }
        },

        async cargarNivelesCalificacion() {
            try {
                const response = await fetch('/api/niveles-calificacion');
                if (response.ok) {
                    this.nivelesCalificacion = await response.json();
                }
            } catch (error) {
                console.error('Error cargando niveles:', error);
            }
        },

        async cargarPreguntas() {
            try {
                const response = await fetch('/api/preguntas');
                if (response.ok) {
                    const todasLasPreguntas = await response.json();
                    this.preguntas = todasLasPreguntas.filter(pregunta => 
                        pregunta.sede_id === this.preguntaForm.sede_id
                    );
                    
                    // Cargar subpreguntas para cada pregunta
                    for (let pregunta of this.preguntas) {
                        await this.cargarSubpreguntasParaPregunta(pregunta);
                    }
                }
            } catch (error) {
                console.error('Error cargando preguntas:', error);
            }
        },

        /**
 * Cargar subpreguntas para todas las opciones de una pregunta
 */
async cargarSubpreguntasParaPregunta(pregunta) {
    try {
        if (pregunta.opciones) {
            for (let opcion of pregunta.opciones) {
                await this.cargarSubpreguntasParaOpcion(opcion);
            }
        }
    } catch (error) {
        console.error('Error cargando subpreguntas para pregunta:', error);
    }
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

        
mostrarModalCrearPregunta() {
            this.esEdicionPregunta = false;
            this.preguntaForm = {
                id: null,
                pregunta: '',
                tipo: 'opcion_unica',
                area_id: null,
                niveles_calificacion_id: null,
                sede_id: this.preguntaForm.sede_id,
                opciones: [
                    { texto: '' },
                    { texto: '' }
                ],
                is_active: true
            };
            this.mostrarModalPregunta = true;
        },

        editarPregunta(pregunta) {
            this.esEdicionPregunta = true;
            this.preguntaForm = {
                id: pregunta.id,
                pregunta: pregunta.pregunta,
                tipo: pregunta.tipo,
                area_id: pregunta.area_id,
                niveles_calificacion_id: pregunta.niveles_calificacion_id,
                sede_id: pregunta.sede_id,
                opciones: pregunta.opciones ? pregunta.opciones.map(op => ({ texto: op.opcion })) : [
                    { texto: '' },
                    { texto: '' }
                ],
                is_active: pregunta.is_active
            };
            this.mostrarModalPregunta = true;
        },

        cambiarTipoPregunta() {
    if (!this.mostrarOpcionesPregunta) {
        this.preguntaForm.opciones = [];
    } else if (this.preguntaForm.opciones.length === 0) {
        this.preguntaForm.opciones = [
            { texto: '' },
            { texto: '' }
        ];
    }
    
    // 🔥 NUEVO: Mostrar/ocultar configuración de rangos
    this.mostrandoConfiguracionRangos = this.esPreguntaIndicador;
    
    // Resetear configuración si no es indicador
    if (!this.esPreguntaIndicador) {
        this.resetearConfiguracionRangos();
    }
    
    // 🔥 CORRECCIÓN: Para opcion_unica_texto_libre, asegurar opción "Otro"
    if (this.preguntaForm.tipo === 'opcion_unica_texto_libre') {
        const tieneOpcionOtro = this.preguntaForm.opciones.some(op => 
            op.texto && (op.texto.toLowerCase().includes('otro') || op.texto.toLowerCase().includes('especifique'))
        );
        
        if (!tieneOpcionOtro) {
            // Agregar automáticamente la opción "Otro" al final
            this.preguntaForm.opciones.push({ texto: 'Otro - especifique' });
            console.log('✅ Opción "Otro - especifique" agregada automáticamente al cambiar tipo');
        }
    }
},

        agregarOpcionPregunta() {
            this.preguntaForm.opciones.push({ texto: '' });
        },

        eliminarOpcionPregunta(index) {
            if (this.preguntaForm.opciones.length > 2) {
                this.preguntaForm.opciones.splice(index, 1);
            }
        },

    async guardarPregunta() {
    this.guardandoPregunta = true;
    try {
        // 🔥 CORRECIÓN: Enviar opciones como array de strings simples
        const datos = {
            pregunta: this.preguntaForm.pregunta,
            tipo: this.preguntaForm.tipo,
            area_id: this.preguntaForm.area_id,
            niveles_calificacion_id: this.preguntaForm.niveles_calificacion_id,
            sede_id: this.preguntaForm.sede_id,
            is_active: this.preguntaForm.is_active,
            opciones: this.mostrarOpcionesPregunta 
                ? this.preguntaForm.opciones.map(op => op.texto).filter(texto => texto.trim())
                : [],
            // 🔥 CORRECCIÓN: Solo enviar configuracion_rangos si es pregunta indicador Y tiene rangos activos
            configuracion_rangos: this.esPreguntaIndicador && this.totalRangosActivos > 0 ? this.configuracionRangos : []
        };

        // 🔥 CORRECCIÓN CRÍTICA: Para opcion_unica_texto_libre, asegurar que tenga opción "Otro"
        if (this.preguntaForm.tipo === 'opcion_unica_texto_libre') {
            const tieneOpcionOtro = datos.opciones.some(opcion => 
                opcion.toLowerCase().includes('otro') || opcion.toLowerCase().includes('especifique')
            );
            
            if (!tieneOpcionOtro) {
                // Agregar automáticamente la opción "Otro" al final
                datos.opciones.push('Otro - especifique');
                console.log('✅ Opción "Otro - especifique" agregada automáticamente');
            }
        }

        console.log('📤 Enviando datos CORREGIDOS:', datos);

        const url = this.esEdicionPregunta ? `/api/preguntas/${this.preguntaForm.id}` : '/api/preguntas';
        const method = this.esEdicionPregunta ? 'PUT' : 'POST';

        const response = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(datos)
        });

        if (!response.ok) {
            let errorMessage = 'Error al guardar la pregunta';
            try {
                const errorText = await response.text();
                console.error('❌ Error respuesta:', errorText);
                
                try {
                    const errorData = JSON.parse(errorText);
                    errorMessage = errorData.message || errorData.error || errorMessage;
                } catch {
                    if (errorText.includes('<!DOCTYPE')) {
                        errorMessage = 'Error del servidor: Respuesta en formato HTML inesperada';
                    } else {
                        errorMessage = `Error ${response.status}: ${errorText.substring(0, 100)}`;
                    }
                }
            } catch (parseError) {
                console.error('Error parseando respuesta de error:', parseError);
                errorMessage = `Error ${response.status}: ${response.statusText}`;
            }
            
            throw new Error(errorMessage);
        }

        const result = await response.json();
        console.log('✅ Pregunta guardada:', result);
        
        await this.cargarPreguntas();
        this.cerrarModalPregunta();
        this.mostrarMensaje(
            `Pregunta ${this.esEdicionPregunta ? 'actualizada' : 'creada'} correctamente`,
            'success'
        );

    } catch (error) {
        console.error('Error:', error);
        this.mostrarMensaje(error.message, 'error');
    } finally {
        this.guardandoPregunta = false;
    }
},

        async toggleEstadoPregunta(pregunta) {
            try {
                const response = await fetch(`/api/preguntas/${pregunta.id}/toggle`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                if (response.ok) {
                    await this.cargarPreguntas();
                    this.mostrarMensaje(
                        `Pregunta ${pregunta.is_active ? 'desactivada' : 'activada'} correctamente`,
                        'success'
                    );
                }
            } catch (error) {
                console.error('Error cambiando estado:', error);
                this.mostrarMensaje('Error al cambiar estado', 'error');
            }
        },

        gestionarSubpreguntas(pregunta) {
            this.preguntaSeleccionada = pregunta;
            this.mostrarModalSubpreguntas = true;
        },

        agregarSubpregunta(opcion) {
            this.opcionSeleccionada = opcion;
            this.subpreguntaEditando = null;
            this.subpreguntaForm = {
                id: null,
                pregunta_texto: '',
                tipo: 'opcion_unica',
                opciones: [
                    { texto: '' },
                    { texto: '' }
                ]
            };
            this.mostrarModalSubpregunta = true;
        },

        editarSubpregunta(subpregunta, opcion) {
            this.opcionSeleccionada = opcion;
            this.subpreguntaEditando = subpregunta;
            this.subpreguntaForm = {
                id: subpregunta.id,
                pregunta_texto: subpregunta.pregunta_texto,
                tipo: subpregunta.tipo,
                opciones: subpregunta.opciones ? subpregunta.opciones.map(op => ({ texto: op })) : [
                    { texto: '' },
                    { texto: '' }
                ]
            };
            this.mostrarModalSubpregunta = true;
        },

        cambiarTipoSubpregunta() {
            if (!['opcion_unica', 'opcion_multiple'].includes(this.subpreguntaForm.tipo)) {
                this.subpreguntaForm.opciones = [];
            } else if (this.subpreguntaForm.opciones.length === 0) {
                this.subpreguntaForm.opciones = [
                    { texto: '' },
                    { texto: '' }
                ];
            }
        },

        agregarOpcionSubpregunta() {
            this.subpreguntaForm.opciones.push({ texto: '' });
        },

        eliminarOpcionSubpregunta(index) {
            if (this.subpreguntaForm.opciones.length > 2) {
                this.subpreguntaForm.opciones.splice(index, 1);
            }
        },

      async guardarSubpregunta() {
    this.guardandoSubpregunta = true;
    try {
        const datos = {
            opcion_pregunta_id: this.opcionSeleccionada.id,
            pregunta_texto: this.subpreguntaForm.pregunta_texto,
            tipo: this.subpreguntaForm.tipo,
            opciones: ['opcion_unica', 'opcion_multiple'].includes(this.subpreguntaForm.tipo) 
                ? this.subpreguntaForm.opciones.map(op => op.texto).filter(texto => texto.trim())
                : []
        };

        console.log('📤 Guardando subpregunta REAL:', datos);

        // 🔥 LLAMADA REAL A LA API
        const url = this.subpreguntaEditando ? `/api/subpreguntas/${this.subpreguntaForm.id}` : '/api/subpreguntas';
        const method = this.subpreguntaEditando ? 'PUT' : 'POST';

        const response = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(datos)
        });

        if (!response.ok) {
            let errorMessage = 'Error al guardar subpregunta';
            try {
                const errorText = await response.text();
                const errorData = JSON.parse(errorText);
                errorMessage = errorData.message || errorData.error || errorMessage;
            } catch {
                errorMessage = `Error ${response.status}: ${response.statusText}`;
            }
            throw new Error(errorMessage);
        }

        const result = await response.json();
        console.log('✅ Subpregunta guardada REAL:', result);

        // 🔥 CORRECIÓN: ACTUALIZAR EL ESTADO DE LA OPCIÓN
        if (this.opcionSeleccionada) {
            // Actualizar el estado a TRUE inmediatamente
            await this.actualizarEstadoSubpreguntas(this.opcionSeleccionada.id, true);
            
            // Recargar las subpreguntas para esta opción
            await this.cargarSubpreguntasParaOpcion(this.opcionSeleccionada);
        }
        
        this.mostrarMensaje(
            `Subpregunta ${this.subpreguntaEditando ? 'actualizada' : 'creada'} correctamente`,
            'success'
        );
        this.cerrarModalSubpregunta();

    } catch (error) {
        console.error('Error:', error);
        this.mostrarMensaje(error.message, 'error');
    } finally {
        this.guardandoSubpregunta = false;
    }
},

async actualizarEstadoSubpreguntas(opcionId, tieneSubpreguntas) {
    try {
        console.log('🔄 Actualizando estado de subpreguntas para opción:', opcionId, '->', tieneSubpreguntas);
        
        // 🔥 CORRECIÓN: Usar la ruta correcta
        const response = await fetch(`/api/opciones-pregunta/${opcionId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                tiene_subpreguntas: tieneSubpreguntas
            })
        });

        if (response.ok) {
            console.log('✅ Estado de subpreguntas actualizado correctamente');
            const result = await response.json();
            console.log('📊 Resultado:', result);
            
            // 🔥 ACTUALIZAR EL ESTADO LOCAL INMEDIATAMENTE
            if (this.opcionSeleccionada) {
                this.opcionSeleccionada.tiene_subpreguntas = tieneSubpreguntas;
            }
        } else {
            const errorText = await response.text();
            console.error('❌ Error actualizando estado:', errorText);
        }
    } catch (error) {
        console.error('❌ Error en actualizarEstadoSubpreguntas:', error);
    }
},


/**
 * Cargar subpreguntas reales desde la API
 */
async cargarSubpreguntasParaOpcion(opcion) {
    try {
        console.log('🔍 Cargando subpreguntas REALES para opción:', opcion.id);
        
        const response = await fetch(`/api/subpreguntas/${opcion.id}`);
        if (response.ok) {
            const subpreguntas = await response.json();
            console.log('📝 Subpreguntas cargadas REALES:', subpreguntas);
            
            // 🔥 CORRECIÓN: Procesar las opciones de las subpreguntas
            opcion.subpreguntas = subpreguntas.map(sp => {
                // Si opciones es un string JSON, convertirlo a array
                if (typeof sp.opciones === 'string') {
                    try {
                        sp.opciones = JSON.parse(sp.opciones);
                    } catch (e) {
                        console.warn('Error parseando opciones:', e);
                        sp.opciones = [];
                    }
                }
                return sp;
            });
            
            opcion.tiene_subpreguntas = subpreguntas.length > 0;
            
            console.log('✅ Subpreguntas procesadas:', opcion.subpreguntas);
        } else {
            opcion.subpreguntas = [];
            opcion.tiene_subpreguntas = false;
        }
    } catch (error) {
        console.error('Error cargando subpreguntas:', error);
        opcion.subpreguntas = [];
        opcion.tiene_subpreguntas = false;
    }
},

/**
 * Actualizar el estado de tiene_subpreguntas en una opción
 */
async actualizarOpcionTieneSubpreguntas(opcionId, tieneSubpreguntas) {
    try {
        const response = await fetch(`/api/opciones-pregunta/${opcionId}/subpreguntas`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                tiene_subpreguntas: tieneSubpreguntas
            })
        });

        if (response.ok) {
            console.log('✅ Estado de subpreguntas actualizado para opción:', opcionId);
        }
    } catch (error) {
        console.error('Error actualizando estado de subpreguntas:', error);
    }
},


    async eliminarSubpregunta(subpregunta) {
        const result = await Swal.fire({
            title: '¿Estás seguro?',
            text: '¿Estás seguro de eliminar esta subpregunta?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        });

        if (!result.isConfirmed) return;

        try {
            console.log('🗑️ Eliminando subpregunta:', subpregunta.id);

            // 🔥 LLAMADA REAL A LA API
            const response = await fetch(`/api/subpreguntas/${subpregunta.id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            if (!response.ok) {
                throw new Error('Error al eliminar subpregunta');
            }

            // Eliminar del estado local
            if (this.opcionSeleccionada && this.opcionSeleccionada.subpreguntas) {
                this.opcionSeleccionada.subpreguntas = this.opcionSeleccionada.subpreguntas.filter(
                    sp => sp.id !== subpregunta.id
                );
                
                // 🔥 CORRECIÓN: Actualizar estado según si quedan subpreguntas
                const tieneSubpreguntas = this.opcionSeleccionada.subpreguntas.length > 0;
                await this.actualizarEstadoSubpreguntas(this.opcionSeleccionada.id, tieneSubpreguntas);
            }
            
            this.mostrarMensaje('Subpregunta eliminada correctamente', 'success');
        } catch (error) {
            console.error('Error eliminando subpregunta:', error);
            this.mostrarMensaje('Error al eliminar la subpregunta', 'error');
        }
    }
},

        cerrarModalPregunta() {
            this.mostrarModalPregunta = false;
            this.esEdicionPregunta = false;
        },

        cerrarModalSubpreguntas() {
            this.mostrarModalSubpreguntas = false;
            this.preguntaSeleccionada = null;
        },

        cerrarModalSubpregunta() {
            this.mostrarModalSubpregunta = false;
            this.opcionSeleccionada = null;
            this.subpreguntaEditando = null;
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
        /**
 * 🔥 NUEVO: Resetear configuración de rangos
 */
resetearConfiguracionRangos() {
    this.configuracionRangos = {
        '0-6': { activo: false, pregunta_texto: '', tipo: 'opcion_unica', opciones: [{ texto: '' }, { texto: '' }] },
        '7-8': { activo: false, pregunta_texto: '', tipo: 'opcion_unica', opciones: [{ texto: '' }, { texto: '' }] },
        '9-10': { activo: false, pregunta_texto: '', tipo: 'opcion_unica', opciones: [{ texto: '' }, { texto: '' }] }
    };
    this.mostrandoConfiguracionRangos = false;
},

/**
 * 🔥 NUEVO: Cuando cambia el tipo de un rango, asegurar opción "Otro" si es necesario
 */
cambiarTipoRango(rangoKey) {
    const rango = this.configuracionRangos[rangoKey];
    
    if (!['opcion_unica', 'opcion_multiple', 'opcion_unica_texto_libre'].includes(rango.tipo)) {
        rango.opciones = [];
    } else if (rango.opciones.length === 0) {
        rango.opciones = [{ texto: '' }, { texto: '' }];
    }
    
    // 🔥 CORRECCIÓN: Para opcion_unica_texto_libre, asegurar opción "Otro"
    if (rango.tipo === 'opcion_unica_texto_libre') {
        const tieneOpcionOtro = rango.opciones.some(op => 
            op.texto && (op.texto.toLowerCase().includes('otro') || op.texto.toLowerCase().includes('especifique'))
        );
        
        if (!tieneOpcionOtro) {
            // Agregar automáticamente la opción "Otro" al final
            rango.opciones.push({ texto: 'Otro - especifique' });
            console.log(`✅ Opción "Otro - especifique" agregada automáticamente al rango ${rangoKey}`);
        }
    }
},

/**
 * 🔥 NUEVO: Agregar opción a un rango
 */
agregarOpcionRango(rangoKey) {
    this.configuracionRangos[rangoKey].opciones.push({ texto: '' });
},

/**
 * 🔥 NUEVO: Eliminar opción de un rango
 */
eliminarOpcionRango(rangoKey, index) {
    const opciones = this.configuracionRangos[rangoKey].opciones;
    if (opciones.length > 2) {
        opciones.splice(index, 1);
    }
},

/**
 * 🔥 NUEVO: Obtener texto descriptivo del rango
 */
getTextoRango(rangoKey) {
    const textos = {
        '0-6': 'Puntuaciones Bajas (0-6) - Detractores',
        '7-8': 'Puntuaciones Medias (7-8) - Pasivos', 
        '9-10': 'Puntuaciones Altas (9-10) - Promotores'
    };
    return textos[rangoKey] || rangoKey;
},

/**
 * 🔥 NUEVO: Obtener texto de ejemplo para cada rango
 */
getTextoEjemploRango(rangoKey) {
    const ejemplos = {
        '0-6': '¿Podría decirnos el motivo principal por el que calificó su experiencia de esta manera?',
        '7-8': '¿Qué podríamos haber hecho para que su experiencia fuera mejor?',
        '9-10': '¿Qué fue lo que más le gustó de su experiencia con nosotros?'
    };
    return ejemplos[rangoKey] || 'Escribe la pregunta para este rango...';
},
    }

</script>

<style scoped>
.gestor-preguntas-management {
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.management-header {
    margin-bottom: 30px;
    text-align: center;
}

.management-header h2 {
    color: #333;
    margin-bottom: 10px;
    font-size: 28px;
}

.management-header p {
    color: #666;
    font-size: 16px;
}

.actions-bar {
    display: flex;
    justify-content: center;
    margin-bottom: 30px;
}

/* Estilos para las tarjetas de preguntas */
.preguntas-container {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.pregunta-card {
    border: 1px solid #e1e5e9;
    border-radius: 12px;
    padding: 24px;
    background: white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.pregunta-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    transform: translateY(-2px);
}

.pregunta-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 20px;
    gap: 20px;
}

.pregunta-info-main {
    flex: 1;
}

.pregunta-info-main h3 {
    margin: 0 0 12px 0;
    color: #2c3e50;
    font-size: 18px;
    line-height: 1.4;
}

.pregunta-meta {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.pregunta-actions {
    display: flex;
    gap: 8px;
    flex-shrink: 0;
}

/* Badges */
.tipo-badge, .area-badge, .nivel-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.tipo-badge {
    background: #e3f2fd;
    color: #1976d2;
    border: 1px solid #bbdefb;
}

.area-badge {
    background: #f3e5f5;
    color: #7b1fa2;
    border: 1px solid #e1bee7;
}

.nivel-badge {
    background: #e8f5e8;
    color: #388e3c;
    border: 1px solid #c8e6c9;
}

.tipo-badge.small {
    font-size: 10px;
    padding: 4px 8px;
}

/* Lista de opciones */
.opciones-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.opcion-item {
    padding: 16px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #e9ecef;
    transition: all 0.2s ease;
}

.opcion-item:hover {
    background: #e9ecef;
}

.opcion-item.con-subpreguntas {
    border-left: 4px solid #ff9800;
    background: #fff3e0;
}

.opcion-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.opcion-text {
    font-weight: 500;
    color: #495057;
}

.opcion-status {
    display: flex;
    gap: 10px;
    align-items: center;
}

.subpreguntas-badge {
    background: #ff9800;
    color: white;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}

.no-subpreguntas {
    color: #6c757d;
    font-size: 12px;
    font-style: italic;
}

/* Modales */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 20px;
}

.modal-container {
    background: white;
    border-radius: 12px;
    width: 90%;
    max-width: 800px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

.large-modal {
    max-width: 1000px;
}

.modal-header {
    padding: 24px;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    background: #f8f9fa;
    border-radius: 12px 12px 0 0;
}

.modal-header h3 {
    margin: 0;
    color: #2c3e50;
    font-size: 20px;
}

.modal-subtitle {
    margin: 5px 0 0 0;
    color: #6c757d;
    font-size: 14px;
}

.btn-close {
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    color: #6c757d;
    padding: 5px;
    border-radius: 4px;
}

.btn-close:hover {
    background: #e9ecef;
    color: #495057;
}

.modal-form {
    padding: 24px;
}

/* Formularios */
.form-section {
    margin-bottom: 30px;
}

.form-group {
    margin-bottom: 20px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #495057;
}

.form-input, .form-select, .form-textarea {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.2s ease;
    box-sizing: border-box;
}

.form-input:focus, .form-select:focus, .form-textarea:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
}

.form-textarea {
    resize: vertical;
    min-height: 100px;
    font-family: inherit;
}

.form-input.disabled {
    background: #f8f9fa;
    color: #6c757d;
    cursor: not-allowed;
}

/* Opciones en formularios */
.opciones-container {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
    background: #f8f9fa;
}

.opcion-input-item {
    display: flex;
    gap: 12px;
    margin-bottom: 12px;
    align-items: center;
}

.opcion-input {
    flex: 1;
}

/* Contenido de subpreguntas */
.subpreguntas-content {
    padding: 20px;
    max-height: 60vh;
    overflow-y: auto;
}

.info-box {
    display: flex;
    gap: 15px;
    padding: 16px;
    background: #e3f2fd;
    border-radius: 8px;
    margin-bottom: 20px;
    border-left: 4px solid #2196f3;
    align-items: flex-start;
}

.info-box i {
    color: #2196f3;
    font-size: 18px;
    margin-top: 2px;
}

.info-box strong {
    display: block;
    margin-bottom: 5px;
    color: #1976d2;
}

.info-box p {
    margin: 0;
    color: #555;
    font-size: 14px;
}

.opcion-subpreguntas-section {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    background: white;
}

.opcion-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e9ecef;
}

.opcion-header h4 {
    margin: 0;
    color: #495057;
    display: flex;
    align-items: center;
    gap: 8px;
}

.opcion-header h4 i {
    color: #6c757d;
}

.subpreguntas-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.subpregunta-item {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 16px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.subpregunta-content {
    flex: 1;
}

.subpregunta-texto {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 8px;
}

.subpregunta-texto strong {
    flex: 1;
    margin-right: 15px;
    color: #495057;
}

.subpregunta-opciones small {
    color: #6c757d;
    font-style: italic;
}

.subpregunta-actions {
    display: flex;
    gap: 8px;
    flex-shrink: 0;
}

/* Botones */
.btn {
    padding: 12px 20px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
}

.btn-primary {
    background: #007bff;
    color: white;
}

.btn-primary:hover:not(:disabled) {
    background: #0056b3;
    transform: translateY(-1px);
}

.btn-primary:disabled {
    background: #6c757d;
    cursor: not-allowed;
    transform: none;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #545b62;
}

.btn-outline {
    background: transparent;
    border: 2px solid #007bff;
    color: #007bff;
}

.btn-outline:hover {
    background: #007bff;
    color: white;
}

.btn-sm {
    padding: 8px 16px;
    font-size: 12px;
}

.btn-icon {
    padding: 8px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    background: transparent;
    color: #6c757d;
    transition: all 0.2s ease;
}

.btn-icon:hover {
    background: #e9ecef;
    color: #495057;
}

.btn-icon.danger {
    color: #dc3545;
}

.btn-icon.danger:hover {
    background: #f8d7da;
    color: #721c24;
}

.btn-icon.success {
    color: #28a745;
}

.btn-icon.success:hover {
    background: #d4edda;
    color: #155724;
}

/* Acciones del formulario */
.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e9ecef;
}

/* Estados vacíos */
.no-subpreguntas {
    text-align: center;
    padding: 30px;
    color: #6c757d;
    font-style: italic;
    background: #f8f9fa;
    border-radius: 8px;
    border: 2px dashed #dee2e6;
}

.no-subpreguntas i {
    font-size: 24px;
    margin-bottom: 10px;
    display: block;
}

/* Responsive */
@media (max-width: 768px) {
    .pregunta-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .pregunta-actions {
        justify-content: flex-start;
        margin-top: 15px;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .modal-container {
        width: 95%;
        margin: 10px;
    }
    
    .subpregunta-texto {
        flex-direction: column;
        gap: 8px;
    }
}
/* Estilos para configuración de rangos */
.configuracion-rangos {
    margin-top: 2rem;
    padding: 1.5rem;
    background: #f8f9fa;
    border-radius: 12px;
    border: 2px dashed #d1d5db;
}

.seccion-titulo h4 {
    color: #1F2937;
    margin-bottom: 0.5rem;
}

.seccion-titulo p {
    color: #6B7280;
    font-size: 0.9rem;
}

.rango-item {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    border: 1px solid #e5e7eb;
}

.rango-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.rango-info h5 {
    color: #1F2937;
    margin: 0 0 0.25rem 0;
}

.rango-valores {
    background: #3B82F6;
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.875rem;
    font-weight: 500;
}

/* Toggle Switch */
.toggle-label {
    display: flex;
    align-items: center;
    cursor: pointer;
}

.toggle-input {
    display: none;
}

.toggle-slider {
    width: 50px;
    height: 24px;
    background: #d1d5db;
    border-radius: 12px;
    position: relative;
    transition: background 0.3s;
    margin-right: 0.5rem;
}

.toggle-slider::before {
    content: '';
    position: absolute;
    width: 20px;
    height: 20px;
    background: white;
    border-radius: 50%;
    top: 2px;
    left: 2px;
    transition: transform 0.3s;
}

.toggle-input:checked + .toggle-slider {
    background: #10B981;
}

.toggle-input:checked + .toggle-slider::before {
    transform: translateX(26px);
}

.toggle-text {
    font-size: 0.875rem;
    color: #6B7280;
}

.rango-contenido {
    border-top: 1px solid #e5e7eb;
    padding-top: 1rem;
}

.rangos-info {
    background: #EFF6FF;
    border: 1px solid #3B82F6;
    border-radius: 6px;
    padding: 0.75rem;
    text-align: center;
    color: #1E40AF;
    font-size: 0.875rem;
}

.rangos-info i {
    margin-right: 0.5rem;
}
</style>