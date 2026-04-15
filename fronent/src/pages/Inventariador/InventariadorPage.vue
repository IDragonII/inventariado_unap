<template>
  <q-page class=" q-mt-md">
    <ConfiguracionAccess clave="Permitir inventariado">
    <div class="full-width text-center flex flex-center">
      <q-card v-if="!pasoResumen" class="" style="max-width: 600px; min-width: 200px;">
        <q-banner dense class="bg-primary text-white flex text-center">
          <q-icon name="check_circle"/> Seleccione los datos para iniciar el inventario
        </q-banner>
        <q-tabs v-model="tabheader" class="text-primary q-md-md" align="justify" dense>
          <q-tab name="first" label="Inventariar"/>
          <q-tab name="second" label="Falta Imprimir"/>
        </q-tabs>
        <q-tab-panels v-model="tabheader" animated class="q-mt-md">
          <q-tab-panel name="first">
        <q-card-section>
          <div class="q-gutter-sm">
            <q-select
              v-model="oficinaSeleccionada"
              :options="oficinasAsignadas"
              label="Seleccine el centro de costo"
              option-label="label" option-value="value"
              :disable="oficinasAsignadas.length===0"
              outlined dense required
            />
            <q-select
              v-model="areaSeleccionada"
              :options="areas"
              label="Seleccione el área"
              option-label="label" option-value="id"
              :disable="(!oficinaSeleccionada)||(areas.length===0)"
              outlined dense required
            />
            <q-select
              v-model="edificioSeleccionado"
              :options="edificios"
              label="Seleccione el edificio"
              option-label="label" option-value="id"
              outlined dense required
              use-input
              fill-input
              input-debounce="0"
              @filter="filtrarEdificios"
            />
            <q-input
              v-model="dni"
              label="DNI de la persona encargada"
              mask="########"
              required
              class="col"
              :disable="!areaSeleccionada || buscandoDni || !edificioSeleccionado"
              :rules="[val => !val || val.length === 8 || 'El DNI debe tener 8 dígitos']"
              dense
              outlined
              maxlength="8"
              inputmode="numeric"
              @keyup.enter="buscarResponsable"
            >
              <template v-slot:append>
                  <q-btn class="bg-primary" icon="search" color="primary" @click="buscarResponsable" :loading="buscandoDni" :disable="dni.length !== 8 || buscandoDni" style="border: #045788 solid 3px; margin-right: -11px;"/>
                  <q-btn class="bg-primary q-ml-md" icon="add" color="primary" @click="buscarResponsable('two')" :loading="buscandoDni" :disable="dni.length !== 8 || buscandoDni || !responsable" style="border: #045788 solid 3px; margin-right: -11px;"/>
              </template>
            </q-input>
          </div>
        </q-card-section>
        <q-card-section class="text-left" style="margin-top: -38px;">
          <q-card v-if="responsable" bordered class="q-pa-sm text-left">
            <div class="text-subtitle2">Responsable:</div>
            <div><b>Nombre:</b> {{ responsable.nombre }}</div>
            <div><b>DNI:</b> {{ responsable.dni }}</div>
            <q-input
            v-model="telefono"
            label="N° Telefono de la persona encargada"
            mask="#########"
            required
            dense
            outlined
            class="q-mt-sm"
            inputmode="numeric"/>
          </q-card>
          <q-card v-if="responsabletwo" bordered class="q-pa-sm text-left">
            <div class="text-subtitle2">Responsable dos:</div>
            <div><b>Nombre:</b> {{ responsabletwo.nombre }}</div>
            <div><b>DNI:</b> {{ responsabletwo.dni }}</div>
            <q-input
            v-model="telefonotwo"
            label="N° Telefono de la persona encargada"
            mask="#########"
            required
            dense
            outlined
            class="q-mt-sm"
            inputmode="numeric"/>
          </q-card>
          <q-btn label="Siguiente" color="primary" class="q-mt-md full-width" :disable="!(telefono) || telefono.length<=8" @click="pasoResumen=true"/>
        </q-card-section>
        </q-tab-panel>
          <q-tab-panel name="second">
            <q-table
              :rows="faltantesReporte"
              :columns="faltantesColumns"
              :pagination="pagination"
              row-key="id"
              @update:pagination="pagination"
              @request="onRequest"
              :rows-per-page-options="[5,10,20]"
            >
              <template v-slot:body-cell-actions="props">
                <q-td :props="props">
                  <q-btn>
                    <q-icon @click="registrarFaltantes(props.row)" name="print"/>
                  </q-btn>
                </q-td>
              </template>
            </q-table>
          </q-tab-panel>
        </q-tab-panels>
      </q-card>
      <q-card v-else class="" style="max-width: 600px; min-width: 200px;">
        <q-banner dense class="bg-primary">
          <q-item dense>
            <q-item-section avatar dense>
              <q-btn dense class="bg-white text-black" @click="createActivo('nuevo')">
                <q-icon dense name="add" color="primary"/><span style="font-size:xx-small;">sin/codigo</span>
              </q-btn>
            </q-item-section>
            <q-item-section dense>
              <div class="text-white">GESTION DE ACTIVOS</div>
            </q-item-section>
            <q-item-section avatar dense>
              <q-btn dense class="bg-white text-black" @click="createActivo('antiguo')">
                <q-icon dense name="add" color="primary"/><span style="font-size:xx-small;">codigo anteriores</span>
              </q-btn>
            </q-item-section>
          </q-item>
        </q-banner>
        <q-card-section>
          <q-tabs v-model="tab" class="text-primary q-md-md" align="justify" dense>
            <q-tab name="inventariar" label="Inventariar bienes"/>
            <q-tab name="faltantes" label="Impresiones"/>
            <q-tab name="historial" label="Historial"/>
            <q-tab name="faltaregistrar" label="Faltantes"/>
          </q-tabs>
          <q-tab-panels v-model="tab" animated class="q-mt-md">
            <q-tab-panel name="inventariar">
              <div>
                <q-input
                  v-model="codigoActivo"
                  label="Código del activo"
                  mask="############"
                  maxlength="12"
                  class="col"
                  dense outlined
                  required
                  @keyup.enter="buscarActivo('edit')"
                >
                <!-- :rules="[val => !val || [10,12].includes(val.length) || 'El código debe tener 10 o 12 dígitos']" -->
                    <template v-slot:before>
                      <q-btn
                      icon="qr_code_2"
                      color="primary"
                      @click="abrirEscanerCodigo"
                      />
                    </template>
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
            </q-tab-panel>
            <q-tab-panel name="faltantes">
              <q-item>
                <q-item-section>
                  <div class="text-h6 q-mb-md">Bienes registrados</div>
                </q-item-section>
                <q-item-section avatar>
                  <q-btn color="primary" @click="dowloadReporte" :loading="loadingdownload"><q-icon name="print"/></q-btn>
                </q-item-section>
              </q-item>
                <q-table
                :rows="bienesFaltantes"
                :columns="bienesColumns"
                row-key="id"
                flat
                bordered
                :loading="loadingFaltantes"
                wrap-cells
              >
              <template v-slot:body-cell-actions="props">
                <q-td :props="props">
                  <q-btn v-if="mostrardelete(props.row)" @click="deleteActivo(props.row)">
                    <q-icon style="font-size: 18px" color="red" name="delete"/>
                  </q-btn>
                  <q-btn v-else>
                    <q-icon @click="habilitarActivo(props.row)" name="edit"/>
                  </q-btn>
                </q-td>
              </template>
              </q-table>
           </q-tab-panel>
           <q-tab-panel name="historial">
              <q-table
              :rows="historial"
              :columns="historialColumns"
              row-key="id"
              flat
              bordered
              wrap-cells
              >
              <template v-slot:body-cell-actions="props">
                <q-td :props="props">
                  <q-btn @click="dowloadHistorial(props.row)">
                    <q-icon style="font-size: 18px" name="download"/>
                  </q-btn>
                </q-td>
              </template>
              </q-table>
           </q-tab-panel>
           <q-tab-panel name="faltaregistrar">
            <q-item>
              <q-item-section avatar>
                <q-btn color="primary" @click="dowloadFaltantes" :loading="loadingFaltantes"><q-icon name="print"/>
                  Reporte de faltantes de toda la oficina
                </q-btn>
              </q-item-section>
            </q-item>
            <q-table
              :rows="faltaresRows"
              :columns="faltantes"
              :pagination="pagination"
              row-key="id"
              @update:pagination="pagination"
              @request="onRequest"
              :rows-per-page-options="[5,10,20]"
            />
           </q-tab-panel>
          </q-tab-panels>
        </q-card-section>
        <q-dialog v-model="mostrarEscaner" persistent>
          <q-card style="width: 350px; max-width: 90vw;">
            <q-card-section>
              <div class="text-h6">Escanear código</div>
            </q-card-section>
            <q-card-section>
              <video ref="video" style="width: 100%; border-radius: 8px;" autoplay muted></video>
            </q-card-section>
            <q-card-actions align="right">
              <q-btn flat label="Cancelar" color="negative" @click="cerrarEscaner" />
            </q-card-actions>
          </q-card>
        </q-dialog>
        <DialogModal v-model:show="openModal.show" :mode="openModal.tipo" :title="openModal.title">
          <div class="notice-section" v-if="openModal.tipo==='create'">
            <p v-if="tipo==='nuevo'" class="text-bold">⚠️ Aplicable solo para bienes nuevos</p>                
            <div v-else class="codes-grid">
              <p class="text-bold">Los siguientes códigos se trabajan por cantidad:</p>
              <div class="code-group">
                <p class="text-bold">Códigos Alfanuméricos</p>
                <div class="code-list">
                  <span class="code-item">B146-E0-0631 - </span>
                  <span class="code-item">C02-LVME-011 - </span>
                  <span class="code-item">C11-EO-109 - </span>
                  <span class="code-item">C11-Q-ME-009</span>
                </div>
              </div>
              
              <div class="code-group">
                <p class="text-bold">Códigos Numéricos</p>
                <div class="code-list">
                  <span class="code-item">070303004933 - </span>
                  <span class="code-item">070301001243 - </span>
                  <span class="code-item">610.593.002 - </span>
                  <span class="code-item">610.587.002 - </span>
                  <span class="code-item">610.830.030 - </span>
                  <span class="code-item">610.628.001 - </span>
                  <span class="code-item">334.004.008 - </span>
                  <span class="code-item">336.003.631 - </span>
                  <span class="code-item">6311.04 - </span>
                  <span class="code-item">11844 - </span>
                  <span class="code-item">26.10.40108.15 - </span>
                  <span class="code-item">620.840.002 - </span>
                  <span class="code-item">02.01.610.833.016 - </span>
                  <span class="code-item">91050068111</span>
                </div>
              </div>
            </div>
          </div>
          <DynamicForm
          v-model="formData"
          :fields="modernFormFields"
          :readonly="openModal.tipo==='view'"
          :mode="openModal.tipo"
          :loading="openModal.loading"
          @cancel="handleCancel"
          @submit="handleSubmit"
          />
        </DialogModal>
      </q-card>
    </div>
    </ConfiguracionAccess>
  </q-page>
