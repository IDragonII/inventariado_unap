<template>
  <q-page>
    <q-card class="q-ma-md">
      <q-toolbar class="shadow-2">
        <q-toolbar-title class="text-bold">
          GESTION DE ACTIVOS
        </q-toolbar-title>
        <q-btn
          @click="openDialog('create')"
          icon="add"
          color="primary"
          :label="$q.screen.width <= $q.screen.sizes.md ? '' : 'AGREGAR ACTIVO'"
          class="q-ml-sm"
          rounded dense
          :class="$q.screen.width > $q.screen.sizes.md ? 'q-px-md' : ''"
        />
        <q-btn
          icon="file_upload"
          color="primary"
          :label="$q.screen.width <= $q.screen.sizes.md ? '' : 'Exportar Excel'"
          class="q-ml-sm"
          rounded dense
          :class="$q.screen.width > $q.screen.sizes.md ? 'q-px-md' : ''"
          @click="exportar"
        />
        <q-btn
          icon="file_download"
          color="positive"
          :label="$q.screen.width <= $q.screen.sizes.md ? '' : 'Importar'"
          class="q-ml-sm"
          rounded dense
          :class="$q.screen.width > $q.screen.sizes.md ? 'q-px-md' : ''"
          @click="triggerFileInput"
        />
        <input
          ref="fileInputRef"
          type="file"
          accept=".xlsx"
          style="display: none"
          @change="handleFileImport"
        />
        <q-btn
          icon="description"
          color="primary"
          :label="$q.screen.width <= $q.screen.sizes.md ? '' : 'Declaración de uso'"
          class="q-ml-sm"
          rounded dense
          :class="$q.screen.width > $q.screen.sizes.md ? 'q-px-md' : ''"
        />
      </q-toolbar>
    </q-card>

    <q-card class="q-ma-md">
      <SearchActivo
        v-model:search="searchFilter"
        v-model:oficina="oficinaFilter"
        v-model:ubicacion="ubicacionFilter"
        :select="seleccionados"
        @update:user="buscarUsuario"
        @update:movimiento="realizarMovimiento"
        :oficina-options="oficinaOptions"
        :ubicacion-options="ubicacionOptions"
      />
    </q-card>

    <q-card class="q-ma-md">
      <TableDynamic
        v-model:selectedRows="seleccionados"
        :columns="columns"
        :row="activos"
        :loading="loading"
        row-key="id"
        show-selection
        :pagination="pagination"
        @update:pagination="onPagination"
      >
        <template #body-cell-estado="props">
          <q-td :props="props">
            <q-badge :color="getStatusColor(props.row.estado)">
              {{ props.row.estado_display }}
            </q-badge>
          </q-td>
        </template>
        <template #body-cell-condicion="props">
          <q-td :props="props">
            <q-badge :color="getCondicionColor(props.row.condicion)">
              {{ props.row.condicion_display }}
            </q-badge>
          </q-td>
        </template>
        <template #body-cell-actions="props">
          <q-td :props="props" class="q-gutter-sm">
            <q-btn flat dense round icon="visibility" color="info" @click="openDialog('view', props.row)">
              <q-tooltip>Ver detalles</q-tooltip>
            </q-btn>
            <q-btn
              v-if="auth.isAdmin || auth.isSupervisor || auth.isResponsable || auth.isConsulta"
              flat dense round icon="edit" color="primary"
              :loading="editingItemId === props.row.id"
              :disable="editingItemId === props.row.id"
              @click="openDialog('edit', props.row)"
            >
              <q-tooltip>Editar</q-tooltip>
            </q-btn>
            <q-btn v-if="auth.isAdmin" flat dense round icon="delete" color="negative" @click="confirmDelete(props.row)">
              <q-tooltip>Eliminar</q-tooltip>
            </q-btn>
            <q-btn v-if="auth.isAdmin" flat dense round icon="history" color="secondary" @click="viewHistory(props.row)">
              <q-tooltip>Historial</q-tooltip>
            </q-btn>
            <q-btn
              v-if="auth.isAdmin || auth.isSupervisor || auth.isResponsable || auth.isConsulta"
              flat dense round icon="check_circle" color="info"
              @click="enableItem(props.row)"
            >
              <q-tooltip>Habilitar</q-tooltip>
            </q-btn>
          </q-td>
        </template>
      </TableDynamic>
    </q-card>

    <DialogModal v-model:show="mode.show" :mode="mode.mode" :title="mode.title">
      <DynamicForm
        :fields="modernFormFields"
        v-model="formData"
        :readonly="mode.mode === 'view'"
        :mode="mode.mode"
        :loading="mode.loading"
        @submit="handleSubmit"
        @select-change="handleSelectChange"
        @cancel="handleClose"
      />
    </DialogModal>

    <q-dialog v-model="dialogDelete">
      <q-card>
        <q-card-section class="row items-center">
          <q-avatar icon="warning" color="negative" text-color="white"/>
          <span class="q-ml-sm">¿Está seguro de eliminar este activo?</span>
        </q-card-section>
        <q-card-section>
          <q-btn flat label="Cancelar" color="primary" v-close-popup/>
          <q-btn flat label="Eliminar" color="negative" @click="delteActivo"/>
        </q-card-section>
      </q-card>
    </q-dialog>

    <q-dialog v-model="dialogEnable">
      <q-card>
        <q-card-section class="row items-center">
          <q-avatar icon="warning" color="negative" text-color="white"/>
          <span class="q-ml-sm">¿Desea habilitar activo para realizar nuevo inventariado?</span>
        </q-card-section>
        <q-card-section>
          <q-btn flat label="Cancelar" color="negative" v-close-popup/>
          <q-btn flat label="habilitar" color="positive" @click="enableItemConfirmation"/>
        </q-card-section>
      </q-card>
    </q-dialog>

    <EntregaModal
      v-model:show="entregaDialog"
      :activos="select"
    />
  </q-page>
