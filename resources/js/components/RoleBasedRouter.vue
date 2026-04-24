<template>
    <div class="role-router">
        <div v-if="loading" class="loading-container">
            <div class="spinner"></div>
            <p>Verificando acceso...</p>
        </div>
        <div v-else>
            <router-view></router-view>
        </div>
    </div>
</template>

<script>
export default {
    name: 'RoleBasedRouter',
    data() {
        return {
            loading: true,
            user: null
        }
    },
    async mounted() {
        await this.checkUserRole();
    },
    methods: {
        async checkUserRole() {
            try {
                const response = await fetch('/api/user', {
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                // Verificar que la respuesta sea JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    console.log('🔐 Respuesta no es JSON, usuario no autenticado');
                    this.loading = false;
                    return;
                }
                
                if (response.ok) {
                    this.user = await response.json();
                    console.log('👤 Usuario detectado:', this.user.role);
                    this.redirectByRole();
                } else {
                    console.log('🔐 Usuario no autenticado');
                    this.loading = false;
                }
            } catch (error) {
                console.error('Error verificando usuario:', error);
                // Si el error es de parsing JSON, probablemente recibimos HTML
                if (error.message && error.message.includes('JSON')) {
                    console.log('🔐 Respuesta no es JSON válido, usuario no autenticado');
                }
                this.loading = false;
            }
        },
        
        redirectByRole() {
            if (!this.user) {
                this.loading = false;
                return;
            }
            
            const currentPath = this.$route.path;
            console.log('📍 Ruta actual:', currentPath, 'Rol:', this.user.role);
            
            // 🔥 CRÍTICO: Solo redirigir si está en login o ruta incorrecta
            if (currentPath === '/login' || !this.isCorrectRoute(currentPath)) {
                console.log('🔄 Redirigiendo según rol...');
                
                switch (this.user.role) {
                    case 'admin':
                        if (currentPath !== '/admin/dashboard') {
                            console.log('👑 Redirigiendo admin a dashboard');
                            this.$router.push('/admin/dashboard');
                        }
                        break;
                        
                    case 'gestor':
                        if (currentPath !== '/gestor/dashboard') {
                            console.log('👨‍💼 Redirigiendo gestor a dashboard');
                            this.$router.push('/gestor/dashboard');
                        }
                        break;
                        
                    case 'user':
                        if (currentPath !== '/areas' && currentPath !== '/calificar') {
                            console.log('👤 Redirigiendo usuario a áreas');
                            this.$router.push('/areas');
                        }
                        break;
                }
            }
            
            this.loading = false;
        },
        
        isCorrectRoute(path) {
            if (!this.user) return false;
            
            switch (this.user.role) {
                case 'admin':
                    return path.startsWith('/admin');
                case 'gestor':
                    return path.startsWith('/gestor');
                case 'user':
                    return path.startsWith('/calificar') || path.startsWith('/areas');
                default:
                    return path === '/login';
            }
        }
    }
}
</script>

<style scoped>
.loading-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.spinner {
    border: 4px solid rgba(255, 255, 255, 0.3);
    border-top: 4px solid white;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 1s linear infinite;
    margin-bottom: 1rem;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>