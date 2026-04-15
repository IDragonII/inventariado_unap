import axios from 'axios';
import { HttpAdapter } from './HttpAdapter';
import { authService } from '../services/authService';

export class AxiosAdapter extends HttpAdapter
{
    constructor(options)
    {
        super()
        this.axiosInstance = axios.create({
            baseURL: options.baseUrl,
            params: options.params,
            headers: options.headers || {}
        })

        this.axiosInstance.interceptors.request.use(config => {
            const token = options.getToken ? options.getToken(): null
            if(token){
                config.headers.Authorization = `Bearer ${token}`
            }
            return config;
        })

        this.axiosInstance.interceptors.response.use(
            response => {
                return response
            },
            async error => {
                if(error.response) {
                    switch (error.response.status){
                        case 401:
                            console.error('No autorizado - Token inválido o expirado')
                            try {
                                // Intentar refrescar el token
                                await authService.refreshToken()
                                // Reintentar la petición original
                                const config = error.config
                                return this.axiosInstance(config)
                            } catch (refreshError) {
                                // Si falla el refresh, cerrar sesión
                                console.error(refreshError)
                                localStorage.removeItem('token')
                                window.location.href = '/login'
                            }
                            break
                        case 404:
                            console.error('Recurso no encontrado')
                            break
                        case 500:
                            console.error('Error del servidor')
                            break
                    }
                }
                return Promise.reject(error)
            }
        )
    }

    async get(url, config = {}) {
        try {
            const response = await this.axiosInstance.get(url, config)
            if (config && config.responseType === 'blob') {
                return response.data
            }
            return response.data
        } catch (error) {
            console.error('Error en GET request:', error)
            throw error
        }
    }

    async post(url, data = {}, config = {}) {
        try {
            const response = await this.axiosInstance.post(url, data, config)
            return response.data
        } catch (error) {
            console.error('Error en POST request:', error)
            throw error
        }
    }

    async put(url, data = {}, config = {}) {
        try {
            const response = await this.axiosInstance.put(url, data, config)
            return response.data
        } catch (error) {
            console.error('Error en PUT request:', error)
            throw error
        }
    }

    async delete(url, config = {}) {
        try {
            const response = await this.axiosInstance.delete(url, config)
            return response.data
        } catch (error) {
            console.error('Error en DELETE request:', error)
            throw error
        }
    }
}