<template>
  <PermissionsAccess :isAdmin="auth.isAdmin">
    <q-page class="q-pa-md">
      <div class="row">
        <!-- Columna Derecha: Tabla de Declaraciones de Uso -->
        <div class="col-12" style="padding: 3px;">
          <q-card class="q-mb-md">
            <q-card-section class="q-pa-sm row items-center">
              <q-input v-model="searchDeclaracion" dense outlined clearable placeholder="Buscar declaración..." class="col" @update:model-value="filtrarDeclaraciones" />
              <q-btn color="primary" icon="download" dense label="Descargar reporte" @click="descargarReporte" :loading="loadingDescargarReporte" :disable="loadingDescargarReporte" />
            </q-card-section>
            <q-separator />
            <q-card-section>
              <TableDynamic
                :columns="columns"
                :row="declaracionesFiltradas"
                row-key="id"
                :filter="searchDeclaracion"
                :pagination="pagination"
                :loading="loadingDeclaraciones"
                @update:pagination="onPagination"
              >
                <template #body-cell-actions="{ row }">
                  <q-btn
                    flat
                    dense
                    round
                    icon="download"
                    color="primary"
                    @click="descargarDeclaracionFila(row.id)"
                    :loading="loadingDescargaDeclaracionFila[row.id]"
                    :disable="loadingDescargaDeclaracionFila[row.id]"
                  >
                    <q-tooltip>Descargar declaración</q-tooltip>
                  </q-btn>
                </template>
              </TableDynamic>
            </q-card-section>
          </q-card>
        </div>
      </div>
      <!-- Modal para crear/editar ito -->
      <q-dialog v-model="modalIto.show" persistent>
        <q-card style="min-width: 180px; max-width: 250px;">
          <q-card-section class="q-pa-sm">
            <div class="text-h6">{{ modalIto.titulo }}</div>
          </q-card-section>
          <q-card-section class="q-pa-sm">
            <DynamicForm
              :fields="formFieldsIto"
              v-model="formDataIto"
              :mode="modalIto.modo"
              @submit="handleFormSubmitIto"
              @cancel="handleClouseIto"
              :loading="loadingFormIto"
            />
          </q-card-section>
        </q-card>
      </q-dialog>
    </q-page>
  </PermissionsAccess>
</template>
<script setup>
import { ref, onMounted } from 'vue';
import { useQuasar } from 'quasar';
import TableDynamic from 'src/components/TableDynamic.vue';
import DynamicForm from 'src/components/DynamicForm.vue';
import { itoService } from 'src/services/itoService';
import { declaracionService } from 'src/services/declaracionService';
import ExcelJS from 'exceljs'
import { useAuthStore } from 'src/stores/auth-store';
import PermissionsAccess from 'src/components/Permissions/PermissionsAccess.vue';

const $q = useQuasar();
const auth = useAuthStore();
const searchDeclaracion = ref('');
const declaraciones = ref([]);
const declaracionesFiltradas = ref([]);
const loadingDeclaraciones = ref(false);
const pagination = ref({
  sortBy: 'id',
  descending: false,
  page: 1,
  rowsPerPage: 20,
  rowsNumber: 0
});

// Modal y formulario para crear/editar ito
const modalIto = ref({ show: false, modo: 'create', titulo: 'Nuevo Ito' });
const formDataIto = ref({ denominacion: '', estado: true });
const loadingFormIto = ref(false);
const formFieldsIto = ref([
  { type: 'separator', label: 'Datos del Ito' },
  { name: 'denominacion', type: 'text', label: 'Denominación', rules: [val => !!val || 'El campo es requerido'], prepend: 'calendar_month', autogrow: true, uppercase: true },
  { name: 'estado', type: 'toggle', label: 'Activo', trueLabel: 'Activo', falseLabel: 'Inactivo', color: 'primary' }
]);

const columns = [
  { name: 'usuario', label: 'Usuario', field: row => row.user?.name, align: 'left' },
  { name: 'dni', label: 'DNI', field: row => row.user?.dni, align: 'left' },
  { name: 'fecha', label: 'Fecha', field: 'fecha_declaracion', align: 'left' },
  { name: 'ito', label: 'Detalle', field: 'ito', align: 'left' },
  { name: 'activos', label: 'N° Activos', field: 'activos_count', lign: 'left'},
  { name: 'actions', label: 'Acciones', align: 'center' }
];

const cargarDeclaraciones = async () => {
  loadingDeclaraciones.value = true;
  try {
    let fecha = String(new Date().getFullYear());
    const res = await declaracionService.getDeclaraciones({ search: fecha });
    declaraciones.value = res.data;
    declaracionesFiltradas.value = res.data;
    pagination.value.rowsNumber = res.meta?.total || res.data.length;
  } catch (e) {
    console.error(e)
    $q.notify({ color: 'negative', message: 'Error al cargar declaraciones', icon: 'error' });
  } finally {
    loadingDeclaraciones.value = false;
  }
};

const filtrarDeclaraciones = () => {
  declaracionesFiltradas.value = declaraciones.value.filter(d =>
    Object.values(d).some(val => String(val).toLowerCase().includes(searchDeclaracion.value.toLowerCase()))
  );
};

const handleFormSubmitIto = async (data) => {
  loadingFormIto.value = true;
  data.codigo=data.denominacion
  try {
    if (modalIto.value.modo === 'edit') {
      await itoService.updateIto(formDataIto.value.id, data);
    } else {
      data.estado=false
      await itoService.createIto(data);
    }
    $q.notify({ color: 'positive', message: 'Ito guardado', icon: 'check' });
    modalIto.value.show = false;
  } catch (e) {
    console.error(e)
    $q.notify({ color: 'negative', message: 'Error al guardar ito', icon: 'error' });
  } finally {
    loadingFormIto.value = false;
  }
};

