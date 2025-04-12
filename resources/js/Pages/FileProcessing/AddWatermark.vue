<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed, onMounted } from 'vue';
import SubscriptionWarning from '@/components/SubscriptionWarning.vue';
import PdfProcessingService from '@/services/PdfProcessingService.js';

const props = defineProps({
    activeSubscription: {
        type: Boolean,
        default: false,
    },
    subscriptionStatus: {
        type: String,
        default: '',
    },
    subscriptionEndsAt: {
        type: String,
        default: '',
    },
    auth: Object,
});

const file = ref(null);
const isProcessing = ref(false);
const processedFileUrl = ref(null);
const errorMessage = ref('');
const dragActive = ref(false);
const fileName = ref('');

// Watermark options
const watermarkType = ref('text'); // text or image
const watermarkText = ref('نص العلامة المائية');
const opacity = ref(30);
const position = ref('center');
const rotation = ref(45);
const watermarkImage = ref(null);
const watermarkImageName = ref('');
const fontSize = ref(24);
const color = ref('#000000');

// Position options
const positionOptions = [
    { value: 'center', label: 'وسط' },
    { value: 'top-left', label: 'أعلى اليسار' },
    { value: 'top-right', label: 'أعلى اليمين' },
    { value: 'bottom-left', label: 'أسفل اليسار' },
    { value: 'bottom-right', label: 'أسفل اليمين' },
];

// Use the activeSubscription prop directly
const hasActiveSubscription = computed(() => props.activeSubscription);

// Handle file selection
function handleFileSelected(e) {
    const selectedFile = e.target.files[0];
    if (!selectedFile) return;
    
    // Reset states
    errorMessage.value = '';
    processedFileUrl.value = '';
    
    if (selectedFile.type !== 'application/pdf') {
        errorMessage.value = 'يرجى اختيار ملف PDF صالح';
        return;
    }
    
    file.value = selectedFile;
    fileName.value = selectedFile.name;
}

// Handle watermark image selection
function handleWatermarkImageSelected(e) {
    const selectedFile = e.target.files[0];
    if (!selectedFile) return;
    
    const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!validTypes.includes(selectedFile.type)) {
        errorMessage.value = 'يرجى اختيار صورة بتنسيق JPEG أو PNG أو GIF';
        return;
    }
    
    watermarkImage.value = selectedFile;
    watermarkImageName.value = selectedFile.name;
}

// Reset everything
function resetForm() {
    file.value = null;
    fileName.value = '';
    processedFileUrl.value = '';
    errorMessage.value = '';
    // Reset watermark options to defaults
    watermarkType.value = 'text';
    watermarkText.value = 'نص العلامة المائية';
    opacity.value = 30;
    position.value = 'center';
    rotation.value = 45;
    watermarkImage.value = null;
    watermarkImageName.value = '';
    fontSize.value = 24;
    color.value = '#000000';
}

// Process the PDF to add watermark
async function processPdf() {
    if (!hasActiveSubscription.value) {
        errorMessage.value = 'هذه الخدمة متاحة فقط للمستخدمين المشتركين. يرجى الاشتراك للاستفادة من هذه الميزة.';
        return;
    }
    
    if (!file.value) {
        errorMessage.value = 'يرجى إضافة ملف PDF أولاً.';
        return;
    }
    
    if (watermarkType.value === 'text' && !watermarkText.value) {
        errorMessage.value = 'يرجى إدخال نص العلامة المائية.';
        return;
    }
    
    if (watermarkType.value === 'image' && !watermarkImage.value) {
        errorMessage.value = 'يرجى اختيار صورة للعلامة المائية.';
        return;
    }
    
    try {
        isProcessing.value = true;
        errorMessage.value = '';
        
        const options = {
            type: watermarkType.value,
            position: position.value,
            opacity: parseInt(opacity.value),
            rotation: parseInt(rotation.value)
        };
        
        if (watermarkType.value === 'text') {
            options.text = watermarkText.value;
            options.fontSize = parseInt(fontSize.value);
            options.color = color.value;
        } else {
            options.image = watermarkImage.value;
        }
        
        const result = await PdfProcessingService.processPdf(file.value, 'add_watermark', options);
        
        if (result.success) {
            processedFileUrl.value = result.file;
        } else {
            errorMessage.value = result.message || 'حدث خطأ أثناء معالجة الملف. يرجى المحاولة مرة أخرى.';
        }
    } catch (error) {
        console.error('Error processing PDF:', error);
        errorMessage.value = 'حدث خطأ أثناء معالجة الملف. يرجى المحاولة مرة أخرى.';
    } finally {
        isProcessing.value = false;
    }
}

