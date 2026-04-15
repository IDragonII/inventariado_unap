<template>
  <q-page class="q-mt-md">
    <div class="full-width text-center flex flex-center">
      <q-card v-if="!pasoResumen" style="max-width: 650px; min-width: 200px;">
        <q-banner dense class="bg-primary text-white flex text-center text-bold">
          <q-icon name="check_circle"/> SELECCIONE LOS DATOS PARA INICIAR EL IVENTARIO
        </q-banner>
        <q-card-section>
          <div class="q-gutter-sm">
            <q-select
            v-model="oficinaSeleccionada"
            :options="oficinaAsignadas"
            label="SELECCIONE EL CENTRO DE COSTO"
            option-label="label" option-value="value"
            :disable="oficinaAsignadas.length===0"
            outlined dense required clearable
            />
            <q-select
            v-model="areaSeleccinada"
            :options="areasAsignadas"
            label="SELECCIONE LA UBICACIÓN"
            option-label="label" option-value="value"
            :disable="areasAsignadasFilter.length===0||!oficinaSeleccionada"
            dense outlined required use-input clearable
            input-debounce="0"
            @filter="filterAreas"
            />
            <q-input
            v-model="dni"
            label="INGRESA EL N° DE DNI DE LA PERSONA ENCARGADA"
            :disable="!areaSeleccinada || buscandoDni"
            dense outlined clearable required mask="########"
            :rules="[val => !val || val.length === 8 || 'El DNI debe tener 8 dígitos']"
            @keyup.enter="buscarResponsable">
              <template v-slot:after>
                <q-btn class="bg-primary" icon="search" color="primary" @click="buscarResponsable" :loading="buscandoDni" :disable="dni.length !== 8 || buscandoDni"/>
                <q-btn class="bg-primary" icon="add" color="primary" @click="buscarResponsable('two')" :loading="buscandoDni" :disable="dni.length !== 8 || buscandoDni || !responsable"/>
              </template>
            </q-input>
          </div>
        </q-card-section>
        <q-card-section class="text-left" style="margin-top: -38px;">
          <q-card v-if="responsable" bordered class="q-pa-sm text-left">
            <div class="text-subtitle2">Responsable:</div>
            <div><b>Nombre:</b> {{ responsable.nombre }}</div>
            <div><b>DNI:</b> {{ responsable.dni }}</div>
          </q-card>
          <q-card v-if="responsabletwo" bordered class="q-pa-sm text-left">
            <div class="text-subtitle2">Responsable dos:</div>
            <div><b>Nombre:</b> {{ responsabletwo.nombre }}</div>
            <div><b>DNI:</b> {{ responsabletwo.dni }}</div>
          </q-card>
          <q-btn label="Siguiente" color="primary" class="q-mt-md full-width" :disable="!responsable" @click="pasoResumen=true"/>
        </q-card-section>
      </q-card>
      <q-card v-else class="q-ma-md">
        <q-banner class="bg-primary" dense>
          <div class="text-white">INVENTARIO DE SOFTWARE</div>
        </q-banner>
        <q-card-section>
          <q-tabs v-model="tab" class="text-primary q-md-md" align="justify" dense>
            <q-tab name="inventariar" label="INVENTARIAR"/>
            <q-tab name="registrados" label="REGISTRADOS"/>
          </q-tabs>
          <q-tab-panels v-model="tab" animated>
            <q-tab-panel name="inventariar">
              <div>
                <q-input
                  v-model="codigoActivo"
                  label="INGRESE EL CODIGO DEL ACTIVO"
                  mask="############"
                  maxlength="12"
                  class="col"
                  dense outlined clearable
                  required
                  @keyup.enter="buscarActivo('edit')"
                >
                    <template v-slot:after>
                      <q-btn
                        label="Buscar"
                        color="primary"
                        @click="buscarActivo('edit')"
                        :disable="![6,8,10,12].includes(codigoActivo.length)"
                      />
                    </template>
                </q-input>
              </div>
              <q-card class="q-mt-sm text-left">
                <q-card-section v-if="denominacionActivo">
                  <div>CODIGO: {{ codigoActivo }}</div>
                  <div>DENOMINACIÓN: {{ denominacionActivo }}</div>
                </q-card-section>
                <q-card-section>
                  <q-file
                    v-if="denominacionActivo"
                    v-model="jsonFile"
                    label="Subir archivo JSON"
                    accept=".json"
                    filled
                    use-chips
                  />
                  <q-btn v-if="denominacionActivo" label="Guardar" @click="onJsonUpload"/>
                  <DynamicForm
                    v-if="!denominacionActivo"
                    :fields="formFields"
                    v-model="formData"
                    :mode="modal.mode"
                    :loading="loadingForm"
                    @submit="handleFormSubmit"
                    @cancel="modal.show = false"
                  />
                </q-card-section>
              </q-card>
            </q-tab-panel>
            <q-tab-panel name="registrados">
              <q-card>
                <q-card-section>
                  <q-btn color="primary" @click="dowloadReporte" :loading="loadingdownload"><q-icon name="print"/></q-btn>
                </q-card-section>
              </q-card>
            </q-tab-panel>
          </q-tab-panels>
        </q-card-section>
      </q-card>
    </div>
  </q-page>
