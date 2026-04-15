<template>
    <q-form @submit="login" class="q-gutter-md">
        <q-input 
            rounded 
            bordered 
            :standout="$q.dark.isActive ? 'bg-grey text-white' : 'bg-grey text-white'"
            v-model="form.email" 
            label="Email" 
            type="email" 
            :error-message="errors.email[0]"
            :error="errors.email[0] != null"
            :rules="[val => !!val || 'El email es requerido', isValidEmail]"
        >
            <template v-slot:prepend>
                <q-icon name="email" />
            </template>
        </q-input>

        <q-input 
            rounded 
            :standout="$q.dark.isActive ? 'bg-grey text-white' : 'bg-grey text-white'"
            v-model="form.password" 
            label="Contraseña" 
            :type="isPassword ? 'password' : 'text'"
            :error-message="errors.password[0]" 
            :error="errors.password[0] != null"
            :rules="[val => !!val || 'La contraseña es requerida', val => val.length >= 6 || 'La contraseña debe tener al menos 6 caracteres']"
        >
            <template v-slot:prepend>
                <q-icon name="lock"/>
            </template>
            <template v-slot:append>
                <q-icon 
                    :name="isPassword ? 'visibility_off' : 'visibility'" 
                    class="cursor-pointer"
                    @click="isPassword = !isPassword" 
                />
            </template>
        </q-input>

        <div class="flex justify-center">
            <q-btn 
                type="submit" 
                rounded 
                push 
                label="Iniciar Sesión" 
                :loading="loading"
                no-caps 
                style="min-width: 200px; font-size: medium;"
                color="primary" 
                class="text-white q-py-md" 
            />
        </div>
        <q-banner 
            v-if="errorMessage" 
            rounded 
            style="border-radius: 20px;"
            :class="$q.dark.isActive ? 'bg-red-4 text-red-10' : 'bg-red-1 text-red-10'"
        >
            {{ errorMessage }}
        </q-banner>
    </q-form>
</template>

<script setup>
import { ref } from "vue";
import { useRouter } from 'vue-router';
import { useQuasar } from 'quasar';
import { authService } from '../../services/authService';
import { useAuthStore } from '../../stores/auth-store'
const $q = useQuasar();
const router = useRouter();
const authStore = useAuthStore();

const loading = ref(false);
const errorMessage = ref(null);
const isPassword = ref(true);

const form = ref({
    email: "",
    password: "",
});

const errors = ref({
    email: [null],
    password: [null]
});

const isValidEmail = (val) => {
    const emailPattern = /^(?=[a-zA-Z0-9@._%+-]{6,254}$)[a-zA-Z0-9._%+-]{1,64}@(?:[a-zA-Z0-9-]{1,63}\.){1,8}[a-zA-Z]{2,63}$/;
    return emailPattern.test(val) || 'Email inválido';
};

const login = async () => {
    try {
        loading.value = true;
        errorMessage.value = null;
        errors.value = {
            email: [null],
            password: [null]
        };

        const response = await authService.login({
            email: form.value.email,
            password: form.value.password
        });

        if (response.token) {
            authStore.setAuth({
                user: response.user,
                role: response.role,
                token: response.token
            })
            $q.notify({
                message: 'Bienvenido al sistema',
                color: 'positive',
                position: 'top',
                timeout: 2000
            });
            router.push('/');
        } else {
            errorMessage.value = 'Error en la respuesta del servidor';
            $q.notify({
                message: 'Error en la respuesta del servidor',
                color: 'negative',
                position: 'top',
                timeout: 2000
            });
        }
    } catch (error) {
        if (error.response?.status === 400) {
            errorMessage.value = 'Credenciales inválidas';
            $q.notify({
                message: 'Credenciales inválidas',
                color: 'negative',
                position: 'top',
                timeout: 2000
            });
        } else if (error.response?.data?.errors) {
            const serverErrors = error.response.data.errors;
            errors.value = {
                email: serverErrors.email || [null],
                password: serverErrors.password || [null]
            };
            $q.notify({
                message: 'Por favor, corrija los errores en el formulario',
                color: 'warning',
                position: 'top',
                timeout: 2000
            });
        } else {
            errorMessage.value = error.message || 'Error al iniciar sesión. Por favor, intente nuevamente.';
            $q.notify({
                message: error.message || 'Error al iniciar sesión. Por favor, intente nuevamente.',
                color: 'negative',
                position: 'top',
                timeout: 2000
            });
        }
    } finally {
        loading.value = false;
    }
};
</script>