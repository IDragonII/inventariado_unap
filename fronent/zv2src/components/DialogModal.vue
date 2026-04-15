<template>
    <q-dialog v-model="showModal" persistent>
        <q-card class="modern-dialog">
            <q-card-section class="dialog-header row items-center q-pb-none">
                <div class="text-h6">{{ title }}</div>
                <q-space />
                <q-btn v-if="mode==='view'" icon="close" flat round dense @click="closeModal" />
            </q-card-section>
            <q-card-section class="dialog-content">
                <slot />
            </q-card-section>
        </q-card>
    </q-dialog>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    show: {
        type: Boolean,
        required: true
    },
    mode: {
        type: String,
        default: 'create' // 'create', 'edit', 'view'
    },
    title: {
        type: String,
        default: 'Modal'
    }
})

const emit = defineEmits(['update:show', 'close'])

const showModal = computed({
    get: () => props.show,
    set: (value) => emit('update:show', value)
})

const closeModal = () => {
    showModal.value = false
    emit('close')
}
</script>

<style lang="scss" scoped>
.modern-dialog {
    min-width: 350px;
    max-width: 900px;
    width: 100%;
    height: auto;
    display: flex;
    flex-direction: column;
    border-radius: 4px;
    overflow: hidden;
}

.modern-dialog-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 16px 24px;
    min-height: 64px;

    .header-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
        width: 100%;
    }

    .title-section {
        display: flex;
        align-items: center;
        flex: 1;
        min-width: 0;
    }

    .dialog-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: white;
        margin: 0;
        line-height: 1.2;
    }

    .close-btn {
        color: white;
        border-radius: 50%;
        transition: all 0.3s ease;

        &:hover {
            background: rgba(255, 255, 255, 0.1);
        }
    }
}

.dialog-content {
    padding-top: 0;
    flex: 1;
    overflow-y: auto;
    max-height: calc(90vh - 64px - 80px); // Altura total - header - actions
}

// Responsive design
@media (max-width: 768px) {
    .modern-dialog {
        max-width: 100vw;
        max-height: 100vh;
        border-radius: 0;
    }

    .modern-dialog-header {
        padding: 12px 16px;

        .header-content {
            flex-direction: column;
            align-items: flex-start;
            gap: 12px;
        }

        .title-section {
            width: 100%;
        }
    }

    .dialog-content {
        padding: 12px 16px;
    }
}

// Dark mode support
.body--dark {
    .modern-dialog-header {
        background: linear-gradient(135deg, #4a5568 0%, #2d3748 100%);
    }
}
</style>