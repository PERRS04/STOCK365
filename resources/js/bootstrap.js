import axios from 'axios';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
if (csrfToken) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
}

window.axios.interceptors.request.use(config => {
    return config;
}, error => {
    return Promise.reject(error);
});

window.axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response?.status === 401) {
            window.toast('Tu sesión ha expirado. Por favor inicia sesión de nuevo.', 'warning');
            setTimeout(() => { window.location.href = '/login'; }, 2000);
        } else if (error.response?.status === 403) {
            window.toast('No tienes permisos para realizar esta acción.', 'error');
        } else if (error.response?.status === 422) {
            const errors = error.response.data?.errors;
            if (errors) {
                const first = Object.values(errors)[0];
                window.toast(Array.isArray(first) ? first[0] : first, 'error');
            } else {
                window.toast(error.response.data?.message ?? 'Error de validación.', 'error');
            }
        } else if (error.response?.status >= 500) {
            window.toast('Error del servidor. Intente nuevamente.', 'error');
        } else if (!error.response) {
            window.toast('Sin conexión. Verifique su red.', 'error');
        }
        return Promise.reject(error);
    }
);
