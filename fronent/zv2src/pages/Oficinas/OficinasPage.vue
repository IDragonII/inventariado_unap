<template>
    <q-page padding="">
        <PermissionsAccess :isAdmin="auth.isAdmin" :isSupervisor="auth.isSupervisor">
            <HeaderComponents
            title="Gestion de Oficinas"
            :isPermissions="auth.isAdmin || auth.isSupervisor"
            v-model:filter="filter"
            :loadingCreate="loadingCreate"
            createButtonLabel="Nueva Oficina"
            @open-dialog="openDialog"
            />
            <q-card>
                <q-card-section>
                    <TableDynamic
                    :columns="columns"
                    :row="oficinas"
                    row-key="id"
                    :filter="filter"
                    :pagination="pagination"
                    :loading="loading"
                    @update:pagination="onPagination"
                    @filter="onFilterChange"
                    >
                        <template #body-cell-actions="props">
                            <q-td :props="props">
                              <q-btn 
                                v-if="auth.isAdmin || auth.isSupervisor"
                                flat dense round icon="visibility" color="info" @click="verDetalles(props.row)">
                                <q-tooltip>Ver detalles</q-tooltip>
                              </q-btn>
                              <q-btn 
                                v-if="auth.isAdmin"
                                flat dense round 
                                :icon="editingOficinaId === props.row.id ? 'hourglass_empty' : 'edit'" 
                                color="primary" 
                                :loading="editingOficinaId === props.row.id"
                                :disable="editingOficinaId === props.row.id"
                                @click="openDialog(props.row)">
                                <q-tooltip>{{ editingOficinaId === props.row.id ? 'Editando...' : 'Editar' }}</q-tooltip>
                              </q-btn>
                              <q-btn 
                                v-if="auth.isAdmin"
                                flat dense round 
                                :icon="loadingDelete[props.row.id] ? 'hourglass_empty' : 'delete'"
                                color="negative" 
                                :loading="loadingDelete[props.row.id]"
                                :disable="loadingDelete[props.row.id]"
                                @click="confirmarEliminar(props.row)">
                                <q-tooltip>{{ loadingDelete[props.row.id] ? 'Eliminando...' : 'Eliminar' }}</q-tooltip>
                              </q-btn>
                            </q-td>
                        </template>
                    </TableDynamic>
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
                </q-card-section>
            </q-card>
            <DeleteComponents v-model:show="confirmDialog" @confirm="eliminarOficina"/>
        </PermissionsAccess>
    </q-page>
</template>
<script setup>
import { computed, onMounted, ref } from 'vue';
import { useQuasar } from 'quasar';
import PermissionsAccess from 'src/components/Permissions/PermissionsAccess.vue';
import { useAuthStore } from 'src/stores/auth-store';
import HeaderComponents from 'src/components/components/HeaderComponents.vue';
import TableDynamic from 'src/components/TableDynamic.vue';
import DialogModal from 'src/components/DialogModal.vue';
import DynamicForm from 'src/components/DynamicForm.vue';
import { oficinaService } from 'src/services/oficinaService';
import { entidadService } from 'src/services/entidadService'
import DeleteComponents from 'src/components/components/DeleteComponents.vue';

