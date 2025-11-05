<template>
    <div class="calificador-container">
        <!-- Loading mientras carga datos -->
        <div v-if="cargandoDatos" class="loading-container">
            <div class="spinner-large"></div>
            <p>Cargando información...</p>
        </div>

        <!-- Contenido principal cuando los datos están listos -->
        <template v-else-if="areaSeleccionada && sedeNombre">
            <!-- Vista Principal - Selección de Nivel -->
            <div class="vista-seleccion">
                <div class="header-info">
                    <h1 class="calificador-titulo">¿Cómo fue tu atención?</h1>
                    <div class="ubicacion-info">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>{{ areaSeleccionada.codigo }} - {{ sedeNombre.toUpperCase() }}</span>
                    </div>
                </div>
                
                <!-- 🔥 NUEVO: Mostrar solo indicadores permitidos según configuración del área -->
                <div v-if="areaSeleccionada.permite_csat" class="caritas-wrapper">
                    <div class="caritas">
                        <!-- Caritas de niveles -->
                        <div v-for="nivel in nivelesCalificacion" :key="nivel.id" class="carita-group">
                            <div class="carita" @click.stop="iniciarCuestionario(nivel)" :title="nivel.nombre">
                                <svg :viewBox="getSvgViewBox(nivel.id)">
                                    <circle cx="60" cy="60" r="58" :fill="getColorFondo(nivel.id)"/>
                                    <circle cx="45" cy="55" r="10" :fill="getColorOjos(nivel.id)"/>
                                    <circle cx="75" cy="55" r="10" :fill="getColorOjos(nivel.id)"/>
                                    <path :d="getBocaSvg(nivel.id)" :stroke="getColorOjos(nivel.id)" stroke-width="5" fill="none" stroke-linecap="round"/>
                                </svg>
                            </div>
                            <div class="carita-label" @click.stop="iniciarCuestionario(nivel)">
                                {{ nivel.nombre }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 🔥 NUEVO: Indicadores alternativos si no tiene CSAT -->
                <div v-else class="indicadores-alt-wrapper">
                    <div v-if="areaSeleccionada.permite_nps" class="indicador-nps-wrapper">
                        <h3>{{ preguntaNPS ? preguntaNPS.pregunta : 'Califica tu experiencia' }}</h3>
                        <div class="indicador-simple-container">
                            <div class="indicador-header">
                                <div class="indicador-labels">
                                    <span class="indicador-min">0</span>
                                    <span class="indicador-value">{{ respuestaIndicadorNPS }}</span>
                                    <span class="indicador-max">10</span>
                                </div>
                            </div>
                            
                            <div class="indicador-track" @mousedown="iniciarArrastreNPS" @touchstart="iniciarArrastreNPS" @click="clickIndicadorNPS">
                                <div class="indicador-progress" :style="{ width: (respuestaIndicadorNPS / 10 * 100) + '%' }"></div>
                                <div class="indicador-thumb" 
                                     :style="{ left: (respuestaIndicadorNPS / 10 * 100) + '%' }">
                                    <div class="thumb-circle"></div>
                                </div>
                            </div>
                            
                            <div class="indicador-ticks">
                                <span v-for="n in 11" :key="n" class="tick" :class="{ active: respuestaIndicadorNPS >= n-1 }" @click="seleccionarValorNPS(n-1)">
                                    {{ n-1 }}
                                </span>
                            </div>
                        </div>
                        <button @click="iniciarConNPS" class="btn-continuar-nps">
                            Evaluar
                        </button>
                    </div>

                    <!-- 🔥 NUEVO: Para áreas con solo FCR, mostrar manitas con la pregunta FCR -->
                    <div v-if="!areaSeleccionada.permite_csat && !areaSeleccionada.permite_nps && areaSeleccionada.permite_fcr" class="indicador-fcr-wrapper">
                        <div v-if="cargandoPreguntaFCR" style="text-align: center; padding: 2rem;">
                            <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #666;"></i>
                            <p>Cargando pregunta...</p>
                        </div>
                        <div v-else-if="preguntaFCRPrincipal">
                            <h2 style="color: #1F2937; text-align: center; margin-bottom: 2rem; font-size: 1.8rem;">
                                {{ preguntaFCRPrincipal.pregunta }}
                            </h2>
                            <div class="fcr-options">
                                <div v-for="opcion in preguntaFCRPrincipal.opciones" 
                                     :key="opcion.id" 
                                     class="fcr-option" 
                                     @click="iniciarConFCR(opcion.opcion === 'Sí', opcion)">
                                    <div class="fcr-icon" :class="opcion.opcion === 'Sí' ? 'bien' : 'mal'">
                                        <i :class="opcion.opcion === 'Sí' ? 'fas fa-thumbs-up' : 'fas fa-thumbs-down'"></i>
                                    </div>
                                    <span>{{ opcion.opcion }}</span>
                                </div>
                            </div>
                        </div>
                        <div v-else style="text-align: center; padding: 2rem; color: #ef4444;">
                            <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                            <p><strong>No hay pregunta FCR configurada</strong></p>
                            <p style="font-size: 0.9rem; margin-top: 0.5rem;">Por favor, crea una pregunta FCR desde el panel de administración.</p>
                        </div>
                    </div>
                </div>

                <!-- 🔥 NUEVO: Mostrar mensaje solo si hay CSAT -->
                <div v-if="areaSeleccionada.permite_csat" class="info-adicional">
                    <p>Selecciona cómo calificarías tu experiencia para continuar con la evaluación</p>
                </div>
            </div>

            <!-- Modal Cuestionario -->
            <div v-if="mostrarCuestionario" class="modal-overlay" @click.self="cancelarCuestionario">
                <div class="modal-container" @click.stop>
                    <div class="cuestionario-content">
                        <div class="cuestionario-header">
                            <div class="progreso-info" style="text-align: center;">
                                <div class="progreso">
                                    <div class="progreso-bar">
                                        <div class="progreso-fill" :style="{ width: porcentajeProgreso + '%' }"></div>
                                    </div>
                                    <span class="progreso-texto">
                                        <template v-if="modoSubpreguntas">
                                            Subpregunta {{ subpreguntaIndex + 1 }} de {{ subpreguntasActuales.length }}
                                        </template>
                                        <template v-else>
                                            Pregunta {{ preguntaActual + 1 }} de {{ preguntas.length }}
                                        </template>
                                    </span>
                                </div>
                                <div class="nivel-info">
                                    <span class="nivel-badge">{{ nivelSeleccionado ? nivelSeleccionado.nombre : 'Subpregunta' }}</span>
                                </div>
                            </div>
                        </div>

<!-- VISTA PRINCIPAL: PREGUNTA NORMAL O DE RANGO -->
<template v-if="!modoSubpreguntas">
    <div class="pregunta-actual">
        <div class="pregunta-header">
            <h3 class="pregunta-texto">{{ preguntaActualData.pregunta }}</h3>
            <span class="tipo-pregunta">{{ getTipoTexto(preguntaActualData.tipo) }}</span>
            <!-- 🔥 NUEVO: Indicador de pregunta de rango -->
            <span v-if="preguntaActualData.es_pregunta_rango" class="rango-indicator">
                📊 Pregunta según tu calificación
            </span>
        </div>
    
    <!-- 🔥 NUEVO: Indicador 0-10 (para preguntas NPS en el modal) -->
        <div v-if="preguntaActualData.tipo === 'indicador_0_10'" class="indicador-container">
            <div class="indicador-header">
                <div class="indicador-labels">
                    <span class="indicador-min">0</span>
                    <span class="indicador-value">{{ respuestas[preguntaActualData.id]?.valor ?? respuestaIndicadorValor }}</span>
                    <span class="indicador-max">10</span>
                </div>
            </div>
            
            <div class="indicador-track" @mousedown="iniciarArrastreIndicador($event)" @click="clickIndicador($event)">
                <div class="indicador-progress" 
                     :style="{ width: ((respuestas[preguntaActualData.id]?.valor ?? respuestaIndicadorValor) / 10 * 100) + '%' }">
                </div>
                <div class="indicador-thumb" 
                     :style="{ left: ((respuestas[preguntaActualData.id]?.valor ?? respuestaIndicadorValor) / 10 * 100) + '%' }">
                    <div class="thumb-circle"></div>
                </div>
            </div>
            
            <div class="indicador-ticks">
                <span v-for="n in 11" :key="n" 
                      class="tick" 
                      :class="{ active: (respuestas[preguntaActualData.id]?.valor ?? respuestaIndicadorValor) >= n-1 }" 
                      @click="seleccionarValorIndicador(n-1)">
                    {{ n-1 }}
                </span>
            </div>
        </div>
    
    <!-- ✅ CORRECCIÓN: Template para opción única -->
                <div v-if="preguntaActualData.tipo === 'opcion_unica'" class="opciones-container">
                    <div 
                        v-for="(opcion, index) in obtenerOpcionesPregunta()" 
                        :key="opcion.id || index"
                        class="opcion-item"
                        :class="{ 
                            seleccionada: esOpcionSeleccionada(opcion, index) 
                        }"
                        @click="seleccionarOpcionUnica(opcion, index)"
                    >
                        <div class="opcion-radio">
                            <div 
                                class="radio-circle" 
                                :class="{ 
                                    activo: esOpcionSeleccionada(opcion, index) 
                                }"
                            ></div>
                        </div>
                        <span class="opcion-texto">{{ obtenerTextoOpcion(opcion) }}</span>
                    </div>
                </div>

        <!-- Opción Múltiple (para preguntas normales Y de rango) -->
        <div v-if="preguntaActualData.tipo === 'opcion_multiple'" class="opciones-container">
            <div v-for="(opcion, index) in obtenerOpcionesPregunta()" 
                 :key="index"
                 class="opcion-item"
                 :class="{ seleccionada: respuestaMultipleIncluye(opcion) }"
                 @click="toggleOpcionMultipleRango(opcion, index)">
                <div class="opcion-checkbox">
                    <div class="checkbox-square" 
                         :class="{ activo: respuestaMultipleIncluye(opcion) }">
                        <i v-if="respuestaMultipleIncluye(opcion)" class="fas fa-check"></i>
                    </div>
                </div>
                <span class="opcion-texto">{{ obtenerTextoOpcion(opcion) }}</span>
            </div>
        </div>

        <!-- Texto Libre (para preguntas normales Y de rango) -->
        <div v-if="preguntaActualData.tipo === 'texto_libre'" class="texto-libre-container">
            <textarea 
                v-model="respuestaLibre"
                placeholder="Escribe tu respuesta aquí..."
                rows="4"
                class="texto-libre-input"
                @input="errorValidacion = ''"
            ></textarea>
            <div class="caracteres-info">
                {{ respuestaLibre.length }}/500 caracteres
            </div>
        </div>

        <!-- Opción Única con Texto Libre (para preguntas normales Y de rango) -->
        <div v-if="preguntaActualData.tipo === 'opcion_unica_texto_libre'" class="opciones-container">
            <div v-for="(opcion, index) in obtenerOpcionesPregunta()" 
                 :key="index"
                 class="opcion-wrapper"
                 :class="{ 
                     'con-texto-libre': opcionEsTextoLibre(obtenerTextoOpcion(opcion)) && 
                                       (respuestaUnica === opcion.id || respuestaUnica === opcion)
                 }">
                
                <div class="opcion-item"
                     :class="{ 
                         'seleccionada': respuestaUnica === opcion.id || respuestaUnica === opcion,
                         'con-texto-libre': opcionEsTextoLibre(obtenerTextoOpcion(opcion))
                     }"
                     @click="seleccionarOpcionUnicaConTextoRango(opcion, index)">
                    
                    <div class="opcion-radio">
                        <div class="radio-circle" 
                             :class="{ 'activo': respuestaUnica === opcion.id || respuestaUnica === opcion }">
                        </div>
                    </div>
                    
                    <span class="opcion-texto">{{ obtenerTextoOpcion(opcion) }}</span>
                </div>
                
                <!-- Campo de texto libre cuando se selecciona "Otro" -->
                <div v-if="opcionEsTextoLibre(obtenerTextoOpcion(opcion)) && 
                          (respuestaUnica === opcion.id || respuestaUnica === opcion)" 
                     class="texto-libre-opcion"> 
                    <textarea 
                        v-model="textoLibreOpcion"
                        placeholder="Por favor, especifica tu respuesta..."
                        rows="3"
                        class="texto-libre-input-opcion"
                        @click.stop
                        @input="guardarTextoLibreOpcionRango()"
                        @focus="manejarFocoTexto"
                    ></textarea>
                    <div class="caracteres-info">
                        {{ textoLibreOpcion.length }}/500 caracteres
                    </div>
                </div>
            </div>
        </div>

        <!-- Validación -->
        <div v-if="errorValidacion" class="error-validacion">
            <i class="fas fa-exclamation-circle"></i>
            {{ errorValidacion }}
        </div>
    </div>
</template>

<!-- VISTA SUBPREGUNTAS -->
<template v-else>
    <div class="subpregunta-actual">
        <div v-if="subpreguntaActual" class="subpregunta-header">
            <h3 class="subpregunta-texto">{{ subpreguntaActual.pregunta_texto }}</h3>
            <span class="tipo-subpregunta">{{ getTipoTexto(subpreguntaActual.tipo) }}</span>
        </div>
        <div v-else class="cargando-subpregunta">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Cargando subpreguntas...</p>
        </div>

        <!-- SUBPREGUNTA: Opción Múltiple -->
        <div v-if="subpreguntaActual && subpreguntaActual.tipo === 'opcion_multiple'" class="opciones-container">
            <div v-for="(opcion, index) in subpreguntaActual.opciones" 
                 :key="index"
                 class="opcion-item"
                 :class="{ seleccionada: (respuestasSubpreguntas[subpreguntaActual.id]?.opcionesSeleccionadas || []).includes(opcion) }"
                 @click="toggleOpcionMultipleSubpregunta(subpreguntaActual, opcion)">
                <div class="opcion-checkbox">
                    <div class="checkbox-square" 
                         :class="{ activo: (respuestasSubpreguntas[subpreguntaActual.id]?.opcionesSeleccionadas || []).includes(opcion) }">
                        <i v-if="(respuestasSubpreguntas[subpreguntaActual.id]?.opcionesSeleccionadas || []).includes(opcion)" 
                           class="fas fa-check"></i>
                    </div>
                </div>
                <span class="opcion-texto">{{ opcion }}</span>
            </div>
        </div>

        <!-- SUBPREGUNTA: Opción Única -->
        <div v-if="subpreguntaActual && (subpreguntaActual.tipo === 'opcion_unica' || subpreguntaActual.tipo === 'opcion_unica_texto_libre')" class="opciones-container">
            <div v-for="(opcion, index) in subpreguntaActual.opciones" 
                 :key="index"
                 class="opcion-item"
                 :class="{ seleccionada: respuestasSubpreguntas[subpreguntaActual.id]?.opcionSeleccionada === opcion }"
                 @click="seleccionarOpcionSubpregunta(subpreguntaActual, opcion)">
                <div class="opcion-radio">
                    <div class="radio-circle" 
                         :class="{ activo: respuestasSubpreguntas[subpreguntaActual.id]?.opcionSeleccionada === opcion }">
                    </div>
                </div>
                <span class="opcion-texto">{{ opcion }}</span>
            </div>
            
            <!-- Texto libre para opción única con texto libre -->
            <div v-if="subpreguntaActual.tipo === 'opcion_unica_texto_libre' && 
                       respuestasSubpreguntas[subpreguntaActual.id]?.opcionSeleccionada && 
                       (respuestasSubpreguntas[subpreguntaActual.id].opcionSeleccionada.toLowerCase().includes('otro') || respuestasSubpreguntas[subpreguntaActual.id].opcionSeleccionada.toLowerCase().includes('especifique'))" 
                 class="texto-libre-subpregunta mt-3">
                <textarea 
                    v-model="respuestasSubpreguntas[subpreguntaActual.id].texto"
                    placeholder="Especifique por favor..."
                    rows="3"
                    class="texto-libre-input"
                ></textarea>
            </div>
        </div>

        <!-- SUBPREGUNTA: Texto Libre -->
        <div v-if="subpreguntaActual && subpreguntaActual.tipo === 'texto_libre'" class="texto-libre-container">
            <textarea 
                :value="respuestasSubpreguntas[subpreguntaActual.id]?.texto || ''"
                @input="actualizarTextoSubpregunta(subpreguntaActual, $event)"
                placeholder="Escribe tu respuesta..."
                rows="4"
                class="texto-libre-input"
            ></textarea>
            <div class="caracteres-info">
                {{ (respuestasSubpreguntas[subpreguntaActual.id]?.texto || '').length }}/500 caracteres
            </div>
        </div>

        <!-- SUBPREGUNTA: Indicador 0-10 -->
        <div v-if="subpreguntaActual && subpreguntaActual.tipo === 'indicador_0_10'" class="indicador-container">
            <div class="indicador-header">
                <div class="indicador-labels">
                    <span class="indicador-min">0</span>
                    <span class="indicador-value">{{ respuestasSubpreguntas[subpreguntaActual.id]?.valor || 5 }}</span>
                    <span class="indicador-max">10</span>
                </div>
            </div>
            
            <div class="indicador-track" @mousedown="iniciarArrastreSubpregunta(subpreguntaActual, $event)">
                <div class="indicador-progress" 
                     :style="{ width: ((respuestasSubpreguntas[subpreguntaActual.id]?.valor || 5) / 10 * 100) + '%' }">
                </div>
                <div class="indicador-thumb" 
                     :style="{ left: ((respuestasSubpreguntas[subpreguntaActual.id]?.valor || 5) / 10 * 100) + '%' }">
                </div>
            </div>
        </div>

        <!-- Validación para subpreguntas -->
        <div v-if="errorValidacion" class="error-validacion">
            <i class="fas fa-exclamation-circle"></i>
            {{ errorValidacion }}
        </div>
    </div>
</template>

                        <div class="navegacion-modal">
                            <!-- 🔥 CORRECCIÓN: Botón retroceder mejorado -->
                            <button v-if="modoSubpreguntas && subpreguntaIndex > 0" 
                                    @click="subpreguntaAnterior" 
                                    class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Subpregunta Anterior
                            </button>
                            
                            <!-- 🔥 CORRECCIÓN: No permitir volver si la primera pregunta es un indicador NPS -->
                            <button v-else-if="preguntaActual > 0 && !(preguntaActual === 1 && preguntas[0] && preguntas[0].tipo === 'indicador_0_10')" 
                                    @click="preguntaAnterior" 
                                    class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Pregunta Anterior
                            </button>
                            
                            <button v-else 
                                    @click="cancelarCuestionario" 
                                    class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </button>

                            <button @click="siguientePregunta" class="btn btn-primary" :disabled="!puedeContinuar">
                                <span v-if="cargando">
                                    <i class="fas fa-spinner fa-spin"></i> Procesando...
                                </span>
                                <span v-else>
                                    {{ textoBotonContinuar }}
                                    <i class="fas fa-arrow-right"></i>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Final - Agradecimiento -->
            <div v-if="mostrarAgradecimiento" class="modal-overlay">
                <div class="modal-container final-modal" @click.stop>
                    <div class="final-content">
                        <div class="final-icon">🎉</div>
                        <h2 class="final-titulo">¡Gracias por tu opinión!</h2>
                        <p class="final-texto">Tu evaluación ha sido registrada exitosamente.</p>
                        
                        <!-- Barra de progreso para cierre automático -->
                        <div class="cierre-automatico">
                            <div class="tiempo-restante">
                                Cerrando en {{ tiempoRestante }} segundos...
                            </div>
                            <div class="barra-progreso">
                                <div class="barra-progreso-fill" :style="{ width: porcentajeTiempo + '%' }"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- Error si faltan datos -->
        <div v-else class="error-container">
            <div class="error-content">
                <i class="fas fa-exclamation-triangle error-icon"></i>
                <h2>Datos incompletos</h2>
                <p>No se pudo cargar la información necesaria para calificar.</p>
                <button @click="goToAreas" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Volver a seleccionar área
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

