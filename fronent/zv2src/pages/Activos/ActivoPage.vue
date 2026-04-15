<template>
    <q-page padding>
      <PermissionsAccess 
      :is-admin="auth.isAdmin" 
      :is-supervisor="auth.isSupervisor" 
      :is-responsable="auth.isResponsable" 
      :is-consulta="auth.isConsulta">
      <!-- Encabezado y filtros -->
      <div class="row q-mb-md">
        <div class="col-12">
          <q-card>
            <q-card-section>
              <div class="row items-center justify-between">
                <div class="text-h6">Gestion de Activos</div>
                <q-btn
                  v-if="auth.isAdmin || auth.isSupervisor || auth.isResponsable || auth.isConsulta"
                  color="primary"
                  icon="add"
                  label="Agregar Activo"
                  :loading="loadingOpenDialog"
                  :disable="loadingOpenDialog"
                  @click="openDialog()"
                />
              </div>
            </q-card-section>
          </q-card>
        </div>
      </div>
  
      <!-- Barra de herramientas -->
      <q-card bordered class="q-mb-md q-py-sm">
        <q-toolbar>
          <div class="row full-width q-col-gutter-md">
            <!-- Primera fila -->
            <div class="col-12 col-md-4">
              <q-input dense v-model="search" placeholder="Buscar..." clearable @update:model-value="loadData">
                <template v-slot:append>
                  <q-icon name="search" />
                </template>
              </q-input>
            </div>
  
            <div class="col-12 col-md-8">
              <div class="row q-col-gutter-sm">
                <div class="col-12 col-sm-6 col-md-4">
                  <q-select
                    v-model="entidadFilter"
                    :options="entidades"
                    option-label="denominacion"
                    option-value="id"
                    label="Unidades de Alta Dirección/Entidad"
                    placeholder="Seleccione una unidad de alta dirección/entidad"
                    dense
                    clearable
                    :disable="(auth.isResponsable || auth.isConsulta) && entidades.length === 1"
                    @update:model-value="loadData"
                  />
                </div>
                <div class="col-12 col-sm-6 col-md-4">
                  <q-select
                    v-model="oficinaFilter"
                    :options="oficinas"
                    option-label="denominacion"
                    option-value="id"
                    label="Oficina"
                    dense
                    clearable
                    :disable="(auth.isResponsable || auth.isConsulta) && oficinas.length === 1"
                    @update:model-value="loadData"
                  />
                </div>
                <div class="col-12 col-sm-6 col-md-4">
                  <q-select
                    v-model="areaFilter"
                    :options="areas"
                    option-label="nombre_completo"
                    option-value="id"
                    label="Área"
                    dense
                    clearable
                    :disable="!oficinaFilter"
                    @update:model-value="loadData"
                  />
                </div>
              </div>
            </div>
  
            <!-- Segunda fila -->
            <div class="col-12">
              <div class="row q-col-gutter-sm justify-end">
                <div v-if="auth.isAdmin || auth.isSupervisor" class="col-12 col-sm-auto">
                  <q-input
                    dense
                    outlined
                    v-model="dniUsuario"
                    label="Buscar por DNI de Usuario"
                    placeholder="DNI"
                    clearable
                    :loading="loadingUsuario"
                    @keyup.enter="buscarUsuarioPorDni"
                    @clear="limpiarFiltroDni"
                  >
                    <template v-slot:append>
                      <q-btn
                        round
                        dense
                        flat
                        icon="search"
                        @click="buscarUsuarioPorDni"
                        color="primary"
                        :disable="!dniUsuario"
                      />
                    </template>
                  </q-input>
                </div>
                <div class="col-12 col-sm-auto">
                  <q-select
                    v-model="estadoFilter"
                    :options="estados"
                    option-label="nombre"
                    option-value="id"
                    label="Estado"
                    dense
                    clearable
                    @update:model-value="loadData"
                  />
                </div>
  
                <div class="col-12 col-sm-auto" v-if="auth.isAdmin">
                  <q-btn-dropdown color="primary" label="Exportar" :loading="loadingExportarTodos" :disable="loadingExportarTodos">
                    <q-list>
                      <q-item clickable v-close-popup @click="exportar('csv')">
                        <q-item-section>CSV</q-item-section>
                      </q-item>
                      <q-item clickable v-close-popup @click="exportar('excel')">
                        <q-item-section>Excel</q-item-section>
                      </q-item>
                      <q-item clickable v-close-popup @click="exportar('excel_todos')">
                        <q-item-section>Excel (Todos)</q-item-section>
                      </q-item>
                    </q-list>
                  </q-btn-dropdown>
                </div>
                <div v-if="(auth.isAdmin || auth.isSupervisor|| auth.isResponsable||auth.isConsulta) && select.length > 0" class="col-12 col-sm-auto">
                  <q-btn-dropdown color="secondary" label="Entrega">
                    <q-list>
                      <q-item clickable v-close-popup @click="entregaActivo(select)">
                        <q-item-section>Realizar entrega de Activos</q-item-section>
                      </q-item>
                    </q-list>
                  </q-btn-dropdown>
                </div>
                <div v-if="(auth.isConsulta || auth.isResponsable || auth.isSupervisor || auth.isAdmin) && select.length > 0" class="col-12 col-sm-auto">
                  <div v-if="((auth.isAdmin || auth.isSupervisor) && responsableId) || auth.isResponsable || auth.isConsulta">
                    <q-btn 
                      color="primary" 
                      label="Conformidad de Uso" 
                      icon="check_circle" 
                      @click="abrirDeclaracionUso"
                    />
                  </div>
                </div>
                <div v-if="auth.isAdmin || auth.isSupervisor || auth.isResponsable || auth.isConsulta" class="col-12 col-sm-auto">
                  <q-btn flat dense round icon="download" color="deep-withe" @click="descargarDeclaracionUso">
                    <q-tooltip>Descargar declaración de Uso</q-tooltip>
                  </q-btn>
                </div>
              </div>
            </div>
          </div>
        </q-toolbar>
      </q-card>

    <TableDynamic
      v-model:selectedRows="select"
      :columns="columns"
      :row="activos"
      row-key="id"
      :loading="loading"
      :pagination="pagination"
      show-selection
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
          <q-btn flat dense round icon="visibility" color="info" @click="viewDetails(props.row)">
            <q-tooltip>Ver detalles</q-tooltip>
          </q-btn>
          <q-btn v-if="auth.isAdmin || auth.isSupervisor || auth.isResponsable || auth.isConsulta" flat dense round icon="edit" color="primary" :loading="editingItemId === props.row.id" :disable="editingItemId === props.row.id" @click="openDialog(props.row)">
            <q-tooltip>Editar</q-tooltip>
          </q-btn>
          <q-btn v-if="auth.isAdmin || auth.isSupervisor" flat dense round icon="delete" color="negative" @click="confirmDelete(props.row)">
            <q-tooltip>Eliminar</q-tooltip>
          </q-btn>
          <q-btn v-if="auth.isAdmin || auth.isSupervisor || auth.isResponsable || auth.isConsulta" flat dense round icon="history" color="secondary" @click="viewHistory(props.row)">
            <q-tooltip>Historial</q-tooltip>
          </q-btn>
        </q-td>
      </template>
    </TableDynamic>
    <DialogModal v-model:show="modal.show" :title="modal.title" :mode="modal.mode" @close="handleClouse">
      <DynamicForm
      :fields="modernFormFields"
      v-model="formData"
      :readonly="modal.mode==='view'"
      :mode="modal.mode"
      :loading="loading"
      @submit="handleFormSubmit"
      @select-change="handleSelectChange"
      @search-click="handleSearchClick"
      @cancel="handleClouse"
      />
    </DialogModal>
    <q-dialog v-model="deleteDialog">
      <q-card>
        <q-card-section class="row items-center">
          <q-avatar icon="warning" color="negative" text-color="white" />
          <span class="q-ml-sm">¿Está seguro de eliminar este activo?</span>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="primary" v-close-popup />
          <q-btn flat label="Eliminar" color="negative" @click="deleteAsset" />
        </q-card-actions>
      </q-card>
    </q-dialog>
    <EntregaModal
      v-model:show="entregaDialog"
      :activos="select"
      @guardar="guardarEntrega"
    />
    <DeclaracionUsoModal
      v-model:show="declaracionUsoDialog"
      :activos="select"
      @declaracion-confirmada="guardarDeclaracionUso"
    />
    <q-dialog v-model="historyDialog" full-width>
      <q-card>
        <q-card-section class="row items-center">
          <div class="text-h6">Historial del Activo - {{ selectedAsset.code }}</div>
          <q-space />
          <q-btn icon="close" flat round dense v-close-popup />
        </q-card-section>

        <q-card-section>
          <q-tabs
            v-model="historyTab"
            class="text-primary"
            active-color="primary"
            indicator-color="primary"
            align="justify"
          >
            <q-tab name="mantenimientos" label="Mantenimientos" />
            <q-tab name="movimientos" label="Movimientos" />
          </q-tabs>

          <q-tab-panels v-model="historyTab" animated>
            <q-tab-panel name="movimientos">
              <q-table
                :rows="movimientos"
                :columns="movimientosColumns"
                row-key="id"
                :loading="loadingMovimientos"
              />
            </q-tab-panel>
          </q-tab-panels>
        </q-card-section>
      </q-card>
    </q-dialog>
    </PermissionsAccess>
  </q-page>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import PermissionsAccess from 'src/components/Permissions/PermissionsAccess.vue'