// Handle drag events for PDF upload
function handleDragEnter(e) {
    e.preventDefault();
    e.stopPropagation();
    dragActive.value = true;
}

function handleDragLeave(e) {
    e.preventDefault();
    e.stopPropagation();
    dragActive.value = false;
}

function handleDragOver(e) {
    e.preventDefault();
    e.stopPropagation();
    dragActive.value = true;
}

function handleDrop(e) {
    e.preventDefault();
    e.stopPropagation();
    dragActive.value = false;
    
    if (e.dataTransfer.files.length) {
        const files = e.dataTransfer.files;
        if (files[0].type === 'application/pdf') {
            handleFileSelected({ target: { files } });
        } else {
            errorMessage.value = 'يرجى اختيار ملف PDF صالح';
        }
    }
}

// API status check
const apiStatus = ref({
    ready: false,
    message: '',
    checked: false
});

// Check API status on mount
onMounted(async () => {
    try {
        // Check PDF processing API status
        const status = await PdfProcessingService.checkApiStatus();
        apiStatus.value = {
            ready: status.success || false,
            message: status.message || '',
            checked: true
        };
    } catch (error) {
        console.error('Error checking API status:', error);
        apiStatus.value = {
            ready: false,
            message: 'فشل الاتصال بخدمة معالجة ملفات PDF',
            checked: true
        };
    }
});
</script>

