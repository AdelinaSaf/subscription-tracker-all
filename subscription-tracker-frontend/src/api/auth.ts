import type {RegisterRequest} from "../common/types/auth/register/RegisterRequest.ts";
import {api} from "./index.ts";
import type {RegisterResponse} from "../common/types/auth/register/RegisterResponse.ts";
import type {LoginRequest} from "../common/types/auth/login/LoginRequest.ts";
import type {LoginResponse} from "../common/types/auth/login/LoginResponse.ts";

export default class AuthApi{
    static async register(payload: RegisterRequest){
        return api.post<RegisterResponse>('register', payload)
    }

    static async login(payload: LoginRequest) {
        return api.post<LoginResponse>('/login', payload);
    }

    static async me() {
        return api.get('/me');
    }
}