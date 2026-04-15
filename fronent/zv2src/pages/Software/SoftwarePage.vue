<template>
  <q-page class="q-pa-lg">
    <PermissionsAccess :is-software="auth.isSoftware">
    <!-- Tabla dinámica -->
    <TableDynamic
      :columns="columns"
      :row="softwares"
      :loading="loading"
      :pagination="pagination"
      @update:pagination="onPagination"
      row-key="id"
      title="Activos de Software"
    >
      <template #top>
        <q-input dense debounce="300" v-model="filter" placeholder="Buscar..." class="q-mr-md" style="width: 300px;">
          <template v-slot:append>
            <q-icon name="search" />
          </template>
        </q-input>
        <q-space />
        <q-btn color="primary" icon="add" label="Nuevo Activo" @click="openCreateModal" />
      </template>

      <template #body-cell-tipo="props">
        <q-td :props="props">
          <q-chip
            :color="getTipoChip(props.row.tipo).color"
            :icon="getTipoChip(props.row.tipo).icon"
            text-color="white"
            dense
            square
          >
            {{ getTipoChip(props.row.tipo).label }}
          </q-chip>
        </q-td>
      </template>

      <template #body-cell-estado="props">
        <q-td :props="props">
          <q-chip
            :color="getEstadoChip(props.row.estado).color"
            text-color="white"
            dense
            square
            class="text-capitalize"
          >
            {{ props.row.estado }}
          </q-chip>
        </q-td>
      </template>

      <template #body-cell-actions="props">
        <q-td :props="props" class="q-gutter-xs">
          <q-btn dense round flat color="primary" icon="edit" @click="openEditModal(props.row)"></q-btn>
          <q-btn dense round flat color="negative" icon="delete" @click="confirmDelete(props.row)"></q-btn>
        </q-td>
      </template>
    </TableDynamic>

    <!-- Modal para Crear/Editar -->
    <DialogModal v-model:show="modal.show" :title="modal.title" :mode="modal.mode">
      <DynamicForm
        :fields="formFields"
        v-model="formData"
        :mode="modal.mode"
        :loading="loadingForm"
        @submit="handleFormSubmit"
        @cancel="modal.show = false"
      />
    </DialogModal>
  </PermissionsAccess>
  </q-page>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { softwareService } from 'src/services/softwareService';
import TableDynamic from 'src/components/TableDynamic.vue';
import DialogModal from 'src/components/DialogModal.vue';
import DynamicForm from 'src/components/DynamicForm.vue';
import { useSoftwareForm } from './formFields';
import { useQuasar } from 'quasar';
import PermissionsAccess from 'src/components/Permissions/PermissionsAccess.vue';
import { useAuthStore } from 'src/stores/auth-store';

const auth=useAuthStore();
const $q = useQuasar();
const softwares = ref([]);
const loading = ref(false);
const loadingForm = ref(false);
const pagination = ref({
  sortBy: 'id',
  descending: true,
  page: 1,
  rowsPerPage: 10,
  rowsNumber: 0,
});
const filter = ref('');
const formData = ref({});
const modal = ref({
  show: false,
  title: 'Crear',
  mode: 'create'
});
const { baseFields, tipoFields, formFields } = useSoftwareForm(formData);
const selectedSoftware = ref(null);

const columns = ref([
  { name: 'codigo', label: 'Código', align: 'left', field: 'codigo', sortable: true },
  { name: 'nombre', label: 'Nombre', align: 'left', field: 'nombre', sortable: true },
  { name: 'tipo', label: 'Tipo', align: 'center', field: 'tipo', sortable: true },
  { name: 'responsable', label: 'Responsable', align: 'left', field: row => row.responsable?.name, sortable: false },
  { name: 'area', label: 'Área', align: 'left', field: row => row.area?.nombre_completo, sortable: false },
  { name: 'estado', label: 'Estado', align: 'center', field: 'estado', sortable: true },
  { name: 'actions', label: 'Acciones', align: 'right', field: 'actions' }
]);

const getTipoChip = (tipo) => {
  const tipos = {
    'desarrollo_interno': { label: 'Sistema Interno', color: 'blue-3', icon: 'code' },
    'licencia_terceros': { label: 'Licencia', color: 'green-3', icon: 'key' },
    'red_social': { label: 'Red Social', color: 'purple-2', icon: 'groups' }
  };
  return tipos[tipo] || { label: tipo, color: 'grey', icon: 'widgets' };
};

const getEstadoChip = (estado) => {
  const estados = {
    'activo': { color: 'positive' },
    'inactivo': { color: 'negative' },
    'en_desarrollo': { color: 'info' },
    'vencido': { color: 'warning' }
  };
  return estados[estado] || { color: 'grey' };
};

const loadData = async () => {
  loading.value = true;
  try {
    const params = {
      page: pagination.value.page,
      per_page: pagination.value.rowsPerPage,
      sort_by: pagination.value.sortBy,
      desc: pagination.value.descending,
      search: filter.value,
    };
    const response = await softwareService.getSoftware(params);
    softwares.value = response.data.data;
    pagination.value.rowsNumber = response.data.meta.total;
  } catch (error) {
    console.error(error);
    softwares.value = [];
    $q.notify({ color: 'negative', message: 'Error al cargar los datos de software' });
  } finally {
    loading.value = false;
  }
};

