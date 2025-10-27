<template>
    <div class="app-container">
        <div class="animated-background">
            <div class="particles">
                <div v-for="n in 50" :key="'particle-'+n" class="particle" :style="getParticleStyle(n)"></div>
            </div>
            
            <div class="floating-elements">
                <div class="floating-star" v-for="n in 10" :key="'star-'+n" :style="getStarStyle(n)">
                    <i class="fas fa-star"></i>
                </div>
                <div class="floating-medal" v-for="n in 7" :key="'medal-'+n" :style="getMedalStyle(n)">
                    <i class="fas fa-medal"></i>
                </div>
                <div class="floating-trophy" v-for="n in 5" :key="'trophy-'+n" :style="getTrophyStyle(n)">
                    <i class="fas fa-trophy"></i>
                </div>
            </div>
            
            <div class="light-beams">
                <div class="beam" v-for="n in 5" :key="'beam-'+n" :style="getBeamStyle(n)"></div>
            </div>
        </div>
        
        <div class="login-container">
            <div class="login-card">
                <div class="brand-section">
                    <div class="logo-wrapper">
                      
                        <h1>UNIFRANZ TI</h1>
                    </div>
                    <p class="system-name">Sistema de Calificación</p>
                </div>
                
                <div class="access-section">
                   <!--<h2>Acceso al Sistema</h2>-->
                    
                    <div class="button-group">
                        <button @click="loginWithGoogle" class="btn google-btn">
                            <div class="btn-icon google-icon">
                                <!-- Icono de Google mejorado con colores oficiales -->
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="24px" height="24px">
                                    <path fill="#FFC107" d="M43.611,20.083H42V20H24v8h11.303c-1.649,4.657-6.08,8-11.303,8c-6.627,0-12-5.373-12-12c0-6.627,5.373-12,12-12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C12.955,4,4,12.955,4,24c0,11.045,8.955,20,20,20c11.045,0,20-8.955,20-20C44,22.659,43.862,21.35,43.611,20.083z"/>
                                    <path fill="#FF3D00" d="M6.306,14.691l6.571,4.819C14.655,15.108,18.961,12,24,12c3.059,0,5.842,1.154,7.961,3.039l5.657-5.657C34.046,6.053,29.268,4,24,4C16.318,4,9.656,8.337,6.306,14.691z"/>
                                    <path fill="#4CAF50" d="M24,44c5.166,0,9.86-1.977,13.409-5.192l-6.19-5.238C29.211,35.091,26.715,36,24,36c-5.202,0-9.619-3.317-11.283-7.946l-6.522,5.025C9.505,39.556,16.227,44,24,44z"/>
                                    <path fill="#1976D2" d="M43.611,20.083H42V20H24v8h11.303c-0.792,2.237-2.231,4.166-4.087,5.571c0.001-0.001,0.002-0.001,0.003-0.002l6.19,5.238C36.971,39.205,44,34,44,24C44,22.659,43.862,21.35,43.611,20.083z"/>
                                </svg>
                            </div>
                            <div class="btn-text">
                                <span class="btn-title">Google</span>
                                <span class="btn-subtitle">Personal Administrativo</span>
                            </div>
                        </button>

                        <button @click="goToCalificar" class="btn guest-btn">
                            <div class="btn-icon guest-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-tablet" viewBox="0 0 16 16">
                                <path d="M12 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1zM4 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z"/>
                                <path d="M8 14a1 1 0 1 0 0-2 1 1 0 0 0 0 2"/>
                                </svg>
                            </div>
                            <div class="btn-text">
                                <span class="btn-title">Tablet</span>
                                <span class="btn-subtitle">Equipos de Calificacion</span>
                            </div>
                        </button>
                    </div>

                    <!-- En la sección de información, reemplaza el info-item actual por: -->
            <!--<div class="info-section">
                <div class="info-item">
                    <i class="fas fa-info-circle"></i>
                    <div class="info-content">
                        <strong>Sistema de Calificación UNIFRANZ</strong>
                        <div class="access-types">
                            <span class="access-type admin">Admin: Acceso completo a todas las sedes</span>
                            <span class="access-type gestor">Gestor: Reportes de sede específica</span>
                            <span class="access-type user">Personal: Calificación de áreas</span>
                            <span class="access-type invitado">Invitado: Evaluación anónima</span>
                        </div>
                    </div>
                </div>
            </div>-->

                    <div v-if="error" class="error-message">
                        <i class="fas fa-exclamation-triangle"></i>
                        {{ error }}
                    </div>
                </div>
            </div>
            
            <div class="rating-display">
                <div class="stars">
                    <i class="fas fa-star" v-for="n in 5" :key="'star-display-'+n"></i>
                </div>
                
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'Login',
    data() {
        return {
            error: null
        }
    },
    methods: {
        loginWithGoogle() {
            window.location.href = '/auth/google';
        },
        
        goToCalificar() {
            this.$router.push('/ubicacion');
        },
        
        getParticleStyle(n) {
            return {
                left: `${Math.random() * 100}%`,
                top: `${Math.random() * 100}%`,
                width: `${3 + Math.random() * 6}px`,
                height: `${3 + Math.random() * 6}px`,
                animationDelay: `${Math.random() * 5}s`,
                animationDuration: `${10 + Math.random() * 20}s`
            }
        },
        
        getStarStyle(n) {
            return {
                left: `${Math.random() * 100}%`,
                top: `${Math.random() * 100}%`,
                animationDelay: `${n * 0.3}s`,
                animationDuration: `${15 + n * 2}s`,
                fontSize: `${0.8 + Math.random() * 1.2}rem`
            }
        },
        
        getMedalStyle(n) {
            return {
                left: `${Math.random() * 100}%`,
                top: `${Math.random() * 100}%`,
                animationDelay: `${n * 0.5}s`,
                animationDuration: `${20 + n * 3}s`,
                fontSize: `${1 + Math.random() * 1.5}rem`
            }
        },
        
        getTrophyStyle(n) {
            return {
                left: `${Math.random() * 100}%`,
                top: `${Math.random() * 100}%`,
                animationDelay: `${n * 0.7}s`,
                animationDuration: `${25 + n * 4}s`,
                fontSize: `${1.2 + Math.random() * 1.8}rem`
            }
        },
        
        getBeamStyle(n) {
            return {
                left: `${n * 20}%`,
                animationDelay: `${n * 0.8}s`,
                animationDuration: `${8 + n * 2}s`
            }
        }
    }
}
</script>

