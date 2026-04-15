<template>
  <q-dialog :model-value="show" @update:model-value="$emit('update:show', $event)" persistent maximized>
    <q-card class="column">
      <q-card-section class="bg-primary text-white">
        <div class="row items-center">
          <div class="text-h6">{{ paso === 'preview' ? 'Vista Previa de Entrega' : 'Configurar Entrega' }}</div>
          <q-space />
          <q-btn icon="close" flat round dense @click="$emit('update:show', false)" class="text-white" />
        </div>
      </q-card-section>

      <q-card-section class="q-pa-md col scroll">
        <div v-if="paso === 'config'" class="q-gutter-y-lg">
          <q-select
            v-model="oficinaUsuarioSeleccionada"
            :options="usuarioEntrega?.oficinas || []"
            option-label="nombre"
            label="Seleccionar Oficina de Origen"
            outlined
            dense
            emit-value
            map-options
            class="q-mb-md"
            :rules="[val => !!val || 'Debe seleccionar una oficina de origen']"
          />
          <q-card flat bordered class="q-pa-lg">
            <div class="text-h6 q-mb-md text-primary">Información del Receptor</div>
            <div class="row q-col-gutter-md">
              <div class="col-12 col-md-6">
                <q-input
                  v-model="dniSearch"
                  label="Buscar por DNI"
                  outlined
                  dense
                  @keyup.enter="buscarReceptor"
                >
                  <template v-slot:append>
                    <q-btn
                      round
                      dense
                      flat
                      icon="search"
                      @click="buscarReceptor"
                      color="primary"
                    />
                  </template>
                </q-input>
              </div>
              <div class="col-12 col-md-6" v-if="receptor">
                <q-input
                  v-model="receptor.nombre"
                  label="Nombre del Receptor"
                  outlined
                  dense
                  readonly
                />
              </div>
              <div class="col-12 col-md-6" v-if="receptor">
                <q-select
                  v-model="receptor.oficinaSeleccionada"
                  :options="receptor.oficinas"
                  option-label="nombre"
                  label="Seleccionar Oficina"
                  outlined
                  dense
                  emit-value
                  map-options
                  @update:model-value="cargarUbicaciones($event.id)"
                />
              </div>

              <!-- Mostrar la entidad de la oficina seleccionada -->
              <div class="col-12 col-md-6" v-if="receptor?.oficinaSeleccionada">
                <q-input
                  :model-value="receptor.oficinaSeleccionada.entidad"
                  label="Unidades de Alta Dirección/Entidad"
                  outlined
                  dense
                  readonly
                />
              </div>
            </div>
          </q-card>

          <!-- Configuración de Entrega -->
          <q-card flat bordered class="q-pa-lg">
            <div class="text-h6 q-mb-md text-primary">Configuración de Entrega</div>
            <div class="row q-col-gutter-md">
              <div class="col-12 col-md-6">
                <q-checkbox
                  v-model="cambiarUbicacion"
                  label="Cambiar Ubicación"
                  color="primary"
                />
              </div>
              <div class="col-12 col-md-6" v-if="cambiarUbicacion">
                <q-select
                  v-model="ubicacion"
                  :options="ubicaciones"
                  option-label="label"
                  option-value="value"
                  label="Nueva area"
                  outlined
                  dense
                  :rules="[val => !cambiarUbicacion || !!val || 'La ubicación es requerida']"
                />
              </div>
              <div class="col-12">
                <q-input
                  v-model="observaciones"
                  label="Observaciones Generales"
                  type="textarea"
                  outlined
                  dense
                  rows="3"
                  placeholder="Ingrese observaciones generales para toda la entrega..."
                />
              </div>
            </div>
          </q-card>

          <q-card flat bordered class="q-pa-lg">
            <div class="text-h6 q-mb-md text-primary">Activos a Entregar</div>
            <div class="q-gutter-y-md">
              <div v-for="(activo) in activosFiltrados" :key="activo.id" class="q-pa-md rounded-borders border-left-primary">
                <div class="row q-col-gutter-md">
                  <!-- Información del Activo -->
                  <div class="col-12 col-lg-8">
                    <div class="row q-col-gutter-sm">
                      <div class="col-12 col-md-6">
                        <div><span class="text-weight-bold text-primary">Activo: </span>{{ activo.catalogo?.denominacion }}</div>
                        <div><span class="text-weight-bold text-primary">N° serie: </span>{{ activo.numero_serie }}</div>
                      </div>
                      <div class="col-12 col-md-6">
                        <q-input
                          v-model="observacionesActivos[activo.id]"
                          :label="`Observaciones para ${activo.nombre}`"
                          type="textarea"
                          outlined
                          dense
                          rows="2"
                          placeholder="Ingrese observaciones específicas para este activo..."
                        />
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </q-card>
        </div>

        <div v-if="paso === 'preview'" class="row q-col-gutter-md">
          <div class="col-12">
            <q-card flat bordered class="q-pa-md">
              <div class="text-subtitle1 q-mb-md">Resumen de la Entrega</div>
              <div class="row q-col-gutter-md">
                <div class="col-12 col-md-6">
                  <div class="text-weight-bold q-mb-sm">Información del Usuario que Entrega:</div>
                  <div class="q-gutter-y-sm">
                    <div><strong>Nombre:</strong> {{ usuarioEntrega?.nombre }}</div>
                    <div><strong>DNI:</strong> {{ usuarioEntrega?.dni }}</div>
                    <div v-if="oficinaUsuarioSeleccionada">
                      <strong>Unidades de Alta Dirección/Entidad:</strong> {{ oficinaUsuarioSeleccionada.entidad }}
                    </div>
                    <div v-if="oficinaUsuarioSeleccionada">
                      <strong>Oficina:</strong> {{ oficinaUsuarioSeleccionada.nombre }}
                    </div>
                    <!-- <div><strong>Unidades de Alta Dirección/Entidad:</strong> {{ usuarioEntrega?.entidad }}</div> -->
                    <!-- <div><strong>Oficina:</strong> {{ usuarioEntrega?.oficina }}</div> -->
                  </div>
                </div>

                <div class="col-12 col-md-6">
                  <div class="text-weight-bold q-mb-sm">Información del Receptor:</div>
                  <div class="q-gutter-y-sm">
                    <div><strong>Nombre:</strong> {{ receptor.nombre }}</div>
                    <div><strong>DNI:</strong> {{ receptor.dni }}</div>
                    <div><strong>Unidades de Alta Dirección/Entidad:</strong> {{ receptor.oficinaSeleccionada?.entidad }}</div>
                    <div><strong>Oficina:</strong> {{ receptor.oficinaSeleccionada?.nombre }}</div>
                  </div>
                </div>

                <div class="col-12">
                  <div class="text-weight-bold q-mb-sm">Información de Ubicación:</div>
                  <div class="q-gutter-y-sm">
                    <div v-if="cambiarUbicacion">
                      <strong>Nueva Ubicación:</strong> {{ ubicacion?.label }}
                    </div>
                    <div v-else>
                      <strong>Mantener ubicación actual</strong>
                      <div class="text-caption text-grey">
                        Nota: El receptor debe pertenecer al mismo departamento que el usuario que entrega
                      </div>
                    </div>
                  </div>
                </div>

                <div class="col-12">
                  <div class="text-weight-bold q-mb-sm">Activos a Entregar:</div>
                  <q-table
                    :rows="activosFiltrados"
                    :columns="[
                      { name: 'marca', label: 'Marca', field: 'marca', align: 'left'},
                      { name: 'modelo', label: 'Modelo', field: 'modelo', align: 'left' },
                      { name: 'numero_serie', label: 'N° Serie', field: 'numero_serie', align: 'left' },
                      { name: 'denominacion', label: 'Denominación', field: row => row.catalogo?.denominacion, align: 'left' },
                      { name: 'oficina', label: 'Oficina', field: row => row.area?.oficina?.denominacion, align: 'left' },
                      { name: 'area', label: 'Area', field: row => `${row.area?.edificio || ''} - ${row.area?.piso || ''} - ${row.area?.aula || ''}`, align: 'left' },
                      { name: 'estado', label: 'Estado', field: 'estado', align: 'center' },
                      { name: 'observaciones', label: 'Observaciones', field: row => observacionesActivos[row.id] || 'Sin observaciones', align: 'left' }
                    ]"
                    row-key="id"
                    dense
                    flat
                    bordered
                  >
                    <template v-slot:body-cell-estado="props">
                      <q-td :props="props">
                        <q-badge :color="getEstadoColor(props.row.estado)">
                          {{ props.row.estado_display }}
                        </q-badge>
                      </q-td>
                    </template>
                    <template v-slot:body-cell-observaciones="props">
                      <q-td :props="props">
                        <div class="text-caption">
                          {{ observacionesActivos[props.row.id] || 'Sin observaciones' }}
                        </div>
                      </q-td>
                    </template>
                  </q-table>
                </div>

                <div class="col-12" v-if="observaciones">
                  <div class="text-weight-bold q-mb-sm">Observaciones Generales:</div>
                  <div>{{ observaciones }}</div>
                </div>
              </div>
            </q-card>
          </div>
        </div>

        <div v-if="paso === 'pdf'" class="row q-col-gutter-md">
          <div class="col-12">
            <q-card flat bordered class="q-pa-md">
              <div class="text-subtitle1 q-mb-md">Documento de Entrega (PDF)</div>
              <div class="q-mb-md">
                <q-btn
                  v-if="pdfUrl"
                  color="primary"
                  icon="download"
                  label="Descargar PDF"
                  :href="pdfUrl"
                  target="_blank"
                  download
                  class="q-mr-md"
                />
              </div>
              <div v-if="pdfUrl">
                <iframe :src="pdfUrl" width="100%" height="600px" style="border: none;"></iframe>
              </div>
              <div v-else class="text-grey">Cargando documento...</div>
            </q-card>
          </div>
        </div>
      </q-card-section>

      <q-card-actions align="right" class="bg-grey-1 q-pa-md">
        <template v-if="paso === 'config'">
          <q-btn
            flat
            label="Cancelar"
            color="grey-7"
            @click="$emit('update:show', false)"
            class="q-mr-sm"
          />
          <q-btn
            label="Vista Previa"
            color="primary"
            @click="mostrarVistaPrevia"
            :disable="!receptor"
          />
        </template>

        <template v-if="paso === 'preview'">
          <q-btn
            flat
            label="Volver"
            color="grey-7"
            @click="paso = 'config'"
            class="q-mr-sm"
          />
          <q-btn
            label="Confirmar Entrega"
            color="primary"
            @click="guardarEntrega"
            :loading="loading"
          />
        </template>
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { ref, defineProps, defineEmits, onMounted, computed, watch } from 'vue'
import { useQuasar } from 'quasar'
import { authService } from '../services/authService'
import { activoService } from 'src/services/activoService'
import { areaService } from 'src/services/areaService'