</template>

<script setup>
import { useQuasar } from 'quasar'
import TableDynamic from 'src/components/TableDynamic.vue'
import SearchActivo from 'src/components/users/searchActivo.vue'
import { activoService } from 'src/services/activoService'
import { onMounted, ref, watch } from 'vue'
import { useAuthStore } from 'src/stores/auth-store'
import { useExportStore } from 'src/stores/export-store'
import { oficinaService } from 'src/services/oficinaService'
import { areaService } from 'src/services/areaService'
import { authService } from 'src/services/authService'
import DialogModal from 'src/components/DialogModal.vue'
import DynamicForm from 'src/components/DynamicForm.vue'
import { categoriaService } from 'src/services/categoriaService'
import EntregaModal from 'src/components/EntregaModal.vue'

const auth        = useAuthStore()
const exportStore = useExportStore()
const $q          = useQuasar()

const select        = ref([])  // Para SearchActivo (no usado activamente)
const seleccionados = ref([])  // Para la tabla de selectiones
const entregaDialog = ref(false)
const loading       = ref(false)
const activos       = ref([])
const editingItemId = ref(null)
const formData      = ref([])

const oficinaOptions  = ref([])
const ubicacionOptions = ref([])
const searchFilter    = ref('')
const oficinaFilter   = ref(null)
const ubicacionFilter = ref(null)
const userFilter      = ref(null)
const selectRow       = ref(null)
const dialogDelete    = ref(false)
const enableRow       = ref(null)
const dialogEnable    = ref(null)
const userFound = ref(false)

const mode = ref({
  mode: 'create',
  title: 'Agregar Activo',
  show: false,
  loading: false
})

const loadingAll = ref({
  create: false,
  edit: false,
  delete: false,
  export: false
})

// ─── Dialog ──────────────────────────────────────────────────────────────────

const openDialog = async (data, row) => {
  formData.value = []
  loadingAll.value.create = true
  mode.value.mode = data

  const categorias = await categoriaService.getCategorias()
  const categoriasFormateadas = categorias.data.map(cat => ({
    value: cat.id,
    label: cat.denominacion
  }))

  if (data === 'create' || data === 'edit') {
    modernFormFields.value.find(f => f.name === 'denominacion').options = categoriasFormateadas
    modernFormFields.value.find(f => f.name === 'oficina').options = oficinaOptions
    modernFormFields.value.find(f => f.name === 'oficina').disabled = false
  }

  if (row) {
    formData.value = {
      ...row,
      estado: { label: row.estado_display, value: row.estado },
      condicion: { label: row.condicion_display, value: row.condicion },
      oficina: row.area?.oficina
        ? { label: row.area.oficina.denominacion, value: row.area.oficina.id }
        : null,
      area: row.area
        ? { label: row.area.aula, value: row.area.id }
        : null,
    }
  }

  mode.value.show = true
}

