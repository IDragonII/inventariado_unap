import { httpClient } from '../boot/axios'

class DeclaracionService {
    constructor() {
        this.http = httpClient
        this.resource = 'auth/declaraciones'
    }

    async getDeclaraciones(params) {
        try {
            const response = await this.http.get(this.resource, {params})
            return response
        } catch (error) {
            console.error('Error al obtener declaraciones:', error)
            throw error
        }
    }

    async descargarPDFEntrega(declaracionId) {
        try {
            const response = await this.http.get(`auth/declaraciones/${declaracionId}/pdf`, {
                responseType: 'blob'
            })
            return response
        } catch (error) {
            console.error('Error al descargar el PDF de la declaracion de uso:', error)
            throw error
        }
    }

    async descargarPDFUso(){
        try {
            const response = await this.http.get('auth/declaraciones/endpoint', {
                responseType: 'blob'
            })
            return response
        } catch (error) {
            console.error('Error al descargar el PDF de la declaracion de uso:', error)
            throw error
        }
    }

    async createDeclaracion(declaracion) {
        try {
            const response = await this.http.post(this.resource, declaracion);
            return response.data;
        } catch (error) {
            console.error('Error al crear declaracion:', error);
            throw error;
        }
    }

}
export const declaracionService = new DeclaracionService()