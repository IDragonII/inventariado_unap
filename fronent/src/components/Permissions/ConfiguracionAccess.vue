<template>
    <div v-if="allowed">
        <slot/>
    </div>
    <div v-else class="text-center q-pa-lg">
        <q-icon name="lock" size="100px" color="grey-5" />
        <div class="text-5 q-mt-md text-grey-6">Acceso denegado</div>
        <div class="text-body1 q-mt-sm text-grey-5">Pagina deshabilitada</div>
    </div>
</template>
<script setup>
import { configuracionService } from 'src/services/configuracion';
import { onMounted, ref, watch } from 'vue';

const props=defineProps({
    clave: {type: String, required: true}
})
const allowed=ref(false);

async function loadConfig() {
  if (!props.clave) {
    allowed.value = false;
    return;
  }
  try {
    const res = await configuracionService.list({ search: props.clave });
    const cfg = res.data?.[0];
    allowed.value = cfg?.mostrar_botones === true;
  } catch (e) {
    console.error('Error cargando configuración', e);
    allowed.value = false;
  }
}

onMounted(loadConfig);
watch(() => props.clave, loadConfig);

</script>