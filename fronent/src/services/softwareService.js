import { httpClient } from '../boot/axios'

class SoftwareService {
    constructor() {
        this.http = httpClient
        this.resource = 'auth/software'
    }

    async getSoftware(params) {
        try {
            const response = await this.http.get(this.resource, {params})
            return response
        } catch (error) {
            console.error('Error al obtener categorias:', error)
            throw error
        }
    }
    async getRelatedData(resource) {
        try {
            const response = await this.http.get(`auth/${resource}`, { params: { per_page: 1000 } });
            return response;
        } catch (error) {
            console.error(`Error al obtener ${resource}:`, error);
            throw error;
        }
    }
    async createSoftware(params){
        try {
            return await this.http.post(this.resource, params)
        } catch (error) {
            console.log(error)
            throw error
        }
    }
    async updateSoftware(id, params){
        try {
            await this.http.put(`${this.resource}/${id}`, params)
        } catch (error) {
            console.log(error)
            throw error
        }
    }

    async deleteSoftware(id){
        await this.http.delete(`${this.resource}/${id}`)
    }
}

export const softwareService = new SoftwareService()