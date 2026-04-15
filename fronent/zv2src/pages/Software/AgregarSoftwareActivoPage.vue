<template>
  <q-page class="q-pa-md">
    <PermissionsAccess :is-installer="auth.isInstaller">
    <q-card class="q-mb-lg">
      <!-- Añade padding interno al card section -->
      <q-card-section class="q-pa-md">
        <!-- Contenedor de fila con wrap y espacio entre columnas -->
        <div class="q-gutter-md">
          <!-- Input ocupa 8 columnas desde sm para arriba, 12 en xs -->
          <div class="col-12">
            <q-input
              v-model="codigoActivo"
              label="Código del Activo"
              placeholder="Ingrese el código del activo"
              dense
              :loading="loadingActivo"
              @keyup.enter="buscarActivo"
            >
            <template v-slot:append>
                <q-btn
                  icon="search"
                  label="Buscar"
                  @click="buscarActivo"
                  :loading="loadingActivo"
                  :disable="!codigoActivo"
                  
                />
            </template>
            </q-input>
          </div>
        </div>
      </q-card-section>
    </q-card>

    <q-card v-if="activoEncontrado" class="q-mb-lg">
      <q-card-section>
        <div class="text-h6 q-mb-md">
          <q-icon name="info" class="q-mr-sm" />
          Información del Activo
        </div>
        
        <!-- Información principal del activo -->
        <div class="row q-gutter-md">
          <div class="col-12 col-sm-6 col-md-4">
            <q-item class="q-pa-none">
              <q-item-section>
                <q-item-label caption class="text-weight-medium">Denominación</q-item-label>
                <q-item-label class="text-body1">{{ activoEncontrado.catalogo?.denominacion || 'N/A' }}</q-item-label>
              </q-item-section>
            </q-item>
          </div>
          <div class="col-12 col-sm-6 col-md-4">
            <q-item class="q-pa-none">
              <q-item-section>
                <q-item-label caption class="text-weight-medium">Número de Serie</q-item-label>
                <q-item-label class="text-body1">{{ activoEncontrado.numero_serie || 'N/A' }}</q-item-label>
              </q-item-section>
            </q-item>
          </div>
          <div class="col-12 col-sm-6 col-md-4">
            <q-item class="q-pa-none">
              <q-item-section>
                <q-item-label caption class="text-weight-medium">Código</q-item-label>
                <q-item-label class="text-body1">{{ activoEncontrado.codigo || 'N/A' }}</q-item-label>
              </q-item-section>
            </q-item>
          </div>
        </div>
        
        <!-- Software ya instalado -->
        <div v-if="softwareInstalado.length > 0" class="q-mt-lg">
          <div class="text-subtitle1 q-mb-md">
            <q-icon name="check_circle" color="positive" class="q-mr-sm" />
            Software Ya Instalado
          </div>
          <div class="row q-gutter-sm">
            <div 
              v-for="instalacion in softwareInstalado" 
              :key="instalacion.id"
              class="col-12 col-sm-6 col-md-4 col-lg-3"
            >
              <q-chip
                color="positive"
                text-color="white"
                icon="check_circle"
                class="full-width"
              >
                <div class="text-caption">{{ instalacion?.nombre || 'Software no encontrado' }}</div>
                <q-tooltip>
                  Instalado el {{ new Date(instalacion.fecha_asignacion).toLocaleDateString() }}
                </q-tooltip>
              </q-chip>
            </div>
          </div>
        </div>
      </q-card-section>
    </q-card>

    <!-- Selección de software -->
    <q-card v-if="activoEncontrado" class="q-mb-lg">
      <q-card-section>
        <div class="text-h6 q-mb-md">
          <q-icon name="computer" class="q-mr-sm" />
          Seleccionar Software a Instalar
        </div>
        
        <!-- Filtros de software -->
        <div class="q-mb-lg">
          <div class="text-subtitle2 q-mb-sm">Filtrar por categoría:</div>
          <div class="row q-gutter-sm">
            <div class="col-12">
              <q-btn-toggle
                v-model="filtroTipo"
                :options="[
                  { label: 'Todos', value: 'todos' },
                  { label: 'Office', value: 'office' },
                  { label: 'Diseño', value: 'diseno' },
                  { label: 'Ingeniería', value: 'ingenieria' },
                  { label: 'Otros', value: 'otros' }
                ]"
                color="primary"
                outline
                dense
                spread
                class="full-width"
              />
            </div>
          </div>
        </div>
        
        <!-- Grid de software -->
        <div v-if="softwareFiltrado.length > 0" class="software-grid-container">
          <div class="software-grid">
            <div 
              v-for="software in softwareFiltrado" 
              :key="software.id"
              class="software-item"
            >
              <q-card 
                :class="['software-card', { 'selected': softwareSeleccionado.includes(software.id) }]"
                clickable
                @click="toggleSoftware(software.id)"
              >
                <q-card-section class="text-center q-pa-md">
                  <div class="software-icon-container">
                    <q-icon 
                      :name="getSoftwareIcon(software.tipo)" 
                      size="48px" 
                      :color="softwareSeleccionado.includes(software.id) ? 'primary' : 'grey'"
                    />
                    <q-badge
                      v-if="softwareSeleccionado.includes(software.id)"
                      color="positive"
                      floating
                      icon="check"
                    />
                  </div>
                  <div class="text-subtitle2 q-mt-sm text-weight-medium text-truncate">{{ software.nombre }}</div>
                  <div class="text-caption text-grey">{{ getTipoChip(software.tipo).label }}</div>
                  <div class="text-caption text-grey-6">{{ software.codigo }}</div>
                </q-card-section>
                
                <q-card-actions align="center" class="q-pa-sm">
                  <q-btn
                    :icon="softwareSeleccionado.includes(software.id) ? 'check_circle' : 'add_circle_outline'"
                    :color="softwareSeleccionado.includes(software.id) ? 'positive' : 'primary'"
                    :label="softwareSeleccionado.includes(software.id) ? 'Seleccionado' : 'Seleccionar'"
                    size="sm"
                    flat
                    class="full-width"
                  />
                </q-card-actions>
              </q-card>
            </div>
          </div>
        </div>
        
        <!-- Mensaje cuando no hay software disponible -->
        <div v-else class="text-center q-pa-xl">
          <q-icon name="info" size="64px" color="grey-5" />
          <div class="text-h6 text-grey-6 q-mt-md">No hay software disponible para instalar</div>
          <div class="text-body2 text-grey-5">Todos los software disponibles ya están instalados en este activo.</div>
        </div>

        <!-- Software seleccionado -->
        <div v-if="softwareSeleccionado.length > 0" class="q-mt-lg">
          <div class="text-subtitle1 q-mb-md">
            <q-icon name="list" class="q-mr-sm" />
            Software Seleccionado
          </div>
          <div class="row q-gutter-sm">
            <div 
              v-for="softwareId in softwareSeleccionado" 
              :key="softwareId"
              class="col-12 col-sm-6 col-md-4 col-lg-3"
            >
              <q-chip
                :color="getSoftwareById(softwareId)?.tipo === 'licencia_terceros' ? 'green' : 'blue'"
                text-color="white"
                removable
                @remove="removeSoftware(softwareId)"
                class="full-width"
              >
                <q-icon :name="getSoftwareIcon(getSoftwareById(softwareId)?.tipo)" class="q-mr-xs" />
                <div class="text-caption">{{ getSoftwareById(softwareId)?.nombre }}</div>
              </q-chip>
            </div>
          </div>
        </div>
      </q-card-section>
    </q-card>

    <!-- Botones de acción -->
    <div v-if="activoEncontrado" class="row q-gutter-md justify-end q-mt-lg">
      <div class="col-12 col-sm-auto">
        <q-btn
          color="secondary"
          icon="clear"
          label="Limpiar Selección"
          @click="limpiarSeleccion"
          :disable="softwareSeleccionado.length === 0"
          class="full-width"
          size="md"
        />
      </div>
      <div class="col-12 col-sm-auto">
        <q-btn
          color="primary"
          icon="save"
          label="Guardar Instalación"
          @click="guardarInstalacion"
          :loading="loadingGuardar"
          :disable="softwareSeleccionado.length === 0"
          class="full-width"
          size="md"
        />
      </div>
    </div>

    <!-- Loading overlay -->
    <q-inner-loading :showing="loadingActivo || loadingGuardar">
      <q-spinner size="50px" color="primary" />
    </q-inner-loading>
  </PermissionsAccess>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useQuasar } from 'quasar';
