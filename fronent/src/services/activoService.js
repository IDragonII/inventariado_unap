import { httpClient } from '../boot/axios'

class ActivoService {

    constructor() {
    // Usamos .instance para acceder al Axios real dentro de tu adaptador
    this.http = httpClient.instance || httpClient; 
    this.resource = 'auth/activos';
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
    async iniciarExport(filtros) {
    // El 'this.http.post' ya devuelve el contenido de 'data' gracias a tu adaptador
    const response = await this.http.post(`${this.resource}/exportar`, filtros)
    return response // <--- QUITA EL '.data' AQUÍ
}

    async iniciarExportActas(filtros) {
    const response = await this.http.post(`${this.resource}/exportar-actas`, filtros)
    return response
}

    async statusExport(exportId) {
    const response = await this.http.get(`${this.resource}/exportar/${exportId}/status`)
    return response // <--- QUITA EL '.data' AQUÍ
}

    async eliminarExport(exportId) {
        await this.http.delete(`${this.resource}/exportar/${exportId}`)
    }

    async importar(formData) {
        try {
            const response = await this.http.post(`${this.resource}/importar`, formData, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            })
            return response
        } catch (error) {
            console.error('Error en importación:', error)
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

    async habilitarActivo(data){
        try {
            const response=await this.http.put(`${this.resource}/habilitar`, data)
            console.log(response)
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

    async getBienesHistorial(params){
        const response = await this.http.get('auth/activos/historial', {params})
        return response
    }
    async getfaltantesReporte(params){
        const response=await this.http.get('auth/activos/faltareporte', {params})
        return response
    }
    async getfaltantesReportePdf(params){
        return await this.http.get('auth/activos/faltareportepdf', {params, responseType: 'blob'})
    }
    async getBienesHistorialPdf(params){
        console.log(params)
        return await this.http.get('auth/activos/historialpdf', {params, responseType: 'blob'})
    }
    async getReporteData (params) {
        //console.log(params)
        const response = await this.http.get(`auth/activos/reporteinventario`, {params, responseType: 'blob'})
        return response
    }
    async getReportePdf (params) {
        const response = await this.http.get(`auth/activos/reportepdf`, {params})
        return response
    }
    async getExportarActas (params) {
        const response = await this.http.get(`auth/activos/exportar-actas`, {params})
        return response
    }
    async reportev2() {
        const response = await this.http.get('auth/activos/export-activos', {
            responseType: 'blob'
        });

        // Verificamos que la respuesta sea un Blob válido
        if (!(response.data instanceof Blob)) {
            console.error('La respuesta no es un Blob válido:', response.data);
            return;
        }

        const url = window.URL.createObjectURL(response.data);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'activos.xlsx';
        document.body.appendChild(a);
        a.click();
        a.remove();
        window.URL.revokeObjectURL(url);
    }
    async reportesoftware(params){
        const response=await this.http.get('auth/activos/reporteSoftwareOTI', {params, responseType: 'blob'})
        return response
    }

    // Consulta por DNI (público)
    async consultarPorDni(dni, sessionId) {
        const response = await this.http.post('/activos/consultar-por-dni', {
            dni: dni,
            session_id: sessionId
        })
        return response
    }

    async exportarPdfDni(dni, ids = null) {
        const response = await this.http.post('/activos/consultar-por-dni/pdf', {
            dni: dni,
            ids: ids
        }, { responseType: 'blob' })
        return response
    }

    async getActivosByUser(dni) {
        try {
            const response = await this.http.post('/activos/consultar-por-dni', { dni })
            return response
        } catch (error) {
            console.error('Error:', error)
            throw error
        }
    }

    async regularizacion(datoRef, ids) {
        try {
            const response = await this.http.post('/activos/regularizacion', {
                dato_ref: datoRef,
                ids: ids
            })
            return response
        } catch (error) {
            console.error('Error:', error)
            throw error
        }
    }

    async exportarPdfDniSinItem(dni, ids = null) {
        try {
            const response = await this.http.post('/activos/consultar-por-dni/pdf-sin-item', {
                dni: dni,
                ids: ids
            }, { responseType: 'blob' })
            return response
        } catch (error) {
            console.log('Error en exportarPdfDniSinItem:', error)
            if (error.response && error.response.status === 404) {
                const data = error.response.data
                let mensaje = 'Error al exportar PDF'
                if (data instanceof Blob) {
                    const text = await data.text()
                    try {
                        const json = JSON.parse(text)
                        mensaje = json.message || 'Los activos seleccionados ya tienen item asignado'
                    } catch {
                        mensaje = text || mensaje
                    }
                } else if (data && typeof data === 'object') {
                    mensaje = data.message || 'Los activos seleccionados ya tienen item asignado'
                }
                throw new Error(mensaje)
            }
            throw error
        }
    }
}
export const activoService = new ActivoService()