import { useQuasar } from 'quasar'
import { useAuthStore } from 'src/stores/auth-store'
import { activoService } from 'src/services/activoService'
import TableDynamic from 'src/components/TableDynamic.vue'
import DialogModal from 'src/components/DialogModal.vue'
import DynamicForm from 'src/components/DynamicForm.vue'
import { authService } from '../../services/authService'
import ExcelJS from 'exceljs'
import EntregaModal from 'src/components/EntregaModal.vue'
import DeclaracionUsoModal from 'src/components/DeclaracionUsoModal.vue'
import { categoriaService } from 'src/services/categoriaService'
import { oficinaService } from 'src/services/oficinaService'
import { entidadService } from 'src/services/entidadService'
import { areaService } from 'src/services/areaService'
import { declaracionService } from 'src/services/declaracionService'
const $q = useQuasar()
const auth = useAuthStore()

const loadingExportarTodos=ref(false)
const loading = ref(false)
const loadingOpenDialog = ref(false)
const editingItemId = ref(null)
const search = ref('')
const entidadFilter = ref(null)
const oficinaFilter = ref(null)
const areaFilter = ref(null)
const estadoFilter = ref(null)
const activos = ref([])
const entidades = ref([])
const oficinas = ref([])
const areas = ref([])
const categorias = ref([])
const estados = ref([])
const select = ref([])
const formData = ref([])
const selectedAsset = ref({})
const deleteDialog = ref(false)
const entregaDialog = ref(false)
const historyDialog = ref(false)
const historyTab = ref('movimientos')
const movimientos = ref([])
const loadingMovimientos = ref(false)
const pagination = ref({
  sortBy: 'id',
  page: 1,
  rowsPerPage: 10,
  rowsNumber: 0,
  descending: false
})
const modal=ref({
  show: false,
  title: 'create',
  mode: 'create'
})
const columns = [
    { name: 'codigo', label: 'Código', field: 'codigo', align: 'left', sortable: true },
    //{ name: 'denominacion', label: 'Denominación', field: 'denominacion', align: 'left', sortable: true },
    { name: 'categoria', label: 'Denominación', field: row => row.catalogo?.denominacion, align: 'left' },
    { name: 'Entidad', label: 'Unidades de Alta Dirección/Entidad', field: row => row.area?.oficina?.entidad?.denominacion, align: 'left' },
    { name: 'Oficina', label: 'Oficina', field: row => row.area?.oficina?.denominacion, align: 'left' },
    { name: 'Área', label: 'Área', field: row => row.area?.nombre_completo, align: 'left' },
    { name: 'numero_serie', label: 'Número Serie', field: 'numero_serie', align: 'left' },
    { name: 'responsable', label: 'Responsable', field: row => row.responsable?.name, align: 'left' },
    { name: 'dni', label: 'DNI', field: row => row.responsable?.dni, align: 'left'},
    { name: 'estado', label: 'Estado', field: 'estado', align: 'center' },
    { name: 'condicion', label: 'Condición', field: 'condicion', align: 'center' },
    { name: 'actions', label: 'Acciones', align: 'center' }
]

