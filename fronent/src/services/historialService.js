import { httpClient } from '../boot/axios'

class HistorialService {
  constructor() {
    this.http = httpClient.instance || httpClient
    this.resource = 'historial'
  }

  async importarHistorial(file) {
    const formData = new FormData()
    formData.append('archivo', file)
    const response = await this.http.post(`${this.resource}/importar`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    return response
  }

  async statusImport(exportId) {
    const response = await this.http.get(`${this.resource}/importar/${exportId}/status`)
    return response
  }

  async eliminarImport(exportId) {
    await this.http.delete(`${this.resource}/importar/${exportId}`)
  }
}

export const historialService = new HistorialService()
