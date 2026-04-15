<template>
  <q-page class="q-pa-md flex flex-center bg-grey-2">
    <q-card class="q-pa-lg q-mx-auto shadow-4" style="width: 100%; max-width: 600px; min-width: 0;">
      <q-card-section v-if="!pasoResumen">
        <q-form>
          <div class="q-gutter-md">
            <q-select
              v-model="oficinaSeleccionada"
              :options="oficinasAsignadas"
              label="Seleccione la oficina"
              option-label="denominacion"
              option-value="id"
              :disable="oficinasAsignadas.length === 0"
              dense
              outlined
              required
            />
            <q-select
              v-model="areaSeleccionada"
              :options="areas"
              label="Seleccione el área"
              option-label="nombre_completo"
              option-value="id"
              :disable="(!oficinaSeleccionada)||(areas.length===0)"
              dense
              outlined
              required
            />
            <div class="row items-end q-gutter-sm">
              <q-input
                v-model="dni"
                label="DNI de la persona encargada"
                mask="########"
                required
                class="col"
                :disable="!areaSeleccionada || buscandoDni"
                :rules="[val => !val || val.length === 8 || 'El DNI debe tener 8 dígitos']"
                dense
                outlined
                maxlength="8"
                inputmode="numeric"
              >
                <template v-slot:append>
                    <q-btn flat round icon="search" color="primary" @click="buscarResponsable" :loading="buscandoDni" :disable="dni.length !== 8 || buscandoDni" />
                </template>
              </q-input>
            </div>
            <div v-if="responsable" class="q-mt-md">
              <q-card flat bordered class="bg-grey-1">
                <q-card-section class="q-pa-sm">
                  <div class="text-subtitle2">Responsable</div>
                  <div><b>Nombre:</b> {{ responsable.nombre }}</div>
                  <div><b>DNI:</b> {{ responsable.dni }}</div>
                </q-card-section>
              </q-card>
             <q-btn label="Siguiente" color="primary" class="q-mt-md full-width" @click="pasoResumen = true" />
            </div>
          </div>
        </q-form>
      </q-card-section>
      <template v-else>
        <q-card-section class="bg-grey-3 q-mb-md q-pa-sm q-rounded-borders">
          <div class="row items-center q-gutter-sm q-col-gutter-sm q-mb-xs">
            <q-btn flat dense color="primary" icon="undo" label="Cambiar selección" @click="volverSeleccion" class="text-caption q-mr-sm" />
            <div class="col-12 col-md-auto text-caption text-grey-8"><b>Oficina:</b> {{ oficinaSeleccionada?.denominacion }} / <b>Área:</b> {{ areaSeleccionada?.nombre_completo }}</div>
            <div class="col-12 col-md-auto text-caption text-grey-8"><b>Responsable:</b> {{ responsable?.nombre }} / <b>DNI:</b> {{ responsable?.dni }}</div>
          </div>
        </q-card-section>
        <q-tabs v-model="tab" class="text-primary q-mb-md" align="justify" dense>
          <q-tab name="inventariar" label="Inventariar Bienes" />
          <q-tab name="faltantes" label="Bienes Faltantes" />
        </q-tabs>
        <q-separator />
        <q-tab-panels v-model="tab" animated class="q-mt-md">
          <q-tab-panel name="inventariar">
            <q-form>
              <div class="row items-center q-gutter-x-sm q-mt-md">
                <q-input
                  v-model="codigoActivo"
                  label="Código del activo"
                  mask="############"
                  maxlength="12"
                  required
                  class="col"
                  :rules="[val => !val || [10,12].includes(val.length) || 'El código debe tener 10 o 12 dígitos']"
                  dense
                  outlined
                >
                    <template v-slot:append>
                      <q-btn
                        label="Buscar Activo"
                        color="primary"
                        @click="buscarActivo"
                        :disable="![10,12].includes(codigoActivo.length)"
                        class="q-px-md"
                        dense
                      />
                    </template>
                </q-input>
              </div>
              <div v-if="activoEncontrado || mostrarFormularioCreacion" class="q-mt-md">
                <q-card flat bordered class="bg-grey-1">
                  <DynamicForm
                    :fields="modernFormFields"
                    v-model="formData"
                    :readonly="modal.mode==='view'"
                    :mode="modal.mode"
                    :loading="loading"
                    @submit="guardarInventario"
                    @cancel="cancelarCreacion"
                  />
                </q-card>
              </div>
              <div v-else-if="creandoNuevoActivo && !mostrarFormularioCreacion" class="q-mt-md">
                <q-card flat bordered class="bg-grey-1">
                  <q-card-section class="text-center">
                    <div class="text-subtitle2 q-mb-md">¿Desea agregar este activo al inventario?</div>
                    <q-btn
                      label="Agregar Activo"
                      color="primary"
                      @click="mostrarFormulario"
                      class="q-mr-sm"
                    />
                    <q-btn
                      label="Cancelar"
                      flat
                      @click="cancelarCreacion"
                    />
                  </q-card-section>
                </q-card>
              </div>
            </q-form>
          </q-tab-panel>
          <q-tab-panel name="faltantes">
             <div class="text-h6 q-mb-md">Bienes faltantes por inventariar</div>
                <q-table
                :rows="bienesFaltantes"
                :columns="bienesColumns"
                row-key="id"
                flat
                bordered
                :loading="loading"
                v-model:pagination="bienesPagination"
                @request="onBienesRequest"
                :rows-per-page-options="[5, 10, 15, 25, 50]"
                wrap-cells
              />
           </q-tab-panel>
        </q-tab-panels>
      </template>
    </q-card>
  </q-page>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useQuasar } from 'quasar'
