import { httpClient } from '../boot/axios'

class CategoriaService {
    constructor() {
        this.http = httpClient
        this.resource = 'auth/catalogobienes'
    }

    async getCategorias(params) {
        try {
            const response = await this.http.get(this.resource, {params})
            return response
        } catch (error) {
            console.error('Error al obtener categorias:', error)
            throw error
        }
    }
    async createCategoria(params){
        try {
            return await this.http.post(this.resource, params)
        } catch (error) {
            console.log(error)
            throw error
        }
    }
    async updateCategoria(id, params){
        try {
            await this.http.put(`${this.resource}/${id}`, params)
        } catch (error) {
            console.log(error)
            throw error
        }
    }

    async deleteCategoria(id){
        await this.http.delete(`${this.resource}/${id}`)
    }
}

export const categoriaService = new CategoriaService()