<template>

  <div class="row justify-between">



    <!-- IZQUIERDA -->

    <div class="col-12 col-md-8 gt-sm">

      <div class="bg-primary" style="height: 100vh; position: relative;">

        <div class="column justify-center" style="height: 100%;">

          <div class="col-8">

            <div class="row justify-center q-pa-sm">

              <q-img src="~assets/imgs/Logo_UNAP.png" style="max-width: 300px" />

            </div>

            <div class="row justify-center q-mt-md">

              <span class="text-h5 text-white text-weight-bold text-center">

                UNIVERSIDAD NACIONAL DEL ALTIPLANO PUNO

              </span>

            </div>

          </div>

        </div>

      </div>

    </div>



    <!-- DERECHA -->

    <div class="col-12 col-md-4">

      <div class="column" style="height: 100vh;">



        <!-- HEADER -->

        <div class="col-2">

          <div class="row justify-end q-pa-md">

            <SwitchDark :black="true" />

          </div>



          <div class="row justify-center items-center">

            <img src="~assets/imgs/logooficina.png" style="width: 150px" />

          </div>



          <div class="row justify-center">

            <span class="text-h6 text-weight-bold">

              Sistema de Gestión de Inventariado

            </span>

          </div>

        </div>



        <!-- LOGIN -->

        <div class="col-8">

          <div class="row justify-center items-center" style="height: 100%">



            <div style="width: 350px; max-width: 100%">



              <p class="text-h5 text-center text-weight-bold q-mb-md">

                Iniciar Sesión

              </p>



              <FormLogin />



              <!-- CONSULTA -->

              <q-expansion-item v-model="panelAbierto" icon="search" label="Consultar mis activos" class="q-mt-md"
                dense>

                <q-card flat bordered>

                  <q-card-section class="q-gutter-sm">



                    <q-input v-model="formConsulta.dni" label="DNI" outlined dense maxlength="8">

                      <template #prepend>

                        <q-icon name="badge" />

                      </template>

                    </q-input>
                    <!-- Solo aparece si no tiene correo registrado -->
    <q-input
      v-if="requiereCorreo"
      v-model="correoManual"
      label="Correo institucional (@unap.edu.pe)"
      outlined dense
      type="email"
      :rules="[
        v => !!v || 'Requerido',
        v => v.endsWith('@unap.edu.pe') || 'Solo correos @unap.edu.pe'
      ]"
    >
      <template #prepend><q-icon name="email" /></template>
    </q-input>

                    <q-btn label="Enviar código" color="primary" class="full-width" :loading="loadingOtp"
                      @click="enviarOtp" />

                  </q-card-section>

</q-card>

            </q-expansion-item>

            <q-expansion-item v-if="false"
              icon="inventory_2"
              label="Consultar activo por código"
              class="q-mt-sm"
              dense
            >
              <q-card flat bordered>
                <q-card-section class="q-gutter-sm">
                  <q-input v-model="codigoActivo" label="Código del activo" outlined dense @keyup.enter="buscarActivo">
                    <template #prepend><q-icon name="qr_code" /></template>
                  </q-input>
                  <q-btn label="Buscar" color="primary" class="full-width" :loading="loadingBusqueda" @click="buscarActivo" />
                  <div v-if="errorBusqueda" class="text-negative text-caption">{{ errorBusqueda }}</div>
                </q-card-section>
              </q-card>
            </q-expansion-item>

            <q-dialog v-if="false" v-model="dialogActivo">
              <q-card style="min-width: 380px">
                <q-card-section class="bg-primary text-white row items-center">
                  <q-icon name="inventory_2" class="q-mr-sm" />
                  <div class="text-h6">Detalle del Activo</div>
                  <q-space />
                  <q-btn icon="close" flat round dense v-close-popup />
                </q-card-section>
                <q-card-section v-if="resultadoActivo" class="q-gutter-y-sm">
                  <div class="text-subtitle1 text-primary text-weight-bold">{{ resultadoActivo.denominacion }}</div>
                  <q-separator />
                  <div class="row q-col-gutter-sm">
                    <div class="col-6"><strong>Código:</strong></div><div class="col-6">{{ resultadoActivo.codigo }}</div>
                    <div class="col-6"><strong>Responsable:</strong></div><div class="col-6">{{ resultadoActivo.responsable }}</div>
                    <div class="col-6"><strong>Oficina:</strong></div><div class="col-6">{{ resultadoActivo.oficina }}</div>
                    <div class="col-6"><strong>Área:</strong></div><div class="col-6">{{ resultadoActivo.area }}</div>
                  </div>
                </q-card-section>
                <q-card-actions align="right">
                  <q-btn flat label="Cerrar" color="primary" v-close-popup />
                </q-card-actions>
              </q-card>
            </q-dialog>
            </div>

          </div>

        </div>



      </div>

    </div>



    <!-- OTP MODAL -->

    <q-dialog v-model="otpDialog">

      <q-card style="width: 350px">

        <q-card-section class="text-h6">

          Verificación OTP

        </q-card-section>



        <q-card-section class="q-gutter-sm">



          <q-input v-model="otpCode" label="Código OTP" outlined dense maxlength="6" />



          <q-btn label="Verificar" color="primary" class="full-width" :loading="verificandoOtp" @click="verificarOtp" />

        </q-card-section>

      </q-card>

    </q-dialog>



    <!-- MODAL ACTIVOS -->

    <q-dialog v-model="mostrarModalActivos" full-width>

      <q-card style="max-width: 95vw">



        <q-card-section class="bg-primary text-white row items-center">

          <div class="text-h6">Activos a Cargo</div>

          <q-space />



          <q-btn v-if="seleccionados.length" label="Movimiento" icon="swap_horiz" color="white" text-color="primary"
            @click="entregaDialog = true" />



          <q-btn icon="close" flat round v-close-popup />

        </q-card-section>



        <q-card-section>



          <div class="text-center text-weight-bold q-mb-md">

            {{ responsableNombre }}

          </div>

          <div class="row justify-end q-mb-sm" v-if="listaActivos.length > 0">
            <q-btn 
              icon="picture_as_pdf" 
              color="primary" 
              label="Exportar PDF" 
              @click="exportarPdfDni"
              :loading="loadingPdf"
            />
          </div>


          <TableDynamic v-model:selectedRows="seleccionados" :columns="columnsConsulta" :row="listaActivos"
            :pagination="paginacionConsulta" @update:pagination="paginacionConsulta = $event" row-key="codigo"
            show-selection hide-bottom>

            <template #body-cell-estado="props">

              <q-td :props="props">

                <q-badge :color="getStatusColor(props.row.estado)">

                  {{ props.row.estado_display }}

                </q-badge>

              </q-td>

            </template>

            <template #body-cell-condicion="props">

              <q-td :props="props">

                <q-badge :color="getCondicionColor(props.row.condicion)">

                  {{ props.row.condicion_display }}

                </q-badge>

              </q-td>

            </template>

          </TableDynamic>



        </q-card-section>

      </q-card>

    </q-dialog>



    <EntregaModal v-if="entregaDialog" v-model:show="entregaDialog" :activos="seleccionados" :modo-publico="true"
      :usuario-publico="usuarioOtp" :http-client="httpClientOtp" />



  </div>