const handleClouseIto = () => {
  modalIto.value.show = false;
};

const onPagination = (newPagination) => {
  pagination.value = { ...pagination.value, ...newPagination };
  cargarDeclaraciones();
};

const loadingDescargarReporte = ref(false)
const descargarReporte = async() => {
  loadingDescargarReporte.value = true
  try {
    let fecha = String(new Date().getFullYear());
    const response = await itoService.reporte({ito: fecha})
    // Procesar los datos para el Excel
    const workbook = new ExcelJS.Workbook();
    const worksheet = workbook.addWorksheet('Reporte');
    worksheet.columns = [
      { header: 'Código', key: 'codigo', width: 15 },
      { header: 'Descripción', key: 'denominacion', width: 30 },
      //{ header: 'Catálogo', key: 'catalogo', width: 20 },
      { header: 'Ubicación', key: 'ubicacion_primera', width: 30 },
      { header: 'Área/Edificio', key: 'area_edificio_primera', width: 25 },
      { header: 'Oficina', key: 'oficina_primera', width: 25 },
      { header: 'Entidad', key: 'entidad_primera', width: 25 },
      { header: 'Estado', key: 'estado_primera', width: 15 },
      { header: 'Condición', key: 'condicion_primera', width: 15 },
      { header: 'Fecha Declaración', key: 'fecha_declaracion_primera', width: 20 },
      { header: 'DNI', key: 'dni_primera', width: 15 },
      { header: 'Usuario', key: 'name_primera', width: 20 },
      { header: 'Ubicación', key: 'ubicacion_ito', width: 30 },
      { header: 'Área/Edificio', key: 'area_edificio_ito', width: 25 },
      { header: 'Oficina', key: 'oficina_ito', width: 25 },
      { header: 'Entidad', key: 'entidad_ito', width: 25 },
      { header: 'Estado', key: 'estado_ito', width: 15 },
      { header: 'Condición', key: 'condicion_ito', width: 15 },
      { header: 'Fecha Declaración', key: 'fecha_declaracion_ito', width: 20 },
      { header: 'DNI', key: 'dni_ito', width: 15 },
      { header: 'Usuario', key: 'name_ito', width: 20 },
    ];
    worksheet.getRow(1).font = { bold: true, size: 12 };
    worksheet.getRow(1).fill = {
      type: 'pattern',
      pattern: 'solid',
      fgColor: { argb: 'FFE0E0E0' }
    };
    worksheet.getRow(1).alignment = { vertical: 'middle', horizontal: 'center' };
    response.forEach(item => {
      const activo = item.activo || {};
      const primera = item.primera_activodeclaracion || {};
      const ito = item.activodeclaracion_ito || {};
      worksheet.addRow({
        codigo: activo.codigo || '-',
        denominacion: activo.catalogo || '-',
        //catalogo: activo.catalogo || '-',
        ubicacion_primera: primera.ubicacion || '-',
        area_edificio_primera: primera.area_edificio || '-',
        oficina_primera: primera.oficina_denominacion || '-',
        entidad_primera: primera.entidad_denominacion || '-',
        estado_primera: primera.estado || '-',
        condicion_primera: primera.condicion || '-',
        fecha_declaracion_primera: primera.fecha_declaracion || '-',
        dni_primera: primera.dni || '-',
        name_primera: primera.name || '-',
        ubicacion_ito: ito.ubicacion || '-',
        area_edificio_ito: ito.area_edificio || '-',
        oficina_ito: ito.oficina_denominacion || '-',
        entidad_ito: ito.entidad_denominacion || '-',
        estado_ito: ito.estado || '-',
        condicion_ito: ito.condicion || '-',
        fecha_declaracion_ito: ito.fecha_declaracion || '-',
        dni_ito: ito.dni || '-',
        name_ito: ito.name || '-',
      });
    });
    worksheet.eachRow((row) => {
      row.eachCell((cell) => {
        cell.border = {
          top: { style: 'thin' },
          left: { style: 'thin' },
          bottom: { style: 'thin' },
          right: { style: 'thin' }
        };
      });
    });
    const buffer = await workbook.xlsx.writeBuffer();
    const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `reporte_itos_${new Date().toISOString().split('T')[0]}.xlsx`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    $q.notify({
      color: 'positive',
      message: 'Archivo Excel exportado exitosamente',
      icon: 'check'
    });
  } catch (e) {
    console.error(e)
    $q.notify({ color: 'negative', message: 'Error al exportar Excel', icon: 'error' });
  } finally {
    loadingDescargarReporte.value = false
  }
}

const loadingDescargaDeclaracionFila = ref({});

const descargarDeclaracionFila = async (declaracionId) => {
  loadingDescargaDeclaracionFila.value = { ...loadingDescargaDeclaracionFila.value, [declaracionId]: true };
  try {
    const blob = await declaracionService.descargarPDFEntrega(declaracionId);
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `declaracion_uso_${declaracionId}.pdf`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
    $q.notify({ color: 'positive', message: 'Declaración descargada', icon: 'check' });
  } catch (error) {
    console.error(error)
    $q.notify({ color: 'negative', message: 'Error al descargar la declaración', icon: 'error' });
  } finally {
    loadingDescargaDeclaracionFila.value = { ...loadingDescargaDeclaracionFila.value, [declaracionId]: false };
  }
};

onMounted(() => {
  //cargarItos();
  cargarDeclaraciones();
});
</script> 