<template>
    <q-dialog v-model="dialogShow">
        <q-card>
            <q-card-section>
                <q-avatar icon="warning" color="negative" text-color="white"/>
                <span class="q-ml-sm">¿Está seguro de eliminar?</span>
            </q-card-section>
            <q-card-actions>
                <q-btn flat label="Cancelar" color="primary" v-close-popup/>
                <q-btn flat label="Eliminar" color="negative" @click="confirmDelete"/>
            </q-card-actions>
        </q-card>
    </q-dialog>
</template>
<script setup>
import { computed } from 'vue'

const props = defineProps({
    show: { type: Boolean, default: false }
})

const emit = defineEmits(['update:show', 'confirm'])

const dialogShow = computed({
    get: () => props.show,
    set: (value) => emit('update:show', value)
})

const confirmDelete = ()=>{
    emit('confirm')
    emit('update:show', false)
}
</script>