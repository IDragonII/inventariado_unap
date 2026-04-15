import { httpClient } from '../boot/axios'

class OficinaService {
    constructor() {
        this.http = httpClient
        this.resource = 'auth/oficinas'
    }

    async getOficinas(params) {
        try {
            const response = await this.http.get(this.resource, {params})
            return response
        } catch (error) {
            console.error('Error al obtener Oficinas:', error)
            throw error
        }
    }

    async createOficina(oficina) {
        try {
            const response = await this.http.post(this.resource, oficina)
            return response.data
        } catch (error) {
            console.error('Error al crear oficina:', error)
            throw error
        }
    }

    async updateOficina(id, oficina) {
        try {
            const response = await this.http.put(`${this.resource}/${id}`, oficina)
            return response.data
        } catch (error) {
            console.error('Error al actualizar oficina:', error)
            throw error
        }
    }

    async deleteOficina(id) {
        try {
            const response = await this.http.delete(`${this.resource}/${id}`)
            return response.data
        } catch (error) {
            console.error('Error al eliminar departamento:', error)
            throw error
        }
    }
}

export const oficinaService = new OficinaService()