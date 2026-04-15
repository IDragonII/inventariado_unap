import { httpClient } from '../boot/axios'

export class BaseService {
    constructor(resource) {
        this.resource = resource
        this.http = httpClient
    }

    async getAll(params = {}) {
        return this.http.get(this.resource, { params })
    }

    async getById(id) {
        return this.http.get(`${this.resource}/${id}`)
    }

    async create(data) {
        return this.http.post(this.resource, data)
    }

    async update(id, data) {
        return this.http.put(`${this.resource}/${id}`, data)
    }

    async delete(id) {
        return this.http.delete(`${this.resource}/${id}`)
    }
} 