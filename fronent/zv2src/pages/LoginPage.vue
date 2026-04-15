<template>
    <div class="row justify-between">
        <div class="col-12 col-md-8 gt-sm">
            <div style="height: 100vh; position: relative;" class="bg-primary">
                <div class="column justify-center" style="height: 100%;">
                    <div class="col-8">
                        <div class="row justify-center items-center q-pa-sm">
                            <q-img src="~assets/imgs/Logo_UNAP.png" style="max-width: 300px;"></q-img>
                        </div>
                        <div class="row justify-center items-center q-mt-md">
                            <span class="text-h5 text-weight-bold text-center text-white" style="letter-spacing: 1px;">
                                UNIVERSIDAD NACIONAL DEL ALTIPLANO PUNO
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="column" style="height: 100vh;">
                <div class="col-2">
                    <div class="q-px-md q-pt-md row justify-end">
                        <SwitchDark :black="true"></SwitchDark>
                    </div>
                    <div style="height: 100%;" class="row justify-center items-start q-py-sm">
                        <div class="col-12 row items-center justify-center">
                            <img src="~assets/imgs/logooficina.png" style="width: 150px;">
                        </div>
                        <div class="row justify-center items-center">
                            <span class="text-h6 text-weight-bold">
                                Sistema de Gestion de Inventariado
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-8">
                    <div style="height: 100%;" class="row justify-center items-center">
                        <div style="max-width: 100%; width: 350px;">
                            <p class="text-center text-weight-bold q-mb-x" style="font-size: 2rem;">Iniciar Sesión
                            </p>
                            <div class="q-px-md">
                                <FormLogin></FormLogin>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import FormLogin from '../components/Login/FormLogin.vue';
import SwitchDark from '../components/Login/SwitchDark.vue';
</script>