export default {
    name: 'Calificacion',
    data() {
        return {
            areaSeleccionada: null,
            sedeNombre: '',
            cargandoDatos: true,
            todasLasPreguntas: [], // 🔥 NUEVO: Para almacenar todas las preguntas
            preguntaNPS: null, // 🔥 NUEVO: Pregunta NPS para mostrar en el título inicial
            preguntaFCRPrincipal: null, // 🔥 NUEVO: Pregunta FCR principal desde BD
            cargandoPreguntaFCR: false, // 🔥 NUEVO: Estado de carga de pregunta FCR
            
            // Niveles de calificación
           nivelesCalificacion: [
            { 
                id: 1, 
                nombre: 'Muy Insatisfecho', 
                valor: 1,
                emoji: '😠',
                color: '#EF4444'
            },
            { 
                id: 2, 
                nombre: 'Insatisfecho', 
                valor: 2,
                emoji: '😕',
                color: '#F59E0B'
            },
            { 
                id: 3, 
                nombre: 'Satisfecho', 
                valor: 3,
                emoji: '😊',
                color: '#10B981'
            },
            { 
                id: 4, 
                nombre: 'Muy Satisfecho', 
                valor: 4,
                emoji: '😍',
                color: '#3B82F6'
            }
        ],
            
            // Datos del cuestionario
            nivelSeleccionado: null,
            preguntas: [],
            preguntaActual: 0,
            respuestas: {},
            cargando: false,
            errorValidacion: '',
            
            // Estados modales
            mostrarCuestionario: false,
            mostrarAgradecimiento: false,
            
            // Temporizador cierre automático
            tiempoRestante: 5,
            intervalo: null,

            // Datos para indicador 0-10
            respuestaIndicadorValor: 5, // Valor por defecto
            arrastrando: false,
            
            // 🔥 NUEVO: Variables para NPS y FCR
            respuestaIndicadorNPS: 5, // Valor por defecto para NPS inicial
            respuestaFCR: null, // null, true (bien) o false (mal)
         
             // 🔥 NUEVO: Estados mejorados para subpreguntas
            modoSubpreguntas: false,
            subpreguntasActuales: [],
            subpreguntaIndex: 0,
            subpreguntasActivas: {},
            respuestasSubpreguntas: {},
            emojisIndicador: [
                { emoji: '😠', label: 'Muy Malo' },
                { emoji: '😕', label: 'Malo' },
                { emoji: '😐', label: 'Regular' },
                { emoji: '😊', label: 'Bueno' },
                { emoji: '😍', label: 'Excelente' }
            ],
        
        // Datos para opción única con texto libre - CORREGIDO
        textoLibreOpcion: '',
        opcionTextoLibreSeleccionada: null,
        respuestaUnica: null, // AGREGAR ESTA LÍNEA
        
        // 🔥 NUEVO: Variables para flujo secuencial de tipos de calificación
        tiposCalificacionSecuencia: [], // Array con el orden de tipos a procesar ['csat', 'nps', 'fcr']
        tipoCalificacionActual: null, // Tipo actual que se está procesando ('csat', 'nps', 'fcr')
        indiceTipoActual: 0, // Índice del tipo actual en la secuencia
        respuestasAcumuladas: {}, // Acumular todas las respuestas de todos los tipos
        respuestasSubpreguntasAcumuladas: [], // Acumular subpreguntas de todos los tipos
        respuestasRangosAcumuladas: [] // Acumular rangos de todos los tipos
        }
    },
    computed: {
    preguntaActualData() {
        const pregunta = this.preguntas[this.preguntaActual] || {};
        console.log('=== PREGUNTA ACTUAL DEBUG ===');
        console.log('Pregunta:', pregunta.pregunta);
        console.log('Tipo:', pregunta.tipo);
        console.log('Opciones:', pregunta.opciones);
        console.log('Respuesta única:', this.respuestaUnica);
        console.log('Texto libre:', this.textoLibreOpcion);
        console.log('Opción texto libre seleccionada:', this.opcionTextoLibreSeleccionada);
        console.log('Respuestas objeto:', this.respuestas);
        console.log('=============================');
        return pregunta;
    },
    esUltimaPregunta() {
        return this.preguntaActual === this.preguntas.length - 1;
    },
    porcentajeProgreso() {
        if (this.modoSubpreguntas) {
            return this.subpreguntasActuales.length > 0 
                ? ((this.subpreguntaIndex + 1) / this.subpreguntasActuales.length) * 100 
                : 0;
        }
        return this.preguntas.length > 0 ? ((this.preguntaActual + 1) / this.preguntas.length) * 100 : 0;
    },
    porcentajeTiempo() {
        return ((5 - this.tiempoRestante) / 5) * 100;
    },
    porcentajeIndicador() {
        return (this.respuestaIndicadorValor / 10) * 100;
    },
    puedeContinuar() {
        if (this.cargando) return false;

        // 🔥 CORRECIÓN: Validación separada para modo subpreguntas
        if (this.modoSubpreguntas) {
            return this.validarSubpreguntaActual();
        }

        // Validación normal de la pregunta principal
        const pregunta = this.preguntaActualData;
        if (!pregunta) return false;

        // 🔥 NUEVO: Validación especial para preguntas de rango
        if (pregunta.es_pregunta_rango) {
            switch (pregunta.tipo) {
                case 'opcion_unica':
                    return this.respuestaUnica !== null;
                case 'opcion_multiple':
                    return this.respuestaMultiple.length > 0;
                case 'texto_libre':
                    return this.respuestaLibre.trim().length > 0;
                case 'indicador_0_10':
                    return this.respuestas[pregunta.id] !== undefined;
                case 'opcion_unica_texto_libre':
                    if (this.respuestaUnica === null) return false;
                    if (this.opcionTextoLibreSeleccionada) {
                        return this.textoLibreOpcion.trim().length > 0;
                    }
                    return true;
                default:
                    return false;
            }
        }
        // Validación para preguntas normales
        switch (pregunta.tipo) {
            case 'opcion_unica':
                return this.respuestaUnica !== null;
            case 'opcion_multiple':
                return this.respuestaMultiple.length > 0;
            case 'texto_libre':
                return this.respuestaLibre.trim().length > 0;
            case 'indicador_0_10':
                return this.respuestas[pregunta.id] !== undefined;
            case 'opcion_unica_texto_libre':
                if (this.respuestaUnica === null) return false;
                if (this.opcionTextoLibreSeleccionada) {
                    return this.textoLibreOpcion.trim().length > 0;
                }
                return true;
            default:
                return false;
        }
    
    },
    // ✅ CORRECCIÓN: Computed respuestaUnica mejorado
respuestaUnica: {
    get() {
        const preguntaId = this.preguntaActualData.id;
        const respuesta = this.respuestas[preguntaId];
        
        console.log('🔍 GET respuestaUnica:', {
            preguntaId,
            respuesta,
            tipo: this.preguntaActualData.tipo
        });
        
        // Para opcion_unica_texto_libre, extraer solo el ID de la opción
        if (this.preguntaActualData.tipo === 'opcion_unica_texto_libre') {
            if (respuesta && typeof respuesta === 'object') {
                return respuesta.opcion_seleccionada_id || null;
            }
            return respuesta || null;
        }
        
        // Para preguntas normales de opción única
        return respuesta || null;
    },
    set(value) {
        const preguntaId = this.preguntaActualData.id;
        console.log('💾 SET respuestaUnica:', { preguntaId, value });
        
        // Para opcion_unica_texto_libre, mantener estructura de objeto
        if (this.preguntaActualData.tipo === 'opcion_unica_texto_libre') {
            const respuestaActual = this.respuestas[preguntaId] || {};
            this.respuestas[preguntaId] = {
                ...respuestaActual,
                opcion_seleccionada_id: value
            };
        } else {
            // Para opción única normal, guardar directamente el valor
            this.respuestas[preguntaId] = value;
        }
        
        console.log('📦 Respuestas actualizadas:', this.respuestas);
    }
},
    respuestaMultiple: {
        get() {
            return this.respuestas[this.preguntaActualData.id] || [];
        },
        set(value) {
            this.respuestas[this.preguntaActualData.id] = value;
        }
    },
    respuestaLibre: {
        get() {
            return this.respuestas[this.preguntaActualData.id] || '';
        },
        set(value) {
            if (value.length <= 500) {
                this.respuestas[this.preguntaActualData.id] = value;
            }
        }
    },
    // 🔥 NUEVO: Determinar texto del botón
    textoBotonContinuar() {
        if (this.modoSubpreguntas) {
            return this.esUltimaSubpregunta ? 'Finalizar Calificación' : 'Continuar';
        }
        return this.esUltimaPregunta ? 'Calificar' : 'Siguiente';
    },
    esUltimaSubpregunta() {
        return this.modoSubpreguntas && this.subpreguntaIndex === this.subpreguntasActuales.length - 1;
    },
    // 🔥 NUEVO: Computed para subpregunta actual
    subpreguntaActual() {
        return this.subpreguntasActuales[this.subpreguntaIndex] || null;
    },
    // 🔥 NUEVO: Verificar si hay subpreguntas activas
    tieneSubpreguntasActivas() {
        return this.modoSubpreguntas && this.subpreguntasActuales.length > 0;
    }

},

    async mounted() {
    // Verificar si hay área seleccionada en localStorage
    const areaGuardada = localStorage.getItem('area_seleccionada');
    const sedeGuardada = localStorage.getItem('sede_seleccionada');
    
    if (!areaGuardada || !sedeGuardada) {
        // Si no hay datos, redirigir a áreas
        this.$router.push('/areas');
        return;
    }
    
    await this.cargarDatosIniciales();
    this.debugFlujoPreguntas();
},
    beforeUnmount() {
        // Limpiar intervalo al destruir el componente
        if (this.intervalo) {
            clearInterval(this.intervalo);
        }
    },
    methods: {
        /**
         * 🔥 NUEVO: Determinar la secuencia de tipos de calificación según el área
         */
        determinarSecuenciaTipos() {
            const tipos = [];
            
            if (this.areaSeleccionada?.permite_csat) {
                tipos.push('csat');
            }
            if (this.areaSeleccionada?.permite_nps) {
                tipos.push('nps');
            }
            if (this.areaSeleccionada?.permite_fcr) {
                tipos.push('fcr');
            }
            
            this.tiposCalificacionSecuencia = tipos;
            this.indiceTipoActual = 0;
            this.tipoCalificacionActual = tipos.length > 0 ? tipos[0] : null;
            
            // Inicializar acumuladores
            this.respuestasAcumuladas = {};
            this.respuestasSubpreguntasAcumuladas = [];
            this.respuestasRangosAcumuladas = [];
            
            console.log('📋 Secuencia de tipos determinada:', this.tiposCalificacionSecuencia);
        },
        
        /**
         * 🔥 NUEVO: Acumular respuestas del tipo actual antes de avanzar
         */
        acumularRespuestasActuales() {
            // Acumular respuestas normales
            this.respuestasAcumuladas = { ...this.respuestasAcumuladas, ...this.respuestas };
            
            // Acumular subpreguntas
            const subpreguntasActuales = this.extraerRespuestasSubpreguntas();
            this.respuestasSubpreguntasAcumuladas = [...this.respuestasSubpreguntasAcumuladas, ...subpreguntasActuales];
            
            // Acumular rangos
            const rangosActuales = this.extraerRespuestasRangos();
            this.respuestasRangosAcumuladas = [...this.respuestasRangosAcumuladas, ...rangosActuales];
            
            console.log('💾 Respuestas acumuladas:', {
                respuestas: Object.keys(this.respuestasAcumuladas).length,
                subpreguntas: this.respuestasSubpreguntasAcumuladas.length,
                rangos: this.respuestasRangosAcumuladas.length
            });
        },
        
        /**
         * 🔥 NUEVO: Guardar un tipo individual de calificación
         */
        async guardarCalificacionTipoIndividual() {
            try {
                console.log('💾 Guardando calificación individual para tipo:', this.tipoCalificacionActual);
                
                const sedeResponse = await fetch(`/api/sedes/buscar?nombre=${encodeURIComponent(this.sedeNombre)}`);
                let sedeId = null;
                if (sedeResponse.ok) {
                    const sedeData = await sedeResponse.json();
                    sedeId = sedeData.id;
                } else {
                    sedeId = 1;
                }

                // Preparar respuestas del tipo actual
                let respuestasFormato = this.respuestas;
                if (respuestasFormato && typeof respuestasFormato === 'object' && !Array.isArray(respuestasFormato)) {
                    if (Object.keys(respuestasFormato).length === 0) {
                        respuestasFormato = {};
                    }
                } else if (!respuestasFormato || Array.isArray(respuestasFormato)) {
                    respuestasFormato = {};
                }

                // Preparar datos para subpreguntas y rangos del tipo actual
                const respuestasSubpreguntasActuales = this.extraerRespuestasSubpreguntas();
                const respuestasRangosActuales = this.extraerRespuestasRangos();

                const calificacionData = {
                    area_id: this.areaSeleccionada.id,
                    nivel_calificacion_id: this.nivelSeleccionado?.esFCR ? null : (this.nivelSeleccionado?.id || null),
                    sede_id: sedeId,
                    respuestas: respuestasFormato,
                    respuestas_subpreguntas: respuestasSubpreguntasActuales,
                    respuestas_rangos: respuestasRangosActuales
                };

                console.log('📤 Guardando calificación tipo individual:', this.tipoCalificacionActual, calificacionData);

                const response = await fetch('/api/calificaciones/completa', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(calificacionData)
                });

                if (!response.ok) {
                    throw new Error(`Error ${response.status}: ${response.statusText}`);
                }

                const result = await response.json();
                console.log('✅ Calificación tipo individual guardada exitosamente:', this.tipoCalificacionActual, result);
                return result;

            } catch (error) {
                console.error('❌ Error guardando calificación tipo individual:', error);
                // No mostrar error al usuario, solo loguear para no interrumpir el flujo
                return null;
            }
        },

        /**
         * 🔥 NUEVO: Avanzar al siguiente tipo de calificación en la secuencia
         */
        async avanzarAlSiguienteTipo() {
            // 🔥 NUEVO: Guardar el tipo actual ANTES de avanzar
            console.log('💾 Guardando tipo actual antes de avanzar:', this.tipoCalificacionActual);
            await this.guardarCalificacionTipoIndividual();
            
            // Acumular respuestas del tipo actual antes de avanzar (para referencia, aunque ya se guardó)
            this.acumularRespuestasActuales();
            
            // Limpiar respuestas actuales para el siguiente tipo
            this.respuestas = {};
            this.respuestasSubpreguntas = {};
            this.respuestasRangos = [];
            this.modoSubpreguntas = false;
            this.subpreguntasActuales = [];
            this.subpreguntaIndex = 0;
            this.preguntaActual = 0;
            this.preguntas = [];
            
            // Cerrar modal actual antes de iniciar el siguiente
            this.mostrarCuestionario = false;
            this.cargando = true;
            
            this.indiceTipoActual++;
            
            if (this.indiceTipoActual >= this.tiposCalificacionSecuencia.length) {
                // Ya terminamos todos los tipos, solo mostrar agradecimiento (ya se guardó cada uno)
                console.log('🎉 Todos los tipos completados (ya guardados individualmente)');
                this.mostrarCuestionario = false;
                this.mostrarAgradecimiento = true;
                this.iniciarTemporizadorCierre();
                this.cargando = false;
                return;
            }
            
            this.tipoCalificacionActual = this.tiposCalificacionSecuencia[this.indiceTipoActual];
            console.log('➡️ Avanzando al siguiente tipo:', this.tipoCalificacionActual);
            
            // Iniciar el siguiente tipo
            await this.iniciarTipoCalificacion(this.tipoCalificacionActual);
            this.cargando = false;
        },
        
        /**
         * 🔥 NUEVO: Iniciar un tipo específico de calificación
         */
        async iniciarTipoCalificacion(tipo) {
            console.log('🚀 Iniciando tipo de calificación:', tipo);
    // Intentar entrar a pantalla completa al iniciar un tipo
    this.solicitarPantallaCompleta();
            
            if (tipo === 'nps') {
                // Iniciar NPS
                await this.iniciarConNPS();
            } else if (tipo === 'fcr') {
                // 🔥 CORRECCIÓN: Cargar pregunta FCR y mostrarla en el modal como pregunta normal
                if (!this.preguntaFCRPrincipal) {
                    await this.cargarPreguntaFCR();
                }
                
                // Cargar la pregunta FCR como pregunta normal para que el usuario la seleccione
                if (this.preguntaFCRPrincipal) {
                    // Establecer nivel para FCR
                    this.nivelSeleccionado = { 
                        id: null, 
                        nombre: 'FCR',
                        esFCR: true
                    };
                    
                    // Cargar la pregunta FCR en el array de preguntas para mostrarla en el modal
                    this.preguntas = [this.preguntaFCRPrincipal];
                    this.preguntaActual = 0;
                    this.respuestas = {};
                    this.mostrarCuestionario = true;
                    this.cargando = false;
                    
                    console.log('✅ FCR cargado como pregunta normal, esperando selección del usuario');
                } else {
                    console.error('❌ No se pudo cargar la pregunta FCR');
                    this.cargando = false;
                }
            }
            // CSAT se inicia desde la pantalla inicial con las caritas, no necesita llamada aquí
        },
        
        /**
         * 🔥 NUEVO: Guardar calificación completa después de todos los tipos
         */
        async guardarCalificacionCompletaSecuencial() {
            // Restaurar respuestas acumuladas antes de guardar
            this.respuestas = { ...this.respuestasAcumuladas };
            
            // Combinar todas las respuestas de subpreguntas
            const todasSubpreguntas = [
                ...this.respuestasSubpreguntasAcumuladas,
                ...this.extraerRespuestasSubpreguntas()
            ];
            
            // Combinar todas las respuestas de rangos
            const todosRangos = [
                ...this.respuestasRangosAcumuladas,
                ...this.extraerRespuestasRangos()
            ];
            
            // Guardar con todas las respuestas acumuladas
            try {
                const sedeResponse = await fetch(`/api/sedes/buscar?nombre=${encodeURIComponent(this.sedeNombre)}`);
                let sedeId = null;
                if (sedeResponse.ok) {
                    const sedeData = await sedeResponse.json();
                    sedeId = sedeData.id;
                } else {
                    sedeId = 1;
                }

                let respuestasFormato = this.respuestas;
                if (respuestasFormato && typeof respuestasFormato === 'object' && !Array.isArray(respuestasFormato)) {
                    if (Object.keys(respuestasFormato).length === 0) {
                        respuestasFormato = {};
                    }
                } else if (!respuestasFormato || Array.isArray(respuestasFormato)) {
                    respuestasFormato = {};
                }
                
                const calificacionData = {
                    area_id: this.areaSeleccionada.id,
                    nivel_calificacion_id: this.nivelSeleccionado?.esFCR ? null : (this.nivelSeleccionado?.id || null),
                    sede_id: sedeId,
                    respuestas: respuestasFormato,
                    respuestas_subpreguntas: todasSubpreguntas,
                    respuestas_rangos: todosRangos
                };

                console.log('📤 Guardando calificación completa secuencial:', JSON.stringify(calificacionData, null, 2));

                const response = await fetch('/api/calificaciones/completa', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(calificacionData)
                });

                if (!response.ok) {
                    throw new Error(`Error ${response.status}: ${response.statusText}`);
                }

                const result = await response.json();
                console.log('✅ Calificación secuencial guardada exitosamente:', result);
                
            } catch (error) {
                console.error('❌ Error guardando calificación secuencial:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error al guardar',
                    text: 'Error al guardar la calificación: ' + error.message,
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#ef4444'
                });
            }
        },
        
        async cargarDatosIniciales() {
            this.cargandoDatos = true;
            try {
                const areaGuardada = localStorage.getItem('area_seleccionada');
                const sedeGuardada = localStorage.getItem('sede_seleccionada');
                
                if (areaGuardada) {
                    this.areaSeleccionada = JSON.parse(areaGuardada);
                    
                    // 🔥 NUEVO: Recargar el área desde la API para obtener los campos actualizados
                    try {
                        const response = await fetch(`/api/areas/${this.areaSeleccionada.id}`);
                        if (response.ok) {
                            const areaActualizada = await response.json();
                            this.areaSeleccionada = areaActualizada;
                            console.log('📋 Área actualizada desde API:', this.areaSeleccionada);
                            console.log('✅ Permite CSAT:', this.areaSeleccionada.permite_csat);
                            console.log('✅ Permite NPS:', this.areaSeleccionada.permite_nps);
                            console.log('✅ Permite FCR:', this.areaSeleccionada.permite_fcr);
                            
                            // 🔥 NUEVO: Determinar secuencia de tipos de calificación
                            this.determinarSecuenciaTipos();
                            
                            // Debug específico para FCR
                            if (!this.areaSeleccionada.permite_csat && !this.areaSeleccionada.permite_nps && this.areaSeleccionada.permite_fcr) {
                                console.log('🔧 MODO FCR ACTIVADO');
                                // Cargar pregunta FCR desde BD
                                await this.cargarPreguntaFCR();
                            }
                        } else {
                            console.error('❌ Error al cargar área desde API:', response.status);
                        }
                    } catch (error) {
                        console.warn('No se pudo actualizar el área desde API:', error);
                    }
                }
                if (sedeGuardada) {
                    this.sedeNombre = sedeGuardada;
                }

                if (!this.areaSeleccionada || !this.sedeNombre) {
                    this.$router.push('/areas');
                    return;
                }

                // 🔥 NUEVO: Cargar pregunta NPS si el área la tiene habilitada
                if (this.areaSeleccionada.permite_nps) {
                    await this.cargarPreguntaNPS();
                }

            } catch (error) {
                console.error('Error cargando datos iniciales:', error);
            } finally {
                this.cargandoDatos = false;
            }
        },

        // 🔥 NUEVO: Cargar pregunta NPS
        async cargarPreguntaNPS() {
            try {
                const sedeGuardada = localStorage.getItem('sede_seleccionada');
                let sedeId = null;
                
                if (this.areaSeleccionada.sede_id) {
                    sedeId = this.areaSeleccionada.sede_id;
                } else if (sedeGuardada) {
                    const sedeResponse = await fetch(`/api/sedes/buscar?nombre=${encodeURIComponent(sedeGuardada)}`);
                    if (sedeResponse.ok) {
                        const sedeData = await sedeResponse.json();
                        sedeId = sedeData.id;
                    }
                }

                let url = `/api/preguntas?area_id=${this.areaSeleccionada.id}&nivel_id=1`;
                if (sedeId) {
                    url += `&sede_id=${sedeId}`;
                }

                const response = await fetch(url);
                if (response.ok) {
                    const preguntas = await response.json();
                    // Buscar la pregunta NPS
                    this.preguntaNPS = preguntas.find(p => p.tipo_pregunta === 'nps' && p.tipo === 'indicador_0_10');
                    console.log('📝 Pregunta NPS cargada:', this.preguntaNPS);
                }
            } catch (error) {
                console.error('Error cargando pregunta NPS:', error);
            }
        },

        async iniciarCuestionario(nivel) {
    this.solicitarPantallaCompleta();
            this.nivelSeleccionado = nivel;
            this.cargando = true;
            
            // 🔥 NUEVO: Si es CSAT, establecer el tipo actual
            if (!this.tipoCalificacionActual) {
                this.tipoCalificacionActual = 'csat';
            }
            
            try {
                const sedeGuardada = localStorage.getItem('sede_seleccionada');
                let sedeId = null;
                
                if (this.areaSeleccionada.sede_id) {
                    sedeId = this.areaSeleccionada.sede_id;
                } else if (sedeGuardada) {
                    const sedeResponse = await fetch(`/api/sedes/buscar?nombre=${encodeURIComponent(sedeGuardada)}`);
                    if (sedeResponse.ok) {
                        const sedeData = await sedeResponse.json();
                        sedeId = sedeData.id;
                    }
                }

                // 🔥 CORRECIÓN: Cargar TODAS las preguntas del nivel, incluyendo condicionales
                let url = `/api/preguntas?area_id=${this.areaSeleccionada.id}&nivel_id=${nivel.id}`;
                if (sedeId) {
                    url += `&sede_id=${sedeId}`;
                }

                console.log('🔍 Solicitando preguntas con URL:', url);
                const response = await fetch(url);
                
                if (response.ok) {
                    const todasLasPreguntas = await response.json();
                    
                    // 🔥 CORRECIÓN: Filtrar solo preguntas raíces para comenzar, excluyendo otros tipos de calificación
                    this.preguntas = todasLasPreguntas.filter(pregunta => {
                        // Mostrar solo preguntas raíces (no condicionales)
                        const esRaiz = !pregunta.es_condicional || pregunta.es_condicional === false;
                        
                        // 🔥 CORRECCIÓN FLUJO SECUENCIAL: Filtrar según el tipo de calificación actual
                        let esDelTipoCorrecto = true;
                        
                        if (this.tipoCalificacionActual === 'csat') {
                            // En CSAT: solo preguntas CSAT (sin tipo_pregunta o tipo_pregunta === 'csat') o preguntas del nivel específico
                            esDelTipoCorrecto = (pregunta.tipo_pregunta === null || pregunta.tipo_pregunta === 'csat' || !pregunta.tipo_pregunta) && 
                                               pregunta.tipo_pregunta !== 'nps' && 
                                               pregunta.tipo_pregunta !== 'fcr';
                            // También incluir preguntas del nivel específico
                            const esDelNivel = pregunta.niveles_calificacion_id == nivel.id;
                            return esRaiz && esDelTipoCorrecto && esDelNivel;
                        } else if (this.tipoCalificacionActual === 'nps') {
                            // En NPS: solo preguntas NPS
                            esDelTipoCorrecto = pregunta.tipo_pregunta === 'nps';
                            return esRaiz && esDelTipoCorrecto;
                        } else if (this.tipoCalificacionActual === 'fcr') {
                            // En FCR: solo preguntas FCR
                            esDelTipoCorrecto = pregunta.tipo_pregunta === 'fcr';
                            return esRaiz && esDelTipoCorrecto;
                        }
                        
                        // Fallback: verificar que pertenezca al nivel seleccionado O sea genérica (sin nivel específico)
                        const esDelNivelCorrecto = pregunta.niveles_calificacion_id == nivel.id || 
                                                   (pregunta.tipo_pregunta !== null && pregunta.niveles_calificacion_id === null);
                        
                        return esRaiz && esDelNivelCorrecto;
                    });
                    
                    console.log('📝 Preguntas raíces cargadas:', this.preguntas.length);
                    console.log('📝 Todas las preguntas disponibles:', todasLasPreguntas.length);
                    console.log('🔍 Nivel seleccionado:', nivel.id, nivel.nombre);
                    console.log('🎯 Tipo de calificación actual:', this.tipoCalificacionActual);
                    console.log('📋 Preguntas filtradas:', this.preguntas.map(p => ({
                        id: p.id,
                        pregunta: p.pregunta,
                        nivel_id: p.niveles_calificacion_id,
                        tipo: p.tipo_pregunta
                    })));
                    console.log('📋 Secuencia completa:', this.tiposCalificacionSecuencia);
                    console.log('📋 Índice actual:', this.indiceTipoActual);
                    
                    // 🔥 NUEVO: Guardar todas las preguntas para referencia
                    this.todasLasPreguntas = todasLasPreguntas;
                    
                    if (this.preguntas.length > 0) {
                        this.mostrarCuestionario = true;
                        this.preguntaActual = 0;
                        this.respuestas = {};
                        this.errorValidacion = '';
                        this.respuestaUnica = null;
                        this.textoLibreOpcion = '';
                        this.opcionTextoLibreSeleccionada = null;
                        this.respuestaLibre = '';
                        // 🔥 CORRECCIÓN: Solo resetear si no es NPS y el valor ya no está configurado
                        if (this.nivelSeleccionado?.nombre !== 'NPS') {
                            this.respuestaIndicadorValor = 5;
                            this.respuestaIndicadorNPS = 5;
                        }
                        this.respuestaFCR = null;
                        // 🔥 Limpiar TODAS las variables de subpreguntas al cambiar de nivel
                        this.modoSubpreguntas = false;
                        this.subpreguntasActuales = [];
                        this.subpreguntaIndex = 0;
                        this.subpreguntasActivas = {};
                        this.respuestasSubpreguntas = {};
                        this.subpreguntas = [];
                        this.preguntaRangoActual = null;
                        this.debugFlujoPreguntas();
                    } else {
                        Swal.fire({
                            icon: 'info',
                            title: 'Sin preguntas configuradas',
                            text: 'No hay preguntas configuradas para este nivel en esta área y sede. Por favor, contacta al administrador.',
                            confirmButtonText: 'Entendido',
                            confirmButtonColor: '#4f46e5'
                        });
                    }
                } else {
                    const errorData = await response.json();
                    throw new Error(errorData.error || 'Error al cargar preguntas');
                }
            } catch (error) {
                console.error('Error cargando preguntas:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error al cargar preguntas',
                    text: error.message || 'Ocurrió un error al cargar las preguntas',
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#ef4444'
                });
            } finally {
                this.cargando = false;
            }
        },

        // ✅ CORRECCIÓN: Método seleccionarOpcionUnica corregido
