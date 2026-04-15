<template>
    <q-page>
        <q-card class="q-ma-md">
            <q-toolbar cllass="showdow-2">
                <q-toolbar-title class="text-bold text-center">
                    Reporte de Software
                </q-toolbar-title>
            </q-toolbar>
        </q-card>
        <q-card class="q-ma-md q-mt-lg">
            <q-card-section class="row items-center q-col-gutter-md">
                <div class="col">
                    <q-select
                    v-model="oficinaFilter"
                    label="Seleccinar Oficina"
                    :options="oficinaOptions"
                    outlined dense clearable
                    use-input
                    input-debounce="1500"
                    @filter="oficinasLoading"
                    />
                </div>
                <div>
                    <q-btn icon="file_upload" color="primary" label="Reporte" class="q-ma-xs q-px-md" dense rounded :disable="!oficinaFilter"/>
                    <q-tooltip>Generar reporte</q-tooltip>
                </div>
            </q-card-section>
        </q-card>
        <q-card class="q-ma-md">
            <q-card-section>
                <q-table
                :columns="columns"
                :rows="softwareList"
                row-key="id"
                >
                </q-table>
            </q-card-section>
        </q-card>
    </q-page>
</template>
<script setup>
import { oficinaService } from 'src/services/oficinaService';
import { ref } from 'vue';
const oficinaFilter = ref('');
const oficinaOptions = ref([]);
const softwareList = ref([]);
const columns = [
    { name: 'codigo', label: 'Codigo', field: 'codigo', align: 'left' },
    { name: 'denominacion', label: 'Denominacion', field: 'denominacion', align: 'left' },
    { name: 'cantidad', label: 'Cantidad de Licencias', field: 'cantidad', align: 'left' },
    { nama: 'Oficina', label: 'Oficina', field: 'oficina', align: 'left' },
    { name: 'actions', label: 'Acciones', align: 'center' }
];
const oficinasLoading=async(newOficina, update)=>{
    const oficinas=await oficinaService.getOficinaSearch({search: newOficina});
    update(() => {
        oficinaOptions.value = oficinas.map(oficina => ({
            label: `${oficina.codigo} - ${oficina.denominacion}`,
            value: oficina.id
        }));
    });
}
</script>