const oficinaUsuarioSeleccionada = ref(null)
const activosFiltrados = computed(() => {
  if (!oficinaUsuarioSeleccionada.value) return []
  return props.activos.filter(activo => {
    return activo.area?.oficina?.id === oficinaUsuarioSeleccionada.value.id
  })
})
const props = defineProps({
  show: {
    type: Boolean,
    required: true
  },
  activos: {
    type: Array,
    required: true
  }
})

const emit = defineEmits(['update:show', 'guardar', watch])
const $q = useQuasar()
const loading = ref(false)
const dniSearch = ref('')
const receptor = ref(null)
const cambiarUbicacion = ref(false)
const ubicacion = ref(null)
const ubicaciones = ref([])
const observaciones = ref('')
const observacionesActivos = ref({})
const paso = ref('config')
const usuarioEntrega = ref(null)
const pdfUrl = ref(null)

const cargarUsuarioEntrega = async () => {
  try {
    const response = await authService.getCurrentUser()
    if (!response.oficinas || response.oficinas.length === 0) {
      throw new Error('Usuario no tiene oficinas asignadas')
    }
    usuarioEntrega.value = {
      id: response.id,
      nombre: response.name,
      dni: response.dni,
      oficinas: response.oficinas.map(oficina => ({
        id: oficina.id,
        nombre: oficina.denominacion,
        entidad: oficina.entidad?.denominacion
      })),
    }
    if (usuarioEntrega.value.oficinas.length === 1) {
      oficinaUsuarioSeleccionada.value = usuarioEntrega.value.oficinas[0]
      cargarUbicaciones(oficinaUsuarioSeleccionada.value.id)
    }
  } catch (error) {
    console.error('Error al cargar usuario:', error)
    $q.notify({
      type: 'negative',
      message: 'Error al cargar información del usuario'
    })
  }
}

