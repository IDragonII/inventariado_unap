<template>
  <q-page padding>
    <PermissionsAccess :isAdmin="auth.isAdmin" :is-responsable="auth.isResponsable">
      <HeaderComponents
        title="Gestión de Áreas"
        :isPermissions="auth.isAdmin||auth.isResponsable"
        v-model:filter="filter"
        :loadingCreate="loadingCreate"
        createButtonLabel="Nueva Área"
        @open-dialog="openDialog"
      />
      <q-card>
        <q-card-section>
          <TableDynamic
            :columns="columns"
            :row="areas"
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
                  <q-tooltip>Ver detalles</q-tooltip>
                </q-btn>
                <q-btn
                  v-if="auth.isAdmin || auth.isResponsable"
                  flat dense round
                  :icon="editingAreaId === props.row.id ? 'hourglass_empty' : 'edit'"
                  color="primary"
                  :loading="editingAreaId === props.row.id"
                  :disable="editingAreaId === props.row.id"
                  @click="openDialog(props.row)"
                >
                  <q-tooltip>{{ editingAreaId === props.row.id ? 'Editando...' : 'Editar' }}</q-tooltip>
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
            :fields="formFields"
            v-model="formData"
            :mode="modal.mode"
            @submit="handleFormSubmit"
            @cancel="handleClouse"
            :loading="formLoading"
          />
        </DialogModal>
        <DeleteComponents v-model:show="confirmDialog" @confirm="eliminarArea"/>
      </q-card>
    </PermissionsAccess>
  </q-page>
</template>
<script setup>
import { ref, computed, onMounted } from 'vue';
import { useQuasar } from 'quasar';
import PermissionsAccess from 'src/components/Permissions/PermissionsAccess.vue';
import { useAuthStore } from 'src/stores/auth-store';
import HeaderComponents from 'src/components/components/HeaderComponents.vue';
import TableDynamic from 'src/components/TableDynamic.vue';
import DialogModal from 'src/components/DialogModal.vue';
import DynamicForm from 'src/components/DynamicForm.vue';
import DeleteComponents from 'src/components/components/DeleteComponents.vue';
import { areaService } from 'src/services/areaService';
import { oficinaService } from 'src/services/oficinaService';
import { authService } from 'src/services/authService';

const $q = useQuasar();
const auth = useAuthStore();
const loadingCreate = ref(false);
const loading = ref(false);
const loadingDelete = ref([]);
const loadingForm = ref(false);
const filter = ref('');
const areas = ref([]);
const filterTimout = ref(null);
const editingAreaId = ref(null);
const selectedArea = ref('');
const confirmDialog = ref(false);
const formData = ref({});
const pagination = ref({
  sortBy: 'id',
  descending: false,
  page: 1,
  rowsPerPage: 20,
  rowsNumber: 0
});
const modal = ref({
  show: false,
  title: '',
  mode: ''
});

const columns = [
  { name: 'edificio', label: 'EDIFICIO', field: 'edificio', align: 'left', sortable: true},
  { name: 'codigo', label: 'CÓDIGO', field: 'codigo', align: 'left' },
  { name: 'piso', label: 'PISO', field: 'piso', align: 'left'},
  { name: 'aula', label: 'AULA', field: 'aula', align: 'left' },
  { name: 'oficina_id', label: 'OFICINA', field: row => row.oficina?.denominacion, align: 'left'},
  { name: 'actions', label: 'ACCIONES', field: 'actions', align: 'center' }
];

const formFields = ref([
  { type: 'separator', label: 'Datos de Área' },
  { name: 'edificio', type: 'text', label: 'Edificio', rules: [val => !!val || 'El campo es requerido'], prepend: 'apartment', autogrow: true, uppercase: true },
  { name: 'codigo', type: 'text', label: 'Código', rules: [val => !!val || 'El campo es requerido'], prepend: 'badge', autogrow: true, uppercase: true },
  { name: 'piso', type: 'text', label: 'Piso', rules: [val => !!val || 'El campo es requerido'], prepend: 'stairs', autogrow: true, uppercase: true },
  { name: 'aula', type: 'text', label: 'Aula', rules: [val => !!val || 'El campo es requerido'], prepend: 'meeting_room', autogrow: true, uppercase: true },
  { name: 'oficina_id', type: 'select', label: 'Oficina', placeholder: 'Seleccione una oficina', rules: [val => !!val || 'La oficina es requerida'], options: [], prepend: 'business' }
]);

