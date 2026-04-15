import { httpClient } from '../boot/axios'

class PermissionService {
  constructor() {
    this.resource = 'auth/permissions'
    this.http = httpClient
  }

  async getPermisos(params) {
    return this.http.get(this.resource, { params })
  }

  async createPermiso(data) {
    return this.http.post(this.resource, data)
  }

  async updatePermiso(id, data) {
    return this.http.put(`${this.resource}/${id}`, data)
  }

  async deletePermiso(id) {
    return this.http.delete(`${this.resource}/${id}`)
  }
}

export const permissionService = new PermissionService() 