seleccionarOpcionUnica(opcion, index) {
    console.log('🔘 Seleccionando opción única:', { 
        opcion, 
        index, 
        esPreguntaRango: this.preguntaActualData.es_pregunta_rango,
        tipoPregunta: this.preguntaActualData.tipo 
    });
    
    let identificador;
    
    if (this.preguntaActualData.es_pregunta_rango) {
        // 🔥 CORRECCIÓN: Para preguntas de rango, usar el ID de la opción (no el texto)
        // Esto es importante para que se guarde correctamente en la BD
        identificador = opcion && opcion.id ? opcion.id : (typeof opcion === 'string' ? opcion : this.obtenerTextoOpcion(opcion));
        console.log('📝 Pregunta de rango - ID de opción:', identificador);
    } else {
        // 🔥 CORRECCIÓN CRÍTICA: Para preguntas normales, usar el ID numérico
        // PERO asegurando que sea un número, no el objeto completo
        identificador = opcion && opcion.id ? opcion.id : null;
        console.log('📝 Pregunta normal - ID:', identificador);
    }
    
    this.respuestaUnica = identificador;
    this.errorValidacion = '';
    
    console.log('✅ Opción seleccionada:', this.respuestaUnica);
    console.log('📊 Estado actual de respuestas:', this.respuestas);
    
    // 🔥 NUEVO: Forzar actualización visual inmediata
    this.$forceUpdate();
    
    // Guardar respuesta inmediatamente
    this.guardarRespuestaUnica();
},

        toggleOpcionMultiple(opcionId) {
            const current = this.respuestaMultiple;
            if (current.includes(opcionId)) {
                this.respuestaMultiple = current.filter(id => id !== opcionId);
            } else {
                this.respuestaMultiple = [...current, opcionId];
            }
            this.errorValidacion = '';
        },

        preguntaAnterior() {
            if (this.preguntaActual > 0) {
                this.preguntaActual--;
                this.errorValidacion = '';
                
                // 🔥 NUEVO: Resetear estados de la pregunta actual
                const preguntaActual = this.preguntaActualData;
                if (preguntaActual.tipo === 'opcion_unica_texto_libre') {
                    const respuesta = this.respuestas[preguntaActual.id];
                    if (respuesta && typeof respuesta === 'object') {
                        this.respuestaUnica = respuesta.opcion_seleccionada_id;
                        this.textoLibreOpcion = respuesta.texto_libre || '';
                        
                        // Verificar si la opción seleccionada es de texto libre
                        const opcionSeleccionada = preguntaActual.opciones.find(
                            op => op.id === this.respuestaUnica
                        );
                        this.opcionTextoLibreSeleccionada = opcionSeleccionada && 
                            this.opcionEsTextoLibre(opcionSeleccionada.opcion) ? this.respuestaUnica : null;
                    }
                }
            }
        },

/**
 * Validar respuesta de subpregunta
 */
validarRespuestaSubpregunta(subpregunta, respuesta) {
    if (!respuesta) return false;

    switch (subpregunta.tipo) {
        case 'opcion_unica':
            return respuesta.opcionSeleccionada !== null;
        case 'opcion_multiple':
            return respuesta.opcionesSeleccionadas && respuesta.opcionesSeleccionadas.length > 0;
        case 'texto_libre':
            return respuesta.texto && respuesta.texto.trim() !== '';
        case 'indicador_0_10':
            return respuesta.valor !== null && respuesta.valor !== '';
        default:
            return false;
    }
},

    async siguientePregunta() {
    if (!this.puedeContinuar) {
        this.errorValidacion = this.modoSubpreguntas 
            ? 'Por favor responde esta subpregunta antes de continuar.'
            : 'Por favor responde esta pregunta antes de continuar.';
        return;
    }

    this.errorValidacion = '';
    this.cargando = true;

    try {
        this.debugEstadoActual();
        // 🔥 NUEVO: Verificar si es un indicador 0-10 para cargar pregunta de rango
        if (!this.modoSubpreguntas && this.preguntaActualData.tipo === 'indicador_0_10') {
            const respuesta = this.obtenerRespuestaActual();
            if (respuesta.valor !== undefined) {
                console.log('🎯 Procesando indicador con valor:', respuesta.valor);
                
                // Guardar la respuesta del indicador antes de eliminar la pregunta
                const preguntaIndicadorId = this.preguntaActualData.id;
                
                // 🔥 CORRECCIÓN: Si es NPS y viene de la barra inicial, ya se eliminó la pregunta en iniciarConNPS
                // Para otros casos (CSAT, FCR, o NPS en flujo secuencial), eliminar la pregunta del indicador
                // antes de cargar la pregunta de rango para que no se muestre dos veces
                const esNPSDesdeBarra = this.tipoCalificacionActual === 'nps' && 
                                       this.respuestaIndicadorNPS !== null && 
                                       this.respuestas[preguntaIndicadorId]?.valor === this.respuestaIndicadorNPS;
                
                if (!esNPSDesdeBarra) {
                    // Eliminar la pregunta del indicador del array antes de cargar la pregunta de rango
                    const indicadorIndex = this.preguntas.findIndex(p => p.id === preguntaIndicadorId);
                    if (indicadorIndex !== -1) {
                        this.preguntas.splice(indicadorIndex, 1);
                        console.log('🗑️ Pregunta indicador eliminada del flujo antes de cargar pregunta de rango');
                        // Ajustar el índice de pregunta actual si es necesario
                        if (this.preguntaActual >= indicadorIndex) {
                            this.preguntaActual = Math.max(0, this.preguntaActual - 1);
                        }
                    }
                }
                
                const tienePreguntaRango = await this.cargarPreguntaRango(
                    preguntaIndicadorId, 
                    respuesta.valor
                );
                
                if (tienePreguntaRango) {
                    // Si hay pregunta de rango, la pregunta ya está en el array (insertada por cargarPreguntaRango)
                    // Ajustar preguntaActual si fue necesario
                    const preguntaRangoIndex = this.preguntas.findIndex(p => p.es_pregunta_rango && p.pregunta_indicador_id === preguntaIndicadorId);
                    if (preguntaRangoIndex !== -1) {
                        this.preguntaActual = preguntaRangoIndex;
                    }
                    this.mostrarCuestionario = true;
                    console.log('✅ Pregunta de rango cargada, mostrando directamente');
                    this.cargando = false;
                    return;
                } else {
                    // 🔥 NUEVO: Cuando no hay pregunta de rango para NPS, verificar si hay más tipos o finalizar
                    console.log('📭 No hay pregunta de rango para este valor');
                    this.cargando = false;
                    
                    // Si es NPS y no hay pregunta de rango, verificar si hay más tipos de calificación
                    if (esNPS) {
                        // La alerta ya se mostró en cargarPreguntaRango() con await
                        // El modal ya está cerrado arriba
                        
                        const hayMasTipos = this.tiposCalificacionSecuencia.length > 1 && 
                                           this.indiceTipoActual < this.tiposCalificacionSecuencia.length - 1;
                        
                        if (!hayMasTipos) {
                            // No hay más tipos, finalizar directamente
                            console.log('🎉 NPS sin pregunta de rango: Finalizando calificación...');
                            if (this.tiposCalificacionSecuencia.length > 1) {
                                await this.guardarCalificacionTipoIndividual();
                            } else {
                                await this.guardarCalificacionCompleta();
                            }
                            this.mostrarAgradecimiento = true;
                            this.iniciarTemporizadorCierre();
                            return;
                        } else {
                            // Hay más tipos, avanzar al siguiente (avanzarAlSiguienteTipo ya guarda)
                            console.log('➡️ NPS sin pregunta de rango: Avanzando al siguiente tipo...');
                            await this.avanzarAlSiguienteTipo();
                            return;
                        }
                    }
                    // Si no es NPS, continuar normalmente
                    console.log('📭 No hay pregunta de rango, continuando normalmente');
                    this.cargando = false;
                }
            }
        }

        // Lógica existente para subpreguntas y preguntas normales
        if (this.modoSubpreguntas) {
            if (this.esUltimaSubpregunta) {
                await this.finalizarSubpreguntas();
            } else {
                this.subpreguntaIndex++;
                console.log('➡️ Avanzando a subpregunta:', this.subpreguntaIndex + 1);
            }
        } else {
            await this.procesarPreguntaNormal();
        }

    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error al procesar',
            text: 'Error al procesar: ' + error.message,
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#ef4444'
        });
    } finally {
        this.cargando = false;
    }
},

/**
 * Procesar pregunta normal y verificar subpreguntas
 */
async procesarPreguntaNormal() {
    const preguntaActual = this.preguntaActualData;
    const respuesta = this.obtenerRespuestaActual();

    console.log('🔄 Procesando pregunta normal:', preguntaActual.pregunta);

    // 🔥 NUEVO: Guardar respuesta si es una pregunta de rango
    if (preguntaActual.es_pregunta_rango) {
        this.guardarRespuestaPreguntaRango();
        
        // 🔥 CORRECCIÓN FLUJO SECUENCIAL: Verificar si hay más tipos después de NPS
        const hayMasTipos = this.tiposCalificacionSecuencia.length > 1 && 
                            this.indiceTipoActual < this.tiposCalificacionSecuencia.length - 1;
        
        if (!hayMasTipos && this.tipoCalificacionActual === 'nps') {
            console.log('🎉 NPS final (último tipo): Finalizando calificación completa...');
            // Guardar individualmente si es flujo secuencial, sino usar método normal
            if (this.tiposCalificacionSecuencia.length > 1) {
                await this.guardarCalificacionTipoIndividual();
            } else {
                await this.guardarCalificacionCompleta();
            }
            this.mostrarCuestionario = false;
            this.mostrarAgradecimiento = true;
            this.iniciarTemporizadorCierre();
            return;
        }
    }

    // 🔥 CORRECCIÓN FCR: Verificar si es pregunta FCR y manejar según la respuesta
    if (this.tipoCalificacionActual === 'fcr' && preguntaActual.tipo_pregunta === 'fcr') {
        const opcionSeleccionada = preguntaActual.opciones.find(
            op => op.id === respuesta.opcion_seleccionada_id
        );
        
        if (opcionSeleccionada) {
            const esSí = opcionSeleccionada.opcion.toLowerCase() === 'sí' || opcionSeleccionada.opcion.toLowerCase() === 'si';
            const esNo = opcionSeleccionada.opcion.toLowerCase() === 'no';
            
            // 🔥 CORRECCIÓN: Verificar subpreguntas tanto para "Sí" como para "No"
            // Verificar flag y también existencia real de subpreguntas
            const tieneSubpreguntasSi = opcionSeleccionada.tiene_subpreguntas || 
                                        (opcionSeleccionada.subpreguntas && opcionSeleccionada.subpreguntas.length > 0);
            
            console.log('🔍 FCR procesarPreguntaNormal - Verificando subpreguntas para "Sí":', {
                esSí,
                tiene_subpreguntas: opcionSeleccionada.tiene_subpreguntas,
                subpreguntas: opcionSeleccionada.subpreguntas,
                subpreguntasLength: opcionSeleccionada.subpreguntas?.length || 0,
                tieneSubpreguntasSi,
                opcionId: opcionSeleccionada.id
            });
            
            if (esSí && tieneSubpreguntasSi) {
                // Si selecciona "Sí" y tiene subpreguntas, cargarlas
                console.log('✅ FCR: Se resolvió (Sí) con subpreguntas, cargando...');
                
                // 🔥 CORRECCIÓN: Mantener el modal abierto antes de iniciar modo subpreguntas
                this.mostrarCuestionario = true;
                
                await this.iniciarModoSubpreguntas(opcionSeleccionada.id);
                return;
            } else if (esSí) {
                console.log('⚠️ FCR: Se resolvió (Sí) pero NO tiene subpreguntas, finalizando...');
                // Si selecciona "Sí" sin subpreguntas, finalizar directamente
                console.log('✅ FCR: Se resolvió (Sí), finalizando...');
                // Verificar si hay más tipos
                const hayMasTipos = this.tiposCalificacionSecuencia.length > 1 && 
                                    this.indiceTipoActual < this.tiposCalificacionSecuencia.length - 1;
                if (hayMasTipos) {
                    // avanzarAlSiguienteTipo() ya guarda el tipo actual antes de avanzar
                    await this.avanzarAlSiguienteTipo();
                } else {
                    // Último tipo, guardar y finalizar
                    if (this.tiposCalificacionSecuencia.length > 1) {
                        await this.guardarCalificacionTipoIndividual();
                    } else {
                        await this.guardarCalificacionCompleta();
                    }
                    this.mostrarCuestionario = false;
                    this.mostrarAgradecimiento = true;
                    this.iniciarTemporizadorCierre();
                }
                return;
            } else if (esNo && opcionSeleccionada.tiene_subpreguntas) {
                // Si selecciona "No" y tiene subpreguntas, cargarlas
                console.log('❌ FCR: NO se resolvió, cargando subpreguntas...');
                
                // 🔥 CORRECCIÓN: Mantener el modal abierto antes de iniciar modo subpreguntas
                this.mostrarCuestionario = true;
                
                await this.iniciarModoSubpreguntas(opcionSeleccionada.id);
                return;
            } else if (esNo) {
                // 🔥 CORRECCIÓN: Si selecciona "No" pero NO tiene subpreguntas, finalizar DIRECTAMENTE sin mensaje
                console.log('❌ FCR: NO se resolvió, pero no hay subpreguntas. Finalizando directamente sin mensaje...');
                // Verificar si hay más tipos
                const hayMasTipos = this.tiposCalificacionSecuencia.length > 1 && 
                                    this.indiceTipoActual < this.tiposCalificacionSecuencia.length - 1;
                if (hayMasTipos) {
                    // avanzarAlSiguienteTipo() ya guarda el tipo actual antes de avanzar
                    await this.avanzarAlSiguienteTipo();
                } else {
                    // Último tipo, guardar y finalizar
                    if (this.tiposCalificacionSecuencia.length > 1) {
                        await this.guardarCalificacionTipoIndividual();
                    } else {
                        await this.guardarCalificacionCompleta();
                    }
                    this.mostrarCuestionario = false;
                    this.mostrarAgradecimiento = true;
                    this.iniciarTemporizadorCierre();
                }
                return;
            }
        }
    }
    
    // Verificar si la opción seleccionada tiene subpreguntas (solo para preguntas normales)
    if (!preguntaActual.es_pregunta_rango && 
        ['opcion_unica', 'opcion_unica_texto_libre'].includes(preguntaActual.tipo) && 
        respuesta.opcion_seleccionada_id) {
        
        const opcionSeleccionada = preguntaActual.opciones.find(
            op => op.id === respuesta.opcion_seleccionada_id
        );
        
        if (opcionSeleccionada && opcionSeleccionada.tiene_subpreguntas) {
            console.log('🎯 La opción tiene subpreguntas, iniciando modo subpreguntas...');
            await this.iniciarModoSubpreguntas(opcionSeleccionada.id);
            return;
        }
    }

    // 🔥 CORRECCIÓN FLUJO SECUENCIAL: Verificar si hay más tipos en la secuencia
    const hayMasTipos = this.tiposCalificacionSecuencia.length > 1 && 
                        this.indiceTipoActual < this.tiposCalificacionSecuencia.length - 1;
    
    // Si no hay más tipos y es CSAT, finalizar
    if (!hayMasTipos && this.tipoCalificacionActual === 'csat') {
        console.log('🎉 CSAT final (último tipo): Finalizando calificación completa...');
        if (this.tiposCalificacionSecuencia.length > 1) {
            await this.guardarCalificacionTipoIndividual();
        } else {
            await this.guardarCalificacionCompleta();
        }
        this.mostrarCuestionario = false;
        this.mostrarAgradecimiento = true;
        this.iniciarTemporizadorCierre();
        return;
    }

    // Si no hay subpreguntas, continuar normalmente
    this.preguntaActual++;
    await this.verificarFinalizacion();
},