const buscarReceptor = async () => {
  if (!dniSearch.value) {
    $q.notify({
      type: 'warning',
      message: 'Ingrese un DNI para buscar'
    })
    return
  }

  loading.value = true
  try {
    const response = await authService.getUsuarios(dniSearch.value)
    console.log(response)
    if (response) {
      receptor.value = {
        id: response[0].id,
        nombre: response[0].name,
        dni: response[0].dni,
        oficinas: response[0].oficinas.map(oficina => ({
          id: oficina.id,
          nombre: oficina.denominacion,
          entidad: oficina.entidad?.denominacion
        })),
        oficinaSeleccionada: null
      }
    } else {
      $q.notify({
        type: 'negative',
        message: 'No se encontró ningún usuario con ese DNI'
      })
    }
  } catch (error) {
    console.error('Error al buscar usuario:', error)
    $q.notify({
      type: 'negative',
      message: 'Error al buscar usuario'
    })
  } finally {
    loading.value = false
  }
}

// Cargar ubicaciones
const cargarUbicaciones = async (newDepartamentoId) => {
  try {
    const response = await areaService.getAreas({ oficinas: newDepartamentoId })
    ubicaciones.value = response.data.map(ubic => ({
      label: `${ubic.edificio} - ${ubic.piso} - ${ubic.aula}`,
      value: ubic.id
    }))
  } catch (error) {
    console.error('Error al cargar areas:', error)
    $q.notify({
      type: 'negative',
      message: 'Error al cargar area'
    })
  }
}

