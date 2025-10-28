<template>
    <div class="preguntas-management">
        <!-- Header Section -->
        <div class="header-section">
            <div class="header-content">
                <div class="header-info">
                    <h1 class="page-title">
                        <i class="fas fa-question-circle"></i>
                        Gestión de Preguntas
                        <span v-if="sedeActual" class="sede-badge">
                            - {{ sedeActual.nombre }}
                        </span>
                    </h1>
                    <p class="page-subtitle">
                        {{ sedeActual 
                            ? `Mostrando preguntas de la sede ${sedeActual.nombre}` 
                            : 'Mostrando todas las preguntas del sistema' 
                        }}
                    </p>
                </div>
                <button @click="mostrarModalCrearPregunta" class="btn-primary">
                    <i class="fas fa-plus"></i>
                    Nueva Pregunta
                </button>
            </div>
        </div>


        <!-- Content Section -->
        <div class="content-section">
            <!-- Loading State -->
            <div v-if="loadingPreguntas" class="loading-container">
                <div class="loading-spinner"></div>
                <p class="loading-text">Cargando preguntas...</p>
            </div>

            <!-- Empty State -->
            <div v-else-if="preguntas.length === 0" class="empty-container">
                <div class="empty-icon">
                    <i class="fas fa-question-circle"></i>
                </div>
                <h3 class="empty-title">
                    {{ sedeActual ? 'No hay preguntas en esta sede' : 'No hay preguntas disponibles' }}
                </h3>
                <p class="empty-description">
                    {{ sedeActual 
                        ? `No se encontraron preguntas para la sede ${sedeActual.nombre}` 
                        : 'Comienza creando la primera pregunta del sistema.' 
                    }}
                </p>
                <button @click="mostrarModalCrearPregunta" class="btn-primary">
                    <i class="fas fa-plus"></i>
                    Crear Primera Pregunta
                </button>
            </div>

            <!-- Questions Grid -->
            <div v-else class="questions-grid">
                <div v-for="pregunta in preguntas" :key="pregunta.id" class="question-card">
                    <div class="card-header">
                        <div class="question-id">#{{ pregunta.id }}</div>
                        <div class="question-status">
                            <!-- Badges de tipo y estado juntos -->
                            <div style="display: flex; gap: 0.5rem; align-items: center;">                                
                                <!-- Badge de tipo de calificación -->                               
                                <span :class="['status-badge', 'inactive']">
                                    <i class="fas fa-pause-circle"></i>
                                    {{ getTipoNombre(pregunta.tipo_pregunta) }}
                                </span>
                                <span :class="['type-badge', getTipoBadgeClass(pregunta.tipo)]">
                                    <i :class="getTipoIcon(pregunta.tipo)"></i>
                                    {{ getTipoText(pregunta.tipo) }}
                                </span>
                                <!-- Badge de estado Activa/Inactiva -->
                                <span :class="['status-badge', pregunta.is_active ? 'active' : 'inactive']">
                                    <i :class="pregunta.is_active ? 'fas fa-check-circle' : 'fas fa-pause-circle'"></i>
                                    {{ pregunta.is_active ? 'Activa' : 'Inactiva' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="card-content">
                        
                        <h3 class="question-text">{{ pregunta.pregunta }}</h3>
                        
                        <!-- 🔥 NUEVO: Solo mostrar nivel de calificación si NO es NPS o FCR (CSAT sí lo muestra) -->
                        <div v-if="!pregunta.tipo_pregunta || pregunta.tipo_pregunta === 'csat'" class="question-meta">
                            <div class="meta-item">
                                <i class="fas fa-layer-group"></i>
                                <span>{{ getNivelName(pregunta.niveles_calificacion_id) }}</span>
                            </div>                            
                        </div>

                        <!-- 🔥 NUEVO: Badges de áreas participantes (agrupadas) -->
                        <div v-if="pregunta.areas_participantes && pregunta.areas_participantes.length > 0" class="participantes-section">
                            <div class="participantes-label">Áreas:</div>
                            <div class="badges-container">
                                <!-- Agrupar áreas por nombre usando computed property -->
                                <span v-for="(area, index) in uniqueAreas(pregunta.areas_participantes)" :key="`area-${index}`" class="participante-badge area-badge">
                                    <i class="fas fa-building"></i>
                                    {{ area.nombre }}
                                </span>
                            </div>
                        </div>

                        <!-- 🔥 NUEVO: Badges de sedes participantes (agrupadas) -->
                        <div v-if="pregunta.sedes_participantes && pregunta.sedes_participantes.length > 0" class="participantes-section">
                            <div class="participantes-label">Sedes:</div>
                            <div class="badges-container">
                                <span v-for="(sede, index) in pregunta.sedes_participantes" :key="`sede-${sede.id}`" class="participante-badge sede-badge">
                                    <i class="fas fa-map-marker-alt"></i>
                                    {{ sede.nombre }}
                                </span>
                            </div>
                        </div>

                        <div class="question-details">
                            
                            <span v-if="pregunta.tipo !== 'texto_libre'" class="options-count">
                                <i class="fas fa-list-ul"></i>
                                {{ pregunta.opciones_count || 0 }} opciones
                            </span>
                        </div>

                        <!-- 🔥 NUEVO: Indicador de subpreguntas -->
                        <div v-if="pregunta.opciones && pregunta.opciones.some(op => op.tiene_subpreguntas)" 
                             class="subpreguntas-indicator">
                            <i class="fas fa-layer-group"></i>
                            Tiene subpreguntas configuradas
                        </div>

                        <!-- 🔥 NUEVO: Indicador de rangos para indicadores -->
                        <div v-if="pregunta.tipo === 'indicador_0_10' && pregunta.subpreguntas_rango && pregunta.subpreguntas_rango.length" 
                             class="rangos-indicator">
                            <i class="fas fa-sliders-h"></i>
                            {{ pregunta.subpreguntas_rango.length }} rangos configurados
                        </div>
                    </div>

                    <div class="card-actions">
    <!-- Editar -->
    <button @click="editarPregunta(pregunta)" class="action-btn edit" title="Editar">
      <svg width="16" height="16" viewBox="0 0 512 512" fill="#007bff" xmlns="http://www.w3.org/2000/svg">
        <path d="M410.3 231l11.3-11.3-33.9-33.9-62.1-62.1L291.7 89.8l-11.3 11.3-22.6 22.6L58.6 322.9c-10.4 10.4-18 23.3-22.2 37.4L1 480.1c-2.5 8.4-.2 17.5 6.1 23.7s15.3 8.5 23.7 6.1l119.8-35.8c14.1-4.2 27-11.8 37.4-22.2L410.3 231zm0 0l-62.1-62.1L291.7 89.8l62.1 62.1 56.5 56.5z"/>
      </svg>
    </button>

    <!-- Subpreguntas -->
    <button @click="gestionarSubpreguntas(pregunta)" class="action-btn subpreguntas"
            :title="pregunta.opciones && pregunta.opciones.some(op => op.tiene_subpreguntas) ? 'Ver subpreguntas' : 'Gestionar subpreguntas'">
      <svg width="16" height="16" viewBox="0 0 512 512" fill="#28a745" xmlns="http://www.w3.org/2000/svg">
        <path d="M32 32c17.7 0 32 14.3 32 32V400c0 8.8 7.2 16 16 16H480c17.7 0 32 14.3 32 32s-14.3 32-32 32H80c-44.2 0-80-35.8-80-80V64C0 46.3 14.3 32 32 32zm128 64c0-17.7 14.3-32 32-32h192c17.7 0 32 14.3 32 32v96c0 17.7-14.3 32-32 32H192c-17.7 0-32-14.3-32-32V96zm0 160c0-17.7 14.3-32 32-32h192c17.7 0 32 14.3 32 32v96c0 17.7-14.3 32-32 32H192c-17.7 0-32-14.3-32-32V256z"/>
      </svg>
    </button>

    <!-- Activar / Desactivar -->
    <button @click="togglePreguntaStatus(pregunta)"
            :title="pregunta.is_active ? 'Desactivar' : 'Activar'"
            :class="['action-btn', pregunta.is_active ? 'deactivate' : 'activate']">
      <svg v-if="pregunta.is_active" width="16" height="16" viewBox="0 0 384 512" fill="#ffc107" xmlns="http://www.w3.org/2000/svg">
        <path d="M192 0C86 0 0 86 0 192v128c0 106 86 192 192 192s192-86 192-192V192C384 86 298 0 192 0z"/>
      </svg>
      <svg v-else width="16" height="16" viewBox="0 0 384 512" fill="#28a745" xmlns="http://www.w3.org/2000/svg">
        <path d="M73 39c-14.8-9.1-33.4-9.4-48.5-.9S0 62.6 0 80V432c0 17.4 9.4 33.4 24.5 41.9s33.7 8.1 48.5-.9L361 297c14.3-8.7 23-24.2 23-41s-8.7-32.2-23-41L73 39z"/>
      </svg>
    </button>

    <!-- Eliminar -->
    <button @click="eliminarPregunta(pregunta)" class="action-btn delete" title="Eliminar">
      <svg width="16" height="16" viewBox="0 0 448 512" fill="#dc3545" xmlns="http://www.w3.org/2000/svg">
        <path d="M135.2 17.7L128 32H32C14.3 32 0 46.3 0 64S14.3 96 32 96H416c17.7 0 32-14.3 32-32s-14.3-32-32-32H320l-7.2-14.3C307.4 6.8 296.3 0 288 0H160c-8.3 0-19.4 6.8-24.8 17.7zM32 128H416V448c0 35.3-28.7 64-64 64H96c-35.3 0-64-28.7-64-64V128zm96 64c-8.8 0-16 7.2-16 16V432c0 8.8 7.2 16 16 16s16-7.2 16-16V208c0-8.8-7.2-16-16-16zm96 0c-8.8 0-16 7.2-16 16V432c0 8.8 7.2 16 16 16s16-7.2 16-16V208c0-8.8-7.2-16-16-16zm96 0c-8.8 0-16 7.2-16 16V432c0 8.8 7.2 16 16 16s16-7.2 16-16V208c0-8.8-7.2-16-16-16z"/>
      </svg>
    </button>
  </div>
                </div>
            </div>
        </div>

        <!-- 🔥 NUEVO: Modal para Seleccionar Tipo de Calificación -->
        <div v-if="mostrarModalTipoCalificacion" class="modal-overlay" @click.self="cerrarModalTipoCalificacion">
            <div class="modal-container medium-modal">
                <div class="modal-header">
                    <h2 class="modal-title">
                        <i class="fas fa-tag"></i>
                        Seleccionar Tipo de Calificación
                    </h2>
                    <button @click="cerrarModalTipoCalificacion" class="btn-close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="modal-body">
                    <p class="modal-subtitle">
                        Elige el tipo de calificación que quieres crear
                    </p>
                    
                    <!-- Cards de tipos de calificación -->
                    <div v-if="cargandoTipos" class="loading-container">
                        <div class="loading-spinner"></div>
                        <p>Cargando tipos...</p>
                    </div>

                    <div v-else class="tipos-calificacion-grid" style="display: flex; flex-wrap: wrap; gap: 1.5rem; justify-content: center; margin-top: 2rem;">
                        <div 
                            v-for="tipo in tiposCalificacion" 
                            :key="tipo.id"
                            @click="seleccionarTipoCalificacion(tipo)"
                            class="tipo-card"
                            :class="{ 'seleccionado': tipoSeleccionado?.id === tipo.id }"
                            style="flex: 0 0 250px; min-width: 200px; max-width: 280px; background: white; border: 3px solid #e5e7eb; border-radius: 16px; padding: 2rem; text-align: center; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);"
                            @mouseenter="$event.target.style.borderColor='#6366f1'; $event.target.style.transform='translateY(-4px)'; $event.target.style.boxShadow='0 8px 24px rgba(99, 102, 241, 0.15)'"
                            @mouseleave="if (!tipoSeleccionado || tipoSeleccionado.id !== tipo.id) { $event.target.style.borderColor='#e5e7eb'; $event.target.style.transform='translateY(0)'; $event.target.style.boxShadow='0 2px 8px rgba(0, 0, 0, 0.1)' }"
                        >
                            <div class="tipo-icon" style="margin-bottom: 1rem; color: #6366f1; font-size: 3rem;">
                                <i v-if="tipo.codigo === 'csat'" class="fas fa-smile"></i>
                                <i v-else-if="tipo.codigo === 'nps'" class="fas fa-chart-line"></i>
                                <i v-else-if="tipo.codigo === 'fcr'" class="fas fa-hand-peace"></i>
                            </div>
                            <h3 class="tipo-nombre" style="font-size: 1.5rem; font-weight: 700; color: #1f2937; margin-bottom: 0.75rem;">{{ tipo.nombre }}</h3>
                            <p class="tipo-descripcion" style="font-size: 0.875rem; color: #6b7280; line-height: 1.5; margin: 0;">{{ tipo.descripcion }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal para Crear/Editar Pregunta Principal -->
        <div v-if="mostrarModalPregunta" class="modal-overlay" @click.self="cerrarModalPregunta">
            <div class="modal-container large-modal">
                <div class="modal-header">
                    <h2 class="modal-title">
                        <i :class="esEdicionPregunta ? 'fas fa-edit' : 'fas fa-plus'"></i>
                        {{ esEdicionPregunta ? 'Editar Pregunta' : 'Nueva Pregunta' }}
                    </h2>
                    <button @click="cerrarModalPregunta" class="modal-close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form @submit.prevent="guardarPregunta" class="modal-form">
                    <div class="form-section">
                        
                        <!-- 🔥 NUEVO: Tipo de Calificación Seleccionado -->
                        <div v-if="tipoSeleccionado" class="info-box tipo-seleccionado">
                            <i v-if="tipoSeleccionado.codigo === 'csat'" class="fas fa-smile"></i>
                            <i v-else-if="tipoSeleccionado.codigo === 'nps'" class="fas fa-chart-line"></i>
                            <i v-else-if="tipoSeleccionado.codigo === 'fcr'" class="fas fa-hand-peace"></i>
                            <strong>{{ tipoSeleccionado.nombre }}</strong> - {{ tipoSeleccionado.descripcion }}
                        </div>
                        
                        <!-- 🔥 NUEVO: Mostrar áreas participantes cuando se edita pregunta genérica -->
                        <div v-if="tipoSeleccionado && esEdicionPregunta" class="info-box areas-participantes">
                            <i class="fas fa-building"></i>
                            <strong>Áreas que usarán esta pregunta:</strong>
                            <div v-if="preguntaForm.areas_participantes && preguntaForm.areas_participantes.length > 0" class="areas-list">
                                <span v-for="area in uniqueAreas(preguntaForm.areas_participantes)" :key="`area-${area.id}`" class="area-tag">
                                    {{ area.nombre }}
                                </span>
                            </div>
                            <div v-else class="areas-list">
                                <span class="area-tag placeholder">Cargando áreas...</span>
                            </div>
                        </div>
                        <div class="form-group full-width">
                            <label class="form-label">
                                <i class="fas fa-question"></i>
                                Pregunta *
                            </label>
                            <textarea 
                                v-model="preguntaForm.pregunta" 
                                rows="3"
                                placeholder="Escribe la pregunta que los usuarios responderán..."
                                class="form-textarea"
                                required
                            ></textarea>
                        </div>
                        <div class="form-row">
                            <!-- NPS no usa niveles de calificación tradicionales, solo escala 0-10 -->
                            <div v-if="!tipoSeleccionado || tipoSeleccionado.codigo !== 'nps'" class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-layer-group"></i>
                                    Nivel de Calificación *
                                </label>
                                <select v-model="preguntaForm.niveles_calificacion_id" class="form-select" :required="!tipoSeleccionado || tipoSeleccionado.codigo !== 'nps'">
                                    <option value="">Seleccionar nivel</option>
                                    <option v-for="nivel in nivelesCalificacion" :key="nivel.id" :value="nivel.id">
                                        {{ nivel.nombre }}
                                    </option>
                                </select>
                            </div>

                            <!-- Para NPS, el tipo de pregunta está predefinido (Indicador 0-10) -->
                            <div v-if="tipoSeleccionado && tipoSeleccionado.codigo === 'nps'" class="form-group full-width">
                                <div class="info-badge" style="background: #e8f4f8; border: 1px solid #b3d7e8; padding: 1rem; border-radius: 8px; text-align: center;">
                                    <i class="fas fa-info-circle" style="color: #1890ff; font-size: 1.2rem;"></i>
                                    <p style="margin: 0.5rem 0 0 0; color: #666;">
                                        <strong>Tipo de Pregunta:</strong> Indicador 0-10 (Predefinido para NPS)
                                    </p>
                                </div>
                            </div>
                            <div v-else :class="['form-group', (!tipoSeleccionado || tipoSeleccionado.codigo !== 'nps') ? '' : 'full-width']">
                                <label class="form-label">
                                    <i class="fas fa-list"></i>
                                    Tipo de Pregunta *
                                </label>
                                <select v-model="preguntaForm.tipo" @change="cambiarTipoPregunta" class="form-select" required>
                                    <option value="">Seleccionar tipo</option>
                                    <option v-for="tipoDisponible in tiposPreguntaDisponibles" 
                                            :key="tipoDisponible.value" 
                                            :value="tipoDisponible.value">
                                        {{ tipoDisponible.label }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- 🔥 NUEVO: Selección múltiple de Áreas -->
                        <!-- Solo mostrar selección de áreas/sedes si NO es pregunta genérica (CSAT/NPS/FCR) -->
                        <div v-if="!tipoSeleccionado" class="form-group full-width">
                            <label class="form-label">
                                <i class="fas fa-building"></i>
                                Áreas para esta Calificación * <span class="text-sm">(selecciona una o más)</span>
                            </label>
                            <div class="checkbox-grid">
                                <label v-for="area in areas" :key="area.id" class="checkbox-item">
                                    <input 
                                        type="checkbox" 
                                        :value="area.id"
                                        v-model="areasSeleccionadas"
                                    >
                                    <span class="checkbox-label">{{ area.nombre }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- 🔥 Solo mostrar sedes si NO es pregunta genérica -->
                        <div v-if="!tipoSeleccionado" class="form-group full-width">
                            <label class="form-label">
                                <i class="fas fa-map-marker-alt"></i>
                                Sedes para esta Calificación * <span class="text-sm">(selecciona una o más)</span>
                            </label>
                            <div class="checkbox-grid">
                                <label v-for="sede in sedes" :key="sede.id" class="checkbox-item">
                                    <input 
                                        type="checkbox" 
                                        :value="sede.id"
                                        v-model="sedesSeleccionadas"
                                    >
                                    <span class="checkbox-label">{{ sede.nombre }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- Opciones de la pregunta principal -->
                        <div v-if="mostrarOpcionesPregunta" class="form-group full-width">
                            <label class="form-label">
                                <i class="fas fa-list-ul"></i>
                                Opciones de respuesta *
                                <span v-if="preguntaForm.tipo === 'opcion_unica_texto_libre'" class="badge-info">
                                    <i class="fas fa-info-circle"></i> La opción "Otro" se agregará automáticamente
                                </span>
                            </label>
                            <div class="opciones-container">
                                <div v-for="(opcion, index) in preguntaForm.opciones" 
                                    :key="index" 
                                    class="opcion-input-item"
                                    :class="{ 
                                        'opcion-otro': esOpcionOtro(opcion.texto),
                                        'ultima-opcion': preguntaForm.tipo === 'opcion_unica_texto_libre' && index === preguntaForm.opciones.length - 1
                                    }">
                                    <input 
                                        type="text"
                                        v-model="opcion.texto"
                                        :placeholder="`Opción ${index + 1}`"
                                        class="form-input opcion-input"
                                        :readonly="preguntaForm.tipo === 'opcion_unica_texto_libre' && esOpcionOtro(opcion.texto)"
                                        required
                                    >
                                    <button 
                                        type="button" 
                                        @click="eliminarOpcionPregunta(index)"
                                        class="btn-icon danger"
                                        :disabled="preguntaForm.opciones.length <= 2 || (preguntaForm.tipo === 'opcion_unica_texto_libre' && esOpcionOtro(opcion.texto))"
                                        :title="esOpcionOtro(opcion.texto) ? 'No se puede eliminar esta opción' : 'Eliminar opción'"
                                    >
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <div v-if="esOpcionOtro(opcion.texto)" class="opcion-otro-badge">
                                        <i class="fas fa-comment"></i> Campo de texto libre
                                    </div>
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
                                <p>Define preguntas específicas según la puntuación del usuario (escala 0-10)</p>
                            </div>

                            <div v-for="(rango, index) in configuracionRangos" :key="index" class="rango-item">
                                <div class="rango-header">
                                    <div class="rango-info">
                                        <h5>Rango {{ index + 1 }}</h5>
                                        <div class="rango-valores-inputs" style="display: flex; gap: 0.5rem; align-items: center; margin-top: 0.5rem;">
                                            <input 
                                                type="number" 
                                                v-model.number="rango.inicio"
                                                min="0"
                                                max="10"
                                                class="form-input"
                                                style="width: 80px;"
                                                placeholder="Inicio"
                                                @change="validarRango(index)"
                                            >
                                            <span>-</span>
                                            <input 
                                                type="number" 
                                                v-model.number="rango.fin"
                                                min="0"
                                                max="10"
                                                class="form-input"
                                                style="width: 80px;"
                                                placeholder="Fin"
                                                @change="validarRango(index)"
                                            >
                                        </div>
                                        <span class="rango-valores" style="display: block; margin-top: 0.5rem;">{{ rango.inicio }}-{{ rango.fin }}</span>
                                    </div>
                                    <div class="rango-toggle" style="display: flex; align-items: center; gap: 1rem;">
                                        <button 
                                            type="button" 
                                            @click="eliminarRango(index)"
                                            class="btn-icon danger full-width"
                                            title="Eliminar rango"
                                        >
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <label class="toggle-label">
                                            <input 
                                                type="checkbox" 
                                                v-model="rango.activo"
                                                class="toggle-input"
                                            >
                                            <span class="toggle-slider"></span>
                                            <span class="toggle-text">{{ rango.activo ? 'Activo' : 'Inactivo' }}</span>
                                        </label>
                                    </div>
                                </div>

                                <div v-if="rango.activo" class="rango-contenido">
                                    <div class="form-group">
                                        <label>Pregunta para este rango *</label>
                                        <textarea 
                                            v-model="rango.pregunta_texto"
                                            placeholder="Ej: ¿Podría decirnos el motivo por el que calificó su experiencia de esta manera?"
                                            rows="2"
                                            class="form-textarea"
                                            required
                                        ></textarea>
                                    </div>

                                    <div class="form-group">
                                        <label>Tipo de Pregunta *</label>
                                        <select v-model="rango.tipo" required class="form-select" @change="cambiarTipoRango(index)">
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
                                            <div v-for="(opcion, opcionIndex) in rango.opciones" :key="opcionIndex" class="opcion-input-item">
                                                <input 
                                                    v-model="opcion.texto"
                                                    type="text"
                                                    :placeholder="`Opción ${opcionIndex + 1}`"
                                                    required
                                                    class="form-input opcion-input"
                                                >
                                                <button 
                                                    type="button" 
                                                    @click="eliminarOpcionRango(index, opcionIndex)"
                                                    class="btn-icon danger"
                                                    :disabled="rango.opciones.length <= 2"
                                                    title="Eliminar opción"
                                                >
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                            <button type="button" @click="agregarOpcionRango(index)" class="btn btn-outline">
                                                <i class="fas fa-plus"></i> Agregar Opción
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Mensaje cuando no hay rangos -->
                            <div v-if="configuracionRangos.length === 0" class="empty-rangos" style="text-align: center; padding: 2rem; background: #f8f9fa; border-radius: 8px; margin-top: 1rem;">
                                <i class="fas fa-inbox" style="font-size: 3rem; color: #ccc; margin-bottom: 1rem;"></i>
                                <p style="color: #666; margin-bottom: 1rem;">
                                    No hay rangos configurados. Agrega rangos para personalizar las preguntas según la puntuación.
                                </p>
                            </div>

                            <!-- Botón para agregar nuevos rangos -->
                            <div class="agregar-rango-container" style="margin-top: 1rem;">
                                <button type="button" @click="agregarRango" class="btn btn-secondary">
                                    <i class="fas fa-plus"></i> Agregar Nuevo Rango
                                </button>
                            </div>

                            <div class="rangos-info">
                                <i class="fas fa-info-circle"></i>
                                <span v-if="totalRangosActivos > 0">
                                    {{ totalRangosActivos }} rango(s) configurado(s) de {{ configuracionRangos.length }}
                                </span>
                                <span v-else>
                                    Ningún rango configurado - El indicador funcionará sin preguntas adicionales
                                </span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="checkbox-container">
                                <input type="checkbox" v-model="preguntaForm.is_active">
                                <span class="checkbox-checkmark"></span>
                                <span class="checkbox-label">Pregunta activa</span>
                            </label>
                        </div>
                    </div>

                    <div class="modal-actions">
                        <button type="button" @click="cerrarModalPregunta" class="btn-secondary">
                            <i class="fas fa-times"></i>
                            Cancelar
                        </button>
                        <button type="submit" :disabled="guardandoPregunta" class="btn-primary">
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

        <!-- 🔥 NUEVO: Modal para Gestionar Subpreguntas -->
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
                        <div v-if="opcion.subpreguntas && opcion.subpreguntas.length" class="subpreguntas-list">
                            <div v-for="subpregunta in opcion.subpreguntas" :key="subpregunta.id" class="subpregunta-item">
                                <div class="subpregunta-content">
                                    <div class="subpregunta-texto">
                                        <strong>{{ subpregunta.pregunta_texto }}</strong>
                                        <span class="tipo-badge small">{{ getTipoTexto(subpregunta.tipo) }}</span>
                                    </div>
                                    <div v-if="subpregunta.opciones && subpregunta.opciones.length" class="subpregunta-opciones">
                                        <small>Opciones: {{ getOpcionesTexto(subpregunta) }}</small>
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

        <!-- 🔥 NUEVO: Modal para Agregar/Editar Subpregunta -->
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
                            <option value="opcion_unica_texto_libre">Opción Única con Texto Libre</option>
                            <option value="opcion_multiple">Opción Múltiple</option>
                            <option value="texto_libre">Texto Libre</option>
                            <option value="indicador_0_10">Indicador 0-10</option>
                        </select>
                    </div>

                    <!-- Opciones para subpreguntas con opciones -->
                    <div v-if="['opcion_unica', 'opcion_unica_texto_libre', 'opcion_multiple'].includes(subpreguntaForm.tipo)" class="form-group">
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
export default {
    name: 'PreguntasManagement',
    data() {
        return {
            subpreguntasCache: new Map(),
            cargandoSubpreguntas: false,
            timeoutSubpreguntas: null,
            sedes: [],
            preguntas: [],
            areas: [],
            nivelesCalificacion: [],
            loadingPreguntas: false,
            filters: {
                area_id: '',
                nivel_id: ''
            },
            
            // 🔥 NUEVOS ESTADOS PARA FUNCIONALIDAD AVANZADA
            mostrarModalPregunta: false,
            mostrarModalSubpreguntas: false,
            mostrarModalSubpregunta: false,
            esEdicionPregunta: false,
            guardandoPregunta: false,
            guardandoSubpregunta: false,
            preguntaSeleccionada: null,
            opcionSeleccionada: null,
            subpreguntaEditando: null,
            sedeActual: null,
            unsubscribe: null,

            // 🔥 NUEVO: Configuración de rangos para indicadores
            configuracionRangos: [],
            nuevoRango: {
                inicio: 0,
                fin: 6,
                activo: true,
                pregunta_texto: '',
                tipo: 'opcion_unica',
                opciones: [{ texto: '' }, { texto: '' }]
            },
            mostrandoConfiguracionRangos: false,
            // 🔥 NUEVO: Bandera para evitar loops en el watcher de opciones
            reorganizandoOpciones: false,

            // Formularios
            preguntaForm: {
                id: null,
                pregunta: '',
                tipo: 'opcion_unica',
                area_id: null,
                niveles_calificacion_id: '', // String vacío para mostrar placeholder
                sede_id: null,
                tipo_pregunta: null, // 🔥 Agregar tipo_pregunta
                areas_participantes: [], // 🔥 Agregar áreas participantes
                sede_participante: null, // 🔥 Agregar sede participante
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
            
            // 🔥 NUEVO: Tipos de calificación
            tiposCalificacion: [],
            cargandoTipos: false,
            mostrarModalTipoCalificacion: false,
            tipoSeleccionado: null,
            areasSeleccionadas: [],
            sedesSeleccionadas: []
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
            return this.configuracionRangos.filter(rango => rango.activo).length;
        },
        
        // 🔥 NUEVO: Filtrar tipos de pregunta disponibles según el tipo de calificación
        tiposPreguntaDisponibles() {
            const todosLosTipos = [
                { value: 'opcion_unica', label: 'Opción Única' },
                { value: 'opcion_multiple', label: 'Opción Múltiple' },
                { value: 'texto_libre', label: 'Texto Libre' },
                { value: 'indicador_0_10', label: 'Indicador 0-10' },
                { value: 'opcion_unica_texto_libre', label: 'Opción Única con Texto Libre' }
            ];
            
            // Si no hay tipo de calificación seleccionado, mostrar todos los tipos
            if (!this.tipoSeleccionado) {
                return todosLosTipos;
            }
            
            // Filtrar según el tipo de calificación
            const tipoCalificacion = this.tipoSeleccionado.codigo;
            
            switch(tipoCalificacion) {
                case 'nps':
                    // NPS debe tener el indicador 0-10 (y otras opciones si es necesario)
                    return todosLosTipos; // NPS puede usar todos los tipos
                    
                case 'csat':
                    // CSAT NO debe tener indicador 0-10 (es para caritas/emojis)
                    return todosLosTipos.filter(tipo => tipo.value !== 'indicador_0_10');
                    
                case 'fcr':
                    // FCR NO debe tener indicador 0-10 (es para manitas)
                    return todosLosTipos.filter(tipo => tipo.value !== 'indicador_0_10');
                    
                default:
                    return todosLosTipos;
            }
        }
    },
    
    watch: {
        // 🔥 Validar que el tipo de pregunta actual sea válido cuando cambie el tipo de calificación
        tipoSeleccionado(newVal, oldVal) {
            if (newVal && newVal !== oldVal && this.preguntaForm.tipo) {
                // Verificar si el tipo actual está disponible
                const tiposDisponibles = this.tiposPreguntaDisponibles.map(t => t.value);
                if (!tiposDisponibles.includes(this.preguntaForm.tipo)) {
                    // Si el tipo actual no está disponible, cambiar al primer tipo disponible
                    this.preguntaForm.tipo = tiposDisponibles[0];
                }
            }
        },
        
        // 🔥 NUEVO: Asegurar que "Otro" siempre esté al final en opcion_unica_texto_libre
        'preguntaForm.opciones': {
            handler(opciones) {
                // Evitar loops infinitos
                if (this.reorganizandoOpciones) return;
                
                if (this.preguntaForm.tipo === 'opcion_unica_texto_libre' && opciones && opciones.length > 0) {
                    const indiceOtro = opciones.findIndex(op => this.esOpcionOtro(op.texto));
                    
                    if (indiceOtro !== -1 && indiceOtro !== opciones.length - 1) {
                        // Activar bandera para evitar loop
                        this.reorganizandoOpciones = true;
                        
                        // Mover "Otro" al final
                        const opcionOtro = this.preguntaForm.opciones.splice(indiceOtro, 1)[0];
                        this.preguntaForm.opciones.push(opcionOtro);
                        
                        // Desactivar bandera después de un pequeño delay
                        this.$nextTick(() => {
                            this.reorganizandoOpciones = false;
                        });
                    }
                }
            },
            deep: true
        }
    },

    async mounted() {
        console.log('🚀 PreguntasManagement montado - INICIANDO SISTEMA AVANZADO');
        
        // CARGAR DATOS INICIALES
        await this.cargarSedes();
        await this.cargarAreas();
        await this.cargarNivelesCalificacion();
        
        // SUSCRIBIRSE A CAMBIOS DE SEDE
        this.suscribirACambiosDeSede();
        
        // CARGAR PREGUNTAS INICIALES
        await this.cargarPreguntas();
    },

    beforeUnmount() {
        if (this.unsubscribe) {
            this.unsubscribe();
        }
    },

    methods: {
        // ✅ MÉTODOS EXISTENTES (actualizados)
        suscribirACambiosDeSede() {
            if (window.SedeStore) {
                this.unsubscribe = window.SedeStore.subscribe((nuevaSede) => {
                    console.log('🔄 PreguntasManagement recibió cambio de sede:', nuevaSede);
                    this.sedeActual = nuevaSede;
                    this.cargarPreguntas();
                });
                this.sedeActual = window.SedeStore.sedeActual;
            }
            
            if (window.EventBus) {
                window.EventBus.on('sede-cambiada', (sede) => {
                    console.log('📡 PreguntasManagement recibió evento de sede:', sede);
                    this.sedeActual = sede;
                    this.cargarPreguntas();
                });
            }
        },

        async cargarSedes() {
            try {
                const response = await fetch('/api/sedes');
                if (response.ok) {
                    this.sedes = await response.json();
                }
            } catch (error) {
                console.error('❌ Error cargando sedes:', error);
            }
        },

        async cargarAreas() {
            try {
                const response = await fetch('/api/areas');
                if (response.ok) {
                    this.areas = await response.json();
                }
            } catch (error) {
                console.error('❌ Error loading areas:', error);
            }
        },

        async cargarNivelesCalificacion() {
            try {
                const response = await fetch('/api/niveles-calificacion');
                if (response.ok) {
                    this.nivelesCalificacion = await response.json();
                }
            } catch (error) {
                console.error('❌ Error loading niveles calificacion:', error);
            }
        },

        async cargarPreguntas() {
            this.loadingPreguntas = true;
            try {
                let url = '/api/preguntas';
                const params = new URLSearchParams();
                
                if (this.filters.area_id) params.append('area_id', this.filters.area_id);
                if (this.filters.nivel_id) params.append('nivel_id', this.filters.nivel_id);
                
                const sedeId = this.sedeActual ? this.sedeActual.id : null;
                if (sedeId) {
                    params.append('sede_id', sedeId);
                }
                
                if (params.toString()) url += '?' + params.toString();
                
                const response = await fetch(url, { credentials: 'include' });
                
                if (response.ok) {
                    this.preguntas = await response.json();
                    
                    // 🔥 NUEVO: Cargar subpreguntas para cada pregunta
                    for (let pregunta of this.preguntas) {
                        await this.cargarSubpreguntasParaPregunta(pregunta);
                    }
                } else {
                    console.error('❌ Error cargando preguntas:', response.status);
                    alert('Error al cargar las preguntas');
                }
            } catch (error) {
                console.error('❌ Error loading preguntas:', error);
                alert('Error al cargar las preguntas: ' + error.message);
            } finally {
                this.loadingPreguntas = false;
            }
        },

        // 🔥 NUEVO: Cargar subpreguntas para todas las opciones de una pregunta
        // ✅ AGREGAR EN data()
data() {
    return {
        subpreguntasCache: new Map(), // Cache para evitar peticiones duplicadas
        cargandoSubpreguntas: false,
        timeoutSubpreguntas: null
    }
},

// ✅ REEMPLAZAR método problemático
async cargarSubpreguntasParaPregunta(pregunta) {
    // ⏰ DEBOUNCE: Esperar 100ms antes de cargar
    if (this.timeoutSubpreguntas) {
        clearTimeout(this.timeoutSubpreguntas);
    }
    
    this.timeoutSubpreguntas = setTimeout(async () => {
        if (this.cargandoSubpreguntas) return;
        
        this.cargandoSubpreguntas = true;
        console.log('🔍 Cargando subpreguntas para pregunta:', pregunta.id);
        
        const opcionesConSubpreguntas = pregunta.opciones.filter(op => op.tiene_subpreguntas);
        
        // ✅ Cargar solo las necesarias, con cache
        for (const opcion of opcionesConSubpreguntas) {
            await this.cargarSubpreguntasParaOpcionConCache(opcion.id);
        }
        
        this.cargandoSubpreguntas = false;
    }, 100);
},

// ✅ MÉTODO CON CACHE
async cargarSubpreguntasParaOpcionConCache(opcionId) {
    // Verificar cache primero
    if (this.subpreguntasCache.has(opcionId)) {
        return this.subpreguntasCache.get(opcionId);
    }
    
    try {
        console.log('📡 Cargando subpreguntas para opción:', opcionId);
        const response = await fetch(`/api/subpreguntas/${opcionId}`);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        
        const subpreguntas = await response.json();
        
        // Guardar en cache
        this.subpreguntasCache.set(opcionId, subpreguntas);
        
        return subpreguntas;
    } catch (error) {
        console.error('❌ Error cargando subpreguntas:', error);
        return [];
    }
},

        // 🔥 NUEVO: Cargar subpreguntas reales desde la API
        async cargarSubpreguntasParaOpcion(opcion) {
            try {
                console.log('🔍 Cargando subpreguntas REALES para opción:', opcion.id);
                
                const response = await fetch(`/api/subpreguntas/${opcion.id}`);
                if (response.ok) {
                    const subpreguntas = await response.json();
                    
                    // Procesar las opciones de las subpreguntas
                    opcion.subpreguntas = subpreguntas.map(sp => {
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

        getSedeName(sedeId) {
            const sede = this.sedes.find(s => s.id === sedeId);
            return sede ? sede.nombre : 'N/A';
        },

        getAreaName(areaId) {
            const area = this.areas.find(a => a.id === areaId);
            return area ? area.nombre : 'N/A';
        },

        getNivelName(nivelId) {
            const nivel = this.nivelesCalificacion.find(n => n.id === nivelId);
            return nivel ? nivel.nombre : 'N/A';
        },

        // 🔥 NUEVO: Agrupar áreas únicas por nombre
        uniqueAreas(areas) {
            if (!areas || areas.length === 0) return [];
            const seen = new Set();
            return areas.filter(area => {
                if (seen.has(area.nombre)) {
                    return false;
                }
                seen.add(area.nombre);
                return true;
            });
        },
        
        // 🔥 NUEVO: Obtener badge class para tipo de calificación
        getTipoBadgeClass(tipo) {
            const classes = {
                'csat': 'tipo-badge-csat',
                'nps': 'tipo-badge-nps',
                'fcr': 'tipo-badge-fcr'
            };
            return classes[tipo] || 'tipo-badge-default';
        },
        
        // 🔥 NUEVO: Obtener icono para tipo de calificación
        getTipoIcono(tipo) {
            const iconos = {
                'csat': 'fas fa-smile',
                'nps': 'fas fa-chart-line',
                'fcr': 'fas fa-hand-peace'
            };
            return iconos[tipo] || 'fas fa-question';
        },
        
        // 🔥 NUEVO: Obtener nombre para tipo de calificación
        getTipoNombre(tipo) {
            const nombres = {
                'csat': 'CSAT',
                'nps': 'NPS',
                'fcr': 'FCR'
            };
            return nombres[tipo] || tipo;
        },

        getTipoText(tipo) {
            const tipos = {
                'opcion_unica': 'Opción Única',
                'opcion_multiple': 'Opción Múltiple',
                'texto_libre': 'Texto Libre',
                'indicador_0_10': 'Indicador 0-10',
                'opcion_unica_texto_libre': 'Opción Única con Texto Libre'
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

        getTipoIcon(tipo) {
            const iconos = {
                'opcion_unica': 'fas fa-dot-circle',
                'opcion_multiple': 'fas fa-check-square',
                'texto_libre': 'fas fa-keyboard',
                'indicador_0_10': 'fas fa-sliders-h',
                'opcion_unica_texto_libre': 'fas fa-comment-dots'
            };
            return iconos[tipo] || 'fas fa-question';
        },

        /**
         * 🔥 NUEVO: Obtener texto del tipo de pregunta
         */
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

        /**
         * 🔥 NUEVO: Cambiar tipo de subpregunta
         */
        cambiarTipoSubpregunta() {
            if (!['opcion_unica', 'opcion_unica_texto_libre', 'opcion_multiple'].includes(this.subpreguntaForm.tipo)) {
                this.subpreguntaForm.opciones = [];
            } else if (this.subpreguntaForm.opciones.length === 0) {
                this.subpreguntaForm.opciones = [
                    { texto: '' },
                    { texto: '' }
                ];
            }
        },

        /**
         * 🔥 NUEVO: Agregar opción a subpregunta
         */
        agregarOpcionSubpregunta() {
            this.subpreguntaForm.opciones.push({ texto: '' });
        },

        /**
         * 🔥 NUEVO: Eliminar opción de subpregunta
         */
        eliminarOpcionSubpregunta(index) {
            if (this.subpreguntaForm.opciones.length > 2) {
                this.subpreguntaForm.opciones.splice(index, 1);
            }
        },


        // 🔥 NUEVO: Mostrar modal de crear pregunta
        async mostrarModalCrearPregunta() {
            this.esEdicionPregunta = false;
            this.mostrarModalTipoCalificacion = true;
            await this.cargarTiposCalificacion();
        },
        
        async cargarTiposCalificacion() {
            this.cargandoTipos = true;
            try {
                const response = await fetch('/api/tipos-calificacion');
                if (response.ok) {
                    this.tiposCalificacion = await response.json();
                } else {
                    console.error('Error cargando tipos de calificación');
                }
            } catch (error) {
                console.error('Error:', error);
            } finally {
                this.cargandoTipos = false;
            }
        },
        
        seleccionarTipoCalificacion(tipo) {
            this.tipoSeleccionado = tipo;
            this.cerrarModalTipoCalificacion();
            this.abrirFormularioCreacion();
        },
        
        cerrarModalTipoCalificacion() {
            this.mostrarModalTipoCalificacion = false;
            // NO resetear tipoSeleccionado aquí, se necesita para mantener el estado
        },
        
        abrirFormularioCreacion() {
            // Determinar tipo de pregunta por defecto según el tipo de calificación
            let tipoPreguntaDefault = 'opcion_unica';
            let opcionesDefault = [{ texto: '' }, { texto: '' }];
            let nivelDefault = ''; // Por defecto vacío
            
            if (this.tipoSeleccionado?.codigo === 'nps') {
                tipoPreguntaDefault = 'indicador_0_10';
                opcionesDefault = [];
                // Para NPS, usar el primer nivel disponible (se usará internamente pero no se muestra en el formulario)
                nivelDefault = this.nivelesCalificacion.length > 0 ? this.nivelesCalificacion[0].id : '';
                
                // Inicializar configuración de rangos para NPS - empezar sin rangos
                this.configuracionRangos = [];
                this.mostrandoConfiguracionRangos = true;
            } else if (this.tipoSeleccionado?.codigo === 'fcr') {
                tipoPreguntaDefault = 'opcion_unica';
                opcionesDefault = [
                    { texto: 'Sí' },
                    { texto: 'No' }
                ];
                nivelDefault = this.nivelesCalificacion.length > 0 ? this.nivelesCalificacion[0].id : '';
            }
            
            this.preguntaForm = {
                id: null,
                pregunta: '',
                tipo: tipoPreguntaDefault,
                tipo_pregunta: this.tipoSeleccionado?.codigo || null,
                area_id: null,
                niveles_calificacion_id: nivelDefault,
                sede_id: this.sedeActual ? this.sedeActual.id : null,
                opciones: opcionesDefault,
                is_active: true
            };
            this.areasSeleccionadas = [];
            this.sedesSeleccionadas = [];
            this.mostrarModalPregunta = true;
        },

        // 🔥 NUEVO: Editar pregunta
        async editarPregunta(pregunta) {
            this.esEdicionPregunta = true;
            
            // 🔥 Detectar si es pregunta genérica (CSAT/NPS/FCR)
            const esPreguntaGenerica = pregunta.tipo_pregunta && ['csat', 'nps', 'fcr'].includes(pregunta.tipo_pregunta);
            
            // Si es pregunta genérica, establecer tipoSeleccionado con nombre y descripción
            if (esPreguntaGenerica) {
                const nombresMap = {
                    'csat': 'CSAT',
                    'nps': 'NPS',
                    'fcr': 'FCR'
                };
                const descripcionesMap = {
                    'csat': 'Customer Satisfaction - Satisfacción del cliente',
                    'nps': 'Net Promoter Score - Recomendación',
                    'fcr': 'First Contact Resolution - Resolución a primer contacto'
                };
                this.tipoSeleccionado = {
                    codigo: pregunta.tipo_pregunta,
                    nombre: nombresMap[pregunta.tipo_pregunta] || pregunta.tipo_pregunta,
                    descripcion: descripcionesMap[pregunta.tipo_pregunta] || ''
                };
            } else {
                this.tipoSeleccionado = null;
            }
            
            // Cargar datos de la pregunta
            let opciones = [];
            if (pregunta.tipo !== 'texto_libre' && pregunta.tipo !== 'indicador_0_10' && pregunta.opciones) {
                opciones = pregunta.opciones.map(op => ({ texto: op.opcion }));
            }
            if (opciones.length === 0 && (pregunta.tipo === 'opcion_unica' || pregunta.tipo === 'opcion_multiple')) {
                opciones = [{ texto: '' }, { texto: '' }];
            }

            // 🔥 NUEVO: Si es opcion_unica_texto_libre, asegurar que "Otro" esté al final
            if (pregunta.tipo === 'opcion_unica_texto_libre' && opciones.length > 0) {
                const indiceOtro = opciones.findIndex(op => 
                    this.esOpcionOtro(op.texto)
                );
                
                if (indiceOtro !== -1 && indiceOtro !== opciones.length - 1) {
                    // Mover "Otro" al final
                    const opcionOtro = opciones.splice(indiceOtro, 1)[0];
                    opciones.push(opcionOtro);
                }
            }

            this.preguntaForm = {
                id: pregunta.id,
                pregunta: pregunta.pregunta,
                tipo: pregunta.tipo,
                tipo_pregunta: pregunta.tipo_pregunta || null, // 🔥 Agregar tipo_pregunta
                area_id: pregunta.area_id,
                niveles_calificacion_id: pregunta.niveles_calificacion_id,
                sede_id: pregunta.sede_id,
                opciones: opciones,
                is_active: pregunta.is_active,
                // 🔥 Agregar áreas participantes para mostrar en el modal
                areas_participantes: pregunta.areas_participantes || [],
                sede_participante: pregunta.sede_participante || null
            };

            // 🔥 NUEVO: Cargar configuración de rangos si es indicador
            if (pregunta.tipo === 'indicador_0_10') {
                await this.cargarConfiguracionRangos(pregunta.id);
            } else {
                this.resetearConfiguracionRangos();
            }

            this.mostrarModalPregunta = true;
        },

        // 🔥 NUEVO: Cargar configuración de rangos existente
        async cargarConfiguracionRangos(preguntaId) {
            try {
                const response = await fetch(`/api/preguntas/${preguntaId}/rangos`);
                if (response.ok) {
                    const rangosExistentes = await response.json();
                    // Procesar rangos existentes...
                    this.mostrandoConfiguracionRangos = true;
                }
            } catch (error) {
                console.error('Error cargando configuración de rangos:', error);
                this.resetearConfiguracionRangos();
            }
        },

        // 🔥 NUEVO: Cambiar tipo de pregunta
        cambiarTipoPregunta() {
            // Validar si el tipo actual está disponible según el tipo de calificación
            const tiposDisponibles = this.tiposPreguntaDisponibles.map(t => t.value);
            if (!tiposDisponibles.includes(this.preguntaForm.tipo)) {
                // Si el tipo actual no está disponible, cambiar al primer tipo disponible
                this.preguntaForm.tipo = tiposDisponibles[0];
            }
            
            if (!this.mostrarOpcionesPregunta) {
                this.preguntaForm.opciones = [];
            } else if (this.preguntaForm.opciones.length === 0) {
                this.preguntaForm.opciones = [
                    { texto: '' },
                    { texto: '' }
                ];
            }
            
            // Mostrar/ocultar configuración de rangos
            this.mostrandoConfiguracionRangos = this.esPreguntaIndicador;
            
            // Resetear configuración si no es indicador
            if (!this.esPreguntaIndicador) {
                this.resetearConfiguracionRangos();
            }
            
            // Para opcion_unica_texto_libre, asegurar opción "Otro" al final
            if (this.preguntaForm.tipo === 'opcion_unica_texto_libre') {
                // Buscar índice de la opción "Otro"
                const indiceOtro = this.preguntaForm.opciones.findIndex(op => 
                    this.esOpcionOtro(op.texto)
                );
                
                if (indiceOtro === -1) {
                    // Si no existe "Otro", agregarlo al final
                    this.preguntaForm.opciones.push({ texto: 'Otro - especifique' });
                } else if (indiceOtro !== this.preguntaForm.opciones.length - 1) {
                    // Si existe pero no está al final, moverlo al final
                    const opcionOtro = this.preguntaForm.opciones.splice(indiceOtro, 1)[0];
                    this.preguntaForm.opciones.push(opcionOtro);
                }
            }
        },

        // 🔥 NUEVO: Agregar opción a pregunta
        agregarOpcionPregunta() {
            // Si es tipo opcion_unica_texto_libre, asegurar que "Otro" esté al final
            if (this.preguntaForm.tipo === 'opcion_unica_texto_libre') {
                // Buscar índice de la opción "Otro"
                const indiceOtro = this.preguntaForm.opciones.findIndex(op => 
                    this.esOpcionOtro(op.texto)
                );
                
                let opcionOtro = null;
                if (indiceOtro !== -1) {
                    // Guardar la opción "Otro" y removerla
                    opcionOtro = this.preguntaForm.opciones.splice(indiceOtro, 1)[0];
                }
                
                // Agregar la nueva opción
                this.preguntaForm.opciones.push({ texto: '' });
                
                // Si había opción "Otro", agregarla al final
                if (opcionOtro) {
                    this.preguntaForm.opciones.push(opcionOtro);
                }
            } else {
                // Para otros tipos, agregar normalmente
                this.preguntaForm.opciones.push({ texto: '' });
            }
        },

        // 🔥 NUEVO: Eliminar opción de pregunta
        eliminarOpcionPregunta(index) {
            if (this.preguntaForm.opciones.length > 2) {
                // Verificar si la opción a eliminar es "Otro"
                const esOtraOpcionOtro = this.esOpcionOtro(this.preguntaForm.opciones[index].texto);
                
                if (!esOtraOpcionOtro) {
                    // Solo eliminar si NO es "Otro"
                    this.preguntaForm.opciones.splice(index, 1);
                    
                    // 🔥 NUEVO: Si es tipo opcion_unica_texto_libre, asegurar que "Otro" esté al final
                    if (this.preguntaForm.tipo === 'opcion_unica_texto_libre') {
                        const indiceOtro = this.preguntaForm.opciones.findIndex(op => 
                            this.esOpcionOtro(op.texto)
                        );
                        
                        if (indiceOtro !== -1 && indiceOtro !== this.preguntaForm.opciones.length - 1) {
                            // Mover "Otro" al final
                            const opcionOtro = this.preguntaForm.opciones.splice(indiceOtro, 1)[0];
                            this.preguntaForm.opciones.push(opcionOtro);
                        }
                    }
                }
            }
        },

        // 🔥 NUEVO: Validar formulario
        validarFormulario() {
            if (!this.preguntaForm.pregunta.trim()) {
                alert('Por favor ingresa la pregunta');
                return false;
            }
            // 🔥 NUEVO: Solo validar áreas y sedes si NO es pregunta genérica (CSAT/NPS/FCR)
            if (!this.tipoSeleccionado && (!this.areasSeleccionadas || this.areasSeleccionadas.length === 0)) {
                alert('Por favor selecciona al menos un área');
                return false;
            }
            if (!this.tipoSeleccionado && (!this.sedesSeleccionadas || this.sedesSeleccionadas.length === 0)) {
                alert('Por favor selecciona al menos una sede');
                return false;
            }
            if (!this.preguntaForm.niveles_calificacion_id) {
                alert('Por favor selecciona un nivel de calificación');
                return false;
            }
            if (!this.preguntaForm.tipo) {
                alert('Por favor selecciona un tipo de pregunta');
                return false;
            }

            // Validar opciones para tipos que las requieren
            if (this.preguntaForm.tipo !== 'texto_libre' && 
                this.preguntaForm.tipo !== 'indicador_0_10' &&
                this.preguntaForm.tipo !== 'opcion_unica_texto_libre') {
                const opcionesValidas = this.preguntaForm.opciones.filter(op => op.texto.trim() !== '');
                if (opcionesValidas.length < 2) {
                    alert('Debe haber al menos 2 opciones válidas');
                    return false;
                }
            }

            // Validación especial para opción única con texto libre
            if (this.preguntaForm.tipo === 'opcion_unica_texto_libre') {
                const opcionesValidas = this.preguntaForm.opciones.filter(op => op.texto && op.texto.trim() !== '');
                if (opcionesValidas.length < 2) {
                    alert('Debe haber al menos 2 opciones válidas');
                    return false;
                }
            }

            return true;
        },

        // 🔥 NUEVO: Guardar pregunta
        async guardarPregunta() {
            if (!this.validarFormulario()) {
                return;
            }

            this.guardandoPregunta = true;
            try {
                // 🔥 NUEVO: Guardar la pregunta con tipo de calificación
                const datos = {
                    pregunta: this.preguntaForm.pregunta,
                    tipo: this.preguntaForm.tipo,
                    tipo_pregunta: this.preguntaForm.tipo_pregunta || null,
                    niveles_calificacion_id: this.preguntaForm.niveles_calificacion_id,
                    is_active: this.preguntaForm.is_active,
                    opciones: this.mostrarOpcionesPregunta 
                        ? this.preguntaForm.opciones.map(op => op.texto).filter(texto => texto.trim())
                        : [],
                    // Solo enviar configuracion_rangos si es pregunta indicador Y tiene rangos activos
                    configuracion_rangos: this.esPreguntaIndicador && this.totalRangosActivos > 0 ? this.configuracionRangos : []
                };
                
                // 🔥 Agregar area_id y sede_id solo si NO es pregunta genérica (solo para creación)
                if (!this.tipoSeleccionado && !this.esEdicionPregunta) {
                    datos.area_id = this.areasSeleccionadas[0] || null;
                    datos.sede_id = this.sedesSeleccionadas[0] || null;
                    datos.areas_id = this.areasSeleccionadas;
                    datos.sedes_id = this.sedesSeleccionadas;
                }

                // Para opcion_unica_texto_libre, asegurar que tenga opción "Otro"
                if (this.preguntaForm.tipo === 'opcion_unica_texto_libre') {
                    const tieneOpcionOtro = datos.opciones.some(opcion => 
                        opcion.toLowerCase().includes('otro') || opcion.toLowerCase().includes('especifique')
                    );
                    
                    if (!tieneOpcionOtro) {
                        datos.opciones.push('Otro - especifique');
                    }
                }

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
                        const errorData = JSON.parse(errorText);
                        errorMessage = errorData.message || errorData.error || errorMessage;
                    } catch {
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

        // 🔥 NUEVO: Gestionar subpreguntas
        gestionarSubpreguntas(pregunta) {
            this.preguntaSeleccionada = pregunta;
            this.mostrarModalSubpreguntas = true;
        },

        // 🔥 NUEVO: Agregar subpregunta
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

        // 🔥 NUEVO: Editar subpregunta
        editarSubpregunta(subpregunta, opcion) {
            this.opcionSeleccionada = opcion;
            this.subpreguntaEditando = subpregunta;
            
            // 🔥 CORRECCIÓN: Procesar correctamente las opciones
            let opcionesArray = [];
            if (subpregunta.opciones) {
                if (Array.isArray(subpregunta.opciones)) {
                    opcionesArray = subpregunta.opciones.map(op => ({ texto: op }));
                } else if (typeof subpregunta.opciones === 'string') {
                    try {
                        const parsed = JSON.parse(subpregunta.opciones);
                        opcionesArray = Array.isArray(parsed) ? parsed.map(op => ({ texto: op })) : [];
                    } catch (e) {
                        console.warn('Error parseando opciones de subpregunta:', e);
                        opcionesArray = [];
                    }
                }
            }
            
            // Asegurar al menos 2 opciones si es necesario
            if (['opcion_unica', 'opcion_multiple'].includes(subpregunta.tipo) && opcionesArray.length < 2) {
                opcionesArray = [
                    { texto: '' },
                    { texto: '' }
                ];
            }

            this.subpreguntaForm = {
                id: subpregunta.id,
                pregunta_texto: subpregunta.pregunta_texto,
                tipo: subpregunta.tipo,
                opciones: opcionesArray
            };
            
            this.mostrarModalSubpregunta = true;
        },

        // 🔥 NUEVO: Guardar subpregunta
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

                console.log('📤 Guardando subpregunta:', datos);

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
                console.log('✅ Subpregunta guardada:', result);

                // Actualizar el estado local
                if (this.opcionSeleccionada) {
                    await this.actualizarEstadoSubpreguntas(this.opcionSeleccionada.id, true);
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

        /**
         * 🔥 NUEVO: Obtener texto de opciones para mostrar
         */
        getOpcionesTexto(subpregunta) {
            if (!subpregunta.opciones) return '';
            
            let opcionesArray = [];
            if (Array.isArray(subpregunta.opciones)) {
                opcionesArray = subpregunta.opciones;
            } else if (typeof subpregunta.opciones === 'string') {
                try {
                    const parsed = JSON.parse(subpregunta.opciones);
                    opcionesArray = Array.isArray(parsed) ? parsed : [];
                } catch (e) {
                    console.warn('Error parseando opciones:', e);
                    opcionesArray = [];
                }
            }
            
            return opcionesArray.join(', ');
        },

        // 🔥 NUEVO: Actualizar estado de subpreguntas
        async actualizarEstadoSubpreguntas(opcionId, tieneSubpreguntas) {
            try {
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
                    if (this.opcionSeleccionada) {
                        this.opcionSeleccionada.tiene_subpreguntas = tieneSubpreguntas;
                    }
                }
            } catch (error) {
                console.error('Error actualizando estado de subpreguntas:', error);
            }
        },

        // 🔥 NUEVO: Eliminar subpregunta
        async eliminarSubpregunta(subpregunta) {
            if (confirm('¿Estás seguro de eliminar esta subpregunta?')) {
                try {
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

        // 🔥 NUEVO: Cambiar tipo de subpregunta
        cambiarTipoSubpregunta() {
            if (!['opcion_unica', 'opcion_unica_texto_libre', 'opcion_multiple'].includes(this.subpreguntaForm.tipo)) {
                this.subpreguntaForm.opciones = [];
            } else if (this.subpreguntaForm.opciones.length === 0) {
                this.subpreguntaForm.opciones = [
                    { texto: '' },
                    { texto: '' }
                ];
            }
        },

        // 🔥 NUEVO: Agregar opción a subpregunta
        agregarOpcionSubpregunta() {
            this.subpreguntaForm.opciones.push({ texto: '' });
        },

        // 🔥 NUEVO: Eliminar opción de subpregunta
        eliminarOpcionSubpregunta(index) {
            if (this.subpreguntaForm.opciones.length > 2) {
                this.subpreguntaForm.opciones.splice(index, 1);
            }
        },

        // 🔥 NUEVO: Resetear configuración de rangos
        resetearConfiguracionRangos() {
            this.configuracionRangos = [];
            this.mostrandoConfiguracionRangos = false;
        },

        // 🔥 NUEVO: Agregar nuevo rango
        agregarRango() {
            const nuevoRango = {
                inicio: 0,
                fin: 6,
                activo: true,
                pregunta_texto: '',
                tipo: 'opcion_unica',
                opciones: [{ texto: '' }, { texto: '' }]
            };
            this.configuracionRangos.push(nuevoRango);
        },

        // 🔥 NUEVO: Eliminar rango
        eliminarRango(index) {
            this.configuracionRangos.splice(index, 1);
        },

        // 🔥 NUEVO: Validar rango (inicio debe ser menor que fin)
        validarRango(index) {
            const rango = this.configuracionRangos[index];
            if (rango.inicio > rango.fin) {
                rango.fin = rango.inicio;
            }
        },

        // 🔥 NUEVO: Cuando cambia el tipo de un rango
        cambiarTipoRango(index) {
            const rango = this.configuracionRangos[index];
            
            if (!['opcion_unica', 'opcion_multiple', 'opcion_unica_texto_libre'].includes(rango.tipo)) {
                rango.opciones = [];
            } else if (rango.opciones.length === 0) {
                rango.opciones = [{ texto: '' }, { texto: '' }];
            }
            
            // Para opcion_unica_texto_libre, asegurar opción "Otro" al final
            if (rango.tipo === 'opcion_unica_texto_libre') {
                const indiceOtro = rango.opciones.findIndex(op => 
                    this.esOpcionOtro(op.texto)
                );
                
                if (indiceOtro === -1) {
                    // Si no existe "Otro", agregarlo al final
                    rango.opciones.push({ texto: 'Otro - especifique' });
                } else if (indiceOtro !== rango.opciones.length - 1) {
                    // Si existe pero no está al final, moverlo al final
                    const opcionOtro = rango.opciones.splice(indiceOtro, 1)[0];
                    rango.opciones.push(opcionOtro);
                }
            }
        },

        // 🔥 NUEVO: Agregar opción a un rango
        agregarOpcionRango(index) {
            const rango = this.configuracionRangos[index];
            
            // Si es tipo opcion_unica_texto_libre, asegurar que "Otro" esté al final
            if (rango.tipo === 'opcion_unica_texto_libre') {
                const indiceOtro = rango.opciones.findIndex(op => 
                    this.esOpcionOtro(op.texto)
                );
                
                let opcionOtro = null;
                if (indiceOtro !== -1) {
                    // Guardar la opción "Otro" y removerla
                    opcionOtro = rango.opciones.splice(indiceOtro, 1)[0];
                }
                
                // Agregar la nueva opción
                rango.opciones.push({ texto: '' });
                
                // Si había opción "Otro", agregarla al final
                if (opcionOtro) {
                    rango.opciones.push(opcionOtro);
                }
            } else {
                // Para otros tipos, agregar normalmente
                rango.opciones.push({ texto: '' });
            }
        },

        // 🔥 NUEVO: Eliminar opción de un rango
        eliminarOpcionRango(index, opcionIndex) {
            const opciones = this.configuracionRangos[index].opciones;
            const rango = this.configuracionRangos[index];
            
            if (opciones.length > 2) {
                // Verificar si la opción a eliminar es "Otro"
                const esOtraOpcionOtro = this.esOpcionOtro(opciones[opcionIndex].texto);
                
                if (!esOtraOpcionOtro) {
                    // Solo eliminar si NO es "Otro"
                    opciones.splice(opcionIndex, 1);
                    
                    // 🔥 NUEVO: Si es tipo opcion_unica_texto_libre, asegurar que "Otro" esté al final
                    if (rango.tipo === 'opcion_unica_texto_libre') {
                        const indiceOtro = opciones.findIndex(op => 
                            this.esOpcionOtro(op.texto)
                        );
                        
                        if (indiceOtro !== -1 && indiceOtro !== opciones.length - 1) {
                            // Mover "Otro" al final
                            const opcionOtro = opciones.splice(indiceOtro, 1)[0];
                            opciones.push(opcionOtro);
                        }
                    }
                }
            }
        },


        esOpcionOtro(opcionTexto) {
            if (!opcionTexto) return false;
            return opcionTexto.toLowerCase().includes('otro') || opcionTexto.toLowerCase().includes('especifique');
        },

        async togglePreguntaStatus(pregunta) {
            if (!confirm(`¿Estás seguro de ${pregunta.is_active ? 'desactivar' : 'activar'} esta pregunta?`)) {
                return;
            }
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
                } else {
                    throw new Error('Error al cambiar estado');
                }
            } catch (error) {
                console.error('Error toggling pregunta:', error);
                this.mostrarMensaje('Error al cambiar el estado de la pregunta', 'error');
            }
        },

        // ✅ MÉTODO MEJORADO: Eliminar pregunta con validación completa
async eliminarPregunta(pregunta) {
    try {
        console.log('🔍 Verificando eliminación de pregunta:', pregunta.id);
        
        // Primero verificar si se puede eliminar
        const verificacionResponse = await fetch(`/api/preguntas/${pregunta.id}/verificar-eliminacion`);
        
        if (!verificacionResponse.ok) {
            throw new Error('Error al verificar la pregunta');
        }
        
        const verificacionData = await verificacionResponse.json();
        
        // Si tiene relaciones, mostrar confirmación detallada
        if (!verificacionData.puede_eliminar) {
            await this.mostrarConfirmacionEliminacion(pregunta, verificacionData);
            return;
        }
        
        // Si no tiene relaciones, confirmación simple
        if (!confirm('¿Estás seguro de eliminar esta pregunta?')) {
            return;
        }
        
        await this.procesarEliminacion(pregunta.id);
        
    } catch (error) {
        console.error('❌ Error verificando pregunta:', error);
        this.mostrarMensaje('Error al verificar la pregunta: ' + error.message, 'error');
    }
},

// ✅ NUEVO MÉTODO: Mostrar confirmación detallada
async mostrarConfirmacionEliminacion(pregunta, datosVerificacion) {
    const detalles = datosVerificacion.detalles || [];
    const estadisticas = datosVerificacion.estadisticas || {};
    
    let mensaje = `⚠️ <strong>No se puede eliminar la pregunta directamente</strong>\n\n`;
    mensaje += `La pregunta "<em>${pregunta.pregunta}</em>" tiene los siguientes datos relacionados:\n\n`;
    
    detalles.forEach(detalle => {
        mensaje += `${detalle}\n`;
    });
    
    mensaje += `\n<strong>Estadísticas:</strong>\n`;
    mensaje += `• Respuestas registradas: ${estadisticas.total_respuestas || 0}\n`;
    mensaje += `• Opciones con subpreguntas: ${estadisticas.total_opciones_con_subpreguntas || 0}\n`;
    mensaje += `• Preguntas de rango: ${estadisticas.total_subpreguntas_rango || 0}\n\n`;
    
    mensaje += `¿Deseas <strong>eliminar todos estos datos</strong> junto con la pregunta?\n\n`;
    mensaje += `🔴 <strong>ADVERTENCIA:</strong> Esta acción eliminará permanentemente:\n`;
    mensaje += `• Todas las respuestas asociadas\n`;
    mensaje += `• Todas las subpreguntas y sus respuestas\n`;
    mensaje += `• Todas las opciones configuradas\n`;
    mensaje += `• Esta acción NO se puede deshacer`;
    
    // Usar SweetAlert2 o confirm personalizado
    if (await this.mostrarConfirmacionAvanzada(mensaje)) {
        await this.procesarEliminacionForzada(pregunta.id);
    }
},

// ✅ NUEVO MÉTODO: Mostrar confirmación avanzada (puedes usar SweetAlert2)
mostrarConfirmacionAvanzada(mensaje) {
    return new Promise((resolve) => {
        // Opción 1: Usar SweetAlert2 (recomendado)
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '⚠️ Confirmación de Eliminación',
                html: mensaje,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar todo',
                cancelButtonText: 'Cancelar',
                width: 600,
                customClass: {
                    popup: 'eliminacion-confirmacion'
                }
            }).then((result) => {
                resolve(result.isConfirmed);
            });
        } else {
            // Opción 2: Confirm nativo (menos elegante)
            resolve(confirm(mensaje.replace(/<[^>]*>/g, '')));
        }
    });
},
// ✅ MÉTODO: Procesar eliminación normal
async procesarEliminacion(preguntaId) {
    try {
        const response = await fetch(`/api/preguntas/${preguntaId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        
        if (response.ok) {
            await this.cargarPreguntas();
            this.mostrarMensaje('Pregunta eliminada correctamente', 'success');
        } else {
            const errorData = await response.json();
            throw new Error(errorData.message || 'Error al eliminar');
        }
    } catch (error) {
        console.error('Error eliminando pregunta:', error);
        this.mostrarMensaje('Error al eliminar la pregunta: ' + error.message, 'error');
    }
},

// ✅ NUEVO MÉTODO: Procesar eliminación forzada
async procesarEliminacionForzada(preguntaId) {
    try {
        console.log('🗑️ Iniciando eliminación forzada de pregunta:', preguntaId);
        
        const response = await fetch(`/api/preguntas/${preguntaId}/eliminar-forzado`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        
        if (response.ok) {
            await this.cargarPreguntas();
            this.mostrarMensaje('Pregunta y todos sus datos relacionados eliminados correctamente', 'success');
        } else {
            const errorData = await response.json();
            throw new Error(errorData.message || 'Error al eliminar forzadamente');
        }
    } catch (error) {
        console.error('Error eliminando pregunta forzadamente:', error);
        this.mostrarMensaje('Error al eliminar la pregunta: ' + error.message, 'error');
    }
},

        cerrarModalPregunta() {
            this.mostrarModalPregunta = false;
            this.esEdicionPregunta = false;
            this.resetearConfiguracionRangos();
            
            // 🔥 Resetear TODOS los datos
            this.tipoSeleccionado = null;
            this.areasSeleccionadas = [];
            this.sedesSeleccionadas = [];
            
            // Resetear el formulario
            this.preguntaForm = {
                id: null,
                pregunta: '',
                tipo: 'opcion_unica',
                tipo_pregunta: null,
                area_id: null,
                niveles_calificacion_id: '',
                sede_id: this.sedeActual ? this.sedeActual.id : null,
                opciones: [{ texto: '' }, { texto: '' }],
                is_active: true,
                areas_participantes: [],
                sede_participante: null
            };
        },

        cerrarModalSubpreguntas() {
            this.mostrarModalSubpreguntas = false;
            this.preguntaSeleccionada = null;
        },

        cerrarModalSubpregunta() {
            this.mostrarModalSubpregunta = false;
            this.opcionSeleccionada = null;
            this.subpreguntaEditando = null;
            
            // 🔥 Resetear TODOS los datos del formulario de subpregunta
            this.subpreguntaForm = {
                id: null,
                pregunta_texto: '',
                tipo: 'opcion_unica',
                opciones: [{ texto: '' }, { texto: '' }]
            };
        },

        mostrarMensaje(mensaje, tipo) {
            alert(`${tipo === 'success' ? '✅' : '❌'} ${mensaje}`);
        }
    }   
}
</script>

<style scoped>
/* ESTILOS GENERALES */
.preguntas-management {
    min-height: 100vh;
    background: #f8f9fa;
}

/* HEADER SECTION */
.header-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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

/* BOTONES */
.btn-primary {
    background: #4f46e5;
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
    background: #4338ca;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
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

.btn-outline {
    background: transparent;
    color: #4f46e5;
    border: 2px solid #4f46e5;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
}

.btn-outline:hover {
    background: #4f46e5;
    color: white;
}

.btn-sm {
    padding: 0.4rem 0.8rem;
    font-size: 0.875rem;
}

.btn-icon {
    background: transparent;
    border: none;
    padding: 0.5rem;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-icon:hover {
    background: #f3f4f6;
}

.btn-icon.danger {
    color: #ef4444;
}

.btn-icon.danger:hover {
    background: #fef2f2;
}

.btn-icon.success {
    color: #10b981;
}

.btn-icon.success:hover {
    background: #ecfdf5;
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
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
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
    border-top: 4px solid #4f46e5;
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

/* QUESTIONS GRID */
.questions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 1.5rem;
}

.question-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.question-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    background: #f8fafc;
    border-bottom: 1px solid #e5e7eb;
}

.question-id {
    color: #6b7280;
    font-size: 0.875rem;
    font-weight: 500;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    white-space: nowrap;
}

.status-badge.active {
    background: #d1fae5;
    color: #065f46;
}

.status-badge.inactive {
    background: #fef3c7;
    color: #92400e;
}

/* 🔥 Badges de tipo de calificación - mismos estilos que .status-badge */
.status-badge.tipo-badge-csat {
    background: #dbeafe !important;
    color: #1e40af !important;
}

.status-badge.tipo-badge-nps {
    background: #fef3c7 !important;
    color: #92400e !important;
}

.status-badge.tipo-badge-fcr {
    background: #fce7f3 !important;
    color: #9f1239 !important;
}

.status-badge.tipo-badge-default {
    background: #e5e7eb !important;
    color: #374151 !important;
}

.card-content {
    padding: 1.5rem;
}

.question-text {
    font-size: 1.125rem;
    font-weight: 600;
    color: #111827;
    margin-bottom: 1rem;
    line-height: 1.4;
}

.question-meta {
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

/* 🔥 NUEVO: Estilos para badges de participantes */
.participantes-section {
    margin: 0.75rem 0;
    padding-top: 0.75rem;
    border-top: 1px solid #e5e7eb;
}

.participantes-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #6b7280;
    margin-bottom: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badges-container {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.participante-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.375rem 0.75rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 500;
    line-height: 1;
}

.area-badge {
    background: #dbeafe;
    color: #1e40af;
}

.sede-badge {
    background: #fef3c7;
    color: #92400e;
}

/* 🔥 Badge grande al inicio */
.badge-large {
    font-size: 1.1rem !important;
    padding: 0.5rem 1rem !important;
    font-weight: 600 !important;
}

.badge-large i {
    font-size: 1rem !important;
    margin-right: 0.5rem;
}

/* 🔥 Badges de estado */
.status-active {
    background: #d1fae5 !important;
    color: #065f46 !important;
}

.status-inactive {
    background: #fee2e2 !important;
    color: #991b1b !important;
}

.participante-badge i {
    font-size: 0.6875rem;
}

.question-details {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
    margin-bottom: 1rem;
}

.type-badge {
    padding: 0.375rem 0.75rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.375rem;
}

.type-badge.primary {
    background: #e0e7ff;
    color: #3730a3;
}

.type-badge.info {
    background: #dbeafe;
    color: #1e40af;
}

.type-badge.secondary {
    background: #f3f4f6;
    color: #374151;
}

.type-badge.warning {
    background: #fef3c7;
    color: #92400e;
}

.type-badge.success {
    background: #d1fae5;
    color: #065f46;
}

.type-badge.small {
    font-size: 0.7rem;
    padding: 0.25rem 0.5rem;
}

.options-count {
    background: #f3f4f6;
    color: #6b7280;
    padding: 0.375rem 0.75rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.375rem;
}

/* 🔥 NUEVOS INDICADORES */
.subpreguntas-indicator, .rangos-indicator {
    background: #e0f2fe;
    border: 1px solid #bae6fd;
    border-radius: 6px;
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
    color: #0369a1;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.rangos-indicator {
    background: #f0fdf4;
    border-color: #bbf7d0;
    color: #166534;
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

.action-btn.subpreguntas {
    background: #dcfce7;
    color: #166534;
}

.action-btn.subpreguntas:hover {
    background: #bbf7d0;
    color: #15803d;
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

.large-modal {
    max-width: 800px;
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

.modal-subtitle {
    color: #6b7280;
    margin: 0.5rem 0 0 0;
    font-size: 0.875rem;
}

.modal-close, .btn-close {
    background: transparent;
    border: none;
    padding: 0.5rem;
    border-radius: 6px;
    cursor: pointer;
    color: #6b7280;
    transition: all 0.3s ease;
}

.modal-close:hover, .btn-close:hover {
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

.badge-info {
    background: #dbeafe;
    color: #1e40af;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

/* 🔥 ESTILOS PARA INPUTS DE OPCIONES */
.opciones-container {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.opcion-input-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: white;
    transition: all 0.3s ease;
    position: relative;
}

.opcion-input-item:hover {
    border-color: #d1d5db;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.opcion-input-item.opcion-otro {
    background: #f0f9ff;
    border-color: #bae6fd;
}

.opcion-input-item.ultima-opcion {
    border: 2px dashed #c7d2fe;
    background: #f8fafc;
}

.opcion-input {
    flex: 1;
    border: none;
    outline: none;
    padding: 0.5rem 0;
    font-size: 0.875rem;
    background: transparent;
}

.opcion-input:read-only {
    color: #6b7280;
    background: transparent;
}

.opcion-otro-badge {
    position: absolute;
    top: -8px;
    right: 10px;
    background: #3b82f6;
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.7rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

/* FORM INPUTS GENERALES */
.form-input, .form-select, .form-textarea {
    padding: 0.75rem 1rem;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.875rem;
    transition: all 0.3s ease;
    background: white;
}

.form-input:focus, .form-select:focus, .form-textarea:focus {
    outline: none;
    border-color: #4f46e5;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.form-textarea {
    resize: vertical;
    min-height: 80px;
    font-family: inherit;
}

.form-input.disabled {
    background: #f9fafb;
    color: #6b7280;
    cursor: not-allowed;
}

/* CHECKBOX */
.checkbox-container {
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
    background: #4f46e5;
    border-color: #4f46e5;
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

/* INFO MESSAGE */
.info-message {
    background: #f0f9ff;
    border: 1px solid #bae6fd;
    border-radius: 8px;
    padding: 1rem;
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    color: #0369a1;
}

.info-message i {
    margin-top: 0.125rem;
}

.info-message p {
    margin: 0;
    font-size: 0.875rem;
}

/* MODAL ACTIONS */
.modal-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    padding-top: 1.5rem;
    border-top: 1px solid #e5e7eb;
    margin-top: 1.5rem;
}

/* 🔥 CONFIGURACIÓN DE RANGOS */
.configuracion-rangos {
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 1.5rem;
    margin-top: 1rem;
    background: #fafafa;
}

.seccion-titulo {
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e5e7eb;
}

.seccion-titulo h4 {
    margin: 0;
    color: #111827;
    font-size: 1.25rem;
}

.seccion-titulo p {
    margin: 0.5rem 0 0 0;
    color: #6b7280;
    font-size: 0.875rem;
}

.rango-item {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    margin-bottom: 1rem;
    background: white;
    overflow: hidden;
}

.rango-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    background: #f8fafc;
    border-bottom: 1px solid #e5e7eb;
}

.rango-info h5 {
    margin: 0;
    color: #111827;
    font-size: 1rem;
}

.rango-valores {
    background: #4f46e5;
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    margin-top: 0.25rem;
    display: inline-block;
}

.rango-contenido {
    padding: 1.5rem;
}

.rangos-info {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 6px;
    padding: 0.75rem 1rem;
    margin-top: 1rem;
    font-size: 0.875rem;
    color: #166534;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* TOGGLE SWITCH */
.toggle-label {
    display: flex;
    align-items: center;
    cursor: pointer;
    gap: 0.5rem;
}

.toggle-input {
    display: none;
}

.toggle-slider {
    width: 44px;
    height: 24px;
    background: #d1d5db;
    border-radius: 24px;
    position: relative;
    transition: all 0.3s ease;
}

.toggle-slider:before {
    content: '';
    position: absolute;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: white;
    top: 3px;
    left: 3px;
    transition: transform 0.3s ease;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
}

.toggle-input:checked + .toggle-slider {
    background: #10b981;
}

.toggle-input:checked + .toggle-slider:before {
    transform: translateX(20px);
}

.toggle-text {
    font-size: 0.875rem;
    color: #374151;
    font-weight: 500;
}

/* 🔥 ESTILOS PARA SUBPREGUNTAS */
.subpreguntas-content {
    max-height: 70vh;
    overflow-y: auto;
    padding: 0 1rem;
}

.opcion-subpreguntas-section {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    background: white;
    overflow: hidden;
}

.opcion-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    background: #f8f9fa;
    border-bottom: 1px solid #e5e7eb;
}

.opcion-header h4 {
    margin: 0;
    color: #111827;
    font-size: 1.125rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.subpreguntas-list {
    padding: 1rem 1.5rem;
}

.subpregunta-item {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    margin-bottom: 0.75rem;
    background: #fafafa;
    transition: all 0.3s ease;
}

.subpregunta-item:hover {
    border-color: #d1d5db;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.subpregunta-content {
    flex: 1;
}

.subpregunta-texto {
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #111827;
}

.subpregunta-opciones {
    color: #6b7280;
    font-size: 0.875rem;
    font-style: italic;
}

.subpregunta-actions {
    display: flex;
    gap: 0.5rem;
}

.no-subpreguntas {
    padding: 2rem;
    text-align: center;
    color: #6b7280;
    background: #f8f9fa;
    border-radius: 6px;
    margin: 1rem 1.5rem;
}

.no-subpreguntas p {
    margin: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.info-box {
    background: #e0f2fe;
    border: 1px solid #bae6fd;
    border-radius: 8px;
    padding: 1.25rem;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: flex-start;
    gap: 1rem;
}

.info-box i {
    color: #0369a1;
    font-size: 1.25rem;
    margin-top: 0.125rem;
}

.info-box strong {
    color: #0369a1;
    display: block;
    margin-bottom: 0.5rem;
}

.info-box p {
    margin: 0;
    color: #0369a1;
    font-size: 0.875rem;
    line-height: 1.5;
}

/* 🔥 Estilos para áreas participantes */
.info-box.areas-participantes {
    background: #f0fdf4;
    border: 2px solid #22c55e;
}

.info-box.areas-participantes i {
    color: #15803d;
}

.info-box.areas-participantes strong {
    color: #15803d;
}

.areas-list {
    margin-top: 0.75rem;
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.area-tag {
    background: white;
    border: 1px solid #22c55e;
    color: #15803d;
    padding: 0.375rem 0.75rem;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
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
    
    .questions-grid {
        grid-template-columns: 1fr;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .modal-container {
        margin: 1rem;
        max-height: calc(100vh - 2rem);
    }
    
    .rango-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .subpregunta-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .subpregunta-actions {
        align-self: flex-end;
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

.question-card {
    animation: fadeIn 0.5s ease;
}

/* SCROLLBAR PERSONALIZADO */
::-webkit-scrollbar {
    width: 6px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* 🔥 NUEVO: Tipos de Calificación */
.tipos-calificacion-grid {
    display: flex !important;
    flex-wrap: wrap !important;
    gap: 1.5rem !important;
    margin-top: 1.5rem;
    justify-content: center;
}

.tipo-card {
    flex: 0 0 220px;
    min-width: 220px;
    max-width: 280px;
    background: white !important;
    border: 3px solid #e5e7eb !important;
    border-radius: 16px !important;
    padding: 2rem !important;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.tipo-card:hover {
    border-color: #6366f1 !important;
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(99, 102, 241, 0.15) !important;
}

.tipo-card.seleccionado {
    border-color: #6366f1 !important;
    background: #f0f9ff !important;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2) !important;
}

.tipo-icon {
    margin-bottom: 1rem;
    color: #6366f1;
    font-size: 3rem !important;
}

.tipo-nombre {
    font-size: 1.5rem !important;
    font-weight: 700 !important;
    color: #1f2937 !important;
    margin-bottom: 0.75rem !important;
}

.tipo-descripcion {
    font-size: 0.875rem !important;
    color: #6b7280 !important;
    line-height: 1.5 !important;
}

.tipo-seleccionado {
    background: #f0fdf4 !important;
    border-color: #22c55e !important;
    color: #16a34a;
}

.tipo-seleccionado i {
    color: #22c55e;
}

/* 🔥 NUEVO: Checkbox Grid */
.checkbox-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
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

.checkbox-item input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
    accent-color: #6366f1;
}

.checkbox-label {
    font-size: 0.875rem;
    color: #374151;
    cursor: pointer;
}

.text-sm {
    font-size: 0.75rem;
    color: #6b7280;
}

.medium-modal {
    max-width: 900px;
}

/* Modal body para selección de tipos */
.modal-body p.modal-subtitle {
    margin-bottom: 2rem !important;
    font-size: 1rem;
    color: #6b7280;
}

/* Asegurar que las cards tengan espacio */
.modal-container.medium-modal .tipos-calificacion-grid {
    display: flex !important;
    flex-wrap: wrap !important;
    justify-content: center !important;
    gap: 1.5rem !important;
    padding: 0 !important;
}

.modal-container.medium-modal .tipo-card {
    flex: 0 0 calc(33.333% - 1rem) !important;
    min-width: 200px !important;
    max-width: 250px !important;
}

@media (max-width: 768px) {
    .modal-container.medium-modal .tipo-card {
        flex: 0 0 100% !important;
        max-width: 100% !important;
    }
}
</style>