import { areaService } from 'src/services/areaService'
import { activoService } from 'src/services/activoService'
import { authService } from '../../services/authService'
import DynamicForm from 'src/components/DynamicForm.vue'
import { categoriaService } from 'src/services/categoriaService'

const $q = useQuasar()

const oficinasAsignadas = ref([])
const oficinaSeleccionada = ref(null)
const areas = ref([])
const areaSeleccionada = ref(null)
const dni = ref('')
const responsable = ref(null)
const buscandoDni = ref(false)
const pasoResumen = ref(false)
const tab = ref('inventariar')
const codigoActivo = ref('')
const bienesFaltantes = ref([])
const formData=ref([])
const loading=ref(false)
const modal= ref({
    mode: 'edit'
})

// Configuración de la tabla de bienes faltantes
const bienesPagination = ref({
  sortBy: 'codigo',
  descending: false,
  page: 1,
  rowsPerPage: 10,
  rowsNumber: 0
})

const bienesColumns = [
  { name: 'codigo', label: 'Código', field: 'codigo', align: 'left', sortable: true },
  { name: 'numero_serie', label: 'Número de Serie', field: 'numero_serie', align: 'left' },
  { name: 'color', label: 'Color', field: 'color', align: 'left' },
  { name: 'catalogo', label: 'Denominación', field: row => row.catalogo?.denominacion, align: 'left' },
  { name: 'responsable', label: 'Responsable', field: row => row.responsable?.name, align: 'left' },
  //{ name: 'area', label: 'Área', field: row => row.area?.nombre_completo || 'Sin asignar', align: 'left' }
]
const modernFormFields = ref([
    {
      type: 'separator', label: 'Información Básica'
    },
    {
      name: 'codigo', type: 'text', label: 'Código del Activo', placeholder: 'ACT-001', rules: [val => !!val || 'El número de serie es requerido', val => (val.length === 10 || val.length === 12) || 'El número de serie debe tener exactamente 10 o 12 caracteres'], search: 'qr_code', minlenth: 10, maxlength: 12
    },
    {
      name: 'catalogo_id',
      type: 'select',
      label: 'Denominación',
      placeholder: 'Seleccione una denominación',
      rules: [val => !!val || 'El campo es requerido'],
      options: [],
      prepend: 'category',
      useInput: true,
      fillInput: true,
      inputDebounce: 300,
      mapOptions: true,
      optionLabel: 'label',
      optionValue: 'value',
      clearable: true
    },
    {
      name: 'marca', type: 'text', label: 'Marca', placeholder: 'Marca del fabricante', rules: [val => !!val || 'La marca es requerida'], prepend: 'branding_watermark'
    },
    {
      name: 'modelo', type: 'text', label: 'Modelo', placeholder: 'Modelo específico', rules: [val => !!val || 'El modelo es requerido'], prepend: 'model_training'
    },
    {
      name: 'color', type: 'text', label: 'Color', placeholder: 'Color específico', prepend: 'model_training'
    },
    {
      name: 'numero_serie', type: 'text', label: 'Número de Serie', placeholder: 'Número de serie del fabricante', rules: [val => !!val || 'El número de serie es requerido'], prepend: 'confirmation_number'
    },
    {
      type: 'separator', label: 'Clasificación'
    },
    {
      name: 'estado',
      type: 'select',
      label: 'Estado',
      placeholder: 'Seleccione el estado',
      rules: [val => !!val || 'El estado es requerido'],
      options: [ { label: 'Activo', value: 'activo' }, { label: 'Inactivo', value: 'inactivo' }
      ],
      prepend: 'check_circle'
    },
    {
      name: 'condicion',
      type: 'select',
      label: 'Condición',
      placeholder: 'Seleccione la condición',
      rules: [val => !!val || 'La condición es requerida'],
      options: [ {label: 'Nuevo', value: 'nuevo'}, { label: 'Bueno', value: 'bueno' }, { label: 'Regular', value: 'regular' }, { label: 'Malo', value: 'malo' }
      ],
      prepend: 'grade'
    },
    {
      type: 'separator', label: 'Información Adicional'
    },
    {
      name: 'descripcion', type: 'textarea', label: 'Descripción', placeholder: 'Descripción detallada del activo...', rows: 3, autogrow: true, maxlength: 500, counter: true, prepend: 'description'
    },
    {
      name: 'notas', type: 'textarea', label: 'Notas', placeholder: 'Notas adicionales...', rows: 2, autogrow: true, maxlength: 300, counter: true, prepend: 'note'
    }
])

