import { defineStore } from 'pinia';

export const useExportStore = defineStore('export', {
  state: () => ({
    exports: [], // { id, mensaje, estado, timestamp }
  }),
  getters: {
    pendientes: (state) => state.exports.filter(e => e.estado === 'procesando' || e.estado === 'completado').length,
  },
  actions: {
    agregarExport(data) {
      this.exports.unshift({
        id: Date.now(),
        timestamp: new Date(),
        estado: 'procesando',
        ...data
      });
    },
    actualizarExport(id, payload) {
      const index = this.exports.findIndex(e => e.id === id);
      if (index !== -1) {
        this.exports[index] = { ...this.exports[index], ...payload };
      }
    },
    eliminarExport(id) {
      this.exports = this.exports.filter(e => e.id !== id);
    }
  }
});