// Obtener color según estado
const getEstadoColor = (estado) => {
  const colores = {
    'pendiente': 'warning',
    'aprobada': 'info',
    'rechazada': 'negative',
    'completada': 'positive',
    'cancelada': 'grey'
  }
  return colores[estado] || 'primary'
}

// Validar entrega
const validarEntrega = () => {
  if (!oficinaUsuarioSeleccionada.value) {
    $q.notify({ type: 'warning', message: 'Seleccione una oficina de origen' })
    return false
  }
  // No permitir entregar a uno mismo
  if (receptor.value && usuarioEntrega.value && receptor.value.dni === usuarioEntrega.value.dni) {
    $q.notify({
      type: 'negative',
      message: 'No puede entregar activos a sí mismo'
    })
    return false
  }
  //const receptorTieneAcceso = receptor.value.oficinas.some(
  //  oficina => oficina.id === oficinaUsuarioSeleccionada.value.id
  //)

  if (!cambiarUbicacion.value && receptor.value.oficinaSeleccionada?.id !== oficinaUsuarioSeleccionada.value?.id) {
  $q.notify({
    type: 'negative',
    message: 'El receptor debe pertenecer al mismo departamento que el usuario que entrega'
  })
  return false
}

  // No permitir entregar activos con movimiento pendiente o entregado
  const activosInvalidos = props.activos.filter(activo => {
    if (Array.isArray(activo.movimientos_activos) && activo.movimientos_activos.length > 0) {
      return activo.movimientos_activos.some(mov =>
        mov.estado === 'pendiente' || mov.estado === 'entregado'
      )
    }
    return false
  })
  if (activosInvalidos.length > 0) {
    $q.notify({
      type: 'negative',
      message: 'No se puede entregar activos con movimiento pendiente o entregado'
    })
    return false
  }
  if (cambiarUbicacion.value && !ubicacion.value) {
    $q.notify({ type: 'warning', message: 'Seleccione una ubicación destino' })
    return false
  }
  if (!receptor.value?.oficinaSeleccionada) {
    $q.notify({ type: 'warning', message: 'Seleccione una oficina del receptor' });
    return false;
  }
  return true
}

