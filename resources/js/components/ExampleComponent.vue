<template>
  <div class="calificador-container">
    <div v-if="mostrarModalUbicacion" class="modal-overlay modal-ubicacion-block" @click="pantallaCompleta()">
      <div class="modal-content modal-ubicacion-content">
        <h2 class="modal-titulo">Ubicación requerida</h2>
        <p class="modal-ubicacion-text">
          No se pudo obtener la ubicación del dispositivo.<br>
          Este sistema solo puede funcionar dentro de una sede UNIFRANZ.<br>
          Por favor, activa los permisos de ubicación y recarga la página.
        </p>
      </div>
    </div>
    <!-- Selección de área si no hay área seleccionada -->
    <div v-if="!areaSeleccionada" class="area-selector">
      <h2 class="area-selector-title">Selecciona el área</h2>
      <div class="area-cards">
        <div v-for="area in areas" :key="area.uuid" class="area-card" @click="seleccionarArea(area)">
          <img :src="area.imagen || '/img/plantilla/logopdf.png'" class="area-img" :alt="area.nombre">
          <div class="area-info">
            <h3>{{ area.nombre }}</h3>
            <p>{{ area.detalles }}</p>
          </div>
        </div>
      </div>
      <!-- Modal de contraseña -->
      <div v-if="modalPasswordVisible" class="modal-overlay" @click.self="cerrarModalPassword">
        <div class="modal-content area-modal-content">
          <h3 class="modal-titulo">Ingresa la contraseña <span style="color:#007bff">{{ areaSeleccionTemp && areaSeleccionTemp.nombre }}</span></h3>
          <input type="password" v-model="passwordArea" class="area-password-input" placeholder="Contraseña...">
          <div class="modal-botones-centro">
            <button @click="validarPasswordArea" class="btn btn-primary modal-btn">Aceptar</button>
            <button @click="cerrarModalPassword" class="btn btn-secondary modal-btn">Cancelar</button>
          </div>
          <div v-if="passwordError" class="area-password-error">Contraseña incorrecta</div>
        </div>
      </div>
    </div>
    <!-- Calificador normal si hay área seleccionada -->
    <template v-else>
      <h2 class="calificador-titulo">¿Cómo fue tu atención?</h2>
      <div class="caritas-wrapper">
        <div class="caritas">
          
          <div class="carita-group">
            <div class="carita" @click="abrirModal('excelente')" title="Excelente">
              <svg viewBox="0 0 120 120"><circle cx="60" cy="60" r="58" fill="#d4edda"/><circle cx="45" cy="55" r="10" fill="#155724"/><circle cx="75" cy="55" r="10" fill="#155724"/><path d="M45 85c7 10 23 10 30 0" stroke="#155724" stroke-width="5" fill="none" stroke-linecap="round"/></svg>
            </div>
            <div class="carita-label" @click="abrirModal('excelente')" style="cursor: pointer;">Excelente</div>
          </div>
          <div class="carita-group">
            <div class="carita" @click="abrirModal('buena')" title="Buena">
              <svg viewBox="0 0 120 120">
                <circle cx="60" cy="60" r="58" fill="#cce4ff"/>
                <circle cx="45" cy="55" r="10" fill="#0056b3"/>
                <circle cx="75" cy="55" r="10" fill="#0056b3"/>
                <path d="M45 90c0 1 15 6 30 0" stroke="#0056b3" stroke-width="5" fill="none" stroke-linecap="round"/>
              </svg>
            </div>
            <div class="carita-label" @click="abrirModal('buena')" style="cursor: pointer;">Buena</div>
          </div>
          <div class="carita-group">
            <div class="carita" @click="abrirModal('regular')" title="Regular">
              <svg viewBox="0 0 120 120"><circle cx="60" cy="60" r="58" fill="#fff3cd"/><circle cx="45" cy="55" r="10" fill="#856404"/><circle cx="75" cy="55" r="10" fill="#856404"/><path d="M45 90c0-2 5-10 30 0" stroke="#856404" stroke-width="5" fill="none" stroke-linecap="round"/></svg>
            </div>
            <div class="carita-label" @click="abrirModal('regular')" style="cursor: pointer;">Regular</div>
          </div>
          <div class="carita-group">
          <div class="carita" @click="abrirModal('mala')" title="Mala">
            <svg viewBox="0 0 120 120">
              <circle cx="60" cy="60" r="58" fill="#f8d7da"/>
              <circle cx="45" cy="55" r="10" fill="#721c24"/>
              <circle cx="75" cy="55" r="10" fill="#721c24"/>
              <path d="M45 95c7-10 23-10 30 0" stroke="#721c24" stroke-width="5" fill="none" stroke-linecap="round"/>
            </svg>
          </div>
          <div class="carita-label" @click="abrirModal('mala')" style="cursor: pointer;">Mala</div>
        </div>
        </div>
      </div>
      <div v-if="modalVisible" class="modal-overlay" @click.self="cerrarModal">
        <div class="modal-content">
          <div class="modal-titulo-wrapper">
            <h3 class="modal-titulo">{{ modalTitle }}</h3>
          </div>
          <div v-if="indicadoresActuales.length === 0" class="no-indicadores-text">
            No hay indicadores
          </div>
          <div v-else class="indicadores-list">
            <label v-for="(indicador, idx) in indicadoresActuales" :key="idx" class="checkbox-indicador">
              <input type="checkbox" class="custom-checkbox" v-model="indicadoresSeleccionados" :value="indicador">
              <span class="checkmark"></span>
              {{ indicador }}
            </label>
          </div>
          <label for="observacion" class="observacion-label">Observaciones:</label>
          <textarea v-model="observacion" id="observacion" rows="3" class="modal-textarea" placeholder="Escribe tus comentarios aquí..."></textarea>
          <div class="modal-botones-centro">
            <button type="submit" class="btn btn-primary modal-btn" @click="enviar" :disabled="cargando">
              <span v-if="cargando" class="spinner"></span>
              <span v-else>Enviar</span>
            </button>
            <button type="button" class="btn btn-secondary modal-btn" @click="cerrarModal" :disabled="cargando">Cancelar</button>
          </div>
        </div>
      </div>
      <small class="no-select" style="display:block; text-align:center; color:#888; margin-top:2rem;">
        {{ areaSeleccionada.nombre }} - {{ sedeDetectada }}
      </small>
    </template>
  </div>