const handleSubmit = async () => {
  mode.value.loading = true
  try {
    const newFormData = {
      ...formData.value,
      area_id:      formData.value.area.value,
      condicion:    formData.value.condicion.value,
      denominacion: formData.value.denominacion.label,
      estado:       formData.value.estado.value,
      piso:         formData.value.piso.value,
    }

    if (mode.value.mode === 'create') {
      newFormData.fecha_adquisicion = new Date().toISOString().slice(0, 10)
      await activoService.createActivo(newFormData)
      $q.notify({ color: 'positive', message: 'Activo creado exitosamente', position: 'top', icon: 'check_circle' })
    } else {
      await activoService.updateActivo(formData.value.id, newFormData)
      $q.notify({ color: 'positive', message: 'Activo actualizado exitosamente', position: 'top', icon: 'check_circle' })
    }
  } catch (error) {
    console.log(error)
    const message = error.response?.data?.errors?.codigo || 'Error al realizar acción'
    $q.notify({ color: 'negative', message, position: 'top', icon: 'error' })
  } finally {
    mode.value.loading = false
    handleClose()
  }
}

const handleClose = () => {
  formData.value = []
  mode.value = { mode: '', title: '', show: false, loading: false }
  loadingAll.value = { create: false, edit: false, delete: false, export: false }
}

// ─── Delete ───────────────────────────────────────────────────────────────────

const confirmDelete = (row) => {
  if (!(auth.isAdmin || auth.isSupervisor || auth.isResponsable)) return
  selectRow.value = row.id
  dialogDelete.value = true
}

const delteActivo = async () => {
  try {
    await activoService.deleteActivo(selectRow.value)
    $q.notify({ color: 'positive', message: 'Activo eliminado correctamente', icon: 'check', position: 'top' })
    loadData()
  } catch (error) {
    console.error(error)
    $q.notify({ color: 'negative', icon: 'report_problem', message: 'Error al eliminar activo', position: 'top' })
  } finally {
    dialogDelete.value = false
  }
}

// ─── Habilitar ────────────────────────────────────────────────────────────────

const enableItem = (row) => {
  enableRow.value = row.id
  dialogEnable.value = true
}

const enableItemConfirmation = async () => {
  try {
    await activoService.habilitarActivo({ id: enableRow.value })
    $q.notify({ color: 'positive', message: 'Activo habilitado', icon: 'check', position: 'top' })
  } catch (error) {
    console.error(error)
    $q.notify({ color: 'negative', icon: 'report_problem', message: 'Error al habilitar activo', position: 'top' })
  } finally {
    dialogEnable.value = false
  }
}

// ─── Buscar usuario ───────────────────────────────────────────────────────────

const buscarUsuario = async (val) => {
  if (!val) {
    userFilter.value = null
    userFound.value = false
    loadData()
    return
  }
  try {
    const response = await authService.getUsuarios(val)
    const usuarios = Array.isArray(response) ? response : response?.data ?? []

    if (usuarios.length > 0) {
      userFilter.value = usuarios[0].id
      userFound.value = true
      select.value = []
      $q.notify({ type: 'positive', message: `Usuario encontrado: ${usuarios[0].name}`, position: 'top' })
    } else {
      userFilter.value = null
      userFound.value = false
      select.value = []
      $q.notify({ type: 'negative', message: 'No se encontró usuario con ese DNI' })
    }
  } catch (error) {
    userFilter.value = null
    select.value = []
    userFound.value = false
    $q.notify({ type: 'negative', message: 'Error al buscar usuario' })
  }
  loadData()
}

// ─── Exportar (segundo plano) ─────────────────────────────────────────────────

