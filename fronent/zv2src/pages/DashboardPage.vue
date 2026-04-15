<template>
  <q-page class="q-pa-md">
    <div class="row q-col-gutter-md q-mb-md">
      <!-- Tarjetas resumen -->
      <div class="col-12 col-md-3">
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
        <q-card class="bg-secondary text-white">
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
        <q-card class="bg-info text-white">
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
              <!-- Aquí irá la gráfica -->
              <pre>{{ movimientosPorMes }}</pre>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>
  </q-page>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { userService } from 'src/services/userService'
import { departamentoService } from 'src/services/departamentoService'
import { activoService } from 'src/services/activoService'
import { movimientoService } from 'src/services/movimientoService'
import VueApexCharts from 'vue3-apexcharts'

const totalUsuarios = ref(0)
const totalDepartamentos = ref(0)
const totalActivos = ref(0)
const activosPorEstado = ref([])
const activosPorCategoria = ref([])
const movimientosPorMes = ref([])
const loadingActivosEstado = ref(true)
const loadingActivosCategoria = ref(true)
const loadingMovimientos = ref(true)

// Registrar el componente de apexcharts
const apexchart = VueApexCharts

onMounted(async () => {
  // Total de usuarios
  try {
    const usuarios = await userService.getUsuarios({ per_page: 1 })
    totalUsuarios.value = usuarios.meta ? usuarios.meta.total : (usuarios.data ? usuarios.data.length : 0)
  } catch (e) {
    console.error(e)
    totalUsuarios.value = 0
  }
  // Total de departamentos
  try {
    const departamentos = await departamentoService.getDepartamentos({ per_page: 1 })
    totalDepartamentos.value = departamentos.meta ? departamentos.meta.total : (departamentos.data ? departamentos.data.length : 0)
  } catch (e) {
    console.error(e)
    totalDepartamentos.value = 0
  }
  // Total de activos
  try {
    const activos = await activoService.getActivos({ per_page: 1 })
    totalActivos.value = activos.meta ? activos.meta.total : (activos.data ? activos.data.length : 0)
  } catch (e) {
    console.error(e)
    totalActivos.value = 0
  }
  // Activos por estado
  try {
    loadingActivosEstado.value = true
    const activos = await activoService.getActivos({})
    const conteo = {}
    ;(activos.data || activos).forEach(a => {
      conteo[a.estado_display] = (conteo[a.estado_display] || 0) + 1
    })
    activosPorEstado.value = Object.entries(conteo).map(([estado, cantidad]) => ({ estado, cantidad }))
  } finally {
    loadingActivosEstado.value = false
  }
  // Activos por categoría
  try {
    loadingActivosCategoria.value = true
    const activos = await activoService.getActivos({})
    const conteo = {}
    ;(activos.data || activos).forEach(a => {
      conteo[a.categoria?.nombre || 'Sin categoría'] = (conteo[a.categoria?.nombre || 'Sin categoría'] || 0) + 1
    })
    activosPorCategoria.value = Object.entries(conteo).map(([categoria, cantidad]) => ({ categoria, cantidad }))
  } finally {
    loadingActivosCategoria.value = false
  }
  // Movimientos por mes
  try {
    loadingMovimientos.value = true
    const movimientos = await movimientoService.getMovimientos({})
    const conteo = {}
    ;(movimientos.data || movimientos).forEach(m => {
      const mes = m.fecha_movimiento ? m.fecha_movimiento.substring(0,7) : 'Sin fecha'
      conteo[mes] = (conteo[mes] || 0) + 1
    })
    movimientosPorMes.value = Object.entries(conteo).map(([mes, cantidad]) => ({ mes, cantidad }))
  } finally {
    loadingMovimientos.value = false
  }
})

// Configuración para la gráfica de barras de activos por estado
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
  colors: ['#1976d2'],
}))
const chartEstadoSeries = computed(() => [{
  name: 'Cantidad',
  data: activosPorEstado.value.map(e => e.cantidad)
}])

// Configuración para la gráfica de barras de activos por categoría
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
  colors: ['#26a69a'],
}))
const chartCategoriaSeries = computed(() => [{
  name: 'Cantidad',
  data: activosPorCategoria.value.map(e => e.cantidad)
}])
</script>

<!--
Recuerda instalar apexcharts y vue3-apexcharts si no lo tienes:
npm install --save apexcharts vue3-apexcharts
--> 