import { activoService } from 'src/services/activoService';
import { softwareService } from 'src/services/softwareService';
import { softwareInstalacionService } from 'src/services/softwareInstalacionService';
import PermissionsAccess from 'src/components/Permissions/PermissionsAccess.vue';
import { useAuthStore } from 'src/stores/auth-store';
const $q = useQuasar();
const auth=useAuthStore()
const codigoActivo = ref('');
const activoEncontrado = ref(null);
const softwareDisponible = ref([]);
const softwareSeleccionado = ref([]);
const softwareInstalado = ref([]);
const filtroTipo = ref('todos');
const loadingActivo = ref(false);
const loadingGuardar = ref(false);

// Computed properties
const getSoftwareById = (id) => {
  return softwareDisponible.value.find(s => s.id === id);
};

const softwareFiltrado = computed(() => {
  // Mostrar todos los software disponibles
  let softwareDisponibleCompleto = softwareDisponible.value;

  if (filtroTipo.value === 'todos') {
    return softwareDisponibleCompleto;
  }
  
  return softwareDisponibleCompleto.filter(software => {
    const nombre = software.nombre.toLowerCase();
    switch (filtroTipo.value) {
      case 'office':
        return nombre.includes('office') || nombre.includes('word') || nombre.includes('excel') || nombre.includes('powerpoint');
      case 'diseno':
        return nombre.includes('photoshop') || nombre.includes('illustrator') || nombre.includes('corel') || nombre.includes('autocad');
      case 'ingenieria':
        return nombre.includes('civil') || nombre.includes('autocad') || nombre.includes('revit') || nombre.includes('solidworks');
      case 'otros':
        return !nombre.includes('office') && !nombre.includes('photoshop') && !nombre.includes('civil') && !nombre.includes('autocad');
      default:
        return true;
    }
  });
});

