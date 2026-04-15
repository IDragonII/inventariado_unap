<template>
  <q-page padding>
    <!-- Encabezado -->
    <div class="row q-mb-md">
      <div class="col-12">
        <q-card>
          <q-card-section>
            <div class="row items-center justify-between">
              <div class="text-h6">Gestión de Grupos de Declaración</div>
              <q-btn
                v-if="auth.isAdmin"
                color="primary"
                icon="add"
                label="Nuevo Grupo"
                @click="abrirModalGrupo()"
              />
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <!-- Tabla de Grupos -->
    <q-card>
      <q-card-section>
        <q-table
          :rows="grupos"
          :columns="columns"
          row-key="id"
          :loading="loading"
          :pagination="pagination"
          @request="onRequest"
        >
          <template #body-cell-estado="props">
            <q-td :props="props">
              <q-badge :color="props.row.estado ? 'positive' : 'negative'">
                {{ props.row.estado ? 'Activo' : 'Inactivo' }}
              </q-badge>
            </q-td>
          </template>

          <template #body-cell-actions="props">
            <q-td :props="props" class="q-gutter-sm">
              <q-btn 
                flat 
                dense 
                round 
                icon="edit" 
                color="primary" 
                @click="editarGrupo(props.row)"
              >
                <q-tooltip>Editar</q-tooltip>
              </q-btn>
              <q-btn 
                flat 
                dense 
                round 
                icon="delete" 
                color="negative" 
                @click="confirmarEliminar(props.row)"
              >
                <q-tooltip>Eliminar</q-tooltip>
              </q-btn>
            </q-td>
          </template>
        </q-table>
      </q-card-section>
    </q-card>

    <!-- Modal para Crear/Editar Grupo -->
    <GrupoDeclaracionModal
      v-model:show="modalGrupo.show"
      :modo="modalGrupo.modo"
      :grupo="modalGrupo.grupo"
      @grupo-guardado="guardarGrupo"
    />

    <!-- Dialog de confirmación para eliminar -->
    <q-dialog v-model="deleteDialog" persistent>
      <q-card>
        <q-card-section class="row items-center">
          <q-avatar icon="warning" color="negative" text-color="white" />
          <span class="q-ml-sm">¿Está seguro de eliminar este grupo de declaración?</span>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancelar" color="primary" v-close-popup />
          <q-btn flat label="Eliminar" color="negative" @click="eliminarGrupo" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useQuasar } from 'quasar';
import { useAuthStore } from 'src/stores/auth-store';
import GrupoDeclaracionModal from 'src/components/GrupoDeclaracionModal.vue';

const $q = useQuasar();
const auth = useAuthStore();

const loading = ref(false);
const grupos = ref([]);
const deleteDialog = ref(false);
const grupoAEliminar = ref(null);

const modalGrupo = ref({
  show: false,
  modo: 'create',
  grupo: null
});

const pagination = ref({
  sortBy: 'id',
  descending: false,
  page: 1,
  rowsPerPage: 10
});

const columns = [
  { name: 'id', label: 'ID', field: 'id', align: 'left', sortable: true },
  { name: 'denominacion', label: 'Denominación', field: 'denominacion', align: 'left', sortable: true },
  { name: 'estado', label: 'Estado', field: 'estado', align: 'center' },
  { name: 'created_at', label: 'Fecha Creación', field: 'created_at', align: 'left', sortable: true },
  { name: 'actions', label: 'Acciones', align: 'center' }
];

onMounted(() => {
  cargarGrupos();
});

const cargarGrupos = async () => {
  try {
    loading.value = true;
    // Aquí deberías llamar a tu servicio para obtener los grupos
    // grupos.value = await grupoDeclaracionService.getGrupos();
    
    // Datos de ejemplo
    grupos.value = [
      { id: 1, denominacion: 'Declaración Q1 2024', estado: true, created_at: '2024-01-15' },
      { id: 2, denominacion: 'Declaración Q2 2024', estado: false, created_at: '2024-01-20' }
    ];
  } catch (error) {
    console.error('Error al cargar grupos:', error);
    $q.notify({
      message: 'Error al cargar los grupos',
      color: 'negative',
      position: 'top'
    });
  } finally {
    loading.value = false;
  }
};

const abrirModalGrupo = (grupo = null) => {
  modalGrupo.value = {
    show: true,
    modo: grupo ? 'edit' : 'create',
    grupo: grupo
  };
};

const editarGrupo = (grupo) => {
  abrirModalGrupo(grupo);
};

const guardarGrupo = async (data) => {
  try {
    loading.value = true;
    
    if (data.id) {
      // Actualizar grupo existente
      // await grupoDeclaracionService.updateGrupo(data.id, data);
      console.log('Actualizando grupo:', data);
    } else {
      // Crear nuevo grupo
      // await grupoDeclaracionService.createGrupo(data);
      console.log('Creando grupo:', data);
    }
    
    await cargarGrupos();
    
    $q.notify({
      message: data.id ? 'Grupo actualizado exitosamente' : 'Grupo creado exitosamente',
      color: 'positive',
      position: 'top'
    });
  } catch (error) {
    console.error('Error al guardar grupo:', error);
    $q.notify({
      message: 'Error al guardar el grupo',
      color: 'negative',
      position: 'top'
    });
  } finally {
    loading.value = false;
  }
};

const confirmarEliminar = (grupo) => {
  grupoAEliminar.value = grupo;
  deleteDialog.value = true;
};

const eliminarGrupo = async () => {
  try {
    loading.value = true;
    
    // await grupoDeclaracionService.deleteGrupo(grupoAEliminar.value.id);
    console.log('Eliminando grupo:', grupoAEliminar.value.id);
    
    await cargarGrupos();
    
    $q.notify({
      message: 'Grupo eliminado exitosamente',
      color: 'positive',
      position: 'top'
    });
  } catch (error) {
    console.error('Error al eliminar grupo:', error);
    $q.notify({
      message: 'Error al eliminar el grupo',
      color: 'negative',
      position: 'top'
    });
  } finally {
    loading.value = false;
    deleteDialog.value = false;
    grupoAEliminar.value = null;
  }
};

const onRequest = (props) => {
  pagination.value = props.pagination;
  cargarGrupos();
};
</script> 