<template>
  <q-layout view="lHh Lpr lFf" class="bg-grey-1 m-8">
    <q-header elevated class="bg-primary text-white">
      <q-toolbar>
        <q-btn flat dense round icon="menu" @click="toggleLeftDrawer" />

        <q-toolbar-title class="row items-center">
          <span class="text-weight-bold">SIUP</span>
          <!-- <span class="text-caption q-ml-sm">una puno</span> -->
        </q-toolbar-title>

        <q-space />

        <q-btn round dense flat :icon="$q.dark.isActive ? 'dark_mode' : 'light_mode'" class="q-mr-xs"
          @click="toggleDarkMode">
          <q-tooltip>
            {{ $q.dark.isActive ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro' }}
          </q-tooltip>
        </q-btn>

        <q-btn round dense flat icon="notifications" class="q-mr-xs">
          <q-badge color="red" floating transparent v-if="pendingNotifications > 0">
            {{ pendingNotifications }}
          </q-badge>
          <q-menu>
            <q-list style="min-width: 350px">
              <q-item-label header class="text-weight-bold">
                <q-icon name="swap_horiz" class="q-mr-sm" />
                Notificaciones de Movimientos
              </q-item-label>
              
              <q-item v-if="loadingNotifications" class="text-center q-py-md">
                <q-spinner color="primary" size="2em" />
                <div class="text-caption q-mt-sm">Cargando notificaciones...</div>
              </q-item>
              
              <template v-else>
                <q-item v-if="notifications.length === 0" class="text-center q-py-md">
                  <q-icon name="notifications_off" size="3em" color="grey-5" />
                  <div class="text-caption q-mt-sm text-grey-6">No hay notificaciones pendientes</div>
                </q-item>
                
                <q-item v-for="(notification, index) in notifications" :key="index" clickable v-close-popup @click="handleNotificationClick(notification)">
                  <q-item-section avatar>
                    <q-icon :name="getNotificationIcon(notification.tipo)" :color="getNotificationColor(notification.tipo, notification.estado)" />
                  </q-item-section>
                  <q-item-section>
                    <q-item-label class="text-weight-medium">{{ notification.titulo }}</q-item-label>
                    <q-item-label caption lines="2">{{ notification.mensaje }}</q-item-label>
                    <q-item-label caption class="text-grey-6">
                      <q-icon name="schedule" size="xs" />
                      {{ formatNotificationTime(notification.fecha_creacion) }}
                    </q-item-label>
                  </q-item-section>
                  <q-item-section side>
                    <q-btn flat round dense icon="more_vert" @click.stop="showNotificationActions(notification, $event)">
                      <q-menu>
                        <q-list>
                          <q-item clickable v-close-popup @click="markAsRead(notification.id)">
                            <q-item-section avatar>
                              <q-icon name="check_circle" color="positive" />
                            </q-item-section>
                            <q-item-section>Marcar como leída</q-item-section>
                          </q-item>
                          <q-item clickable v-close-popup @click="goToMovimiento(notification.movimiento_id)">
                            <q-item-section avatar>
                              <q-icon name="visibility" color="primary" />
                            </q-item-section>
                            <q-item-section>Ver movimiento</q-item-section>
                          </q-item>
                        </q-list>
                      </q-menu>
                    </q-btn>
                  </q-item-section>
                </q-item>
              </template>
              
              <q-separator />
              <q-item clickable v-close-popup @click="goToMovimientos">
                <q-item-section class="text-center text-primary">
                  <q-icon name="swap_horiz" class="q-mr-sm" />
                  Ver todos los movimientos
                </q-item-section>
              </q-item>
            </q-list>
          </q-menu>
        </q-btn>

        <q-btn round flat>
          <q-avatar size="56px" color="#ccc" text-color="#555">
            <q-icon name="person" size="32px" />
          </q-avatar>
          <q-menu>
            <q-list style="min-width: 200px">
              <q-item clickable v-close-popup to="/perfil">
                <q-item-section avatar>
                  <q-icon name="account_circle" />
                </q-item-section>
                <q-item-section>Mi perfil</q-item-section>
              </q-item>
              <q-separator />
              <q-item clickable v-close-popup @click="logout">
                <q-item-section avatar>
                  <q-icon name="logout" />
                </q-item-section>
                <q-item-section>Cerrar sesión</q-item-section>
              </q-item>
            </q-list>
          </q-menu>
        </q-btn>
      </q-toolbar>
    </q-header>

    <q-drawer v-model="leftDrawerOpen" show-if-above bordered :width="240"
      :class="$q.dark.isActive ? 'bg-black text-white' : 'bg-white text-primary'">
      <q-scroll-area style="height: calc(100% - 180px); margin-top: 180px">
        <q-list padding>
          <q-item-label header class="text-weight-bold">
            Gestión Principal
          </q-item-label>

          <q-item v-if="auth.isAdmin || auth.isSupervisor || auth.isResponsable || auth.isConsulta" clickable v-ripple exact to="/">
            <q-item-section avatar>
              <q-icon name="dashboard" />
            </q-item-section>
            <q-item-section>Dashboard</q-item-section>
            <q-item-section side v-if="dashboardAlerts > 0">
              <q-badge color="red" rounded floating>{{ dashboardAlerts }}</q-badge>
            </q-item-section>
          </q-item>

          <q-item v-if="auth.isAdmin || auth.isSupervisor || auth.isResponsable || auth.isConsulta" clickable v-ripple exact to="/activos">
            <q-item-section avatar>
              <q-icon name="inventory_2" />
            </q-item-section>
            <q-item-section>Activos</q-item-section>
          </q-item>
          <q-item v-if="auth.isSoftware" clickable v-ripple exact to="/software">
            <q-item-section avatar>
              <q-icon name="widgets" />
            </q-item-section>
            <q-item-section>Software</q-item-section>
          </q-item>
          <q-item v-if="auth.isInstaller" clickable v-ripple exact to="/software/agregar-activo">
            <q-item-section avatar>
              <q-icon name="install_desktop" />
            </q-item-section>
            <q-item-section>Instalar Software</q-item-section>
          </q-item>
          <q-item v-if="auth.isAdmin || auth.isSupervisor || auth.isResponsable || auth.isConsulta" clickable v-ripple to="/movimientos">
            <q-item-section avatar>
              <q-icon name="swap_horiz" />
            </q-item-section>
            <q-item-section>Movimientos</q-item-section>
          </q-item>
          <q-item v-if="auth.isAdmin" clickable v-ripple exact to="/activos/categorias">
            <q-item-section avatar>
              <q-icon name="category" />
            </q-item-section>
            <q-item-section>Categorías</q-item-section>
          </q-item>
          <q-item v-if="auth.isAdmin || auth.isSupervisor || auth.isResponsable" clickable v-ripple to="/areas">
            <q-item-section avatar>
              <q-icon name="map" />
            </q-item-section>
            <q-item-section>Areas</q-item-section>
          </q-item>
          <q-item v-if="auth.isAdmin || auth.isSupervisor" clickable v-ripple to="/oficinas">
            <q-item-section avatar>
              <q-icon name="business" />
            </q-item-section>
            <q-item-section>Oficinas</q-item-section>
          </q-item>
          <q-item v-if="auth.isAdmin" clickable v-ripple to="/entidades">
            <q-item-section avatar>
              <q-icon name="account_balance"/>
            </q-item-section>
            <q-item-section>Gestión de Unidades de Alta Dirección/Entidad</q-item-section>
          </q-item>
          <q-item v-if="auth.isInventariador" clickable v-ripple to="/inventariador">
            <q-item-section avatar>
              <q-icon name="assignment" />
            </q-item-section>
            <q-item-section>Inventariar Bienes</q-item-section>
          </q-item>
          <q-item v-if="auth.isInventariador" clickable v-ripple to="/inventariador">
            <q-item-section avatar>
              <q-icon name="assignment" />
            </q-item-section>
            <q-item-section>Acta de verificación</q-item-section>
          </q-item>
          <q-separator class="q-my-md" />

          <q-item-label v-if="auth.isAdmin || auth.isSupervisor || auth.isSoftware" header class="text-weight-bold">
            Administración
          </q-item-label>

          <q-item v-if="auth.isAdmin || auth.isSupervisor || auth.isSoftware" clickable v-ripple to="/usuarios">
            <q-item-section avatar>
              <q-icon name="people" />
            </q-item-section>
            <q-item-section>Usuarios</q-item-section>
          </q-item>
          <q-item v-if="auth.isAdmin" clickable v-ripple to="/configuracion">
            <q-item-section avatar>
              <q-icon name="settings" />
            </q-item-section>
            <q-item-section>Configuracion/Reporte</q-item-section>
          </q-item>
        </q-list>
      </q-scroll-area>

      <q-img class="text-center absolute-top bg-primary" style="height: 180px">
        <div class="absolute-bottom bg-transparent q-pa-sm">
          <div size="80px" class="q-mb-md" square>
            <!-- <img :src="user.avatar" /> -->
             <q-img src="~assets/imgs/Logo_UNAP.png" style="max-width: 72px;"></q-img>
          </div>
          <div class="text-weight-bold text-white">{{ user.name }}</div>
          <div class="text-caption text-grey-3">{{ user.role }}</div>
          <div class="text-caption text-grey-3">Último acceso: {{ lastAccess }}</div>
        </div>
      </q-img>
    </q-drawer>
    <DialogModal v-model:show="modal.show" :title="modal.title" :mode="modal.mode">
      <DynamicForm
      :fields="modernFormFields"
      v-model="formData"
      :mode="modal.mode"
      :loading="loading"
      @submit="handleFormSubmit"
      @cancel="handleClouse"
      />
    </DialogModal>
    <q-page-container>
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { date } from 'quasar'
import { authService } from '../services/authService'
import { useAuthStore } from '../stores/auth-store'
import { movimientoService } from '../services/movimientoService'
import DialogModal from 'src/components/DialogModal.vue'
import DynamicForm from 'src/components/DynamicForm.vue'
import { oficinaService } from 'src/services/oficinaService'
import { userService } from 'src/services/userService'

const $q = useQuasar()
const router = useRouter()
const auth = useAuthStore()
const leftDrawerOpen = ref(false)
const lastAccess = ref(date.formatDate(Date.now(), 'DD/MM/YYYY HH:mm'))
const formData=ref({})
const loading=ref(false)
const modal=ref({
  show: false,
  title: 'Actualizar',
  mode: 'edit'
})

const toggleDarkMode = () => {
  $q.dark.toggle()
  localStorage.setItem('darkMode', $q.dark.isActive.toString())
}
const modernFormFields = ref([
  { type: 'separator', label: 'Datos Personales'},
  { name: 'dni', type: 'text', label: 'Numero de DNI del usuario', rules: [val => !!val || 'El campo es requerido', val => /^\d{8}$/.test(val) || 'El DNI debe tener 8 dígitos numéricos'], prepend: 'badge', autogrow: true, maxlength: 8},
  { name: 'name', type: 'text', label: 'Nombre del usuario', rules: [val => !!val || 'El nombre de usuario es obligatorio'], prepend: 'account_circle', autogrow: true, uppercase: true},
  { name: 'email', type: 'email', label: 'Correo electrónico institucional', rules: [val => !!val || 'El correo electrónico es obligatorio', val => /unap\.edu\.pe$/.test(val) || 'Debe ser un correo institucional (@unap.edu.pe)'], prepend: 'email', autogrow: true},
  { name: 'telefono', type: 'number', label: 'Numero de telefono', rules: [val => !!val || 'El número de teléfono es obligatorio', val => /^\d{9}$/.test(val) || 'El número debe tener 9 dígitos numéricos'], prepend: 'phone', autogrow: true},
  { type: 'separator', label: '⚙️ Configuración de Usuario'},
  { name: 'oficinas', type: 'select', label: 'Oficinas', multiple: true, rules: [val => val && val.length > 0 || 'Debe seleccionar al menos una oficina'], options: [], prepend: 'business', useInput: true, fillInput: true},
  { type: 'separator', label: 'Contraseña del usuario'},
  { name: 'password', type: 'password', label: 'Ingrese la contraseña', rules: [val => !!val || 'El campo es obligatorio', val => typeof val === 'string' || 'Debe ser una cadena', val => val.length >= 8 || 'Debe tener al menos 8 caracteres', val => /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&.]+$/.test(val) || 'Debe contener mayúsculas, minúsculas, números y un carácter especial'], prepend: 'lock', autogrow: true},
  { name: 'password_confirmation', type: 'password', label: 'confirma la contraseña', rules: [val => !!val || 'El campo es obligatorio', val => val === formData.value.password || 'Las contraseñas no coinciden'], prepend: 'lock', autogrow: true}
])
onMounted(async() => {
  if(auth.isAdmin, auth.isSupervisor, auth.isResponsable, auth.isConsulta){
    const currentUser = await authService.getCurrentUser()
    const oficinas = await oficinaService.getOficinas({})
    const oficinasFormateadas = oficinas.data.map(ofe=>({
      label: ofe.denominacion,
      value: ofe.id
    }))
    modernFormFields.value.find(f=>f.name==='oficinas').options=oficinasFormateadas
    formData.value={
      id: currentUser.id,
      dni: currentUser.dni,
      name: currentUser.name,
      email: currentUser.email,
      telefono: currentUser.telefono,
      oficinas: currentUser.oficinas
    }
    if ((!currentUser.oficinas || currentUser.oficinas.length === 0) && !auth.isAdmin) {
      modal.value.show = true
    }
  }
  const darkMode = localStorage.getItem('darkMode')
  if (darkMode !== null) {
    $q.dark.set(darkMode === 'true')
  } else {
    // Si no hay preferencia guardada, usar la preferencia del sistema
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches
    $q.dark.set(prefersDark)
  }
})

const user = ref({
  name: '',
  role: '',
  //avatar: 'https://cdn.quasar.dev/img/avatar.png'
})

// Variables para notificaciones
const notifications = ref([])
const loadingNotifications = ref(false)
const pendingNotifications = computed(() => notifications.value.filter(n => !n.leida).length)

// Funciones para notificaciones
const loadNotifications = async () => {
  try {
    loadingNotifications.value = true
    const currentUser = await authService.getCurrentUser()
    const notifications = []
    
    // Obtener movimientos según el rol y estado
    if (auth.isAdmin || auth.isSupervisor) {
      // Admin y Supervisor ven todos los movimientos pendientes
      const pendientesResponse = await movimientoService.getMovimientos({ estado: 'pendiente', per_page: 10 })
      const pendientes = pendientesResponse.data || []
      
      pendientes.forEach(mov => {
        notifications.push({
          id: mov.id,
          tipo: getMovimientoTipo(mov),
          titulo: getMovimientoTitulo(mov),
          mensaje: getMovimientoMensaje(mov),
          fecha_creacion: mov.fecha_creacion,
          movimiento_id: mov.id,
          leida: false,
          estado: 'pendiente'
        })
      })
      
      // También ven movimientos en_entrega
      const enEntregaResponse = await movimientoService.getMovimientos({ estado: 'en_entrega', per_page: 10 })
      const enEntrega = enEntregaResponse.data || []
      
      enEntrega.forEach(mov => {
        notifications.push({
          id: mov.id,
          tipo: getMovimientoTipo(mov),
          titulo: getMovimientoTitulo(mov),
          mensaje: getMovimientoMensaje(mov),
          fecha_creacion: mov.fecha_creacion,
          movimiento_id: mov.id,
          leida: false,
          estado: 'en_entrega'
        })
      })
      
    } else if (auth.isResponsable) {
      // Responsable ve movimientos pendientes de su departamento
      const pendientesResponse = await movimientoService.getMovimientos({ 
        estado: 'pendiente', 
        departamento_id: currentUser.departamento?.id,
        per_page: 10 
      })
      const pendientes = pendientesResponse.data || []
      
      pendientes.forEach(mov => {
        notifications.push({
          id: mov.id,
          tipo: getMovimientoTipo(mov),
          titulo: getMovimientoTitulo(mov),
          mensaje: getMovimientoMensaje(mov),
          fecha_creacion: mov.fecha_creacion,
          movimiento_id: mov.id,
          leida: false,
          estado: 'pendiente'
        })
      })
      
      // También ve movimientos en_entrega donde es responsable_destino
      const enEntregaResponse = await movimientoService.getMovimientos({ 
        estado: 'en_entrega', 
        responsable_destino_id: currentUser.id,
        per_page: 10 
      })
      const enEntrega = enEntregaResponse.data || []
      
      enEntrega.forEach(mov => {
        notifications.push({
          id: mov.id,
          tipo: getMovimientoTipo(mov),
          titulo: getMovimientoTitulo(mov),
          mensaje: getMovimientoMensaje(mov),
          fecha_creacion: mov.fecha_creacion,
          movimiento_id: mov.id,
          leida: false,
          estado: 'en_entrega'
        })
      })
      
    } else if (auth.isConsulta) {
      // Usuario consulta solo ve movimientos en_entrega donde es responsable_destino
      const enEntregaResponse = await movimientoService.getMovimientos({ 
        estado: 'en_entrega', 
        responsable_destino_id: currentUser.id,
        per_page: 10 
      })
      const enEntrega = enEntregaResponse.data || []
      
      enEntrega.forEach(mov => {
        notifications.push({
          id: mov.id,
          tipo: getMovimientoTipo(mov),
          titulo: getMovimientoTitulo(mov),
          mensaje: getMovimientoMensaje(mov),
          fecha_creacion: mov.fecha_creacion,
          movimiento_id: mov.id,
          leida: false,
          estado: 'en_entrega'
        })
      })
    }
    
    // Ordenar por fecha de creación (más recientes primero)
    notifications.sort((a, b) => new Date(b.fecha_creacion) - new Date(a.fecha_creacion))
    
    // Limitar a 10 notificaciones
    notifications.value = notifications.slice(0, 10)
    
  } catch (error) {
    console.error('Error al cargar notificaciones:', error)
    notifications.value = []
  } finally {
    loadingNotifications.value = false
  }
}

const getMovimientoTipo = (movimiento) => {
  if (movimiento.tipo === 'entrega') return 'entrega'
  if (movimiento.tipo === 'devolucion') return 'devolucion'
  if (movimiento.tipo === 'transferencia') return 'transferencia'
  return 'general'
}

const getMovimientoTitulo = (movimiento) => {
  const estado = movimiento.estado || 'pendiente'
  
  if (estado === 'pendiente') {
    switch (movimiento.tipo) {
      case 'entrega':
        return 'Entrega de Activos Pendiente de Aprobación'
      case 'devolucion':
        return 'Devolución de Activos Pendiente de Aprobación'
      case 'transferencia':
        return 'Transferencia de Activos Pendiente de Aprobación'
      default:
        return 'Movimiento Pendiente de Aprobación'
    }
  } else if (estado === 'en_entrega') {
    switch (movimiento.tipo) {
      case 'entrega':
        return 'Entrega de Activos en Proceso'
      case 'devolucion':
        return 'Devolución de Activos en Proceso'
      case 'transferencia':
        return 'Transferencia de Activos en Proceso'
      default:
        return 'Movimiento en Proceso de Entrega'
    }
  }
  
  return 'Movimiento Pendiente'
}

const getMovimientoMensaje = (movimiento) => {
  const activos = movimiento.activos?.length || 0
  const solicitante = movimiento.solicitante?.nombre || 'Usuario'
  const estado = movimiento.estado || 'pendiente'
  
  if (estado === 'pendiente') {
    switch (movimiento.tipo) {
      case 'entrega':
        return `${solicitante} solicita entrega de ${activos} activo(s) - Requiere aprobación`
      case 'devolucion':
        return `${solicitante} solicita devolución de ${activos} activo(s) - Requiere aprobación`
      case 'transferencia':
        return `${solicitante} solicita transferencia de ${activos} activo(s) - Requiere aprobación`
      default:
        return `Movimiento de ${activos} activo(s) pendiente de aprobación`
    }
  } else if (estado === 'en_entrega') {
    switch (movimiento.tipo) {
      case 'entrega':
        return `${solicitante} está entregando ${activos} activo(s) - Pendiente de recepción`
      case 'devolucion':
        return `${solicitante} está devolviendo ${activos} activo(s) - Pendiente de recepción`
      case 'transferencia':
        return `${solicitante} está transfiriendo ${activos} activo(s) - Pendiente de recepción`
      default:
        return `Movimiento de ${activos} activo(s) en proceso de entrega`
    }
  }
  
  return `Movimiento de ${activos} activo(s) pendiente de revisión`
}

const getNotificationIcon = (tipo) => {
  switch (tipo) {
    case 'entrega':
      return 'local_shipping'
    case 'devolucion':
      return 'keyboard_return'
    case 'transferencia':
      return 'swap_horiz'
    default:
      return 'notifications'
  }
}

const getNotificationColor = (tipo, estado = 'pendiente') => {
  if (estado === 'en_entrega') {
    switch (tipo) {
      case 'entrega':
        return 'teal'
      case 'devolucion':
        return 'deep-orange'
      case 'transferencia':
        return 'indigo'
      default:
        return 'blue-grey'
    }
  } else {
    // Estado pendiente
    switch (tipo) {
      case 'entrega':
        return 'primary'
      case 'devolucion':
        return 'orange'
      case 'transferencia':
        return 'purple'
      default:
        return 'grey'
    }
  }
}

const formatNotificationTime = (fecha) => {
  if (!fecha) return 'Recién'
  
  const now = new Date()
  const notificationDate = new Date(fecha)
  const diffMs = now - notificationDate
  const diffMins = Math.floor(diffMs / 60000)
  const diffHours = Math.floor(diffMs / 3600000)
  const diffDays = Math.floor(diffMs / 86400000)
  
  if (diffMins < 1) return 'Recién'
  if (diffMins < 60) return `Hace ${diffMins} min`
  if (diffHours < 24) return `Hace ${diffHours} h`
  if (diffDays < 7) return `Hace ${diffDays} días`
  
  return date.formatDate(notificationDate, 'DD/MM/YYYY')
}

const handleNotificationClick = (notification) => {
  goToMovimiento(notification.movimiento_id)
}

const goToMovimiento = (movimientoId) => {
  router.push(`/movimientos?id=${movimientoId}`)
}

const goToMovimientos = () => {
  router.push('/movimientos')
}

const markAsRead = async (notificationId) => {
  try {
    // Aquí se podría implementar la lógica para marcar como leída en el backend
    const notification = notifications.value.find(n => n.id === notificationId)
    if (notification) {
      notification.leida = true
    }
  } catch (error) {
    console.error('Error al marcar notificación como leída:', error)
  }
}

const showNotificationActions = (notification, event) => {
  console.log(notification, event)
}

const dashboardAlerts = computed(() => pendingNotifications.value)

function toggleLeftDrawer() {
  leftDrawerOpen.value = !leftDrawerOpen.value
}

// Cargar notificaciones al montar el componente
onMounted(async () => {
  try {
    const currentUser = await authService.getCurrentUser()
    user.value = {
      name: currentUser.name,
      role: currentUser.role?.name || 'Usuario',
      avatar: currentUser.avatar || user.value.avatar
    }

    await loadNotifications()
  } catch (error) {
    console.error('Error al obtener información del usuario:', error)
  }
})

setInterval(() => {
  if (auth.isAuthenticated) {
    loadNotifications()
  }
}, 300000)

async function logout() {
  try {
    await authService.logout()
    $q.notify({
      type: 'positive',
      message: 'Sesión cerrada correctamente',
      position: 'top'
    })
    router.push('/login')
  } catch (error) {
    console.error('Error al cerrar sesión:', error)
    $q.notify({
      type: 'negative',
      message: `Error al cerrar sesión: ${error.message || 'Error desconocido'}`,
      position: 'top'
    })
  }
}

const handleFormSubmit = async() => {
  loading.value=true
  const submitdate = {
    ...formData.value,
    telefono: `${formData.value.telefono}`,
    oficinas: Array.isArray(formData.value.oficinas) ? formData.value.oficinas.map(of => of.value) : []
  }
  await userService.updateUsuario(submitdate.id, submitdate)
  $q.notify({
    color: 'positive',
    message: 'Usuario actualizado',
    icon: 'check'
  })
  modal.value.show=false
  loading.value=false
}

const handleClouse =()=>{
  $q.notify({
    type: 'negative',
    message: 'Actualiza los datos',
    position: 'top'
  })
}

</script>

<style lang="scss">
.q-drawer {
  .q-item {
    border-radius: 0 24px 24px 0;
    margin-right: 8px;
    
    &.q-router-link--active {
      background-color: var(--q-primary);
      color: white;
      .q-icon {
        color: white;
      }
    }
    
    &:hover {
      background-color: rgba(var(--q-primary-rgb), 0.1);
    }
  }
  
  .q-expansion-item {
    .q-item {
      padding-left: 56px;
    }
  }
}

.q-header {
  .q-toolbar {
    min-height: 64px;
  }
  
  .q-breadcrumbs {
    font-size: 16px;
  }
}

.q-footer {
  .q-toolbar {
    min-height: 42px;
  }
}

// Estilos para modo oscuro
body.body--dark {
  .q-drawer {
    background: #121212;
    color: #fff;

    .q-item {
      color: #fff;
      
      &.q-router-link--active {
        background-color: var(--q-primary);
        color: white;
      }
      
      &:hover {
        background-color: rgba(255, 255, 255, 0.05);
      }
    }

    .q-item-label {
      color: #fff;
    }

    .q-separator {
      background: rgba(255, 255, 255, 0.12);
    }
  }

  .q-header {
    background: #121212;
  }

  .q-page-container {
    background: #121212;
  }
}

// Estilos para modo claro
body.body--light {
  .q-drawer {
    background: #fff;
    color: #000;

    .q-item {
      color: #000;
      
      &.q-router-link--active {
        background-color: var(--q-primary);
        color: white;
      }
      
      &:hover {
        background-color: rgba(0, 0, 0, 0.05);
      }
    }

    .q-item-label {
      color: #000;
    }
  }

  .q-header {
    background: var(--q-primary);
  }

  .q-page-container {
    background: #f5f5f5;
  }
}
</style>