</template>
<script setup>
import { onMounted, onBeforeUnmount, ref, watch } from 'vue'
import { BrowserMultiFormatReader } from '@zxing/browser'
import { authService } from 'src/services/authService'
import { edificioService } from 'src/services/edificioService'
import { areaService } from 'src/services/areaService'
import { categoriaService } from 'src/services/categoriaService'
import { useQuasar } from 'quasar'
import ExcelJS from 'exceljs'
import ConfiguracionAccess from 'src/components/Permissions/ConfiguracionAccess.vue'
import DialogModal from 'src/components/DialogModal.vue'
import DynamicForm from 'src/components/DynamicForm.vue'
import { activoService } from 'src/services/activoService'
const $q = useQuasar()
//variables
const oficinaSeleccionada = ref(null)
const areaSeleccionada = ref(null)
///////////////////////////////////////////////////////////////////////////////////////////////
const oficinasAsignadas = ref([])
const edificios = ref([])
const bienesFaltantes = ref([])
const areas = ref([])
const catalogsoFormateadas=ref([])
const formData=ref([])
const faltaresRows=ref([])
///////////////////////////////////////////////////////////////////////////////////////////////
const responsable = ref(null)
const responsabletwo = ref(null)
const dni = ref('')
const telefono = ref('')
const telefonotwo = ref('')
const codigoActivo = ref('')
const edificioSeleccionado = ref(null)
const pasoResumen = ref(false)
const buscandoDni = ref(false)
const tab = ref('inventariar')
const tabheader = ref('first')
const loading = ref(false)
const loadingFaltantes=ref(false)
const loadingdownload=ref(false)
const activoEncontrado=ref(null)
const tipo=ref('create')
// Escáner ZXing
const pagination=ref({
  page: 1,
  rowsPerPage: 10,
  rowsNumber: 0
})
//
const mostrarEscaner = ref(false)
const video = ref(null)
let codeReader = null
function abrirEscanerCodigo(){
  mostrarEscaner.value = true
  setTimeout(() => iniciarEscaner(), 200)
}
function iniciarEscaner(){
  codeReader = new BrowserMultiFormatReader()
  codeReader
    .decodeOnceFromVideoDevice(undefined, video.value)
    .then((result) => {
      codigoActivo.value = result.text
      cerrarEscaner()
    })
    .catch((err) => {
      console.error(err)
      cerrarEscaner()
    })
}
function cerrarEscaner(){
  mostrarEscaner.value = false
  if (codeReader) {
    try { codeReader.reset() } catch (e) {console.log(e)}
  }
}
onBeforeUnmount(() => {
  if (codeReader) {
    try { codeReader.reset() } catch (e) {console.log(e)}
  }
})
//funciones con acciones
const buscarResponsable=async(two)=>{
  if(!dni.value) return
  buscandoDni.value=true
  const catalogos=await categoriaService.getCategorias()
  catalogsoFormateadas.value=catalogos.data.map(c=>({
    value: c.id,
    label: c.denominacion
  }))
  try {
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
      responsable.value=null
    }
  } catch (e) {
    console.error(e)
    $q.notify({ color: 'negative', message: 'Responsable no encontrado', icon: 'error', position: 'top' })
    responsable.value=null
  }
  finally {
    buscandoDni.value=false
  }
}
const buscarActivo=async(tipo)=>{
  loading.value=true
  if(!codigoActivo.value) return
  modernFormFields.value=[...modernFormFieldTemp.value]
  try {
    if(tipo==='edit'){
      openModal.value.title='Gestión de activos'
      openModal.value.tipo='edit'
      const res=await activoService.getActivos({codigo: codigoActivo.value})
      if (!res.data || res.data.length === 0) {
        $q.notify({ color: 'warning', message: 'Activo no encontrado.', position: 'top', icon: 'warning' })
        activoEncontrado.value = null
        formData.value = { codigo: codigoActivo.value }
        return
      }
      formData.value={
        ...res.data[0],
        estado: {
          label: res.data[0]?.estado === 'activo' ? 'U' : 'D',
          value: res.data[0]?.estado
        },
        condicion: {
          label: res.data[0]?.condicion_display,
          value: res.data[0]?.condicion
        },
        descripcion: res.data[0]?.descripcion || '',
      }
      modernFormFields.value.find(f=>f.name==='denominacion').options=catalogsoFormateadas.value
      if(res.data[0]?.ultimo_report==1){
        openModal.value.title='Ver activo'
        openModal.value.tipo='view'
      }
      openModal.value.show=true
    }
  } catch (e) {
    console.error(e)
    $q.notify({ color: 'negative', message: 'Activo no encontrado.', position: 'top', icon: 'error' })
  }
  finally{
    loading.value=false
  }
}
const createActivo=async(tipos)=>{
  loading.value=true
  formData.value={}
  modernFormFields.value=[]
  //if(tipos==='nuevo')
  modernFormFields.value = modernFormFieldTemp.value.filter(field => field.name !== 'codigo')
  //else
  //modernFormFields.value = [...modernFormFieldTemp.value]
  openModal.value.title='Crear nuevo activo'
  openModal.value.tipo='create'
  modernFormFields.value.find(f=>f.name==='denominacion').options=catalogsoFormateadas.value
  tipo.value=tipos
  openModal.value.show=true
  loading.value=false
}
const handleCancel=()=>{
  openModal.value.show=false
  openModal.value.title='Gestión de activos'
  openModal.value.tipo='edit'
  codigoActivo.value=''
}
const handleSubmit=async()=>{
  openModal.value.loading=true
  if (!oficinaSeleccionada.value || !areaSeleccionada.value || !dni.value || !formData.value) {
    $q.notify({ color: 'negative', message: 'Complete todos los campos', position: 'top', icon: 'error' })
    return
  }
  try {
    const user=await authService.getCurrentUser()
    let activoId=formData.value.id || null
    let tipoActivo=''
    if(openModal.value.tipo==='create'){
      if(tipo.value==='nuevo'){
        formData.value.codigo='SIN/CODIGO->'+Math.random().toString(36).substring(2, 10).toUpperCase()
        tipoActivo='AU'
      }
      else{
        formData.value.codigo='cod/anteriores->'+Math.random().toString(36).substring(2, 10).toUpperCase()
        tipoActivo='AU'
      }
    }
    else{
      tipoActivo='AF'
    }
    const newFormData={
      ...formData.value,
      denominacion: formData.value.denominacion.label,
      //catalogo_id: formData.value.catalogo_id.value,
      responsable_id: responsable.value.id,
      area_id: areaSeleccionada.value.id,
      estado: formData.value.estado.value,
      condicion: formData.value.condicion.value,
      descripcion: formData.value.descripcion,
      notas: formData.value.notas,
      dniInventariador: user.dni,
      nombreInventariador: user.name,
      tipo: tipoActivo,
      piso: formData.value.piso.value,
      edificio_id: edificioSeleccionado.value.id,
      telefono: telefono.value,
      cod_toma: user.grupo,
      user_id_two: responsabletwo.value?.id||null
    }
    if(openModal.value.tipo==='edit' && activoId){
      await activoService.updateActivo(activoId, newFormData)
      $q.notify({ color: 'positive', message: 'Activo actualizado exitosamente', position: 'top', icon: 'check_circle'})
    }
    else{
      newFormData.fecha_adquisicion ="2025-11-11"//new Date().toISOString().slice(0, 10)
      await activoService.createActivo(newFormData)
      $q.notify({ color: 'positive', message: 'Activo creado exitosamente', position: 'top', icon: 'check_circle'})
    }
    console.log(newFormData)
  } catch (e) {
    console.log(e)
    const message=e.response?.data?.errors?.codigo || 'Error al guardar inventario'
    $q.notify({ color: 'negative', message: message, position: 'top', icon: 'error' })
  }
  finally{
    openModal.value.loading=false
    openModal.value.show=false
    openModal.value.title='Gestión de activos'
    openModal.value.tipo='edit'
    codigoActivo.value=''
    handleCancel()
  }
}
const mostrardelete=(rows)=>{
  console.log(rows.a_id)
  if(rows.codigo.length>13) return true
  else return false
}
const habilitarActivo=async(rows)=>{
  tab.value='historial'
  await activoService.habilitarActivo({id: rows.a_id})
  tab.value='faltantes'
}
const registrarFaltantes=async(rows)=>{
  const userOficina = await authService.getCurrentUser();
  const response=await activoService.getfaltantesReportePdf({inventariador_id: userOficina.id, fecha: rows.fecha, responsable_dni: rows.dni, area_aula: rows.aula})
  
  if (!(response instanceof Blob)) {
      throw new Error('La respuesta no es un Blob válido');
    }
    const blob = response;
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `reporte.pdf`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
    loadFaltantesReporte()
}
const dowloadReporte=async()=>{
  try{
    loadingdownload.value=true
    const userOficina = await authService.getCurrentUser();
    let response=null
    //console.log(userOficina.id, responsable.value.id, areaSeleccionada.value.id, responsabletwo.value?.id)
    if(!responsabletwo.value){
      console.log('not')
      response=await activoService.getReporteData({ id: userOficina.id, responsable_id: responsable.value.id, area_id: areaSeleccionada.value.id });
    }else{
      console.log('notgere')
      response=await activoService.getReporteData({ id: userOficina.id, responsable_id: responsable.value.id, area_id: areaSeleccionada.value.id, user_id_two: responsabletwo.value.id});
    }
  if (!(response instanceof Blob)) {
      throw new Error('La respuesta no es un Blob válido');
    }
    const blob = response;
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `reporte.pdf`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
  } catch (error) {
    console.error('Error al descargar el PDF:', error);
  } finally {
    loadingdownload.value = false;
  }
}
//funciones inicioales
const edificioscargados=ref([])
onMounted(async () => {
  const user=await authService.getCurrentUser()
  if(user.oficinas.length>0){
    oficinasAsignadas.value=user.oficinas.map(o=>({
      id: o.id,
      label: `${o.codigo} - ${o.denominacion}${o.escuela ? ' - ' + o.escuela : ''}`
    }))
  }
  const edificiosResp=await edificioService.getEdificios()
  edificioscargados.value=edificiosResp.map(e=>({
    id:e.id,
    codigo: e.codigo,
    denominacion: e.denominacion,
    label:`${e.codigo} - ${e.denominacion}`
  }))
  edificios.value=edificioscargados.value
})
function filtrarEdificios(val, update) {
  update(()=>{
    const texto=val.toLowerCase()
    if(texto===''){
      edificios.value=edificioscargados.value
    }
    else{
      edificios.value=edificioscargados.value.filter(e=>
        e.codigo.toLowerCase().includes(texto)||
        e.denominacion.toLowerCase().includes(texto)
      )
    }
  })
}
watch(oficinaSeleccionada, async (newOficina)=>{
  areaSeleccionada.value=null
  responsable.value=null
  pasoResumen.value=false
  if(newOficina){
    const area=await areaService.getAreas({oficina: newOficina.id})
    areas.value=[...new Set(area.data.map(a=>({
      ...a,
      label: `${a.codigo} - ${a.aula}`
    })))]
  }
  else{
    areas.value=[]
    bienesFaltantes.value=[]
  }
})
watch(formData, (newVal) => {
  if(newVal.dimension){
      modernFormFields.value.find(f=>f.name==='numero_serie').required=false
  }
  else{
      modernFormFields.value.find(f=>f.name==='numero_serie').required=true
  }
});
const historial=ref([])
const faltantesReporte=ref([])
watch(tabheader, async()=>{
  if(tabheader.value==='second'){
    loadFaltantesReporte()
  }
})
const loadFaltantesReporte=async()=>{
  const userOficina = await authService.getCurrentUser();
  const faltantes=await activoService.getfaltantesReporte({inventariador_id: userOficina.id})
  faltantesReporte.value=faltantes
  console.log(faltantesReporte.value)
}
watch(tab, async()=>{
  if(tab.value==='faltantes'){
    loadingFaltantes.value=true
    const userOficina = await authService.getCurrentUser();
    let response=null
    if(!responsabletwo.value){
      
      response=await activoService.getReportePdf({ id: userOficina.id, responsable_id: responsable.value.id, area_id: areaSeleccionada.value.id });
    }
    else{
      response=await activoService.getReportePdf({ id: userOficina.id, responsable_id: responsable.value.id, area_id: areaSeleccionada.value.id, user_id_two: responsabletwo.value.id});
    }
    bienesFaltantes.value=response
    loadingFaltantes.value=false
  }
  if(tab.value==='historial'){
    let response=null
    const userOficina = await authService.getCurrentUser();
    if(!responsabletwo.value){
      response=await activoService.getBienesHistorial({user_id: userOficina.id, responsable_id: responsable.value.id, area_id: areaSeleccionada.value.id})
    }
    else{
      console.log(userOficina.id, responsable.value.id, areaSeleccionada.value.id, responsabletwo.value.id)
      response=await activoService.getBienesHistorial({user_id: userOficina.id, responsable_id: responsable.value.id, area_id: areaSeleccionada.value.id, user_id_two: responsabletwo.value.id})
    }
    historial.value=response
  }
  if(tab.value==='faltaregistrar'){
    faltantesLoad()
  }
})
const faltantesLoad=async()=>{
  try {
    let response=null
    response=await activoService.getBienesFaltantes({area_id: areaSeleccionada.value.id, page: pagination.value.page})
    console.log(response.total)
    pagination.value={
      page: response.current_page,
      rowsPerPage: response.per_page,
      rowsNumber: response.total
    }
    console.log(pagination.value)
    faltaresRows.value=response.data
  } catch (error) {
    console.log(error)
  }
}
const dowloadFaltantes=async()=>{
  loadingFaltantes.value=true
  const response=await activoService.getBienesFaltantes({oficina_id: oficinaSeleccionada.value.id})
  console.log(response)
  const workbook = new ExcelJS.Workbook();
  const worksheet = workbook.addWorksheet('Reporte');
  worksheet.columns = [
    { header: 'Código', key: 'codigo', width: 15 },
    { header: 'Descripción', key: 'denominacion', width: 30 },
    { header: 'Cod Centro de costo', key: 'cod_cc', width: 35 },
    { header: 'Cod Ub', key: 'cod_ubicacion', width: 5 },
    { header: 'Ubicación', key: 'ubicacion', width: 25 }
  ];
  worksheet.getRow(1).font = { bold: true, size: 12 };
  worksheet.getRow(1).fill = {
    type: 'pattern',
    pattern: 'solid',
    fgColor: { argb: 'FFE0E0E0' }
  };
  worksheet.getRow(1).alignment = { vertical: 'middle', horizontal: 'center' };
  response.forEach(activo => {
    worksheet.addRow({
      codigo: activo.codigo
      ? (activo.codigo.includes('->')
          ? activo.codigo.split('->')[0]
          : activo.codigo)
      : '-',
      denominacion: activo.denominacion || '-',
      cod_cc: activo.oficina_id || '-',
      cod_ubicacion: activo.arcodigo || '-',
      ubicacion: activo.aula || '-'
    });
  });
  worksheet.eachRow((row) => {
    row.eachCell((cell) => {
      cell.border = {
        top: { style: 'thin' },
        left: { style: 'thin' },
        bottom: { style: 'thin' },
        right: { style: 'thin' }
      };
    });
  });
  const buffer = await workbook.xlsx.writeBuffer();
  const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
  const url = window.URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.setAttribute('download', `reporte_itos_${new Date().toISOString().split('T')[0]}.xlsx`);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  $q.notify({
    color: 'positive',
    message: 'Archivo Excel exportado exitosamente',
    icon: 'check',
    position: 'top'
  });
  loadingFaltantes.value=false
}
const dowloadHistorial=async(row)=>{
  const userOficina = await authService.getCurrentUser();
  //const response=await activoService.getReporteData({responseble_id: '3', area_id: '1540', id: '3'})
  //console.log(userOficina.id, responsable.value, areaSeleccionada.value, row.fecha)
  let response=null
  if(!responsabletwo.value){
    response=await activoService.getBienesHistorialPdf({ user_id: userOficina.id, responsable_id: responsable.value.id, area_id: areaSeleccionada.value.id, fecha: row.fecha});
  }
  else{
    response=await activoService.getBienesHistorialPdf({ user_id: userOficina.id, responsable_id: responsable.value.id, area_id: areaSeleccionada.value.id, fecha: row.fecha, user_id_two: responsabletwo.value.id});
  }
  //const response=await activoService.getBienesHistorialPdf({ id: userOficina.id, responsable_id: responsable.value.id, area_id: areaSeleccionada.value.id, fecha: row.fecha});
  if (!(response instanceof Blob)) {
      throw new Error('La respuesta no es un Blob válido');
    }
    const blob = response;
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `reporte.pdf`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
}
const deleteActivo=async(rows)=>{
  console.log(rows.a_id)
  tab.value='hisotrial'
  await activoService.deleteActivo(rows.a_id)
  tab.value='faltantes'
}
const onRequest = (props) => {
  const { page, rowsPerPage } = props.pagination
  pagination.value = { ...pagination.value, page, rowsPerPage }
  faltantesLoad()
}
///////////////////////////////////////////////////////////////////////////////////////////////
const openModal=ref({
  title: 'Gestión de activos',
  tipo: 'edit',
  show: false,
  loading: false
})
const modernFormFields=ref([])
const modernFormFieldTemp = ref([
    { type: 'separator', label: 'Información Básica' },
    { name: 'codigo', type: 'text', label: 'Código del Activo', placeholder: 'ACT-001', rules: [val => !!val || 'El número de serie es requerido', val => (val.length === 10 || val.length === 12) || 'El número de serie debe tener exactamente 10 o 12 caracteres'], search: 'qr_code', minlenth: 10, maxlength: 12},
    { name: 'denominacion', type: 'select', label: 'Denominación', placeholder: 'Seleccione una denominación', rules: [val => !!val || 'El campo es requerido'], options: [], prepend: 'category', useInput: true, fillInput: true, inputDebounce: 300, mapOptions: true, optionLabel: 'label', optionValue: 'value', clearable: true},
    //{ name: 'marca', type: 'text', label: 'Marca', placeholder: 'Marca del fabricante', prepend: 'branding_watermark', space: true},
    { name: 'marca', type: 'text', label: 'Marca', placeholder: 'Marca del fabricante', rules: [val => !!val || 'La marca es requerida'], prepend: 'branding_watermark' },
    { name: 'modelo', type: 'text', label: 'Modelo', placeholder: 'Modelo específico', prepend: 'model_training', space: true},
    { name: 'color', type: 'text', label: 'Color', placeholder: 'Color específico', prepend: 'model_training', space: true},
    //{ name: 'numero_serie', type: 'text', label: 'Número de Serie', placeholder: 'Número de serie del fabricante', prepend: 'confirmation_number', space:true},
    { name: 'numero_serie', type: 'text', label: 'Número de Serie', placeholder: 'Número de serie del fabricante', prepend: 'confirmation_number' },
    { name: 'dimension', type: "text", label: 'Dimensión', placeholder: 'Dimensión del activo', prepend: 'straighten'},
    { type: 'separator', label: 'Clasificación'},
    {
      name: 'piso', type: 'select', label: 'Piso', placeholder: 'Seleccionae un Piso', rules: [val=> !!val || 'El campo es requerido'], options:[
          { label: 'Sótano', value: 'Sótano' },
          { label: 'Primer piso', value: 'Primer piso' },
          { label: 'Segundo piso', value: 'Segundo piso' },
          { label: 'Tercer piso', value: 'Tercer piso' },
          { label: 'Cuarto piso', value: 'Cuarto piso' },
          { label: 'Quinto piso', value: 'Quinto piso' },
          { label: 'Sexto piso', value: 'Sexto piso' },
          { label: 'Séptimo piso', value: 'Séptimo piso' },
          { label: 'Octavo piso', value: 'Octavo piso' },
          { label: 'Noveno piso', value: 'Noveno piso' },
          { label: 'Décimo piso', value: 'Décimo piso' },
          { label: 'Onceavo piso', value: 'Onceavo piso' },
          { label: 'Doceavo piso', value: 'Doceavo piso' },
          { label: 'Treceavo piso', value: 'Treceavo piso' },
          { label: 'Catorceavo piso', value: 'Catorceavo piso' },
          { label: 'Quinceavo piso', value: 'Quinceavo piso' },
          { label: 'Azotea', value: 'Azotea' },
        ], prepend: 'stairs', autogrow: true, uppercase: true
    },
    { name: 'estado', type: 'select', label: 'Situación', placeholder: 'Seleccione el estado', rules: [val => !!val || 'El estado es requerido'], options: [ { label: 'En uso', value: 'activo' }, { label: 'En desuso', value: 'inactivo' }], prepend: 'check_circle'},
    { name: 'condicion', type: 'select', label: 'Estado', placeholder: 'Seleccione la condición', rules: [val => !!val || 'La condición es requerida'],
      options: [ {label: 'Nuevo', value: 'nuevo'}, { label: 'Bueno', value: 'bueno' }, { label: 'Regular', value: 'regular' }, { label: 'Malo', value: 'malo' }],
      prepend: 'grade'
    },
    //{ name: 'tipo', type: 'select', label: 'Tipo', placeholder: 'Seleccione el tipo', rules: [val => !!val || 'El tipo es requerido'], options: [ { label: 'Activo fijo', value: 'AF' }, { label: 'Bienes no depresiables', value: 'ND' }, { label: 'Bienes auxiliares', value: 'AU' }], prepend: 'check_circle'},
    //{ name: 'cod_toma', type: 'text', label: 'Código toma', placeholder: 'ACT-001', rules: [val => !!val || 'El número de serie es requerido', val => (val.length > 6 && val.length < 13) || 'El número de serie debe tener exactamente entre 6 a 12 caracteres'], minlenth: 6, maxlength: 12},
    { name: 'aula', type: 'text', label: 'Aula', placeholder: 'Ingrese el aula', rules: [val => !!val || 'El tipo es requerido'], prepend: 'check_circle'},
    { type: 'separator', label: 'Información Adicional'},
    { name: 'descripcion', type: 'textarea', label: 'Observación', placeholder: 'Observación detallada del activo...', rows: 3, autogrow: true, maxlength: 255, counter: true, prepend: 'description'}
])
const bienesColumns = [
  { name: 'codigo', label: 'Código', field: 'codigo', align: 'left', sortable: true },
  { name: 'denominacion', label: 'Denominación', field: row => row.denominacion, align: 'left' },
  { name: 'numero_serie', label: 'Número de Serie', field: 'numero_serie', align: 'left' },
  { name: 'marca', label: 'Marca', field: 'marca', align: 'left' },
  { name: 'responsable', label: 'Responsable', field: row => row.r_name, align: 'left' },
  { name: 'actions', label: 'Acciones'}
  //{ name: 'responsable', label: 'Responsable', field: row => row.responsable?.name, align: 'left' },
  //{ name: 'area', label: 'Área', field: row => row.area?.nombre_completo || 'Sin asignar', align: 'left' }
]
const historialColumns=[
  { name: 'name', label: 'Responsable', field: 'name', align: 'left', sortable: true },
  { name: 'aula', label: 'Ubicacion', field: row => row.aula, align: 'left' },
  { name: 'fecha', label: 'Fecha', field: row=>row.fecha, align: 'left'},
  { name: 'total_activos', label: 'N° Activos', field: row=>row.total_activos, align: 'left'},
  { name: 'actions', label: 'acciones'}
]
const faltantes=[
  { name: 'codigo', label: 'Codigo', field: 'codigo', align: 'left', sortable: true },
  { name: 'denominacion', label: 'denominacion', field: 'denominacion', align: 'left'},
  { name: 'area', label:'Ubicación', field: row=>row.aula, align: 'left'}
]
const faltantesColumns=[
  { name: 'name', label: 'Responsable', field: 'name', align: 'left', sortable: true },
  { name: 'dni', label: 'DNI', field: 'dni', align: 'left'},
  { name: 'fecha', label: 'Fecha', field: 'fecha', align: 'left'},
  { name: 'total_activos', label: 'N° Activos', field: 'total_activos', align: 'left'},
  { name: 'area', label:'Ubicación', field: row=>row.aula, align: 'left'},
  { name: 'actions', label: 'Acciones'}
]
</script>