/**
 * Iniciar modo subpreguntas
 */
async iniciarModoSubpreguntas(opcionId) {
    try {
        console.log('🔍 Cargando subpreguntas para opción ID:', opcionId);
        
        const response = await fetch(`/api/subpreguntas/${opcionId}`);
        if (response.ok) {
            let subpreguntas = await response.json();
            
            // 🔥 CORRECIÓN: Parsear opciones de cada subpregunta
            subpreguntas = subpreguntas.map(sp => {
                // Asegurarnos de que las opciones sean un array
                if (sp.opciones && typeof sp.opciones === 'string') {
                    try {
                        sp.opciones = JSON.parse(sp.opciones);
                    } catch (e) {
                        console.warn('Error parseando opciones:', e);
                        sp.opciones = [];
                    }
                } else if (!sp.opciones) {
                    sp.opciones = [];
                }
                return sp;
            });
            
            console.log('📝 Subpreguntas cargadas y parseadas:', subpreguntas);
            
            if (subpreguntas.length > 0) {
                this.subpreguntasActuales = subpreguntas;
                this.subpreguntaIndex = 0;
                this.modoSubpreguntas = true;
                this.inicializarRespuestasSubpreguntas();
                
                // 🔥 CORRECCIÓN: Asegurar que el modal esté visible para mostrar subpreguntas
                this.mostrarCuestionario = true;
                this.cargando = false;
                
                console.log('✅ Modo subpreguntas activado:', this.subpreguntasActuales.length);
                console.log('✅ mostrarCuestionario:', this.mostrarCuestionario);
            } else {
                console.log('📭 No hay subpreguntas, continuando normalmente');
                this.cargando = false;
                this.preguntaActual++;
                await this.verificarFinalizacion();
            }
        }
    } catch (error) {
        console.error('❌ Error iniciando modo subpreguntas:', error);
        this.preguntaActual++;
        await this.verificarFinalizacion();
    }
},

/**
 * Finalizar modo subpreguntas y volver al flujo normal
 */
async finalizarSubpreguntas() {
    console.log('🏁 Finalizando modo subpreguntas');
    
    // Guardar respuestas de subpreguntas
    await this.guardarRespuestasSubpreguntas();
    
    // 🔥 CORRECCIÓN FLUJO SECUENCIAL: Verificar si hay más tipos en la secuencia
    const hayMasTipos = this.tiposCalificacionSecuencia.length > 1 && 
                        this.indiceTipoActual < this.tiposCalificacionSecuencia.length - 1;
    
    // Si es FCR y no hay más tipos, finalizar
    if ((this.nivelSeleccionado?.esFCR || this.tipoCalificacionActual === 'fcr') && !hayMasTipos) {
        console.log('🎉 FCR final (último tipo): Guardando calificación completa...');
        if (this.tiposCalificacionSecuencia.length > 1) {
            await this.guardarCalificacionTipoIndividual();
        } else {
            await this.guardarCalificacionCompleta();
        }
        this.mostrarCuestionario = false;
        this.mostrarAgradecimiento = true;
        this.iniciarTemporizadorCierre();
        return;
    }
    
        // Si hay más tipos, avanzar al siguiente (avanzarAlSiguienteTipo ya guarda)
        if (hayMasTipos) {
            console.log('➡️ Tipo actual completado, avanzando al siguiente tipo...');
            await this.avanzarAlSiguienteTipo();
            return;
        }
    
    // Limpiar estados y continuar con siguiente pregunta normal
    this.modoSubpreguntas = false;
    this.subpreguntasActuales = [];
    this.subpreguntaIndex = 0;
    
    // Continuar con siguiente pregunta normal
    this.preguntaActual++;
    await this.verificarFinalizacion();
},

/**
 * Verificar si hemos llegado al final del cuestionario
 */
async verificarFinalizacion() {
    if (this.preguntaActual >= this.preguntas.length) {
        // 🔥 CORRECCIÓN FLUJO SECUENCIAL: Verificar si hay más tipos en la secuencia
        const hayMasTipos = this.tiposCalificacionSecuencia.length > 1 && 
                            this.indiceTipoActual < this.tiposCalificacionSecuencia.length - 1;
        
        if (hayMasTipos) {
            console.log('➡️ Tipo actual completado, avanzando al siguiente tipo...');
            // avanzarAlSiguienteTipo() ya guarda el tipo actual antes de avanzar
            await this.avanzarAlSiguienteTipo();
            return;
        }
        
        // Es el último tipo, guardar y finalizar
        console.log('🎉 Finalizando último tipo de calificación');
        if (this.tiposCalificacionSecuencia.length > 1) {
            // Si es flujo secuencial, guardar el último tipo individualmente
            await this.guardarCalificacionTipoIndividual();
        } else {
            // Si no es flujo secuencial, usar el método normal
            await this.guardarCalificacionCompleta();
        }
        this.mostrarCuestionario = false;
        this.mostrarAgradecimiento = true;
        this.iniciarTemporizadorCierre();
    }
},

obtenerRespuestaActual() {
    const pregunta = this.preguntaActualData;
    switch (pregunta.tipo) {
        case 'opcion_unica':
            return { opcion_seleccionada_id: this.respuestaUnica };
        case 'opcion_multiple':
            return { opciones_seleccionadas: this.respuestaMultiple };
        case 'texto_libre':
            return { texto: this.respuestaLibre };
        case 'indicador_0_10':
            return { valor: this.respuestaIndicadorValor };
        case 'opcion_unica_texto_libre':
            return {
                opcion_seleccionada_id: this.respuestaUnica,
                texto_libre: this.textoLibreOpcion
            };
        default:
            return {};
    }
},

async guardarCalificacionCompleta() {
    try {
        console.log('=== INICIANDO guardarCalificacionCompleta ===');
        
        // 🔥 CORRECCIÓN FLUJO SECUENCIAL: Si hay más tipos, solo acumular, no guardar todavía
        const hayMasTipos = this.tiposCalificacionSecuencia.length > 1 && 
                            this.indiceTipoActual < this.tiposCalificacionSecuencia.length - 1;
        
        if (hayMasTipos) {
            console.log('💾 Hay más tipos, acumulando respuestas del tipo actual...');
            this.acumularRespuestasActuales();
            // No guardar todavía, solo acumular
            return;
        }
        
        // Si es el último tipo o flujo no secuencial, guardar
        // 🔥 CORRECCIÓN: En flujo secuencial, cada tipo ya se guardó individualmente
        // Solo necesitamos guardar si NO es flujo secuencial
        let respuestasFinales = { ...this.respuestas };
        if (this.tiposCalificacionSecuencia.length > 1) {
            // En flujo secuencial, este método solo se llama para el último tipo
            // pero ya debería haberse guardado en avanzarAlSiguienteTipo
            // Por seguridad, combinamos con respuestas acumuladas
            respuestasFinales = { ...this.respuestasAcumuladas, ...this.respuestas };
        }
        
        const sedeResponse = await fetch(`/api/sedes/buscar?nombre=${encodeURIComponent(this.sedeNombre)}`);
        let sedeId = null;
        if (sedeResponse.ok) {
            const sedeData = await sedeResponse.json();
            sedeId = sedeData.id;
            console.log('Sede ID encontrado:', sedeId);
        } else {
            console.warn('No se pudo encontrar el ID de la sede');
            sedeId = 1;
        }

        // 🔥 NUEVO: Preparar datos para subpreguntas
        const respuestasSubpreguntasActuales = this.extraerRespuestasSubpreguntas();
        
        // 🔥 NUEVO: Preparar datos para preguntas de rango
        const respuestasRangosActuales = this.extraerRespuestasRangos();
        
        // 🔥 CORRECCIÓN FLUJO SECUENCIAL: Combinar con acumuladas si hay
        let todasSubpreguntas = respuestasSubpreguntasActuales;
        let todosRangos = respuestasRangosActuales;
        
        if (this.tiposCalificacionSecuencia.length > 1) {
            todasSubpreguntas = [
                ...this.respuestasSubpreguntasAcumuladas,
                ...respuestasSubpreguntasActuales
            ];
            todosRangos = [
                ...this.respuestasRangosAcumuladas,
                ...respuestasRangosActuales
            ];
        }
        
        // 🔥 CORRECCIÓN: Convertir respuestas a formato correcto
        let respuestasFormato = respuestasFinales;
        if (respuestasFormato && typeof respuestasFormato === 'object' && !Array.isArray(respuestasFormato)) {
            if (Object.keys(respuestasFormato).length === 0) {
                respuestasFormato = {};
            }
        } else if (!respuestasFormato || Array.isArray(respuestasFormato)) {
            respuestasFormato = {};
        }
        
        const calificacionData = {
            area_id: this.areaSeleccionada.id,
            nivel_calificacion_id: this.nivelSeleccionado?.esFCR ? null : (this.nivelSeleccionado?.id || null), // 🔥 CORRECCIÓN FCR: NULL para FCR
            sede_id: sedeId,
            respuestas: respuestasFormato,
            respuestas_subpreguntas: todasSubpreguntas,
            respuestas_rangos: todosRangos
        };

        console.log('📤 Enviando datos al servidor:', JSON.stringify(calificacionData, null, 2));

        const response = await fetch('/api/calificaciones/completa', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(calificacionData)
        });

        if (!response.ok) {
            throw new Error(`Error ${response.status}: ${response.statusText}`);
        }

        const result = await response.json();
        console.log('✅ Calificación guardada exitosamente:', result);
        
    } catch (error) {
        console.error('❌ Error guardando calificación:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error al guardar la calificación: ' + error.message,
            confirmButtonColor: '#ef4444'
        });
    }
},

/**
 * 🔥 NUEVO: Extraer respuestas de subpreguntas para guardar en BD
 */
extraerRespuestasSubpreguntas() {
    const respuestas = [];
    
    // 🔥 NUEVO: Si estamos en modo subpreguntas (como FCR directo), extraer de respuestasSubpreguntas
    if (this.modoSubpreguntas && this.subpreguntasActuales.length > 0) {
        console.log('📝 Extrayendo respuestas de subpreguntas FCR...');
        console.log('📋 respuestasSubpreguntas:', this.respuestasSubpreguntas);
        console.log('📋 subpreguntasActuales:', this.subpreguntasActuales);
        
        Object.keys(this.respuestasSubpreguntas).forEach(subpreguntaId => {
            const respuesta = this.respuestasSubpreguntas[subpreguntaId];
            const subpregunta = this.subpreguntasActuales.find(s => s.id == subpreguntaId);
            
            console.log('🔍 Procesando respuesta de subpregunta:', subpreguntaId, respuesta, subpregunta);
            
    // 🔥 CORRECCIÓN: Verificar que haya al menos un dato válido
    const tieneOpcion = respuesta?.opcionSeleccionada !== null && respuesta?.opcionSeleccionada !== undefined && respuesta?.opcionSeleccionada !== '';
    const tieneTexto = respuesta?.texto && respuesta.texto.trim() !== '';
    const tieneValor = respuesta?.valor !== null && respuesta?.valor !== undefined;
    const tieneOpciones = respuesta?.opcionesSeleccionadas && Array.isArray(respuesta.opcionesSeleccionadas) && respuesta.opcionesSeleccionadas.length > 0;
    
    if (respuesta && (tieneOpcion || tieneTexto || tieneValor || tieneOpciones)) {
        const respuestaData = {
            subpregunta_id: parseInt(subpreguntaId)
        };
        
        // 🔥 CORRECCIÓN: Para opción única, guardar el texto de la opción seleccionada (PRIORITARIO)
        if (tieneOpcion) {
            respuestaData.opcion_seleccionada = String(respuesta.opcionSeleccionada);
            console.log('✅ Opción seleccionada agregada:', respuestaData.opcion_seleccionada);
        }
        
        // Para opción múltiple
        if (tieneOpciones) {
            respuestaData.opciones_seleccionadas = respuesta.opcionesSeleccionadas;
        }
        
        // Texto libre (solo si hay texto)
        if (tieneTexto) {
            respuestaData.texto_respuesta = respuesta.texto.trim();
        }
        
        // Valor indicador (solo si hay valor)
        if (tieneValor) {
            respuestaData.valor_indicador = respuesta.valor;
        }
        
        console.log('📦 Respuesta de subpregunta procesada:', respuestaData);
        respuestas.push(respuestaData);
    } else {
        console.warn('⚠️ Respuesta de subpregunta sin datos válidos:', { subpreguntaId, respuesta });
    }
        });
        
        console.log('✅ Respuestas FCR extraídas:', respuestas);
        return respuestas;
    }
    
    // Buscar todas las claves que empiecen con "subpreguntas_"
    Object.keys(this.respuestas).forEach(clave => {
        if (clave.startsWith('subpreguntas_')) {
            const datosSubpreguntas = this.respuestas[clave];
            
            if (datosSubpreguntas && datosSubpreguntas.subpreguntas) {
                Object.keys(datosSubpreguntas.subpreguntas).forEach(subpreguntaId => {
                    const respuesta = datosSubpreguntas.subpreguntas[subpreguntaId];
                    
                    if (respuesta) {
                        respuestas.push({
                            subpregunta_id: parseInt(subpreguntaId),
                            opcion_pregunta_id: datosSubpreguntas.opcion_pregunta_id,
                            opcion_seleccionada: respuesta.opcionSeleccionada || null,
                            opciones_seleccionadas: respuesta.opcionesSeleccionadas || null,
                            texto_respuesta: respuesta.texto || null,
                            valor_indicador: respuesta.valor || null
                        });
                    }
                });
            }
        }
    });
    
    console.log('📝 Respuestas subpreguntas extraídas:', respuestas);
    return respuestas;
},

/**
     * Cargar subpreguntas cuando se selecciona una opción
     */
    async cargarSubpreguntasParaOpcion(pregunta, opcionId) {
        try {
            console.log('🔍 Cargando subpreguntas para opción:', opcionId);
            
            const response = await fetch(`/api/subpreguntas/${opcionId}`);
            if (response.ok) {
                const subpreguntas = await response.json();
                console.log('📝 Subpreguntas cargadas:', subpreguntas);
                
                if (subpreguntas.length > 0) {
                    this.subpreguntasActivas[pregunta.id] = subpreguntas;
                    
                    // Inicializar respuestas para subpreguntas
                    subpreguntas.forEach(subpregunta => {
                        if (!this.respuestasSubpreguntas[subpregunta.id]) {
                            this.inicializarRespuestaSubpregunta(subpregunta);
                        }
                    });
                } else {
                    // Limpiar subpreguntas si no hay
                    delete this.subpreguntasActivas[pregunta.id];
                }
            }
        } catch (error) {
            console.error('Error cargando subpreguntas:', error);
        }
    },

/**
 * 🔥 CORREGIDO: Cargar pregunta de rango para indicador
 */
