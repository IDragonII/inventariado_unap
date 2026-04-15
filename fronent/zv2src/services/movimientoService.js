import { httpClient } from '../boot/axios'

class MovimientosService {

    constructor() {
        this.http = httpClient
        this.resource = 'auth/movimientos'
    }

    async getMovimientos(params) {
        const response = await this.http.get(this.resource, {params})
        return response
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

    async autorizarEntrega (movimientoId) {
        try {
            await this.http.post(`auth/movimientos/${movimientoId}/entregar`)
            //await this.http.post(`movimientos-activos/${movimientoId}/entregar`)
        } catch (error) {
            console.error('Error al realizar entrega:', error)
            throw error
        }
    }

    async rechazarMovimiento (movimientoId, observacion) {
        try {
            await this.http.post(`auth/movimientos/${movimientoId}/rechazar`, observacion)
            //await this.http.post(`movimientos-activos/${movimientoId}/rechazar`)
        } catch (error) {
            console.error('Error al rechazar entrega:', error)
            throw error
        }
    }

    async aceptarEntrega (movimientoId, observacion) {
        try {
            await this.http.post(`auth/movimientos/${movimientoId}/recibir`, observacion)
            //await this.http.post(`movimientos-activos/${movimientoId}/recibir`)
        } catch (error) {
            console.error('Error al realizar entrega:', error)
            throw error
        }
    }
}
export const movimientoService = new MovimientosService()
