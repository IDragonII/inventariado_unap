<template>
  <q-btn 
    v-if="props.black" 
    outline 
    round 
    size="11px" 
    :color="$q.dark.isActive ? 'grey-6' : 'grey-5'" 
    @click="toggleDarkMode"
  >
    <q-icon 
      :name="isDarkMode ? 'las la-moon-stars' : 'las la-sun'"
      :class="darkModeClass"
    />
  </q-btn>
  <q-btn 
    v-else 
    outline 
    round 
    size="11px" 
    :color="$q.dark.isActive ? 'grey-4' : 'grey-4'" 
    @click="toggleDarkMode"
  >
    <q-icon 
      :name="isDarkMode ? 'las la-moon-stars' : 'las la-sun'"
      :class="darkModeClass"
    />
  </q-btn>
</template>

<script setup>
import { useQuasar } from "quasar";
import { ref, onMounted, computed, onBeforeUnmount } from "vue";

const $q = useQuasar()

// Props
const props = defineProps({
  black: { 
    type: Boolean, 
    default: false 
  }
});

// Estado reactivo
const isDarkMode = ref(false);
const mediaQuery = ref(null);

// Computed
const darkModeClass = computed(() => ({
  'text-white': $q.dark.isActive,
  'text-black': !$q.dark.isActive
}));

// Métodos
const toggleDarkMode = () => {
  try {
    isDarkMode.value = !isDarkMode.value;
    $q.dark.set(isDarkMode.value);
    localStorage.setItem("darkMode", isDarkMode.value.toString());
  } catch (error) {
    console.error('Error al cambiar el modo oscuro:', error);
  }
};

const handleSystemThemeChange = (e) => {
  if (localStorage.getItem("darkMode") === null) {
    isDarkMode.value = e.matches;
    $q.dark.set(isDarkMode.value);
  }
};

// Inicialización
onMounted(() => {
  try {
    const savedDarkMode = localStorage.getItem("darkMode");
    if (savedDarkMode !== null) {
      isDarkMode.value = savedDarkMode === "true";
      $q.dark.set(isDarkMode.value);
    } else {
      // Si no hay preferencia guardada, usar la preferencia del sistema
      mediaQuery.value = window.matchMedia('(prefers-color-scheme: dark)');
      isDarkMode.value = mediaQuery.value.matches;
      $q.dark.set(isDarkMode.value);
      localStorage.setItem("darkMode", isDarkMode.value.toString());
      
      // Escuchar cambios en la preferencia del sistema
      mediaQuery.value.addEventListener('change', handleSystemThemeChange);
    }
  } catch (error) {
    console.error('Error al inicializar el modo oscuro:', error);
  }
});

// Limpieza
onBeforeUnmount(() => {
  if (mediaQuery.value) {
    mediaQuery.value.removeEventListener('change', handleSystemThemeChange);
  }
});
</script>