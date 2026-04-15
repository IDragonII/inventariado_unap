import { boot } from 'quasar/wrappers'
import { AxiosAdapter } from '../adapters/AxiosAdapter'
import { inactivityService } from '../services/inactivityService'

if (!process.env.API_URL) {
    console.warn('API_URL no está definida en las variables de entorno')
}

const httpClient = new AxiosAdapter({
    baseUrl: process.env.API_URL || '',
    getToken: () => {
        const token = localStorage.getItem('token')
        return token || null
    }
})

export default boot(({ app }) => {
    app.config.globalProperties.$http = httpClient
    app.provide('http', httpClient)
    
    // Inicializar el servicio de inactividad
    inactivityService.resetTimer()
})

export { httpClient }