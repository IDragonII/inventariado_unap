




<template>
  <div class="modern-form-container">
    <q-form @submit.prevent="emitSubmit" class="modern-form">
      <div class="form-content">
        <div class="form-fields-container">
          <template v-for="(field, index) in fields" :key="`field-${index}`">
            <!-- Separador -->
            <div v-if="field.type === 'separator'" class="field-separator"><!-- q-my-md -->
              <q-separator :inset="field.inset || false" />
              <div v-if="field.label" class="separator-label q-mt-sm">
                {{ field.label }}
              </div>
            </div>

            <!-- Campo personalizado -->
            <div v-else-if="field.type === 'custom'" :class="'form-field'">
              <slot 
                :name="`custom-${field.name}`" 
                :field="field" 
                :value="formData[field.name]"
              />
            </div>

            <!-- Campos estándar -->
            <div v-else :class="'form-field'">
              <!-- Input de texto, email, password -->
              <q-input
                v-if="['text', 'email', 'password', 'tel', 'url'].includes(field.type)"
                v-model="formData[field.name]"
                :type="field.type"
                :label="field.label"
                :placeholder="field.placeholder"
                :rules="field.rules"
                :readonly="isReadonly(field)"
                :disable="field.disabled"
                :hint="field.hint"
                :error-message="field.errorMessage"
                :filled="field.filled"
                :outlined="field.outlined !== false" 
                :dense="field.dense !== false"
                :clearable="field.clearable !== false"
                :autofocus="field.autofocus"
                :maxlength="field.maxlength"
                :counter="field.counter"
                :loading="field.loading"
                :lazy-rules="field.lazyRules"
                :bottom-slots="field.bottomSlots"
                :required="field.required"
                :style="field.uppercase ? 'text-transform: uppercase;' : ''"
                :class="field.space ? 'q-mb-md' : ''"
                @update:model-value="val => handleInputChange(field, val)"
              >
                <template v-if="field.prepend" #prepend>
                  <q-icon :name="field.prepend" />
                </template>
                <template v-if="field.append" #append>
                  <q-icon :name="field.append" />
                </template>
                <template v-if="field.search">
                  <q-btn icon="search" @click="emitSearch(field.name, formData[field.name])" round flat dense :loading="loadingSearch"/>
                </template>
              </q-input>

              <!-- Input numérico -->
              <q-input
                v-else-if="field.type === 'number'"
                v-model.number="formData[field.name]"
                type="number"
                :label="field.label"
                :placeholder="field.placeholder"
                :rules="field.rules"
                :readonly="isReadonly(field)"
                :disable="field.disabled"
                :hint="field.hint"
                :error-message="field.errorMessage"
                :filled="field.filled"
                :outlined="field.outlined !== false"
                :dense="field.dense !== false"
                :clearable="field.clearable !== false"
                :step="field.step || 1"
                :min="field.min"
                :max="field.max"
                :autofocus="field.autofocus"
                :loading="field.loading"
                :lazy-rules="field.lazyRules"
              >
                <template v-if="field.prepend" #prepend>
                  <q-icon :name="field.prepend" />
                </template>
                <template v-if="field.append" #append>
                  <q-icon :name="field.append" />
                </template>
              </q-input>
              <q-select
                v-else-if="field.type === 'select'"
                v-model="formData[field.name]"
                :options="filteredOptions[field.name] || field.options"
                :label="field.label"
                :placeholder="!formData[field.name] ? field.placeholder : ''"
                :rules="field.rules"
                :readonly="isReadonly(field)"
                :disable="field.disabled"
                :hint="field.hint"
                :error-message="field.errorMessage"
                :filled="field.filled"
                :outlined="field.outlined !== false"
                :dense="field.dense !== false"
                :clearable="field.clearable !== false"
                :multiple="field.multiple"
                :use-chips="field.useChips"
                :use-counter="field.useCounter"
                :loading="field.loading"
                :lazy-rules="field.lazyRules"
                :use-input="field.useInput"
                :fill-input="false"
                :input-debounce="field.inputDebounce"
                @update:model-value="(val) => handleSelectChange(field.name, val)"
                @filter="(val, update) => handleSelectFilter(field, val, update)"
              >
                <template v-if="field.prepend" #prepend>
                  <q-icon :name="field.prepend" />
                </template>
                <template v-if="field.append" #append>
                  <q-icon :name="field.append" />
                </template>
              </q-select>

              <!-- Checkbox -->
              <q-checkbox
                v-else-if="field.type === 'checkbox'"
                v-model="formData[field.name]"
                :label="field.label"
                :disable="field.disabled"
                :readonly="isReadonly(field)"
                :indeterminate-value="field.indeterminateValue"
                :color="field.color || 'primary'"
                :keep-color="field.keepColor"
                :dense="field.dense !== false"
              />

              <!-- Radio buttons -->
              <div v-else-if="field.type === 'radio'" class="radio-group">
                <div class="radio-label q-mb-sm">{{ field.label }}</div>
                <q-option-group
                  v-model="formData[field.name]"
                  :options="field.options"
                  :disable="field.disabled"
                  :readonly="isReadonly(field)"
                  :color="field.color || 'primary'"
                  :inline="field.inline"
                  :dense="field.dense !== false"
                />
              </div>

              <!-- Date picker -->
              <q-input
                v-else-if="field.type === 'date'"
                v-model="formData[field.name]"
                type="date"
                :label="field.label"
                :placeholder="field.placeholder"
                :rules="field.rules"
                :readonly="isReadonly(field)"
                :disable="field.disabled"
                :hint="field.hint"
                :error-message="field.errorMessage"
                :filled="field.filled"
                :outlined="field.outlined !== false"
                :dense="field.dense !== false"
                :clearable="field.clearable !== false"
                :min="field.min"
                :max="field.max"
                :loading="field.loading"
                :lazy-rules="field.lazyRules"
              >
                <template v-if="field.prepend" #prepend>
                  <q-icon :name="field.prepend" />
                </template>
                <template v-if="field.append" #append>
                  <q-icon :name="field.append" />
                </template>
              </q-input>

              <!-- Textarea -->
              <q-input
                v-else-if="field.type === 'textarea'"
                v-model="formData[field.name]"
                type="textarea"
                :label="field.label"
                :placeholder="field.placeholder"
                :rules="field.rules"
                :readonly="isReadonly(field)"
                :disable="field.disabled"
                :hint="field.hint"
                :error-message="field.errorMessage"
                :filled="field.filled"
                :outlined="field.outlined !== false"
                :dense="field.dense !== false"
                :clearable="field.clearable !== false"
                :autofocus="field.autofocus"
                :maxlength="field.maxlength"
                :counter="field.counter"
                :loading="field.loading"
                :lazy-rules="field.lazyRules"
                :rows="field.rows || 3"
                :autogrow="field.autogrow"
                @update:model-value="val => handleInputChange(field, val)"
              />

              <!-- File input -->
              <q-file
                v-else-if="field.type === 'file'"
                v-model="formData[field.name]"
                :label="field.label"
                :placeholder="field.placeholder"
                :rules="field.rules"
                :readonly="isReadonly(field)"
                :disable="field.disabled"
                :hint="field.hint"
                :error-message="field.errorMessage"
                :filled="field.filled"
                :outlined="field.outlined !== false"
                :dense="field.dense !== false"
                :clearable="field.clearable !== false"
                :multiple="field.multiple"
                :accept="field.accept"
                :max-files="field.maxFiles"
                :max-file-size="field.maxFileSize"
                :loading="field.loading"
                :lazy-rules="field.lazyRules"
              >
                <template v-if="field.prepend" #prepend>
                  <q-icon :name="field.prepend" />
                </template>
                <template v-if="field.append" #append>
                  <q-icon :name="field.append" />
                </template>
              </q-file>
            </div>
          </template>
        </div>

        <!-- Acciones del formulario -->
        <div v-if="showActions" class="form-actions q-mt-lg">
          <div class="actions-container">
            <div class="secondary-actions">
              <q-btn
                v-if="mode !== 'view'"
                label="Cancelar"
                icon="close"
                color="grey-6"
                flat
                type="button"
                :disable="loading || disabled"
                @click="emit('cancel')"
              />
              <q-btn
                v-if="mode === 'view' && allowDelete"
                label="Eliminar"
                icon="delete"
                color="negative"
                outline
                :disable="loading || disabled"
                @click="emit('delete')"
              />
            </div>
            <div class="primary-actions">
              <q-btn
                v-if="mode !== 'view'"
                :label="submitLabel"
                :icon="submitIcon"
                :color="submitColor"
                :loading="loading"
                :disable="loading || disabled"
                type="submit"
                unelevated
                class="submit-btn"
              />
            </div>
          </div>
        </div>
      </div>
    </q-form>
  </div>