// Métodos
const getTipoChip = (tipo) => {
  const tipos = {
    'desarrollo_interno': { label: 'Sistema Interno', color: 'blue-3', icon: 'code' },
    'licencia_terceros': { label: 'Licencia', color: 'green-3', icon: 'key' },
    'red_social': { label: 'Red Social', color: 'purple-2', icon: 'groups' }
  };
  return tipos[tipo] || { label: tipo, color: 'grey', icon: 'widgets' };
};

const getSoftwareIcon = (tipo) => {
  const iconos = {
    'desarrollo_interno': 'code',
    'licencia_terceros': 'key',
    'red_social': 'groups'
  };
  return iconos[tipo] || 'widgets';
};

const buscarActivo = async () => {
  if (!codigoActivo.value.trim()) {
    $q.notify({ color: 'warning', message: 'Ingrese un código válido' });
    return;
  }

  loadingActivo.value = true;
  try {
    const response = await activoService.getActivos({
      search: codigoActivo.value,
      per_page: 1
    });
    
    if (response.data && response.data.length > 0) {
      activoEncontrado.value = response.data[0];
      await cargarSoftwareDisponible();
      await cargarSoftwareInstalado();
      $q.notify({ color: 'positive', message: 'Activo encontrado correctamente' });
    } else {
      activoEncontrado.value = null;
      $q.notify({ color: 'negative', message: 'No se encontró ningún activo con ese código' });
    }
  } catch (error) {
    console.error('Error al buscar activo:', error);
    $q.notify({ color: 'negative', message: 'Error al buscar el activo' });
  } finally {
    loadingActivo.value = false;
  }
};

