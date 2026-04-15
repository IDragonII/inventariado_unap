<template>
  <q-card flat bordered class="table-dynamic">
    <q-table
      :rows="props.row"
      :columns="columns"
      :row-key="rowKey"
      :loading="loading"
      :pagination="pagination"
      @request="onRequest"
      :selected="selected"
      @update:selected="onSelectionChange"
      :selection="props.showSelection ? selectionType : 'none'"
      binary-state-sort
      flat
      bordered
      class="modern-table my-sticky-header-last-column-table"
      hide-pagination
    >
      <template v-for="(_, slotName) in $slots" #[slotName]="slotProps">
        <slot :name="slotName" v-bind="slotProps" />
      </template>
      <template #no-data>
        <div class="no-data-container">
          <q-icon name="inbox" size="48px" color="grey-4" />
          <div class="text-grey-6 text-body1 q-mt-sm">No hay datos para mostrar</div>
        </div>
      </template>
      <template #loading>
        <q-inner-loading showing color="primary" />
      </template>
    </q-table>
    
    <div class="pagination-container">
      <div class="pagination-info">
        <span class="text-caption text-grey-6">
          Mostrando {{ props.row.length }} de {{ props.pagination.rowsNumber }} registros
        </span>
      </div>
      <div class="pagination-controls">
        <q-pagination
          v-model="currentPage"
          :max="pagesCount"
          color="primary"
          input
          boundary-numbers
          direction-links
          size="sm"
          class="custom-pagination"
          @update:model-value="onPageChange"
        />
        <q-select
          v-model="currentRowsPerPage"
          :options="[10, 20, 50, 100]"
          dense
          outlined
          emit-value
          map-options
          class="rows-selector"
          :label="'Filas por página'"
          @update:model-value="onRowsPerPageChange"
        />
      </div>
    </div>
  </q-card>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { QTable, QPagination, QSelect, QCard, QIcon, QInnerLoading } from 'quasar'

const props = defineProps({
  columns: { type: Array, required: true },
  row: { type: Array, required: true },
  rowKey: { type: String, default: 'id' },
  loading: { type: Boolean, default: false },
  pagination: {
    type: Object,
    required: true
  },
  filter: { type: String, default: ''},
  selectedRows: { type: Array, default: () => []},
  showSelection: { type: Boolean, default: false },
  selectionType: { type: String, default: 'multiple', validator: (value) => ['single', 'multiple', 'none'].includes(value)}
})

const emit = defineEmits(['update:pagination', 'filter', 'update:selectedRows'])
const currentPage = ref(props.pagination.page)
const currentRowsPerPage = ref(props.pagination.rowsPerPage)
const selected = ref([...props.selectedRows])
const columns = computed(() => props.columns)
const resc = ref(props.pagination.descending)

watch(() => props.selectedRows, (newVal) => {
  selected.value = [...newVal]
}, { deep: true })

watch(() => props.pagination, (newPagination) => {
  currentPage.value = newPagination.page
  currentRowsPerPage.value = newPagination.rowsPerPage
}, { deep: true, immediate: true })

watch(() => props.filter, (newFilter) => {
  emit('filter', newFilter)
})

const onPageChange = (newPage) => {
  emit('update:pagination', {
    ...props.pagination,
    page: newPage,
    rowsPerPage: currentRowsPerPage.value,
  })
}

const onRowsPerPageChange = (newRowsPerPage) => {
  emit('update:pagination', {
    ...props.pagination,
    page: 1,
    rowsPerPage: newRowsPerPage
  })
}

const onRequest = ({pagination: newPagination}) => {
  resc.value=!resc.value
  emit('update:pagination', {
    ...newPagination,
    page: newPagination.page,
    rowsPerPage: newPagination.rowsPerPage,
    sortBy: newPagination.sortBy,
    descending: resc.value
  })
}

const onSelectionChange = (newSelection) => {
  selected.value = newSelection;
  emit('update:selectedRows', newSelection);
};
const pagesCount = computed(() => {
  return Math.ceil(props.pagination.rowsNumber / currentRowsPerPage.value) || 1
})
</script>
<style scoped>
.table-dynamic {
  background: #ffffff;
  border-radius: 4px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  border: 1px solid #e8eaed;
  overflow: hidden;
  transition: all 0.3s ease;
}

.table-dynamic:hover {
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
  transform: translateY(-2px);
}

.modern-table {
  background: transparent;
}

.modern-table .q-table__top,
.modern-table .q-table__bottom {
  display: none;
}

/* Estilos para las filas de la tabla */
.modern-table .q-table tbody tr {
  transition: all 0.2s ease;
  border-bottom: 1px solid #f0f0f0;
}

