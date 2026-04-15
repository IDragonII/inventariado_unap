<template>
  <q-dialog :model-value="modelValue" @update:model-value="emit('update:modelValue', $event)">
    <q-card style="min-width:400px;max-width:500px;">
      <q-card-section>
        <div class="text-h6">{{ usuario && usuario.id ? 'Editar Usuario' : 'Nuevo Usuario' }}</div>
        <q-input v-model="form.name" label="Nombre" dense outlined class="q-mb-sm" :rules="[val => !!val || 'El campo es obligatoria']"/>
        <q-input v-model="form.dni" label="DNI" dense outlined class="q-mb-sm" mask="########" :rules="[val => !!val || 'El campo es obligatoria', val => val.length === 8 || 'El DNI debe tener exactamente 8 dígitos']"/>
        <q-input v-model="form.email" label="Email" dense outlined class="q-mb-sm" :rules="[val => !!val || 'El campo es obligatoria']"/>
        <q-input v-model="form.telefono" label="Teléfono" dense outlined class="q-mb-sm" :rules="[val => !!val || 'El campo es obligatoria']"/>
        <q-select v-model="form.departamento_id" :options="departamentos" option-label="nombre" option-value="id" label="Departamento" dense outlined class="q-mb-sm" :rules="[val => !!val || 'El campo es obligatoria']"/>
        <q-input v-if="!usuario.name || !usuario.id" v-model="form.password" label="Contraseña" type="password" dense outlined class="q-mb-sm" :rules="[val => !!val || 'El campo es obligatoria']"/>
        <q-select v-model="form.roles" :options="roles" option-label="name" option-value="id" label="Roles" dense outlined class="q-mb-sm" emit-value map-options :rules="[val => !!val || 'El campo es obligatoria']"/>
      </q-card-section>
      <q-card-actions align="right">
        <q-btn flat label="Cancelar" color="primary" v-close-popup />
        <q-btn flat label="Guardar" color="primary" @click="guardar" />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import { userService } from 'src/services/userService'
import { departamentoService } from 'src/services/departamentoService'

const props = defineProps({
  modelValue: Boolean,
  usuario: Object
})
const emit = defineEmits(['update:modelValue', 'guardado'])

const form = ref({
  name: '',
  email: '',
  telefono: '',
  dni: '',
  departamento_id: null,
  password: '',
  roles: null
})
const departamentos = ref([])
const roles = ref([])

const cargarDepartamentos = async () => {
  departamentos.value = await departamentoService.getDepartamentos()
}
const cargarRoles = async () => {
  const res = await userService.getRoles()
  roles.value = res.data || res
}
watch(() => props.usuario, (nuevo) => {
  if (nuevo) {
    form.value = {
      name: nuevo.name || '',
      dni: nuevo.dni || '',
      email: nuevo.email || '',
      telefono: nuevo.telefono || '',
      departamento_id: nuevo.departamento_id || null,
      password: '',
      roles: (nuevo.roles && nuevo.roles.length > 0) ? nuevo.roles[0].id : null
    }
  } else {
    form.value = { name: '', dni: '', email: '', telefono: '', departamento_id: null, password: '', roles: null }
  }
}, { immediate: true })

const guardar = async () => {
  console.log(props.usuario.id)
  console.log('this is', form.value)
  await userService.updateUsuario(props.usuario.id, form.value)
  emit('update:modelValue', false)
  emit('guardado')
}

onMounted(() => {
  cargarDepartamentos()
  cargarRoles()
})
</script> 