import { httpClient } from '../boot/axios'

class EntidadService {
    constructor() {
        this.http = httpClient
        this.resource = 'auth/entidades'
    }

    async getEntidades(params) {
        try {
            const response = await this.http.get(this.resource, {params})
            return response
        } catch (error) {
            console.error('Error al obtener entidades:', error)
            throw error
        }
    }
    async createEntidades(params){
        try {
            return await this.http.post(this.resource, params)
        } catch (error) {
            console.log(error)
            throw error
        }
    }
    async updateEntidades(id, params){
        try {
            await this.http.put(`${this.resource}/${id}`, params)
        } catch (error) {
            console.log(error)
            throw error
        }
    }

    async deleteEntidades(id){
        await this.http.delete(`${this.resource}/${id}`)
    }
}

export const entidadService = new EntidadService() 