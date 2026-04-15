<template>
  <q-page padding>
    <PermissionsAccess :is-admin="auth.isAdmin">
    <div class="row q-mb-md">
      <div class="col-12">
        <q-card>
          <q-card-section>
            <div class="text-h6">Gestión de usuarios</div>
          </q-card-section>
          <q-card-section>
            <div class="row q-col-gutter-md">
              <div class="col-12 col-md-4">
                <q-input
                  v-model="filter"
                  label="Buscar usuario"
                  dense
                  outlined
                  clearable
                >
                  <template v-slot:append>
                    <q-icon name="search" />
                  </template>
                </q-input>
              </div>
              <div class="col-12 col-md-8 text-right">
                <q-btn
                  v-if="auth.isAdmin || auth.isSoftware"
                  color="primary"
                  :icon="loadingCreate ? 'hourglass_empty' : 'add'"
                  :label="loadingCreate ? 'Creando...' : 'Nuevo Usuario'"
                  :loading="loadingCreate"
                  :disable="loadingCreate"
                  @click="openDialog()"
                />
              </div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>
    <q-tabs v-model="tab" class="text-primary q-md-md" align="justify" dense>
      <q-tab name="5" label="Inventariadores"/>
      <q-tab name="2" label="Supervisores"/>
      <q-tab name="3" label="Responsables"/>
      <q-tab name="4" label="Responsables Agregados"/>
      <q-tab name="7" label="Responsables Agregados"/>
    </q-tabs>
    <q-card>
      <q-card-section>
        <TableDynamic
        :columns="columns"
        :row="usuarios"
        row-key="id"
        :filter="filter"
        :loading="loading"
        :pagination="pagination"
        @update:pagination="onPagination"
        @filter="onFilterChange"
        >
          <template #body-cell-actions="props">
            <q-td :props="props">
              <q-btn 
                v-if="auth.isAdmin || auth.isSoftware"
                flat dense round 
                :icon="editingUserId === props.row.id ? 'hourglass_empty' : 'edit'" 
                color="primary" 
                :loading="editingUserId === props.row.id"
                :disable="editingUserId === props.row.id"
                @click="openDialog(props.row)">
                <q-tooltip>{{ editingUserId === props.row.id ? 'Editando...' : 'Editar' }}</q-tooltip>
              </q-btn>
              <q-btn 
                v-if="auth.isAdmin || auth.isSoftware"
                flat dense round 
                :icon="loadingDelete ? 'hourglass_empty' : 'delete'" 
                color="negative" 
                :loading="loadingDelete"
                :disable="loadingDelete"
                @click="confirmarEliminar(props.row)">
                <q-tooltip>{{ loadingDelete ? 'Eliminando...' : 'Eliminar' }}</q-tooltip>
              </q-btn>
            </q-td>
          </template>
        </TableDynamic>
      </q-card-section>
    </q-card>
    <DialogModal v-model:show="modal.show" :mode="modal.mode" :title="modal.title">
      <DynamicForm
        :fields = "modernFormFields"
        v-model = "formData"
        @submit="handleFormSubmit"
        :loading="formLoading"
        @cancel="handleClouse"
      />
    </DialogModal>

    <q-dialog v-model="deleteDialog">
      <q-card>
        <q-card-section class="row items-center">
          <q-avatar icon="warning" color="negative" text-color="white" />
          <span class="q-ml-sm">¿Está seguro de eliminar este usuario?</span>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="primary" v-close-popup />
          <q-btn flat label="Eliminar" color="negative" @click="deleteUsuario" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </PermissionsAccess>
  </q-page>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { userService } from 'src/services/userService'
import { oficinaService } from 'src/services/oficinaService'
import DialogModal from '../../components/DialogModal.vue'
import DynamicForm from 'src/components/DynamicForm.vue'
import TableDynamic from 'src/components/TableDynamic.vue'
import { useQuasar } from 'quasar'
import { useAuthStore } from 'src/stores/auth-store'
import PermissionsAccess from 'src/components/Permissions/PermissionsAccess.vue'

const $q = useQuasar()
const auth = useAuthStore()