const exportar = async () => {
  // Verificar qué hay en seleccionados
  console.log('=== DEBUG EXPORT ===')
  console.log('seleccionados.value:', JSON.stringify(seleccionados.value))
  console.log('seleccionados.length:', seleccionados.value?.length)
  
  // Si hay filas seleccionadas, exportar solo esas
  let filtros
  if (seleccionados.value && seleccionados.value.length > 0) {
    const ids = seleccionados.value.map(r => r.id)
    console.log('IDs a exportar:', ids)
    filtros = { ids: ids }
    console.log('Enviando con IDs')
  } else {
    console.log('Enviando con filtros')
    filtros = {
      oficina_id:     oficinaFilter.value?.value ?? null,
      area_id:        ubicacionFilter.value?.value ?? null,
      search:         searchFilter.value || null,
      responsable_id:  userFilter.value ?? null,
    }
  }
  console.log('filtros:', JSON.stringify(filtros))

  // Registrar en campanita como "procesando"
  const localId = Date.now()
  const tieneSeleccion = seleccionados.value && seleccionados.value.length > 0
  exportStore.agregarExport({
    id:      localId,
    estado:  'procesando',
    mensaje: tieneSeleccion
      ? `Preparando Excel de ${seleccionados.value.length} activo(s) seleccionado(s)...`
      : `Preparando Excel de ${oficinaFilter.value?.label ?? 'todos los activos'}...`,
  })

  $q.notify({ type: 'info', message: 'Exportación iniciada en segundo plano', position: 'top' })

  try {
    const res = await activoService.iniciarExport(filtros);
    
    // Si res es undefined aquí, es que el Adaptador sigue sin retornar nada
    console.log('Respuesta final recibida:', res);

    const export_id = res.export_id || res.data?.export_id;

    if (!export_id) {
       throw new Error('No se pudo obtener el ID de exportación');
    }

    console.log('ID detectado:', export_id);

    // 3. Iniciar el Polling
    const intervalo = setInterval(async () => {
      try {
        const status = await activoService.statusExport(export_id);
        
        // Verificamos que status exista
        if (!status) return;

        if (status.estado === 'completado') {
          clearInterval(intervalo);
          exportStore.actualizarExport(localId, {
            estado: 'completado',
            mensaje: `Excel listo · ${new Date().toLocaleTimeString()}`,
            url: status.url,
            export_id: export_id,
          });
          $q.notify({
            type: 'positive',
            message: '¡Excel listo! Revisa las notificaciones',
            position: 'top',
            icon: 'download',
          });
        } else if (status.estado === 'fallido') {
          clearInterval(intervalo);
          exportStore.actualizarExport(localId, {
            estado: 'fallido',
            mensaje: status.mensaje || 'Error al generar el archivo',
          });
          $q.notify({ type: 'negative', message: 'Error en la exportación', position: 'top' });
        }
      } catch (pollError) {
        console.error('Error en polling:', pollError);
      }
    }, 5000);

  } catch (error) {
    console.error('ERROR CRITICO EXPORTAR:', error);
    exportStore.actualizarExport(localId, {
      estado: 'fallido',
      mensaje: 'Error de conexión o datos inválidos',
    });
    $q.notify({ type: 'negative', message: 'No se pudo iniciar la exportación', position: 'top' });
  }
}

// ─── Importar ─────────────────────────────────────────────────────────────────

const fileInputRef = ref(null)

const triggerFileInput = () => {
  if (fileInputRef.value) {
    fileInputRef.value.click()
  }
}

const handleFileImport = async (event) => {
  const file = event.target.files[0]
  if (!file) return
  
  try {
    const formData = new FormData()
    formData.append('archivo', file)
    
    const response = await activoService.importar(formData)
    
    if (response.success) {
      let msg = `Importación completada: ${response.data.creados} creados, ${response.data.actualizados} actualizados`
      if (response.data.errores && response.data.errores.length > 0) {
        msg += ` (${response.data.errores.length} errores)` + '\n\n' + response.data.errores.join('\n')
        console.error('Errores de importación:', response.data.errores)
      }
      $q.notify({
        type: response.data.errores?.length > 0 ? 'warning' : 'positive',
        message: msg,
        timeout: response.data.errores?.length > 0 ? 0 : 2000,
        actions: response.data.errores?.length > 0 ? [{ icon: 'close', color: 'white' }] : []
      })
      loadData()
    } else {
      $q.notify({
        type: 'negative',
        message: response.message || 'Error en la importación'
      })
    }
  } catch (error) {
    console.error('Error en importación:', error)
    $q.notify({
      type: 'negative',
      message: 'Error al importar el archivo'
    })
  }
  
  event.target.value = ''
}

// ─── Movimiento ───────────────────────────────────────────────────────────────

const realizarMovimiento = () => {
  entregaDialog.value = true
}

// ─── Tabla / Paginación ───────────────────────────────────────────────────────

const pagination = ref({
  sortBy: 'id',
  page: 1,
  rowsPerPage: 10,
  rowsNumber: 0,
  descending: false
})

function getStatusColor(estado) {
  if (estado === 'activo') return 'positive'
  if (estado === 'inactivo') return 'negative'
  return 'grey'
}

function getCondicionColor(condicion) {
  if (condicion === 'nueva') return 'grey'
  if (condicion === 'bueno') return 'primary'
  if (condicion === 'regular') return 'warning'
  if (condicion === 'malo') return 'negative'
  return 'grey'
}

const onPagination = (newPagination) => {
  pagination.value = { ...pagination.value, ...newPagination }
  loadData()
}

