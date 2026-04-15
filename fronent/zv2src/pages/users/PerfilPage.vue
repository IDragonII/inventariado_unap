<template>
  <q-page padding>
    <div class="text-h5 q-mb-md">Mi Perfil</div>

    <div class="row q-col-gutter-md">
      <!-- Columna izquierda - Información del perfil -->
      <div class="col-12 col-md-4">
        <q-card class="q-mb-md">
          <q-card-section class="text-center">
            <q-avatar size="150px" class="q-mb-md">
              <img :src="usuario.avatar" />
            </q-avatar>
            <div class="text-h6">{{ usuario.name }}</div>
            <div class="text-subtitle2 text-grey">{{ usuario.rol }}</div>
            <div class="text-caption text-grey q-mt-sm">
              Último acceso: {{ usuario.ultimoAcceso }}
            </div>
          </q-card-section>
          <q-card-section>
            <q-btn
              color="primary"
              label="Cambiar Foto"
              icon="photo_camera"
              class="full-width"
              @click="cambiarFoto"
            />
          </q-card-section>
        </q-card>

        <q-card class="q-mb-md">
          <q-card-section>
            <div class="row items-center justify-between q-mb-md">
              <div class="text-h6">Sesiones Activas</div>
              <q-btn
                color="negative"
                label="Cerrar todas las sesiones"
                icon="logout"
                @click="confirmarCerrarTodasSesiones"
              />
            </div>
            <q-list separator>
              <q-item v-for="session in sesionesActivas" :key="session.id">
                <q-item-section>
                  <q-item-label>{{ getBrowserInfo(session.user_agent) }}</q-item-label>
                  <q-item-label caption>
                    IP: {{ session.ip_address }}
                    <br>
                    Última actividad: {{ formatDate(session.last_activity) }}
                  </q-item-label>
                </q-item-section>
                <q-item-section side v-if="!isCurrentSession(session)">
                  <q-btn
                    flat
                    round
                    dense
                    icon="close"
                    color="negative"
                    @click="confirmarCerrarSesion(session)"
                  />
                </q-item-section>
              </q-item>
            </q-list>
          </q-card-section>
        </q-card>
      </div>

      <!-- Columna derecha - Formulario de edición -->
      <div class="col-12 col-md-8">
        <q-card>
          <q-card-section class="p-2">
            <div class="text-h6 q-mb-md">Información Personal</div>
            <q-form @submit="guardarPerfil" class="q-gutter-md">
              <div class="row q-col-gutter-md">
                <div class="col-12 col-md-6">
                  <q-input
                    v-model="usuario.name"
                    label="Nombre Completo"
                    outlined
                    dense
                    :rules="[val => !!val || 'El nombre es requerido']"
                  />
                </div>
                <div class="col-12 col-md-6">
                  <q-input
                    v-model="usuario.email"
                    label="Correo Electrónico"
                    type="email"
                    outlined
                    dense
                    :rules="[
                      val => !!val || 'El email es requerido',
                      val => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val) || 'Email inválido'
                    ]"
                  />
                </div>
                <div class="col-12 col-md-6">
                  <q-input
                    v-model="usuario.telefono"
                    label="Teléfono"
                    outlined
                    dense
                    mask="###-###-####"
                  />
                </div>
                <div class="col-12 col-md-6">
                  <q-input
                    v-model="usuario.departamento"
                    label="Departamento"
                    outlined
                    dense
                  />
                </div>
              </div>

              <q-separator class="q-my-md" />

              <div class="text-h6 q-mb-md">Cambiar Contraseña</div>
              <div class="row q-col-gutter-md">
                <div class="col-12 col-md-6">
                  <q-input
                    v-model="password.actual"
                    label="Contraseña Actual"
                    type="password"
                    outlined
                    dense
                  />
                </div>
                <div class="col-12 col-md-6">
                  <q-input
                    v-model="password.nueva"
                    label="Nueva Contraseña"
                    type="password"
                    outlined
                    dense
                  />
                </div>
                <div class="col-12 col-md-6">
                  <q-input
                    v-model="password.confirmacion"
                    label="Confirmar Nueva Contraseña"
                    type="password"
                    outlined
                    dense
                    :rules="[
                      val => val === password.nueva || 'Las contraseñas no coinciden'
                    ]"
                  />
                </div>
              </div>

              <div class="row justify-end q-mt-lg">
                <q-btn
                  type="submit"
                  color="primary"
                  label="Guardar Cambios"
                  :loading="guardando"
                />
              </div>
            </q-form>
          </q-card-section>
        </q-card>
      </div>
    </div>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useQuasar } from 'quasar'
//import { Dialog } from 'quasar'
import { date } from 'quasar'
import { authService } from '../../services/authService'

const $q = useQuasar()
const guardando = ref(false)

