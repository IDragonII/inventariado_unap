import { httpClient } from '../boot/axios'

class SoftwareInstalacionService {
    constructor() {
        this.http = httpClient
        this.resource = 'auth/software'
    }

    async getInstalaciones(params) {
        try {
            const response = await this.http.get('auth/software/activos', { params })
            return response
        } catch (error) {
            console.error('Error al obtener instalaciones:', error)
            throw error
        }
    }

    async getInstalacionesByActivo(activoId) {
        try {
            const response = await this.http.get(`${this.resource}/activo/${activoId}`)
            return response
        } catch (error) {
            console.error('Error al obtener instalaciones del activo:', error)
            throw error
        }
    }

    async createInstalacion(data) {
        try {
            const response = await this.http.post(this.resource, data)
            return response
        } catch (error) {
            console.error('Error al crear instalación:', error)
            throw error
        }
    }

    async updateInstalacion(id, data) {
        try {
            const response = await this.http.put(`${this.resource}/${id}`, data)
            return response
        } catch (error) {
            console.error('Error al actualizar instalación:', error)
            throw error
        }
    }

    async deleteInstalacion(id) {
        try {
            const response = await this.http.delete(`${this.resource}/${id}`)
            return response
        } catch (error) {
            console.error('Error al eliminar instalación:', error)
            throw error
        }
    }

    async instalarSoftwareEnActivo(activoId, softwareIds) {
        try {
            const data = {
                activo_id: activoId,
                software_id: softwareIds,
                fecha_instalacion: new Date().toISOString()
            }
            console.log(data)
            const response = await this.http.post(`${this.resource}/instalar`, data)
            console.log(response)
            return response
        } catch (error) {
            console.error('Error al instalar software:', error)
            throw error
        }
    }
}

export const softwareInstalacionService = new SoftwareInstalacionService() 