const loading = ref(false)
const loadingCreate = ref(false)
const editingUserId = ref(null)
const loadingDelete = ref(false)
const usuarios = ref([])
const filter = ref('')
const formData = ref({})
const filterTimout = ref(null)
const deleteDialog = ref(false)
const selectedUsuario = ref({})
const tab=ref('5')
const modal = ref({
  show:false,
  mode: '',
  title: 'Nuevo'
})

const pagination = ref({
  sortBy: 'name',
  descending: false,
  page: 1,
  rowsPerPage: 10,
  rowsNumber: 0
})

const columns = [
  { name: 'name', label: 'Nombre', field: 'name', align: 'left', sortable: true },
  { name: 'dni', label: 'DNI', field: 'dni', align: 'left'},
  { name: 'email', label: 'Email', field: 'email', align: 'left', sortable: true },
  { name: 'telefono', label: 'Teléfono', field: 'telefono', align: 'left' },
  ...((auth.isAdmin) ? [
  { name: 'oficinas', label: 'Oficinas', field: row => Array.isArray(row.oficinas) ? row.oficinas.map(o => o.denominacion).join(', ') : (row.oficina?.denominacion || ''), align: 'left' },
  ]: []),
  { name: 'roles', label: 'Roles', field: row=>row.role?.name , align: 'left' },
  ...((auth.isAdmin)?[ 
  { name: 'grupo', label: 'Grupo', field: row=>row.grupo, align: 'left'}
  ]:[]),
  { name: 'actions', label: 'Acciones', align: 'center' }
]

const modernFormFields = ref([
  { type: 'separator', label: 'Datos Personales'},
  { name: 'dni', type: 'text', label: 'Numero de DNI del usuario', rules: [val => !!val || 'El campo es requerido', val => /^\d{8}$/.test(val) || 'El DNI debe tener 8 dígitos numéricos'], prepend: 'badge', autogrow: true, maxlength: 8},
  { name: 'name', type: 'text', label: 'Nombre del usuario', rules: [val => !!val || 'El nombre de usuario es obligatorio'], prepend: 'account_circle', autogrow: true, uppercase: true},
  { name: 'email', type: 'email', label: 'Correo electrónico institucional', rules: [val => !!val || 'El correo electrónico es obligatorio', val => /^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/.test(val) || 'Debe ser un correo electrónico válido'], prepend: 'email', autogrow: true},
  //{ name: 'email', type: 'email', label: 'Correo electrónico institucional', rules: [val => !!val || 'El correo electrónico es obligatorio', val => /unap\.edu\.pe$/.test(val) || 'Debe ser un correo institucional (@unap.edu.pe)'], prepend: 'email', autogrow: true},
  { name: 'telefono', type: 'number', label: 'Numero de telefono', rules: [val => !!val || 'El número de teléfono es obligatorio', val => /^\d{9}$/.test(val) || 'El número debe tener 9 dígitos numéricos'], prepend: 'phone', autogrow: true},
  { type: 'separator', label: '⚙️ Configuración de Usuario'},
  ...((auth.isAdmin)?[
  { name: 'oficinas', type: 'select', label: 'Oficinas', multiple: true, rules: [val => val && val.length > 0 || 'Debe seleccionar al menos una oficina'], options: [], prepend: 'business' },
  ]:[]),
  { name:'role', type: 'select', label: 'Rol', rules: [val => !!val || 'El campo es obligatorio'], options: [], prepend: 'security'},
  ...((auth.isAdmin)?[
  { name:'grupo', type: 'select', label: 'Grupo', options: [{ label: 'A', value: 'A' },{ label: 'B', value: 'B' },{ label: 'C', value: 'C' },{ label: 'D', value: 'D' },{ label: 'E', value: 'E' },{ label: 'F', value: 'F' },{ label: 'G', value: 'G' },{ label: 'H', value: 'H' },{ label: 'I', value: 'I' },{ label: 'J', value: 'J' },{ label: 'K', value: 'K' },{ label: 'L', value: 'L' },{ label: 'M', value: 'M' },{ label: 'N', value: 'N' },{ label: 'O', value: 'O' },{ label: 'P', value: 'P' },{ label: 'Q', value: 'Q' },{ label: 'R', value: 'R' },{ label: 'S', value: 'S' },{ label: 'T', value: 'T' },{ label: 'U', value: 'U' },{ label: 'V', value: 'V'},{ label: 'W', value: 'W'},{ label: 'X', value: 'X' },{ label: 'Y', value: 'Y'},{ label: 'Z', value: 'Z'},{ label: '1', value: '1'},{ label: '2', value: '2'},{ label: '3', value: '3'},{ label: '4', value: '4'},{ label: '5', value: '5'},{ label: '6', value: '6'},{ label: '7', value: '7'},{ label: '8', value: '8'},{ label: '9', value: '9'}], prepend: 'security'},
  ]:[]),
  { type: 'separator', label: '🔐 Configuración de Contraseña'},
  { name: 'updatePassword', type: 'checkbox', label: 'Actualizar contraseña', color: 'primary'}
])

