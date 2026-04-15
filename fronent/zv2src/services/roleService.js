import { httpClient } from '../boot/axios'

class RoleService {
  constructor() {
    this.resource = 'auth/roles'
    this.http = httpClient
  }

  async getRoles(params) {
    return this.http.get(this.resource, { params })
  }

  async createRol(data) {
    return this.http.post(this.resource, data)
  }

  async updateRol(id, data) {
    return this.http.put(`${this.resource}/${id}`, data)
  }

  async deleteRol(id) {
    return this.http.delete(`${this.resource}/${id}`)
  }

  async getPermisos() {
    return this.http.get('auth/permissions')
  }

  async asignarPermisos(rolId, permisos) {
    return this.http.post(`${this.resource}/${rolId}/permissions`, { permisos })
  }
}

export const roleService = new RoleService() 