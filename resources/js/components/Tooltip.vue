<template>
    <div class="relative inline-block">
        <div @mouseenter="showTooltip = true" @mouseleave="showTooltip = false">
            <slot></slot>
        </div>
        
        <transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="transform opacity-0 scale-95"
            enter-to-class="transform opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="transform opacity-100 scale-100"
            leave-to-class="transform opacity-0 scale-95"
        >
            <div v-show="showTooltip" class="absolute z-50 mt-2 px-3 py-2 text-sm rounded-md shadow-lg bg-gray-900 text-white dark:bg-gray-700 whitespace-nowrap"
                :class="[
                    position === 'top' ? 'bottom-full mb-2' : '',
                    position === 'bottom' ? 'top-full mt-2' : '',
                    position === 'left' ? 'right-full mr-2' : '',
                    position === 'right' ? 'left-full ml-2' : '',
                ]">
                {{ content }}
            </div>
        </transition>
    </div>
</template>

<script setup>
import { ref } from 'vue';

// تعريف الخصائص
const props = defineProps({
    content: {
        type: String,
        required: true
    },
    position: {
        type: String,
        default: 'top',
        validator: (value) => ['top', 'bottom', 'left', 'right'].includes(value)
    }
});

// الحالة
const showTooltip = ref(false);
</script> 