const columns = [
  { name: 'codigo',       label: 'Código',        field: 'codigo',                             align: 'left',   sortable: true },
  { name: 'item',         label: 'Item',           field: row => row.id_item ?? 'NULL',         align: 'left',   sortable: true },
  { name: 'denominacion', label: 'Denominación',   field: 'denominacion',                       align: 'left',   sortable: true },
  { name: 'Oficina',      label: 'Oficina',        field: row => row.area?.oficina?.denominacion, align: 'left' },
  { name: 'Área',         label: 'Área',           field: row => row.area?.aula,                align: 'left' },
  { name: 'numero_serie', label: 'Número Serie',   field: 'numero_serie',                       align: 'left' },
  { name: 'responsable',  label: 'Responsable',    field: row => row.responsable?.name,         align: 'left' },
  { name: 'dni',          label: 'DNI',            field: row => row.responsable?.dni,          align: 'left' },
  { name: 'estado',       label: 'Estado',         field: 'estado',                             align: 'center' },
  { name: 'condicion',    label: 'Condición',      field: 'condicion',                          align: 'center' },
  { name: 'actions',      label: 'Acciones',                                                    align: 'center' }
]

const loadData = async () => {
  loading.value = true
  try {
    const response = await activoService.getActivos({
      page:           pagination.value.page,
      per_page:       pagination.value.rowsPerPage,
      sort_by:        pagination.value.sortBy,
      desc:           pagination.value.descending,
      search:         searchFilter.value,
      oficina_id:     oficinaFilter.value?.value,
      area_id:        ubicacionFilter.value?.value,
      responsable_id: userFilter.value
    })
    activos.value    = response.data
    pagination.value = { ...response.pagination }
  } catch (error) {
    console.error(error)
    activos.value = []
    $q.notify({ color: 'negative', message: 'Error al cargar los datos' })
  } finally {
    loading.value = false
  }
}

// ─── Watchers ─────────────────────────────────────────────────────────────────

watch(searchFilter, () => { loadData() })

watch(oficinaFilter, async (val) => {
  if (val) {
    const ubicaciones = await areaService.getAreas({ oficina: val.value })
    ubicacionOptions.value = ubicaciones.data.map(u => ({ label: u.aula, value: u.id }))
    loadData()
  }
})

watch(ubicacionFilter, (val) => { if (val) loadData() })

// ─── Select change (form) ─────────────────────────────────────────────────────

const handleSelectChange = (fieldName, value) => {
  if (fieldName === 'oficina') handleOficinaChanges(value)
}

const handleOficinaChanges = async (oficina) => {
  const ubicacion = modernFormFields.value.find(f => f.name === 'area')
  if (ubicacion) {
    ubicacion.options  = []
    ubicacion.disabled = true
  }
  if (oficina?.value) {
    try {
      const areas = await areaService.getAreas({ oficina: oficina.value })
      ubicacion.options  = areas.data.map(ubi => ({ label: ubi.codigo + ' - ' + ubi.aula, value: ubi.id }))
      ubicacion.disabled = false
    } catch (error) {
      console.log(error)
    }
  }
}

// ─── Mounted ──────────────────────────────────────────────────────────────────

onMounted(async () => {
  loading.value = true
  if (auth.isConsulta) {
    const user = await authService.getCurrentUser()
    if (user?.oficinas?.length > 0) {
      oficinaOptions.value = user.oficinas.map(o => ({
        label: o.codigo + ' - ' + o.denominacion,
        value: o.id
      }))
      if (user.oficinas.length === 1) {
        oficinaFilter.value = oficinaOptions.value[0]
      }
    }
  }
  if (auth.isAdmin || auth.isSupervisor) {
    const oficinas = await oficinaService.getOficinas()
    oficinaOptions.value = oficinas.data.map(o => ({
      label: o.codigo + ' - ' + o.denominacion,
      value: o.id
    }))
  }
  loadData()
  loading.value = false
})

// ─── Form fields ──────────────────────────────────────────────────────────────