</template>
<script setup>
import { onMounted, ref, watch } from 'vue';
import { areaService } from 'src/services/areaService';
import { authService } from 'src/services/authService';
import { useQuasar } from 'quasar';
import { activoService } from 'src/services/activoService';
import DynamicForm from 'src/components/DynamicForm.vue';
import { useSoftwareForm } from './formFields';
import { softwareService } from 'src/services/softwareService';
const $q=useQuasar()
const oficinaSeleccionada=ref(null)
const oficinaAsignadas=ref([])
const areaSeleccinada=ref(null)
const areasAsignadas=ref([])
const areasAsignadasFilter=ref([])
const dni=ref('')
const buscandoDni=ref(false)
const responsable=ref(null)
const responsabletwo=ref(null)
const pasoResumen=ref(false)
const tab=ref('inventariar')
const codigoActivo=ref('')
const denominacionActivo=ref(null)
const loadingBuscarActivo=ref(false)
const loadingForm=ref(false)
const modal = ref({
  show: false,
  title: 'Crear',
  mode: 'create'
});
const jsonFile = ref(null)
///////////////////////////////////////////////////////////////////////////////////////////////
const filterAreas = (val, update) => {
  update(()=>{
    const text=val.toLowerCase()
    if(text===''){
      areasAsignadas.value=areasAsignadasFilter.value
    } else {
      areasAsignadas.value=areasAsignadasFilter.value.filter(ar=>
        ar.label.toLowerCase().includes(text)
      )
    }
  })
};
const buscarResponsable=async(two)=>{
  if(!dni.value) return
  buscandoDni.value=true
  try{
    const responsableData=await authService.getUsuarios(dni.value)
    if(responsableData[0]?.name){
      if(two==='two' && responsable.value.dni!=dni.value){
        responsabletwo.value={id: responsableData[0].id, nombre: responsableData[0].name, dni: dni.value}
      }
      else{
        responsable.value={id: responsableData[0].id, nombre: responsableData[0].name, dni: dni.value}
      }
    }
    else{
      $q.notify({ color: 'negative', message: 'Responsable no encontrado', icon: 'error', position: 'top' })
      if(two==='two')
        responsabletwo.value=null
      else{
        responsable.value=null
        responsabletwo.value=null
      }
    }
  } catch (e) {
    console.error(e)
    $q.notify({ color: 'negative', message: 'Responsable no encontrado', icon: 'error', position: 'top' })
    responsable.value=null
  } finally {
    buscandoDni.value=false
  }
}
const buscarActivo=async()=>{
  loadingBuscarActivo.value=true
  try {
    const res=await activoService.getActivos({codigo: codigoActivo.value})
    if (!res.data || res.data.length === 0) {
      $q.notify({ color: 'warning', message: 'Activo no encontrado.', position: 'top', icon: 'warning' })
      denominacionActivo.value = null
      return
    }
    formData.value = { tipo: 'licencia_terceros' };
    denominacionActivo.value=res.data[0]?.denominacion||null
  } catch (e) {
    console.log(e)
    $q.notify({ color: 'negative', message: 'Activo no encontrado', icon: 'error', position: 'top' })
  } finally{
    loadingBuscarActivo.value=false
  }
}
const formData = ref({});
const { formFields } = useSoftwareForm(formData);
const onJsonUpload = async () => {
  loadingForm.value = true

  try {
    const user = await authService.getCurrentUser()
    const content = await jsonFile.value.text()
    const data = JSON.parse(content)

    if (!Array.isArray(data.aplicaciones)) {
      console.error("El JSON no contiene un array 'aplicaciones'")
      loadingForm.value = false
      return
    }
    if (!codigoActivo.value) {
      $q.notify({color: 'negative',message: 'Seleccione el activo',icon: 'report_problem',position: 'top'})
      loadingForm.value = false
      return
    }
    const payloadBase = {
      codigoA: codigoActivo.value,
      denominacion: denominacionActivo.value,
      area_id: areaSeleccinada.value.value,
      responsable_id: responsable.value.id,
      user_id_two: responsabletwo.value?.id || null,
      inventariador_id: user.id,
      tipo: 'licencia_terceros',
      estado: 'activo'
    }
    for (const app of data.aplicaciones) {
      const payload = {
        ...payloadBase,
        ...app,
        clave_licencia: app.mac_address||null,
        fecha_compra: parseFechaDDMMYYYY(app.fecha_instalacion),
        notas: app.sistema
      }
      await softwareService.createSoftware(payload)
      $q.notify({ color: 'positive', message: `Software "${app.nombre}" creado exitosamente`, icon: 'check', position: 'top'})
    }
  } catch (error) {
    console.error("Error procesando JSON:", error)
  } finally{
    loadingForm.value = false
  }
}
function parseFechaDDMMYYYY(fechaString) {
  if (!fechaString) return null; // por si viene vacío

  const [dia, mes, anio] = fechaString.split('/');

  // Validar que existan
  if (!dia || !mes || !anio) return null;

  return new Date(Number(anio), Number(mes) - 1, Number(dia));
}