</template>



<script setup>

import { ref } from 'vue'

import { useQuasar } from 'quasar'

import FormLogin from '../components/Login/FormLogin.vue'

import SwitchDark from '../components/Login/SwitchDark.vue'

import TableDynamic from 'src/components/TableDynamic.vue'

import EntregaModal from 'src/components/EntregaModal.vue'

import { AxiosAdapter } from 'src/adapters/AxiosAdapter'

import { httpClient } from '../boot/axios'

import { activoService } from 'src/services/activoService'



const $q = useQuasar()



// UI

const panelAbierto = ref(false)

const otpDialog = ref(false)

const loadingOtp = ref(false)

const verificandoOtp = ref(false)
const requiereCorreo = ref(false)
const correoManual = ref('')



// FORM

const formConsulta = ref({ correo: '', dni: '' })

const otpCode = ref('')



// DATA

const listaActivos = ref([])

const seleccionados = ref([])

const responsableNombre = ref('')

const mostrarModalActivos = ref(false)

const entregaDialog = ref(false)

const tokenTemporal = ref(null)

const loadingPdf = ref(false)

const dniUltimoConsulta = ref('')

const usuarioOtp = ref(null)

const httpClientOtp = ref(null)
const codigoActivo    = ref('')
const loadingBusqueda = ref(false)
const resultadoActivo = ref(null)
const errorBusqueda   = ref('')




// OTP TEMP DATA

const otpSession = ref(null)
const dialogActivo = ref(false)

const paginacionConsulta = ref({

  page: 1,

  rowsPerPage: 10,

  rowsNumber: 0,

  sortBy: null,

  descending: false

})



// ─── Tabla ────────────────────────────────────────────────────────────────────



const columnsConsulta = [

  { name: 'codigo', label: 'Código', field: 'codigo', align: 'left' },

  { name: 'denominacion', label: 'Denominación', field: 'denominacion', align: 'left' },

  { name: 'marca', label: 'Marca', field: 'marca', align: 'left' },

  { name: 'numero_serie', label: 'Número Serie', field: 'numero_serie', align: 'left' },

  { name: 'oficina', label: 'Oficina', field: 'oficina', align: 'left' },

  { name: 'area', label: 'Área', field: 'area', align: 'left' },

  { name: 'estado', label: 'Estado', field: 'estado', align: 'center' },

  { name: 'condicion', label: 'Condición', field: 'condicion', align: 'center' },

]



function getStatusColor(estado) {

  if (estado === 'activo') return 'positive'

  if (estado === 'inactivo') return 'negative'

  return 'grey'

}



function getCondicionColor(condicion) {

  if (condicion === 'nuevo') return 'grey'

  if (condicion === 'bueno') return 'primary'

  if (condicion === 'regular') return 'warning'

  if (condicion === 'malo') return 'negative'

  return 'grey'

}