const formLoading = computed(() => {
  if (modal.value.mode === 'edit') {
    return editingUserId.value === formData.value.id
  }
  return loadingCreate.value
})

const onPagination = (newPagination) => {
  pagination.value = { ... pagination.value, ...newPagination }
  loadData()
}

const onFilterChange = () => {
  clearTimeout(filterTimout.value)
  filterTimout.value=setTimeout(()=>{
    pagination.value.page=1
    loadData()
  },500)
}

const loadData = async () => {
  loading.value = true
  try {
    const params = {
      search: filter.value,
      usuario_type: tab.value,
      page: pagination.value.page,
      per_page: pagination.value.rowsPerPage,
      sort_by: pagination.value.sortBy,
      desc: pagination.value.descending
    }
    const response = await userService.getUsuarios(params)
    usuarios.value = response.data || response
    pagination.value = {
      page: response.meta.current_page,
      rowsPerPage: response.meta.per_page,
      rowsNumber: response.meta.total
    }
  } catch (error) {
    console.error(error)
    usuarios.value = []
    pagination.value.rowsNumber = 0
  } finally {
    loading.value = false
  }
}

const openDialog = async (usuario = null) => {
  if (!auth.isAdmin && !auth.isSupervisor && !auth.isSoftware) {
    $q.notify({
      color: 'negative',
      message: 'No tienes permisos para esta acción',
      icon: 'error'
    })
    return
  }

  if (usuario) {
    editingUserId.value = usuario.id
  } else {
    loadingCreate.value = true
  }

  try {
    if(auth.isAdmin){
      const response = await oficinaService.getOficinas()
      const oficinas = response.data
      const oficinasFormateadas = oficinas.map(dep => ({ label: dep.denominacion, value: dep.id }))
      modernFormFields.value.find(f => f.name === 'oficinas').options = oficinasFormateadas
    }
    const roles = await userService.getRoles()
    const rolesFormateados = roles.data.map(role => ({
      label: role.name,
      value: role.id
    }))
    modernFormFields.value.find(f => f.name === 'role').options = rolesFormateados
    
    // Mostrar/ocultar campo de actualizar password según el modo
    const updatePasswordField = modernFormFields.value.find(f => f.name === 'updatePassword')
    if (updatePasswordField) {
      updatePasswordField.disabled = !usuario // Solo habilitado en modo edición
    }
    
    if(usuario){
      formData.value = {
        ...usuario,
        role: {
          label: usuario.role?.name,
          value: usuario.role?.id
        },
        //oficinas: Array.isArray(usuario.oficinas) ? usuario.oficinas.map(of => ({ label: of.denominacion, value: of.id })) : [],
        ...(auth.isAdmin ? {
          oficinas: Array.isArray(usuario.oficinas) ? usuario.oficinas.map(of => ({ label: of.denominacion, value: of.id })) : []
        } : {}),
        updatePassword: false
      }
      modal.value.title = "Actualizar Usuario"
      modal.value.mode = "edit"
    }else {
      formData.value = {}
      modal.value.title = "Crear Nuevo Usuario"
      modal.value.mode = "create"
    }
    modal.value.show = true
  }catch(error){
    console.error(error)
    throw error
  } finally {
    // Desactivar estados de carga
    editingUserId.value = null
    loadingCreate.value = false
  }
}