<style scoped>
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

.app-container {
    min-height: 110vh;
    display: flex;
    justify-content: center;
    align-items: center;
    position: relative;
    overflow: hidden;
}

/* Fondo animado mejorado */
.animated-background {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%);
    background-size: 300% 300%;
    animation: gradientShift 15s ease infinite;
    z-index: -1;
}

@keyframes gradientShift {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

/* Partículas más dinámicas */
.particles {
    position: absolute;
    width: 100%;
    height: 100%;
    overflow: hidden;
}

.particle {
    position: absolute;
    background: radial-gradient(circle, rgba(255,255,255,0.8) 0%, rgba(255,255,255,0) 70%);
    border-radius: 50%;
    animation: particleFloat linear infinite;
}

@keyframes particleFloat {
    0% {
        transform: translateY(100vh) translateX(0) scale(0);
        opacity: 0;
    }
    10% {
        opacity: 1;
        transform: translateY(80vh) translateX(20px) scale(1);
    }
    90% {
        opacity: 1;
        transform: translateY(20vh) translateX(-20px) scale(1);
    }
    100% {
        transform: translateY(0) translateX(0) scale(0);
        opacity: 0;
    }
}

/* Elementos flotantes más llamativos */
.floating-elements {
    position: absolute;
    width: 100%;
    height: 100%;
    overflow: hidden;
}

.floating-star, .floating-medal, .floating-trophy {
    position: absolute;
    color: rgba(255, 255, 255, 0.7);
    animation: floatElement linear infinite;
}

.floating-star {
    animation-name: floatStar;
    color: #fbbf24;
}

.floating-medal {
    animation-name: floatMedal;
    color: #60a5fa;
}

.floating-trophy {
    animation-name: floatTrophy;
    color: #f87171;
}

@keyframes floatStar {
    0% {
        transform: translateY(100vh) rotate(0deg) scale(0);
        opacity: 0;
    }
    10% {
        opacity: 0.8;
        transform: translateY(80vh) rotate(90deg) scale(1);
    }
    90% {
        opacity: 0.8;
        transform: translateY(20vh) rotate(270deg) scale(1);
    }
    100% {
        transform: translateY(0) rotate(360deg) scale(0);
        opacity: 0;
    }
}

@keyframes floatMedal {
    0% {
        transform: translateY(100vh) rotate(0deg) scale(0);
        opacity: 0;
    }
    10% {
        opacity: 0.8;
        transform: translateY(80vh) rotate(-120deg) scale(1);
    }
    90% {
        opacity: 0.8;
        transform: translateY(20vh) rotate(120deg) scale(1);
    }
    100% {
        transform: translateY(0) rotate(240deg) scale(0);
        opacity: 0;
    }
}

@keyframes floatTrophy {
    0% {
        transform: translateY(100vh) rotate(0deg) scale(0);
        opacity: 0;
    }
    10% {
        opacity: 0.8;
        transform: translateY(80vh) rotate(180deg) scale(1);
    }
    90% {
        opacity: 0.8;
        transform: translateY(20vh) rotate(540deg) scale(1);
    }
    100% {
        transform: translateY(0) rotate(720deg) scale(0);
        opacity: 0;
    }
}

/* Rayos de luz */
.light-beams {
    position: absolute;
    width: 100%;
    height: 100%;
    overflow: hidden;
}

.beam {
    position: absolute;
    top: -50%;
    width: 2px;
    height: 200%;
    background: linear-gradient(to bottom, transparent, rgba(255,255,255,0.4), transparent);
    animation: beamMove linear infinite;
}

@keyframes beamMove {
    0% {
        transform: translateY(-100%) rotate(15deg);
        opacity: 0;
    }
    50% {
        opacity: 0.6;
    }
    100% {
        transform: translateY(100%) rotate(15deg);
        opacity: 0;
    }
}

/* Contenedor de login */
.login-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 30px;
    z-index: 1;
}

