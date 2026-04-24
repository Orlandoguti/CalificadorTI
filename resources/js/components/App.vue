<template>
    <div id="app">
        <!-- Indicador de estado offline -->
        <offline-indicator />
        <!-- Usar el NUEVO RoleBasedRouter -->
        <role-based-router />
    </div>
</template>

<script>
import RoleBasedRouter from './RoleBasedRouter.vue';
import OfflineIndicator from './OfflineIndicator.vue';

export default {
    name: 'App',
    components: {
        RoleBasedRouter,
        OfflineIndicator
    },
    data() {
        return {
            sedeSeleccionada: null
        }
    },
    provide() {
        return {
            sedeSeleccionada: this.sedeSeleccionada,
            setSedeSeleccionada: this.setSedeSeleccionada
        }
    },
    methods: {
        setSedeSeleccionada(sede) {
            this.sedeSeleccionada = sede;
            if (sede) {
                localStorage.setItem('admin_sede_seleccionada', JSON.stringify(sede));
            } else {
                localStorage.removeItem('admin_sede_seleccionada');
            }
        }
    },
    mounted() {
        const sedeGuardada = localStorage.getItem('admin_sede_seleccionada');
        if (sedeGuardada) {
            this.sedeSeleccionada = JSON.parse(sedeGuardada);
        }
    }
}
</script>

<style>
/* Estilos globales */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f5f5f5;
    color: #333;
    line-height: 1.6;
}

#app {
    min-height: 100vh;
}

/* Estilos para botones */
.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 16px;
    font-weight: 500;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
    text-align: center;
}

.btn-primary {
    background: #4f46e5;
    color: white;
}

.btn-primary:hover {
    background: #4338ca;
    transform: translateY(-2px);
}

.btn-secondary {
    background: #6b7280;
    color: white;
}

.btn-secondary:hover {
    background: #4b5563;
}

/* Utilidades */
.text-center {
    text-align: center;
}

.mt-4 {
    margin-top: 1rem;
}

.mb-4 {
    margin-bottom: 1rem;
}

.p-4 {
    padding: 1rem;
}

.loading {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 200px;
}

.error {
    background: #fee2e2;
    border: 1px solid #fecaca;
    color: #dc2626;
    padding: 1rem;
    border-radius: 6px;
    margin: 1rem 0;
}
</style>