import { httpClient } from '../boot/axios'
import { useAuthStore } from 'src/stores/auth-store'
class UserService {
  constructor() {
    this.resource = 'auth/users'
    this.http = httpClient
  }

  async getUsuarios(params) {
    let auth=useAuthStore()
    if(auth.isAdmin || auth.isConsulta || auth.isResponsable || auth.isConsulta)
      return this.http.get(this.resource, { params })
    else if(auth.isSoftware)
      return this.http.get('auth/usuarios/software', {params})
  }

  async getUsuario(id) {
    return this.http.get(`${this.resource}/${id}`)
  }

  async createUsuario(data) {
    let auth=useAuthStore()
    if(auth.isAdmin)
      return this.http.post(this.resource, data)
    else 
      return this.http.post('auth/usuarios/software', data)
  }
  async updateUsuario(id, data) {
    let auth=useAuthStore()
    if(auth.isAdmin || auth.isConsulta || auth.isResponsable || auth.isConsulta)
      return this.http.put(`${this.resource}/${id}`, data)
    else 
      return this.http.put(`auth/usuarios/software/${id}`, data)
  }

  async deleteUsuario(id) {
    let auth=useAuthStore()
    if(auth.isAdmin)
      return this.http.delete(`${this.resource}/${id}`)
    else 
      return this.http.delete(`auth/usuarios/software/${id}`)
  }

  async getRoles() {
    let auth=useAuthStore()
    const response = {
      data: [
        ...((auth.isAdmin)?[
        {
          "id": 1, "name": "Adminstrador",
        },
        {
          "id": 2, "name": "Supervisor",
        }
        ,
        {
          "id": 3, "name": "Responsable de oficina",
        }
        ,
        {
          "id": 4, "name": "Usuario",
        },
        {
          "id": 5, "name": "Encargado de realizar inventariado",
        }
        ]: [])
        ,
        ...((auth.isSoftware)?[
        {
          "id": 6, "name": "Administrador de software",
        },
        {
          "id": 7, "name": "Instalar software",
        }
        ]: [])
      ]
    }
    return response
  }

  async asignarRoles(userId, roles) {
    return this.http.post(`${this.resource}/${userId}/roles`, { roles })
  }

  async createUsuarioSend (usuario) {
        try {
            const response = await this.http.post('auth/register', usuario)
            return response
        } catch (error) {
            console.error('Error al crear usuario: ', error)
            throw error
        }
    }
}

export const userService = new UserService() 