async cargarPreguntaRango(preguntaId, valor, mostrarAlerta = true) {
    try {
        console.log('🔍 Buscando pregunta de rango para:', { preguntaId, valor });
        
        const response = await fetch(`/api/preguntas/${preguntaId}/rango/${valor}`);
        if (response.ok) {
            const preguntaRango = await response.json();
            
            if (preguntaRango) {
                console.log('✅ Pregunta de rango encontrada:', preguntaRango);
                
                // Parsear opciones si es necesario
                let opcionesArray = preguntaRango.opciones_array || [];
                if (preguntaRango.opciones && typeof preguntaRango.opciones === 'string') {
                    try {
                        opcionesArray = JSON.parse(preguntaRango.opciones);
                    } catch (e) {
                        console.warn('Error parseando opciones de rango:', e);
                        opcionesArray = [];
                    }
                }
                
                // 🔥 CORRECCIÓN: Crear ID numérico único para la pregunta de rango
                const preguntaRangoObj = {
                    id: Date.now(), // ID temporal único
                    pregunta: preguntaRango.pregunta_texto,
                    tipo: preguntaRango.tipo,
                    opciones: opcionesArray,
                    opciones_array: opcionesArray,
                    es_pregunta_rango: true,
                    pregunta_rango_id: preguntaRango.id,
                    pregunta_indicador_id: preguntaId // Guardar el ID del indicador original
                };
                
                console.log('📥 Pregunta de rango procesada:', preguntaRangoObj);
                
                // 🔥 CORRECCIÓN: Insertar la pregunta de rango DESPUÉS del indicador actual
                // Si el indicador ya fue eliminado, insertar al inicio
                const indicadorIndex = this.preguntas.findIndex(p => p.id === preguntaId);
                if (indicadorIndex !== -1) {
                    this.preguntas.splice(indicadorIndex + 1, 0, preguntaRangoObj);
                    console.log('✅ Pregunta de rango insertada en posición:', indicadorIndex + 1);
                } else {
                    // Si no se encuentra el indicador (fue eliminado antes), insertar al inicio
                    this.preguntas.unshift(preguntaRangoObj);
                    console.log('✅ Pregunta de rango agregada al inicio (indicador ya fue eliminado)');
                }
                
                return true;
            }
        }
        
        // 🔥 NUEVO: Mostrar alerta cuando no hay preguntas de rango configuradas (solo si se solicita)
        if (mostrarAlerta) {
            // Esperar a que el usuario vea y cierre la alerta antes de continuar
            await Swal.fire({
                icon: 'info',
                title: 'Sin preguntas configuradas',
                text: `No hay preguntas configuradas para el valor ${valor} (escala 0-10). La calificación será guardada sin preguntas adicionales.`,
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#4f46e5',
                allowOutsideClick: false,
                allowEscapeKey: false
            });
        }
        
        console.log('📭 No se encontró pregunta de rango para este valor');
        return false;
        
    } catch (error) {
        console.error('❌ Error cargando pregunta de rango:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Ocurrió un error al cargar la pregunta de rango: ' + error.message,
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#ef4444'
        });
        return false;
    }
},

    /**
     * Inicializar estructura de respuesta para subpregunta
     */
    inicializarRespuestaSubpregunta(subpregunta) {
        this.respuestasSubpreguntas[subpregunta.id] = this.crearEstructuraRespuesta(subpregunta.tipo);
    },

    /**
     * Crear estructura de respuesta según tipo
     */
    crearEstructuraRespuesta(tipo) {
        switch (tipo) {
            case 'indicador_0_10':
                return { valor: 5 };
            case 'texto_libre':
                return { texto: '' };
            case 'opcion_unica':
                return { opcionSeleccionada: null };
            case 'opcion_unica_texto_libre':
                // 🔥 CORRECCIÓN: Inicializar correctamente para opcion_unica_texto_libre
                return { opcionSeleccionada: null, texto: '' };
            case 'opcion_multiple':
                return { opcionesSeleccionadas: [] };
            default:
                // 🔥 CORRECCIÓN: Por defecto, usar estructura para opción única
                return { opcionSeleccionada: null };
        }
    },

    /**
     * Manejar selección de opción con subpreguntas
     */
    async onOpcionSeleccionadaConSubpreguntas(pregunta, opcionId) {
        this.seleccionarOpcionUnica(opcionId);
        
        // Cargar subpreguntas si la opción las tiene
        const opcionSeleccionada = pregunta.opciones.find(op => op.id === opcionId);
        if (opcionSeleccionada && opcionSeleccionada.tiene_subpreguntas) {
            await this.cargarSubpreguntasParaOpcion(pregunta, opcionId);
        } else {
            // Limpiar subpreguntas si no las tiene
            delete this.subpreguntasActivas[pregunta.id];
        }
    },

    /**
     * Actualizar respuesta de subpregunta
     */
    actualizarSubpregunta(subpregunta) {
        console.log('📝 Subpregunta actualizada:', subpregunta.id, this.respuestasSubpreguntas[subpregunta.id]);
    },

        iniciarTemporizadorCierre() {
            this.tiempoRestante = 5;
            this.intervalo = setInterval(() => {
                this.tiempoRestante--;
                
                if (this.tiempoRestante <= 0) {
                    this.cerrarAgradecimiento();
                }
            }, 1000);
        },

        cerrarAgradecimiento() {
            if (this.intervalo) {
                clearInterval(this.intervalo);
            }
            this.mostrarAgradecimiento = false;
            this.reiniciarCalificacion();
            // 🔥 Refrescar pantalla en tablet al finalizar el flujo
            try {
                window.location.reload();
            } catch (e) {
                // fallback silencioso
            }
        },

        cancelarCuestionario() {
                this.cerrarCuestionario();
        },

        cerrarCuestionario() {
            this.mostrarCuestionario = false;
            this.reiniciarCalificacion();
        },

        reiniciarCalificacion() {
            this.nivelSeleccionado = null;
            this.preguntas = [];
            this.preguntaActual = 0;
            this.respuestas = {};
            this.errorValidacion = '';
            this.respuestaIndicadorValor = 5;
            this.respuestaIndicadorNPS = 5;
            this.textoLibreOpcion = '';
            this.opcionTextoLibreSeleccionada = null;
            this.respuestaUnica = null;
        },

        goToAreas() {
            this.$router.push('/areas');
        },

        getTipoTexto(tipo) {
        const tipos = {
            'opcion_unica': 'Selecciona una opción',
            'opcion_multiple': 'Selecciona una o más opciones', 
            'texto_libre': 'Escribe tu respuesta',
            'indicador_0_10': 'Desliza para calificar del 0 al 10',
            'opcion_unica_texto_libre': 'Selecciona una opción o escribe tu respuesta'
        };
        return tipos[tipo] || '';
    },

    // 🔥 Pantalla completa (para tablets)
    solicitarPantallaCompleta() {
        const elem = document.documentElement;
        try {
            if (!document.fullscreenElement && elem.requestFullscreen) {
                elem.requestFullscreen();
            } else if (!document.fullscreenElement && elem.webkitRequestFullscreen) {
                elem.webkitRequestFullscreen();
            } else if (!document.fullscreenElement && elem.msRequestFullscreen) {
                elem.msRequestFullscreen();
            }
        } catch (e) {
            // Ignorar errores si el navegador bloquea fullscreen fuera de gesto de usuario
        }
    },

        // Métodos auxiliares para SVGs (sin cambios)
        getSvgViewBox(nivelId) {
            return '0 0 120 120';
        },
         getColorFondo(nivelId) {
        const colores = {
            1: '#FEE2E2', // Muy Insatisfecho - rojo claro
            2: '#FEF3C7', // Insatisfecho - amarillo claro  
            3: '#D1FAE5', // Satisfecho - verde claro
            4: '#DBEAFE'  // Muy Satisfecho - azul claro
        };
        return colores[nivelId] || '#f8f9fa';
    },

    getColorOjos(nivelId) {
        const colores = {
            1: '#DC2626', // Muy Insatisfecho - rojo
            2: '#D97706', // Insatisfecho - naranja
            3: '#059669', // Satisfecho - verde
            4: '#2563EB'  // Muy Satisfecho - azul
        };
        return colores[nivelId] || '#333';
    },

    getBocaSvg(nivelId) {
        const bocas = {
            1: 'M45 95c7-10 23-10 30 0',  // Muy Insatisfecho - muy triste
            2: 'M45 90c0-2 5-10 30 0',    // Insatisfecho - triste
            3: 'M45 85c7 10 23 10 30 0',  // Satisfecho - sonriente
            4: 'M45 80c10 15 20 15 30 0'  // Muy Satisfecho - muy sonriente
        };
        return bocas[nivelId] || 'M45 90c0 1 15 6 30 0';
    },
        // Métodos para indicador 0-10
       // MÉTODOS PARA INDICADOR 0-10 - VERSIÓN CORREGIDA
iniciarArrastre(event) {
    this.arrastrando = true;
    this.actualizarIndicador(event);
    
    document.addEventListener('mousemove', this.actualizarIndicador);
    document.addEventListener('mouseup', this.detenerArrastre);
    document.addEventListener('touchmove', this.actualizarIndicador, { passive: false });
    document.addEventListener('touchend', this.detenerArrastre);
    
    event.preventDefault();
},

actualizarIndicador(event) {
    if (!this.arrastrando) return;
    
    const track = document.querySelector('.indicador-track');
    if (!track) return;
    
    const rect = track.getBoundingClientRect();
    let clientX;
    
    if (event.type.includes('touch')) {
        clientX = event.touches[0].clientX;
    } else {
        clientX = event.clientX;
    }
    
    let posicion = (clientX - rect.left) / rect.width;
    posicion = Math.max(0, Math.min(1, posicion));
    
    // ACTUALIZAR DIRECTAMENTE EN respuestas
    const valor = Math.round(posicion * 10);
    this.respuestaIndicadorValor = valor;
    
    // GUARDAR DIRECTAMENTE EN EL OBJETO respuestas
    if (this.preguntaActualData.id) {
        this.respuestas[this.preguntaActualData.id] = valor;
        console.log('💾 Guardando respuesta:', this.preguntaActualData.id, '=', valor);
    }
    
    event.preventDefault();
},

detenerArrastre() {
    this.arrastrando = false;
    
    document.removeEventListener('mousemove', this.actualizarIndicador);
    document.removeEventListener('mouseup', this.detenerArrastre);
    document.removeEventListener('touchmove', this.actualizarIndicador);
    document.removeEventListener('touchend', this.detenerArrastre);
},

    // MÉTODOS PARA OPCIÓN ÚNICA CON TEXTO LIBRE - CORREGIDOS
    opcionEsTextoLibre(opcionTexto) {
        if (!opcionTexto) return false;
        const texto = opcionTexto.toLowerCase();
        console.log('🔍 Verificando si es texto libre:', opcionTexto, '->', texto.includes('otro') || texto.includes('especifique'));
        return texto.includes('otro') || texto.includes('especifique');
    },

    seleccionarOpcionUnicaConTexto(opcionId, opcionTexto) {
        console.log('🔘 Seleccionando opción con texto libre:', opcionId, '-', opcionTexto);
        
        this.respuestaUnica = opcionId;
        this.errorValidacion = '';
        
        // Verificar si es la opción de texto libre
        const esTextoLibre = this.opcionEsTextoLibre(opcionTexto);
        console.log('📝 Es texto libre?:', esTextoLibre);
        
        if (esTextoLibre) {
            this.opcionTextoLibreSeleccionada = opcionId;
            console.log('✅ Opción texto libre seleccionada');
            // Inicializar texto libre si está vacío
            if (this.textoLibreOpcion === undefined || this.textoLibreOpcion === null) {
                this.textoLibreOpcion = '';
            }
        } else {
            this.opcionTextoLibreSeleccionada = null;
            this.textoLibreOpcion = '';
            console.log('✅ Opción normal seleccionada');
        }
        
        // Guardar respuesta inmediatamente
        this.guardarRespuestaUnicaConTexto();
    },

    guardarTextoLibreOpcion() {
        console.log('📝 Texto libre cambiado:', this.textoLibreOpcion);
        
        // Limitar a 500 caracteres
        if (this.textoLibreOpcion.length > 500) {
            this.textoLibreOpcion = this.textoLibreOpcion.substring(0, 500);
        }
        this.guardarRespuestaUnicaConTexto();
    },

    guardarRespuestaUnicaConTexto() {
        const respuestaCompleta = {
            opcion_seleccionada_id: this.respuestaUnica,
            texto_libre: this.textoLibreOpcion || ''
        };
        
        this.respuestas[this.preguntaActualData.id] = respuestaCompleta;
        console.log('💾 Guardando respuesta con texto libre:', {
            preguntaId: this.preguntaActualData.id,
            respuesta: respuestaCompleta,
            todasLasRespuestas: this.respuestas
        });
    },

manejarFocoTexto(event) {
    // Prevenir que el clic en el textarea deseleccione la opción
    event.stopPropagation();
    console.log('🎯 Textarea enfocado');
},

// 🔥 NUEVO: Método para debug del flujo
debugFlujoPreguntas() {
    console.log('=== DEBUG FLUJO PREGUNTAS ===');
    console.log('Preguntas en flujo:', this.preguntas.length);
    this.preguntas.forEach((p, index) => {
        console.log(`${index + 1}. ${p.pregunta} (ID: ${p.id}) ${p.es_condicional ? '[CONDICIONAL]' : '[RAÍZ]'}`);
        if (p.opciones) {
            p.opciones.forEach(op => {
                console.log(`   - ${op.opcion} → Pregunta siguiente: ${op.pregunta_siguiente_id}`);
            });
        }
    });
    console.log('Pregunta actual:', this.preguntaActual + 1);
    console.log('=============================');
},


/**
 * Cargar subpreguntas para una opción específica
 */
async cargarSubpreguntasParaOpcion(opcionId) {
    try {
        console.log('🔍 Cargando subpreguntas para opción ID:', opcionId);
        
        const response = await fetch(`/api/subpreguntas/${opcionId}`);
        if (response.ok) {
            const subpreguntas = await response.json();
            console.log('📝 Subpreguntas cargadas:', subpreguntas);
            
            if (subpreguntas.length > 0) {
                this.subpreguntasActuales = subpreguntas;
                this.mostrandoSubpreguntas = true;
                this.opcionConSubpreguntasSeleccionada = opcionId;
                
                // Inicializar respuestas para subpreguntas
                this.inicializarRespuestasSubpreguntas(subpreguntas);
                
                console.log('✅ Subpreguntas activadas:', this.subpreguntasActuales.length);
            } else {
                console.log('📭 No hay subpreguntas para esta opción');
                this.mostrandoSubpreguntas = false;
                this.subpreguntasActuales = [];
            }
        } else {
            console.log('❌ Error en la respuesta de subpreguntas');
            this.mostrandoSubpreguntas = false;
            this.subpreguntasActuales = [];
        }
    } catch (error) {
        console.error('❌ Error cargando subpreguntas:', error);
        this.mostrandoSubpreguntas = false;
        this.subpreguntasActuales = [];
    }
},

/**
 * Inicializar estructura de respuestas para subpreguntas
 */
inicializarRespuestasSubpreguntas(subpreguntas) {
    this.respuestasSubpreguntas = {};
    
    subpreguntas.forEach(subpregunta => {
        switch (subpregunta.tipo) {
            case 'indicador_0_10':
                this.respuestasSubpreguntas[subpregunta.id] = { valor: 5 };
                break;
            case 'texto_libre':
                this.respuestasSubpreguntas[subpregunta.id] = { texto: '' };
                break;
            case 'opcion_unica':
                this.respuestasSubpreguntas[subpregunta.id] = { opcionSeleccionada: null };
                break;
            case 'opcion_unica_texto_libre':
                // 🔥 CORRECCIÓN: Inicializar correctamente para opcion_unica_texto_libre
                this.respuestasSubpreguntas[subpregunta.id] = { opcionSeleccionada: null, texto: '' };
                break;
            case 'opcion_multiple':
                this.respuestasSubpreguntas[subpregunta.id] = { opcionesSeleccionadas: [] };
                break;
            default:
                // 🔥 CORRECCIÓN: Por defecto, usar estructura para opción única
                this.respuestasSubpreguntas[subpregunta.id] = { opcionSeleccionada: null };
        }
    });
    
    console.log('📋 Respuestas subpreguntas inicializadas:', this.respuestasSubpreguntas);
},

/**
 * Validar que todas las subpreguntas estén respondidas
 */
validarSubpreguntasRespondidas() {
    if (this.subpreguntasActuales.length === 0) return true;
    
    const todasRespondidas = this.subpreguntasActuales.every(subpregunta => {
        const respuesta = this.respuestasSubpreguntas[subpregunta.id];
        if (!respuesta) return false;
        
        switch (subpregunta.tipo) {
            case 'opcion_unica':
                return respuesta.opcionSeleccionada !== null;
            case 'opcion_multiple':
                return respuesta.opcionesSeleccionadas && respuesta.opcionesSeleccionadas.length > 0;
            case 'texto_libre':
                return respuesta.texto && respuesta.texto.trim() !== '';
            case 'indicador_0_10':
                return respuesta.valor !== null && respuesta.valor !== '';
            default:
                return true;
        }
    });
    
    console.log('✅ Validación subpreguntas:', todasRespondidas ? 'TODAS RESPONDIDAS' : 'FALTAN RESPUESTAS');
    return todasRespondidas;
},

/**
 * Validar la subpregunta actual
 */
validarSubpreguntaActual() {
    const subpregunta = this.subpreguntaActual;
    if (!subpregunta) return false;
    
    const respuesta = this.respuestasSubpreguntas[subpregunta.id];
    if (!respuesta) return false;
    
    switch (subpregunta.tipo) {
        case 'opcion_unica':
        case 'opcion_unica_texto_libre':
            if (respuesta.opcionSeleccionada === null) return false;
            // Si es texto libre y seleccionó "Otro", validar que haya texto
            if (subpregunta.tipo === 'opcion_unica_texto_libre' && 
                respuesta.opcionSeleccionada && 
                (respuesta.opcionSeleccionada.toLowerCase().includes('otro') || respuesta.opcionSeleccionada.toLowerCase().includes('especifique'))) {
                return respuesta.texto && respuesta.texto.trim() !== '';
            }
            return true;
        case 'opcion_multiple':
            return respuesta.opcionesSeleccionadas && respuesta.opcionesSeleccionadas.length > 0;
        case 'texto_libre':
            return respuesta.texto && respuesta.texto.trim() !== '';
        case 'indicador_0_10':
            return respuesta.valor !== null && respuesta.valor !== undefined;
        default:
            return true;
    }
},

/**
 * Inicializar respuestas para subpreguntas
 */
inicializarRespuestasSubpreguntas() {
    this.respuestasSubpreguntas = {};
    
    this.subpreguntasActuales.forEach(subpregunta => {
        switch (subpregunta.tipo) {
            case 'indicador_0_10':
                this.respuestasSubpreguntas[subpregunta.id] = { valor: 5 };
                break;
            case 'texto_libre':
                this.respuestasSubpreguntas[subpregunta.id] = { texto: '' };
                break;
            case 'opcion_unica':
                this.respuestasSubpreguntas[subpregunta.id] = { opcionSeleccionada: null };
                break;
            case 'opcion_unica_texto_libre':
                // 🔥 CORRECCIÓN: Inicializar correctamente para opcion_unica_texto_libre
                this.respuestasSubpreguntas[subpregunta.id] = { opcionSeleccionada: null, texto: '' };
                break;
            case 'opcion_multiple':
                this.respuestasSubpreguntas[subpregunta.id] = { opcionesSeleccionadas: [] };
                break;
            default:
                // 🔥 CORRECCIÓN: Por defecto, usar estructura para opción única
                this.respuestasSubpreguntas[subpregunta.id] = { opcionSeleccionada: null };
        }
    });
    
    console.log('📋 Respuestas subpreguntas inicializadas:', this.respuestasSubpreguntas);
},

/**
 * Guardar respuestas de subpreguntas en el objeto principal
 */
guardarRespuestasSubpreguntas() {
    // 🔥 CORRECCIÓN FCR: Si estamos en modo FCR directo, no hay pregunta/opción padre
    if (this.nivelSeleccionado?.esFCR) {
        // Para FCR directo, las respuestas ya están en this.respuestasSubpreguntas
        console.log('💾 FCR: Respuestas de subpreguntas ya están en respuestasSubpreguntas:', this.respuestasSubpreguntas);
        return;
    }
    
    // Para subpreguntas normales (de opciones de preguntas)
    if (this.subpreguntasActuales.length > 0 && this.respuestaUnica && this.preguntaActualData) {
        const preguntaId = this.preguntaActualData.id;
        const opcionId = this.respuestaUnica;
        
        const claveSubpreguntas = `subpreguntas_${preguntaId}_${opcionId}`;
        
        this.respuestas[claveSubpreguntas] = {
            opcion_pregunta_id: opcionId,
            subpreguntas: this.respuestasSubpreguntas
        };
        
        console.log('💾 Respuestas de subpreguntas guardadas:', claveSubpreguntas);
    }
},

/**
 * Manejar selección de opción única para subpreguntas
 */
seleccionarOpcionSubpregunta(subpregunta, opcion) {
    console.log('🔘 Seleccionando opción para subpregunta:', subpregunta.id, opcion);
    
    if (!this.respuestasSubpreguntas[subpregunta.id]) {
        this.respuestasSubpreguntas[subpregunta.id] = { 
            opcionSeleccionada: null,
            texto: ''
        };
    }
    
    this.respuestasSubpreguntas[subpregunta.id].opcionSeleccionada = opcion;
    
    // Si es opción única con texto libre y NO seleccionó "Otro especificar", limpiar texto
    if (subpregunta.tipo === 'opcion_unica_texto_libre' && opcion !== 'Otro especificar') {
        this.respuestasSubpreguntas[subpregunta.id].texto = '';
    }
    
    console.log('📝 Respuesta subpregunta actualizada:', this.respuestasSubpreguntas[subpregunta.id]);
},

/**
 * Manejar selección múltiple para subpreguntas
 */
toggleOpcionMultipleSubpregunta(subpregunta, opcion) {
    console.log('🔘 Toggle opción múltiple para subpregunta:', subpregunta.id, opcion);
    
    if (!this.respuestasSubpreguntas[subpregunta.id]) {
        this.respuestasSubpreguntas[subpregunta.id] = { opcionesSeleccionadas: [] };
    }
    
    const current = this.respuestasSubpreguntas[subpregunta.id].opcionesSeleccionadas || [];
    
    if (current.includes(opcion)) {
        this.respuestasSubpreguntas[subpregunta.id].opcionesSeleccionadas = current.filter(o => o !== opcion);
    } else {
        this.respuestasSubpreguntas[subpregunta.id].opcionesSeleccionadas = [...current, opcion];
    }
    
    console.log('📝 Respuesta subpregunta actualizada:', this.respuestasSubpreguntas[subpregunta.id]);
},

/**
 * Actualizar texto libre para subpreguntas
 */
actualizarTextoSubpregunta(subpregunta, event) {
    const texto = event.target.value;
    console.log('📝 Actualizando texto para subpregunta:', subpregunta.id, texto);
    
    if (!this.respuestasSubpreguntas[subpregunta.id]) {
        this.respuestasSubpreguntas[subpregunta.id] = { texto: '' };
    }
    
    this.respuestasSubpreguntas[subpregunta.id].texto = texto;
},

/**
 * Actualizar indicador para subpreguntas
 */
actualizarIndicadorSubpregunta(subpregunta, valor) {
    console.log('🎚️ Actualizando indicador para subpregunta:', subpregunta.id, valor);
    
    if (!this.respuestasSubpreguntas[subpregunta.id]) {
        this.respuestasSubpreguntas[subpregunta.id] = { valor: 5 };
    }
    
    this.respuestasSubpreguntas[subpregunta.id].valor = valor;
},

