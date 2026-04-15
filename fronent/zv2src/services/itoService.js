import { httpClient } from '../boot/axios'

class ItoService {
    constructor() {
        this.http = httpClient
        this.resource = 'auth/itos'
    }

    async getItos(params) {
        try {
            const response = await this.http.get(this.resource, {params})
            return response
        } catch (error) {
            console.error('Error al obtener itos:', error)
            throw error
        }
    }
    async createIto(params){
        try {
            return await this.http.post(this.resource, params)
        } catch (error) {
            console.log(error)
            throw error
        }
    }
    async updateIto(id, params){
        try {
            await this.http.put(`${this.resource}/${id}`, params)
        } catch (error) {
            console.log(error)
            throw error
        }
    }

    async deleteIto(id){
        await this.http.delete(`${this.resource}/${id}`)
    }

    async reporte(params){
        const response = await this.http.get('auth/declaraciones/reporte', {params})
        return response
    }
}

export const itoService = new ItoService() 