</template>

<script setup>
import { ref, watch, defineEmits, defineProps } from 'vue'

const props = defineProps({
  fields: { type: Array, required: true },
  modelValue: { type: Object, default: () => ({}) },
  readonly: { type: Boolean, default: false },
  mode: { type: String, default: 'create' },
  loading: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  showActions: { type: Boolean, default: true },
  allowDelete: { type: Boolean, default: false },
  submitLabel: { type: String, default: 'Guardar' },
  submitIcon: { type: String, default: 'save' },
  submitColor: { type: String, default: 'primary' }
})

const emit = defineEmits(['submit', 'update:modelValue', 'cancel', 'delete', 'select-change', 'search-click'])

const formData = ref({ ...props.modelValue })
const filteredOptions = ref({})
const loadingSearch=ref(false)
const handleSelectChange = (fieldName, value) => {
  formData.value[fieldName] = value;
  emit('select-change', fieldName, value);
  emit('update:modelValue', { ...formData.value });
}

const handleInputChange = (field, value) => {
  if (field.uppercase && typeof value === 'string') {
    formData.value[field.name] = value.toUpperCase();
  } else {
    formData.value[field.name] = value;
  }
  emit('update:modelValue', { ...formData.value });
};

function handleSelectFilter(field, val, update) {
  if (!val) {
    filteredOptions.value[field.name] = field.options
    update(() => field.options)
    return
  }
  const searchTerm = val.toLowerCase();
  update(() => {
    // Filtra las opciones que coincidan con el texto (ignorando mayúsculas)
    filteredOptions.value[field.name] = field.options.filter(opt => {
      const label = typeof opt === 'string' ? opt : (opt.label || opt.value || '');
      return label.toString().toLowerCase().includes(searchTerm);
    });
  });
}