/**
 * Iniciar arrastre para indicador de subpreguntas
 */
iniciarArrastreSubpregunta(subpregunta, event) {
    event.preventDefault();
    const track = event.currentTarget;
    const rect = track.getBoundingClientRect();
    
    const actualizarValor = (clientX) => {
        let posicion = (clientX - rect.left) / rect.width;
        posicion = Math.max(0, Math.min(1, posicion));
        const valor = Math.round(posicion * 10);
        
        if (!this.respuestasSubpreguntas[subpregunta.id]) {
            this.respuestasSubpreguntas[subpregunta.id] = { valor: 5 };
        }
        this.respuestasSubpreguntas[subpregunta.id].valor = valor;
    };
    
    // Manejar mouse
    const onMouseMove = (e) => actualizarValor(e.clientX);
    const onMouseUp = () => {
        document.removeEventListener('mousemove', onMouseMove);
        document.removeEventListener('mouseup', onMouseUp);
    };
    
    // Manejar touch
    const onTouchMove = (e) => {
        e.preventDefault();
        actualizarValor(e.touches[0].clientX);
    };
    const onTouchEnd = () => {
        document.removeEventListener('touchmove', onTouchMove);
        document.removeEventListener('touchend', onTouchEnd);
    };
    
    actualizarValor(event.type.includes('touch') ? event.touches[0].clientX : event.clientX);
    
    document.addEventListener('mousemove', onMouseMove);
    document.addEventListener('mouseup', onMouseUp);
    document.addEventListener('touchmove', onTouchMove, { passive: false });
    document.addEventListener('touchend', onTouchEnd);
},

/**
 * 🔥 NUEVO: Retroceder a subpregunta anterior
 */
subpreguntaAnterior() {
    if (this.subpreguntaIndex > 0) {
        this.subpreguntaIndex--;
        this.errorValidacion = '';
        console.log('⬅️ Retrocediendo a subpregunta:', this.subpreguntaIndex + 1);
    }
},

/**
 * 🔥 CORREGIDO: Guardar respuestas de preguntas de rango
 */
guardarRespuestaPreguntaRango() {
    const pregunta = this.preguntaActualData;
    if (pregunta && pregunta.es_pregunta_rango) {
        const respuesta = this.obtenerRespuestaActual();
        
        // 🔥 CORRECCIÓN: Usar el ID real de la pregunta de rango
        const claveRango = `pregunta_${pregunta.id}`;
        
        this.respuestas[claveRango] = {
            ...respuesta,
            pregunta_rango_id: pregunta.pregunta_rango_id,
            es_pregunta_rango: true
        };
        
        console.log('💾 Respuesta de pregunta de rango guardada:', {
            clave: claveRango,
            respuesta: this.respuestas[claveRango],
            preguntaActual: pregunta
        });
    } else {
        console.warn('⚠️ No se puede guardar respuesta de rango - pregunta no válida:', pregunta);
    }
},
/**
 * 🔥 CORREGIDO: Extraer respuestas de preguntas de rango SIN DUPLICADOS
 */
extraerRespuestasRangos() {
    const respuestas = [];
    const clavesProcesadas = []; // Para evitar duplicados
    
    console.log('🔍 Buscando respuestas de rangos en:', this.respuestas);
    
    Object.keys(this.respuestas).forEach(clave => {
        const respuesta = this.respuestas[clave];
        
        // Verificar si es una respuesta de rango
        if (respuesta && respuesta.es_pregunta_rango && respuesta.pregunta_rango_id) {
            // 🔥 CORRECCIÓN: Crear clave única incluyendo opciones_seleccionadas
            const opcionesKey = respuesta.opciones_seleccionadas 
                ? JSON.stringify(respuesta.opciones_seleccionadas.sort()) 
                : '';
            const claveUnica = `rango_${respuesta.pregunta_rango_id}_${respuesta.opcion_seleccionada_id || ''}_${respuesta.texto_libre || ''}_${opcionesKey}`;
            
            if (!clavesProcesadas.includes(claveUnica)) {
                clavesProcesadas.push(claveUnica);
                
                console.log('📝 Procesando respuesta de rango ÚNICA:', clave, respuesta);
                
                const respuestaProcesada = {
                    pregunta_rango_id: respuesta.pregunta_rango_id
                };
                
                // 🔥 NUEVO: Procesar opciones múltiples PRIMERO (prioridad)
                if (respuesta.opciones_seleccionadas && Array.isArray(respuesta.opciones_seleccionadas) && respuesta.opciones_seleccionadas.length > 0) {
                    respuestaProcesada.opciones_seleccionadas = respuesta.opciones_seleccionadas;
                    console.log('✅ Opciones múltiples detectadas:', respuesta.opciones_seleccionadas);
                }
                // Procesar opción única
                else if (respuesta.opcion_seleccionada_id !== undefined && respuesta.opcion_seleccionada_id !== null) {
                    respuestaProcesada.opcion_seleccionada = respuesta.opcion_seleccionada_id;
                }
                
                // Procesar texto libre
                if (respuesta.texto_libre && respuesta.texto_libre.trim() !== '') {
                    respuestaProcesada.texto_respuesta = respuesta.texto_libre;
                }
                // Procesar valor indicador
                if (respuesta.valor !== undefined && respuesta.valor !== null) {
                    respuestaProcesada.valor_indicador = respuesta.valor;
                }
                // 🔥 NUEVO: También procesar si viene 'texto' (para texto_libre)
                if (respuesta.texto && respuesta.texto.trim() !== '') {
                    respuestaProcesada.texto_respuesta = respuesta.texto.trim();
                }
                
                // Solo agregar si tiene datos válidos
                if (respuestaProcesada.opciones_seleccionadas !== undefined ||
                    respuestaProcesada.opcion_seleccionada !== undefined || 
                    respuestaProcesada.texto_respuesta !== undefined || 
                    respuestaProcesada.valor_indicador !== undefined) {
                    
                    respuestas.push(respuestaProcesada);
                    console.log('✅ Respuesta de rango única procesada:', respuestaProcesada);
                }
            } else {
                console.warn('⚠️ Ignorando respuesta duplicada:', claveUnica);
            }
        }
    });
    
    console.log('📦 Respuestas de rangos únicas extraídas:', respuestas);
    return respuestas;
},
// ✅ MEJORA: Método obtenerOpcionesPregunta más robusto
obtenerOpcionesPregunta() {
    const pregunta = this.preguntaActualData;
    
    console.log('📋 Obteniendo opciones para pregunta:', {
        id: pregunta.id,
        tipo: pregunta.tipo,
        esRango: pregunta.es_pregunta_rango,
        opcionesOriginales: pregunta.opciones,
        opcionesArray: pregunta.opciones_array
    });
    
    if (pregunta.es_pregunta_rango) {
        // Para preguntas de rango, usar opciones_array
        const opciones = pregunta.opciones_array || pregunta.opciones || [];
        console.log('📝 Opciones de pregunta rango:', opciones);
        return opciones;
    } else {
        // Para preguntas normales, usar el formato estándar
        const opciones = pregunta.opciones || [];
        console.log('📝 Opciones de pregunta normal:', opciones);
        return opciones;
    }
},

/**
 * 🔥 NUEVO: Obtener texto de una opción (compatible con ambos formatos)
 */
obtenerTextoOpcion(opcion) {
    if (typeof opcion === 'string') {
        return opcion;
    } else if (opcion && opcion.opcion) {
        return opcion.opcion;
    } else if (opcion && opcion.texto) {
        return opcion.texto;
    }
    return opcion;
},

/**
 * 🔥 NUEVO: Seleccionar opción única para preguntas de rango
 */
seleccionarOpcionUnicaRango(opcion, index) {
    console.log('🔘 Seleccionando opción para pregunta rango:', opcion, index);
    
    // Para preguntas de rango, usar el índice o el texto como identificador
    if (this.preguntaActualData.es_pregunta_rango) {
        this.respuestaUnica = typeof opcion === 'string' ? opcion : index;
    } else {
        // Para preguntas normales, usar el ID
        this.respuestaUnica = opcion.id;
    }
    
    this.errorValidacion = '';
    console.log('✅ Opción seleccionada:', this.respuestaUnica);
},

/**
 * 🔥 NUEVO: Verificar si una opción está seleccionada en múltiple
 */
respuestaMultipleIncluye(opcion) {
    if (this.preguntaActualData.es_pregunta_rango) {
        const opcionTexto = typeof opcion === 'string' ? opcion : this.obtenerTextoOpcion(opcion);
        return this.respuestaMultiple.includes(opcionTexto);
    } else {
        return this.respuestaMultiple.includes(opcion.id);
    }
},

/**
 * 🔥 NUEVO: Toggle opción múltiple para preguntas de rango
 */
toggleOpcionMultipleRango(opcion, index) {
    console.log('🔘 Toggle opción múltiple para pregunta rango:', opcion, index);
    
    let identificador;
    if (this.preguntaActualData.es_pregunta_rango) {
        identificador = typeof opcion === 'string' ? opcion : this.obtenerTextoOpcion(opcion);
    } else {
        identificador = opcion.id;
    }
    
    const current = this.respuestaMultiple;
    if (current.includes(identificador)) {
        this.respuestaMultiple = current.filter(item => item !== identificador);
    } else {
        this.respuestaMultiple = [...current, identificador];
    }
    
    this.errorValidacion = '';
    console.log('✅ Opciones múltiples actualizadas:', this.respuestaMultiple);
},

/**
 * 🔥 NUEVO: Seleccionar opción única con texto para rangos
 */
seleccionarOpcionUnicaConTextoRango(opcion, index) {
    console.log('🔘 Seleccionando opción con texto para rango:', opcion, index);
    
    // Determinar identificador
    let identificador;
    if (this.preguntaActualData.es_pregunta_rango) {
        identificador = typeof opcion === 'string' ? opcion : this.obtenerTextoOpcion(opcion);
    } else {
        identificador = opcion.id;
    }
    
    this.respuestaUnica = identificador;
    this.errorValidacion = '';
    
    // Verificar si es la opción de texto libre
    const opcionTexto = this.obtenerTextoOpcion(opcion);
    const esTextoLibre = this.opcionEsTextoLibre(opcionTexto);
    
    if (esTextoLibre) {
        this.opcionTextoLibreSeleccionada = identificador;
        console.log('✅ Opción texto libre seleccionada');
        if (this.textoLibreOpcion === undefined || this.textoLibreOpcion === null) {
            this.textoLibreOpcion = '';
        }
    } else {
        this.opcionTextoLibreSeleccionada = null;
        this.textoLibreOpcion = '';
        console.log('✅ Opción normal seleccionada');
    }
    
    this.guardarRespuestaUnicaConTextoRango();
},

/**
 * 🔥 NUEVO: Guardar texto libre para preguntas de rango
 */
guardarTextoLibreOpcionRango() {
    console.log('📝 Texto libre cambiado para rango:', this.textoLibreOpcion);
    
    if (this.textoLibreOpcion.length > 500) {
        this.textoLibreOpcion = this.textoLibreOpcion.substring(0, 500);
    }
    this.guardarRespuestaUnicaConTextoRango();
},

/**
 * 🔥 CORRECCIÓN: Guardar respuesta con texto libre para rangos
 */
guardarRespuestaUnicaConTextoRango() {
    const respuestaCompleta = {
        opcion_seleccionada_id: this.respuestaUnica,
        texto_libre: this.textoLibreOpcion || ''
    };
    
    // 🔥 CORRECCIÓN: Para preguntas de rango, no usar opcion_seleccionada_id numérico
    if (this.preguntaActualData.es_pregunta_rango) {
        // Para rangos, simplificar la estructura
        const respuestaSimple = {
            opcion_seleccionada_id: this.respuestaUnica, // Esto será el texto de la opción
            texto_libre: this.textoLibreOpcion || '',
            pregunta_rango_id: this.preguntaActualData.pregunta_rango_id,
            es_pregunta_rango: true
        };
        
        this.respuestas[this.preguntaActualData.id] = respuestaSimple;
    } else {
        // Para preguntas normales, estructura original
        this.respuestas[this.preguntaActualData.id] = respuestaCompleta;
    }
    
    console.log('💾 Guardando respuesta con texto libre para rango:', {
        preguntaId: this.preguntaActualData.id,
        respuesta: this.preguntaActualData.es_pregunta_rango ? 
                   this.respuestas[this.preguntaActualData.id] : respuestaCompleta
    });
},
/**
 * 🔥 NUEVO: Debug del estado actual
 */
debugEstadoActual() {
    console.log('=== DEBUG ESTADO ACTUAL ===');
    console.log('Pregunta actual:', this.preguntaActual);
    console.log('Total preguntas:', this.preguntas.length);
    console.log('Pregunta actual data:', this.preguntaActualData);
    console.log('Respuestas:', this.respuestas);
    console.log('Modo subpreguntas:', this.modoSubpreguntas);
    console.log('===========================');
},
// ✅ NUEVO MÉTODO: Guardar respuesta de opción única
guardarRespuestaUnica() {
    const preguntaId = this.preguntaActualData.id;
    const respuesta = this.respuestaUnica;
    
    console.log('💾 Guardando respuesta única:', { preguntaId, respuesta, esPreguntaRango: this.preguntaActualData.es_pregunta_rango });
    
    // 🔥 CORRECCIÓN: Si es pregunta de rango, usar guardarRespuestaPreguntaRango
    if (this.preguntaActualData.es_pregunta_rango) {
        const claveRango = `pregunta_${preguntaId}`;
        this.respuestas[claveRango] = {
            opcion_seleccionada_id: respuesta,
            pregunta_rango_id: this.preguntaActualData.pregunta_rango_id,
            es_pregunta_rango: true
        };
        console.log('✅ Respuesta de rango guardada:', this.respuestas[claveRango]);
        return;
    }
    
    if (this.preguntaActualData.tipo === 'opcion_unica_texto_libre') {
        // Mantener estructura para opción única con texto libre
        const respuestaActual = this.respuestas[preguntaId] || {};
        this.respuestas[preguntaId] = {
            ...respuestaActual,
            opcion_seleccionada_id: respuesta
        };
    } else {
        // Guardar directamente para opción única normal
        this.respuestas[preguntaId] = respuesta;
    }
    
    console.log('✅ Respuesta guardada:', this.respuestas[preguntaId]);
},

// ✅ NUEVO MÉTODO: Verificar si una opción está seleccionada
esOpcionSeleccionada(opcion, index) {
    if (!this.respuestaUnica) return false;
    
    if (this.preguntaActualData.es_pregunta_rango) {
        // Para preguntas de rango, comparar por texto
        const opcionTexto = typeof opcion === 'string' ? opcion : this.obtenerTextoOpcion(opcion);
        return this.respuestaUnica === opcionTexto;
    } else {
        // Para preguntas normales, comparar por ID
        const opcionId = opcion && opcion.id ? opcion.id : null;
        return this.respuestaUnica === opcionId;
    }
},

// 🔥 NUEVO: Métodos para NPS inicial
iniciarArrastreNPS(event) {
    this.solicitarPantallaCompleta();
    this.arrastrando = true;
    this.actualizarIndicadorNPS(event);
    document.addEventListener('mousemove', this.actualizarIndicadorNPS);
    document.addEventListener('mouseup', this.detenerArrastreNPS);
    document.addEventListener('touchmove', this.actualizarIndicadorNPS);
    document.addEventListener('touchend', this.detenerArrastreNPS);
},

actualizarIndicadorNPS(event) {
    if (!this.arrastrando) return;
    
    const track = event.currentTarget;
    if (!track) return;
    
    const rect = track.getBoundingClientRect();
    const clientX = event.clientX || (event.touches && event.touches[0].clientX);
    const percentage = Math.max(0, Math.min(100, ((clientX - rect.left) / rect.width) * 100));
    this.respuestaIndicadorNPS = Math.round((percentage / 100) * 10);
},

detenerArrastreNPS() {
    this.arrastrando = false;
    document.removeEventListener('mousemove', this.actualizarIndicadorNPS);
    document.removeEventListener('mouseup', this.detenerArrastreNPS);
    document.removeEventListener('touchmove', this.actualizarIndicadorNPS);
    document.removeEventListener('touchend', this.detenerArrastreNPS);
},

// 🔥 NUEVO: Seleccionar valor NPS directamente
seleccionarValorNPS(valor) {
    this.respuestaIndicadorNPS = valor;
},

// 🔥 NUEVO: Click en el track del slider
clickIndicadorNPS(event) {
    this.solicitarPantallaCompleta();
    const rect = event.currentTarget.getBoundingClientRect();
    const clientX = event.clientX || (event.touches && event.touches[0].clientX);
    const percentage = Math.max(0, Math.min(100, ((clientX - rect.left) / rect.width) * 100));
    this.respuestaIndicadorNPS = Math.round((percentage / 100) * 10);
},

// 🔥 NUEVO: Métodos para indicador 0-10 en el modal (NPS)
iniciarArrastreIndicador(event) {
    this.arrastrando = true;
    this.actualizarIndicadorModal(event);
    document.addEventListener('mousemove', this.actualizarIndicadorModal);
    document.addEventListener('mouseup', this.detenerArrastreIndicador);
    document.addEventListener('touchmove', this.actualizarIndicadorModal);
    document.addEventListener('touchend', this.detenerArrastreIndicador);
},

actualizarIndicadorModal(event) {
    if (!this.arrastrando || !this.preguntaActualData) return;
    
    const track = event.currentTarget || event.target?.closest('.indicador-track');
    if (!track) return;
    
    const rect = track.getBoundingClientRect();
    const clientX = event.clientX || (event.touches && event.touches[0]?.clientX);
    const percentage = Math.max(0, Math.min(100, ((clientX - rect.left) / rect.width) * 100));
    const valor = Math.round((percentage / 100) * 10);
    
    // Guardar el valor en las respuestas
    if (this.preguntaActualData.id) {
        this.respuestas[this.preguntaActualData.id] = { valor: valor };
        this.respuestaIndicadorValor = valor;
    }
    
    this.$forceUpdate();
},

detenerArrastreIndicador() {
    this.arrastrando = false;
    document.removeEventListener('mousemove', this.actualizarIndicadorModal);
    document.removeEventListener('mouseup', this.detenerArrastreIndicador);
    document.removeEventListener('touchmove', this.actualizarIndicadorModal);
    document.removeEventListener('touchend', this.detenerArrastreIndicador);
},

clickIndicador(event) {
    if (!this.preguntaActualData) return;
    
    const track = event.currentTarget || event.target?.closest('.indicador-track');
    if (!track) return;
    
    const rect = track.getBoundingClientRect();
    const clientX = event.clientX || (event.touches && event.touches[0]?.clientX);
    const percentage = Math.max(0, Math.min(100, ((clientX - rect.left) / rect.width) * 100));
    const valor = Math.round((percentage / 100) * 10);
    
    // Guardar el valor en las respuestas
    if (this.preguntaActualData.id) {
        this.respuestas[this.preguntaActualData.id] = { valor: valor };
        this.respuestaIndicadorValor = valor;
    }
    
    this.$forceUpdate();
},

seleccionarValorIndicador(valor) {
    if (!this.preguntaActualData) return;
    
    // Guardar el valor en las respuestas
    if (this.preguntaActualData.id) {
        this.respuestas[this.preguntaActualData.id] = { valor: valor };
        this.respuestaIndicadorValor = valor;
    }
    
    this.$forceUpdate();
},

