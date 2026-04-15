<template>
  <q-page class="q-pa-md">
    <PermissionsAccess 
    :is-admin="auth.isAdmin"
    :is-supervisor="auth.isSupervisor" 
    :is-responsable="auth.isResponsable" 
    :is-consulta="auth.isConsulta">
    <div class="row q-mb-md items-center">
      <div class="col">
        <h5 class="q-my-none text-weight-bold">Movimientos de Activos</h5>
      </div>
    </div>
    <q-card flat bordered class="q-mb-md">
      <q-toolbar>
        <div class="row full-width q-col-gutter-md">
          <div class="col-12 col-md-3">
            <q-input dense v-model="search" placeholder="Buscar por activo, responsable..." clearable @update:model-value="onSearchInput">
              <template v-slot:append>
                <q-icon name="search" />
              </template>
            </q-input>
          </div>
          <div class="col-12 col-md-3">
            <q-input dense v-model="fechaDesde" type="date" label="Desde" clearable @update:model-value="loadData" />
          </div>
          <div class="col-12 col-md-3">
            <q-input dense v-model="fechaHasta" type="date" label="Hasta" clearable @update:model-value="loadData" />
          </div>
          <div class="col-12 col-md-3">
            <q-select v-model="tipo" :options="tiposMovimiento" label="Tipo de Movimiento" dense clearable @update:model-value="loadData" />
          </div>
        </div>
      </q-toolbar>
    </q-card>
    <q-table
      flat
      bordered
      :rows="movimientos"
      :columns="columns"
      row-key="id"
      :loading="loading"
      :v-model:pagination="pagination"
      @request="onRequest"
    >
      <template v-slot:body-cell-pdf="props">
        <q-td :props="props">
          <q-btn v-if="props.row.id" flat dense round icon="picture_as_pdf" color="primary" @click="descargarPDF(props.row.id)">
            <q-tooltip>Descargar PDF</q-tooltip>
          </q-btn>
        </q-td>
      </template>
      <template v-slot:body-cell-actions="props">
        <q-td :props="props">
          <q-btn flat dense round icon="visibility" color="info" @click="verDetalle(props.row)">
            <q-tooltip>Ver detalle</q-tooltip>
          </q-btn>
          <q-btn v-if="mostrarAccionAutorizador(props.row)" flat dense round icon="check" color="positive" @click="abrirDialogAccion('autorizar', props.row)">
            <q-tooltip>Autorizar Entrega</q-tooltip>
          </q-btn>
          <q-btn v-if="mostrarAccionAutorizador(props.row)" flat dense round icon="close" color="negative" @click="abrirDialogAccion('rechazar_autorizador', props.row)">
            <q-tooltip>Rechazar</q-tooltip>
          </q-btn>
          <q-btn v-if="mostrarAccionReceptor(props.row)" flat dense round icon="check" color="positive" @click="abrirDialogAccion('aceptar_receptor', props.row)">
            <q-tooltip>Aceptar Entrega</q-tooltip>
          </q-btn>
          <q-btn v-if="mostrarAccionReceptor(props.row)" flat dense round icon="close" color="negative" @click="abrirDialogAccion('rechazar_receptor', props.row)">
            <q-tooltip>Rechazar Entrega</q-tooltip>
          </q-btn>
        </q-td>
      </template>
      <template v-slot:body-cell-fecha_movimiento="props">
        <q-td :props="props">
          {{ props.row.fecha_movimiento ? props.row.fecha_movimiento.split('T')[0] : '' }}
        </q-td>
      </template>
    </q-table>
    <q-dialog v-model="detalleDialog">
      <q-card style="min-width:400px">
        <q-card-section>
          <div class="text-h6">Detalle de Movimiento</div>
          <div v-if="movimientoDetalle">
            <div><b>Codigo:</b> {{ movimientoDetalle.codigo }}</div>
            <div><b>Fecha:</b> {{ movimientoDetalle.fecha_movimiento }}</div>
          </div>
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat label="Cerrar" color="primary" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>
    <q-dialog v-model="accionDialog">
      <q-card style="min-width:400px">
        <q-card-section>
          <div class="text-h6">
            {{
              accionTipo === 'autorizar' ? '¿Autorizar entrega de este movimiento?' :
              accionTipo === 'rechazar_autorizador' ? '¿Rechazar este movimiento?' :
              accionTipo === 'aceptar_receptor' ? '¿Aceptar y finalizar entrega?' :
              '¿Rechazar entrega?'
            }}
          </div>
          <div v-if="accionTipo === 'aceptar_receptor' || accionTipo === 'rechazar_receptor' || accionTipo === 'rechazar_autorizador'">
            <q-input v-model="observaciones" type="textarea" label="Observaciones (opcional)" autogrow />
          </div>
        </q-card-section>
        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="primary" v-close-popup />
          <q-btn flat :label="'Confirmar'" color="positive" @click="ejecutarAccion" />
        </q-card-actions>
      </q-card>
    </q-dialog>
    </PermissionsAccess>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import PermissionsAccess from 'src/components/Permissions/PermissionsAccess.vue'
import { useQuasar } from 'quasar'
import { movimientoService } from 'src/services/movimientoService'
import { useAuthStore } from 'src/stores/auth-store'

