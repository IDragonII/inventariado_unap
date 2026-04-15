import { httpClient } from "src/boot/axios"

export class ActivoUseCases {

    async obtenerTodos(filtros={}) {
        try {
            const response = await httpClient.get('/activo', { params: filtros})
            return response.data
        } catch (error) {
            console.error('Error al obtener activos: ', error)
            throw new Error('No se pudieron obtener los activos')
        }
    }

    async crearActivo(datosActivo){
        try {
            if (!datosActivo.nombre || !datosActivo.categoria_id) {
                throw new Error('Datos incompletos')
            }
            const response = await httpClient.post('/activo', datosActivo)
            return response.data
        } catch (error) {
            console.error('Error al crear activo: ', error)
            throw new Error('No se pudo crear el activo')
        }
    }

    async actualizaActivo(cambios, id) {
        try{
            if (!cambios.nombre || !cambios.categoria_id) {
                throw new Error('Datos incompletos')
            }
            const response = await httpClient.put(`/activo/${id}`, cambios)
            return response.data
        }
        catch (error) {
            console.error('Error al actualizar activo: ', error)
            throw new Error('No se pudo actualizar el activo')
        }
    }
}