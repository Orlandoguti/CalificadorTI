import { createRouter, createWebHistory } from 'vue-router';
import Login from '../components/Login.vue';
import LocationRequester from '../components/LocationRequester.vue';
import Areas from '../components/Areas.vue';
import Calificacion from '../components/Calificacion.vue';

const routes = [
    {
        path: '/gestor/dashboard',
        name: 'GestorDashboard',
        component: () => import('../components/GestorDashboard.vue')
    },
    {
        path: '/admin/dashboard',
        name: 'AdminDashboard',
        component: () => import('../components/AdminDashboard.vue')
    },
    {
        path: '/login',
        name: 'login',
        component: Login
    },
    {
        path: '/ubicacion',
        name: 'ubicacion',
        component: LocationRequester
    },
    {
        path: '/areas',
        name: 'areas',
        component: Areas
    },
    {
        path: '/calificar',
        name: 'calificar',
        component: Calificacion
    },
    {
        path: '/',
        redirect: '/login'
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes
});

// Funciones auxiliares
async function checkAuth() {
    try {
        const response = await fetch('/api/user', {
            credentials: 'include',
            headers: { 'Accept': 'application/json' }
        });
        
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.ok;
        }
        return false;
    } catch (error) {
        return false;
    }
}

async function checkAdmin() {
    try {
        const response = await fetch('/api/user', {
            credentials: 'include',
            headers: { 'Accept': 'application/json' }
        });
        
        if (response.ok) {
            const user = await response.json();
            return user.role === 'admin';
        }
        return false;
    } catch (error) {
        return false;
    }
}

async function checkLocation() {
    const sedeGuardada = localStorage.getItem('sede_seleccionada');
    return !!sedeGuardada;
}

// Guard de navegación
router.beforeEach(async (to, from, next) => {
    console.log('🔄 Navegando de:', from.path, 'a:', to.path);
    
    const isAuthenticated = await checkAuth();
    const primeraVez = localStorage.getItem('primera_vez_area_seleccionada');
    const tieneArea = localStorage.getItem('area_seleccionada');
    const tieneSede = localStorage.getItem('sede_seleccionada');
    
    let userRole = null;
    let userSedeId = null;
    
    if (isAuthenticated) {
        try {
            const userResponse = await fetch('/api/user', {
                credentials: 'include',
                headers: { 'Accept': 'application/json' }
            });
            
            if (userResponse.ok) {
                const userData = await userResponse.json();
                userRole = userData.role;
                userSedeId = userData.sede_id;
                
                localStorage.setItem('user_role', userRole);
                localStorage.setItem('user_sede_id', userSedeId || '');
            }
        } catch (error) {
            console.error('Error obteniendo datos del usuario:', error);
        }
    }
    
    console.log('🔐 Estado:', { isAuthenticated, userRole, userSedeId, tieneSede });
    
    // Gestor sin sede
    if (isAuthenticated && userRole === 'gestor' && (!userSedeId || userSedeId === 'null')) {
        if (to.path !== '/ubicacion') {
            console.log('🔍 Gestor sin sede, redirigiendo a ubicación');
            next('/ubicacion');
            return;
        }
    }
    
    // Modo kiosko
    if (primeraVez === 'true' && tieneArea && tieneSede) {
        if (['/ubicacion', '/areas', '/login'].includes(to.path)) {
            next('/calificar');
            return;
        }
    }
    
    // Verificaciones normales
    if (to.meta.requiresAuth && !isAuthenticated) {
        next('/login');
        return;
    }

    if (to.meta.requiresAdmin && !(await checkAdmin())) {
        next('/areas');
        return;
    }

    if (to.meta.requiresLocation && !tieneSede) {
        next('/ubicacion');
        return;
    }

    next();
});

export default router;