const onPagination = (newPagination) => {
  pagination.value = { ...pagination.value, ...newPagination };
  cargarDatos();
};
const onFilterChange = () => {
  clearTimeout(filterTimout.value);
  filterTimout.value = setTimeout(() => {
    pagination.value.page = 1;
    cargarDatos();
  }, 500);
};
const formLoading = computed(() => loadingForm.value);

const cargarDatos = async () => {
  if (!(auth.isAdmin || auth.isResponsable)) return;
  loading.value = true;
  try {
    const res = await areaService.getAreas({
      page: pagination.value.page,
      per_page: pagination.value.rowsPerPage,
      search: filter.value,
      sort_by: pagination.value.sortBy,
      desc: pagination.value.descending
    });
    areas.value = res.data;
    pagination.value = {
      page: res.meta.current_page,
      rowsPerPage: res.meta.per_page,
      rowsNumber: res.meta.total
    };
  } catch (error) {
    console.error(error);
    $q.notify({
      color: 'negative',
      message: 'Error al cargar los datos',
      icon: 'error'
    });
  } finally {
    loading.value = false;
  }
};

const openDialog = async (area = null) => {
  if (!auth.isAdmin&&!auth.isResponsable) {
    $q.notify({
      color: 'negative',
      message: 'No tienes permisos para esta acción',
      icon: 'error'
    });
    return;
  }
  try {
    const res = await oficinaService.getOficinas();
    const oficinasOptions = res.data.map(ofi => ({ label: ofi.denominacion, value: ofi.id }));
    formFields.value.find(f => f.name === 'oficina_id').options = oficinasOptions;
    if(auth.isResponsable){
      let userOficina=await authService.getCurrentUser()
      let userOficinaFormateada=userOficina.oficinas.map(ofe=>({
        label: ofe.denominacion,
        value: ofe.id
      }))
      formFields.value.find(f=>f.name===('oficina_id')).options=userOficinaFormateada
    }
    if (area) {
      editingAreaId.value = area.id;
      formData.value = { ...area,
        oficina_id: {
            label: area.oficina.denominacion,
            value: area.oficina.id
        }
       };
      modal.value.mode = 'edit';
      modal.value.title = 'Editar Área';
    } else {
      loadingCreate.value = true;
      formData.value = {};
      modal.value.mode = 'create';
      modal.value.title = 'Crear nueva Área';
    }
    modal.value.show = true;
    loadingCreate.value = false;
    editingAreaId.value=null
  } catch (error) {
    console.error(error);
  }
};

const handleFormSubmit = async (data) => {
  if (!auth.isAdmin && !auth.isResponsable) {
    $q.notify({
      color: 'negative',
      message: 'No tienes permiso para esta acción',
      icon: 'error',
      position: 'top'
    });
    return;
  }
  loadingForm.value = true;
  try {
    data.oficina_id=data.oficina_id.value
    if (modal.value.mode === 'edit') {
      await areaService.updateArea(formData.value.id, data);
    } else {
      await areaService.createArea(data);
    }
    $q.notify({
      color: 'positive',
      message: 'Acción realizada con éxito',
      icon: 'check',
      position: 'top'
    });
    modal.value.show = false;
    await cargarDatos();
  } catch (error) {
    const messages = Object.values(error.response?.data?.errors || {}).flat();
    $q.notify({
      color: 'negative',
      message: messages.length ? messages : 'Ocurrió un error',
      icon: 'error',
      position: 'top',
    });
  } finally {
    editingAreaId.value = null;
    loadingCreate.value = false;
    loadingForm.value = false;
  }
};

const handleClouse = () => {
  formData.value = {};
  modal.value.show = false;
  modal.value.mode = '';
  modal.value.title = '';
  loadingCreate.value = false;
  editingAreaId.value = null;
};

const verDetalles = (data) => {
  console.log(data);
};
const confirmarEliminar = (data) => {
  if (!auth.isAdmin) return;
  selectedArea.value = data;
  confirmDialog.value = true;
};
const eliminarArea = async () => {
  if (!auth.isAdmin) return;
  loadingDelete.value[selectedArea.value.id] = true;
  try {
    await areaService.deleteArea(selectedArea.value.id);
    await cargarDatos();
    $q.notify({
      type: 'positive',
      message: 'Área eliminada correctamente',
      icon: 'check',
      position: 'top'
    });
  } catch (error) {
    console.error(error);
    $q.notify({
      type: 'negative',
      message: 'Error al eliminar el área',
      icon: 'error',
      position: 'top'
    });
  } finally {
    loadingDelete.value[selectedArea.value.id] = false;
    confirmDialog.value = false;
  }
};
onMounted(() => {
  if (auth.isAdmin || auth.isResponsable) {
    cargarDatos();
  }
});
</script>
