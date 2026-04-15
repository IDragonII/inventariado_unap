import { httpClient } from '../boot/axios'
import { useAuthStore } from 'src/stores/auth-store'
class AuthService {
    constructor() {
        this.http = httpClient
        this.resource = 'auth'
    }

    async login(credentials) {
        try {
            const response = await this.http.post(`${this.resource}/login`, credentials)
            if (response.data?.access_token) {
                //localStorage.setItem('token', response.data.data.access_token)
                return {
                    token: response.data.access_token,
                    user: response.data.user,
                    role: response.data.role
                }
            }
            
            throw new Error('No se recibió token en la respuesta')
        } catch (error) {
            console.error('Error en login:', error)
            throw error
        }
    }

    async logout() {
        try {
            await this.http.post(`${this.resource}/logout`)
        } finally {
            localStorage.removeItem('token')
        }
    }

    async getCurrentUser() {
        const auth = useAuthStore()
        try {
            if(auth.isSoftware || auth.isInstaller){
                const response = await this.http.get(`${this.resource}/meusuario`)
                return response.data.usuario
            }
            else{
                const response = await this.http.get(`${this.resource}/me`)
                return response.data.user
            }
        } catch (error) {
            console.error('Error al obtener usuario actual:', error)
            throw error
        }
    }

    async getActiveSessions() {
        try {
            const response = await this.http.get(`${this.resource}/me`)
            return response.data.user.active_sessions
        } catch (error) {
            console.error('Error al obtener sesiones activas:', error)
            throw error
        }
    }

    async refreshToken() {
        try {
            const response = await this.http.post(`${this.resource}/refresh`)
            if (response.data?.access_token) {
                localStorage.setItem('token', response.data.data.access_token)
                return response.data.data
            }
            throw new Error('No se recibió token en la respuesta')
        } catch (error) {
            console.error('Error al refrescar token:', error)
            throw error
        }
    }

    isAuthenticated() {
        return !!localStorage.getItem('token')
    }

    async updateProfile(profileData) {
        try {
            const response = await this.http.put(`${this.resource}/profile`, profileData)
            return response.data.data
        } catch (error) {
            console.error('Error al actualizar perfil:', error)
            throw error
        }
    }

    async updatePassword(passwordData) {
        try {
            const response = await this.http.put(`${this.resource}/profile`, passwordData)
            return response.data.data
        } catch (error) {
            console.error('Error al actualizar contraseña:', error)
            throw error
        }
    }

    async revokeSession(sessionId) {
        try {
            const response = await this.http.delete(`${this.resource}/sessions/${sessionId}`)
            if (response.success) {
                return response
            }
            throw new Error(response.message || 'Error al revocar la sesión')
        } catch (error) {
            console.error('Error al revocar sesión:', error)
            throw error
        }
    }

    async revokeAllSessions() {
        try {
            const response = await this.http.delete(`${this.resource}/sessions`)
            if (response.success) {
                return response
            }
            throw new Error(response.message || 'Error al revocar todas las sesiones')
        } catch (error) {
            console.error('Error al revocar todas las sesiones:', error)
            throw error
        }
    }
    async getUsuarios (dni = null) {
        try {
            const response = await this.http.get(`${this.resource}/usuarios`, {params: {dni: dni}})
            return response
        } catch (error) {
            console.error('Error al obtener usuarios: ', error)
            throw error
        }
    }
}

export const authService = new AuthService()