const handleFormSubmit=async(data)=>{
  loadingForm.value=true
  try {
    const payload = { ...data }
    for (const key in payload) {
      if (payload[key] && typeof payload[key] === 'object' && payload[key].value !== undefined) {
        payload[key] = payload[key].value;
      }
    }
    if(payload.tipo==='licencia_terceros'){
      payload.codigoA=codigoActivo.value
      payload.denominacion=denominacionActivo.value
      if(codigoActivo.value===null||codigoActivo.value===''){
        $q.notify({ color: 'negative', message: 'Seleccione el activo', icon: 'report_problem', position: 'top' });
        return
      }
    }
    const user=await authService.getCurrentUser()
    payload.area_id=areaSeleccinada.value.value
    payload.responsable_id=responsable.value.id
    payload.user_id_two=responsabletwo.value?.id||null
    payload.inventariador_id=user.id
    await softwareService.createSoftware(payload);
    $q.notify({ color: 'positive', message: 'Activo de software creado exitosamente', icon: 'check' });
    formData.value={}
  } catch (error) {
    const message = error.response?.data?.message || 'Error al guardar el activo de software';
    $q.notify({ color: 'negative', message, icon: 'report_problem' });
  } finally {
    loadingForm.value=false
  }
}
const loadingDownloadReporte=ref(false)
loadingDownloadReporte.value=false
const dowloadReporte=async()=>{
  loadingDownloadReporte.value=true
  const user=await authService.getCurrentUser()
  console.log(areaSeleccinada.value.value, responsable.value.id, responsabletwo.value?.id, user.id)
  const response=await activoService.reportesoftware({responsable_id: responsable.value.id, area_id: areaSeleccinada.value.value, inventariador_id: user.id, responsable_software_id: responsable.value.id})
  const blob = response;
  const url = window.URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.setAttribute('download', `reporte.pdf`);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  window.URL.revokeObjectURL(url);
  loadingDownloadReporte.value=false
}
///////////////////////////////////////////////////////////////////////////////////////////////
onMounted(async()=>{
  const user=await authService.getCurrentUser()
  if(user.oficinas.length>0){
    oficinaAsignadas.value=user.oficinas.map(o=>({
      value: o.id,
      label: `${o.codigo} - ${o.denominacion}${o.escuela ? ' - ' + o.escuela : ''}`
    }))
  }
})
watch(oficinaSeleccionada, async()=>{
  areaSeleccinada.value=null
  const areas=await areaService.getAreas({oficina: oficinaSeleccionada.value})
  if(areas.data.length>0){
    areasAsignadas.value=areas.data.map(a=>({
      value: a.id,
      label: `${a.codigo} - ${a.aula}`
    }))
    areasAsignadasFilter.value=areasAsignadas.value
  }
})
watch(tab, async(val)=>{
  if(val==='registrados') tab.value=val
})
</script>