<template>
    <q-card-section class="row q-col-gutter-sm items-center">
        <div class="col-12 col-sm-4 col-md-3">
            <q-input v-model="searchLocal" outlined dense placeholder="Buscar ...">
                <template v-slot:append>
                    <q-icon name="search"/>
                </template>
            </q-input>
        </div>
        <div class="col-12 col-sm-4 col-md-3">
            <q-select
            v-model="oficinaLocal"
            :options="oficinaOptions||[]"
            label="Oficina"
            option-label="label"
            option-value="value"
            outlined dense clearable/>
        </div>
        <div class="col-12 col-sm-4 col-md-3">
            <q-select
            v-model="ubicacionLocal"
            :options="ubicacionOptions||[]"
            label="Ubicación"
            option-label="label"
            option-value="value"
            outlined dense clearable/>
        </div>
        <div class="col-12 col-sm-4 col-md-3">
            <q-input v-model="searchUserLocal" outlined dense clearable placeholder="Buscar por DNI de usuario">
                <template v-slot:append>
                    <q-icon name="search" color="primary" />
                </template>
            </q-input>
        </div>
        <div class="col flex justify-end">
            <q-btn 
                v-if="select && select.length > 0 && isUserSearched" 
                @click="handleMovimiento" 
                icon="how_to_reg" 
                color="primary" 
                label="Movimiento" 
                class="q-ma-xs q-px-md" 
                dense 
                rounded
            />
            <q-btn icon="how_to_reg" color="primary" label="Declaración de uso" class="q-ma-xs q-px-md" dense rounded/>
            <q-btn 
                v-if="isUserSearched" 
                icon="inventory_2" 
                color="orange-9" 
                label="PDF Sin Item" 
                class="q-ma-xs q-px-md" 
                dense 
                rounded
                @click="handlePdfSinItem"
            />
        </div>
    </q-card-section>
</template>

<script setup>
import { debounce } from 'lodash'
import { ref, watch } from 'vue'

const props=defineProps({
    search: { type: String, default: '' },
    oficina: { type: [String, Number, Array, Object, null], default: null },
    ubicacion: { type: [String, Number, Array, Object, null], default: null },
    user: { type: String, default: '' },
    select: { type: Array, default: ()=>[]},
    oficinaOptions: {type: Array, default: ()=>[]},
    ubicacionOptions: {type: Array, default: ()=>[]},
})
const searchLocal = ref(props.search)
const oficinaLocal = ref(props.oficina)
const ubicacionLocal = ref(props.ubicacion)
const searchUserLocal = ref(props.user)
const select=ref(props.select)
const isUserSearched = ref(false)
watch(() => props.search, (newVal) => {
  searchLocal.value = newVal
})
watch(() => props.oficina, (newVal) => {
  oficinaLocal.value = newVal
})
watch(() => props.ubicacion, (newVal) => {
  ubicacionLocal.value = newVal
})
watch(() => props.user, (newVal) => {
  searchUserLocal.value = newVal
})
watch(()=> props.select, (newVal) => {
    select.value=newVal
})

const emit=defineEmits(['update:search', 'update:oficina', 'update:ubicacion', 'update:user', 'update:movimiento', 'update:pdfSinItem'])
const emitSearch = debounce((val) => emit('update:search', val), 500)
const emitOficina = debounce((val) => emit('update:oficina', val), 300)
const emitUbicacion = debounce((val) => emit('update:ubicacion', val), 300)
const emitUser = debounce((val) => emit('update:user', val), 500)
const emitMovimiento=debounce(()=>emit('update:movimiento'),500)

watch(searchLocal, (val) => emitSearch(val))
watch(oficinaLocal, (val) => emitOficina(val))
watch(ubicacionLocal, (val) => emitUbicacion(val))
//watch(searchUserLocal, (val) => emitUser(val))

const handleMovimiento=()=>{
    emitMovimiento()
    //console.log('update movimiento')
}

const handlePdfSinItem=()=>{
    emit('update:pdfSinItem')
}
watch(searchUserLocal, (val) => {
    if (!val) isUserSearched.value = false
})
watch(searchUserLocal, (val) => {
  if (val && val.length > 0) {
    isUserSearched.value = true // Marcamos que se está buscando
    emitUser(val)               // Llama a la función con debounce (500ms)
  } else {
    isUserSearched.value = false // Si borra todo, ocultamos el botón
    emitUser(null)               // Limpiamos el filtro en el padre
  }
})
</script>