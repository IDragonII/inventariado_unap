import { authService } from './authService'

class InactivityService {
    constructor() {
        this.timeoutId = null
        this.inactivityTime = 30 * 60 * 1000 // 30 minutos por defecto
        this.setupInactivityTimer()
    }

    setupInactivityTimer() {
        // Eventos que resetean el temporizador
        const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart']
        
        events.forEach(event => {
            document.addEventListener(event, () => this.resetTimer())
        })
    }

    resetTimer() {
        if (this.timeoutId) {
            clearTimeout(this.timeoutId)
        }
        
        this.timeoutId = setTimeout(() => {
            this.handleInactivity()
        }, this.inactivityTime)
    }

    async handleInactivity() {
        try {
            await authService.logout()
            // Usar window.location en lugar del router
            window.location.href = '/login'
        } catch (error) {
            console.error('Error al manejar inactividad:', error)
            // Asegurarse de que el usuario sea redirigido incluso si hay un error
            window.location.href = '/login'
        }
    }

    setInactivityTime(minutes) {
        this.inactivityTime = minutes * 60 * 1000
        this.resetTimer()
    }
}

export const inactivityService = new InactivityService() 