const modernFormFields = ref([
  { type: 'separator', label: 'Información Básica' },
  {
    name: 'codigo', type: 'text', label: 'Código del Activo', placeholder: 'ACT-001',
    rules: [
      val => !!val || 'El número de serie es requerido',
      val => (val.length === 10 || val.length === 12) || 'El número de serie debe tener exactamente 10 o 12 caracteres'
    ],
    minlength: 10, maxlength: 12
  },
  {
    name: 'denominacion', type: 'select', label: 'Denominación', placeholder: 'Seleccione una denominación',
    rules: [val => !!val || 'El campo es requerido'],
    options: [], prepend: 'category', useInput: true, fillInput: true,
    inputDebounce: 300, mapOptions: true, optionLabel: 'label', optionValue: 'value', clearable: true
  },
  { name: 'marca',        type: 'text', label: 'Marca',            placeholder: 'Marca del fabricante',      rules: [val => !!val || 'La marca es requerida'], prepend: 'branding_watermark' },
  { name: 'modelo',       type: 'text', label: 'Modelo',           placeholder: 'Modelo específico',         prepend: 'model_training', space: true },
  { name: 'color',        type: 'text', label: 'Color',            placeholder: 'Color específico',          prepend: 'model_training', space: true },
  { name: 'numero_serie', type: 'text', label: 'Número de Serie',  placeholder: 'Número de serie del fabricante', prepend: 'confirmation_number' },
  { name: 'dimension',    type: 'text', label: 'Dimensión',        placeholder: 'Dimensión del activo',      prepend: 'straighten' },
  { type: 'separator', label: 'Clasificación' },
  {
    name: 'piso', type: 'select', label: 'Piso', placeholder: 'Seleccione un Piso',
    rules: [val => !!val || 'El campo es requerido'],
    options: [
      { label: 'Sótano',          value: 'Sótano' },
      { label: 'Primer piso',     value: 'Primer piso' },
      { label: 'Segundo piso',    value: 'Segundo piso' },
      { label: 'Tercer piso',     value: 'Tercer piso' },
      { label: 'Cuarto piso',     value: 'Cuarto piso' },
      { label: 'Quinto piso',     value: 'Quinto piso' },
      { label: 'Sexto piso',      value: 'Sexto piso' },
      { label: 'Séptimo piso',    value: 'Séptimo piso' },
      { label: 'Octavo piso',     value: 'Octavo piso' },
      { label: 'Noveno piso',     value: 'Noveno piso' },
      { label: 'Décimo piso',     value: 'Décimo piso' },
      { label: 'Onceavo piso',    value: 'Onceavo piso' },
      { label: 'Doceavo piso',    value: 'Doceavo piso' },
      { label: 'Treceavo piso',   value: 'Treceavo piso' },
      { label: 'Catorceavo piso', value: 'Catorceavo piso' },
      { label: 'Quinceavo piso',  value: 'Quinceavo piso' },
      { label: 'Azotea',          value: 'Azotea' },
    ],
    prepend: 'stairs', autogrow: true, uppercase: true
  },
  {
    name: 'estado', type: 'select', label: 'Situación', placeholder: 'Seleccione el estado',
    rules: [val => !!val || 'El estado es requerido'],
    options: [{ label: 'En uso', value: 'activo' }, { label: 'En desuso', value: 'inactivo' }],
    prepend: 'check_circle'
  },
  {
    name: 'condicion', type: 'select', label: 'Estado', placeholder: 'Seleccione la condición',
    rules: [val => !!val || 'La condición es requerida'],
    options: [
      { label: 'Nuevo',   value: 'nuevo' },
      { label: 'Bueno',   value: 'bueno' },
      { label: 'Regular', value: 'regular' },
      { label: 'Malo',    value: 'malo' }
    ],
    prepend: 'grade'
  },
  { name: 'aula', type: 'text', label: 'Aula', placeholder: 'Ingrese el aula', rules: [val => !!val || 'El tipo es requerido'], prepend: 'check_circle' },
  { type: 'separator', label: 'Ubicación' },
  {
    name: 'oficina', type: 'select', label: 'Oficina', placeholder: 'Seleccione una oficina',
    rules: [val => !!val || 'La Oficina es requerida'],
    options: [], disabled: true, prepend: 'business',
    emitvalue: false, mapOptions: true, optionLabel: 'label', optionValue: 'value'
  },
  {
    name: 'area', type: 'select', label: 'Área', placeholder: 'Seleccione una Área',
    rules: [val => !!val || 'El campo es requerido'],
    options: [], disabled: true, prepend: 'location_on',
    emitvalue: false, mapOptions: true, optionLabel: 'label', optionValue: 'value'
  },
  { type: 'separator', label: 'Información Adicional' },
  {
    name: 'descripcion', type: 'textarea', label: 'Observación',
    placeholder: 'Observación detallada del activo...',
    rows: 3, autogrow: true, maxlength: 255, counter: true, prepend: 'description'
  }
])
</script>