<template>
    <Head title="إضافة علامة مائية" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">إضافة علامة مائية</h2>
                <Link
                    :href="route('file-processing')"
                    class="px-4 py-2 bg-gray-200 rounded-md text-gray-700 hover:bg-gray-300 transition-colors"
                >
                    العودة إلى معالجة الملفات
                </Link>
            </div>
        </template>

        <div class="py-12 bg-gray-50">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Subscription Warning -->
                <div v-if="!hasActiveSubscription" class="mb-6">
                    <SubscriptionWarning 
                        message="للوصول إلى جميع ميزات معالجة الملفات، يرجى ترقية اشتراكك."
                        :subscription-status="subscriptionStatus"
                        :subscription-ends-at="subscriptionEndsAt"
                    />
                </div>
                
                <!-- API Status Warning -->
                <div v-if="apiStatus.checked && !apiStatus.ready" class="mb-6 bg-yellow-50 border-l-4 border-yellow-400 p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="mr-3">
                            <p class="text-sm text-yellow-700">
                                خدمة تحويل الملفات غير متاحة حالياً. يرجى التحقق من إعدادات API.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Error Message -->
                <div v-if="errorMessage" class="mb-6 bg-red-50 border-l-4 border-red-400 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="mr-3">
                            <p class="text-sm text-red-700">{{ errorMessage }}</p>
                        </div>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <!-- Success State -->
                    <div v-if="processedFileUrl" class="text-center">
                        <div class="mb-6">
                            <div class="mx-auto w-16 h-16 bg-green-100 rounded-full flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <h3 class="mt-4 text-xl font-medium text-gray-900">تم إضافة العلامة المائية بنجاح!</h3>
                            <p class="mt-1 text-gray-500">الملف جاهز للتنزيل الآن.</p>
                        </div>
                        
                        <div class="flex flex-col sm:flex-row justify-center space-y-3 sm:space-y-0 sm:space-x-4 sm:space-x-reverse">
                            <a 
                                :href="processedFileUrl" 
                                class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
                                target="_blank"
                                download
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                تنزيل الملف المعالج
                            </a>
                            <button 
                                @click="resetForm" 
                                class="inline-flex items-center justify-center px-5 py-3 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                بدء عملية جديدة
                            </button>
                        </div>
                    </div>

                    <!-- Upload & Settings State -->
                    <div v-else>
                        <div class="text-center mb-8">
                            <h3 class="text-lg font-medium text-gray-900 mb-2">إضافة علامة مائية لملف PDF</h3>
                            <p class="text-gray-600">قم بتحميل ملف PDF وتخصيص إعدادات العلامة المائية.</p>
                        </div>

                        <!-- File Drop Area (if no file selected) -->
                        <div v-if="!file"
                            class="mb-6 border-2 border-dashed rounded-lg p-10 text-center"
                            :class="{ 'border-blue-400 bg-blue-50': dragActive, 'border-gray-300': !dragActive }"
                            @dragenter="handleDragEnter"
                            @dragleave="handleDragLeave"
                            @dragover="handleDragOver"
                            @drop="handleDrop"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p class="mt-1 text-sm text-gray-600">
                                اسحب وأفلت ملف PDF هنا، أو
                                <label class="relative cursor-pointer text-blue-600 hover:text-blue-800">
                                    <span>انقر للاختيار</span>
                                    <input type="file" class="sr-only" accept="application/pdf" @change="handleFileSelected" />
                                </label>
                            </p>
                            <p class="mt-1 text-xs text-gray-500">يمكنك تحميل ملف PDF فقط</p>
                        </div>

                        <!-- File Selected & Settings UI -->
                        <div v-if="file" class="mt-6">
                            <div class="flex justify-between items-center bg-gray-50 p-4 rounded-md mb-6">
                                <div class="flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-500 ml-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    </svg>
                                    <div>
                                        <p class="font-medium text-gray-900">{{ fileName }}</p>
                                    </div>
                                </div>
                                <button 
                                    @click="resetForm" 
                                    class="text-red-500 hover:text-red-700"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <h4 class="font-medium text-gray-900 mb-4">إعدادات العلامة المائية:</h4>
                            
                            <!-- Watermark Type Selection -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">نوع العلامة المائية</label>
                                <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-4 sm:space-x-reverse">
                                    <div class="flex items-center">
                                        <input 
                                            id="text-watermark" 
                                            type="radio" 
                                            v-model="watermarkType" 
                                            value="text" 
                                            class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300"
                                        />
                                        <label for="text-watermark" class="mr-2 block text-sm font-medium text-gray-700">نص</label>
                                    </div>
                                    <div class="flex items-center">
                                        <input 
                                            id="image-watermark" 
                                            type="radio" 
                                            v-model="watermarkType" 
                                            value="image" 
                                            class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300"
                                        />
                                        <label for="image-watermark" class="mr-2 block text-sm font-medium text-gray-700">صورة</label>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Text Watermark Settings -->
                            <div v-if="watermarkType === 'text'" class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div class="space-y-4">
                                    <div>
                                        <label for="watermarkText" class="block text-sm font-medium text-gray-700 mb-1">نص العلامة المائية</label>
                                        <input 
                                            type="text" 
                                            id="watermarkText" 
                                            v-model="watermarkText" 
                                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                        />
                                    </div>
                                    
                                    <div>
                                        <label for="fontSize" class="block text-sm font-medium text-gray-700 mb-1">حجم الخط</label>
                                        <input 
                                            type="number" 
                                            id="fontSize" 
                                            v-model="fontSize" 
                                            min="8" 
                                            max="72" 
                                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                        />
                                    </div>
                                    
                                    <div>
                                        <label for="color" class="block text-sm font-medium text-gray-700 mb-1">لون النص</label>
                                        <input 
                                            type="color" 
                                            id="color" 
                                            v-model="color" 
                                            class="mt-1 block w-full h-10 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                        />
                                    </div>
                                </div>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label for="position" class="block text-sm font-medium text-gray-700 mb-1">موضع العلامة المائية</label>
                                        <select 
                                            id="position" 
                                            v-model="position" 
                                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                        >
                                            <option v-for="option in positionOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label for="opacity" class="block text-sm font-medium text-gray-700 mb-1">شفافية ({{ opacity }}%)</label>
                                        <input 
                                            type="range" 
                                            id="opacity" 
                                            v-model="opacity" 
                                            min="10" 
                                            max="100" 
                                            class="mt-1 block w-full"
                                        />
                                    </div>
                                    
                                    <div>
                                        <label for="rotation" class="block text-sm font-medium text-gray-700 mb-1">زاوية الدوران ({{ rotation }}°)</label>
                                        <input 
                                            type="range" 
                                            id="rotation" 
                                            v-model="rotation" 
                                            min="-180" 
                                            max="180" 
                                            class="mt-1 block w-full"
                                        />
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Image Watermark Settings -->
                            <div v-if="watermarkType === 'image'" class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">صورة العلامة المائية</label>
                                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                                        <div class="space-y-1 text-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            <div class="flex text-sm text-gray-600">
                                                <label class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none">
                                                    <span>تحميل صورة</span>
                                                    <input 
                                                        type="file" 
                                                        class="sr-only" 
                                                        @change="handleWatermarkImageSelected" 
                                                        accept="image/jpeg,image/png,image/gif"
                                                    />
                                                </label>
                                                <p class="pr-1">أو اسحب وأفلت</p>
                                            </div>
                                            <p class="text-xs text-gray-500">PNG, JPG, GIF حتى 5MB</p>
                                            <p v-if="watermarkImageName" class="text-sm text-gray-700 mt-2">{{ watermarkImageName }}</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label for="position" class="block text-sm font-medium text-gray-700 mb-1">موضع العلامة المائية</label>
                                        <select 
                                            id="position" 
                                            v-model="position" 
                                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                        >
                                            <option v-for="option in positionOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label for="opacity" class="block text-sm font-medium text-gray-700 mb-1">شفافية ({{ opacity }}%)</label>
                                        <input 
                                            type="range" 
                                            id="opacity" 
                                            v-model="opacity" 
                                            min="10" 
                                            max="100" 
                                            class="mt-1 block w-full"
                                        />
                                    </div>
                                    
                                    <div>
                                        <label for="rotation" class="block text-sm font-medium text-gray-700 mb-1">زاوية الدوران ({{ rotation }}°)</label>
                                        <input 
                                            type="range" 
                                            id="rotation" 
                                            v-model="rotation" 
                                            min="-180" 
                                            max="180" 
                                            class="mt-1 block w-full"
                                        />
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Preview (simplified) -->
                            <div class="mb-6 p-4 border rounded-md bg-gray-50">
                                <h5 class="font-medium text-gray-900 mb-2">معاينة:</h5>
                                <div class="relative w-full h-[200px] bg-white border border-gray-200 rounded-md flex items-center justify-center overflow-hidden">
                                    <!-- Document placeholder -->
                                    <div class="w-32 h-44 bg-white border border-gray-300 shadow-sm flex items-center justify-center text-gray-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    
                                    <!-- Text watermark preview -->
                                    <div v-if="watermarkType === 'text'" 
                                        class="absolute inset-0 flex items-center justify-center pointer-events-none"
                                        :style="{ 
                                            opacity: opacity / 100, 
                                            transform: `rotate(${rotation}deg)`,
                                            color: color
                                        }"
                                    >
                                        <span :style="{ fontSize: `${fontSize}px` }">{{ watermarkText }}</span>
                                    </div>
                                    
                                    <!-- Image watermark placeholder -->
                                    <div v-if="watermarkType === 'image' && watermarkImage" 
                                        class="absolute inset-0 flex items-center justify-center pointer-events-none"
                                        :style="{ 
                                            opacity: opacity / 100, 
                                            transform: `rotate(${rotation}deg)` 
                                        }"
                                    >
                                        <div class="w-16 h-16 bg-gray-300 rounded flex items-center justify-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex justify-between mt-6">
                                <button 
                                    @click="resetForm" 
                                    class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                >
                                    إلغاء
                                </button>
                                
                                <button 
                                    @click="processPdf" 
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
                                    :disabled="isProcessing || !apiStatus.ready || !hasActiveSubscription"
                                >
                                    <span v-if="isProcessing" class="flex items-center">
                                        <svg class="animate-spin h-5 w-5 ml-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        جاري المعالجة...
                                    </span>
                                    <span v-else>إضافة العلامة المائية</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Instructions -->
                <div class="mt-8 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">كيفية إضافة علامة مائية لملف PDF</h3>
                    <div class="grid md:grid-cols-3 gap-6">
                        <div class="flex flex-col items-center text-center">
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                                <span class="text-blue-600 font-bold">1</span>
                            </div>
                            <h4 class="font-medium mb-2">اختر الملف</h4>
                            <p class="text-gray-600 text-sm">قم بتحميل ملف PDF الذي ترغب في إضافة علامة مائية له.</p>
                        </div>
                        <div class="flex flex-col items-center text-center">
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                                <span class="text-blue-600 font-bold">2</span>
                            </div>
                            <h4 class="font-medium mb-2">تخصيص العلامة المائية</h4>
                            <p class="text-gray-600 text-sm">اختر بين علامة مائية نصية أو صورة، وقم بضبط الموضع والشفافية وزاوية الدوران.</p>
                        </div>
                        <div class="flex flex-col items-center text-center">
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                                <span class="text-blue-600 font-bold">3</span>
                            </div>
                            <h4 class="font-medium mb-2">تنزيل الملف المعالج</h4>
                            <p class="text-gray-600 text-sm">انتظر انتهاء العملية وقم بتنزيل ملف PDF مع العلامة المائية.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template> 