<template>
    <div v-if="hasAccess">
        <slot/>
    </div>
    <div v-else class="text-center q-pa-lg">
        <q-icon name="lock" size="100px" color="grey-5"/>
        <div class="text-5 q-mt-md text-grey-6">Acceso denegado</div>
        <div class="text-body1 q-mt-sm text-grey-5">
            No tienes permiso para accesder a este sigtio
        </div>
    </div>
</template>
<script setup>
import { computed } from 'vue';

const props = defineProps({
    isAdmin: {type: Boolean, default: false},
    isSupervisor: {type: Boolean, default: false},
    isResponsable: {type: Boolean, default: false},
    isConsulta: {type: Boolean, default: false},
    isSoftware: {type: Boolean, default: false},
    isInstaller: {type: Boolean, default: false},
})

const hasAccess = computed(() => {
    return props.isAdmin || props.isSupervisor || props.isResponsable || props.isConsulta || props.isSoftware || props.isInstaller
})
</script>