.login-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.4);
    width: 420px;
    overflow: hidden;
    animation: cardAppear 0.8s ease-out;
}

@keyframes cardAppear {
    from {
        opacity: 0;
        transform: scale(0.9) translateY(20px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

/* Sección de marca */
.brand-section {
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    padding: 30px;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.brand-section::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0) 70%);
    animation: pulse 4s infinite;
}

@keyframes pulse {
    0% { transform: scale(0.8); opacity: 0.5; }
    50% { transform: scale(1.2); opacity: 0.8; }
    100% { transform: scale(0.8); opacity: 0.5; }
}

.logo-wrapper {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
    margin-bottom: 10px;
}

.logo {
    width: 50px;
    height: 50px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: logoRotate 10s linear infinite;
}

@keyframes logoRotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.logo i {
    font-size: 1.8rem;
    color: white;
}

.brand-section h1 {
    font-size: 1.8rem;
    font-weight: 700;
    color: white;
    margin: 0;
    text-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.system-name {
    font-size: 0.9rem;
    color: rgba(255, 255, 255, 0.9);
    margin: 5px 0 0;
    position: relative;
    z-index: 1;
}

/* Sección de acceso */
.access-section {
    padding: 30px;
}

.access-section h2 {
    font-size: 1.4rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 25px;
    text-align: center;
}

/* Grupo de botones horizontal */
.button-group {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin-bottom: 20px;
}

.btn {
    display: flex;
    align-items: center;
    padding: 16px 20px;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    transition: left 0.5s;
}

.btn:hover::before {
    left: 100%;
}

.btn-icon {
    width: 45px;
    height: 45px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
    flex-shrink: 0;
}

.btn-icon i {
    font-size: 1.6rem;
}

.btn-text {
    flex: 1;
    text-align: left;
}

.btn-title {
    display: block;
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 2px;
}

.btn-subtitle {
    display: block;
    font-size: 0.85rem;
    opacity: 0.8;
}

/* Botón de Google mejorado - REMOVIDO EL FONDO GRADIENTE ANTERIOR */
.google-btn {
    background: white;
    border: 2px solid #e5e7eb;
    color: #1f2937;
}

.google-btn:hover {
    background: #f9fafb;
    border-color: #d1d5db;
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

.google-icon {
    background: white;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    overflow: hidden;
}

/* Botón de Invitado */
.guest-btn {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    border: 2px solid transparent;
}

.guest-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(16, 185, 129, 0.4);
}

.guest-icon {
    background: rgba(255, 255, 255, 0.2);
    color: white;
}

/* Sección de información */
.info-section {
    margin-bottom: 15px;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 15px;
    background: #f0f9ff;
    border-radius: 10px;
    border-left: 4px solid #0ea5e9;
    color: #0369a1;
    font-size: 0.9rem;
}

.info-item i {
    color: #0ea5e9;
    font-size: 1.2rem;
}

/* Mensaje de error */
.error-message {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 15px;
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 10px;
    color: #dc2626;
    font-size: 0.9rem;
    animation: shake 0.5s;
}

.error-message i {
    font-size: 1.2rem;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}

/* Display de calificación */
.rating-display {
    text-align: center;
    color: white;
    animation: fadeIn 1s ease-out 0.5s both;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.stars {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-bottom: 8px;
}

.stars i {
    font-size: 1.4rem;
    color: #fbbf24;
    animation: starPulse 2s infinite;
    animation-delay: calc(var(--i) * 0.2s);
}

.stars i:nth-child(1) { --i: 1; }
.stars i:nth-child(2) { --i: 2; }
.stars i:nth-child(3) { --i: 3; }
.stars i:nth-child(4) { --i: 4; }
.stars i:nth-child(5) { --i: 5; }

@keyframes starPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.2); }
}

