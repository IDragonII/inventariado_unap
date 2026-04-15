<template>
  <q-dialog :model-value="show" @update:model-value="$emit('update:show', $event)" persistent maximized>
    <q-card class="column">
      <q-card-section class="bg-primary text-white">
        <div class="row items-center">
          <div class="text-h6">Declaración de Uso de Activos</div>
          <q-space />
          <q-btn icon="close" flat round dense @click="$emit('update:show', false)" class="text-white" />
        </div>
      </q-card-section>
      <q-card-section class="q-pa-md col scroll">
        <q-card flat bordered class="q-pa-lg">
          <div class="text-h6 q-mb-md text-primary">Activos a Declarar</div>
          <q-table
            :rows="activos"
            :columns="columns"
            row-key="id"
            dense
            flat
            :pagination="{ rowsPerPage: 10 }"
          >
            <template v-slot:body-cell-observaciones="props">
              <q-td :props="props">
                <q-input
                  v-model="observacionesActivos[props.row.id]"
                  placeholder="Observaciones (opcional)"
                  dense
                  outlined
                  type="textarea"
                  rows="2"
                  style="min-width: 180px;"
                />
              </q-td>
            </template>
          </q-table>
        </q-card>
        <div class="row justify-end q-mt-lg">
          <q-btn
            label="Conformidad de Uso"
            color="primary"
            :disable="activos.length === 0 || loading"
            :loading="loading"
            @click="confirmarDeclaracion"
            icon="check_circle"
            class="text-white"
          />
        </div>
      </q-card-section>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useQuasar } from 'quasar';
import { declaracionService } from 'src/services/declaracionService';
import { useAuthStore } from 'src/stores/auth-store';
//import { itoService } from 'src/services/itoService';

const props = defineProps({
  show: Boolean,
  activos: {
    type: Array,
    default: () => []
  }
});
const emit = defineEmits(['update:show', 'declaracion-confirmada']);
const $q = useQuasar();
const auth = useAuthStore();

const activosSeleccionados = ref([]);
const observacionesActivos = ref({});
const loading = ref(false);

const columns = [
  { name: 'denominacion', label: 'Denominación', field: row => row.catalogo?.denominacion, align: 'left' },
  { name: 'codigo', label: 'Código', field: 'codigo', align: 'left' },
  { name: 'marca', label: 'Marca', field: 'marca', align: 'left' },
  { name: 'modelo', label: 'Modelo', field: 'modelo', align: 'left' },
  { name: 'numero_serie', label: 'N° Serie', field: 'numero_serie', align: 'left' }
];

watch(() => props.show, (val) => {
  if (val) {
    activosSeleccionados.value = [];
    observacionesActivos.value = {};
  }
});

const confirmarDeclaracion = async () => {
  try {
    loading.value = true;
    let userId;
    if (auth.isAdmin || auth.isSupervisor) {
      if (props.activos.length > 0) {
        const primerActivo = props.activos[0];
        if (primerActivo.responsable_id) {
          userId = primerActivo.responsable_id;
        } else if (primerActivo.responsable && primerActivo.responsable.id) {
          userId = primerActivo.responsable.id;
        } else if (primerActivo.user_id) {
          userId = primerActivo.user_id;
        } else {
          // Si no hay responsable_id, usar el usuario actual
          userId = auth.user.id;
        }
      } else {
        userId = auth.user.id;
      }
    } else {
      // Para otros roles, usar el usuario actual
      userId = auth.user.id;
    }
    
    const declaracionData = {
      user_id: userId,
      fecha_declaracion: new Date().toISOString(),
      activos: props.activos.map(activo => activo.id)
    };
    //const ito = await itoService.getItos({estado: true})
    //declaracionData.ito=ito.data[0].codigo
    declaracionData.ito = String(new Date().getFullYear());
    await declaracionService.createDeclaracion(declaracionData);
    
    $q.notify({
      message: 'Declaración de uso registrada exitosamente',
      color: 'positive',
      position: 'top',
      timeout: 2000
    });
    
    emit('declaracion-confirmada', {
      activos: props.activos.map(activo => ({
        ...activo,
        observacion: observacionesActivos.value[activo.id] || ''
      }))
    });
    
    emit('update:show', false);
  } catch (error) {
    console.error('Error al crear declaración:', error);
    $q.notify({
      message: 'No esta habilitado la función, espera que el administrador habilite',
      color: 'negative',
      position: 'top',
      timeout: 3000
    });
  } finally {
    loading.value = false;
  }
};
</script> 