watch(() => props.modelValue, (val) => {
  formData.value = { ...val }
}, { deep: true })

watch(formData, () => {
  props.fields.forEach(field => {
    if (field.uppercase && typeof formData.value[field.name] === 'string') {
      formData.value[field.name] = formData.value[field.name].toUpperCase();
    }
  })
}, { deep: true })

const isReadonly = (field) => props.readonly || field.readonly || props.mode === 'view'

const emitSearch=(field, currentValue)=>{
  emit('search-click', {field, currentValue})
}

const emitSubmit = () => {
  const data = { ...formData.value }
  props.fields.forEach(field => {
    if (field.uppercase && typeof data[field.name] === 'string') {
      data[field.name] = data[field.name].toUpperCase()
    }
  })
  emit('submit', data)
}
</script>
<style lang="scss" scoped>
.modern-form-container {
  background: transparent;
  border-radius: 8px;
  box-shadow: none;
  border: 1px solid #e0e0e0;
  overflow: hidden;
  transition: none;

  &:hover {
    box-shadow: none;
  }
}

.modern-form {
  padding: 16px;
}

.form-content {
  .form-fields-container {
    display: flex;
    flex-wrap: wrap;
    gap: 0px 12px;
    margin-bottom: 24px;
  }

  .form-field {
    flex: 1;
    min-width: 250px;
  }
}

.field-separator {
  width: 100%;
  
  .separator-label {
    font-size: 0.8rem;
    font-weight: 500;
    color: #666;
    text-transform: uppercase;
    text-align: left;
  }
}

.radio-group {
  .radio-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: #333;
  }
}

.form-actions {
  border-top: 1px solid #e0e0e0;
  padding-top: 8px;

  .actions-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
  }

  .secondary-actions {
    display: flex;
    gap: 12px;
  }

  .primary-actions {
    display: flex;
    gap: 12px;
  }

  .submit-btn {
    min-width: 120px;
    height: 40px;
    font-weight: 500;
    border-radius: 8px;
    transition: all 0.3s ease;

    &:hover {
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
  }
}

// Responsive design
@media (max-width: 768px) {
  .modern-form-container {
    border-radius: 0;
    border: none;
    margin: 0;
  }

  .modern-form {
    padding: 8px;
  }

  .form-content {
    .form-fields-container {
      gap: 0px;
    }

    .form-field {
      min-width: 100%;
    }
  }

  .form-actions {
    .actions-container {
      flex-direction: column-reverse;
      align-items: stretch;
    }

    .secondary-actions,
    .primary-actions {
      justify-content: center;
    }

    .submit-btn {
      width: 100%;
    }
  }
}

.body--dark .modern-form-container {
  background: transparent;
  border-color: #444;
}

.body--dark .form-actions {
  border-top-color: #2d3748;
}

.body--dark .field-separator .separator-label,
.body--dark .radio-group .radio-label {
  color: #ccc;
}
</style>