// Mostrar vista previa
const mostrarVistaPrevia = () => {
  if (!receptor.value) {
    $q.notify({
      type: 'warning',
      message: 'Debe seleccionar un receptor'
    })
    return
  }
  if (activosFiltrados.value.length === 0) {
    $q.notify({
      type: 'warning',
      message: 'No hay activos para entregar en la oficina seleccionada'
    })
    return
  }
  if (!validarEntrega()) {
    return
  }
  if (cambiarUbicacion.value && !ubicacion.value) {
    $q.notify({
      type: 'warning',
      message: 'Debe seleccionar una ubicación'
    })
    return
  }
  if (!cambiarUbicacion.value && receptor.value.oficina !== usuarioEntrega.value.oficina) {
    $q.notify({
      type: 'negative',
      message: 'El receptor debe pertenecer al mismo departamento que el usuario que entrega'
    })
    return false
  }
  paso.value = 'preview'
}

// Mostrar y descargar el documento PDF de la entrega
const mostrarDocumentoPDF = async (movimientoId) => {
  try {
    const blob = await activoService.descargarPDFEntrega(movimientoId)
    pdfUrl.value = URL.createObjectURL(blob)
    paso.value = 'pdf'
  } catch (error) {
    console.error(error)
    $q.notify({
      type: 'negative',
      message: 'No se pudo obtener el documento PDF de la entrega'
    })
  }
}

const guardarEntrega = async () => {
  if (!receptor.value) {
    $q.notify({
      type: 'warning',
      message: 'Debe seleccionar un receptor'
    })
    return
  }
  if (activosFiltrados.value.length === 0) {
    $q.notify({
      type: 'warning',
      message: 'No hay activos para entregar en la oficina seleccionada'
    })
    return
  }
  if (!validarEntrega()) {
    return
  }
  if (cambiarUbicacion.value && !ubicacion.value) {
    $q.notify({
      type: 'warning',
      message: 'Debe seleccionar una ubicación'
    })
    return
  }
  loading.value = true
  try {
    const data = {
      receptor: {
        id: receptor.value.id,
        nombre: receptor.value.nombre,
        dni: receptor.value.dni,
        oficina: receptor.value.oficinaSeleccionada.nombre,
        entidad: oficinaUsuarioSeleccionada.value.entidad,
      },
      cambiarUbicacion: cambiarUbicacion.value,
      ubicacion: ubicacion.value,
      ubicacion_origen_id: receptor.value.oficinaSeleccionada.id,
      ubicacion_destino_id: receptor.value.oficinaSeleccionada.id,
      observaciones: observaciones.value,
      observacionesActivos: observacionesActivos.value,
      activos: activosFiltrados.value.map(activo => ({
        ...activo,
        observaciones: observacionesActivos.value[activo.id] || ''
      })),
      //activos: props.activos.map(activo => ({
      //  ...activo,
      //  observaciones: observacionesActivos.value[activo.id] || ''
      //})),
      usuario: {
        id: usuarioEntrega.value.id,
        dni: usuarioEntrega.value.dni,
        nombre: usuarioEntrega.value.nombre,
        oficina: oficinaUsuarioSeleccionada.value.nombre,
        entidad: oficinaUsuarioSeleccionada.value.entidad
      }
    }
    const response = await activoService.createEntrega(data)
    $q.notify({
      type: 'positive',
      message: 'Entrega realizada con éxito'
    })
    await mostrarDocumentoPDF(response.id)
    emit('guardar', data)
  } catch (error) {
    const messages = error.response.data?.errors
    $q.notify({
      type: 'negative',
      message: messages ? messages['activos.0'] : 'Error al guardar la entrega'
    })
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  cargarUsuarioEntrega()
  //props.activos.forEach(activo => {
  //  observacionesActivos.value[activo.id] = ''
  //})
  watch(oficinaUsuarioSeleccionada, (nuevaOficina) => {
    if (nuevaOficina) {
      activosFiltrados.value.forEach(activo => {
        observacionesActivos.value[activo.id] = activo.observaciones || ''
      })
      cargarUbicaciones(nuevaOficina.id)
    }
  }, { immediate: true });
})
</script>

<style scoped>
.border-left-primary {
  border-left: 4px solid var(--q-primary);
}

.q-card {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.q-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  transition: box-shadow 0.3s ease;
}

.text-primary {
  color: var(--q-primary) !important;
}

.bg-grey-1 {
  background-color: #f5f5f5;
}

.rounded-borders {
  border-radius: 8px;
}
</style>