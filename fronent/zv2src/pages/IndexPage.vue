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
            <q-spinner v-if="loadingActivosEstado" color="primary" size="2em" />
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
            <q-spinner v-if="loadingActivosCategoria" color="primary" size="2em" />
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
            <div class="text-h6">Movimientos por Mes</div>
            <q-spinner v-if="loadingMovimientos" color="primary" size="2em" />
            <div v-else>
              <apexchart type="bar" height="300" :options="chartMovimientosOptions" :series="chartMovimientosSeries" />
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
const activosPorEstado = ref([])
const activosPorCategoria = ref([])
const movimientosPorMes = ref([])
const loadingActivosEstado = ref(true)
const loadingActivosCategoria = ref(true)
const loadingMovimientos = ref(true)
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
    loadingActivosCategoria.value = true
    loadingActivosEstado.value = true
    const configuracion = await activoService.getDashboard()
    totalDepartamentos.value = configuracion?.data?.total_oficinas ? configuracion?.data?.total_oficinas : 0
    totalActivos.value=configuracion?.data?.total_activos
    activosPorEstado.value=Object.entries(configuracion?.data?.activos_por_estado).map(
      ([estado, cantidad]) => ({ estado, cantidad })
    )
    activosPorCategoria.value=Object.entries(configuracion?.data.activos_por_categoria).map(
      ([categoria, cantidad]) => ({ categoria, cantidad })
    )
    movimientosPorMes.value = Object.entries(configuracion?.data?.movimientos_ultimos_6_meses).map(
      ([mes, cantidad]) => ({ mes, cantidad })
    )
  } catch (e) {
    console.error(e)
    totalDepartamentos.value = 0
    totalActivos.value = 0
  }
  finally{
    loadingActivosEstado.value = false
    loadingActivosCategoria.value = false
    loadingActivosCategoria.value = false
    loadingMovimientos.value = false
  }
})

const chartEstadoOptions = computed(() => ({
  chart: {
    type: 'bar',
    toolbar: { show: false }
  },
  xaxis: {
    categories: activosPorEstado.value.map(e => e.estado)
  },
  plotOptions: {
    bar: { horizontal: false, columnWidth: '50%' }
  },
  dataLabels: { enabled: true },
  title: { text: 'Activos por Estado' },
  colors: ['#334155'],
}))
const chartEstadoSeries = computed(() => [{
  name: 'Cantidad',
  data: activosPorEstado.value.map(e => e.cantidad)
}])

const chartCategoriaOptions = computed(() => ({
  chart: {
    type: 'bar',
    toolbar: { show: false }
  },
  xaxis: {
    categories: activosPorCategoria.value.map(e => e.categoria)
  },
  plotOptions: {
    bar: { horizontal: false, columnWidth: '50%' }
  },
  dataLabels: { enabled: true },
  title: { text: 'Activos por Categoría' },
  colors: ['#7c3aed'],
}))
const chartCategoriaSeries = computed(() => [{
  name: 'Cantidad',
  data: activosPorCategoria.value.map(e => e.cantidad)
}])

const chartMovimientosOptions = computed(() => ({
  chart: {
    type: 'bar',
    toolbar: { show: false }
  },
  xaxis: {
    categories: movimientosPorMes.value.map(e => e.mes)
  },
  plotOptions: {
    bar: { horizontal: false, columnWidth: '50%' }
  },
  dataLabels: { enabled: true },
  title: { text: 'Movimientos por Mes' },
  colors: ['#1e3a8a'],
}))
const chartMovimientosSeries = computed(() => [{
  name: 'Cantidad',
  data: movimientosPorMes.value.map(e => e.cantidad)
}])

</script>