<template>
  <q-dialog :model-value="show" @update:model-value="$emit('update:show', $event)" persistent maximized>
    <q-card class="column">
      <q-card-section class="bg-primary text-white">
        <div class="row items-center">
          <div class="text-h6">{{ modo === 'create' ? 'Nuevo Grupo de Declaración' : 'Editar Grupo de Declaración' }}</div>
          <q-space />
          <q-btn icon="close" flat round dense @click="$emit('update:show', false)" class="text-white" />
        </div>
      </q-card-section>

      <q-card-section class="q-pa-md col scroll">
        <!-- Formulario de Grupo -->
        <q-card flat bordered class="q-pa-lg">
          <div class="text-h6 q-mb-md text-primary">Información del Grupo</div>
          <div class="row q-col-gutter-md">
            <div class="col-12 col-md-8">
              <q-input
                v-model="formData.denominacion"
                label="Denominación del Grupo"
                outlined
                dense
                :rules="[val => !!val || 'La denominación es requerida']"
                placeholder="Ej: Declaración Q1 2024"
              />
            </div>
            <div class="col-12 col-md-4">
              <q-toggle
                v-model="formData.estado"
                label="Estado Activo"
                color="positive"
                icon="check_circle"
              />
            </div>
          </div>
        </q-card>

        <!-- Botones de Acción -->
        <div class="row justify-end q-mt-lg">
          <q-btn
            label="Cancelar"
            color="grey"
            @click="$emit('update:show', false)"
            class="q-mr-sm"
          />
          <q-btn
            :label="modo === 'create' ? 'Crear Grupo' : 'Actualizar Grupo'"
            color="primary"
            :loading="loading"
            @click="guardarGrupo"
            icon="save"
          />
        </div>
      </q-card-section>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useQuasar } from 'quasar';

const props = defineProps({
  show: Boolean,
  modo: {
    type: String,
    default: 'create'
  },
  grupo: {
    type: Object,
    default: () => ({})
  }
});

const emit = defineEmits(['update:show', 'grupo-guardado']);
const $q = useQuasar();

const loading = ref(false);
const formData = ref({
  denominacion: '',
  estado: true
});

// Limpiar formulario al abrir
watch(() => props.show, (val) => {
  if (val) {
    if (props.modo === 'edit' && props.grupo) {
      formData.value = {
        denominacion: props.grupo.denominacion || '',
        estado: props.grupo.estado || true
      };
    } else {
      formData.value = {
        denominacion: '',
        estado: true
      };
    }
  }
});

const guardarGrupo = async () => {
  try {
    loading.value = true;
    
    // Validar campos requeridos
    if (!formData.value.denominacion.trim()) {
      $q.notify({
        message: 'La denominación es requerida',
        color: 'negative',
        position: 'top',
        timeout: 2000
      });
      return;
    }

    // Emitir evento con los datos del grupo
    emit('grupo-guardado', {
      ...formData.value,
      id: props.modo === 'edit' ? props.grupo.id : undefined
    });

    $q.notify({
      message: props.modo === 'create' ? 'Grupo creado exitosamente' : 'Grupo actualizado exitosamente',
      color: 'positive',
      position: 'top',
      timeout: 2000
    });

    emit('update:show', false);
  } catch (error) {
    console.error('Error al guardar grupo:', error);
    $q.notify({
      message: 'Error al guardar el grupo',
      color: 'negative',
      position: 'top',
      timeout: 3000
    });
  } finally {
    loading.value = false;
  }
};
</script> 