// const buscarActivo = async () => {
//   if (!codigoActivo.value) {
//     return $q.notify({ type: 'warning', message: 'Ingresa un código' })
//   }
//   loadingBusqueda.value = true
//   resultadoActivo.value = null
//   errorBusqueda.value   = ''
//   try {
//     const res = await httpClient.get(`/activos/consultar-por-codigo/${codigoActivo.value}`)
//     resultadoActivo.value = res
//     dialogActivo.value = true
//   } catch (err) {
//     errorBusqueda.value = err.response?.data?.message || 'Activo no encontrado'
//   } finally {
//     loadingBusqueda.value = false
//   }
// }
// ─── OTP ──────────────────────────────────────────────────────────────────────



const enviarOtp = async () => {
  if (!formConsulta.value.dni) {
    return $q.notify({ type: 'warning', message: 'Ingresa tu DNI' })
  }
  // Si ya sabe que requiere correo, validarlo
  if (requiereCorreo.value && !correoManual.value) {
    return $q.notify({ type: 'warning', message: 'Ingresa un correo' })
  }

  loadingOtp.value = true
  try {
    const payload = { dni: formConsulta.value.dni }
    if (requiereCorreo.value) payload.correo = correoManual.value

    const res = await httpClient.post('/otp/solicitar', payload)

    if (res?.requiere_correo) {
      requiereCorreo.value = true
      $q.notify({ type: 'warning', message: 'No tienes correo registrado, ingresa uno para recibir el código' })
      return
    }

    otpSession.value = res?.session_id ?? res?.data?.session_id
    otpDialog.value = true
    $q.notify({ type: 'positive', message: 'Código enviado al correo' })
  } catch (err) {
    $q.notify({ type: 'negative', message: err.response?.data?.message || 'Error al enviar código' })
  } finally {
    loadingOtp.value = false
  }
}



const verificarOtp = async () => {

  if (!otpCode.value) return



  verificandoOtp.value = true

  try {

const data = await httpClient.post('/otp/verificar', {
      dni: formConsulta.value.dni,
      otp: otpCode.value,
      correo: formConsulta.value.correo || null
    })

    tokenTemporal.value = data.token_temporal

    usuarioOtp.value = data.usuario

    httpClientOtp.value = new AxiosAdapter({

      baseUrl: process.env.API_URL || '',

      getToken: () => tokenTemporal.value

    })



    otpDialog.value = false

    $q.notify({ type: 'positive', message: 'Verificado correctamente' })



    await consultarActivos()

  } catch (err) {

    $q.notify({ type: 'negative', message: 'Código incorrecto' })

  } finally {

    verificandoOtp.value = false

  }

}



const consultarActivos = async () => {

  const res = await httpClient.post('/activos/consultar-por-dni', {

    dni: formConsulta.value.dni,

    session_id: otpSession.value

  })



  console.log('RES:', res)

  console.log('LISTA:', res.data)

  console.log('LENGTH:', res.data?.length)
  console.log('RES COMPLETO:', res)

  //listaActivos.value = res.data ?? []

  paginacionConsulta.value.rowsNumber = listaActivos.value.length

  //responsableNombre.value = res.responsable ?? 'Usuario'



  //usuarioOtp.value = {
  // id: res.id,
  // nombre: res.responsable ?? 'Usuario',

  //  dni: formConsulta.value.dni,

  // oficinas: res.oficinas ?? []

  //}
  listaActivos.value = res.data ?? []
  responsableNombre.value = res.responsable ?? 'Usuario'
  
  dniUltimoConsulta.value = formConsulta.value.dni
  
  usuarioOtp.value = {
    id: res.id,
    nombre: res.responsable ?? 'Usuario',
    dni: formConsulta.value.dni,
    oficinas: res.oficinas ?? []
  }


  console.log('listaActivos:', listaActivos.value)

  console.log('mostrarModal antes:', mostrarModalActivos.value)



  if (listaActivos.value.length) {

    mostrarModalActivos.value = true

    console.log('mostrarModal después:', mostrarModalActivos.value)

  } else {

    $q.notify({ type: 'warning', message: 'Sin activos' })

  }

}

const exportarPdfDni = async () => {
  if (!dniUltimoConsulta.value) {
    $q.notify({ type: 'negative', message: 'No hay DNI consultar', position: 'top' })
    return
  }
  
  try {
    loadingPdf.value = true
    
    let ids = null
    if (seleccionados.value && seleccionados.value.length > 0) {
      ids = seleccionados.value.map(a => a.id)
    }
    
    const response = await activoService.exportarPdfDni(dniUltimoConsulta.value, ids)
    
    if (!(response instanceof Blob)) {
      throw new Error('La respuesta no es un Blob válido')
    }
    
    const blob = response
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `activos_${dniUltimoConsulta.value}.pdf`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
    
    $q.notify({ type: 'positive', message: 'PDF exportado exitosamente', position: 'top' })
  } catch (error) {
    console.error('Error al exportar PDF:', error)
    $q.notify({ type: 'negative', message: error.message || 'Error al exportar PDF', position: 'top' })
  } finally {
    loadingPdf.value = false
  }
}

</script>