const cargarSoftwareDisponible = async () => {
  try {
    const response = await softwareService.getSoftware({ per_page: 200, tipo: 'licencia_terceros' });
    softwareDisponible.value = response.data.data || [];
  } catch (error) {
    console.error('Error al cargar software:', error);
    $q.notify({ color: 'negative', message: 'Error al cargar el software disponible' });
  }
};

const cargarSoftwareInstalado = async () => {
  try {
    const response = await softwareInstalacionService.getInstalacionesByActivo(activoEncontrado.value.id);
    softwareInstalado.value = response.data || [];
    softwareInstalado.value.forEach(element => {
      toggleSoftware(element.id)
    });
  } catch (error) {
    console.error('Error al cargar software instalado:', error);
    // No mostrar error si no hay instalaciones
    softwareInstalado.value = [];
  }
};

const toggleSoftware = (softwareId) => {
  const index = softwareSeleccionado.value.indexOf(softwareId);
  if (index > -1) {
    softwareSeleccionado.value.splice(index, 1);
  } else {
    softwareSeleccionado.value.push(softwareId);
  }
};

const removeSoftware = (softwareId) => {
  const index = softwareSeleccionado.value.indexOf(softwareId);
  if (index > -1) {
    softwareSeleccionado.value.splice(index, 1);
  }
};

const limpiarSeleccion = () => {
  softwareSeleccionado.value = [];
};

const guardarInstalacion = async () => {
  if (softwareSeleccionado.value.length === 0) {
    $q.notify({ color: 'warning', message: 'Seleccione al menos un software' });
    return;
  }

  loadingGuardar.value = true;
  try {
    await softwareInstalacionService.instalarSoftwareEnActivo(
      activoEncontrado.value.id,
      softwareSeleccionado.value
    );
    
    const softwareNames = softwareSeleccionado.value.map(id => getSoftwareById(id)?.nombre).join(', ');
    
    $q.notify({ 
      color: 'positive', 
      message: `Software instalado correctamente en el activo: ${softwareNames}`,
      icon: 'check'
    });
    
    // Limpiar formulario
    codigoActivo.value = '';
    activoEncontrado.value = null;
    softwareSeleccionado.value = [];
    
  } catch (error) {
    console.error('Error al guardar instalación:', error);
    const message = error.response?.data?.message || 'Error al guardar la instalación';
    $q.notify({ color: 'negative', message });
  } finally {
    loadingGuardar.value = false;
  }
};

// Cargar software disponible al montar el componente
onMounted(() => {
  cargarSoftwareDisponible();
});
</script>

<style scoped>
.software-grid-container {
  max-height: 600px;
  overflow-y: auto;
  border-radius: 8px;
  padding: 16px;
}

.software-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 16px;
}

.software-item {
  min-width: 250px;
}

.software-icon-container {
  position: relative;
  display: inline-block;
}

.software-card {
  transition: all 0.3s ease;
  border: 2px solid transparent;
  height: 100%;
  display: flex;
  flex-direction: column;
}

.software-card:hover {
  transform: translateY(-2px);
}

.software-card.selected {
  border-color: #1976d2;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(25, 118, 210, 0.2);
}

/* Responsive adjustments */
@media (max-width: 599px) {
  .software-grid {
    grid-template-columns: 1fr;
  }
  
  .software-item {
    min-width: auto;
  }
}

@media (min-width: 600px) and (max-width: 1023px) {
  .software-grid {
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  }
}

@media (min-width: 1024px) {
  .software-grid {
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  }
}

/* Scrollbar styling */
.software-grid-container::-webkit-scrollbar {
  width: 8px;
}

.software-grid-container::-webkit-scrollbar-track {
  border-radius: 4px;
}

.software-grid-container::-webkit-scrollbar-thumb {
  border-radius: 4px;
}
</style> 