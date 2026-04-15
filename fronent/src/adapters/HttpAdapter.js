export class HttpAdapter {
    // eslint-disable-next-line no-unused-vars
    get(url, options) {
        throw new Error('Método get no implementado')
    }
    // eslint-disable-next-line no-unused-vars
    post(url, body, options) {
        throw new Error(`Método post no implementado para la URL: ${url}`)
    }
    // eslint-disable-next-line no-unused-vars
    put(url, body, options) {
        throw new Error(`Método put no implementado para la URL: ${url}`)
    }
    // eslint-disable-next-line no-unused-vars
    delete(url, body, options) {
        throw new Error(`Método delete no implementado para la URL: ${url}`)
    }
}