.rating-display p {
    font-size: 0.9rem;
    opacity: 0.9;
}

/* Responsive */
@media (max-width: 480px) {
    .login-card {
        width: 90%;
        max-width: 380px;
    }
    
    .brand-section {
        padding: 25px 20px;
    }
    
    .access-section {
        padding: 25px 20px;
    }
    
    .logo {
        width: 45px;
        height: 45px;
    }
    
    .logo i {
        font-size: 1.6rem;
    }
    
    .brand-section h1 {
        font-size: 1.6rem;
    }
    
    .btn {
        padding: 14px 16px;
    }
    
    .btn-icon {
        width: 40px;
        height: 40px;
    }
    
    .btn-icon i {
        font-size: 1.4rem;
    }
    
    .btn-title {
        font-size: 1rem;
    }
    
    .btn-subtitle {
        font-size: 0.8rem;
    }
}

.info-content {
    flex: 1;
}

.access-types {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    margin-top: 0.5rem;
}

.access-type {
    font-size: 0.8rem;
    padding: 0.2rem 0.5rem;
    border-radius: 4px;
    font-weight: 500;
}

.access-type.admin {
    background: #fef2f2;
    color: #dc2626;
    border-left: 3px solid #dc2626;
}

.access-type.gestor {
    background: #f0f9ff;
    color: #0369a1;
    border-left: 3px solid #0369a1;
}

.access-type.user {
    background: #f0fdf4;
    color: #059669;
    border-left: 3px solid #059669;
}

.access-type.invitado {
    background: #fafafa;
    color: #6b7280;
    border-left: 3px solid #6b7280;
}
</style>