const usuario = ref({
  name: '',
  email: '',
  telefono: '',
  departamento: '',
  rol: '',
  avatar: 'https://cdn.pixabay.com/photo/2018/08/28/12/41/avatar-3637425_960_720.png',
  ultimoAcceso: '',
  activosAsignados: 0,
  mantenimientosPendientes: 0
})

const sesionesActivas = ref([])
const currentTokenId = ref('')

const password = ref({
  actual: '',
  nueva: '',
  confirmacion: ''
})

const getBrowserInfo = (userAgent) => {
  if (userAgent.includes('Chrome')) return 'Chrome'
  if (userAgent.includes('Firefox')) return 'Firefox'
  if (userAgent.includes('Safari')) return 'Safari'
  if (userAgent.includes('Edge')) return 'Edge'
  return 'Navegador desconocido'
}

const formatDate = (dateString) => {
  return date.formatDate(dateString, 'DD/MM/YYYY HH:mm')
}

const isCurrentSession = (session) => {
  return session.token_id === currentTokenId.value
}

const confirmarCerrarSesion = (session) => {
  $q.dialog({
    title: 'Confirmar',
    message: `¿Estás seguro de que deseas cerrar la sesión de ${getBrowserInfo(session.user_agent)}?`,
    cancel: true,
    persistent: true
  }).onOk(async () => {
    await cerrarSesion(session.id)
  })
}

const confirmarCerrarTodasSesiones = () => {
  $q.dialog({
    title: 'Confirmar',
    message: '¿Estás seguro de que deseas cerrar todas las sesiones activas? Esto cerrará tu sesión en todos los dispositivos.',
    cancel: true,
    persistent: true
  }).onOk(async () => {
    await cerrarTodasSesiones()
  })
}

const cerrarSesion = async (sessionId) => {
  try {
    const response = await authService.revokeSession(sessionId)
    if (response.success) {
      await cargarSesiones()
      $q.notify({
        type: 'positive',
        message: response.message || 'Sesión cerrada correctamente',
        position: 'top'
      })
    } else {
      throw new Error(response.message || 'Error al cerrar la sesión')
    }
  } catch (error) {
    console.error('Error al cerrar sesión:', error)
    $q.notify({
      type: 'negative',
      message: error.message || 'Error al cerrar la sesión',
      position: 'top'
    })
  }
}

const cerrarTodasSesiones = async () => {
  try {
    const response = await authService.revokeAllSessions()
    if (response.success) {
      await cargarSesiones()
      $q.notify({
        type: 'positive',
        message: response.message || 'Todas las sesiones han sido cerradas',
        position: 'top'
      })
    } else {
      throw new Error(response.message || 'Error al cerrar todas las sesiones')
    }
  } catch (error) {
    console.error('Error al cerrar todas las sesiones:', error)
    $q.notify({
      type: 'negative',
      message: error.message || 'Error al cerrar todas las sesiones',
      position: 'top'
    })
  }
}

const cargarSesiones = async () => {
  try {
    sesionesActivas.value = await authService.getActiveSessions()
  } catch (error) {
    console.error('Error al cargar sesiones:', error)
  }
}

onMounted(async () => {
  try {
    const userData = await authService.getCurrentUser()
    usuario.value = {
      ...usuario.value,
      ...userData,
      ultimoAcceso: date.formatDate(Date.now(), 'DD/MM/YYYY HH:mm')
    }
    await cargarSesiones()
  } catch (error) {
    console.error('Error al cargar datos del usuario:', error)
    $q.notify({
      type: 'negative',
      message: 'Error al cargar datos del usuario',
      position: 'top'
    })
  }
})

const cambiarFoto = () => {
  // Aquí iría la lógica para cambiar la foto
  $q.notify({
    type: 'info',
    message: 'Función de cambio de foto en desarrollo',
    position: 'top'
  })
}

const guardarPerfil = async () => {
  guardando.value = true
  try {
    // Actualizar datos del perfil
    const profileData = {
      name: usuario.value.name,
      email: usuario.value.email
    }
    await authService.updateProfile(profileData)

    // Si se proporcionó una nueva contraseña, actualizarla
    if (password.value.nueva && password.value.actual) {
      const passwordData = {
        current_password: password.value.actual,
        password: password.value.nueva,
        password_confirmation: password.value.confirmacion
      }
      await authService.updatePassword(passwordData)
    }

    $q.notify({
      type: 'positive',
      message: 'Perfil actualizado correctamente',
      position: 'top'
    })

    // Limpiar campos de contraseña
    password.value = {
      actual: '',
      nueva: '',
      confirmacion: ''
    }
  } catch (error) {
    const mensaje = error.message || 'Error al actualizar el perfil'
    const tipo = error.response?.data?.success === false ? 'warning' : 'negative'
    
    $q.notify({
      type: tipo,
      message: mensaje,
      position: 'top'
    })
  } finally {
    guardando.value = false
  }
}
</script>

<style lang="scss" scoped>
.q-card {
  transition: all 0.3s ease;
  
  &:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  }
}

.q-avatar {
  border: 4px solid var(--q-primary);
}
</style> 