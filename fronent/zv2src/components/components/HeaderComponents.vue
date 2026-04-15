<template>
    <div class="row q-mb-md">
        <div class="col-12">
            <q-card>
                <q-card-section>
                    <div class="text-h6">{{ title }}</div>
                </q-card-section>
                <q-card-section>
                    <div class="row q-col-gutter-md">
                        <div class="col-12 col-md-4">
                            <q-input v-model="filterLocal" dense outlined clearable label="Buscar ..." @update:model-value="emit('update:filter', $event)">
                                <template v-slot:append>
                                    <q-icon name="search"/>
                                </template>
                            </q-input>
                        </div>
                        <div>
                            <q-btn
                            v-if="isPermissions"
                            color="primary"
                            :icon="loadingCreate ? 'hourglass_empty' : 'add'"
                            :label="loadingCreate ? 'Creando ...': createButtonLabel"
                            :loading="loadingCreate"
                            :disable="loadingCreate"
                            @click="emit('open-dialog')"
                            />
                        </div>
                    </div>
                </q-card-section>
            </q-card>
        </div>
    </div>
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