const onPagination = (newPagination) => {
  pagination.value = { ...pagination.value, ...newPagination };
  loadData();
};

watch(filter, async () => {
  pagination.value.page = 1;
  await loadData();
});

onMounted(loadData);

// --- Lógica de Modal y Formulario ---

const populateFormOptions = async () => {
  loadingForm.value = true;
  try {
    const [usersRes, areasRes, activosRes] = await Promise.all([
      softwareService.getRelatedData('users'),
      softwareService.getRelatedData('areas'),
      softwareService.getRelatedData('activos')
    ]);

    const responsableField = baseFields.value.find(f => f.name === 'responsable_id');
    if (responsableField) responsableField.options = usersRes.data.map(u => ({ label: u.name, value: u.id }));

    const areaField = baseFields.value.find(f => f.name === 'area_id');
    if (areaField) areaField.options = areasRes.data.map(a => ({ label: a.nombre_completo, value: a.id }));

    const activosField = tipoFields.licencia_terceros.find(f => f.name === 'activos_asignados');
    if (activosField) activosField.options = activosRes.data.map(a => ({ label: `${a.codigo} - ${a.catalogo.denominacion}`, value: a.id }));

  } catch (error) {
    console.error("Error loading form options:", error);
    $q.notify({ color: 'negative', message: 'Error al cargar datos para el formulario.' });
    modal.value.show = false;
  } finally {
    loadingForm.value = false;
  }
};

const openCreateModal = async () => {
  formData.value = { tipo: null };
  modal.value = { show: true, title: 'Nuevo Activo de Software', mode: 'create' };
  await populateFormOptions();
};

const openEditModal = async (row) => {
  selectedSoftware.value = row;
  modal.value = { show: true, title: 'Editar Activo de Software', mode: 'edit' };
  await populateFormOptions();

  // Mapear los datos del 'row' al formato que espera el formulario (con objetos label/value para selects)
  formData.value = {
    ...row,
    tipo: { label: getTipoChip(row.tipo).label, value: row.tipo },
    responsable_id: row.responsable ? { label: row.responsable.name, value: row.responsable.id } : null,
    area_id: row.area ? { label: row.area.nombre_completo, value: row.area.id } : null,
    estado: { label: row.estado.charAt(0).toUpperCase() + row.estado.slice(1), value: row.estado },
    tipo_licencia: row.tipo_licencia ? { label: row.tipo_licencia.charAt(0).toUpperCase() + row.tipo_licencia.slice(1), value: row.tipo_licencia } : null,
    activos_asignados: row.activos_asignados?.map(a => ({ label: `${a.codigo} - ${a.catalogo.denominacion}`, value: a.id })) || []
  };
};

const handleFormSubmit = async (data) => {
  loadingForm.value = true;
  try {
    const payload = { ...data };
    // Convertir objetos de select a sus valores primitivos
    for (const key in payload) {
      if (payload[key] && typeof payload[key] === 'object' && payload[key].value !== undefined) {
        payload[key] = payload[key].value;
      }
    }
    if (payload.activos_asignados && Array.isArray(payload.activos_asignados)) {
      payload.activos_asignados = payload.activos_asignados.map(item => item.value !== undefined ? item.value : item);
    }

    if (modal.value.mode === 'create') {
      await softwareService.createSoftware(payload);
      $q.notify({ color: 'positive', message: 'Activo de software creado exitosamente', icon: 'check' });
    } else if (modal.value.mode === 'edit') {
      await softwareService.updateSoftware(selectedSoftware.value.id, payload);
      $q.notify({ color: 'positive', message: 'Activo de software actualizado exitosamente', icon: 'check' });
    }

    modal.value.show = false;
    await loadData();
  } catch (error) {
    const message = error.response?.data?.message || 'Error al guardar el activo de software';
    $q.notify({ color: 'negative', message, icon: 'report_problem' });
  } finally {
    loadingForm.value = false;
  }
};

const confirmDelete = (row) => {
  selectedSoftware.value = row;
  $q.dialog({
    title: 'Confirmar Eliminación',
    message: `¿Está seguro de que desea eliminar "${row.nombre}"? Esta acción no se puede deshacer.`,
    cancel: true,
    persistent: true,
    ok: { label: 'Eliminar', color: 'negative' }
  }).onOk(deleteSoftware);
};

const deleteSoftware = async () => {
  if (!selectedSoftware.value) return;
  loading.value = true;
  try {
    await softwareService.deleteSoftware(selectedSoftware.value.id);
    $q.notify({ color: 'positive', message: 'Activo de software eliminado.' });
    await loadData();
  } catch (error) {
    console.error("Error deleting software:", error);
    $q.notify({ color: 'negative', message: 'Error al eliminar el activo.' });
  } finally {
    loading.value = false;
    selectedSoftware.value = null;
  }
};
</script>

<style scoped>
.page-header {
  border-bottom: 1px solid #e0e0e0;
  padding-bottom: 16px;
}
</style>
