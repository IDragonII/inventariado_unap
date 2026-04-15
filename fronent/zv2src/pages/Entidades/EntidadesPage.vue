<template>
    <q-page padding>
        <PermissionsAccess :isAdmin="auth.isAdmin">
            <HeaderComponents
            title="Gestión de Unidades de Alta Dirección/Entidad"
            :isPermissions="auth.isAdmin"
            v-model:filter="filter"
            :loadingCreate="loadingCreate"
            createButtonLabel="Nueva Unidad de Alta Dirección/Entidad"
            @open-dialog="openDialog"
            />
            <q-card>
                <q-card-section>
                    <TableDynamic
                    :columns="columns"
                    :row="entidades"
                    row-key="id"
                    :filter="filter"
                    :pagination="pagination"
                    :loading="loading"
                    @update:pagination="onPagination"
                    @filter="onFilterChange"
                    >
                        <template #body-cell-actions="props">
                            <q-td :props="props">
                                <q-btn flat dense round icon="visibility" color="info" @click="verDetalles(props.row)">
                                    <q-tooltip>ver detalles</q-tooltip>
                                </q-btn>
                                <q-btn
                                v-if="auth.isAdmin"
                                flat dense round
                                :icon="editingEntidadId===props.row.id ? 'hourglass_empty' : 'edit'"
                                color="primary"
                                :loading="editingEntidadId===props.row.id"
                                :disable="editingEntidadId===props.row.id"
                                @click="openDialog(props.row)"
                                >
                                    <q-tooltip>{{ editingEntidadId === props.row.id ? 'Editando...' : 'Editar' }}</q-tooltip>
                                </q-btn>
                                <q-btn
                                v-if="auth.isAdmin"
                                flat dense round
                                :icon="loadingDelete[props.row.id] ? 'hourglass_empty' : 'delete'"
                                color="negative"
                                :loading="loadingDelete[props.row.id]"
                                :disable="loadingDelete[props.row.id]"
                                @click="confirmarEliminar(props.row)"
                                >
                                    <q-tooltip>{{ loadingDelete[props.row.id] ? 'Eliminando...' : 'Eliminar' }}</q-tooltip>
                                </q-btn>
                            </q-td>
                        </template>
                    </TableDynamic>
                </q-card-section>
                <DialogModal v-model:show="modal.show" :mode="modal.mode" :title="modal.title">
                    <DynamicForm
                    :fields="modernFormFields"
                    v-model="formData"
                    :mode="modal.mode"
                    @submit="handleFormSubmit"
                    @cancel="handleClouse"
                    :loading="formLoading"
                    />
                </DialogModal>
                <DeleteComponents v-model:show="confirmDialog" @confirm="eliminarEntidad"/>
            </q-card>
        </PermissionsAccess>
    </q-page>
</template>
<script setup>
import { onMounted, ref, computed } from 'vue';
import { useQuasar } from 'quasar';
import PermissionsAccess from 'src/components/Permissions/PermissionsAccess.vue';
import { useAuthStore } from 'src/stores/auth-store';
import HeaderComponents from 'src/components/components/HeaderComponents.vue';
import TableDynamic from 'src/components/TableDynamic.vue';
import { entidadService } from 'src/services/entidadService';
import DialogModal from 'src/components/DialogModal.vue';
import DynamicForm from 'src/components/DynamicForm.vue';
import DeleteComponents from 'src/components/components/DeleteComponents.vue';
const $q = useQuasar()
const auth = useAuthStore()
const loadingCreate = ref(false)
const loading=ref(false)
const loadingDelete=ref([])
const loadingForm=ref(false)
const filter = ref('')
const entidades=ref([])
const filterTimout = ref(null)
const editingEntidadId = ref(null)
const selectedEntidad=ref('')
const confirmDialog=ref(false)
const formData=ref({})
const pagination = ref({
    sortBy: 'id',
    descending: false,
    page: 1,
    rowsPerPage: 20,
    rowsNumber: 0
})
const modal=ref({
    show: false,
    title: '',
    mode: ''
})

const columns = [
  { name: 'denominacion', label: 'DENOMINACION', field: 'denominacion', align: 'left', sortable: true },
  { name: 'codigo', label: 'CÓDIGO', field: 'codigo', align: 'left' },
  { name: 'actions', label: 'ACCIONES', field: 'actions', align: 'center' }
]