const creandoNuevoActivo = ref(false)
const activoEncontrado = ref(null)
const mostrarFormularioCreacion = ref(false)

onMounted(async () => {
  const user = await authService.getCurrentUser()
  oficinasAsignadas.value = user?.oficinas || []
})

watch(oficinaSeleccionada, async (nuevaOficina) => {
  areaSeleccionada.value = null
  responsable.value = null
  pasoResumen.value = false
  if (nuevaOficina) {
    const res = await areaService.getAreas({ oficina: nuevaOficina.id })
    areas.value = [...new Set(res.data.map(o => o))]
    //await cargarBienesFaltantes()
  } else {
    areas.value = []
    bienesFaltantes.value = []
  }
})

watch(areaSeleccionada, async () => {
  responsable.value = null
  pasoResumen.value = false
  //await cargarBienesFaltantes()
})

watch(tab, async (newTab) => {
  if (newTab === 'faltantes') {
    await cargarBienesFaltantes()
  }
})

const buscarResponsable=async()=> {
  if (!dni.value) return
  buscandoDni.value = true
  try {
    const res=await authService.getUsuarios(dni.value)
    if(res[0]?.name)
        responsable.value = { id: res[0].id, nombre: res[0].name, dni: dni.value }
    else {
        $q.notify({ color: 'negative', message: 'Responsable no encontrado' })
        responsable.value = null
    }
  } catch (e) {
    console.error(e)
    $q.notify({ color: 'negative', message: 'Responsable no encontrado' })
    responsable.value = null
  } finally {
    buscandoDni.value = false
  }
}

async function buscarActivo() {
  if (!codigoActivo.value) return
  try {
    const res = await activoService.getActivos({codigo: codigoActivo.value})
    if (!res.data || res.data.length === 0) {
      $q.notify({ color: 'warning', message: 'Activo no encontrado.' })
      creandoNuevoActivo.value = true
      activoEncontrado.value = null
      mostrarFormularioCreacion.value = false
      formData.value = { codigo: codigoActivo.value }
      return
    }
    // Si se encuentra, flujo normal
    creandoNuevoActivo.value = false
    mostrarFormularioCreacion.value = false
    activoEncontrado.value = res.data[0]
    formData.value={
        ...res.data[0],
        catalogo_id: {
            label: res.data[0]?.catalogo?.denominacion,
            value: res.data[0]?.catalogo?.id
        },
        estado: {
            label: res.data[0]?.estado_display,
            value: res.data[0]?.estado
        },
        condicion: {
            label: res.data[0]?.condicion_display,
            value: res.data[0]?.condicion
        },
        descripcion: res.data[0]?.descripcion || '',
        notas: res.data[0]?.notas || ''
    }
    modernFormFields.value = modernFormFields.value.map(field => {
      if (!field || field.type === 'separator') return field;
      if (field.name === 'estado' || field.name === 'condicion') {
        return {
          ...field,
          disabled: false
        };
      }
      // Solo editable si el valor es null, undefined o vacío
      const fieldValue = formData.value[field.name];
      const isDisabled = fieldValue !== null && fieldValue !== undefined && fieldValue !== '';
      return {
        ...field,
        disabled: isDisabled
      };
    });
  } catch (e) {
    console.error(e)
    $q.notify({ color: 'negative', message: 'Activo no encontrado.' })
    creandoNuevoActivo.value = true
    activoEncontrado.value = null
    mostrarFormularioCreacion.value = false
    formData.value = { codigo: codigoActivo.value }
  }
}