</template>

<script>
export default {
  name: 'Calificador',
  data() {
    return {
      areas: [],
      areaSeleccionada: JSON.parse(localStorage.getItem('areaSeleccionada') || 'null'),
      modalPasswordVisible: false,
      areaSeleccionTemp: null,
      passwordArea: '',
      passwordError: false,
      modalVisible: false,
      caritaSeleccionada: '',
      observacion: '',
      indicadoresSeleccionados: [],
      indicadoresPorArea: {},
      tipoTablet: localStorage.getItem('tipoTablet') || '',
      tipoTabletSeleccionado: '',
      cargando: false,
      ubicacionesSedes: [
        { id: 1, nombre: 'LA PAZ', lat: -16.505855, lng: -68.132680 },
        { id: 4, nombre: 'EL ALTO', lat: -16.508245, lng: -68.166321 },
        { id: 3, nombre: 'COCHABAMBA', lat: -17.37511298710457, lng: -66.15864914753865 },
        { id: 2, nombre: 'SANTA CRUZ', lat: -17.773898665690574, lng: -63.19247654937392 }
      ],
      sedeDetectada: '',
      idsedeDetectada: null,
      latitud: null,
      longitud: null,
      mostrarModalUbicacion: false,
    }
  },
  computed: {
    indicadoresActuales() {
      if (!this.areaSeleccionada) return [];
      const areaKey = this.areaSeleccionada.nombre
        ? this.areaSeleccionada.nombre.toLowerCase().trim()
        : '';
      let estado = this.caritaSeleccionada;
      if (
        this.indicadoresPorArea[areaKey] &&
        this.indicadoresPorArea[areaKey][estado] &&
        this.indicadoresPorArea[areaKey][estado].length
      ) {
        return this.indicadoresPorArea[areaKey][estado];
      }
      return [];
    },
    modalTitle() {
      if (this.caritaSeleccionada === 'excelente') return '¡Gracias! ¿Qué fue lo mejor?';
      if (this.caritaSeleccionada === 'buena') return '¿Qué podemos mejorar?';
      if (this.caritaSeleccionada === 'regular') return 'Lamentamos tu experiencia. ¿Qué ocurrió?';
      if (this.caritaSeleccionada === 'mala') return '¿Qué podríamos haber hecho de manera diferente?';
      return '';
    }
  },
  methods: {
    async obtenerAreas() {
      try {
        const response = await axios.get('/selectArea');
        this.areas = response.data.areas;
      } catch (error) {
        console.error(error);
      }
    },
    async obtenerIndicadores() {
      try {
        const response = await axios.get('/selectIndicador');
        // Transforma los indicadores en un objeto por área
        const indicadoresObj = {};
        response.data.indicadores.forEach(ind => {
          indicadoresObj[ind.nombre.toLowerCase().trim()] = {
            excelente: ind.excelente ? ind.excelente.split(',') : [],
            buena: ind.buena ? ind.buena.split(',') : [],
            regular: ind.regular ? ind.regular.split(',') : [],
            mala: ind.mala ? ind.mala.split(',') : []
          };
        });
        this.indicadoresPorArea = indicadoresObj;
      } catch (error) {
        console.error(error);
      }
    },
    seleccionarArea(area) {
      this.areaSeleccionTemp = area;
      this.passwordArea = '';
      this.passwordError = false;
      this.modalPasswordVisible = true;
    },
    validarPasswordArea() {
      if (this.passwordArea === '13116407') {
        localStorage.setItem('areaSeleccionada', JSON.stringify(this.areaSeleccionTemp));
        this.areaSeleccionada = this.areaSeleccionTemp;
        this.cerrarModalPassword();
      } else {
        this.passwordError = true;
      }
    },
    cerrarModalPassword() {
      this.modalPasswordVisible = false;
      this.areaSeleccionTemp = null;
      this.passwordArea = '';
      this.passwordError = false;
    },
    guardarTipoTablet() {
      if (this.tipoTabletSeleccionado) {
        localStorage.setItem('tipoTablet', this.tipoTabletSeleccionado);
        this.tipoTablet = this.tipoTabletSeleccionado;
      }
    },
    pantallaCompleta() {
      const elem = document.documentElement;
      if (elem.requestFullscreen) {
        elem.requestFullscreen();
      } else if (elem.mozRequestFullScreen) { /* Firefox */
        elem.mozRequestFullScreen();
      } else if (elem.webkitRequestFullscreen) { /* Chrome, Safari & Opera */
        elem.webkitRequestFullscreen();
      } else if (elem.msRequestFullscreen) { /* IE/Edge */
        elem.msRequestFullscreen();
      }
    },
   async abrirModal(tipo) {      
    this.pantallaCompleta();
    this.caritaSeleccionada = tipo;
    
    // Obtener preguntas dinámicas desde la BD
    try {
        const response = await axios.get('/api/preguntas-calificador', {
            params: {
                idsede: this.idsedeDetectada,
                area: this.areaSeleccionada.nombre,
                tipo: tipo
            }
        });
        
        this.preguntasDinamicas = response.data.preguntas;
    } catch (error) {
        console.error('Error cargando preguntas:', error);
        // Fallback a preguntas por defecto
        this.preguntasDinamicas = [];
    }
    
    this.modalVisible = true;
    this.respuestas = {};
    this.observacion = '';
},
    cerrarModal() {
      this.modalVisible = false;
      this.caritaSeleccionada = '';
      this.indicadoresSeleccionados = [];
      this.observacion = '';
    },
    enviar() {
      // Validación: al menos un indicador o una observación
      if (this.indicadoresSeleccionados.length === 0 && (!this.observacion || this.observacion.trim() === '')) {
        Swal.fire({
          icon: 'warning',
          title: 'Falta información',
          text: 'Debes seleccionar al menos un dato o escribir una observación.',
          timer: 1800,
          timerProgressBar: true,
          showConfirmButton: false,
          background: '#f8f9fa',
          color: '#222',
          iconColor: '#ffc107',
        });
        return;
      }
      this.cargando = true;
      const payload = {
        idarea: this.areaSeleccionada.id,
        estado: this.caritaSeleccionada,
        detalles: this.indicadoresSeleccionados,
        observacion: this.observacion,
        idsede: this.idsedeDetectada
        // fecha: new Date().toISOString() // Descomenta si quieres enviar la fecha desde el frontend
      };
      axios.post('/registro/store', payload)
        .then(() => {
          this.cerrarModal();
          Swal.fire({
            icon: 'success',
            title: '¡Gracias por tu opinión!',
            text: 'Tu calificación ha sido registrada correctamente.',
            background: '#f8f9fa',
            color: '#222',
            iconColor: '#28a745',
            showConfirmButton: false,
            customClass: {
              popup: 'swal2-popup-custom',
              title: 'swal2-title-custom'
            },
            timer: 2000,
            timerProgressBar: true,
            allowOutsideClick: false
          });
        })
        .catch(() => {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo registrar la calificación. Intenta de nuevo.',
            background: '#f8f9fa',
            color: '#222',
            iconColor: '#d32f2f',
            showConfirmButton: true
          });
        })
        .finally(() => {
          this.cargando = false;
        });
    },
    obtenerUbicacionYDetectarSede() {
      if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
          (position) => {
            this.latitud = position.coords.latitude;
            this.longitud = position.coords.longitude;
            this.detectarSede();
          },
          (error) => {
            this.sedeDetectada = 'NO SE PUDO OBTENER UBICACIÓN';
            this.mostrarModalUbicacion = true;
          }
        );
      } else {
        this.sedeDetectada = 'NO SE PUDO OBTENER UBICACIÓN';
        this.mostrarModalUbicacion = true;
      }
    },
    detectarSede() {
      let minDist = Infinity;
      let sedeCercana = '';
      let idSedeCercana = null;
      this.ubicacionesSedes.forEach(sede => {
        const dist = this.calcularDistancia(
          this.latitud, this.longitud, sede.lat, sede.lng
        );
        if (dist < minDist) {
          minDist = dist;
          sedeCercana = sede.nombre;
          idSedeCercana = sede.id;
        }
      });
      this.sedeDetectada = sedeCercana;
      this.idsedeDetectada = idSedeCercana;
    },
    calcularDistancia(lat1, lon1, lat2, lon2) {
      // Haversine formula
      const R = 6371; // km
      const dLat = (lat2 - lat1) * Math.PI / 180;
      const dLon = (lon2 - lon1) * Math.PI / 180;
      const a =
        Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos(lat1 * Math.PI / 180) *
        Math.cos(lat2 * Math.PI / 180) *
        Math.sin(dLon / 2) * Math.sin(dLon / 2);
      const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
      return R * c;
    }
  },
  mounted() {
    this.obtenerAreas();
    this.obtenerIndicadores();
    this.obtenerUbicacionYDetectarSede();
  }
}
</script>