const $q = useQuasar()
const auth = useAuthStore()
const loading = ref(false)
const movimientos = ref([])
const search = ref('')
const fechaDesde = ref(null)
const fechaHasta = ref(null)
const tipo = ref(null)
const searchTimeout = ref(null)
const tiposMovimiento = [
  { label: 'Pendiente', value: 'pendiente'},
  { label: 'En entrega', value: 'en_entrega' },
  { label: 'Aceptado', value: 'entregado' },
  { label: 'Rechazo', value: 'rechazado' }
]
const pagination = ref({
  sortBy: 'fecha_movimiento',
  descending: true,
  page: 1,
  rowsPerPage: 10,
  rowsNumber: 0
})
const columns = [
  { name: 'codigo', label: 'Código', field: 'codigo', align: 'left' },
  { name: 'fecha_movimiento', label: 'Fecha', field: 'fecha_movimiento', align: 'left', sortable: true },
  { name: 'activos', label: 'N° Activos', field: row => row.activos?.length, align: 'left' },
  { name: 'usuario', label: 'Realizado por', field: row => row.usuario?.nombre, align: 'left' },
  { name: 'receptor', label: 'Entrega a', field: row => row.receptor?.nombre, align: 'left' },
  { name: 'autorizado_por', label: 'Autorizado por', field: row => row.autorizado_por?.nombre, align: 'left' },
  { name: 'estado', label: 'Esado', field: 'estado', align: 'left'},
  { name: 'pdf', label: 'PDF', field: 'pdf', align: 'center' },
  { name: 'actions', label: 'Acciones', align: 'center' }
]
const detalleDialog = ref(false)
const movimientoDetalle = ref(null)
const observaciones = ref('')
const accionDialog = ref(false)
const accionTipo = ref('')
const movimientoAccion = ref(null)

const onSearchInput =()=>{
  if(searchTimeout.value) clearTimeout(searchTimeout)
  searchTimeout.value=setTimeout(()=>{
    loadData()
  }, 1200)
}

const loadData = async () => {
  loading.value = true
  try {
    const params = {
      search: search.value,
      fecha_desde: fechaDesde.value,
      fecha_hasta: fechaHasta.value,
      page: pagination.value.page,
      per_page: pagination.value.rowsPerPage,
      sort_by: pagination.value.sortBy,
      desc: pagination.value.descending,
      estado: tipo?.value?.value
    }
    if (movimientoService.getMovimientos) {
      const response = await movimientoService.getMovimientos(params)
      movimientos.value = response.data || response
      pagination.value.rowsNumber = response.pagination?.rowsNumber || (response.data ? response.data.length : 0)
    } else {
      movimientos.value = []
      pagination.value.rowsNumber = 0
    }
  } catch (error) {
    console.error(error)
    $q.notify({ type: 'negative', message: 'Error al cargar movimientos' })
  } finally {
    loading.value = false
  }
}

const onRequest = (props) => {
  const { page, rowsPerPage, sortBy, descending } = props.pagination
  pagination.value = { ...pagination.value, page, rowsPerPage, sortBy, descending }
  loadData()
}

const verDetalle = (row) => {
  movimientoDetalle.value = row
  detalleDialog.value = true
}

const descargarPDF = async (movimientoId) => {
  try {
    const blob = await movimientoService.descargarPDFEntrega(movimientoId)
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `movimiento_${movimientoId}.pdf`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  } catch (error) {
    console.error(error)
    $q.notify({ type: 'negative', message: 'No se pudo descargar el PDF' })
  }
}

const mostrarAccionAutorizador = (row) => {
  return row.estado === 'pendiente' && row.autorizado_por?.id === auth.user?.id
}
const mostrarAccionReceptor = (row) => {
  if(auth.isAdmin){
    return row.estado === 'en_entrega'
  }
  return row.estado === 'en_entrega' && row.receptor?.id === auth.user?.id
}

const abrirDialogAccion = (tipo, row) => {
  accionTipo.value = tipo
  movimientoAccion.value = row
  observaciones.value = ''
  accionDialog.value = true
}

const ejecutarAccion = async () => {
  try {
    //let res
    if (accionTipo.value === 'autorizar') {
      await movimientoService.autorizarEntrega(movimientoAccion.value.id)
    } else if (accionTipo.value === 'rechazar_autorizador') {
      await movimientoService.rechazarMovimiento(movimientoAccion.value.id, { observaciones: observaciones.value })
    } else if (accionTipo.value === 'aceptar_receptor') {
      await movimientoService.aceptarEntrega(movimientoAccion.value.id, { observaciones: observaciones.value })
    } else if (accionTipo.value === 'rechazar_receptor') {
      await movimientoService.rechazarMovimiento(movimientoAccion.value.id, { observaciones: observaciones.value })
    }
    //console.log(res)
    $q.notify({ type: 'positive', message: 'Acción realizada correctamente' })
    accionDialog.value = false
    loadData()
  } catch (error) {
    console.error(error)
    $q.notify({ type: 'negative', message: 'Error al realizar la acción' })
  }
}

onMounted(loadData)
</script> 