async iniciarConNPS() {
    this.solicitarPantallaCompleta();
    // 🔥 CORRECCIÓN: Usar el nivel correcto desde la base de datos (nivel 1 para NPS)
    const valorGuardado = this.respuestaIndicadorNPS; // Guardar el valor del slider
    this.nivelSeleccionado = { id: 1, nombre: 'NPS', valor: valorGuardado };
    
    // Sincronizar ambos valores antes de iniciar el cuestionario
    this.respuestaIndicadorNPS = valorGuardado;
    this.respuestaIndicadorValor = valorGuardado;
    
    // 🔥 NUEVO: Establecer tipo actual como NPS
    this.tipoCalificacionActual = 'nps';
    
    // 🔥 CORRECCIÓN: Cargar las preguntas pero NO abrir el modal todavía
    this.cargando = true;
    
    try {
        const sedeGuardada = localStorage.getItem('sede_seleccionada');
        let sedeId = null;
        
        if (this.areaSeleccionada.sede_id) {
            sedeId = this.areaSeleccionada.sede_id;
        } else if (sedeGuardada) {
            const sedeResponse = await fetch(`/api/sedes/buscar?nombre=${encodeURIComponent(sedeGuardada)}`);
            if (sedeResponse.ok) {
                const sedeData = await sedeResponse.json();
                sedeId = sedeData.id;
            }
        }

        let url = `/api/preguntas?area_id=${this.areaSeleccionada.id}&nivel_id=${this.nivelSeleccionado.id}`;
        if (sedeId) {
            url += `&sede_id=${sedeId}`;
        }

        const response = await fetch(url);
        
        if (response.ok) {
            const todasLasPreguntas = await response.json();
            
            // Filtrar solo preguntas NPS raíces
            this.preguntas = todasLasPreguntas.filter(pregunta => {
                const esRaiz = !pregunta.es_condicional || pregunta.es_condicional === false;
                const esDelTipoCorrecto = pregunta.tipo_pregunta === 'nps';
                return esRaiz && esDelTipoCorrecto;
            });
            
            this.todasLasPreguntas = todasLasPreguntas;
        } else {
            throw new Error('Error al cargar preguntas');
        }
    } catch (error) {
        console.error('Error cargando preguntas NPS:', error);
        this.cargando = false;
        return;
    }
    
    // 🔥 CORRECCIÓN: Determinar si debemos eliminar la pregunta NPS del modal
    // Si NO viene del flujo secuencial (solo NPS) O si viene del flujo secuencial PERO NPS es el primer tipo
    // entonces debemos eliminar la pregunta NPS porque ya se seleccionó en la barra inicial
    const vieneDelFlujoSecuencial = this.tiposCalificacionSecuencia.length > 1;
    const esNPSPrimerTipo = vieneDelFlujoSecuencial && 
                           this.indiceTipoActual === 0 && 
                           this.tipoCalificacionActual === 'nps';
    const debeEliminarNPSDelModal = !vieneDelFlujoSecuencial || esNPSPrimerTipo;
    
    if (this.preguntas.length > 0 && debeEliminarNPSDelModal) {
        // Aplicar la lógica si NO viene del flujo secuencial O si NPS es el primer tipo del flujo
        const preguntaNPS = this.preguntas[0]; // La primera pregunta es NPS
        
        // Guardar la respuesta del indicador NPS
        if (preguntaNPS.tipo === 'indicador_0_10') {
            this.respuestas[preguntaNPS.id] = { valor: valorGuardado };
            console.log('✅ Respuesta NPS guardada:', valorGuardado);
            
            // Guardar el ID de la pregunta NPS antes de eliminarla
            const preguntaNPSId = preguntaNPS.id;
            
            // 🔥 CORRECCIÓN: Eliminar la pregunta NPS PRIMERO, antes de cargar la pregunta de rango
            // porque ya se seleccionó el valor en la barra inicial y no debe aparecer en el modal
            this.preguntas.splice(0, 1);
            console.log('🗑️ Pregunta NPS principal eliminada del flujo antes de cargar pregunta de rango');
            console.log('📋 Preguntas después de eliminar NPS:', this.preguntas.length);
            
            // Cargar directamente las subpreguntas de rango (mostrar alerta si no hay)
            const tienePreguntaRango = await this.cargarPreguntaRango(preguntaNPSId, valorGuardado, true);
            
            console.log('📋 Preguntas después de cargar pregunta de rango:', this.preguntas.length, this.preguntas.map(p => ({ id: p.id, tipo: p.tipo, es_rango: p.es_pregunta_rango })));
            
            if (tienePreguntaRango) {
                console.log('✅ Subpreguntas de rango cargadas, abriendo modal con pregunta de rango');
                
                // 🔥 VERIFICACIÓN CRÍTICA: Asegurar que no haya preguntas NPS (indicador_0_10) en el array
                this.preguntas = this.preguntas.filter(p => !(p.tipo === 'indicador_0_10' && p.tipo_pregunta === 'nps'));
                console.log('🔍 Preguntas después de filtrar NPS:', this.preguntas.length);
                
                // Verificar que la pregunta de rango esté en el array
                const preguntaRangoIndex = this.preguntas.findIndex(p => p.es_pregunta_rango && p.pregunta_indicador_id === preguntaNPSId);
                if (preguntaRangoIndex !== -1) {
                    this.preguntaActual = preguntaRangoIndex;
                } else {
                    // Si no se encuentra por ID, buscar la primera pregunta de rango
                    const primeraRangoIndex = this.preguntas.findIndex(p => p.es_pregunta_rango);
                    this.preguntaActual = primeraRangoIndex !== -1 ? primeraRangoIndex : 0;
                }
                
                // Verificar que la pregunta en preguntaActual no sea NPS
                if (this.preguntas[this.preguntaActual] && this.preguntas[this.preguntaActual].tipo === 'indicador_0_10') {
                    console.error('❌ ERROR: La pregunta actual es NPS, debería ser pregunta de rango');
                    // Buscar la siguiente pregunta que no sea NPS
                    const siguienteNoNPS = this.preguntas.findIndex((p, idx) => idx >= this.preguntaActual && p.tipo !== 'indicador_0_10');
                    if (siguienteNoNPS !== -1) {
                        this.preguntaActual = siguienteNoNPS;
                    }
                }
                
                // La pregunta de rango ya está en el array (se insertó por cargarPreguntaRango)
                // Ahora sí abrir el modal para mostrar directamente la pregunta de rango
                this.mostrarCuestionario = true;
                this.cargando = false;
                console.log('✅ Abriendo modal directamente con pregunta de rango (sin pregunta NPS)');
                console.log('📍 Índice de pregunta actual:', this.preguntaActual);
                console.log('📝 Pregunta que se mostrará:', this.preguntas[this.preguntaActual]);
                console.log('🔍 Verificación final - Tipo de pregunta:', this.preguntas[this.preguntaActual]?.tipo, 'Es rango:', this.preguntas[this.preguntaActual]?.es_pregunta_rango);
                return; // 🔥 IMPORTANTE: Salir aquí para no continuar con el código siguiente
            } else {
                // 🔥 CORRECCIÓN: Cuando no hay pregunta de rango, la alerta ya se mostró en cargarPreguntaRango
                // NO abrir el modal, solo finalizar o avanzar
                this.cargando = false;
                const hayMasTiposNPS = this.tiposCalificacionSecuencia.length > 1 && 
                                        this.indiceTipoActual < this.tiposCalificacionSecuencia.length - 1;
                
                if (!hayMasTiposNPS) {
                    console.log('🎉 NPS final sin preguntas de rango: Finalizando directamente...');
                    if (this.tiposCalificacionSecuencia.length > 1) {
                        await this.guardarCalificacionTipoIndividual();
                    } else {
                        await this.guardarCalificacionCompleta();
                    }
                    this.mostrarAgradecimiento = true;
                    this.iniciarTemporizadorCierre();
                    return;
                } else {
                    console.log('➡️ NPS sin pregunta de rango pero hay más tipos: Avanzando...');
                    await this.avanzarAlSiguienteTipo();
                    return;
                }
            }
        } else {
            // Si la pregunta NPS no es de tipo indicador_0_10, abrir el modal normalmente
            this.mostrarCuestionario = true;
            this.preguntaActual = 0;
            this.cargando = false;
            console.log('✅ Pregunta NPS no es indicador_0_10, abriendo modal normalmente');
            return;
        }
    } else if (vieneDelFlujoSecuencial && this.preguntas.length > 0 && !esNPSPrimerTipo) {
        // 🔥 CORRECCIÓN: Si viene del flujo secuencial pero NPS NO es el primer tipo,
        // inicializar el valor y abrir el modal (el usuario aún no ha seleccionado en la barra inicial)
        const preguntaNPS = this.preguntas[0];
        if (preguntaNPS.tipo === 'indicador_0_10') {
            // Inicializar el valor del indicador con el valor guardado
            this.respuestas[preguntaNPS.id] = { valor: valorGuardado };
            this.respuestaIndicadorValor = valorGuardado;
            // Abrir el modal para que el usuario pueda usar el slider
            this.mostrarCuestionario = true;
            this.preguntaActual = 0;
            this.cargando = false;
            console.log('✅ NPS en flujo secuencial (no es primer tipo): Pregunta preparada con valor inicial:', valorGuardado);
        }
    } else {
        this.cargando = false;
    }
},

async iniciarConFCR(resuelto, opcion) {
    this.solicitarPantallaCompleta();
    this.respuestaFCR = resuelto;
    const opcionSeleccionada = opcion;
    
    // 🔥 NUEVO: Establecer tipo actual como FCR
    this.tipoCalificacionActual = 'fcr';
    
    if (resuelto) {
        // ✅ Si selecciona "Sí" → Verificar si tiene subpreguntas primero
        console.log('✅ FCR: Se resolvió');
        
        // Guardar respuesta FCR
        if (this.preguntaFCRPrincipal && opcionSeleccionada) {
            this.respuestas[this.preguntaFCRPrincipal.id] = {
                opcion_seleccionada_id: opcionSeleccionada.id,
                texto_libre: ''
            };
        }
        
        // 🔥 CORRECCIÓN: Verificar si la opción "Sí" tiene subpreguntas
        // Primero verificar el flag, pero también verificar directamente si hay subpreguntas en la opción
        const tieneSubpreguntasFlag = opcionSeleccionada.tiene_subpreguntas || 
                                      (opcionSeleccionada.subpreguntas && opcionSeleccionada.subpreguntas.length > 0);
        
        console.log('🔍 Verificando subpreguntas para "Sí":', {
            tiene_subpreguntas: opcionSeleccionada.tiene_subpreguntas,
            subpreguntas: opcionSeleccionada.subpreguntas,
            opcionId: opcionSeleccionada.id,
            opcion: opcionSeleccionada
        });
        
        if (tieneSubpreguntasFlag) {
            console.log('✅ FCR: Opción "Sí" tiene subpreguntas, cargándolas...');
            
            // Simular nivel para cargar las subpreguntas
            this.nivelSeleccionado = { 
                id: 2, 
                nombre: 'FCR - Sí',
                valor: 2,
                esFCR: true,
                resuelto: true
            };
            
            // 🔥 CORRECCIÓN: Mantener el modal abierto antes de cargar subpreguntas
            this.mostrarCuestionario = true;
            
            // Cargar las subpreguntas de "Sí"
            await this.cargarSubpreguntasFCRParaOpcion(opcionSeleccionada.id, true);
            return;
        } else {
            console.log('⚠️ FCR: Opción "Sí" NO tiene subpreguntas, continuando flujo normal');
        }
        
        // Si no tiene subpreguntas, continuar con el flujo normal
        // Verificar si hay más tipos en la secuencia
        const hayMasTipos = this.tiposCalificacionSecuencia.length > 1 && 
                            this.indiceTipoActual < this.tiposCalificacionSecuencia.length - 1;
        
        if (!hayMasTipos) {
            // Es el último tipo, finalizar
            console.log('🎉 FCR final (último tipo con Sí): Finalizando calificación completa...');
            if (this.tiposCalificacionSecuencia.length > 1) {
                await this.guardarCalificacionTipoIndividual();
            } else {
                await this.guardarCalificacionCompleta();
            }
            this.mostrarCuestionario = false;
            this.mostrarAgradecimiento = true;
            this.iniciarTemporizadorCierre();
            return;
        } else {
            // Hay más tipos, avanzar al siguiente
            console.log('➡️ FCR completado (Sí), avanzando al siguiente tipo...');
            await this.avanzarAlSiguienteTipo();
            return;
        }
        
    } else {
        // ❌ Si selecciona "No" → Guardar respuesta y cargar subpreguntas
        console.log('❌ FCR: NO se resolvió, cargando pregunta del motivo');
        
        // Guardar respuesta FCR "No"
        if (this.preguntaFCRPrincipal && opcionSeleccionada) {
            this.respuestas[this.preguntaFCRPrincipal.id] = {
                opcion_seleccionada_id: opcionSeleccionada.id,
                texto_libre: ''
            };
        }
        
        // Simular nivel para cargar las subpreguntas
        this.nivelSeleccionado = { 
            id: 2, 
            nombre: 'FCR - Motivo',
            valor: 2,
            esFCR: true,
            resuelto: false
        };
        
        // Iniciar con las subpreguntas directamente
        await this.cargarSubpreguntasFCR();
    }
},

// 🔥 NUEVO: Método para cargar subpreguntas de cualquier opción FCR (Sí o No)
async cargarSubpreguntasFCRParaOpcion(opcionId, esSi = false) {
    this.cargando = true;
    
    try {
        const sedeGuardada = localStorage.getItem('sede_seleccionada');
        const sedeResponse = await fetch(`/api/sedes/buscar?nombre=${encodeURIComponent(sedeGuardada)}`);
        let sedeId = 1;
        if (sedeResponse.ok) {
            const sedeData = await sedeResponse.json();
            sedeId = sedeData.id;
        }
        
        // Cargar las subpreguntas de la opción
        const subpreguntasResponse = await fetch(`/api/subpreguntas/${opcionId}`);
        
        if (subpreguntasResponse.ok) {
            let subpreguntas = await subpreguntasResponse.json();
            
            // Procesar opciones de las subpreguntas
            subpreguntas = subpreguntas.map(subpregunta => {
                // Asegurar que las opciones sean un array
                let opcionesArray = [];
                if (subpregunta.opciones) {
                    if (Array.isArray(subpregunta.opciones)) {
                        opcionesArray = subpregunta.opciones;
                    } else if (typeof subpregunta.opciones === 'string') {
                        try {
                            opcionesArray = JSON.parse(subpregunta.opciones);
                        } catch (e) {
                            console.warn('Error parseando opciones:', e);
                            opcionesArray = [];
                        }
                    }
                }
                
                return {
                    ...subpregunta,
                    opciones: opcionesArray,
                    opciones_array: opcionesArray
                };
            });
            
            if (subpreguntas.length > 0) {
                console.log('✅ Subpreguntas cargadas:', subpreguntas);
                
                // Inicializar modo subpreguntas con los datos ya cargados
                this.subpreguntasActuales = subpreguntas;
                this.subpreguntaIndex = 0;
                this.modoSubpreguntas = true;
                this.inicializarRespuestasSubpreguntas();
                
                // 🔥 CORRECCIÓN: Asegurar que el modal esté visible para mostrar subpreguntas
                this.mostrarCuestionario = true;
                this.cargando = false;
                
                console.log('✅ Modo subpreguntas activado para opción:', opcionId);
                console.log('✅ mostrarCuestionario:', this.mostrarCuestionario);
                console.log('✅ subpreguntasActuales.length:', this.subpreguntasActuales.length);
            } else {
                console.log('⚠️ No hay subpreguntas para esta opción');
                this.cargando = false;
                
                // Si no hay subpreguntas, continuar con el flujo normal
                const hayMasTipos = this.tiposCalificacionSecuencia.length > 1 && 
                                    this.indiceTipoActual < this.tiposCalificacionSecuencia.length - 1;
                
                if (!hayMasTipos) {
                    if (this.tiposCalificacionSecuencia.length > 1) {
                        await this.guardarCalificacionTipoIndividual();
                    } else {
                        await this.guardarCalificacionCompleta();
                    }
                    this.mostrarCuestionario = false;
                    this.mostrarAgradecimiento = true;
                    this.iniciarTemporizadorCierre();
                } else {
                    await this.avanzarAlSiguienteTipo();
                }
            }
        } else {
            throw new Error('Error al cargar subpreguntas');
        }
    } catch (error) {
        console.error('❌ Error cargando subpreguntas FCR:', error);
        this.cargando = false;
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error al cargar las subpreguntas: ' + error.message,
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#ef4444'
        });
        
        // Continuar con el flujo normal en caso de error
        const hayMasTipos = this.tiposCalificacionSecuencia.length > 1 && 
                            this.indiceTipoActual < this.tiposCalificacionSecuencia.length - 1;
        
        if (!hayMasTipos) {
            if (this.tiposCalificacionSecuencia.length > 1) {
                await this.guardarCalificacionTipoIndividual();
            } else {
                await this.guardarCalificacionCompleta();
            }
            this.mostrarCuestionario = false;
            this.mostrarAgradecimiento = true;
            this.iniciarTemporizadorCierre();
        } else {
            await this.avanzarAlSiguienteTipo();
        }
    }
},

async cargarSubpreguntasFCR() {
    this.cargando = true;
    
    try {
        const sedeGuardada = localStorage.getItem('sede_seleccionada');
        const sedeResponse = await fetch(`/api/sedes/buscar?nombre=${encodeURIComponent(sedeGuardada)}`);
        let sedeId = 1;
        if (sedeResponse.ok) {
            const sedeData = await sedeResponse.json();
            sedeId = sedeData.id;
        }
        
        // Buscar la opción "No" de la pregunta FCR
        const response = await fetch(`/api/preguntas?area_id=${this.areaSeleccionada.id}&nivel_id=1&sede_id=${sedeId}`);
        
        if (response.ok) {
            const todasLasPreguntas = await response.json();
            
            if (todasLasPreguntas.length > 0) {
                const preguntaFCR = todasLasPreguntas[0];
                
                // Buscar la opción "No" y sus subpreguntas
                const opcionNo = preguntaFCR.opciones.find(op => op.opcion.toLowerCase() === 'no');
                
                if (opcionNo && opcionNo.tiene_subpreguntas) {
                    console.log('📝 Opción "No" tiene subpreguntas, cargándolas...');
                    
                    // Cargar las subpreguntas
                    const subpreguntasResponse = await fetch(`/api/subpreguntas/${opcionNo.id}`);
                    
                    if (subpreguntasResponse.ok) {
                        let subpreguntas = await subpreguntasResponse.json();
                        
                        // Procesar opciones de las subpreguntas
                        subpreguntas = subpreguntas.map(subpregunta => {
                            // Asegurar que las opciones sean un array
                            let opciones = subpregunta.opciones;
                            if (typeof opciones === 'string') {
                                try {
                                    opciones = JSON.parse(opciones);
                                } catch (e) {
                                    console.error('Error parseando opciones:', e);
                                    opciones = [];
                                }
                            }
                            if (!Array.isArray(opciones)) {
                                opciones = [];
                            }
                            
                            return {
                                ...subpregunta,
                                opciones: opciones
                            };
                        });
                        
                        this.subpreguntasActuales = subpreguntas;
                        console.log('✅ Subpreguntas cargadas:', this.subpreguntasActuales.length);
                        console.log('📋 Subpreguntas completas:', subpreguntas);
                        
                        // Iniciar modo subpreguntas
                        this.modoSubpreguntas = true;
                        this.subpreguntaIndex = 0;
                        this.inicializarRespuestasSubpreguntas();
                        this.mostrarCuestionario = true;
                        this.cargando = false;
                    } else {
                        // 🔥 CORRECCIÓN: Si hay error cargando subpreguntas, finalizar DIRECTAMENTE sin mensaje
                        console.error('❌ No se pudieron cargar las subpreguntas, finalizando directamente');
                        this.cargando = false;
                        if (this.tiposCalificacionSecuencia.length > 1) {
                            await this.guardarCalificacionTipoIndividual();
                        } else {
                            await this.guardarCalificacionCompleta();
                        }
                        this.mostrarCuestionario = false;
                        this.mostrarAgradecimiento = true;
                        this.iniciarTemporizadorCierre();
                    }
                } else {
                    // 🔥 CORRECCIÓN: Si no hay subpreguntas para "No", finalizar DIRECTAMENTE sin mensaje
                    console.log('⚠️ FCR "No": No tiene subpreguntas configuradas, finalizando directamente sin mensaje');
                    this.cargando = false;
                    if (this.tiposCalificacionSecuencia.length > 1) {
                        await this.guardarCalificacionTipoIndividual();
                    } else {
                        await this.guardarCalificacionCompleta();
                    }
                    this.mostrarCuestionario = false;
                    this.mostrarAgradecimiento = true;
                    this.iniciarTemporizadorCierre();
                }
            }
        }
        
    } catch (error) {
        console.error('Error cargando subpreguntas FCR:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error al cargar las preguntas del motivo',
            confirmButtonColor: '#ef4444'
        });
    } finally {
        this.cargando = false;
    }
},

