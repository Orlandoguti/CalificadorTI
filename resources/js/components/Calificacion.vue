<template>
    <div class="calificador-container">
        <!-- 🔥 NUEVO: Pantalla de carga con barra de progreso -->
        <div v-if="cargandoCalificador" class="loading-calificador-overlay">
            <div class="loading-calificador-content">
                <div class="loading-calificador-icon">
                    <i class="fas fa-clipboard-check"></i>
                </div>
                <h2 class="loading-calificador-title">Cargando Calificador</h2>
                <p class="loading-calificador-subtitle">{{ mensajeCarga }}</p>
                
                <!-- Barra de progreso -->
                <div class="progress-bar-container">
                    <div class="progress-bar-background">
                        <div class="progress-bar-fill" :style="{ width: progresoCarga + '%' }"></div>
                    </div>
                    <div class="progress-bar-text">{{ Math.round(progresoCarga) }}%</div>
                </div>
                
                <!-- Detalles de carga -->
                <div class="loading-details">
                    <div class="loading-detail-item" v-if="detallesCarga.niveles > 0">
                        <i class="fas fa-check-circle" v-if="detallesCarga.nivelesCargados >= detallesCarga.niveles"></i>
                        <i class="fas fa-spinner fa-spin" v-else></i>
                        <span>Niveles: {{ detallesCarga.nivelesCargados }}/{{ detallesCarga.niveles }}</span>
                    </div>
                    <div class="loading-detail-item" v-if="detallesCarga.preguntas > 0">
                        <i class="fas fa-check-circle" v-if="detallesCarga.preguntasCargadas >= detallesCarga.preguntas"></i>
                        <i class="fas fa-spinner fa-spin" v-else></i>
                        <span>Preguntas: {{ detallesCarga.preguntasCargadas }}/{{ detallesCarga.preguntas }}</span>
                    </div>
                    <div class="loading-detail-item" v-if="detallesCarga.subpreguntas > 0">
                        <i class="fas fa-check-circle" v-if="detallesCarga.subpreguntasCargadas >= detallesCarga.subpreguntas"></i>
                        <i class="fas fa-spinner fa-spin" v-else></i>
                        <span>Subpreguntas: {{ detallesCarga.subpreguntasCargadas }}/{{ detallesCarga.subpreguntas }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading mientras carga datos -->
        <div v-else-if="cargandoDatos" class="loading-container">
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
                        <span>{{ formatearTexto(areaSeleccionada.codigo + ' - ' + sedeNombre) }}</span>
                    </div>
                </div>
                
                <!-- 🔥 NUEVO: Mostrar solo indicadores permitidos según configuración del área -->
                <div v-if="areaSeleccionada.permite_csat" class="caritas-wrapper">
                    <div class="caritas">
                        <div v-for="nivel in nivelesCalificacion" :key="nivel.id" class="carita-group">      
                        <div class="carita" @click.stop="iniciarCuestionario(nivel)" :title="nivel.nombre">

                            <!-- Emoji normal -->
                            <span v-if="!nivel.emoji.includes('<svg') && !nivel.emoji.match(/\.(png|jpe?g|gif)$/i)" class="emoji" @contextmenu.prevent
                            @dragstart.prevent>
                            {{ nivel.emoji }}
                            </span>
                            <!-- SVG -->
                            <div v-else-if="nivel.emoji.includes('<svg')" class="emoji-svg" @contextmenu.prevent
                            @dragstart.prevent v-html="nivel.emoji"></div>
                            <!-- Imagen o GIF -->
                            <img v-else :src="`/imagen/csat/${nivel.emoji}`" class="emoji-img" @contextmenu.prevent
                            @dragstart.prevent />
                        </div>
                        <div class="carita-label" @click.stop="iniciarCuestionario(nivel)">
                            {{ nivel.nombre }}
                        </div>
                        </div>
                    </div>
                </div>
                
                <!-- 🔥 NUEVO: Indicadores alternativos si no tiene CSAT (sin NPS) -->
                <div v-else class="indicadores-alt-wrapper">
                    <!-- 🔥 NUEVO: Para áreas con solo FCR, mostrar manitas con la pregunta FCR -->
                    <div v-if="!areaSeleccionada.permite_csat && areaSeleccionada.permite_fcr" class="indicador-fcr-wrapper">
                        <div v-if="cargandoPreguntaFCR" style="text-align: center; padding: 2rem;">
                            <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #666;"></i>
                            <p>Cargando pregunta...</p>
                        </div>
                        <div v-else-if="preguntaFCRPrincipal">
                            <h2 style="color: #1F2937; text-align: center; margin-bottom: 2rem; font-size: 1.5rem;">
                                {{ preguntaFCRPrincipal.pregunta }}
                            </h2>
                            <div class="fcr-options">
                                <div v-for="opcion in preguntaFCRPrincipal.opciones" 
                                     :key="opcion.id" 
                                     class="fcr-option" 
                                     @click="iniciarConFCR(opcion.opcion === 'Sí', opcion, $event)">
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
    
    <!-- Indicador 0-10 (modal) -->
        <div v-if="preguntaActualData.tipo === 'indicador_0_10'" class="indicador-container">
            <div class="indicador-header">
                <div class="indicador-labels">
                    <span class="indicador-min">0</span>
                    <span class="indicador-value">{{ respuestas[preguntaActualData.id]?.valor ?? respuestaIndicadorValor }}</span>
                    <span class="indicador-max">10</span>
                </div>
            </div>
            
            <div class="indicador-track" @mousedown="iniciarArrastreIndicador($event)" @touchstart="iniciarArrastreIndicador($event)" @click="clickIndicador($event)">
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
                            @input="guardarTextoLibreOpcionRango()"
                                @focus="manejarFocoTexto"
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
                            
                            <!-- No permitir volver si la primera pregunta es un indicador 0-10 -->
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
            preguntaFCRPrincipal: null, // 🔥 NUEVO: Pregunta FCR principal desde BD
            cargandoPreguntaFCR: false, // 🔥 NUEVO: Estado de carga de pregunta FCR
            
            // Niveles de calificación
           nivelesCalificacion: [],
            
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
            cerrandoAgradecimientoEnCurso: false,
            
            // Timeout para cerrar modal si no hay interacción
            timeoutInactividad: null,
            // Milisegundos sin interacción antes de cerrar el modal (ej.: 15*1000 = 15 s, 60*1000 = 1 min)
            tiempoInactividad: 45 * 1000,

            // Datos para indicador 0-10
            respuestaIndicadorValor: 5, // Valor por defecto
            arrastrando: false,
            trackModal: null, // Referencia al track del indicador 0-10 en el modal
            
            respuestaFCR: null, // null, true (bien) o false (mal)
         
             // 🔥 NUEVO: Estados mejorados para subpreguntas
            modoSubpreguntas: false,
            subpreguntasActuales: [],
            subpreguntaIndex: 0,
            subpreguntasActivas: {},
            respuestasSubpreguntas: {},
        
        // Datos para opción única con texto libre - CORREGIDO
        textoLibreOpcion: '',
        opcionTextoLibreSeleccionada: null,
        respuestaUnica: null, // AGREGAR ESTA LÍNEA
        
        // 🔥 NUEVO: Variables para flujo secuencial de tipos de calificación
        tiposCalificacionSecuencia: [], // Orden de tipos: 'csat', 'fcr' (NPS deshabilitado)
        tipoCalificacionActual: null, // 'csat' | 'fcr'
        indiceTipoActual: 0, // Índice del tipo actual en la secuencia
        respuestasAcumuladas: {}, // Acumular todas las respuestas de todos los tipos
        respuestasSubpreguntasAcumuladas: [], // Acumular subpreguntas de todos los tipos
        respuestasRangosAcumuladas: [], // Acumular rangos de todos los tipos
        intentosPrecarga: 0, // Contador de intentos de precarga
        sincronizando: false, // Estado de sincronización
        
        // 🔥 NUEVO: Estados para carga inicial con progreso
        cargandoCalificador: false, // Mostrar pantalla de carga del calificador
        progresoCarga: 0, // Progreso de carga (0-100)
        mensajeCarga: 'Preparando calificador...', // Mensaje de estado
        detallesCarga: {
            niveles: 0,
            nivelesCargados: 0,
            preguntas: 0,
            preguntasCargadas: 0,
            subpreguntas: 0,
            subpreguntasCargadas: 0
        }
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
                // Resetear timeout de inactividad cuando se escribe texto libre
                this.resetearTimeoutInactividad();
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
    watch: {
        /**
         * Watcher para iniciar el timeout de inactividad cuando se abre el modal
         */
        mostrarCuestionario(nuevoValor) {
            if (nuevoValor) {
                // Modal abierto, iniciar timeout de inactividad después de que Vue actualice el DOM
                this.$nextTick(() => {
                    console.log('⏱️ Modal abierto, iniciando timeout de inactividad:', this.tiempoInactividad, 'ms');
                    this.iniciarTimeoutInactividad();
                });
            } else {
                // Modal cerrado, limpiar timeout
                this.limpiarTimeoutInactividad();
            }
        },
        /**
         * Al terminar una carga dentro del modal, volver a arrancar el conteo (sin bucle infinito).
         * Solo reprogramar en el callback cuando `cargando` evita perder el timer si la carga dura más que tiempoInactividad.
         */
        cargando(nuevo) {
            if (nuevo === false && this.mostrarCuestionario) {
                this.$nextTick(() => this.iniciarTimeoutInactividad());
            }
        }
    },
    async mounted() {
        // 🔥 BLOQUEO DE ZOOM: Prevenir zoom con gestos de pellizco
        this.prevenirZoom();
        
        // 🔥 NUEVO: Mostrar pantalla de carga del calificador
        this.cargandoCalificador = true;
        this.progresoCarga = 0;
        this.mensajeCarga = 'Iniciando calificador...';
        
        // Cargar niveles primero
        this.progresoCarga = 5;
        this.mensajeCarga = 'Cargando niveles...';
        await this.cargarNiveles();
        
        // Verificar si hay área seleccionada en localStorage
        const areaGuardada = localStorage.getItem('area_seleccionada');
        const sedeGuardada = localStorage.getItem('sede_seleccionada');
        
        if (!areaGuardada || !sedeGuardada) {
            // Si no hay datos, redirigir a áreas
            this.cargandoCalificador = false;
            this.$router.push('/areas');
            return;
        }
        
        // Cargar datos iniciales
        this.progresoCarga = 10;
        this.mensajeCarga = 'Cargando datos iniciales...';
        await this.cargarDatosIniciales();
        
        // 🔥 NUEVO: Cargar todas las preguntas desde caché local
        await this.cargarPreguntasDesdeCache();
        
        // Finalizar carga
        this.progresoCarga = 100;
        this.mensajeCarga = '¡Listo!';
        await new Promise(resolve => setTimeout(resolve, 500)); // Esperar medio segundo para mostrar 100%
        this.cargandoCalificador = false;
        
        this.debugFlujoPreguntas();
        
        // 🔥 NUEVO: Escuchar eventos de sincronización
        this.configurarEventosSincronizacion();
},
    beforeUnmount() {
        // Limpiar intervalo al destruir el componente
        if (this.intervalo) {
            clearInterval(this.intervalo);
        }
        // Limpiar timeout de inactividad
        this.limpiarTimeoutInactividad();
        // Limpiar eventos de zoom
        this.limpiarPrevencionZoom();
    },
    methods: {
        /**
         * 🔥 BLOQUEO DE ZOOM: Prevenir zoom con gestos de pellizco y doble toque
         */
        prevenirZoom() {
            // Prevenir zoom con gestos de pellizco
            let lastTouchEnd = 0;
            const preventZoom = (e) => {
                const now = Date.now();
                if (now - lastTouchEnd <= 300) {
                    e.preventDefault();
                }
                lastTouchEnd = now;
            };

            // Prevenir zoom con doble toque
            const preventDoubleTapZoom = (e) => {
                if (e.touches.length > 1) {
                    e.preventDefault();
                }
            };

            // Prevenir zoom con rueda del mouse (Ctrl + Scroll)
            const preventWheelZoom = (e) => {
                if (e.ctrlKey) {
                    e.preventDefault();
                }
            };

            // Agregar event listeners
            document.addEventListener('touchend', preventZoom, { passive: false });
            document.addEventListener('touchstart', preventDoubleTapZoom, { passive: false });
            document.addEventListener('wheel', preventWheelZoom, { passive: false });
            document.addEventListener('gesturestart', (e) => e.preventDefault());
            document.addEventListener('gesturechange', (e) => e.preventDefault());
            document.addEventListener('gestureend', (e) => e.preventDefault());

            // Guardar referencias para poder limpiarlas después
            this._zoomPreventionHandlers = {
                touchend: preventZoom,
                touchstart: preventDoubleTapZoom,
                wheel: preventWheelZoom
            };
        },

        /**
         * 🔥 BLOQUEO DE ZOOM: Limpiar eventos de prevención de zoom
         */
        limpiarPrevencionZoom() {
            if (this._zoomPreventionHandlers) {
                document.removeEventListener('touchend', this._zoomPreventionHandlers.touchend);
                document.removeEventListener('touchstart', this._zoomPreventionHandlers.touchstart);
                document.removeEventListener('wheel', this._zoomPreventionHandlers.wheel);
                this._zoomPreventionHandlers = null;
            }
        },
        /**
         * 🔥 NUEVO: Cargar todas las preguntas desde caché local con barra de progreso
         */
        async cargarPreguntasDesdeCache() {
            try {
                if (!this.areaSeleccionada || !this.nivelesCalificacion || this.nivelesCalificacion.length === 0) {
                    console.log('⚠️ No se pueden cargar preguntas: faltan datos necesarios');
                    return;
                }

                const sedeGuardada = localStorage.getItem('sede_seleccionada');
                let sedeId = null;
                
                if (this.areaSeleccionada.sede_id) {
                    sedeId = this.areaSeleccionada.sede_id;
                } else if (sedeGuardada) {
                    try {
                        const sedeResponse = await fetch(`/api/sedes/buscar?nombre=${encodeURIComponent(sedeGuardada)}`);
                        if (sedeResponse.ok) {
                            const sedeData = await sedeResponse.json();
                            sedeId = sedeData.id;
                        }
                    } catch (error) {
                        console.warn('⚠️ No se pudo obtener sede_id:', error);
                    }
                }

                // Inicializar contadores
                this.detallesCarga.niveles = this.nivelesCalificacion.length + 1; // +1 para FCR
                this.detallesCarga.nivelesCargados = 0;
                this.detallesCarga.preguntas = 0;
                this.detallesCarga.preguntasCargadas = 0;
                this.detallesCarga.subpreguntas = 0;
                this.detallesCarga.subpreguntasCargadas = 0;

                this.progresoCarga = 20;
                this.mensajeCarga = 'Cargando preguntas CSAT...';

                // Cargar preguntas de cada nivel desde caché
                let totalPreguntas = 0;

                for (let i = 0; i < this.nivelesCalificacion.length; i++) {
                    const nivel = this.nivelesCalificacion[i];
                    const cacheKey = `preguntas_${this.areaSeleccionada.id}_${nivel.id}_${sedeId || 'sin_sede'}`;
                    const precached = localStorage.getItem(`precache_${cacheKey}`);
                    
                    if (precached) {
                        const cacheData = JSON.parse(precached);
                        const preguntas = cacheData.data || [];
                        totalPreguntas += preguntas.length;
                        
                        // Contar subpreguntas
                        for (const pregunta of preguntas) {
                            if (pregunta.opciones && Array.isArray(pregunta.opciones)) {
                                for (const opcion of pregunta.opciones) {
                                    if (opcion.tiene_subpreguntas && opcion.id) {
                                        const subpreguntasCache = localStorage.getItem(`precache_subpreguntas_${opcion.id}`);
                                        if (subpreguntasCache) {
                                            const subpreguntasData = JSON.parse(subpreguntasCache);
                                            this.detallesCarga.subpreguntas += (subpreguntasData.data || []).length;
                                        }
                                    }
                                }
                            }
                        }
                    }
                    
                    this.detallesCarga.nivelesCargados++;
                    this.progresoCarga = 20 + (this.detallesCarga.nivelesCargados / this.detallesCarga.niveles) * 40;
                    this.mensajeCarga = `Cargando nivel ${nivel.nombre}...`;
                    await new Promise(resolve => setTimeout(resolve, 100)); // Pequeña pausa para mostrar progreso
                }

                // Cargar FCR
                this.mensajeCarga = 'Cargando preguntas FCR...';
                const fcrCacheKey = `preguntas_fcr_todas_${this.areaSeleccionada.id}_${sedeId || 'sin_sede'}`;
                const fcrPrecached = localStorage.getItem(`precache_${fcrCacheKey}`);
                
                if (fcrPrecached) {
                    const fcrCacheData = JSON.parse(fcrPrecached);
                    const fcrPreguntas = fcrCacheData.data || [];
                    const preguntaFCR = fcrPreguntas.find(p => p.tipo_pregunta === 'fcr' && p.is_active);
                    
                    if (preguntaFCR) {
                        totalPreguntas += 1;
                        // Contar subpreguntas de FCR
                        if (preguntaFCR.opciones && Array.isArray(preguntaFCR.opciones)) {
                            for (const opcion of preguntaFCR.opciones) {
                                if (opcion.tiene_subpreguntas && opcion.id) {
                                    const subpreguntasCache = localStorage.getItem(`precache_subpreguntas_${opcion.id}`);
                                    if (subpreguntasCache) {
                                        const subpreguntasData = JSON.parse(subpreguntasCache);
                                        this.detallesCarga.subpreguntas += (subpreguntasData.data || []).length;
                                    }
                                }
                            }
                        }
                    }
                }
                
                this.detallesCarga.nivelesCargados = this.detallesCarga.niveles;
                this.detallesCarga.preguntas = totalPreguntas;
                this.detallesCarga.preguntasCargadas = totalPreguntas;
                this.detallesCarga.subpreguntasCargadas = this.detallesCarga.subpreguntas;
                
                this.progresoCarga = 80;
                this.mensajeCarga = 'Verificando subpreguntas...';
                await new Promise(resolve => setTimeout(resolve, 200));
                
                this.progresoCarga = 95;
                this.mensajeCarga = 'Finalizando carga...';
                await new Promise(resolve => setTimeout(resolve, 200));
                
                console.log('✅ Preguntas cargadas desde caché:', {
                    niveles: this.detallesCarga.nivelesCargados,
                    preguntas: this.detallesCarga.preguntasCargadas,
                    subpreguntas: this.detallesCarga.subpreguntasCargadas
                });
            } catch (error) {
                console.error('❌ Error cargando preguntas desde caché:', error);
                this.mensajeCarga = 'Error al cargar. Continuando...';
            }
        },

        async cargarNiveles() {
            try {
                const res = await axios.get('/api/niveles-calificacion');
                this.nivelesCalificacion = res.data;
            } catch (error) {
                console.error("Error cargando niveles:", error);
            }
        },
        capitalizarPalabras(texto) {
            return texto
            .toLowerCase()
            .split(' ')
            .map(p => p.charAt(0).toUpperCase() + p.slice(1))
            .join(' ');
        },
        formatearTexto(texto) {
            if (!texto) return '';
            return texto
            .split(' - ')
            .map(p => this.capitalizarPalabras(p))
            .join(' - ');
        },
        /**
         * 🔥 NUEVO: Determinar la secuencia de tipos de calificación según el área
         */
        determinarSecuenciaTipos() {
            const tipos = [];
            
            if (this.areaSeleccionada?.permite_csat) {
                tipos.push('csat');
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
            
            console.log('📋 Secuencia de tipos determinada:', {
                secuencia: this.tiposCalificacionSecuencia,
                cantidad: this.tiposCalificacionSecuencia.length,
                tipoInicial: this.tipoCalificacionActual,
                permite_csat: this.areaSeleccionada?.permite_csat,
                permite_fcr: this.areaSeleccionada?.permite_fcr
            });
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

                // 🔥 NUEVO: Usar función helper con soporte offline
                const result = await this.enviarCalificacionConOffline('/api/calificaciones/completa', calificacionData);
                
                if (result && result.offline) {
                    console.log('📦 Calificación guardada offline, se sincronizará cuando haya conexión');
                    return { offline: true, success: true };
                }
                
                console.log('✅ Calificación tipo individual guardada exitosamente:', this.tipoCalificacionActual, result);
                return result;

            } catch (error) {
                console.error('❌ Error guardando calificación tipo individual:', error);
                // Si está offline, intentar guardar en cola
                if (!navigator.onLine && window.offlineHandler) {
                    const payload = this.payloadCalificacionParaOffline(calificacionData);
                    window.offlineHandler.addToSyncQueue(
                        '/api/calificaciones/completa',
                        'POST',
                        payload,
                        {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                        }
                    );
                    return { offline: true, success: true };
                }
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
            
            // 🔥 NUEVO: Limpiar todas las respuestas antes de iniciar cualquier tipo de calificación
            // Esto asegura que no haya valores residuales que interfieran
            this.limpiarRespuestasParaNuevaCalificacion();
            
            if (tipo === 'csat') {
                // Para CSAT, continuar con el flujo normal de iniciarCuestionario
                // pero sin reiniciar todo, solo cargar las preguntas
                console.log('📋 Iniciando CSAT desde secuencia...');
                // El flujo normal de iniciarCuestionario continuará después
                // No hacer return aquí, dejar que continúe
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
                    
                    // 🔥 NUEVO: Limpiar todas las respuestas antes de abrir el modal FCR
                    this.limpiarRespuestasParaNuevaCalificacion();
                    
                    // Cargar la pregunta FCR en el array de preguntas para mostrarla en el modal
                    this.preguntas = [this.preguntaFCRPrincipal];
                    this.preguntaActual = 0;
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

                // 🔥 NUEVO: Usar función helper con soporte offline
                const result = await this.enviarCalificacionConOffline('/api/calificaciones/completa', calificacionData);
                
                if (result && result.offline) {
                    console.log('📦 Calificación guardada offline, se sincronizará cuando haya conexión');
                    return;
                }
                
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
                            console.log('✅ Permite FCR:', this.areaSeleccionada.permite_fcr);
                            
                            // 🔥 NUEVO: Determinar secuencia de tipos de calificación
                            this.determinarSecuenciaTipos();
                            
                            // Debug específico para FCR
                            if (!this.areaSeleccionada.permite_csat && this.areaSeleccionada.permite_fcr) {
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

                // 🔥 NUEVO: Precargar todas las preguntas en segundo plano para mejorar rendimiento
                this.precargarTodasLasPreguntas();

            } catch (error) {
                console.error('Error cargando datos iniciales:', error);
            } finally {
                this.cargandoDatos = false;
            }
        },

        async iniciarCuestionario(nivel) {
            this.nivelSeleccionado = nivel;
            this.cargando = true;
            
            // 🔥 NUEVO: Si hay una secuencia de tipos activa, iniciar desde el primer tipo
            if (this.tiposCalificacionSecuencia.length > 0) {
                // Asegurar que estamos en el primer tipo de la secuencia
                this.indiceTipoActual = 0;
                this.tipoCalificacionActual = this.tiposCalificacionSecuencia[0];
                console.log('🔄 Iniciando calificación desde el primer tipo de la secuencia:', this.tipoCalificacionActual);
                
                // Si el primer tipo es CSAT, limpiar respuestas y continuar con el flujo normal
                if (this.tipoCalificacionActual === 'csat') {
                    // Para CSAT, limpiar respuestas y continuar con el flujo normal de iniciarCuestionario
                    this.limpiarRespuestasParaNuevaCalificacion();
                    console.log('📋 CSAT: Continuando con flujo normal de carga de preguntas...');
                } else {
                    // Para FCR, usar iniciarTipoCalificacion
                    await this.iniciarTipoCalificacion(this.tipoCalificacionActual);
                    return;
                }
            }
            
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
                
                // 🔥 NUEVO: Usar función helper con soporte offline
                const cacheKey = `preguntas_${this.areaSeleccionada.id}_${nivel.id}_${sedeId || 'sin_sede'}`;
                const todasLasPreguntas = await this.cargarDatosConOffline(url, cacheKey);
                
                if (todasLasPreguntas) {
                    
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
                        // 🔥 NUEVO: Limpiar todas las respuestas antes de abrir el modal
                        this.limpiarRespuestasParaNuevaCalificacion();
                        
                        this.mostrarCuestionario = true;
                        this.preguntaActual = 0;
                        this.respuestaIndicadorValor = 5;
                        this.respuestaFCR = null;
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
    
    // Resetear timeout de inactividad
    this.resetearTimeoutInactividad();
},

        toggleOpcionMultiple(opcionId) {
            const current = this.respuestaMultiple;
            if (current.includes(opcionId)) {
                this.respuestaMultiple = current.filter(id => id !== opcionId);
            } else {
                this.respuestaMultiple = [...current, opcionId];
            }
            this.errorValidacion = '';
            
            // Resetear timeout de inactividad
            this.resetearTimeoutInactividad();
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
                
                // Eliminar la pregunta del indicador del array antes de cargar la pregunta de rango
                const indicadorIndex = this.preguntas.findIndex(p => p.id === preguntaIndicadorId);
                if (indicadorIndex !== -1) {
                    this.preguntas.splice(indicadorIndex, 1);
                    console.log('🗑️ Pregunta indicador eliminada del flujo antes de cargar pregunta de rango');
                    if (this.preguntaActual >= indicadorIndex) {
                        this.preguntaActual = Math.max(0, this.preguntaActual - 1);
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
                    console.log('📭 No hay pregunta de rango para este valor');
                    this.cargando = false;
                    console.log('📭 No hay pregunta de rango, continuando normalmente');
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
        
    }

    // 🔥 CORRECCIÓN FCR: Verificar si es pregunta FCR y manejar según la respuesta
    if (this.tipoCalificacionActual === 'fcr' && preguntaActual.tipo_pregunta === 'fcr') {
        const opcionSeleccionada = preguntaActual.opciones.find(
            op => op.id == respuesta.opcion_seleccionada_id
        );
        
        if (opcionSeleccionada) {
            const normOpc = (s) => (s || '').trim().normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
            const esSí = normOpc(opcionSeleccionada.opcion) === 'si';
            const esNo = normOpc(opcionSeleccionada.opcion) === 'no';
            
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
                
                // 🔥 CORRECCIÓN: Guardar respuesta FCR antes de cargar subpreguntas
                if (this.preguntaFCRPrincipal && opcionSeleccionada) {
                    this.respuestas[this.preguntaFCRPrincipal.id] = {
                        opcion_seleccionada_id: opcionSeleccionada.id,
                        texto_libre: ''
                    };
                }
                
                // 🔥 CORRECCIÓN: Usar cargarSubpreguntasFCRParaOpcion para FCR (igual que cuando hay otros tipos)
                this.mostrarCuestionario = true;
                await this.$nextTick();
                
                await this.cargarSubpreguntasFCRParaOpcion(opcionSeleccionada.id, true);
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
            } else if (esNo) {
                // 🔥 CORRECCIÓN: Verificar subpreguntas para "No" de manera más completa
                const tieneSubpreguntasNo = opcionSeleccionada.tiene_subpreguntas || 
                                           (opcionSeleccionada.subpreguntas && opcionSeleccionada.subpreguntas.length > 0);
                
                console.log('🔍 FCR procesarPreguntaNormal - Verificando subpreguntas para "No":', {
                    esNo,
                    tiene_subpreguntas: opcionSeleccionada.tiene_subpreguntas,
                    subpreguntas: opcionSeleccionada.subpreguntas,
                    subpreguntasLength: opcionSeleccionada.subpreguntas?.length || 0,
                    tieneSubpreguntasNo,
                    opcionId: opcionSeleccionada.id
                });
                
                if (tieneSubpreguntasNo) {
                    // Si selecciona "No" y tiene subpreguntas, cargarlas
                    console.log('❌ FCR: NO se resolvió, cargando subpreguntas...');
                    
                    // 🔥 CORRECCIÓN: Guardar respuesta FCR antes de cargar subpreguntas
                    if (this.preguntaFCRPrincipal && opcionSeleccionada) {
                        this.respuestas[this.preguntaFCRPrincipal.id] = {
                            opcion_seleccionada_id: opcionSeleccionada.id,
                            texto_libre: ''
                        };
                    }
                    
                    // 🔥 CORRECCIÓN: Usar cargarSubpreguntasFCRParaOpcion para FCR (igual que cuando hay otros tipos)
                    this.mostrarCuestionario = true;
                    await this.$nextTick();
                    
                    await this.cargarSubpreguntasFCRParaOpcion(opcionSeleccionada.id, false);
                    return;
                } else {
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

    // Si no hay subpreguntas, continuar normalmente
    this.preguntaActual++;
    await this.verificarFinalizacion();
},

/**
 * Iniciar modo subpreguntas
 */
async iniciarModoSubpreguntas(opcionId) {
    this.cargando = true;
    
    try {
        console.log('🔍 Cargando subpreguntas para opción ID:', opcionId);
        
        // 🔥 NUEVO: Usar función helper con soporte offline
        const cacheKey = `subpreguntas_${opcionId}`;
        let subpreguntas = await this.cargarDatosConOffline(`/api/subpreguntas/${opcionId}`, cacheKey);
        
        if (subpreguntas) {
            
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
                this.cargando = false;
                this.mostrarCuestionario = true;
                
                // Forzar actualización de Vue
                await this.$nextTick();
                
                console.log('✅ Modo subpreguntas activado:', this.subpreguntasActuales.length);
                console.log('✅ mostrarCuestionario:', this.mostrarCuestionario);
                console.log('✅ modoSubpreguntas:', this.modoSubpreguntas);
            } else {
                console.log('📭 No hay subpreguntas, continuando normalmente');
                this.cargando = false;
                
                // Si no hay subpreguntas y es FCR, verificar si hay más tipos
                if (this.tipoCalificacionActual === 'fcr') {
                    const hayMasTipos = this.tiposCalificacionSecuencia.length > 1 && 
                                        this.indiceTipoActual < this.tiposCalificacionSecuencia.length - 1;
                    if (hayMasTipos) {
                        await this.avanzarAlSiguienteTipo();
                    } else {
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
                    this.preguntaActual++;
                    await this.verificarFinalizacion();
                }
            }
        } else {
            console.error('❌ Error al cargar subpreguntas, respuesta no OK:', response.status);
            this.cargando = false;
            
            // Si es FCR y hay error, verificar si hay más tipos
            if (this.tipoCalificacionActual === 'fcr') {
                const hayMasTipos = this.tiposCalificacionSecuencia.length > 1 && 
                                    this.indiceTipoActual < this.tiposCalificacionSecuencia.length - 1;
                if (hayMasTipos) {
                    await this.avanzarAlSiguienteTipo();
                } else {
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
                this.preguntaActual++;
                await this.verificarFinalizacion();
            }
        }
    } catch (error) {
        console.error('❌ Error iniciando modo subpreguntas:', error);
        this.cargando = false;
        
        // Si es FCR y hay error, verificar si hay más tipos
        if (this.tipoCalificacionActual === 'fcr') {
            const hayMasTipos = this.tiposCalificacionSecuencia.length > 1 && 
                                this.indiceTipoActual < this.tiposCalificacionSecuencia.length - 1;
            if (hayMasTipos) {
                await this.avanzarAlSiguienteTipo();
            } else {
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
            this.preguntaActual++;
            await this.verificarFinalizacion();
        }
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
        // Verificar si hay más tipos: el índice actual debe ser menor que el último índice
        const hayMasTipos = this.tiposCalificacionSecuencia.length > 1 && 
                            this.indiceTipoActual < (this.tiposCalificacionSecuencia.length - 1);
        
        console.log('🔍 Verificando finalización:', {
            preguntaActual: this.preguntaActual,
            totalPreguntas: this.preguntas.length,
            tiposSecuencia: this.tiposCalificacionSecuencia,
            indiceActual: this.indiceTipoActual,
            tipoActual: this.tipoCalificacionActual,
            hayMasTipos: hayMasTipos,
            longitudSecuencia: this.tiposCalificacionSecuencia.length
        });
        
        if (hayMasTipos) {
            console.log('➡️ Tipo actual completado, avanzando al siguiente tipo...');
            // avanzarAlSiguienteTipo() ya guarda el tipo actual antes de avanzar
            await this.avanzarAlSiguienteTipo();
            return;
        }
        
        // Es el último tipo, guardar y finalizar
        console.log('🎉 Finalizando último tipo de calificación');
        
        // Si hay múltiples tipos en la secuencia, guardar individualmente
        // Si solo hay un tipo, usar el método completo
        if (this.tiposCalificacionSecuencia.length > 1) {
            // Si es flujo secuencial (múltiples tipos), guardar el último tipo individualmente
            console.log('💾 Guardando último tipo de secuencia múltiple...');
            await this.guardarCalificacionTipoIndividual();
        } else {
            // Si solo hay un tipo, usar el método normal de guardado completo
            console.log('💾 Guardando calificación única...');
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

        // 🔥 NUEVO: Usar función helper con soporte offline
        const result = await this.enviarCalificacionConOffline('/api/calificaciones/completa', calificacionData);
        
        if (result && result.offline) {
            console.log('📦 Calificación guardada offline, se sincronizará cuando haya conexión');
            return;
        }
        
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
 * 🔥 NUEVO: Función helper para cargar datos con soporte offline (preguntas, subpreguntas, etc.)
 * Con conexión: siempre intentar API primero y actualizar caché (evita servir precache vacío/obsoleto).
 * Sin conexión: precache y luego cache de respaldo.
 */
async cargarDatosConOffline(url, cacheKey) {
    const readStoredData = (prefix) => {
        try {
            const raw = localStorage.getItem(`${prefix}_${cacheKey}`);
            if (!raw) return undefined;
            const parsed = JSON.parse(raw);
            return parsed && Object.prototype.hasOwnProperty.call(parsed, 'data') ? parsed.data : undefined;
        } catch (e) {
            console.warn(`⚠️ Error leyendo ${prefix}_${cacheKey}:`, e);
            return undefined;
        }
    };

    const persistData = (data) => {
        const cacheData = {
            data,
            url,
            timestamp: new Date().toISOString()
        };
        const json = JSON.stringify(cacheData);
        localStorage.setItem(`cache_${cacheKey}`, json);
        localStorage.setItem(`precache_${cacheKey}`, json);
        console.log(`✅ Datos guardados en cache/precache: ${cacheKey}`);
    };

    const fetchAndCache = async () => {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 8000); // Evita fetch colgado en tablets al reanudar
        let response;
        try {
            response = await fetch(url, { credentials: 'include', signal: controller.signal });
        } finally {
            clearTimeout(timeoutId);
        }
        if (!response.ok) {
            throw new Error(`Error ${response.status}: ${response.statusText}`);
        }
        const data = await response.json();
        try {
            persistData(data);
        } catch (e) {
            console.warn('⚠️ Error guardando en cache:', e);
        }
        return data;
    };

    const fallbackOffline = (originalError) => {
        const fromPrecache = readStoredData('precache');
        if (fromPrecache !== undefined && fromPrecache !== null) {
            console.log(`⚡ Offline: datos desde precache: ${cacheKey}`);
            return fromPrecache;
        }
        const fromCache = readStoredData('cache');
        if (fromCache !== undefined && fromCache !== null) {
            console.log(`⚡ Offline: datos desde cache: ${cacheKey}`);
            return fromCache;
        }
        console.warn(`⚠️ No hay datos en cache para: ${cacheKey}`);
        throw originalError || new Error(`No hay datos en cache para ${cacheKey}`);
    };

    const fromPrecache = readStoredData('precache');
    const fromCache = readStoredData('cache');
    const hasLocalData = (fromPrecache !== undefined && fromPrecache !== null) || (fromCache !== undefined && fromCache !== null);
    const localData = fromPrecache !== undefined && fromPrecache !== null ? fromPrecache : fromCache;
    const esDatosPreguntas =
        cacheKey.startsWith('preguntas_') ||
        cacheKey.startsWith('preguntas_fcr_') ||
        cacheKey.startsWith('subpreguntas_');

    if (navigator.onLine) {
        // Estrategia rápida: para preguntas/subpreguntas, servir local al instante
        // y refrescar en segundo plano para mantener frescura sin bloquear UI.
        if (esDatosPreguntas && hasLocalData) {
            fetchAndCache().catch((error) => {
                console.warn(`⚠️ Refresh en segundo plano falló (${cacheKey}):`, error.message);
            });
            console.log(`⚡ Online rápido: datos locales inmediatos (${cacheKey})`);
            return localData;
        }

        try {
            return await fetchAndCache();
        } catch (error) {
            console.warn(`📦 Fetch falló (${cacheKey}), usando caché local:`, error.message);
            try {
                return fallbackOffline(error);
            } catch (e2) {
                throw error;
            }
        }
    }

    try {
        return fallbackOffline(new Error('Sin conexión'));
    } catch (error) {
        console.error(`❌ cargarDatosConOffline sin red ni caché (${cacheKey}):`, error);
        throw error;
    }
},

/**
 * Fecha/hora en que el usuario envió la calificación (para que al sincronizar
 * el servidor use created_at correcto, no el momento en que volvió internet).
 */
payloadCalificacionParaOffline(data) {
    if (!data || typeof data !== 'object' || Array.isArray(data)) {
        return data;
    }
    return {
        ...data,
        registrado_en: data.registrado_en || new Date().toISOString(),
    };
},

/**
 * 🔥 NUEVO: Función helper para enviar calificaciones con soporte offline
 */
async enviarCalificacionConOffline(url, data) {
    try {
        // Intentar enviar normalmente
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify(data)
        });

        if (response.ok) {
            return await response.json();
        } else {
            throw new Error(`Error ${response.status}: ${response.statusText}`);
        }
    } catch (error) {
        // 🔥 MEJORADO: Detectar mejor cuando estamos offline
        const isOffline = !navigator.onLine || 
                         error.message.includes('Failed to fetch') || 
                         error.message.includes('NetworkError') ||
                         error.message.includes('Network request failed') ||
                         error.name === 'TypeError' ||
                         error.name === 'NetworkError';
        
        if (isOffline) {
            console.log('📦 Sin conexión detectada, guardando calificación offline...', {
                navigatorOnLine: navigator.onLine,
                errorMessage: error.message,
                errorName: error.name
            });
            
            // Esperar un momento para que offlineHandler se inicialice si no está disponible
            let handler = window.offlineHandler;
            if (!handler) {
                // Esperar hasta 1 segundo para que se inicialice
                for (let i = 0; i < 10; i++) {
                    await new Promise(resolve => setTimeout(resolve, 100));
                    if (window.offlineHandler) {
                        handler = window.offlineHandler;
                        break;
                    }
                }
            }
            
            if (handler && typeof handler.addToSyncQueue === 'function') {
                try {
                    const payload = this.payloadCalificacionParaOffline(data);
                    const requestId = handler.addToSyncQueue(
                        url,
                        'POST',
                        payload,
                        {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                        }
                    );
                    
                    console.log('✅ Calificación agregada a cola de sincronización:', requestId);
                    
                    return {
                        offline: true,
                        success: true,
                        message: 'Calificación guardada localmente. Se sincronizará cuando haya conexión.',
                        requestId
                    };
                } catch (handlerError) {
                    console.error('❌ Error agregando a cola de sincronización:', handlerError);
                }
            }
            
            // Si no hay offlineHandler o falló, guardar en localStorage como respaldo
            try {
                const offlineCalificaciones = JSON.parse(localStorage.getItem('offline_calificaciones') || '[]');
                const payload = this.payloadCalificacionParaOffline(data);
                offlineCalificaciones.push({
                    url,
                    data: payload,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    timestamp: new Date().toISOString()
                });
                localStorage.setItem('offline_calificaciones', JSON.stringify(offlineCalificaciones));
                
                console.log('✅ Calificación guardada en localStorage como respaldo');
                
                return {
                    offline: true,
                    success: true,
                    message: 'Calificación guardada localmente. Se sincronizará cuando haya conexión.'
                };
            } catch (storageError) {
                console.error('❌ Error guardando en localStorage:', storageError);
                throw error; // Si falla todo, lanzar el error original
            }
        }
        
        // Si es otro tipo de error, lanzarlo
        throw error;
    }
},

/**
 * 🔥 NUEVO: Configurar eventos de sincronización
 */
configurarEventosSincronizacion() {
    // Escuchar evento de sincronización completada
    window.addEventListener('calificaciones-sincronizadas', (event) => {
        const { sincronizadas, pendientes } = event.detail || {};
        console.log(`✅ ${sincronizadas} calificaciones sincronizadas. Pendientes: ${pendientes}`);
        
        // Mostrar notificación al usuario
        if (sincronizadas > 0) {
            Swal.fire({
                icon: 'success',
                title: 'Sincronización completada',
                text: `${sincronizadas} calificación(es) sincronizada(s) exitosamente.`,
                timer: 3000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        }
    });
    
    // Escuchar cambios de estado de conexión
    window.addEventListener('connection-status', (event) => {
        const { online } = event.detail || {};
        if (online) {
            console.log('✅ Conexión restaurada, verificando calificaciones pendientes...');
            // Verificar si hay calificaciones pendientes
            const offlineCalificaciones = JSON.parse(localStorage.getItem('offline_calificaciones') || '[]');
            const syncQueue = JSON.parse(localStorage.getItem('sync_queue') || '[]');
            
            if (offlineCalificaciones.length > 0 || syncQueue.length > 0) {
                console.log(`🔄 Hay ${offlineCalificaciones.length + syncQueue.length} calificaciones pendientes de sincronizar`);
            }
        }
    });
    
    console.log('✅ Eventos de sincronización configurados');
},

/**
 * 🔥 NUEVO: Precargar todas las preguntas de todos los niveles en segundo plano
 * Esto mejora significativamente el rendimiento al calificar
 */
async precargarTodasLasPreguntas() {
    // Ejecutar en segundo plano sin bloquear la UI
    setTimeout(async () => {
        try {
            // Verificar que todos los datos necesarios estén disponibles
            if (!this.areaSeleccionada) {
                console.log('⚠️ No se puede precargar: falta areaSeleccionada');
                return;
            }
            
            if (!this.nivelesCalificacion || this.nivelesCalificacion.length === 0) {
                console.log('⚠️ No se puede precargar: niveles no cargados aún. Reintentando en 1 segundo...');
                // Reintentar después de 1 segundo, máximo 5 intentos
                if (!this.intentosPrecarga) {
                    this.intentosPrecarga = 0;
                }
                this.intentosPrecarga++;
                if (this.intentosPrecarga < 5) {
                    setTimeout(() => this.precargarTodasLasPreguntas(), 1000);
                } else {
                    console.warn('⚠️ Máximo de intentos de precarga alcanzado. Los niveles no están disponibles.');
                }
                return;
            }
            
            // Resetear contador de intentos si los niveles están disponibles
            this.intentosPrecarga = 0;

            console.log('🚀 Iniciando precarga de preguntas para todos los niveles...');
            const sedeGuardada = localStorage.getItem('sede_seleccionada');
            let sedeId = null;
            
            if (this.areaSeleccionada.sede_id) {
                sedeId = this.areaSeleccionada.sede_id;
            } else if (sedeGuardada) {
                try {
                    const sedeResponse = await fetch(`/api/sedes/buscar?nombre=${encodeURIComponent(sedeGuardada)}`);
                    if (sedeResponse.ok) {
                        const sedeData = await sedeResponse.json();
                        sedeId = sedeData.id;
                    }
                } catch (error) {
                    console.warn('⚠️ No se pudo obtener sede_id para precarga:', error);
                }
            }

            // 🔥 NUEVO: Precargar preguntas para cada nivel (CSAT)
            const precargasNiveles = this.nivelesCalificacion.map(async (nivel) => {
                try {
                    let url = `/api/preguntas?area_id=${this.areaSeleccionada.id}&nivel_id=${nivel.id}`;
                    if (sedeId) {
                        url += `&sede_id=${sedeId}`;
                    }

                    const cacheKey = `preguntas_${this.areaSeleccionada.id}_${nivel.id}_${sedeId || 'sin_sede'}`;
                    
                    // Verificar si ya está en caché precargado
                    const precached = localStorage.getItem(`precache_${cacheKey}`);
                    if (precached) {
                        console.log(`✅ Preguntas CSAT del nivel ${nivel.id} ya están precargadas`);
                        return await this.precargarSubpreguntasDePreguntas(JSON.parse(precached).data);
                    }

                    // Cargar desde API
                    const response = await fetch(url, {
                        credentials: 'include'
                    });

                    if (response.ok) {
                        const data = await response.json();
                        
                        // Guardar en caché precargado
                        const cacheData = {
                            data: data,
                            url: url,
                            timestamp: new Date().toISOString()
                        };
                        localStorage.setItem(`precache_${cacheKey}`, JSON.stringify(cacheData));
                        console.log(`✅ Precargadas preguntas CSAT para nivel ${nivel.id} (${data.length} preguntas)`);
                        
                        // Precargar subpreguntas
                        await this.precargarSubpreguntasDePreguntas(data);
                    } else {
                        console.warn(`⚠️ No se pudieron precargar preguntas CSAT para nivel ${nivel.id}: ${response.status}`);
                    }
                } catch (error) {
                    console.warn(`⚠️ Error precargando preguntas CSAT para nivel ${nivel.id}:`, error);
                }
            });

            // 🔥 NUEVO: Precargar preguntas FCR (individuales y cuando se usan con CSAT)
            const precargaFCR = async () => {
                try {
                    const url = `/api/preguntas?area_id=${this.areaSeleccionada.id}&nivel_id=todas&sede_id=${sedeId || 'sin_sede'}`;
                    const cacheKey = `preguntas_fcr_todas_${this.areaSeleccionada.id}_${sedeId || 'sin_sede'}`;
                    
                    // Verificar si ya está en caché precargado
                    const precached = localStorage.getItem(`precache_${cacheKey}`);
                    if (precached) {
                        console.log(`✅ Preguntas FCR ya están precargadas`);
                        const data = JSON.parse(precached).data;
                        const preguntaFCR = data.find(p => p.tipo_pregunta === 'fcr' && p.is_active);
                        if (preguntaFCR) {
                            await this.precargarSubpreguntasDePreguntas([preguntaFCR]);
                        }
                        return;
                    }

                    // Cargar desde API
                    const response = await fetch(url, {
                        credentials: 'include'
                    });

                    if (response.ok) {
                        const data = await response.json();
                        
                        // Guardar en caché precargado
                        const cacheData = {
                            data: data,
                            url: url,
                            timestamp: new Date().toISOString()
                        };
                        localStorage.setItem(`precache_${cacheKey}`, JSON.stringify(cacheData));
                        
                        // Buscar pregunta FCR y precargar sus subpreguntas
                        const preguntaFCR = data.find(p => p.tipo_pregunta === 'fcr' && p.is_active);
                        if (preguntaFCR) {
                            console.log(`✅ Precargadas preguntas FCR (${data.length} preguntas totales, 1 FCR encontrada)`);
                            await this.precargarSubpreguntasDePreguntas([preguntaFCR]);
                        } else {
                            console.log(`✅ Precargadas preguntas (${data.length} preguntas, sin FCR)`);
                        }
                    } else {
                        console.warn(`⚠️ No se pudieron precargar preguntas FCR: ${response.status}`);
                    }
                } catch (error) {
                    console.warn(`⚠️ Error precargando preguntas FCR:`, error);
                }
            };

            // Ejecutar todas las precargas en paralelo
            const todasLasPrecargas = [
                ...precargasNiveles, // CSAT por nivel
                precargaFCR() // FCR
            ];

            // Esperar a que todas las precargas terminen
            await Promise.all(todasLasPrecargas);
            console.log('🎉 Precarga completa: CSAT y FCR cargados');
        } catch (error) {
            console.error('❌ Error en precarga de preguntas:', error);
        }
    }, 500); // Esperar 500ms para no bloquear la carga inicial
},

/**
 * 🔥 NUEVO: Helper para precargar subpreguntas de un array de preguntas
 */
async precargarSubpreguntasDePreguntas(preguntas) {
    if (!preguntas || !Array.isArray(preguntas)) {
        return;
    }

    for (const pregunta of preguntas) {
        if (pregunta.opciones && Array.isArray(pregunta.opciones)) {
            for (const opcion of pregunta.opciones) {
                if (opcion.tiene_subpreguntas && opcion.id) {
                    try {
                        const subpreguntasUrl = `/api/subpreguntas/${opcion.id}`;
                        const subpreguntasCacheKey = `subpreguntas_${opcion.id}`;
                        
                        // Verificar si ya está precargado
                        const subpreguntasPrecached = localStorage.getItem(`precache_${subpreguntasCacheKey}`);
                        if (subpreguntasPrecached) {
                            continue; // Ya está precargado
                        }
                        
                        const subpreguntasResponse = await fetch(subpreguntasUrl, {
                            credentials: 'include'
                        });
                        
                        if (subpreguntasResponse.ok) {
                            const subpreguntasData = await subpreguntasResponse.json();
                            const subpreguntasCacheData = {
                                data: subpreguntasData,
                                url: subpreguntasUrl,
                                timestamp: new Date().toISOString()
                            };
                            localStorage.setItem(`precache_${subpreguntasCacheKey}`, JSON.stringify(subpreguntasCacheData));
                            console.log(`✅ Precargadas subpreguntas para opción ${opcion.id}`);
                        }
                    } catch (subpreguntasError) {
                        // Ignorar errores de subpreguntas, no es crítico
                        console.warn(`⚠️ No se pudieron precargar subpreguntas para opción ${opcion.id}:`, subpreguntasError);
                    }
                }
            }
        }
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
            
            // 🔥 NUEVO: Usar función helper con soporte offline
            const cacheKey = `subpreguntas_${opcionId}`;
            const subpreguntas = await this.cargarDatosConOffline(`/api/subpreguntas/${opcionId}`, cacheKey);
            
            if (subpreguntas) {
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
        
        // 🔥 NUEVO: Usar función helper con soporte offline
        const cacheKey = `pregunta_rango_${preguntaId}_${valor}`;
        const preguntaRango = await this.cargarDatosConOffline(`/api/preguntas/${preguntaId}/rango/${valor}`, cacheKey);
        
        if (preguntaRango) {
            
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
            // Evitar múltiples intervalos activos (causa bucles de cierre/recarga)
            if (this.intervalo) {
                clearInterval(this.intervalo);
                this.intervalo = null;
            }
            this.tiempoRestante = 5;
            this.intervalo = setInterval(() => {
                this.tiempoRestante--;
                
                if (this.tiempoRestante <= 0) {
                    this.cerrarAgradecimiento();
                }
            }, 1000);
        },

        async cerrarAgradecimiento() {
            if (this.cerrandoAgradecimientoEnCurso) {
                return;
            }
            this.cerrandoAgradecimientoEnCurso = true;
            try {
                if (this.intervalo) {
                    clearInterval(this.intervalo);
                    this.intervalo = null;
                }
                this.mostrarAgradecimiento = false;
                
                // 🔥 NUEVO: Reiniciar completamente la calificación incluyendo la secuencia
                console.log('🔄 Cerrando agradecimiento y reiniciando todo...');
                this.reiniciarCalificacion();
                
                // 🔥 NUEVO: Volver a determinar la secuencia de tipos para que esté lista para la próxima calificación
                if (this.areaSeleccionada) {
                    this.determinarSecuenciaTipos();
                    console.log('📋 Secuencia de tipos reiniciada:', this.tiposCalificacionSecuencia);
                    try {
                        if (!this.areaSeleccionada.permite_csat && this.areaSeleccionada.permite_fcr) {
                            await this.cargarPreguntaFCR();
                        }
                    } catch (e) {
                        console.warn('⚠️ No se pudieron recargar preguntas tras agradecimiento:', e);
                    }
                }
            } finally {
                this.cerrandoAgradecimientoEnCurso = false;
            }
        },

        /**
         * Verifica si hay alguna interacción con preguntas o subpreguntas
         * Solo considera interacciones reales del usuario, no valores por defecto
         */
        hayInteraccionConCalificacion() {
            const preguntaActual = this.preguntaActualData;
            
            // Verificar si hay respuestas válidas en preguntas principales
            let hayRespuestasPreguntas = false;
            if (Object.keys(this.respuestas).length > 0) {
                // Verificar que las respuestas tengan contenido real
                for (const preguntaId in this.respuestas) {
                    const respuesta = this.respuestas[preguntaId];
                    const pregunta = this.preguntas.find(p => p.id == preguntaId);
                    
                    // Si es un objeto, verificar que tenga contenido
                    if (typeof respuesta === 'object' && respuesta !== null) {
                        // Verificar opción seleccionada
                        if (respuesta.opcion_seleccionada_id !== undefined && respuesta.opcion_seleccionada_id !== null) {
                            hayRespuestasPreguntas = true;
                            break;
                        }
                        // Verificar texto libre con contenido
                        if (respuesta.texto_libre && typeof respuesta.texto_libre === 'string' && respuesta.texto_libre.trim().length > 0) {
                            hayRespuestasPreguntas = true;
                            break;
                        }
                        // Verificar valor de indicador modificado (diferente de 5)
                        if (respuesta.valor !== undefined && respuesta.valor !== null && respuesta.valor !== 5) {
                            if (preguntaActual && preguntaActual.tipo === 'indicador_0_10' && preguntaActual.id == preguntaId) {
                                hayRespuestasPreguntas = true;
                                break;
                            }
                        }
                        // Verificar opciones múltiples
                        if (respuesta.opciones_seleccionadas && Array.isArray(respuesta.opciones_seleccionadas) && respuesta.opciones_seleccionadas.length > 0) {
                            hayRespuestasPreguntas = true;
                            break;
                        }
                    } 
                    // Si es un valor directo (número o string), verificar que no sea vacío
                    else if (respuesta !== null && respuesta !== undefined && respuesta !== '' && respuesta !== 5) {
                        hayRespuestasPreguntas = true;
                        break;
                    }
                }
            }
            
            // Verificar si hay respuesta única seleccionada (y que no sea un valor por defecto)
            const hayRespuestaUnica = this.respuestaUnica !== null && 
                                      this.respuestaUnica !== undefined && 
                                      this.respuestaUnica !== '';
            
            // Verificar si hay texto libre escrito (para opción única con texto libre)
            const hayTextoLibre = this.textoLibreOpcion && 
                                  typeof this.textoLibreOpcion === 'string' && 
                                  this.textoLibreOpcion.trim().length > 0;
            
            // Verificar si hay respuesta libre (para preguntas de tipo texto_libre)
            // respuestaLibre puede ser un string o un objeto, verificar que sea string con contenido
            let hayRespuestaLibre = false;
            if (this.respuestaLibre) {
                if (typeof this.respuestaLibre === 'string' && this.respuestaLibre.trim().length > 0) {
                    hayRespuestaLibre = true;
                } else if (typeof this.respuestaLibre === 'object' && this.respuestaLibre !== null) {
                    // Si es objeto, verificar que tenga contenido real
                    if (this.respuestaLibre.texto_libre && typeof this.respuestaLibre.texto_libre === 'string' && this.respuestaLibre.texto_libre.trim().length > 0) {
                        hayRespuestaLibre = true;
                    }
                }
            }
            
            // Verificar si hay respuesta libre en la pregunta actual
            let hayRespuestaLibrePregunta = false;
            if (preguntaActual && preguntaActual.id) {
                const respuesta = this.respuestas[preguntaActual.id];
                if (respuesta && typeof respuesta === 'object' && respuesta.texto_libre && 
                    typeof respuesta.texto_libre === 'string' && respuesta.texto_libre.trim().length > 0) {
                    hayRespuestaLibrePregunta = true;
                }
            }
            
            // Verificar si hay respuesta múltiple con elementos reales
            const hayRespuestaMultiple = this.respuestaMultiple && 
                                        Array.isArray(this.respuestaMultiple) && 
                                        this.respuestaMultiple.length > 0;
            
            // Verificar si el indicador ha sido modificado (diferente del valor por defecto 5)
            let indicadorModificado = false;
            if (preguntaActual && preguntaActual.tipo === 'indicador_0_10' && preguntaActual.id) {
                const respuestaIndicador = this.respuestas[preguntaActual.id];
                if (respuestaIndicador !== undefined && respuestaIndicador !== null) {
                    // Si es un objeto con valor
                    if (typeof respuestaIndicador === 'object' && respuestaIndicador.valor !== undefined) {
                        indicadorModificado = respuestaIndicador.valor !== 5;
                    } 
                    // Si es un número directo
                    else if (typeof respuestaIndicador === 'number') {
                        indicadorModificado = respuestaIndicador !== 5;
                    }
                }
            }
            
            // Verificar respuestas de subpreguntas de manera más completa
            let hayRespuestasSubpreguntas = false;
            if (Object.keys(this.respuestasSubpreguntas).length > 0) {
                // Verificar cada subpregunta para ver si tiene alguna respuesta válida
                for (const subpreguntaId in this.respuestasSubpreguntas) {
                    const respuestaSubpregunta = this.respuestasSubpreguntas[subpreguntaId];
                    
                    // Verificar opción única seleccionada
                    if (respuestaSubpregunta.opcionSeleccionada !== null && 
                        respuestaSubpregunta.opcionSeleccionada !== undefined) {
                        hayRespuestasSubpreguntas = true;
                        break;
                    }
                    
                    // Verificar opciones múltiples
                    if (respuestaSubpregunta.opcionesSeleccionadas && 
                        respuestaSubpregunta.opcionesSeleccionadas.length > 0) {
                        hayRespuestasSubpreguntas = true;
                        break;
                    }
                    
                    // Verificar texto libre
                    if (respuestaSubpregunta.texto && 
                        respuestaSubpregunta.texto.trim().length > 0) {
                        hayRespuestasSubpreguntas = true;
                        break;
                    }
                    
                    // Verificar indicador modificado (diferente del valor por defecto 5)
                    if (respuestaSubpregunta.valor !== undefined && 
                        respuestaSubpregunta.valor !== null &&
                        respuestaSubpregunta.valor !== 5) {
                        hayRespuestasSubpreguntas = true;
                        break;
                    }
                }
            }
            
            const resultado = hayRespuestasPreguntas || 
                              hayRespuestasSubpreguntas || 
                              hayRespuestaUnica || 
                              hayTextoLibre || 
                              hayRespuestaLibre ||
                              hayRespuestaLibrePregunta || 
                              hayRespuestaMultiple ||
                              indicadorModificado;
            
            // Log de depuración solo si detecta interacción
            if (resultado) {
                console.log('🔍 Detección de interacción:', {
                    hayRespuestasPreguntas,
                    hayRespuestasSubpreguntas,
                    hayRespuestaUnica,
                    hayTextoLibre,
                    hayRespuestaLibre,
                    hayRespuestaLibrePregunta,
                    hayRespuestaMultiple,
                    indicadorModificado,
                    respuestas: this.respuestas,
                    respuestaUnica: this.respuestaUnica,
                    respuestaMultiple: this.respuestaMultiple
                });
            }
            
            return resultado;
        },

        cancelarCuestionario() {
            // Si no hay interacción con ninguna calificación, cerrar el modal automáticamente
            if (!this.hayInteraccionConCalificacion()) {
                console.log('⚠️ No hay interacción con ninguna calificación, cerrando modal automáticamente...');
                this.cerrarCuestionario();
            } else {
                // Si hay interacción, mostrar confirmación antes de cerrar
                this.cerrarCuestionario();
            }
        },

        /**
         * Cierra el modal tras tiempoInactividad sin eventos que llamen a resetearTimeoutInactividad.
         * No se mira el estado del formulario: si el temporizador vence, el usuario dejó de interactuar.
         */
        iniciarTimeoutInactividad() {
            // Solo iniciar si el modal está abierto
            if (!this.mostrarCuestionario) {
                return;
            }
            
            // Limpiar timeout anterior si existe
            this.limpiarTimeoutInactividad();
            
            console.log('⏱️ Iniciando timeout de inactividad:', this.tiempoInactividad, 'ms');
            
            this.timeoutInactividad = setTimeout(() => {
                if (!this.mostrarCuestionario) {
                    return;
                }
                if (this.cargando) {
                    this.iniciarTimeoutInactividad();
                    return;
                }
                console.log('⏱️ Tiempo de inactividad agotado, cerrando modal automáticamente...');
                this.cerrarCuestionario();
            }, this.tiempoInactividad);
        },

        /**
         * Resetea el timeout de inactividad cuando hay interacción
         */
        resetearTimeoutInactividad() {
            if (this.mostrarCuestionario) {
                this.iniciarTimeoutInactividad();
            }
        },

        /**
         * Limpia el timeout de inactividad
         */
        limpiarTimeoutInactividad() {
            if (this.timeoutInactividad) {
                clearTimeout(this.timeoutInactividad);
                this.timeoutInactividad = null;
            }
        },

        cerrarCuestionario() {
            // Limpiar timeout de inactividad
            this.limpiarTimeoutInactividad();
            this.mostrarCuestionario = false;
            
            // Si hay una secuencia de tipos activa, reiniciar al primer tipo de la secuencia
            if (this.tiposCalificacionSecuencia.length > 0) {
                console.log('🔄 Cerrando modal y reiniciando secuencia desde el primer tipo...', {
                    secuencia: this.tiposCalificacionSecuencia
                });
                // Reiniciar índice y tipo actual al primero de la secuencia
                this.indiceTipoActual = 0;
                this.tipoCalificacionActual = this.tiposCalificacionSecuencia[0];
                
                // Limpiar respuestas y preguntas actuales
                this.preguntas = [];
                this.preguntaActual = 0;
                this.respuestas = {};
                this.respuestasSubpreguntas = {};
                this.modoSubpreguntas = false;
                this.subpreguntasActuales = [];
                this.subpreguntaIndex = 0;
                this.errorValidacion = '';
                this.respuestaUnica = null;
                this.respuestaMultiple = [];
                this.textoLibreOpcion = '';
                this.opcionTextoLibreSeleccionada = null;
                this.respuestaLibre = '';
                this.arrastrando = false;
                // Limpiar acumuladores para empezar de nuevo
                this.respuestasAcumuladas = {};
                this.respuestasSubpreguntasAcumuladas = [];
                this.respuestasRangosAcumuladas = [];
            } else {
                // Si no hay secuencia, reiniciar completamente
                console.log('🔄 Reiniciando calificación completamente...');
                this.reiniciarCalificacion();
            }
        },

        /**
         * Limpia todas las respuestas antes de iniciar un nuevo tipo de calificación
         * Esto evita que valores residuales interfieran con la detección de interacción
         */
        limpiarRespuestasParaNuevaCalificacion() {
            console.log('🧹 Limpiando respuestas para nueva calificación...');
            
            // Limpiar todas las respuestas
            this.respuestas = {};
            this.respuestaUnica = null;
            this.respuestaMultiple = [];
            this.textoLibreOpcion = '';
            this.opcionTextoLibreSeleccionada = null;
            this.respuestaLibre = '';
            
            // Limpiar subpreguntas
            this.modoSubpreguntas = false;
            this.subpreguntasActuales = [];
            this.subpreguntaIndex = 0;
            this.subpreguntasActivas = {};
            this.respuestasSubpreguntas = {};
            
            // Limpiar errores
            this.errorValidacion = '';
            
            this.respuestaIndicadorValor = 5;
            
            // Limpiar estado de arrastre
            this.arrastrando = false;
            
            // Resetear pregunta actual
            this.preguntaActual = 0;
        },

        reiniciarCalificacion() {
            // Limpiar nivel seleccionado para volver a la pantalla inicial
            this.nivelSeleccionado = null;
            
            // Forzar recarga desde caché/API en la próxima calificación (evita estado vacío en 2.ª visita offline)
            this.preguntaFCRPrincipal = null;
            
            // Limpiar preguntas y respuestas
            this.preguntas = [];
            this.preguntaActual = 0;
            this.respuestas = {};
            this.respuestasAcumuladas = {};
            
            // Limpiar subpreguntas
            this.modoSubpreguntas = false;
            this.subpreguntasActuales = [];
            this.subpreguntaIndex = 0;
            this.subpreguntasActivas = {};
            this.respuestasSubpreguntas = {};
            this.respuestasSubpreguntasAcumuladas = [];
            
            // Limpiar rangos
            this.respuestasRangosAcumuladas = [];
            
            // Limpiar estados de calificación
            this.tipoCalificacionActual = null;
            this.indiceTipoActual = 0;
            this.tiposCalificacionSecuencia = [];
            this.respuestaFCR = null;
            
            // Limpiar errores y valores por defecto
            this.errorValidacion = '';
            this.respuestaIndicadorValor = 5;
            this.textoLibreOpcion = '';
            this.opcionTextoLibreSeleccionada = null;
            this.respuestaUnica = null;
            this.respuestaMultiple = [];
            this.arrastrando = false;
            this.cargando = false;
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
        // Resetear timeout de inactividad cuando se arrastra el indicador
        this.resetearTimeoutInactividad();
    }
    
    event.preventDefault();
},

detenerArrastre() {
    this.arrastrando = false;
    
    document.removeEventListener('mousemove', this.actualizarIndicador);
    document.removeEventListener('mouseup', this.detenerArrastre);
    document.removeEventListener('touchmove', this.actualizarIndicador);
    document.removeEventListener('touchend', this.detenerArrastre);
    
    // Resetear timeout de inactividad al finalizar el arrastre
    this.resetearTimeoutInactividad();
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
    const modal = this.$el.querySelector('.modal-container');
    if (!modal) return;

    // Scroll al final
    modal.scrollTop = modal.scrollHeight;
    setTimeout(() => {
      modal.scrollTop = modal.scrollHeight;
    }, 500);
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
        
        // 🔥 NUEVO: Usar función helper con soporte offline
        const cacheKey = `subpreguntas_${opcionId}`;
        const subpreguntas = await this.cargarDatosConOffline(`/api/subpreguntas/${opcionId}`, cacheKey);
        
        if (subpreguntas) {
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
    
    // 🔥 NUEVO: Auto-focus cuando se selecciona "Otro" en subpreguntas (igual que en preguntas normales)
    if (subpregunta.tipo === 'opcion_unica_texto_libre' && 
        opcion && 
        (opcion.toLowerCase().includes('otro') || opcion.toLowerCase().includes('especifique'))) {
        // 1️⃣ Esperar que Vue renderice el textarea
        this.$nextTick(() => {
            const textarea = this.$el.querySelector('.texto-libre-subpregunta .texto-libre-input');
            const modal = this.$el.querySelector('.modal-container');
            
            if (!textarea || !modal) return;
            // 2️⃣ Focus forzado mediante setTimeout para que Full Kiosk abra teclado
            setTimeout(() => {
                textarea.focus({ preventScroll: true });  // focus real
            }, 100);
        });
    }
    
    console.log('📝 Respuesta subpregunta actualizada:', this.respuestasSubpreguntas[subpregunta.id]);
    
    // Resetear timeout de inactividad
    this.resetearTimeoutInactividad();
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
    
    // Resetear timeout de inactividad
    this.resetearTimeoutInactividad();
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
    
    // Resetear timeout de inactividad
    this.resetearTimeoutInactividad();
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
    
    // Resetear timeout de inactividad
    this.resetearTimeoutInactividad();
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
    
    // Resetear timeout de inactividad
    this.resetearTimeoutInactividad();
},

/**
 * 🔥 NUEVO: Seleccionar opción única con texto para rangos
 */
seleccionarOpcionUnicaConTextoRango(opcion, index) {
     // 1️⃣ Marcar la opción como seleccionada
     this.respuestaUnica = opcion.id || opcion;

        // 2️⃣ Esperar que Vue renderice el textarea
        this.$nextTick(() => {
        const textarea = this.$el.querySelector('.texto-libre-input-opcion');
        const modal = this.$el.querySelector('.modal-container');

        if (!textarea || !modal) return;
        // 4️⃣ Focus forzado mediante setTimeout para que Full Kiosk abra teclado
        setTimeout(() => {
            textarea.focus({ preventScroll: true });  // focus real
        }, 100);        
        });

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
    
    // Resetear timeout de inactividad
    this.resetearTimeoutInactividad();
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
    
    // Resetear timeout de inactividad
    this.resetearTimeoutInactividad();
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

// Indicador 0-10 en el modal
iniciarArrastreIndicador(event) {
    this.arrastrando = true;
    
    // Guardar referencia al track para usarla en actualizarIndicadorModal
    this.trackModal = event.currentTarget || event.target?.closest('.indicador-track');
    
    this.actualizarIndicadorModal(event);
    document.addEventListener('mousemove', this.actualizarIndicadorModal);
    document.addEventListener('mouseup', this.detenerArrastreIndicador);
    document.addEventListener('touchmove', this.actualizarIndicadorModal, { passive: false });
    document.addEventListener('touchend', this.detenerArrastreIndicador);
    
    event.preventDefault();
},

actualizarIndicadorModal(event) {
    if (!this.arrastrando || !this.preguntaActualData) return;
    
    // Usar la referencia guardada o buscar el track en el modal
    const track = this.trackModal || document.querySelector('.modal-container .indicador-track');
    if (!track) return;
    
    const rect = track.getBoundingClientRect();
    let clientX;
    
    if (event.type.includes('touch')) {
        clientX = event.touches[0].clientX;
    } else {
        clientX = event.clientX;
    }
    
    const percentage = Math.max(0, Math.min(100, ((clientX - rect.left) / rect.width) * 100));
    const valor = Math.round((percentage / 100) * 10);
    
    // Guardar el valor en las respuestas
    if (this.preguntaActualData.id) {
        this.respuestas[this.preguntaActualData.id] = { valor: valor };
        this.respuestaIndicadorValor = valor;
    }
    
    // Resetear timeout de inactividad cuando se arrastra el indicador
    this.resetearTimeoutInactividad();
    
    this.$forceUpdate();
    event.preventDefault();
},

detenerArrastreIndicador() {
    this.arrastrando = false;
    this.trackModal = null;
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
    
    // Resetear timeout de inactividad
    this.resetearTimeoutInactividad();
},

seleccionarValorIndicador(valor) {
    if (!this.preguntaActualData) return;
    
    // Guardar el valor en las respuestas
    if (this.preguntaActualData.id) {
        this.respuestas[this.preguntaActualData.id] = { valor: valor };
        this.respuestaIndicadorValor = valor;
    }
    
    this.$forceUpdate();
    
    // Resetear timeout de inactividad
    this.resetearTimeoutInactividad();
},

async iniciarConFCR(resuelto, opcion, event) {
    this.respuestaFCR = resuelto;
    const opcionSeleccionada = opcion;
    
    // Forzar que el hover se desactive después del clic
    if (event && event.currentTarget) {
        // Agregar clase para desactivar hover
        event.currentTarget.classList.add('fcr-no-hover');
        // Usar nextTick para asegurar que el DOM se actualice
        this.$nextTick(() => {
            // Forzar que el elemento pierda el hover disparando mouseleave
            const mouseLeaveEvent = new MouseEvent('mouseleave', {
                bubbles: true,
                cancelable: true,
                view: window
            });
            event.currentTarget.dispatchEvent(mouseLeaveEvent);
            // Remover la clase después de 2 segundos para permitir hover de nuevo
            setTimeout(() => {
                if (event.currentTarget) {
                    event.currentTarget.classList.remove('fcr-no-hover');
                }
            }, 2000);
        });
    }
    
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
        
        // 🔥 CORRECCIÓN: Verificar si la opción "No" tiene subpreguntas
        const tieneSubpreguntasNo = opcionSeleccionada.tiene_subpreguntas || 
                                    (opcionSeleccionada.subpreguntas && opcionSeleccionada.subpreguntas.length > 0);
        
        console.log('🔍 Verificando subpreguntas para "No" en iniciarConFCR:', {
            tiene_subpreguntas: opcionSeleccionada.tiene_subpreguntas,
            subpreguntas: opcionSeleccionada.subpreguntas,
            opcionId: opcionSeleccionada.id,
            tieneSubpreguntasNo
        });
        
        if (tieneSubpreguntasNo) {
            // Simular nivel para cargar las subpreguntas
            this.nivelSeleccionado = { 
                id: 2, 
                nombre: 'FCR - Motivo',
                valor: 2,
                esFCR: true,
                resuelto: false
            };
            
            // 🔥 CORRECCIÓN: Usar cargarSubpreguntasFCRParaOpcion en lugar de cargarSubpreguntasFCR
            this.mostrarCuestionario = true;
            await this.$nextTick();
            
            await this.cargarSubpreguntasFCRParaOpcion(opcionSeleccionada.id, false);
        } else {
            // Si no tiene subpreguntas, finalizar directamente
            console.log('⚠️ FCR "No": No tiene subpreguntas configuradas, finalizando directamente sin mensaje');
            this.cargando = false;
            
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
        // 🔥 NUEVO: Usar función helper con soporte offline
        const cacheKey = `subpreguntas_${opcionId}`;
        let subpreguntas = null;
        let errorCarga = null;
        let noHayCache = false;
        
        try {
            subpreguntas = await this.cargarDatosConOffline(`/api/subpreguntas/${opcionId}`, cacheKey);
        } catch (error) {
            errorCarga = error;
            console.error('❌ Error cargando subpreguntas:', error);
            
            // Verificar si realmente no hay datos en caché
            try {
                const cached = localStorage.getItem(`cache_${cacheKey}`);
                if (!cached) {
                    noHayCache = true;
                    console.warn(`⚠️ No hay datos en caché para: ${cacheKey}`);
                } else {
                    // Hay datos en caché, intentar cargarlos directamente
                    const cacheData = JSON.parse(cached);
                    subpreguntas = cacheData.data;
                    console.log(`✅ Subpreguntas cargadas desde caché: ${cacheKey}`);
                }
            } catch (cacheError) {
                console.error('❌ Error verificando/cargando desde caché:', cacheError);
                noHayCache = true;
            }
        }
        
        if (subpreguntas) {
            
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
                this.cargando = false;
                this.mostrarCuestionario = true;
                
                // Forzar actualización de Vue para asegurar que el modal se muestre
                this.$nextTick(() => {
                    console.log('✅ Modo subpreguntas activado para opción:', opcionId);
                    console.log('✅ mostrarCuestionario:', this.mostrarCuestionario);
                    console.log('✅ subpreguntasActuales.length:', this.subpreguntasActuales.length);
                });
                // El conteo de inactividad se reinicia vía watch(cargando) al poner cargando = false arriba
            } else {
                console.log('⚠️ No hay subpreguntas para esta opción');
                this.cargando = false;
                
                await Swal.fire({
                    icon: 'info',
                    title: 'Sin preguntas adicionales',
                    text: 'No se encontraron preguntas adicionales para este motivo. Puede continuar y se guardará su respuesta.',
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#4f46e5',
                    allowOutsideClick: false,
                    allowEscapeKey: true
                });
                
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
            // Si no hay subpreguntas (null o undefined), verificar si es por falta de conexión Y falta de caché
            const isOffline = !navigator.onLine || noHayCache;
            if (isOffline && noHayCache) {
                console.warn('⚠️ Sin conexión y no hay subpreguntas en caché para esta opción');
                this.cargando = false;
                
                // Mostrar mensaje informativo pero NO finalizar automáticamente
                await Swal.fire({
                    icon: 'warning',
                    title: 'Sin conexión',
                    html: 'No se pueden cargar las subpreguntas porque no hay conexión a internet y no hay datos guardados previamente.<br><br>¿Desea continuar sin responder las subpreguntas?',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, continuar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#4f46e5',
                    cancelButtonColor: '#6b7280',
                    allowOutsideClick: false
                }).then(async (result) => {
                    if (result.isConfirmed) {
                        // El usuario decidió continuar sin subpreguntas
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
                    } else {
                        // El usuario canceló, volver al inicio
                        this.cargando = false;
                        this.mostrarCuestionario = false;
                    }
                });
                return;
            } else {
                throw new Error('Error al cargar subpreguntas');
            }
        }
    } catch (error) {
        console.error('❌ Error cargando subpreguntas FCR:', error);
        this.cargando = false;
        
        // Verificar si es un error de conexión
        const isOfflineError = !navigator.onLine || (error.message && error.message.includes('cache'));
        
        if (isOfflineError) {
            // Error por falta de conexión
            await Swal.fire({
                icon: 'warning',
                title: 'Sin conexión',
                html: 'No se pueden cargar las subpreguntas porque no hay conexión a internet y no hay datos guardados previamente.<br><br>¿Desea continuar sin responder las subpreguntas?',
                showCancelButton: true,
                confirmButtonText: 'Sí, continuar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#4f46e5',
                cancelButtonColor: '#6b7280',
                allowOutsideClick: false
            }).then(async (result) => {
                if (result.isConfirmed) {
                    // El usuario decidió continuar sin subpreguntas
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
                } else {
                    // El usuario canceló, volver al inicio
                    this.cargando = false;
                    this.mostrarCuestionario = false;
                }
            });
        } else {
            // Error diferente (no de conexión)
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al cargar las subpreguntas: ' + error.message,
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#ef4444'
            });
            
            // NO finalizar automáticamente en caso de error, dejar que el usuario decida
            this.cargando = false;
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
        // 🔥 NUEVO: Usar función helper con soporte offline
        const url = `/api/preguntas?area_id=${this.areaSeleccionada.id}&nivel_id=1&sede_id=${sedeId}`;
        const cacheKey = `preguntas_fcr_${this.areaSeleccionada.id}_${sedeId}`;
        const todasLasPreguntas = await this.cargarDatosConOffline(url, cacheKey);
        
        if (todasLasPreguntas) {
            
            if (todasLasPreguntas.length > 0) {
                const preguntaFCR = todasLasPreguntas[0];
                
                // Buscar la opción "No" y sus subpreguntas
                const opcionNo = preguntaFCR.opciones.find(op => op.opcion.toLowerCase() === 'no');
                
                if (opcionNo && opcionNo.tiene_subpreguntas) {
                    console.log('📝 Opción "No" tiene subpreguntas, cargándolas...');
                    
                    // Cargar las subpreguntas
                    // 🔥 NUEVO: Usar función helper con soporte offline
                    const cacheKey = `subpreguntas_${opcionNo.id}`;
                    let subpreguntas = await this.cargarDatosConOffline(`/api/subpreguntas/${opcionNo.id}`, cacheKey);
                    
                    if (subpreguntas) {
                        
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
                        
                        // 🔥 CORRECCIÓN: Asegurar que el modal se abra correctamente
                        this.cargando = false;
                        this.mostrarCuestionario = true;
                        
                        // Forzar actualización de Vue para asegurar que el modal se muestre
                        this.$nextTick(() => {
                            console.log('✅ Modal de subpreguntas FCR abierto:', {
                                mostrarCuestionario: this.mostrarCuestionario,
                                modoSubpreguntas: this.modoSubpreguntas,
                                subpreguntasCount: this.subpreguntasActuales.length
                            });
                        });
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
    if (this.cargandoPreguntaFCR) {
        console.log('⏳ cargarPreguntaFCR omitido: ya hay una carga en curso');
        return;
    }
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
        // 🔥 NUEVO: Usar función helper con soporte offline
        const url = `/api/preguntas?area_id=${this.areaSeleccionada.id}&nivel_id=todas&sede_id=${sedeId}`;
        const cacheKey = `preguntas_fcr_todas_${this.areaSeleccionada.id}_${sedeId}`;
        const preguntas = await this.cargarDatosConOffline(url, cacheKey);
        
        if (preguntas) {
            // Buscar pregunta con tipo_pregunta = 'fcr'
            const preguntaFCR = preguntas.find(p => p.tipo_pregunta === 'fcr' && p.is_active);
            
            if (preguntaFCR) {
                // 🔥 CORRECCIÓN: Asegurar que cada opción tenga el flag tiene_subpreguntas correcto
                if (preguntaFCR.opciones) {
                    preguntaFCR.opciones = preguntaFCR.opciones.map(opcion => {
                        // Verificar si realmente tiene subpreguntas
                        // Primero verificar el flag del backend, luego verificar si hay subpreguntas en el array
                        const tieneSubpreguntasEnArray = opcion.subpreguntas && 
                                                         Array.isArray(opcion.subpreguntas) && 
                                                         opcion.subpreguntas.length > 0;
                        
                        // Usar el flag del backend si está disponible (puede ser true o false), 
                        // o verificar el array si el flag no viene del backend
                        const flagBackend = opcion.tiene_subpreguntas;
                        const tieneSubpreguntas = (flagBackend !== undefined && flagBackend !== null)
                                                  ? flagBackend 
                                                  : tieneSubpreguntasEnArray;
                        
                        // Establecer el flag correctamente
                        opcion.tiene_subpreguntas = tieneSubpreguntas;
                        
                        console.log(`📋 Opción "${opcion.opcion}": tiene_subpreguntas=${tieneSubpreguntas}, subpreguntas=${opcion.subpreguntas?.length || 0}, flag_backend=${opcion.tiene_subpreguntas}`);
                        
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
* {
    -webkit-tap-highlight-color: transparent;
}

/* 🔥 BLOQUEO DE ZOOM: Prevenir zoom con gestos táctiles */
.calificador-container,
.calificador-container * {
    touch-action: pan-x pan-y;
    -ms-touch-action: pan-x pan-y;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
    -webkit-touch-callout: none;
}

/* Prevenir zoom con doble toque */
.calificador-container {
    -ms-content-zooming: none;
    -webkit-text-size-adjust: 100%;
    -moz-text-size-adjust: 100%;
    -ms-text-size-adjust: 100%;
    text-size-adjust: 100%;
}

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
}

.header-info {
    margin-bottom: 1rem;
    
}
.header-info * {
    user-select: none;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    cursor: pointer;
}
.calificador-titulo {
    font-size: clamp(3rem, 7vw, 3rem);
    margin-bottom: 1.5rem;
    font-weight: 700;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
    user-select: none;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    cursor: pointer;
    
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
    margin: 0.5rem;
}

.carita {
    width: 220px;
    height: 220px;
    border-radius: 50%;
    background: #fff;
    border: 0px solid #fff;
    /*box-shadow: 0 8px 32px rgba(0,0,0,0.2);*/
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-bottom: 1.5rem;
    outline: none; /* 🔥 Eliminar outline por defecto */
    
}
.carita * {
    user-select: none;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    cursor: pointer;
}

.carita:focus {
    outline: none; /* 🔥 Sin outline al hacer focus */
    /*box-shadow: 0 8px 32px rgba(0,0,0,0.2); /* Mantener sombra normal */
}

.carita:active {
    outline: none; /* 🔥 Sin outline al hacer click */
    transform: scale(0.98); /* Efecto de presión suave */
}

.carita:hover {
    transform: scale(1.1) rotate(5deg);
   /* box-shadow: 0 12px 40px rgba(0,0,0,0.3);*/
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
    background: rgba(0, 0, 0, 0);
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
    box-shadow: 0 0px 60px rgba(0, 0, 0, 0.3);
    max-width: 90%;
    max-height: 95vh;
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
    padding: 1rem 1rem;
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
    padding: 1rem 2rem;
}

.pregunta-header {
    text-align: center;
}

.pregunta-texto {
    font-size: 1.4rem;
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
    padding: 1.5rem;
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
    width: 460px;
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
    margin-bottom: 12%;
    font-size: 13rem; /* se puede ajustar según el tamaño de .carita */
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}
.emoji-img {
    width: 100%;
    height: 100%;
    object-fit: contain; /* mantiene proporción */

    -webkit-user-drag: none;  /* Chrome, Safari */
    -khtml-user-drag: none;
    -moz-user-drag: none;     /* Firefox */
    -o-user-drag: none;

    -webkit-user-select: none; /* no seleccionar */
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
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
.emoji * {
    user-select: none;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    cursor: pointer;
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

/* Indicadores alternativos (p. ej. FCR inicial sin CSAT) */
.indicadores-alt-wrapper {
    display: flex;
    flex-direction: column;
    gap: 2rem;
    max-width: 700px;
    margin: 0 auto;
}

.indicador-fcr-wrapper h3 {
    text-align: center;
    margin-bottom: 2rem;
    color: #1F2937;
    font-size: 1.5rem;
}

.fcr-options {
    display: flex;
    gap: 3rem;
    justify-content: center;
    align-items: center;
}

.fcr-option {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1.5rem;
    padding: 0 0 4rem 0;
    border: none;
    border-radius: 0;
    cursor: pointer;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    background: transparent;
    min-width: auto;
    position: relative;
    overflow: visible;
}

.fcr-option:hover:not(.fcr-no-hover) {
    transform: translateY(-10px) scale(1.1);
}

.fcr-option:active {
    transform: translateY(-5px) scale(1.05);
    transition: all 0.1s ease;
}

.fcr-option:first-child:hover:not(.fcr-no-hover) .fcr-icon {
    animation: pulse-bien 0.6s ease-in-out;
    transform: scale(1.2);
}

.fcr-option:last-child:hover:not(.fcr-no-hover) .fcr-icon {
    animation: pulse-mal 0.6s ease-in-out;
    transform: scale(1.2);
}

.fcr-icon {
    width: 200px;
    height: 200px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 6.5rem;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    z-index: 1;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
}

.fcr-icon i {
    transition: transform 0.3s ease;
}

.fcr-option:hover:not(.fcr-no-hover) .fcr-icon i {
    transform: scale(1.1) rotate(5deg);
}

.fcr-option:first-child:hover:not(.fcr-no-hover) .fcr-icon i {
    transform: scale(1.1) rotate(-10deg);
}

.fcr-option:last-child:hover:not(.fcr-no-hover) .fcr-icon i {
    transform: scale(1.1) rotate(10deg);
}

.fcr-icon.bien {
    background: linear-gradient(135deg, #D1FAE5 0%, #A7F3D0 100%);
    color: #10B981;
}

.fcr-icon.mal {
    background: linear-gradient(135deg, #FEE2E2 0%, #FECACA 100%);
    color: #EF4444;
}

.fcr-option span {
    font-size: 1.5rem;
    font-weight: 700;
    color: #374151;
    position: relative;
    z-index: 1;
    letter-spacing: 0.5px;
    transition: all 0.3s ease;
}

.fcr-option:hover:not(.fcr-no-hover) span {
    transform: scale(1.1);
    color: #1F2937;
}

@keyframes pulse-bien {
    0%, 100% {
        box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
    }
    50% {
        box-shadow: 0 8px 40px rgba(16, 185, 129, 0.6);
    }
}

@keyframes pulse-mal {
    0%, 100% {
        box-shadow: 0 8px 20px rgba(239, 68, 68, 0.3);
    }
    50% {
        box-shadow: 0 8px 40px rgba(239, 68, 68, 0.6);
    }
}

/* 🔥 NUEVO: Estilos para pantalla de carga del calificador */
.loading-calificador-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
    animation: fadeIn 0.3s ease-in;
}

.loading-calificador-content {
    background: white;
    border-radius: 24px;
    padding: 3rem;
    max-width: 500px;
    width: 90%;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    text-align: center;
    animation: slideUp 0.4s ease-out;
}

.loading-calificador-icon {
    font-size: 4rem;
    color: #667eea;
    margin-bottom: 1.5rem;
    animation: pulse 2s ease-in-out infinite;
}

.loading-calificador-title {
    font-size: 2rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 0.5rem;
}

.loading-calificador-subtitle {
    font-size: 1rem;
    color: #6b7280;
    margin-bottom: 2rem;
}

.progress-bar-container {
    margin-bottom: 2rem;
}

.progress-bar-background {
    width: 100%;
    height: 12px;
    background: #e5e7eb;
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 0.5rem;
}

.progress-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    border-radius: 10px;
    transition: width 0.3s ease;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.4);
}

.progress-bar-text {
    font-size: 0.875rem;
    font-weight: 600;
    color: #667eea;
}

.loading-details {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e5e7eb;
}

.loading-detail-item {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #4b5563;
}

.loading-detail-item i {
    font-size: 1rem;
}

.loading-detail-item i.fa-check-circle {
    color: #10b981;
}

.loading-detail-item i.fa-spinner {
    color: #667eea;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes slideUp {
    from {
        transform: translateY(30px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.1);
    }
}

/* Animación de entrada para las opciones */
.fcr-option {
    animation: fadeInUp 0.5s ease-out;
}

.fcr-option:first-child {
    animation-delay: 0.1s;
}

.fcr-option:last-child {
    animation-delay: 0.2s;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

</style>