const mostrarFormulario =async()=> {
  const catalogos=await categoriaService.getCategorias()
  const catalogosFormateadas=catalogos.data.map(cat=>({
    label: cat.denominacion,
    value: cat.id
  }))
  modernFormFields.value.find(f=>f.name==='catalogo_id').options=catalogosFormateadas
  mostrarFormularioCreacion.value = true
  modernFormFields.value = modernFormFields.value.map(field => ({
    ...field,
    disabled: formData.value[field.name] ? true : field.disabled
  }))
}

async function guardarInventario() {
  loading.value=true
  if (!oficinaSeleccionada.value || !areaSeleccionada.value || !dni.value || !formData.value) {
    $q.notify({ color: 'negative', message: 'Complete todos los campos' })
    return
  }
  try {
    const user = await authService.getCurrentUser()
    let activoId = formData.value.id
    const newFormData={
        ...formData.value,
        catalogo_id: formData.value.catalogo_id.value,
        responsable_id: responsable.value.id,
        area_id: areaSeleccionada.value.id,
        estado: formData.value.estado.value,
        condicion: formData.value.condicion.value,
        descripcion: formData.value.descripcion,
        notas: formData.value.notas,
        dniInventariador: user.dni,
        nombreInventariador: user.name
    }
    if (creandoNuevoActivo.value) {
      newFormData.fecha_adquisicion = new Date().toISOString().slice(0, 10)
      const res = await activoService.createActivo(newFormData)
      activoId = res.data?.id
      $q.notify({ color: 'positive', message: 'Activo creado exitosamente' })

    } else if (activoId) {
      await activoService.updateActivo(activoId, newFormData)
      $q.notify({ color: 'positive', message: 'Activo actualizado exitosamente' })
    }
    cancelarCreacion()
    loading.value=false
  } catch (e) {
    console.error(e)
    $q.notify({ color: 'negative', message: 'Error al guardar inventario' })
  }
}

async function cargarBienesFaltantes() {
  if (!oficinaSeleccionada.value || !areaSeleccionada.value) {
    bienesFaltantes.value = []
    bienesPagination.value.rowsNumber = 0
    return
  }
  
  loading.value = true
  try {
    const params = {
      //area_id: areaSeleccionada.value.id,
      page: bienesPagination.value.page,
      per_page: bienesPagination.value.rowsPerPage,
      sort_by: bienesPagination.value.sortBy,
      desc: bienesPagination.value.descending
    }
    
    const res = await activoService.getBienesFaltantes(params)
    console.log('Respuesta completa de la API:', res)
    
    if (res.data) {
      bienesFaltantes.value = res.data
      bienesPagination.value.rowsNumber = res.total
      console.log('Datos asignados:', bienesFaltantes.value.length, 'elementos')
      console.log('Total configurado:', bienesPagination.value.rowsNumber)
    } else {
      bienesFaltantes.value = []
      bienesPagination.value.rowsNumber = 0
    }
    console.log('Estado final de bienesPagination:', bienesPagination.value)
  } catch (e) {
    console.error(e)
    bienesFaltantes.value = []
    bienesPagination.value.rowsNumber = 0
  } finally {
    loading.value = false
  }
}

function volverSeleccion() {
  pasoResumen.value = false
}

function cancelarCreacion() {
  creandoNuevoActivo.value = false
  mostrarFormularioCreacion.value = false
  formData.value = {}
  codigoActivo.value = ''
  activoEncontrado.value=null
}

// Función para manejar cambios de paginación y ordenamiento en la tabla de bienes faltantes
const onBienesRequest = (props) => {
  const { page, rowsPerPage, sortBy, descending } = props.pagination
  bienesPagination.value = { ...bienesPagination.value, page, rowsPerPage, sortBy, descending }
  cargarBienesFaltantes()
}

</script>

<style scoped>
@media (max-width: 600px) {
  .q-card {
    padding: 0 !important;
    box-shadow: none !important;
  }
  .q-card-section {
    padding-left: 8px !important;
    padding-right: 8px !important;
  }
  .row.no-wrap {
    flex-wrap: wrap !important;
  }
}
</style>