const movimientosColumns = [
  { name: 'fecha', label: 'Fecha', field: row=>row.movimiento?.fecha_movimiento, align: 'left', sortable: true },
  { name: 'ubicacion_origen', label: 'Ubicación Origen', field: row=>row.ubicacion_origen?.nombre, align: 'left' },
  { name: 'ubicacion_destino', label: 'Ubicación Destino', field: row=>row.ubicacion_destino?.nombre, align: 'left' },
  { name: 'estado', label: 'Estado', field: 'estado', align: 'left' }
]

const modernFormFields = ref([
    {
      type: 'separator', label: 'Información Básica'
    },
    {
      name: 'codigo', type: 'text', label: 'Código del Activo', placeholder: 'ACT-001', rules: [val => !!val || 'El número de serie es requerido', val => (val.length === 10 || val.length === 12) || 'El número de serie debe tener exactamente 10 o 12 caracteres'], search: 'qr_code', minlenth: 10, maxlength: 12
    },
    //{
    //  name: 'denominacion', type: 'text', label: 'denominación del Activo', placeholder: 'Denominación', rules: [val => !!val || 'el campo es requerido'], prepend: 'inventory_2', uppercase: true
    //},
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
    ...(!(auth.isConsulta || auth.isResponsable) ? [
    {
      type: 'separator', label: 'Información Financiera', hide: auth.isResponsable || auth.isConsulta
    },
    {
      name: 'fecha_adquisicion', type: 'date', label: 'Fecha de Adquisición', rules: [val => !!val || 'La fecha de adquisición es requerida'], prepend: 'event', hide: auth.isResponsable || auth.isConsulta, default: () => new Date().toISOString().slice(0, 10)
    },
    {
      name: 'valor_inicial', type: 'number', label: 'Valor inicial', placeholder: '0.00', min: 0, step: 0.01, prepend: 'attach_money', hide: auth.isResponsable || auth.isConsulta
    }
    ]: []),
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
      type: 'separator', label: 'Ubicación'
    },
    {
      name: 'entidad', type: 'select', label: 'Entidad', placeholder: 'Seleccione una unidad de alta dirección/entidad', rules: [val => !!val || 'La unidad de alta dirección/entidad es requerida'], options: [], prepend: 'school', emitvalue: false, mapOptions: true, optionLabel: 'label', optionValue: 'value'
    },
    {
      name: 'oficina', type: 'select', label: 'Oficina', placeholder: 'Seleccione una oficina', rules: [val => !!val || 'La Oficina es requerida'], options: [], disabled: true, prepend: 'business', emitvalue: false, mapOptions: true, optionLabel: 'label', optionValue: 'value'
    },
    {
      name: 'area', type: 'select', label: 'Área', placeholder: 'Seleccione una Área', rules: [val => !!val || 'El campo es requerida'], options: [], disabled: true, prepend: 'location_on', emitvalue: false, mapOptions: true, optionLabel: 'label', optionValue: 'value'
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
const onPagination = (newPagination) => {
  pagination.value = { ...pagination.value, ...newPagination }
  loadData()
}

const loadData = async () => {
  loading.value = true
  try {
    const params = {
      page: pagination.value.page,
      per_page: pagination.value.rowsPerPage,
      sort_by: pagination.value.sortBy,
      desc: pagination.value.descending,
      search: search.value,
      entidad_id: entidadFilter.value,
      oficina_id: oficinaFilter.value,
      area_id: areaFilter.value,
      estado: estadoFilter.value,
      responsable_id: responsableId.value || undefined
    }
    const response = await activoService.getActivos(params)
    activos.value = response.data
    pagination.value = { ...response.pagination }
  } catch (error) {
    console.error(error)
    activos.value = []
    $q.notify({ color: 'negative', message: 'Error al cargar los datos' })
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  loading.value=true
  await Promise.all([
    entidadService.getEntidades().then(res => entidades.value = res.data),
    categoriaService.getCategorias().then(res => categorias.value = res.data),
    activoService.getEstados().then(res => estados.value = res)
  ])
  if (auth.isResponsable || auth.isConsulta) {
    const user = await authService.getCurrentUser()
    if (user?.oficinas?.length>0) {
      const entidadesUsuario = [...new Set(user.oficinas.map(o => o.entidad))]
      entidades.value = entidadesUsuario.filter((entidad, index, self) =>
        index === self.findIndex(e => e.id === entidad.id)
      )
      //entidades.value = entidadesUsuario
      if (entidades.value.length === 1) {
        entidadFilter.value = entidades.value[0]
      }
    }
    oficinas.value = user.oficinas
    if (user?.oficinas?.length > 0) {
      if (user.oficinas.length === 1) {
        oficinaFilter.value = user.oficinas[0]
      }
    }
  }
  await loadData()
  loading.value=false
})

const handleSearchClick=async({field, currentValue})=>{
  if (!currentValue) {
    $q.notify({
      color: 'warning',
      message: 'Ingrese un valor para buscar',
      icon: 'warning',
      position: 'top'
    });
    return;
  }
  try {
    let response
    if(field==='codigo'){
      response = await activoService.getActivos({codigo: currentValue});
    }
    if(!response?.data){
      $q.notify({
        color: 'warning',
        message: 'No se encontraron resultados',
        icon: 'search_off',
        position: 'top'
      });
      return;
    }
    const activo=response.data;
    let currentUser;
    let entidadesFormateadas = [];
    if(!activo[0].responsable){
      currentUser = await authService.getCurrentUser()
      const entidadesUsuario = [...new Set(currentUser.oficinas.map(o => o.entidad))]
      const ent = entidadesUsuario.filter((entidad, index, self) =>
        index === self.findIndex(e => e.id === entidad.id)
      )
      entidadesFormateadas = ent.map(ent => ({
        label: ent.denominacion,
        value: ent.id
      }))
      modernFormFields.value.find(f => f.name === 'entidad').options = entidadesFormateadas
    }
    const newFormData={
      id: activo[0].id,
      codigo: activo[0].codigo,
      catalogo_id: activo[0].catalogo? {
        label: activo[0].catalogo.denominacion,
        value: activo[0].catalogo.id
      }:null,
      ...(currentUser && {responsable_id: currentUser}),
      ...(activo[0].color && {color: activo[0].color}),
      ...(activo[0].marca && {marca: activo[0].marca}),
      ...(activo[0].modelo && { modelo: activo[0].modelo}),
      ...(activo[0].numero_serie && {numero_serie: activo[0].numero_serie}),
      estado: activo[0].estado?{
        label: activo[0].estado_display,
        value: activo[0].estado.estado
      }: null,
      condicion: activo[0].condicion?{
        label: activo[0].condicion_display,
        value: activo[0].condicion
      }: null,
      ...(activo[0].descripcion && {descripcion: activo[0].descripcion}),
      ...(activo[0].notas && {notas: activo[0].notas}),
      ...(activo[0].valor_inicial && {valor_inicial: activo[0].valor_inicial}),
      ...(activo[0].fecha_adquisicion && {fecha_adquisicion: activo[0].fecha_adquisicion}),
      ...(activo[0].area && {area: { label: activo[0].area.nombre_completo, value: activo[0].area.id}}),
      ...(activo[0].area?.oficina && {oficina: activo[0].area.oficina.denominacion}),
      ...(activo[0].area?.oficina?.entidad && {entidad: activo[0].area.oficina.entidad.denominacion})
    }
    if(activo[0].responsable)
      modal.value.mode="view"
    else
      modal.value.mode="edit"
    modernFormFields.value = modernFormFields.value.map(field => {
      if (!field || field.type === 'separator') return field;
      if (field.name === 'estado' || field.name === 'condicion') {
        return {
          ...field,
          disabled: false
        };
      }
      const fieldValue = newFormData[field.name];
      const isDisabled = fieldValue !== null && fieldValue !== undefined;
      
      return {
        ...field,
        disabled: isDisabled
      };
    });
    formData.value={...formData.value, ...newFormData};
    modal.value.title="Agregar activo"
    
  } catch (error) {
    console.error(error)
    $q.notify({
      color: 'negative',
      message: 'Acitvo no encontrado',
      icon: 'report_problem',
      position: 'top'
    })
  }
}

watch(entidadFilter, async (val) => {
  oficinaFilter.value = null
  areaFilter.value = null
  if (val) {
    if (auth.isAdmin || auth.isSupervisor) {
      const ofi = await oficinaService.getOficinas({entidad: val.id})
      oficinas.value = ofi.data
    } else if (auth.isResponsable || auth.isConsulta) {
      const user = await authService.getCurrentUser()
      oficinas.value = user.oficinas.filter(o => o.entidad_id === val.id)
      if (oficinas.value.length === 1) {
        oficinaFilter.value = oficinas.value[0]
      }
    }
  } else {
    oficinas.value = []
  }
})
watch(oficinaFilter, async (val) => {
  areaFilter.value = null
  if (val) {
    const area = await areaService.getAreas({oficina:val.id})
    areas.value = area.data
  } else {
    areas.value = []
  }
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
const openDialog = async (activo = null) => {
  if (!(auth.isAdmin || auth.isSupervisor || auth.isResponsable || auth.isConsulta)) return  
  if(!activo) {
    loadingOpenDialog.value = true
  }
  if (activo) {
    editingItemId.value = activo.id
  }
  try {
      const categorias = await categoriaService.getCategorias()
      const categoriasFormateadas = categorias.data.map(cat => ({
          label: cat.denominacion,
          value: cat.id
      }))
      modernFormFields.value.find(f => f.name === 'catalogo_id').options = categoriasFormateadas
      let entidadesFormateadas = []
      if (auth.isAdmin || auth.isSupervisor) {
        const entidadesResponse = await entidadService.getEntidades()
        entidadesFormateadas = entidadesResponse.data.map(fac => ({
          label: fac.denominacion,
          value: fac.id
        }))
      } else if (auth.isResponsable || auth.isConsulta) {
        const user = await authService.getCurrentUser()
        if (user?.oficinas?.length > 0) {
          const entidadesUsuario = [...new Set(user.oficinas.map(o => o.entidad))]
          entidadesFormateadas = entidadesUsuario.map(ent => ({
            label: ent.denominacion,
            value: ent.id
          }))
        }
      }
      modernFormFields.value.find(f => f.name === 'entidad').options = entidadesFormateadas
      if (activo) {
          formData.value = {
              ...activo,
              catalogo_id: activo.catalogo ? {
                  label: activo.catalogo.denominacion,
                  value: activo.catalogo.id
              } : null,
              estado: {
                  label: activo.estado_display,
                  value: activo.estado
              },
              condicion: {
                  label: activo.condicion_display,
                  value: activo.condicion
              },
              entidad: activo.area?.oficina?.entidad ? {
                  label: activo.area.oficina.entidad.denominacion,
                  value: activo.area.oficina.entidad.id
              } : null,
              oficina: activo.area?.oficina ? 
              {
                  label: activo.area.oficina.denominacion,
                  value: activo.area.oficina.id
              } : null,
              area: activo.area ? {
                  label: activo.area.nombre_completo,
                  value: activo.area.id
              } : null,
              notas: activo.notas || ''
          }
          if (formData.value.entidad) {
              const oficinas = await oficinaService.getOficinas({entidad: formData.value.entidad.value})
              const oficinasFormateados = oficinas.data.map(dep => ({
                  label: dep.denominacion,
                  value: dep.id
              }))
              //
              modernFormFields.value.find(f => f.name === 'oficina').options = oficinasFormateados
              modernFormFields.value.find(f => f.name === 'oficina').disabled = false
              formData.value.oficina = activo.area?.oficina ? {
                  label: activo.area.oficina.denominacion,
                  value: activo.area.oficina.id
              } : null
              // Si hay oficina seleccionada, cargar areas
              if (formData.value.oficina) {
                  const areas = await areaService.getAreas({oficina: formData.value.oficina.value})
                  const areasFormateadas = areas.data.map(ubic => ({
                      label: ubic.nombre_completo,
                      value: ubic.id
                  }))
                  modernFormFields.value.find(f => f.name === 'area').options = areasFormateadas
                  modernFormFields.value.find(f => f.name === 'area').disabled = false
                  // Establecer la area seleccionada
                  formData.value.area = activo.area ? {
                      label: activo.area.nombre_completo,
                      value: activo.area.id
                  } : null
              }
          }
          modal.value.title = "Editar Activo"
          modal.value.mode = 'edit'
      } else {
          formData.value = {}
          modal.value.title = "Crear Nuevo Activo"
          modal.value.mode = 'create'
          modernFormFields.value = modernFormFields.value.map(field => ({
            ...field,
            disabled: false
          }))
      }
      if (auth.isResponsable || auth.isConsulta) {
        const user = await authService.getCurrentUser()
        if (user?.oficinas?.length > 0) {
          const entidadesUsuario = [...new Set(user.oficinas.map(o => o.entidad))]
          const ent = entidadesUsuario.filter((entidad, index, self) =>
            index === self.findIndex(e => e.id === entidad.id)
          )
          if (ent.length === 1) {
            formData.value.entidad = {
              label: ent[0].denominacion,
              value: ent[0].id
            }
            
            modernFormFields.value.find(f => f.name === 'entidad').disabled = true
            const oficinasUsuario = user.oficinas
              .filter(o => o.entidad_id === ent[0].id)
              .map(o => ({
                label: o.denominacion,
                value: o.id
              }))
            
            modernFormFields.value.find(f => f.name === 'oficina').options = oficinasUsuario
            modernFormFields.value.find(f => f.name === 'oficina').disabled = false
            if (oficinasUsuario.length === 1) {
              formData.value.oficina = oficinasUsuario[0]
              modernFormFields.value.find(f => f.name === 'oficina').disabled = true
              const area = await areaService.getAreas({oficina: oficinasUsuario[0].id})
              const areasFormateadas = area.data.map(ubic => ({
                label: ubic.nombre_completo,
                value: ubic.id
              }))
              modernFormFields.value.find(f=>f.name==='area').options=areasFormateadas
              modernFormFields.value.find(f => f.name === 'area').disabled = false
            }
          }
        }
      }
      if (!activo) {
        formData.value.fecha_adquisicion = new Date().toISOString().slice(0, 10)
        modal.value.title = "Agregar Activo"
        modal.value.mode = 'create'
      }
      modernFormFields.value = modernFormFields.value.map(field => {
          if((auth.isResponsable||auth.isConsulta) && modal.value.mode==="edit") {
              const editableFields = ['estado', 'condicion', 'descripcion', 'notas', 'area']
              return {
                  ...field,
                  disabled: !editableFields.includes(field.name)
              }
          }
          return field
      })
      modal.value.show = true
  } catch (error) {
      console.error('Error al cargar datos del formulario:', error)
      $q.notify({
          color: 'negative',
          message: 'Error al cargar datos del formulario',
          icon: 'error'
      })
  } finally {
    loadingOpenDialog.value = false
    editingItemId.value = null
  }
}

const handleFormSubmit = async(data) => {
  if (
    modal.value.mode === 'edit' && !(auth.isAdmin || auth.isSupervisor || auth.isResponsable || auth.isConsulta)
  ) return
  if (modal.value.mode === 'create' && !(auth.isAdmin || auth.isSupervisor || auth.isResponsable)) {
    $q.notify({
    color: 'negative',
      message: 'No tienes permiso para crear un activo',
      icon: 'error',
      position: 'top',
    })
    return
  }
  try {
    loading.value = true
    const currentUser = await authService.getCurrentUser()
    const dataToSubmit = {
      ...data,
      responsable_id: currentUser.id,
      catalogo_id: data.catalogo_id?.value,
      estado: data.estado?.value,
      condicion: data.condicion?.value,
      area_id: typeof data.area === 'object' ? data.area.value : data.area,
      entidad_id: data.entidad?.value,
      oficina_id: data.oficina?.value,
      notas: data.notas || ''
    }
    if (modal.value.mode === 'edit') {
      if(dataToSubmit.fecha_adquisicion) dataToSubmit.fecha_adquisicion = new Date().toISOString().slice(0, 10)
      await activoService.updateActivo(formData.value.id, dataToSubmit)
      $q.notify({
        color: 'positive',
        message: 'Activo actualizado exitosamente',
        icon: 'check'
      })
    } else {
      await activoService.createActivo(dataToSubmit)
      $q.notify({
        color: 'positive',
        message: 'Activo creado exitosamente',
        icon: 'check'
      })
    }
    
    modal.value.show = false
    await loadData()
  } catch (error) {
    if (error.response?.data?.errors) {
      Object.entries(error.response.data.errors).forEach(([field, messages])=>{
        console.log(field)
        $q.notify({
          color: 'negative',
          message: messages ? messages : 'Error al guardar Activo',
          icon: 'error',
          position: 'top'
        })
      })
    } else {
      $q.notify({
        color: 'negative',
        message: 'Error al guardar el activo',
        icon: 'report_problem',
        position: 'top'
      })
    }
  } finally {
    loading.value = false
  }
}

const handleSelectChange = (fieldName, value) => {
  if(fieldName === 'entidad') {
    handleEntidadChange(value);
  } else if(fieldName === 'oficina') {
    handleOficinaChanges(value);
  }
};

const handleClouse = () => {
  modal.value.show=false
  modal.value.title=''
  modal.value.mode=''
  formData.value={}
}

const handleEntidadChange = async (entidad) => {
  const depField = modernFormFields.value.find(f => f.name === 'oficina');
  const ubiField = modernFormFields.value.find(f => f.name === 'area');
  if (depField) {
    depField.options = [];
    depField.disabled = true;
  }
  if (ubiField) {
    ubiField.options = [];
    ubiField.disabled = true;
  }
  if (entidad?.value) {
    try {
      const oficinas = await oficinaService.getOficinas({entidad: entidad.value});
      if (depField) {
        depField.options = oficinas.data.map(dep => ({
          label: dep.denominacion,
          value: dep.id
        }));
        depField.disabled = false;
      }
    } catch (error) {
      console.error('Error al cargar oficinas:', error);
    }
  }
};
const handleOficinaChange = async (oficina) => {
  const ubiField = modernFormFields.value.find(f => f.name === 'area');
  if (ubiField) {
    ubiField.options = [];
    ubiField.disabled = true;
  }
  if (oficina?.value) {
    try {
      const areas = await areaService.getAreas({oficina: oficina.value});
      if (ubiField) {
        ubiField.options = areas.data.map(ubi => ({
          label: ubi.nombre_completo,
          value: ubi.id
        }));
        ubiField.disabled = false;
      }
    } catch (error) {
      console.error('Error al cargar areas:', error);
    }
  }
};
const viewDetails = (activo) => {
  try {
      formData.value = {
          ...activo,
          catalogo_id: activo.catalogo ? {
              label: activo.catalogo.denominacion,
              value: activo.catalogo.id
          } : null,
          estado: {
              label: activo.estado_display,
              value: activo.estado
          },
          condicion: {
              label: activo.condicion_display,
              value: activo.condicion
          },
          entidad: activo.area?.oficina?.entidad ? {
              label: activo.area.oficina.entidad.denominacion,
              value: activo.area.oficina.entidad.id
          } : null,
          oficina: activo.area?.oficina ? {
              label: activo.area.oficina.denominacion,
              value: activo.area.oficina.id
          } : null,
          area: activo.area ? {
              label: activo.area.nombre_completo,
              value: activo.area.id
          } : null
      }
      modal.value.title = "Ver Detalles del Activo"
      modal.value.mode = 'view'
      modal.value.show = true
  } catch (error) {
      console.error('Error al cargar detalles del activo:', error)
      $q.notify({
          color: 'negative',
          message: 'Error al cargar detalles del activo',
          icon: 'error'
      })
  }
}

// Observar cambios en la pestaña de historial
//watch(historyTab, async (newTab) => {
//  if (newTab === 'movimientos' && selectedAsset.value.id) {
//    await loadMovimientos(selectedAsset.value.id)
//  }
//})

const confirmDelete = (asset) => {
  if (!(auth.isAdmin || auth.isSupervisor || auth.isResponsable)) return
  selectedAsset.value = asset
  deleteDialog.value = true
}

const deleteAsset = async () => {
  try {
    await activoService.deleteActivo(selectedAsset.value.id)
    $q.notify({
      color: 'positive',
      message: 'Activo eliminado exitosamente',
      icon: 'check',
      position: 'top'
    })
    loadData()
  } catch (error) {
    console.error(error)
    $q.notify({
      color: 'negative',
      message: 'Error al eliminar el activo',
      icon: 'report_problem',
      position: 'top'
    })
  } finally {
    deleteDialog.value = false
  }
}

const viewHistory = async (asset) => {
  selectedAsset.value = asset
  historyDialog.value = true
  historyTab.value = 'movimientos'
  await loadMovimientos(asset.id)
  //await loadMantenimientos(asset.id)
}

const exportar = async (type) => {
  loadingExportarTodos.value=true
  try {
    if (type === 'csv') {
      const data = activos.value.map(activo => ({
        'Código': activo.codigo,
        //'': activo.denominacion,
        'Denominacion': activo.catalogo?.denominacion || '-',
        'Marca': activo.marca || '-',
        'Modelo': activo.modelo || '-',
        'Número Serie': activo.numero_serie || '-',
        'Estado': activo.estado_display || '-',
        'Condición': activo.condicion_display || '-',
        'Unidades de Alta Dirección/Entidad': activo.area?.oficina?.entidad?.denominacion || '-',
        'Oficina': activo.area?.oficina?.denominacion || '-',
        'Área': activo.area?.nombre_completo || '-',
        'Responsable': activo.responsable?.name || '-',
        'Responsable DNI': activo.responsable?.dni || '-',
        'Fecha Adquisición': activo.fecha_adquisicion || '-',
        'Valor Inicial': activo.valor_inicial ? `$${activo.valor_inicial.toFixed(2)}` : '-',
      }))
      // Convertir a CSV
      const headers = Object.keys(data[0])
      const csvContent = [
        headers.join(','),
        ...data.map(row => headers.map(header => `"${row[header]}"`).join(','))
      ].join('\n')
      // Crear y descargar el archivo
      const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
      const link = document.createElement('a')
      const url = URL.createObjectURL(blob)
      link.setAttribute('href', url)
      link.setAttribute('download', `activos_${new Date().toISOString().split('T')[0]}.csv`)
      link.style.visibility = 'hidden'
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      $q.notify({
        color: 'positive',
        message: 'Archivo CSV exportado exitosamente',
        icon: 'check'
      })
    } else if (type === 'excel') {
      const workbook = new ExcelJS.Workbook()
      const worksheet = workbook.addWorksheet('Activos')
      worksheet.columns = [
        { header: 'Código', key: 'codigo', width: 15 },
        //{ header: '', key: 'denominacion', width: 30 },
        { header: 'Denominación', key: 'categoria', width: 20 },
        { header: 'Marca', key: 'marca', width: 15 },
        { header: 'Modelo', key: 'modelo', width: 15 },
        { header: 'Número Serie', key: 'numero_serie', width: 15 },
        { header: 'Estado', key: 'estado', width: 15 },
        { header: 'Condición', key: 'condicion', width: 15 },
        { header: 'Unidades de Alta Dirección/Entidad', key: 'entidad', width: 25 },
        { header: 'Oficina', key: 'oficina', width: 25 },
        { header: 'Área', key: 'area', width: 30 },
        { header: 'Responsable', key: 'responsable', width: 25 },
        { header: 'Responsable DNI', key: 'dni', width: 15 },
        { header: 'Fecha Adquisición', key: 'fecha_adquisicion', width: 15 },
        { header: 'Costo Adquisición', key: 'varlor_inicial', width: 15 }
      ]
      // Estilo para el encabezado
      worksheet.getRow(1).font = { bold: true, size: 12 }
      worksheet.getRow(1).fill = {
        type: 'pattern',
        pattern: 'solid',
        fgColor: { argb: 'FFE0E0E0' }
      }
      worksheet.getRow(1).alignment = { vertical: 'middle', horizontal: 'center' }
      // Agregar los datos
      activos.value.forEach(activo => {
        worksheet.addRow({
          codigo: activo.codigo,
          categoria: activo.catalogo?.denominacion || '-',
          marca: activo.marca || '-',
          modelo: activo.modelo || '-',
          numero_serie: activo.numero_serie || '-',
          estado: activo.estado_display || '-',
          condicion: activo.condicion_display || '-',
          entidad: activo.area?.oficina?.entidad?.denominacion || '-',
          oficina: activo.area?.oficina?.denominacion || '-',
          area: activo.area?.nombre_completo || '-',
          responsable: activo.responsable?.name || '-',
          dni: activo.responsable?.dni || '-',
          fecha_adquisicion: activo.fecha_adquisicion || '-',
          costo_adquisicion: activo.varlor_inicial ? `$${activo.valor_inicial.toFixed(2)}` : '-'
        })
      })
      // Aplicar bordes a todas las celdas
      worksheet.eachRow((row) => {
        row.eachCell((cell) => {
          cell.border = {
            top: { style: 'thin' },
            left: { style: 'thin' },
            bottom: { style: 'thin' },
            right: { style: 'thin' }
          }
        })
      })
      // Generar el archivo Excel
      const buffer = await workbook.xlsx.writeBuffer()
      const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' })
      
      // Descargar el archivo
      const url = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', `activos_${new Date().toISOString().split('T')[0]}.xlsx`)
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      $q.notify({
        color: 'positive',
        message: 'Archivo Excel exportado exitosamente',
        icon: 'check'
      })
    } else if (type === 'excel_todos') {
      // Obtener todos los activos sin paginación ni filtros
      const response = await activoService.getActivos({})
      const todosActivos = response.data
      // Crear un nuevo libro de Excel
      const workbook = new ExcelJS.Workbook()
      const worksheet = workbook.addWorksheet('Activos')
      worksheet.columns = [
        { header: 'Código', key: 'codigo', width: 15 },
        { header: 'Denominación', key: 'categoria', width: 20 },
        { header: 'Marca', key: 'marca', width: 15 },
        { header: 'Modelo', key: 'modelo', width: 15 },
        { header: 'Número Serie', key: 'numero_serie', width: 15 },
        { header: 'Estado', key: 'estado', width: 15 },
        { header: 'Condición', key: 'condicion', width: 15 },
        { header: 'Unidades de Alta Dirección/Entidad', key: 'entidad', width: 25 },
        { header: 'Oficina', key: 'oficina', width: 25 },
        { header: 'Área', key: 'area', width: 30 },
        { header: 'Responsable', key: 'responsable', width: 25 },
        { header: 'Responsable DNI', key: 'dni', width: 15 },
        { header: 'Fecha Adquisición', key: 'fecha_adquisicion', width: 15 },
        { header: 'Costo Adquisición', key: 'varlor_inicial', width: 15 }
      ]
      worksheet.getRow(1).font = { bold: true, size: 12 }
      worksheet.getRow(1).fill = {
        type: 'pattern',
        pattern: 'solid',
        fgColor: { argb: 'FFE0E0E0' }
      }
      worksheet.getRow(1).alignment = { vertical: 'middle', horizontal: 'center' }
      todosActivos.forEach(activo => {
        worksheet.addRow({
          codigo: activo.codigo,
          categoria: activo.catalogo?.denominacion || '-',
          marca: activo.marca || '-',
          modelo: activo.modelo || '-',
          numero_serie: activo.numero_serie || '-',
          estado: activo.estado_display || '-',
          condicion: activo.condicion_display || '-',
          entidad: activo.area?.oficina?.entidad?.denominacion || '-',
          oficina: activo.area?.oficina?.denominacion || '-',
          area: activo.area?.nombre_completo || '-',
          responsable: activo.responsable?.name || '-',
          dni: activo.responsable?.dni || '-',
          fecha_adquisicion: activo.fecha_adquisicion || '-',
          varlor_inicial: activo.valor_inicial ? `$${activo.valor_inicial.toFixed(2)}` : '-'
        })
      })
      worksheet.eachRow((row) => {
        row.eachCell((cell) => {
          cell.border = {
            top: { style: 'thin' },
            left: { style: 'thin' },
            bottom: { style: 'thin' },
            right: { style: 'thin' }
          }
        })
      })
      const buffer = await workbook.xlsx.writeBuffer()
      const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' })
      const url = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', `activos_todos_${new Date().toISOString().split('T')[0]}.xlsx`)
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      $q.notify({
        color: 'positive',
        message: 'Archivo Excel (Todos) exportado exitosamente',
        icon: 'check'
      })
    } else {
      const params = {
        entidad_id: entidadFilter.value,
        oficina_id: oficinaFilter.value,
        area_id: areaFilter.value,
        estado: estadoFilter.value
      }
      
      const blob = await activoService.exportar(type, params)
      const url = window.URL.createObjectURL(new Blob([blob]))
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', `activos.${type}`)
      document.body.appendChild(link)
      link.click()
      link.remove()
    }
  } catch (error) {
    console.error(error)
    $q.notify({
      color: 'negative',
      message: `Error al exportar a ${type}`,
      icon: 'report_problem'
    })
  }
  loadingExportarTodos.value=false
}
const entregaActivo = () => {
  entregaDialog.value = true
}

const guardarEntrega = async () => {
  try {
    loading.value = true
    //await activoService.createEntrega(data)
    $q.notify({
      type: 'positive',
      message: 'Entrega realizada con éxito'
    })
    loadData()
  } catch (error) {
    console.error('Error al guardar entrega:', error)
    $q.notify({
      type: 'negative',
      message: 'Error al guardar la entrega'
    })
  } finally {
    loading.value = false
  }
}

const loadMovimientos = async (id) => {
  loadingMovimientos.value = true
  try {
    const response = await activoService.getMovimientos(id)
    movimientos.value = response.data
  } catch (error) {
    console.error(error)
    $q.notify({
      color: 'negative',
      message: 'Error al cargar movimientos',
      icon: 'report_problem'
    })
  } finally {
    loadingMovimientos.value = false
  }
}

const declaracionUsoDialog = ref(false)
const ultimaDeclaracionUso = ref(null)

function abrirDeclaracionUso() {
  declaracionUsoDialog.value = true
}

function guardarDeclaracionUso(data) {
  // Aquí puedes guardar la declaración en el backend o en el store
  // Por ahora solo guardamos la última declaración localmente
  ultimaDeclaracionUso.value = data
  $q.notify({
    message: 'Conformidad de uso registrada',
    color: 'positive',
    position: 'top',
    timeout: 2000
  })
}

async function descargarDeclaracionUso() {
  try {
    const blob = await declaracionService.descargarPDFUso();
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', 'declaracion_uso.pdf');
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
  } catch (error) {
    console.error(error)
    $q.notify({ type: 'negative', message: 'Error al descargar la declaración de uso' });
  }
}

const dniUsuario = ref("");
const responsableId = ref(null);
const loadingUsuario = ref(false);

const buscarUsuarioPorDni = async () => {
  if (!dniUsuario.value) {
    responsableId.value = null;
    loadData();
    return;
  }
  loadingUsuario.value = true;
  try {
    const response = await authService.getUsuarios(dniUsuario.value);
    if (Array.isArray(response) && response.length > 0) {
      responsableId.value = response[0].id;
      select.value = [];
      $q.notify({
        type: 'positive',
        message: `Usuario encontrado: ${response[0].name}`
      });
      loadData();
    } else if (response && response.data && Array.isArray(response.data) && response.data.length > 0) {
      responsableId.value = response.data[0].id;
      select.value = [];
      $q.notify({
        type: 'positive',
        message: `Usuario encontrado: ${response.data[0].name}`
      });
      loadData();
    } else {
      responsableId.value = null;
      select.value = [];
      $q.notify({
        type: 'negative',
        message: 'No se encontró ningún usuario con ese DNI'
      });
      loadData();
    }
  } catch (error) {
    console.error(error)
    responsableId.value = null;
    select.value = [];
    $q.notify({
      type: 'negative',
      message: 'Error al buscar usuario por DNI'
    });
    loadData();
  } finally {
    loadingUsuario.value = false;
  }
};

const limpiarFiltroDni = () => {
  responsableId.value = null;
  loadData();
};
</script>
<style scoped>
.q-table__top {
  padding: 8px;
}
</style>