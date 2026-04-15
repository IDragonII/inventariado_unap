import { httpClient } from '../boot/axios'

class AreaService {
    constructor() {
        this.http = httpClient
        this.resource = 'auth/areas'
    }

    async getAreas(params) {
        try {
            const response = await this.http.get(this.resource, {params})
            return response
        } catch (error) {
            console.error('Error al obtener areas:', error)
            throw error
        }
    }
    async createArea(params){
        try {
            return await this.http.post(this.resource, params)
        } catch (error) {
            console.log(error)
            throw error
        }
    }
    async updateArea(id, params){
        try {
            await this.http.put(`${this.resource}/${id}`, params)
        } catch (error) {
            console.log(error)
            throw error
        }
    }

    async deleteArea(id){
        await this.http.delete(`${this.resource}/${id}`)
    }
}

export const areaService = new AreaService() 