<style scoped>
.calificador-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  background: #f8f9fa;
  user-select: none;
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
}
.calificador-titulo {
  font-size: clamp(2.5rem, 6vw, 6rem);
  margin-bottom: 2rem;
  text-align: center;
  font-weight: bold;
}
.caritas-wrapper {
  width: 90vw;
  max-width: 1150px;
  display: flex;
  justify-content: center;
  align-items: center;
}
.caritas {
  display: flex;
  gap: 2vw;
  width: 100%;
  justify-content: center;
  align-items: center;
}
.carita {
  flex: 1 1 0;
  max-width: 13vw;
  max-height: 28vw;
  min-width: 245px;
  min-height: 245px;
  width: 100%;
  height: 100%;
  aspect-ratio: 1/1;
  border-radius: 50%;
  background: #fff;
  border: 10px solid #fff;
  box-shadow: 0 2px 24px rgba(0,0,0,0.12);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: transform 0.2s;
  position: relative;
}
.carita:hover {
  transform: scale(1.08) rotate(-3deg);
  box-shadow: 0 4px 32px rgba(0,0,0,0.18);
}
.carita svg {
  width: 95%;
  height: 95%;
  display: block;
}
@media (max-width: 900px) {
  .caritas-wrapper { width: 98vw; }
  .carita { min-width: 100px; min-height: 100px; max-width: 180px; max-height: 180px; }
}
@media (max-width: 600px) {
  .caritas { gap: 1.5rem; }
  .carita { min-width: 70px; min-height: 70px; max-width: 90px; max-height: 90px; }
  .carita svg { width: 60px; height: 60px; }
}