const modernFormFields = ref ([
  { type: 'separator', label: 'Datos de unidad de alta dirección/entidad' },
  { name: 'denominacion', type: 'text', label: 'denominación de unidad de alta dirección/entidad', rules: [val => !!val || 'El campo es requerido'], prepend: 'badge', autogrow: true, uppercase: true},
  { name: 'codigo', type: 'text', label: 'Código de unidad de alta dirección/entidad', rules: [val => !!val || 'El campo es requerido'], prepend: 'badge', autogrow: true, uppercase: true}
])

const onPagination=(newPagination)=>{
    pagination.value={...pagination.value, ...newPagination}
    cargarDatos()
}
const onFilterChange = () => {
  clearTimeout(filterTimout.value)
  filterTimout.value=setTimeout(()=>{
    pagination.value.page=1
    cargarDatos()
  },500)
}
const formLoading = computed(() => {
  return loadingForm.value
})
const cargarDatos = async () => {
    if(!auth.isAdmin) {
        return
    }
    loading.value = true
    try {
        const res = await entidadService.getEntidades({
            page: pagination.value.page,
            per_page: pagination.value.rowsPerPage,
            search: filter.value,
            sort_by: pagination.value.sortBy,
            desc: pagination.value.descending
        })
        entidades.value=res.data
        pagination.value={
            page:res.meta.current_page,
            rowsPerPage:res.meta.per_page,
            rowsNumber:res.meta.total
        }
    } catch (error) {
      console.error(error)
      $q.notify({
        color: 'negative',
        message: 'Error al cargar los datos',
        icon: 'error'
      })
    } finally {
      loading.value = false
    }
}
const openDialog = (entidad = null) => {
    if (!auth.isAdmin) {
      $q.notify({
        color: 'negative',
        message: 'No tienes permisos para esta acción',
        icon: 'error'
      })
      return
    }
    try {
        if (entidad) {
            editingEntidadId.value=entidad.id
            formData.value={...entidad}
            modal.value.mode='edit'
            modal.value.title='Editar Unidad de Alta Dirección/Entidad'
        } else {
            loadingCreate.value=true
            formData.value = {}
            modal.value.mode='create'
            modal.value.title='Crear nueva Unidad de Alta Dirección/Entidad'
        }
        modal.value.show=true
    } catch (error) {
        console.error(error)
    } finally {
        loadingCreate.value=false
        editingEntidadId.value=null
    }
}
const handleFormSubmit = async(data)=>{
    if(!auth.isAdmin){
        $q.notify({
            color: 'negative',
            message: 'No tienes permiso para esta acción',
            icon: 'error',
            position: 'top'
        })
        return
    }
    loadingForm.value = true
    try {
        if (modal.value.mode==='edit') {
            await entidadService.updateEntidades(formData.value.id, data)
        } else {
            await entidadService.createEntidades(data)
        }
        $q.notify({
          color: 'positive',
          message: 'Acción realiad con exito',
          icon: 'check',
          position: 'top'
        })
        modal.value.show = false
        await cargarDatos()
    } catch (error) {
      const messages = Object.values(error.response?.data?.errors).flat();
      $q.notify({
        color: 'negative',
        message: messages ? messages : 'Ocurrio un error',
        icon: 'error',
        position: 'top',
      })
    } finally {
      editingEntidadId.value = null
      loadingCreate.value = false
      loadingForm.value = false
    }
}
const handleClouse = () => {
  formData.value = {}
  modal.value.show = false
  modal.value.mode = ''
  modal.value.title = ''
  loadingCreate.value = false
  editingEntidadId.value = null
}
const verDetalles=(data)=>{
    console.log(data)
}
const confirmarEliminar=(data)=>{
    if(!auth.isAdmin) return
    selectedEntidad.value=data
    confirmDialog.value=true
}
const eliminarEntidad=async()=>{
    if(!auth.isAdmin) return
    loadingDelete.value[selectedEntidad.value.id] = true
    try {
        await entidadService.deleteEntidades(selectedEntidad.value.id)
        await cargarDatos()
        $q.notify({
            type:'positive',
            message: 'Unidad de alta dirección/entidad eliminada correctamente',
            icon: 'check',
            position: 'top'
        })
    } catch (error) {
        console.error(error)
        $q.notify({
          type: 'negative',
          message: 'Error al eliminar la unidad de alta dirección/entidad',
          icon: 'error',
          position: 'top'
        })
    } finally {
        loadingDelete.value[selectedEntidad.value.id] = false
        confirmDialog.value = false
    }
}
onMounted(() => {
    if(auth.isAdmin) {
        cargarDatos()
    }
})
</script>