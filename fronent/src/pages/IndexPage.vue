<template>
  <q-page class="q-pa-md">
    <PermissionsAccess 
        :is-admin="auth.isAdmin" 
    :is-supervisor="auth.isSupervisor" 
    :is-responsable="auth.isResponsable" 
    :is-consulta="auth.isConsulta">
    <div class="row q-col-gutter-md q-mb-md">
      <!-- Tarjetas resumen -->
      <div v-if="!auth.isConsulta && !auth.isResponsable" class="col-12 col-md-3">
        <q-card class="bg-primary text-white">
          <q-card-section class="row items-center">
            <q-icon name="people" size="2em" class="q-mr-md" />
            <div>
              <div class="text-h6">Total de Usuarios</div>
              <div class="text-h4">{{ totalUsuarios }}</div>
            </div>
          </q-card-section>
        </q-card>
      </div>
      <div class="col-12 col-md-3">
        <q-card class="bg-teal-10 text-white">
          <q-card-section class="row items-center">
            <q-icon name="apartment" size="2em" class="q-mr-md" />
            <div>
              <div class="text-h6">Total de Departamentos</div>
              <div class="text-h4">{{ totalDepartamentos }}</div>
            </div>
          </q-card-section>
        </q-card>
      </div>
      <div class="col-12 col-md-3">
        <q-card class="bg-indigo-10 text-white">
          <q-card-section class="row items-center">
            <q-icon name="inventory_2" size="2em" class="q-mr-md" />
            <div>
              <div class="text-h6">Total de Activos</div>
              <div class="text-h4">{{ totalActivos }}</div>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <div class="row q-col-gutter-md">
      <!-- Gráfica: Activos por Estado -->
      <div class="col-12 col-md-6">
        <q-card>
          <q-card-section>
            <div class="text-h6">Activos por Estado</div>
            <q-spinner v-if="loadingActivoPorOficina" color="primary" size="2em" />
            <div v-else>
              <!-- Aquí irá la gráfica (puedes usar apexcharts, chart.js, etc.) -->
              <apexchart type="bar" height="300" :options="chartEstadoOptions" :series="chartEstadoSeries" />
            </div>
          </q-card-section>
        </q-card>
      </div>
      <!-- Gráfica: Activos por Categoría -->
      <div class="col-12 col-md-6">
        <q-card>
          <q-card-section>
            <div class="text-h6">Activos por Categoría</div>
            <q-spinner v-if="loadingActivosGrupo" color="primary" size="2em" />
            <div v-else>
              <!-- Aquí irá la gráfica -->
              <apexchart type="bar" height="300" :options="chartCategoriaOptions" :series="chartCategoriaSeries" />
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <div class="row q-col-gutter-md q-mt-md">
      <!-- Gráfica: Movimientos por Mes -->
      <div class="col-12">
        <q-card>
          <q-card-section>
            <div class="text-h6">Activos por Oficina</div>
            <q-spinner v-if="loadingActivoPorOficina" color="primary" size="2em" />
            <div v-else>
              <q-table
                :rows="activosPorOficina"
                :columns="columns"
                row-key="denominacion"
                flat
                bordered
                dense
              />
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>
  </PermissionsAccess>
  </q-page>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import PermissionsAccess from 'src/components/Permissions/PermissionsAccess.vue'
import { userService } from 'src/services/userService'
import { activoService } from 'src/services/activoService'
import VueApexCharts from 'vue3-apexcharts'
import { useAuthStore } from 'src/stores/auth-store'
const apexchart = VueApexCharts
const totalUsuarios = ref(0)
const totalDepartamentos = ref(0)
const totalActivos = ref(0)

const activosPorDia=ref([])
const activosPorGrupo = ref([])
const activosPorOficina=ref([])

const loadingActivosPorDia = ref(false)
const loadingActivosGrupo = ref(false)
const loadingActivoPorOficina=ref(false)
const auth = useAuthStore()
onMounted(async () => {
  if (!auth.isConsulta && !auth.isResponsable && !auth.isSoftware) {
    try {
      const usuarios = await userService.getUsuarios()
      totalUsuarios.value = usuarios.meta ? usuarios.meta.total : (usuarios.data ? usuarios.data.length : 0)
    } catch (e) {
      console.error(e)
      totalUsuarios.value = 0
    }
  }
  try {
    loadingActivosPorDia.value = true
    loadingActivosGrupo.value = true
    loadingActivoPorOficina.value=true
    const configuracion = await activoService.getDashboard()
    activosPorDia.value=configuracion.data.activosPorDia
    activosPorGrupo.value=configuracion.data.activosPorGrupoDia
    activosPorOficina.value=configuracion.data.activosPorOficina
  } catch (e) {
    console.error(e)
  }
  finally{
    loadingActivosPorDia.value = false
    loadingActivosGrupo.value = false
    loadingActivoPorOficina.value = false
  }
})

const chartEstadoOptions = computed(() => ({
  chart: {
    type: 'bar',
    toolbar: { show: false }
  },
  xaxis: {
    // aquí usamos las fechas
    categories: activosPorDia.value.map(e => e.fecha)
  },
  plotOptions: {
    bar: { horizontal: false, columnWidth: '50%' }
  },
  dataLabels: { enabled: true },
  title: { text: 'Activos por Día' },
  colors: ['#334155'],
}))

const chartEstadoSeries = computed(() => [{
  name: 'Cantidad',
  // aquí usamos total
  data: activosPorDia.value.map(e => e.total)
}])

const chartCategoriaOptions = computed(() => ({
  chart: {
    type: 'bar',
    toolbar: { show: false }
  },
  xaxis: {
    // Usamos el nombre del grupo
    categories: activosPorGrupo.value.map(e => e.grupo)
  },
  plotOptions: {
    bar: { horizontal: false, columnWidth: '50%' }
  },
  dataLabels: { enabled: true },
  title: { text: 'Activos por Grupo' },
  colors: ['#7c3aed'],
}))

const chartCategoriaSeries = computed(() => [{
  name: 'Cantidad',
  // Usamos total
  data: activosPorGrupo.value.map(e => e.total)
}])

const columns = [
  { name: 'denominacion', label: 'Denominación de la Oficina', field: 'denominacion', align: 'left' },
  { name: 'total_activos', label: 'Total', field: row => Number(row.total_activos), align: 'right' },
  { name: 'registrados', label: 'Registrados', field: row => Number(row.registrados), align: 'right' },
  { name: 'faltantes', label: 'Faltantes', field: row => Number(row.faltantes), align: 'right' },
  { 
    name: 'porcentaje', 
    label: '% Registrados', 
    field: row => {
      const total = Number(row.total_activos) || 0
      const registrados = Number(row.registrados) || 0
      return total ? ((registrados / total) * 100).toFixed(1) + '%' : '0%'
    }, 
    align: 'right' 
  }
]
</script>