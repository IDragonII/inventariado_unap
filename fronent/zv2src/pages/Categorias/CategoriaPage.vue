<template>
  <q-page padding>
    <PermissionsAccess :isAdmin="auth.isAdmin">
      <HeaderComponents
        title="Gestión de Categorías"
        :isPermissions="auth.isAdmin"
        v-model:filter="filter"
        :loadingCreate="loadingCreate"
        createButtonLabel="Nueva Categoría"
        @open-dialog="openDialog"
      />
      <q-card>
        <q-card-section>
          <TableDynamic
            :columns="columns"
            :row="categorias"
            row-key="id"
            :filter="filter"
            :pagination="pagination"
            :loading="loading"
            @update:pagination="onPagination"
            @filter="onFilterChange"
          >
            <template #body-cell-estado="props">
              <q-td :props="props">
                <q-badge :color="props.row.estado === 'activo' ? 'positive' : 'negative'">
                  {{ props.row.estado === 'activo' ? 'Activo' : 'Excluido' }}
                </q-badge>
              </q-td>
            </template>
            <template #body-cell-actions="props">
              <q-td :props="props">
                <q-btn flat dense round icon="visibility" color="info" @click="verDetalles(props.row)">
                  <q-tooltip>Ver detalles</q-tooltip>
                </q-btn>
                <q-btn
                  v-if="auth.isAdmin"
                  flat dense round
                  :icon="editingCategoriaId === props.row.id ? 'hourglass_empty' : 'edit'"
                  color="primary"
                  :loading="editingCategoriaId === props.row.id"
                  :disable="editingCategoriaId === props.row.id"
                  @click="openDialog(props.row)"
                >
                  <q-tooltip>{{ editingCategoriaId === props.row.id ? 'Editando...' : 'Editar' }}</q-tooltip>
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
        <DeleteComponents v-model:show="confirmDialog" @confirm="eliminarCategoria"/>
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
import { categoriaService } from 'src/services/categoriaService';

const $q = useQuasar();
const auth = useAuthStore();
const loadingCreate = ref(false);
const loading = ref(false);
const loadingDelete = ref([]);
const loadingForm = ref(false);
const filter = ref('');
const categorias = ref([]);
const filterTimout = ref(null);
const editingCategoriaId = ref(null);
const selectedCategoria = ref('');
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
  { name: 'codigo', label: 'CÓDIGO', field: 'codigo', align: 'left', sortable: true },
  { name: 'denominacion', label: 'DENOMINACIÓN', field: 'denominacion', align: 'left', sortable: true },
  { name: 'grupo', label: 'GRUPO', field: 'grupo', align: 'left' },
  { name: 'clase', label: 'CLASE', field: 'clase', align: 'left' },
  { name: 'resolucion', label: 'RESOLUCIÓN', field: 'resolucion', align: 'left' },
  { name: 'estado', label: 'ESTADO', field: 'estado', align: 'left' },
  { name: 'actions', label: 'ACCIONES', field: 'actions', align: 'center' }
];
const estadoOptions = [
  { label: 'Activo', value: 'activo' },
  { label: 'Excluido', value: 'excluido' }
];

const formFields = ref([
  { type: 'separator', label: 'Datos de Categoría' },
  { name: 'codigo', type: 'text', label: 'Código', rules: [val => !!val || 'El campo es requerido'], prepend: 'badge', autogrow: true, uppercase: true },
  { name: 'denominacion', type: 'text', label: 'Denominación', rules: [val => !!val || 'El campo es requerido'], prepend: 'category', autogrow: true, uppercase: true },
  { name: 'grupo', type: 'text', label: 'Grupo', rules: [val => !!val || 'El grupo es requerido'], prepend: 'group', autogrow: true, uppercase: true },
  { name: 'clase', type: 'text', label: 'Clase', rules: [val => !!val || 'La clase es requerida'], prepend: 'class', autogrow: true, uppercase: true },
  { name: 'resolucion', type: 'text', label: 'Resolución', rules: [val => !!val || 'El campo es requerido'], prepend: 'gavel', autogrow: true, uppercase: true },
  { name: 'estado', type: 'select', label: 'Estado', placeholder: 'Seleccione estado', rules: [val => !!val || 'El estado es requerido'], options: estadoOptions, prepend: 'check_circle' }
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
  if (!auth.isAdmin) return;
  loading.value = true;
  try {
    const res = await categoriaService.getCategorias({
      page: pagination.value.page,
      per_page: pagination.value.rowsPerPage,
      search: filter.value,
      sort_by: pagination.value.sortBy,
      desc: pagination.value.descending
    });
    categorias.value = res.data;
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

const openDialog = (categoria = null) => {
  if (!auth.isAdmin) {
    $q.notify({
      color: 'negative',
      message: 'No tienes permisos para esta acción',
      icon: 'error'
    });
    return;
  }
  try {
    if (categoria) {
      editingCategoriaId.value = categoria.id;
      formData.value = { ...categoria };
      modal.value.mode = 'edit';
      modal.value.title = 'Editar Categoría';
    } else {
      loadingCreate.value = true;
      formData.value = {};
      modal.value.mode = 'create';
      modal.value.title = 'Crear nueva Categoría';
    }
    modal.value.show = true;
    loadingCreate.value = false;
    editingCategoriaId.value = null;
  } catch (error) {
    console.error(error);
  }
};

const handleFormSubmit = async (data) => {
  if (!auth.isAdmin) {
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
    data.estado=data.estado.value
    if (modal.value.mode === 'edit') {
      await categoriaService.updateCategoria(formData.value.id, data);
    } else {
      await categoriaService.createCategoria(data);
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
    editingCategoriaId.value = null;
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
  editingCategoriaId.value = null;
};

const verDetalles = (data) => {
  console.log(data);
};
const confirmarEliminar = (data) => {
  if (!auth.isAdmin) return;
  selectedCategoria.value = data;
  confirmDialog.value = true;
};
const eliminarCategoria = async () => {
  if (!auth.isAdmin) return;
  loadingDelete.value[selectedCategoria.value.id] = true;
  try {
    await categoriaService.deleteCategoria(selectedCategoria.value.id);
    await cargarDatos();
    $q.notify({
      type: 'positive',
      message: 'Categoría eliminada correctamente',
      icon: 'check',
      position: 'top'
    });
  } catch (error) {
    console.error(error);
    $q.notify({
      type: 'negative',
      message: 'Error al eliminar la categoría',
      icon: 'error',
      position: 'top'
    });
  } finally {
    loadingDelete.value[selectedCategoria.value.id] = false;
    confirmDialog.value = false;
  }
};
onMounted(() => {
  if (auth.isAdmin) {
    cargarDatos();
  }
});
</script>
