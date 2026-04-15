<template>
  <q-page padding>
    <PermissionsAccess :isAdmin="auth.isAdmin" :is-supervisor="auth.isSupervisor">
      <HeaderComponents
        title="Gestión de Configuraciones"
        :isPermissions="auth.isAdmin"
        v-model:filter="filter"
        :loadingCreate="loadingCreate"
        createButtonLabel="Nueva Configuración"
        @open-dialog="openDialog"
      />
      <q-card>
        <q-card-section>
          <TableDynamic
            :columns="columns"
            :row="configuraciones"
            row-key="id"
            :filter="filter"
            :pagination="pagination"
            :loading="loading"
            @update:pagination="onPagination"
            @filter="onFilterChange"
          >
            <template #body-cell-mostrar_botones="props">
              <q-td :props="props">
                <q-toggle
                  v-model="props.row.mostrar_botones"
                  color="primary"
                  dense
                  @update:model-value="val => toggleMostrarBotones(props.row, val)"
                />
              </q-td>
            </template>
            <template #body-cell-actions="props">
              <q-td :props="props">
                <q-btn
                  flat
                  dense
                  round
                  icon="edit"
                  color="primary"
                  :loading="editingId === props.row.id"
                  :disable="editingId === props.row.id"
                  @click="openDialog(props.row)"
                >
                  <q-tooltip>{{ editingId === props.row.id ? 'Editando...' : 'Editar' }}</q-tooltip>
                </q-btn>
                <q-btn
                  flat
                  dense
                  round
                  icon="delete"
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
        <DeleteComponents v-model:show="confirmDialog" @confirm="eliminarConfiguracion" />
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
import { configuracionService } from 'src/services/configuracion';

const $q = useQuasar();
const auth = useAuthStore();
const loadingCreate = ref(false);
const loading = ref(false);
const loadingDelete = ref({});
const loadingForm = ref(false);
const filter = ref('');
const configuraciones = ref([]);
const filterTimout = ref(null);
const editingId = ref(null);
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
  { name: 'clave', label: 'CLAVE', field: 'clave', align: 'left' },
  { name: 'mostrar_botones', label: 'MOSTRAR BOTONES', field: 'mostrar_botones', align: 'center', sortable: true },
  { name: 'actions', label: 'ACCIONES', field: 'actions', align: 'center' }
];

const formFields = ref([
  { type: 'separator', label: 'Datos de Configuración' },
  { name: 'clave', type: 'text', label: 'Clave', rules: [val => !!val || 'El campo es requerido'], prepend: 'label', autogrow: true, uppercase: true },
  { name: 'mostrar_botones', type: 'toggle', label: 'Mostrar botones', trueLabel: 'Sí', falseLabel: 'No', color: 'primary' }
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
  //if (!auth.isAdmin) return;
  loading.value = true;
  try {
    const res = await configuracionService.list({
      page: pagination.value.page,
      per_page: pagination.value.rowsPerPage,
      search: filter.value,
      sort_by: pagination.value.sortBy,
      desc: pagination.value.descending
    });
    configuraciones.value = res.data;
    pagination.value = {
      page: res.meta?.current_page || 1,
      rowsPerPage: res.meta?.per_page || configuraciones.value.length,
      rowsNumber: res.meta?.total || configuraciones.value.length
    };
  } catch (error) {
    console.error(error);
    $q.notify({ color: 'negative', message: 'Error al cargar datos', icon: 'error' });
  } finally {
    loading.value = false;
  }
};

const openDialog = (config = null) => {
  if (!auth.isAdmin) return;
  if (config) {
    editingId.value = config.id;
    formData.value = { ...config };
    modal.value.mode = 'edit';
    modal.value.title = 'Editar Configuración';
  } else {
    editingId.value = null;
    formData.value = {};
    modal.value.mode = 'create';
    modal.value.title = 'Nueva Configuración';
  }
  modal.value.show = true;
};

const handleFormSubmit = async (data) => {
  loadingForm.value = true;
  try {
    if (modal.value.mode === 'edit') {
      await configuracionService.update(editingId.value, data);
      $q.notify({ color: 'positive', message: 'Configuración actualizada', icon: 'check' });
    } else {
      await configuracionService.create(data);
      $q.notify({ color: 'positive', message: 'Configuración creada', icon: 'check' });
    }
    modal.value.show = false;
    cargarDatos();
  } catch (e) {
    console.error(e);
    $q.notify({ color: 'negative', message: 'Error al guardar', icon: 'error' });
  } finally {
    loadingForm.value = false;
  }
};

const handleClouse = () => {
  modal.value.show = false;
};

const confirmarEliminar = (config) => {
  confirmDialog.value = true;
  editingId.value = config.id;
};

const eliminarConfiguracion = async () => {
  loadingDelete.value = { ...loadingDelete.value, [editingId.value]: true };
  try {
    await configuracionService.delete(editingId.value);
    $q.notify({ color: 'positive', message: 'Configuración eliminada', icon: 'check' });
    cargarDatos();
  } catch (e) {
    console.error(e);
    $q.notify({ color: 'negative', message: 'Error al eliminar', icon: 'error' });
  } finally {
    loadingDelete.value = { ...loadingDelete.value, [editingId.value]: false };
    confirmDialog.value = false;
  }
};

// quick toggle helper that flips flag and calls backend update
const toggleMostrarBotones = async (row, value) => {
  try {
    await configuracionService.update(row.id, { mostrar_botones: value });
    $q.notify({ color: 'positive', message: 'Cambio guardado', icon: 'check' });
    // optionally refresh list or just update local row (already changed by v-model)
  } catch (e) {
    console.error(e);
    $q.notify({ color: 'negative', message: 'Error al guardar cambio', icon: 'error' });
    // revert value
    row.mostrar_botones = !value;
  }
};

onMounted(() => {
  cargarDatos();
});
</script>