// 🔥 NUEVO: Cargar pregunta FCR desde BD
async cargarPreguntaFCR() {
    this.cargandoPreguntaFCR = true;
    try {
        const sedeGuardada = localStorage.getItem('sede_seleccionada');
        const sedeResponse = await fetch(`/api/sedes/buscar?nombre=${encodeURIComponent(sedeGuardada)}`);
        let sedeId = 1;
        if (sedeResponse.ok) {
            const sedeData = await sedeResponse.json();
            sedeId = sedeData.id;
        }
        
        // Buscar pregunta FCR para esta área
        const response = await fetch(`/api/preguntas?area_id=${this.areaSeleccionada.id}&nivel_id=todas&sede_id=${sedeId}`);
        
        if (response.ok) {
            const preguntas = await response.json();
            // Buscar pregunta con tipo_pregunta = 'fcr'
            const preguntaFCR = preguntas.find(p => p.tipo_pregunta === 'fcr' && p.is_active);
            
            if (preguntaFCR) {
                // 🔥 CORRECCIÓN: Asegurar que cada opción tenga el flag tiene_subpreguntas correcto
                if (preguntaFCR.opciones) {
                    preguntaFCR.opciones = preguntaFCR.opciones.map(opcion => {
                        // Verificar si realmente tiene subpreguntas
                        const tieneSubpreguntas = opcion.subpreguntas && 
                                                  Array.isArray(opcion.subpreguntas) && 
                                                  opcion.subpreguntas.length > 0;
                        
                        // Establecer el flag correctamente
                        opcion.tiene_subpreguntas = tieneSubpreguntas;
                        
                        console.log(`📋 Opción "${opcion.opcion}": tiene_subpreguntas=${tieneSubpreguntas}, subpreguntas=${opcion.subpreguntas?.length || 0}`);
                        
                        return opcion;
                    });
                }
                
                this.preguntaFCRPrincipal = preguntaFCR;
                console.log('✅ Pregunta FCR cargada con opciones procesadas:', preguntaFCR);
            } else {
                console.warn('⚠️ No se encontró pregunta FCR configurada');
                this.preguntaFCRPrincipal = null;
            }
        } else {
            console.error('Error cargando preguntas FCR');
            this.preguntaFCRPrincipal = null;
        }
    } catch (error) {
        console.error('Error:', error);
        this.preguntaFCRPrincipal = null;
    } finally {
        this.cargandoPreguntaFCR = false;
    }
},
    }
}

</script>

<style scoped>
/* Estilos base del calificador */
.calificador-container {
    min-height: 100vh;
    background: linear-gradient(135deg, #ffffff 0%, #ffffffeb  100%);
    padding: 2rem;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Vista Selección */
.vista-seleccion {
    text-align: center;
    color: rgb(0, 0, 0);
    max-width: 1200px;
    width: 100%;
    height: 650px;
}

.header-info {
    margin-bottom: 1rem;
}

.calificador-titulo {
    font-size: clamp(2.5rem, 6vw, 4rem);
    margin-bottom: 1rem;
    font-weight: 700;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}

.ubicacion-info {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: rgb(0 0 0 / 4%);
    padding: 0.75rem 1.5rem;
    border-radius: 50px;
    backdrop-filter: blur(10px);
    font-size: 1.1rem;
}

.caritas-wrapper {
    margin: 1rem auto;
}

.caritas {
    display: flex;
    justify-content: center;
    align-items: center;
    flex-wrap: wrap;
}

.carita-group {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin: 1rem;
}

.carita {
    width: 240px;
    height: 240px;
    border-radius: 50%;
    background: #fff;
    border: 0px solid #fff;
    box-shadow: 0 8px 32px rgba(0,0,0,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-bottom: 1.5rem;
    outline: none; /* 🔥 Eliminar outline por defecto */
}

.carita:focus {
    outline: none; /* 🔥 Sin outline al hacer focus */
    box-shadow: 0 8px 32px rgba(0,0,0,0.2); /* Mantener sombra normal */
}

.carita:active {
    outline: none; /* 🔥 Sin outline al hacer click */
    transform: scale(0.98); /* Efecto de presión suave */
}

.carita:hover {
    transform: scale(1.1) rotate(5deg);
    box-shadow: 0 12px 40px rgba(0,0,0,0.3);
}

.carita svg {
    width: 90%;
    height: 90%;
}

.carita-label {
    font-size: 1.5rem;
    font-weight: 600;
    color: rgb(0, 0, 0);
    text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
    cursor: pointer;
    transition: color 0.3s ease;
}

.carita-label:hover {
    color: #f0f0f0;
}

.info-adicional {
    margin-top: 2rem;
    font-size: 1.2rem;
    opacity: 0.9;
}

/* Estilos para modales */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.modal-container {
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    max-width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    animation: slideUp 0.3s ease;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

/* Modal de cuestionario */
.cuestionario-content {
    width: 800px;
    max-width: 95vw;
}

.cuestionario-header {
    padding: 1.5rem 2rem;
    background: linear-gradient(135deg, #4f46e5, #7c3aed);
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-radius: 20px 20px 0 0;
}

.progreso-info {
    flex: 1;
}

.progreso {
    margin-bottom: 0.5rem;
}

.progreso-bar {
    width: 100%;
    height: 8px;
    background: rgba(255, 255, 255, 0.3);
    border-radius: 4px;
    overflow: hidden;
}

.progreso-fill {
    height: 100%;
    background: white;
    border-radius: 4px;
    transition: width 0.5s ease;
}

.progreso-texto {
    font-size: 0.9rem;
    opacity: 0.9;
}

.nivel-info {
    margin-top: 0.5rem;
}

.nivel-badge {
    background: rgba(255, 255, 255, 0.2);
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 500;
}

.btn-cerrar-modal {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.3s ease;
}

.btn-cerrar-modal:hover {
    background: rgba(255, 255, 255, 0.3);
}

.pregunta-actual {
    padding: 2rem 2rem;
}

.pregunta-header {
    text-align: center;
}

.pregunta-texto {
    font-size: 1.5rem;
    color: #1f2937;
    margin-bottom: 0.5rem;
    line-height: 1.4;
}

.tipo-pregunta {
    color: #6b7280;
    font-size: 0.9rem;
    font-style: italic;
}

.opciones-container {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    max-width: 600px;
    margin: 0 auto;
}

.opcion-item {
    display: flex;
    align-items: center;
    padding: 1.25rem 1.5rem;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    background: #fafafa;
}

.opcion-item:hover {
    border-color: #4f46e5;
    background: #f8fafc;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.1);
}

.opcion-item.seleccionada {
    border-color: #4f46e5 !important;
    background: #eef2ff !important;
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.15) !important;
    transform: translateY(-2px) !important;
}

.opcion-radio, .opcion-checkbox {
    margin-right: 1rem;
    flex-shrink: 0;
}

.radio-circle {
    width: 24px;
    height: 24px;
    border: 2px solid #d1d5db;
    border-radius: 50%;
    position: relative;
    transition: all 0.3s ease;
}

.radio-circle.activo {
    border-color: #4f46e5 !important;
    background: #4f46e5 !important;
}

.radio-circle.activo::after {
    content: '' !important;
    position: absolute !important;
    top: 50% !important;
    left: 50% !important;
    transform: translate(-50%, -50%) !important;
    width: 8px !important;
    height: 8px !important;
    background: white !important;
    border-radius: 50% !important;
}

.checkbox-square {
    width: 24px;
    height: 24px;
    border: 2px solid #d1d5db;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.checkbox-square.activo {
    border-color: #4f46e5;
    background: #4f46e5;
    color: white;
}

.opcion-texto {
    flex: 1;
    font-size: 1.1rem;
    color: #374151;
    font-weight: 500;
}

.texto-libre-container {
    max-width: 600px;
    margin: 2% auto 0 auto;
}

.texto-libre-input {
    width: 100%;
    padding: 1.25rem;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    font-size: 1rem;
    resize: vertical;
    transition: border-color 0.3s ease;
    background: #fafafa;
    font-family: inherit;
}

.texto-libre-input:focus {
    outline: none;
    border-color: #4f46e5;
    background: white;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.caracteres-info {
    text-align: right;
    font-size: 0.8rem;
    color: #6b7280;
    margin-top: 0.5rem;
}

.error-validacion {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #dc2626;
    padding: 1rem 1.25rem;
    border-radius: 8px;
    margin-top: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

.navegacion-modal {
    display: flex;
    justify-content: space-evenly;
    align-items: center;
    padding: 2rem;
    background: #f8fafc;
    border-top: 1px solid #e5e7eb;
    border-radius: 0 0 20px 20px;
}

.btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-secondary {
    background: #6b7280;
    color: white;
}

.btn-secondary:hover:not(:disabled) {
    background: #4b5563;
}

.btn-primary {
    background: #4f46e5;
    color: white;
}

.btn-primary:hover:not(:disabled) {
    background: #4338ca;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
}

/* Modal final de agradecimiento */
.final-modal {
    width: 500px;
    max-width: 90vw;
}

.final-content {
    padding: 3rem 3rem;
    text-align: center;
}

.final-icon {
    font-size: 4rem;
    margin-bottom: 1.5rem;
    animation: bounce 1s ease;
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% {
        transform: translateY(0);
    }
    40% {
        transform: translateY(-10px);
    }
    60% {
        transform: translateY(-5px);
    }
}

.final-titulo {
    font-size: 2rem;
    color: #059669;
    margin-bottom: 1rem;
    font-weight: 700;
}

.final-texto {
    color: #6b7280;
    font-size: 1.1rem;
    margin-bottom: 2rem;
}

.final-datos {
    background: #f8fafc;
    padding: 1.5rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    text-align: left;
}

.dato-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
}

.dato-item:last-child {
    margin-bottom: 0;
}

.dato-item i {
    width: 35px;
    height: 35px;
    background: #4f46e5;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.dato-item strong {
    display: block;
    color: #1f2937;
    margin-bottom: 0.25rem;
    font-size: 0.9rem;
}

.dato-item p {
    color: #6b7280;
    margin: 0;
    font-size: 0.9rem;
}

/* Barra de cierre automático */
.cierre-automatico {
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e5e7eb;
}

.tiempo-restante {
    color: #6b7280;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

.barra-progreso {
    width: 100%;
    height: 6px;
    background: #e5e7eb;
    border-radius: 3px;
    overflow: hidden;
}

.barra-progreso-fill {
    height: 100%;
    background: linear-gradient(90deg, #10b981, #059669);
    border-radius: 3px;
    transition: width 1s linear;
}

/* Estados de carga y error */
.loading-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    background: #f8f9fa;
    color: #666;
}

.spinner-large {
    border: 4px solid #f3f3f3;
    border-top: 4px solid #007bff;
    border-radius: 50%;
    width: 50px;
    height: 50px;
    animation: spin 1s linear infinite;
    margin-bottom: 1rem;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.error-container {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    padding: 2rem;
}

.error-content {
    text-align: center;
    background: white;
    padding: 3rem;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
    max-width: 500px;
    width: 100%;
}

.error-icon {
    font-size: 4rem;
    color: #dc2626;
    margin-bottom: 1.5rem;
}

.error-content h2 {
    color: #1f2937;
    margin-bottom: 1rem;
    font-size: 1.8rem;
}

.error-content p {
    color: #6b7280;
    margin-bottom: 2rem;
    font-size: 1.1rem;
}

/* Estilos responsive */
@media (max-width: 768px) {
    .calificador-container {
        padding: 1rem;
    }
    
    .modal-container {
        margin: 1rem;
        max-width: calc(100% - 2rem);
    }
    
    .cuestionario-content {
        width: 100%;
    }
    
    .cuestionario-header,
    .pregunta-actual,
    .navegacion-modal {
        padding: 1.5rem 1rem;
    }
    
    .final-content {
        padding: 2rem 1.5rem;
    }
    
    .carita {
        width: 140px;
        height: 140px;
    }
    
    .carita-label {
        font-size: 1.2rem;
    }
    
    .pregunta-texto {
        font-size: 1.3rem;
    }
    
    .opcion-item {
        padding: 1rem 1.25rem;
    }
    
    .navegacion-modal {
        flex-direction: column;
        gap: 1rem;
    }
    
    .navegacion-modal .btn {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .carita {
        width: 120px;
        height: 120px;
    }
    
    .carita-label {
        font-size: 1rem;
    }
    
    .calificador-titulo {
        font-size: 2rem;
    }
    
    .ubicacion-info {
        font-size: 0.9rem;
        padding: 0.5rem 1rem;
    }
    
    .pregunta-texto {
        font-size: 1.1rem;
    }
    
    .opcion-texto {
        font-size: 1rem;
    }
}

/* ESTILOS PARA INDICADOR 0-10 */
.indicador-container {
    max-width: 600px;
    margin: 0 auto;
    padding: 2rem 0;
}

.indicador-header {
    margin-bottom: 2rem;
    text-align: center;
}

.indicador-labels {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.indicador-min,
.indicador-max {
    font-size: 1.2rem;
    font-weight: 600;
    color: #6b7280;
}

.indicador-value {
    font-size: 3rem;
    font-weight: 700;
    color: #4f46e5;
    min-width: 60px;
}

.indicador-track {
    position: relative;
    width: 100%;
    height: 12px;
    background: #e5e7eb;
    border-radius: 10px;
    cursor: pointer;
    margin: 2rem 0;
    user-select: none;
}

.indicador-progress {
    position: absolute;
    height: 100%;
    background: linear-gradient(90deg, #ef4444, #f59e0b, #10b981, #3b82f6);
    border-radius: 10px;
    transition: width 0.1s ease;
}

.indicador-thumb {
    position: absolute;
    top: 50%;
    transform: translate(-50%, -50%);
    cursor: grab;
    z-index: 10;
}

.indicador-thumb:active {
    cursor: grabbing;
}

.thumb-circle {
    width: 30px;
    height: 30px;
    background: white;
    border: 3px solid #4f46e5;
    border-radius: 50%;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
    transition: all 0.2s ease;
}

.indicador-thumb:hover .thumb-circle {
    transform: scale(1.1);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
}

.indicador-ticks {
    display: flex;
    justify-content: space-between;
    margin: 1rem 0 2rem;
}

.tick {
    font-size: 0.8rem;
    color: #9ca3af;
    font-weight: 500;
    transition: all 0.3s ease;
    position: relative;
    width: 20px;
    text-align: center;
}

.tick::before {
    content: '';
    position: absolute;
    top: -8px;
    left: 50%;
    transform: translateX(-50%);
    width: 2px;
    height: 6px;
    background: currentColor;
    border-radius: 1px;
}

.tick.active {
    color: #4f46e5;
    font-weight: 600;
}

.tick.active::before {
    height: 10px;
    background: #4f46e5;
}

.indicador-emojis {
    display: flex;
    justify-content: space-between;
    margin-top: 2rem;
    padding: 1rem;
    background: #f8fafc;
    border-radius: 12px;
}

.emoji-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    opacity: 0.5;
    transition: all 0.3s ease;
    flex: 1;
}

.emoji-item.active {
    opacity: 1;
    transform: scale(1.1);
}

.emoji {
    font-size: 2rem;
    transition: all 0.3s ease;
}

.emoji-item.active .emoji {
    transform: scale(1.2);
}

.emoji-label {
    font-size: 0.75rem;
    color: #6b7280;
    font-weight: 500;
    text-align: center;
}

.emoji-item.active .emoji-label {
    color: #4f46e5;
    font-weight: 600;
}

/* Responsive */
@media (max-width: 768px) {
    .indicador-value {
        font-size: 2.5rem;
    }
    
    .emoji {
        font-size: 1.5rem;
    }
    
    .emoji-label {
        font-size: 0.7rem;
    }
    
    .thumb-circle {
        width: 25px;
        height: 25px;
    }
}

/* ESTILOS PARA OPCIÓN ÚNICA CON TEXTO LIBRE */
.opcion-item.con-texto-libre {
    border-left: 4px solid #10B981;
    background: #f8fafc;
}


.opcion-wrapper.con-texto-libre .opcion-item {
    border-bottom-left-radius: 0;
    border-bottom-right-radius: 0;
    margin-bottom: 0;
}

.texto-libre-opcion {
    margin-top: 0;
    padding: 1rem;
    background: white;
    border: 1px solid #e5e7eb;
    border-top: none;
    border-bottom-left-radius: 8px;
    border-bottom-right-radius: 8px;
    width: 100%;
}

.texto-libre-input-opcion {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.9rem;
    resize: vertical;
    transition: border-color 0.3s ease;
    font-family: inherit;
}

.texto-libre-input-opcion:focus {
    outline: none;
    border-color: #10B981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

.opcion-item.seleccionada.con-texto-libre {
    border-color: #10B981;
    background: #ecfdf5;
}

/* Estilos para subpreguntas */
.subpreguntas-container {
    margin-top: 2rem;
    padding: 1.5rem;
    background: #f8f9fa;
    border-radius: 12px;
    border-left: 4px solid #3B82F6;
}

.subpreguntas-header {
    margin-bottom: 1.5rem;
}

.subpreguntas-header h4 {
    color: #1F2937;
    margin-bottom: 0.5rem;
}

.subpreguntas-header p {
    color: #6B7280;
    font-size: 0.9rem;
}

.subpregunta-item {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 1rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

/* Estilos antiguos - ya no se usan para subpreguntas actuales */
.subpregunta-item .subpregunta-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.subpregunta-item .subpregunta-header h5 {
    color: #1F2937;
    margin: 0;
    flex: 1;
}

.subpregunta-tipo {
    background: #E5E7EB;
    color: #4B5563;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 500;
}

/* Estilos para subpreguntas mini */
.indicador-container.mini {
    margin-top: 1rem;
}

.texto-libre-container.mini {
    margin-top: 1rem;
}

.opciones-container.mini {
    margin-top: 1rem;
}

.opcion-item.mini {
    padding: 0.75rem;
}

.opcion-item.mini .opcion-texto {
    font-size: 0.9rem;
}
/* Estilos para subpreguntas */
.subpregunta-actual {
    padding: 1rem 0;
}

.progreso-subpreguntas {
    margin-bottom: 1.5rem;
}

.progreso-subpreguntas .progreso-bar {
    height: 6px;
    background: #E5E7EB;
    border-radius: 3px;
    overflow: hidden;
    margin-bottom: 0.5rem;
}

.progreso-subpreguntas .progreso-fill {
    height: 100%;
    background: #3B82F6;
    transition: width 0.3s ease;
}

.progreso-subpreguntas .progreso-texto {
    font-size: 0.875rem;
    color: #6B7280;
    text-align: center;
    display: block;
}

.subpregunta-header {
    text-align: center;
}

.subpregunta-texto {
    color: #1F2937;
    margin-bottom: 0.5rem;
    font-size: 1.25rem;
}

.tipo-subpregunta {
    background: #E5E7EB;
    color: #4B5563;
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.875rem;
    font-weight: 500;
}

.subpregunta-indicator {
    margin-left: 0.5rem;
    font-size: 0.875rem;
    opacity: 0.7;
}

/* Estilos para subpreguntas mini */
.indicador-container.mini {
    margin-top: 1rem;
}

.texto-libre-container.mini {
    margin-top: 1rem;
}

.opciones-container.mini {
    margin-top: 1rem;
}

.opcion-item.mini {
    padding: 0.75rem;
}

.opcion-item.mini .opcion-texto {
    font-size: 0.9rem;
}
/* Estilos para indicador de pregunta de rango */
.rango-indicator {
    background: #FEF3C7;
    color: #92400E;
    padding: 0.5rem 1rem;
    border-radius: 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    border: 1px solid #F59E0B;
    margin-left: 1rem;
}

/* Asegurar que las opciones se muestren correctamente */
.opciones-container {
    margin-top: 1.5rem;
}

.opcion-item {
    display: flex;
    align-items: flex-start;
    padding: 1rem;
    border: 2px solid #E5E7EB;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.opcion-item:hover {
    border-color: #3B82F6;
    background: #F8FAFC;
}


.opcion-texto {
    flex: 1;
    margin-left: 1rem;
    font-size: 1rem;
    line-height: 1.5;
}

/* 🔥 NUEVO: Estilos para indicadores alternativos (NPS y FCR iniciales) */
.indicadores-alt-wrapper {
    display: flex;
    flex-direction: column;
    gap: 2rem;
    max-width: 700px;
    margin: 0 auto;
}

.indicador-nps-wrapper h3 {
    text-align: center;
    margin-bottom: 2rem;
    color: #1F2937;
    font-size: 1.5rem;
}

.indicador-simple-container {
    margin-bottom: 2rem;
}

.btn-continuar-nps {
    width: 100%;
    padding: 1rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.btn-continuar-nps:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
}

.indicador-fcr-wrapper h3 {
    text-align: center;
    margin-bottom: 2rem;
    color: #1F2937;
    font-size: 1.5rem;
}

.fcr-options {
    display: flex;
    gap: 2rem;
    justify-content: center;
}

.fcr-option {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
    padding: 2rem;
    border: 3px solid #e5e7eb;
    border-radius: 16px;
    cursor: pointer;
    transition: all 0.3s ease;
    background: white;
    min-width: 180px;
}

.fcr-option:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
}

.fcr-option:first-child:hover {
    border-color: #10B981;
    background: #f0fdf4;
}

.fcr-option:last-child:hover {
    border-color: #EF4444;
    background: #fef2f2;
}

.fcr-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
}

.fcr-icon.bien {
    background: #D1FAE5;
    color: #10B981;
}

.fcr-icon.mal {
    background: #FEE2E2;
    color: #EF4444;
}

.fcr-option span {
    font-size: 1.1rem;
    font-weight: 600;
    color: #374151;
}

</style>