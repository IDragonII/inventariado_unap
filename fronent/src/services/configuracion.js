import { httpClient } from "../boot/axios";

class ConfiguracionService {
    constructor(){
        this.http = httpClient
        this.resource = 'auth/configuracion'
    }

    async list(params = {}){
        try {
            const response = await this.http.get(this.resource, { params });
            return response;
        } catch (error) {
            console.error('Error al listar configuraciones: ', error)
            throw error
        }
    }

    async create(data){
        try {
            const response = await this.http.post(this.resource, data);
            return response;
        } catch (error) {
            console.error('Error al crear configuración: ', error)
            throw error
        }
    }

    async show(id){
        try {
            const response = await this.http.get(`${this.resource}/${id}`);
            return response;
        } catch (error) {
            console.error('Error al obtener configuración: ', error)
            throw error
        }
    }

    async update(id, data){
        try {
            const response = await this.http.put(`${this.resource}/${id}`, data);
            return response;
        } catch (error) {
            console.error('Error al actualizar configuración: ', error)
            throw error
        }
    }

    async delete(id){
        try {
            const response = await this.http.delete(`${this.resource}/${id}`);
            return response;
        } catch (error) {
            console.error('Error al eliminar configuración: ', error)
            throw error
        }
    }
}

export const configuracionService = new ConfiguracionService()