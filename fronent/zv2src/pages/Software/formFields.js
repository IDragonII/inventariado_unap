import { ref, computed } from 'vue';

export function useSoftwareForm(formData) {
  const baseFields = ref([
    { name: 'tipo', type: 'select', label: 'Tipo de Activo', options: [
        { label: 'Sistema Interno', value: 'desarrollo_interno' },
        { label: 'Licencia de Terceros', value: 'licencia_terceros' },
        { label: 'Red Social', value: 'red_social' }
      ], rules: [val => !!val || 'El tipo es requerido'], prepend: 'category'
    },
    { name: 'nombre', type: 'text', label: 'Nombre', rules: [val => !!val || 'El nombre es requerido'], prepend: 'label' },
    { name: 'descripcion', type: 'textarea', label: 'Descripción', autogrow: true, prepend: 'description' },
    { name: 'responsable_id', type: 'select', label: 'Responsable', options: [], rules: [val => !!val || 'El responsable es requerido'], prepend: 'person', useInput: true, fillInput: true },
    { name: 'area_id', type: 'select', label: 'Área', options: [], rules: [val => !!val || 'El área es requerida'], prepend: 'location_on', useInput: true, fillInput: true },
    { name: 'estado', type: 'select', label: 'Estado', options: [
        { label: 'Activo', value: 'activo' },
        { label: 'Inactivo', value: 'inactivo' },
        { label: 'En Desarrollo', value: 'en_desarrollo' },
        { label: 'Vencido', value: 'vencido' }
      ], rules: [val => !!val || 'El estado es requerido'], prepend: 'toggle_on'
    },
    { name: 'notas', type: 'textarea', label: 'Notas', autogrow: true, prepend: 'notes' }
  ]);

  const tipoFields = {
    desarrollo_interno: [
      { type: 'separator', label: 'Detalles del Sistema' },
      { name: 'url_acceso', type: 'text', label: 'URL de Acceso', rules: [val => !val || /^(https?|ftp):\/\/[^\s/$.?#].[^\s]*$/i.test(val) || 'URL no válida'], prepend: 'link' },
      { name: 'tecnologias', type: 'text', label: 'Tecnologías', prepend: 'code' },
      { name: 'ubicacion_servidor', type: 'text', label: 'Ubicación del Servidor', prepend: 'dns' }
    ],
    licencia_terceros: [
      { type: 'separator', label: 'Detalles de la Licencia' },
      { name: 'clave_licencia', type: 'text', label: 'Clave de Licencia', prepend: 'key' },
      { name: 'tipo_licencia', type: 'select', label: 'Tipo de Licencia', options: [
          { label: 'Perpetua', value: 'perpetua' },
          { label: 'Suscripción', value: 'suscripcion' },
          { label: 'Volumen', value: 'volumen' }
        ], prepend: 'receipt_long'
      },
      { name: 'cantidad_puestos', type: 'number', label: 'Cantidad de Puestos', prepend: 'group' },
      { name: 'fecha_compra', type: 'date', label: 'Fecha de Compra', prepend: 'event' },
      { name: 'fecha_vencimiento', type: 'date', label: 'Fecha de Vencimiento', prepend: 'event_busy' },
      { name: 'activos_asignados', type: 'select', label: 'Asignado a Activos', multiple: true, options: [], prepend: 'devices', useInput: true, fillInput: true }
    ],
    red_social: [
      { type: 'separator', label: 'Detalles de la Cuenta' },
      { name: 'plataforma', type: 'text', label: 'Plataforma (ej. Facebook)', rules: [val => !!val || 'La plataforma es requerida'], prepend: 'public' },
      { name: 'url_perfil', type: 'text', label: 'URL del Perfil', rules: [val => !val || /^(https?|ftp):\/\/[^\s/$.?#].[^\s]*$/i.test(val) || 'URL no válida'], prepend: 'link' },
      { name: 'correo_institucional', type: 'email', label: 'Correo Institucional', rules: [val => !val || /unap\.edu\.pe$/.test(val) || 'Debe ser un correo @est.unap.edu.pe'], prepend: 'email' }
    ]
  };

  const formFields = computed(() => {
    const tipo = formData.value.tipo?.value || formData.value.tipo;
    if (!tipo) {
      return baseFields.value.slice(0, 1);
    }
    return [...baseFields.value, ...(tipoFields[tipo] || [])];
  });

  return { baseFields, tipoFields, formFields };
}