const $q=useQuasar()
const auth=useAuthStore()
const filter=ref('')
const loadingCreate=ref(false)
const oficinas=ref([])
const loading=ref(false)
const loadingForm=ref(false)
const loadingDelete=ref([])
const formData=ref([])
const editingOficinaId=ref(null)
const filterTimout=ref('')
const confirmDialog=ref(false)
const selectedOficina=ref('')
const columns = [
  { name: 'denominacion', label: 'DENOMINACION', field: 'denominacion', align: 'left', sortable: true },
  { name: 'codigo', label: 'CÓDIGO', field: 'codigo', align: 'left' },
  { name: 'entidad', label: 'Gestión de Unidades de Alta Dirección/Entidad', field: row => row.entidad?.denominacion, align: 'left' },
  { name: 'actions', label: 'Acciones', field: 'actions', align: 'center' }
]
const modernFormFields = ref([
  { type: 'separator', label: 'Datos de Oficina' },
  { name: 'denominacion', type: 'text', label: 'Denominación', rules: [val => !!val || 'El campo es requerido'], prepend: 'business', autogrow: true, uppercase: true },
  { name: 'codigo', type: 'text', label: 'codigo de Oficina', rules: [val => !!val || 'El campo es requerido'], prepend: 'qr_code', autogrow: true, uppercase: true },
  { name: 'entidad_id', type: 'select', label: 'Gestión de Unidades de Alta Dirección/Entidad', placeholder: 'Seleccione una unidad de alta dirección/entidad', rules: [val=>!!val || 'La unidad de alta dirección/entidad es requerida'], options: [], prepend: 'school'}
])
const formLoading=computed(()=>{
    if(modal.value.mode==='edit'){
        return editingOficinaId.value===formData.value.id
    }
    return loadingCreate.value
})
const modal=ref({
    show: false,
    title: '',
    mode: ''
})
const pagination = ref({
    sortBy: 'id',
    descending: false,
    page: 1,
    rowsPerPage: 20,
    rowsNumber: 0
})
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
const cargarDatos=async()=>{
    if(!auth.isAdmin && !auth.isSupervisor) return
    loading.value=true
    try {
        const res=await oficinaService.getOficinas({
            page: pagination.value.page,
            per_page: pagination.value.rowsPerPage,
            search: filter.value,
            sort_by: pagination.value.sortBy,
            desc: pagination.value.descending
        })
        oficinas.value=res.data
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
const openDialog=async(oficina=null)=>{
    if(!auth.isAdmin && !auth.isSupervisor) {
        $q.notify({
            color: 'negative',
            message: 'No tienes permisos para esta acción',
            icon: 'error',
            position: 'top'
        })
        return
    }
    try {
        const ofi = await entidadService.getEntidades()
        const ofiFormateadas=ofi.data.map(fac=>({
            label:fac.denominacion,
            value:fac.id
        }))
        modernFormFields.value.find(f=>f.name==='entidad_id').options=ofiFormateadas
        if(oficina){
            console.log(oficina)
            editingOficinaId.value=oficina.id
            formData.value={...oficina,
                entidad_id: {
                    label: oficina.entidad.denominacion,
                    value: oficina.entidad.id
                }
            }
            modal.value.mode='edit'
            modal.value.title='Editar Oficina'
        } else {
            loadingCreate.value=true
            formData.value={}
            modal.value.mode='create'
            modal.value.title='Crear nueva Oficina'
        }
        modal.value.show=true
        loadingCreate.value=false
        editingOficinaId.value=null
        
    } catch (error) {
        console.error(error)
        $q.notify({
            color: 'negative',
            message: 'Error',
            icon: 'error',
            position: 'top'
        })
    }
}
const handleFormSubmit=async(data)=>{
    loadingForm.value=true
    if(!auth.isAdmin&&!auth.isSupervisor){
        $q.notify({
            color:'negative',
            message:'Notienes permiso para esta accions',
            icon: 'error',
            position:'top'
        })
        return
    }
    data.entidad_id=data.entidad_id.value
    try {
        if(modal.value.mode==='edit'){
            editingOficinaId.value=formData.value.id
            await oficinaService.updateOficina(formData.value.id, data)
            $q.notify({
                color: 'positive',
                message:'Oficina creado exitosamente',
                icon: 'check',
                position: 'top'
            })
        } else {
            await oficinaService.createOficina(data)
            $q.notify({
                color: 'positive',
                message: 'Oficina creado exitosamente',
                icon: 'check',
                position: 'top'
            })
        }
        modal.value.show=false
        await cargarDatos()
    } catch (error) {
      const messages = Object.values(error.response?.data?.errors).flat();
      $q.notify({
        color: 'negative',
        message: messages ? messages : 'Error al guardar Oficina',
        icon: 'error',
        position: 'top'
      })
    } finally {
      editingOficinaId.value = null
      loadingCreate.value = false
      loadingForm.value=false
    }
}
const handleClouse=()=> {
    modal.value.show=false
    modal.value.title=''
    modal.value.mode=''
    loadingCreate.value=false
    editingOficinaId.value=null
}
const verDetalles=(data)=>{
    console.log(data)
}
const confirmarEliminar=async(data)=>{
    if(!auth.isAdmin) return
    selectedOficina.value=data
    confirmDialog.value=true
}
const eliminarOficina=async()=>{
    loadingDelete.value[selectedOficina.value.id] = true
    try {
        await oficinaService.deleteOficina(selectedOficina.value.id)
        await cargarDatos()
        $q.notify({
            type:'positive',
            message: 'Oficina eliminada correctamente',
            icon: 'check',
            position: 'top'
        })
    } catch (error) {
        console.error(error)
        $q.notify({
          type: 'negative',
          message: 'Error al eliminar Oficina',
          icon: 'error',
          position: 'top'
        })
    } finally {
        loadingDelete.value[selectedOficina.value.id] = false
        confirmDialog.value = false
    }
}
onMounted(() => {
    if(auth.isAdmin || auth.isSupervisor){
        cargarDatos()
    }
})
</script>