.modern-table .q-table tbody tr:hover {
  background: linear-gradient(135deg, #f8f9ff 0%, #f0f4ff 100%);
  transform: scale(1.001);
}

.modern-table .q-table tbody tr:nth-child(even) {
  background: #fafbfc;
}

.modern-table .q-table tbody tr:nth-child(even):hover {
  background: linear-gradient(135deg, #f0f4ff 0%, #e8f0ff 100%);
}

/* Estilos para las celdas */
.modern-table .q-table td {
  padding: 16px 12px;
  font-size: 14px;
  color: #2c3e50;
  border: none;
}

.modern-table .q-table th {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  font-weight: 600;
  font-size: 14px;
  padding: 16px 12px;
  border: none;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* Contenedor de paginación */
.pagination-container {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  padding: 20px;
  border-top: 1px solid #e8eaed;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 0px;
}

.pagination-info {
  display: flex;
  align-items: center;
}

.pagination-controls {
  display: flex;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
}

/* Estilos para la paginación */
.custom-pagination {
  background: white;
  border-radius: 4px;
  padding: 2px 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.custom-pagination .q-btn {
  border-radius: 4px;
  margin: 0 2px;
  font-weight: 500;
  transition: all 0.2s ease;
}

.custom-pagination .q-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.custom-pagination .q-btn--active {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  font-weight: 600;
}

/* Selector de filas por página */
.rows-selector {
  min-width: 140px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.rows-selector .q-field__control {
  border-radius: 12px;
  border: 1px solid #e0e0e0;
  transition: all 0.2s ease;
}

.rows-selector .q-field__control:hover {
  border-color: #667eea;
  box-shadow: 0 2px 8px rgba(102, 126, 234, 0.2);
}

.rows-selector .q-field--focused .q-field__control {
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

/* Contenedor de no datos */
.no-data-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  text-align: center;
}

/* Responsive */
@media (max-width: 768px) {
  .pagination-container {
    flex-direction: column;
    align-items: stretch;
    gap: 12px;
  }
  
  .pagination-controls {
    justify-content: center;
  }
  
  .custom-pagination {
    order: 2;
  }
  
  .rows-selector {
    order: 1;
    min-width: 120px;
  }
  
  .table-dynamic {
    border-radius: 12px;
  }
  
  .modern-table .q-table td,
  .modern-table .q-table th {
    padding: 12px 8px;
    font-size: 13px;
  }
}

/* Animaciones */
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.table-dynamic {
  animation: fadeIn 0.3s ease-out;
}

/* Estados de carga */
.modern-table .q-table--loading {
  opacity: 0.7;
}

/* Scroll personalizado para tablas grandes */
.modern-table .q-table__container {
  border-radius: 12px;
  overflow: hidden;
}

.modern-table .q-table__container::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

.modern-table .q-table__container::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 4px;
}

.modern-table .q-table__container::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 4px;
}

.modern-table .q-table__container::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}

body.body--dark .table-dynamic {
  background: #151212;
  border-color: #0f2f0f;
  box-shadow: 0 4px 20px rgba(255, 255, 255, 0.05);
}

body.body--dark .table-dynamic:hover {
  box-shadow: 0 8px 30px rgba(255, 255, 255, 0.08);
}

body.body--dark .modern-table .q-table tbody tr {
  border-bottom: 1px solid #202030;
}

body.body--dark .modern-table .q-table tbody tr:hover {
  background: linear-gradient(135deg, #202030 0%, #000000 100%);
}

body.body--dark .modern-table .q-table tbody tr:nth-child(even) {
  background: #000000;
}

body.body--dark .modern-table .q-table tbody tr:nth-child(even):hover {
  background: linear-gradient(135deg, #000000 0%, #000000 100%);
}

body.body--dark .modern-table .q-table td {
  color: #000000;
}

body.body--dark .modern-table .q-table th {
  background: linear-gradient(135deg, #000000 0%, #000000 100%);
  color: #000000;
}

body.body--dark .pagination-container {
  background: linear-gradient(135deg, #2a2a2a 0%, #1f1f1f 100%);
  border-top-color: #333;
}

body.body--dark .pagination-info .text-caption {
  color: #aaa !important;
}

body.body--dark .custom-pagination {
  background: #000000;
  box-shadow: 0 2px 8px rgba(255, 255, 255, 0.05);
}

body.body--dark .custom-pagination .q-btn {
  color: #000000;
}

body.body--dark .custom-pagination .q-btn--active {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

body.body--dark .rows-selector {
  background: #000000;
}

body.body--dark .rows-selector .q-field__control {
  border-color: #444;
  background-color: #2b2b3b;
  color: #e0e0e0;
}

body.body--dark .rows-selector .q-field__native {
  color: #e0e0e0;
}

body.body--dark .rows-selector .q-field__control:hover {
  border-color: #88aaff;
  box-shadow: 0 2px 8px rgba(136, 170, 255, 0.2);
}

body.body--dark .rows-selector .q-field--focused .q-field__control {
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
}

body.body--dark .q-icon {
  color: #999;
}

body.body--dark .text-grey-6 {
  color: #aaa !important;
}

body.body--dark .text-grey-4 {
  color: #888 !important;
}

body.body--dark .no-data-container {
  color: #bbb;
}
</style>
<style lang="sass">
.my-sticky-header-last-column-table
  max-height: 510px

  td:last-child
    background-color: #eeeeee

  tr th
    position: sticky
    z-index: 2
    background: #eeeeee
  
  body.body--dark &
    td:last-child
      background-color: #333333
    tr th
      background: #333333

  thead tr:last-child th
    top: 48px
    z-index: 3
  thead tr:first-child th
    top: 0
    z-index: 1
  tr:last-child th:last-child
    z-index: 3

  td:last-child
    z-index: 1

  td:last-child, th:last-child
    position: sticky
    right: 0

  tbody
    scroll-margin-top: 48px
</style>