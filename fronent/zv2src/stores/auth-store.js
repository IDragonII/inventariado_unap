import { defineStore } from 'pinia'

export const useAuthStore = defineStore('auth', {
    state: () => {
        // Cargar datos del localStorage al crear el estado
        const token = localStorage.getItem('token')
        const user = localStorage.getItem('user')
        const role = localStorage.getItem('role')
        
        let parsedUser = null
        let parsedRole = null
        
        try {
            if (user) {
                parsedUser = JSON.parse(user)
            }
            if (role) {
                parsedRole = JSON.parse(role)
            }
        } catch (error) {
            console.error('Error parseando JSON del localStorage:', error)
        }
        
        return {
            user: parsedUser,
            role: parsedRole,
            token: token || null
        }
    },
    getters: {
        isAuthenticated: (state) => !!state.token,
        isAdmin: (state) => state.role?.name === 'admin',
        isSupervisor: (state) => state.role?.name === 'supervisor',
        isResponsable: (state) => state.role?.name === 'responsable_departamento',
        isConsulta: (state) => state.role?.name === 'usuario_consulta',
        isInventariador: (state) => state.role?.name === 'inventarista',
        isSoftware: (state) => state.role?.name === 'software',
        isInstaller: (state) => state.role?.name === 'installer'
    },
    actions: {
        setAuth({ user, role, token }) {
            this.user = user
            this.role = role
            this.token = token
            localStorage.setItem('token', token)
            localStorage.setItem('user', JSON.stringify(user))
            localStorage.setItem('role', JSON.stringify(role))
        },
        logout() {
            this.user = null
            this.role = null
            this.token = null
            localStorage.removeItem('token')
            localStorage.removeItem('user')
            localStorage.removeItem('role')
        },
        loadFromStorage() {
            const token = localStorage.getItem('token')
            const user = localStorage.getItem('user')
            const role = localStorage.getItem('role')
            
            if (token && user && role) {
                this.token = token
                this.user = JSON.parse(user)
                this.role = JSON.parse(role)
            }
        },
        init() {
            this.loadFromStorage()
        }
    }
})