const handleFormSubmit = async (date) => {
  if (modal.value.mode === 'edit' && !auth.isAdmin && !auth.isSupervisor &&!auth.isSoftware) {
    $q.notify({
      color: 'negative',
      message: 'No tienes permisos para editar usuarios',
      icon: 'error'
    })
    return
  }
  
  if (modal.value.mode === 'create' && !auth.isAdmin && !auth.isSupervisor &&!auth.isSoftware) {
    $q.notify({
      color: 'negative',
      message: 'No tienes permisos para crear usuarios',
      icon: 'error'
    })
    return
  }

  if (modal.value.mode === 'edit') {
    editingUserId.value = formData.value.id
  } else {
    loadingCreate.value = true
  }

  try {
    const submitdate = {
      ...date,
      telefono: `${date.telefono}`,
      //oficinas: Array.isArray(date.oficinas) ? date.oficinas.map(of => of.value) : [],
      ...(auth.isAdmin ? { oficinas: Array.isArray(date.oficinas) ? date.oficinas.map(of => of.value) : [] } : {}),
      role_id: date.role.value,
      grupo: date.grupo?.value
    }
    if (modal.value.mode === 'edit') {
      if (date.updatePassword) {
        submitdate.password = 'Unap@'+date.dni
        submitdate.password_confirmation = 'Unap@'+date.dni
      }
      await userService.updateUsuario(formData.value.id, submitdate)
      $q.notify({
        color: 'positive',
        message: 'Usuario actualizado',
        icon: 'check'
      })
    } else {
      submitdate.password = date.dni
      submitdate.email_verified_at = formatoFecha();
        if(auth.isSupervisor){
          submitdate.oficina_id=['8']
          console.log('here')
        }
        await userService.createUsuario(submitdate)
        $q.notify({
          color: 'positive',
          message: 'Usuario creado correctamente',
          icon: 'check'
        })
    }
    await loadData()
    modal.value.show = false
  } catch (error) {
    const messages = Object.values(error.response?.data?.errors).flat();
    $q.notify({
      color: 'negative',
      message: messages ? messages : 'Error al guardar Usuario',
      icon: 'error'
    })
  } finally {
    // Desactivar estados de carga
    editingUserId.value = null
    loadingCreate.value = false
  }
}
function formatoFecha(fecha = new Date()) {
  const pad = n => n.toString().padStart(2, '0');
  return (
    fecha.getFullYear() + '-' +
    pad(fecha.getMonth() + 1) + '-' +
    pad(fecha.getDate()) + ' ' +
    pad(fecha.getHours()) + ':' +
    pad(fecha.getMinutes()) + ':' +
    pad(fecha.getSeconds())
  );
}
const handleClouse = () => {
  formData.value = {}
  modal.value.show = false
  modal.value.mode = ''
  modal.value.title = ''
  // Limpiar estados de carga
  loadingCreate.value = false
  editingUserId.value = null
  
  // Habilitar el campo de actualizar password para futuras ediciones
  const updatePasswordField = modernFormFields.value.find(f => f.name === 'updatePassword')
  if (updatePasswordField) {
    updatePasswordField.disabled = false
  }
}

const confirmarEliminar = async (usuario) => {
  // Verificar permisos antes de eliminar
  if (!auth.isAdmin && !auth.isSoftware) {
    $q.notify({
      color: 'negative',
      message: 'No tienes permisos para eliminar usuarios',
      icon: 'error'
    })
    return
  }

  selectedUsuario.value = usuario
  deleteDialog.value = true
}

const deleteUsuario = async () => {
  if (!auth.isAdmin && !auth.isSoftware) {
    $q.notify({
      color: 'negative',
      message: 'No tienes permisos para eliminar usuarios',
      icon: 'error'
    })
    return
  }

  loadingDelete.value = true

  try {
    await userService.deleteUsuario(selectedUsuario.value.id)
    $q.notify({
      color: 'positive',
      message: 'Usuario eliminado correctamente',
      icon: 'check'
    })
    await loadData()
    deleteDialog.value = false
  } catch (error) {
    console.error(error)
    $q.notify({
      color: 'negative',
      message: 'Error al eliminar usuario',
      icon: 'error'
    })
  } finally {
    loadingDelete.value = false
  }
}
loadData()
watch(tab, () => {
  if(tab.value==='5' || tab.value==='2' || tab.value==='4'|| tab.value==='3'|| tab.value==='7'){
    loadData()
    console.log(tab.value)
  }
})
</script> 