/* Modal styles */
.modal-overlay {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0,0,0,0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}
.modal-content {
  background: #fff;
  padding: 2.5rem 2.5rem 2.5rem 2.5rem;
  border-radius: 1.5rem;
  box-shadow: 0 8px 40px rgba(0,0,0,0.18);
  min-width: 320px;
  max-width: 95vw;
  width: 420px;
  display: flex;
  flex-direction: column;
  align-items: stretch;
  margin: 2rem;
}
.modal-titulo-wrapper {
  width: 100%;
  border-bottom: 2.5px solid #e0e0e0;
  margin-bottom: 1rem;
  padding-bottom: 0.7rem;
  display: flex;
  justify-content: center;
}
.modal-titulo {
  font-size: 2rem;
  font-weight: 700;
  text-align: center;
  margin: 0;
}
.indicadores {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  margin-bottom: 1rem;
  width: 100%;
}
.checkbox-indicador {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 1.5rem;
  font-weight: 500;
  user-select: none;
  width: 100%;
}
.custom-checkbox {
  appearance: none;
  width: 2rem;
  height: 2rem;
  border: 2.5px solid #bbb;
  border-radius: 50%;
  background: #fff;
  margin-right: 0.7rem;
  transition: border-color 0.2s, box-shadow 0.2s;
  position: relative;
  cursor: pointer;
  outline: none;
  display: inline-block;
}
.custom-checkbox:checked {
  border-color: #007bff;
  background: #e6f0ff;
}
.custom-checkbox:checked::after {
  content: '';
  display: block;
  width: 1rem;
  height: 1rem;
  background: #007bff;
  border-radius: 50%;
  position: absolute;
  top: 0.4rem;
  left: 0.4rem;
}
.indicador-label {
  font-size: 1.3rem;
  font-weight: 500;
  color: #222;
  text-align: left;
}
.observacion-label {
  font-size: 1.1rem;
  font-weight: 500;
  margin-bottom: 0.5rem;
  margin-top: 0.5rem;
}
.modal-textarea {
  width: 100%;
  border-radius: 0.7rem;
  border: 1.5px solid #ddd;
  padding: 0.8rem;
  font-size: 1.1rem;
  resize: none;
  background: #f8f9fa;
}
.modal-botones-centro {
  display: flex;
  justify-content: center;
  gap: 2rem;
  margin-top: 1.2rem;
}
.modal-btn {
  min-width: 120px;
  font-size: 1.2rem;
  border-radius: 0.7rem;
  padding: 0.7rem 1.2rem;
  font-weight: 600;
  box-shadow: 0 2px 8px rgba(0,0,0,0.07);
  border: none;
}
.btn-primary.modal-btn {
  background: #007bff;
  color: #fff;
}
.btn-secondary.modal-btn {
  background: #e9ecef;
  color: #222;
}
.btn-primary.modal-btn:hover {
  background: #0056b3;
}
.btn-secondary.modal-btn:hover {
  background: #d6d8db;
}
.carita-group {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-start;
  width: 100%;
}
.carita-label {
  margin-top: 1.2rem;
  font-size: 2rem;
  font-weight: 600;
  color: #222;
  text-align: center;
  letter-spacing: 0.02em;
}
.swal2-timer-progress-bar {
  margin-top: 1.2rem !important;
  margin-bottom: 0 !important;
  border-radius: 0 0 1.5rem 1.5rem !important;
  height: 0.35rem !important;
  left: 0 !important;
  right: 0 !important;
}
.modal-ubicacion-block {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0,0,0,0.7);
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
}
.modal-ubicacion-content {
  background: #fff;
  padding: 2.5rem 2.5rem 2.5rem 2.5rem;
  border-radius: 1.5rem;
  box-shadow: 0 8px 40px rgba(0,0,0,0.18);
  min-width: 320px;
  max-width: 95vw;
  width: 420px;
  display: flex;
  flex-direction: column;
  align-items: center;
  margin: 2rem;
}
.modal-ubicacion-text {
  font-size: 1.3rem;
  color: #222;
  text-align: center;
  margin-top: 1.5rem;
}
</style>

