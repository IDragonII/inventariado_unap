import { httpClient } from "src/boot/axios";

class EdificioService {
    constructor(){
        this.http=httpClient
        this.resource='auth/edificios'
    }

    async getEdificios(params){
        const response=await this.http.get(this.resource, {params})
        return response
    }
}

export const edificioService=new EdificioService()