<template>
    <q-toolbar>
        <q-toolbar-title class="text-bold">
            {{ title }}
        </q-toolbar-title>
    </q-toolbar>
    <q-card-section class="row items-center q-col-gutter-sm">
        <div class="col">
            <q-input v-model="filterLocal" @update:model-value="emit('update:filter', $event)" label="Buscar..." outlined dense>
                <template v-slot:append>
                    <q-icon name="search"/>
                </template>
            </q-input>
        </div>
        <div class="col-12 col-sm-4 col-md-3 col-lg-2">
            <q-btn 
            v-if="isPermissions"
            color="primary"
            :label="loadingCreate ? 'Creando ...': createButtonLabel"
            :icon="loadingCreate ? 'hourglass_empty' : 'add'"
            style="width: 100%;"
            :loading="loadingCreate"
            :disable="loadingCreate"
            @click="emit('open-dialog')"/>
        </div>
    </q-card-section>
</template>
<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
    isPermissions: { type: Boolean, default: false },
    filter: { type: String },
    loadingCreate: Boolean,
    title: String,
    createButtonLabel: { type: String, default: 'Nuevo' }
})

const emit = defineEmits(['update:filter', 'open-dialog'])
const filterLocal = ref(props.filter)
watch (() => props.filter, (val) => {
    if(val != filterLocal.value) {
        filterLocal.value = val
    }
})
</script>