<style>
.swal2-popup-custom {
  border-radius: 1.5rem !important;
  padding: 0 2rem 3rem 2rem !important;
  font-size: 1.3rem;
  box-shadow: 0 8px 40px rgba(0,0,0,0.18);
}
.swal2-title-custom {
  font-size: 2.2rem !important;
  font-weight: 700 !important;
  margin-top: 0rem !important;
}
.swal2-timer-progress-bar {
  margin-top: 1.2rem !important;
  margin-bottom: 0 !important;
  border-radius: 0 0 1.5rem 1.5rem !important;
  height: 0.35rem !important;
  left: 0 !important;
  right: 0 !important;
}
div:where(.swal2-container) .swal2-timer-progress-bar-container {
  position: absolute;
  right: 0;
  bottom: 0;
  left: 0;
  grid-column: auto !important;
  overflow: hidden;
  border-bottom-right-radius: 50px;
  border-bottom-left-radius: 50px;
}

div:where(.swal2-container) h2:where(.swal2-title) {
    position: relative;
    max-width: 100%;
    margin: 0;
    padding: .1em 1em 0 !important;
    color: inherit;
    font-size: 1.875em;
    font-weight: 600;
    text-align: center;
    text-transform: none;
    word-wrap: break-word;
}

.area-selector {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  background: #f8f9fa;
}
.area-selector-title {
  font-size: 2.5rem;
  font-weight: 700;
  margin-bottom: 2rem;
  text-align: center;
}
.area-cards {
  display: flex;
  flex-wrap: wrap;
  gap: 2.5rem;
  justify-content: center;
  align-items: center;
}
.area-card {
  background: #fff;
  border-radius: 1.5rem;
  box-shadow: 0 2px 16px rgba(0,0,0,0.10);
  width: 260px;
  min-height: 340px;
  display: flex;
  flex-direction: column;
  align-items: center;
  cursor: pointer;
  transition: transform 0.18s, box-shadow 0.18s;
  border: 2.5px solid #e9ecef;
  overflow: hidden;
}
.area-card:hover {
  transform: scale(1.04) translateY(-6px);
  box-shadow: 0 8px 32px rgba(0,0,0,0.16);
  border-color: #007bff;
}
.area-img {
  width: 100%;
  height: 160px;
  object-fit: cover;
  border-top-left-radius: 1.5rem;
  border-top-right-radius: 1.5rem;
}
.area-info {
  padding: 1.2rem 1.2rem 0.8rem 1.2rem;
  text-align: center;
}
.area-info h3 {
  font-size: 1.4rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
}
.area-info p {
  font-size: 1.05rem;
  color: #555;
}
.area-modal-content {
  min-width: 320px;
  max-width: 95vw;
  width: 400px;
  padding: 2.5rem 2rem 2rem 2rem;
  border-radius: 1.2rem;
  box-shadow: 0 2px 24px rgba(0,0,0,0.18);
  display: flex;
  flex-direction: column;
  align-items: stretch;
}
.area-password-input {
  margin: 1.2rem 0 0.5rem 0;
  padding: 0.7rem 1rem;
  font-size: 1.2rem;
  border-radius: 0.7rem;
  border: 1.5px solid #ddd;
  background: #f8f9fa;
}
.area-password-error {
  color: #d32f2f;
  font-size: 1.1rem;
  margin-top: 0.5rem;
  text-align: center;
}
.no-select {
  user-select: none;
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
}
/* Agrega el spinner al CSS */
.spinner {
  border: 3px solid #f3f3f3;
  border-top: 3px solid #007bff;
  border-radius: 50%;
  width: 22px;
  height: 22px;
  animation: spin 0.8s linear infinite;
  display: inline-block;
  vertical-align: middle;
  margin-right: 8px;
}
@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style> 