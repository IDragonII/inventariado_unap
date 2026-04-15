import { httpClient } from '../boot/axios'

class ActivoService {

    constructor() {
        this.http = httpClient
        this.resource = 'auth/activos'
    }
    // CRUD básico
    async getActivos (params) {
        try {
            const response = await this.http.get(this.resource, { params })
            return {
                data: response.data,
                pagination: {
                    page: response.meta?.current_page,
                    rowsPerPage: response.meta?.per_page,
                    rowsNumber: response.meta?.total,
                    sortBy: params?.sort_by || 'id'
                }
            }
        } catch (error) {
            console.error('Error: ', error)
            throw error
        }
    }

    async getActivo (id) {
        try {
            const response = await this.http.get(this.resource, id)
            return response.data
        } catch (error) {
            console.error('Error: ', error)
            throw error
        }
    }

    async createActivo (data) {
        try {
            const response = await this.http.post(this.resource, data)
            return response
        } catch (error) {
            console.error('Error: ', error)
            throw error
        }
    }

    async updateActivo (id, data) {
        try {
            const response = await this.http.put(`${this.resource}/${id}`, data)
            return response
        } catch (error) {
            console.error('Error: ', error)
            throw error
        }
    }

    async deleteActivo (id) {
        try {
            const response = await this.http.delete(`${this.resource}/${id}`)
            return response.data
        } catch (error) {
            console.error('Error: ', error)
            throw error
        }
    }

    async getFacultades () {
        try {
            const response = await this.http.get('/auth/entidades')
            return response.data
        } catch (error) {
            console.error('Error: ', error)
            throw error
        }
    }

    async getDepartamentos (id) {
        try {
            const response = await this.http.get('auth/departamentos/all', { params: { id } })
            return response.data
        } catch (error) {
            console.error('Error: ', error)
            throw error
        }
    }

    async getUbicaciones (id) {
        try {
            const response = await this.http.get('auth/ubicaciones/all', { params: {id}})
            return response.data
        } catch (error) {
            console.error('Error: ', error)
            throw error
        }
    }

    async getCategorias () {
        try {
            const response = await this.http.get('auth/catalogobienes')
            console.log(response)
            return response.data
        } catch (error) {
            console.error('Error: ', error)
            throw error
        }
    }

    async getEstados () {
        return [
            { id: 'A', nombre: 'Activo' },
            { id: 'I', nombre: 'Inactivo' }
        ]
    }

    async createEntrega (data) {
        try {
            const response = await this.http.post('auth/movimientos', data)
            return response.data
        } catch (error) {
            console.error('Error', error)
            throw error
        }
    }

    async descargarPDFEntrega(movimientoId) {
        try {
            const response = await this.http.get(`auth/movimientos/${movimientoId}/pdf`, {
                responseType: 'blob'
            })
            return response
        } catch (error) {
            console.error('Error al descargar el PDF de la entrega:', error)
            throw error
        }
    }

    async getMovimientos (id) {
        try {
            const response = await this.http.get(`movimientos-activos/activo/${id}`)
            return response
        } catch (error) {
            console.error('Error al optener movimientos: ', error)
            throw error
        }
    }

    async getDashboard(){
        try {
            const response = await this.http.get('auth/activos/dashboard')
            return response
        } catch (error) {
            console.error('Error al optener movimientos: ', error)
            throw error
        }
    }

    async getBienesFaltantes (params) {
        return await this.http.get('auth/activos/inventariador', {params})
    }
}
export const activoService = new ActivoService()


    // Mantenimientos y movimientos
    //getMantenimientos: async (params = {}) => {
    //    const response = await httpClient.get('/auth/activo/mantenimientos', { params })
    //    return response.data
    //},

    //getMovimientos: async (params = {}) => {
    //    const response = await httpClient.get('/auth/activo/movimientos', { params })
    //    return response.data
    //},

    // QR
    //generateQR: async (id) => {
    //    const response = await httpClient.get(`/auth/activo/qr/${id}`)
    //    return response.data
    //},

    //scanQR: async (data) => {
    //    const response = await httpClient.post('/auth/qr-scan', data)
    //    return response.data
    //},

    // Exportación
    //exportar: async (type, params = {}) => {
    //    const response = await httpClient.get(`/auth/export/${type}`, {
    //        params,
    //        responseType: 'blob'
    //    })
    //    return response.data
    //},

    // Reportes
    //getReporteEstado: async (params = {}) => {
    //    const response = await httpClient.get('/auth/reportes/estado', { params })
    //    return response.data
    //},

    //getReporteUbicacion: async (params = {}) => {
    //    const response = await httpClient.get('/auth/reportes/ubicacion', { params })
    //    return response.data
    //},

    //getReporteDepreciacion: async (params = {}) => {
    //    const response = await httpClient.get('/auth/reportes/depreciacion', { params })
    //    return response.data
    //},

    // Categorías y otros datos de referencia
