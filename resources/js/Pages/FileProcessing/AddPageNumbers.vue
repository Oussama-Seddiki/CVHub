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
const isUploading = ref(false);
const isProcessing = ref(false);
const processedFileUrl = ref(null);
const errorMessage = ref('');
const dragActive = ref(false);
const fileName = ref('');
const filePreview = ref(null);

// Page numbering options
const position = ref('bottom-center');
const startNumber = ref(1);
const fontSize = ref(12);
const color = ref('#000000');
const font = ref('Arial');
const margin = ref(10);
const prefix = ref('');
const suffix = ref('');

// Available options
const positionOptions = [
    { value: 'top-left', label: 'أعلى اليسار' },
    { value: 'top-center', label: 'أعلى الوسط' },
    { value: 'top-right', label: 'أعلى اليمين' },
    { value: 'bottom-left', label: 'أسفل اليسار' },
    { value: 'bottom-center', label: 'أسفل الوسط' },
    { value: 'bottom-right', label: 'أسفل اليمين' },
];

const fontOptions = [
    { value: 'Arial', label: 'Arial' },
    { value: 'Times New Roman', label: 'Times New Roman' },
    { value: 'Courier', label: 'Courier' },
    { value: 'Helvetica', label: 'Helvetica' },
    { value: 'Tahoma', label: 'Tahoma' },
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
    
    // Generate preview
    const reader = new FileReader();
    reader.onload = (e) => {
        filePreview.value = e.target.result;
    };
    reader.readAsDataURL(selectedFile);
}

// Reset everything
function resetForm() {
    file.value = null;
    fileName.value = '';
    filePreview.value = null;
    errorMessage.value = '';
    processedFileUrl.value = '';
    // Reset options to defaults
    position.value = 'bottom-center';
    startNumber.value = 1;
    fontSize.value = 12;
    color.value = '#000000';
    font.value = 'Arial';
    margin.value = 10;
    prefix.value = '';
    suffix.value = '';
}

// Process the PDF to add page numbers
async function processPdf() {
    if (!hasActiveSubscription.value) {
        errorMessage.value = 'هذه الخدمة متاحة فقط للمستخدمين المشتركين. يرجى الاشتراك للاستفادة من هذه الميزة.';
        return;
    }
    
    if (!file.value) {
        errorMessage.value = 'يرجى إضافة ملف PDF أولاً.';
        return;
    }
    
    try {
        isProcessing.value = true;
        errorMessage.value = '';
        
        // Parse position to get horizontal and vertical alignment
        let [vertical, horizontal] = position.value.split('-');
        
        const options = {
            startNumber: parseInt(startNumber.value),
            fontSize: parseInt(fontSize.value),
            color: color.value,
            fontFamily: font.value,
            margin: parseInt(margin.value),
            prefix: prefix.value,
            suffix: suffix.value,
            vertical: vertical, // 'top' or 'bottom'
            horizontal: horizontal // 'left', 'center', or 'right'
        };
        
        const result = await PdfProcessingService.processPdf(file.value, 'add_page_numbers', options);
        
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

// Handle drag events
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
    <Head title="إضافة أرقام الصفحات" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">إضافة أرقام الصفحات</h2>
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
                            <h3 class="mt-4 text-xl font-medium text-gray-900">تم إضافة أرقام الصفحات بنجاح!</h3>
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
                            <h3 class="text-lg font-medium text-gray-900 mb-2">إضافة أرقام الصفحات</h3>
                            <p class="text-gray-600">قم بتحميل ملف PDF وتخصيص إعدادات أرقام الصفحات.</p>
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

                            <h4 class="font-medium text-gray-900 mb-4">إعدادات ترقيم الصفحات:</h4>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <!-- Position Settings -->
                                <div class="space-y-4">
                                    <div>
                                        <label for="position" class="block text-sm font-medium text-gray-700 mb-1">موضع أرقام الصفحات</label>
                                        <select 
                                            id="position" 
                                            v-model="position" 
                                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                        >
                                            <option v-for="option in positionOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label for="startNumber" class="block text-sm font-medium text-gray-700 mb-1">رقم البداية</label>
                                        <input 
                                            type="number" 
                                            id="startNumber" 
                                            v-model="startNumber" 
                                            min="1" 
                                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                        />
                                    </div>
                                    
                                    <div>
                                        <label for="margin" class="block text-sm font-medium text-gray-700 mb-1">الهامش (بالبكسل)</label>
                                        <input 
                                            type="number" 
                                            id="margin" 
                                            v-model="margin" 
                                            min="0" 
                                            max="100" 
                                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                        />
                                    </div>
                                </div>
                                
                                <!-- Appearance Settings -->
                                <div class="space-y-4">
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
                                        <label for="font" class="block text-sm font-medium text-gray-700 mb-1">نوع الخط</label>
                                        <select 
                                            id="font" 
                                            v-model="font" 
                                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                        >
                                            <option v-for="option in fontOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                                        </select>
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
                            </div>
                            
                            <!-- Prefix & Suffix -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <label for="prefix" class="block text-sm font-medium text-gray-700 mb-1">بادئة (قبل الرقم)</label>
                                    <input 
                                        type="text" 
                                        id="prefix" 
                                        v-model="prefix" 
                                        placeholder="مثال: صفحة" 
                                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                    />
                                </div>
                                
                                <div>
                                    <label for="suffix" class="block text-sm font-medium text-gray-700 mb-1">لاحقة (بعد الرقم)</label>
                                    <input 
                                        type="text" 
                                        id="suffix" 
                                        v-model="suffix" 
                                        placeholder="مثال: من 100" 
                                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                    />
                                </div>
                            </div>
                            
                            <!-- Preview -->
                            <div class="mb-6 p-4 border rounded-md bg-gray-50">
                                <h5 class="font-medium text-gray-900 mb-2">معاينة:</h5>
                                <div class="relative w-full h-[200px] bg-white border border-gray-200 rounded-md p-4 flex items-center justify-center">
                                    <!-- Position indicator based on selected position -->
                                    <div class="absolute inset-0 flex items-stretch justify-stretch pointer-events-none">
                                        <div class="w-full h-full grid grid-cols-3 grid-rows-3">
                                            <div class="flex items-start justify-start p-3" :class="{ 'bg-blue-50': position === 'top-left' }">
                                                <span v-if="position === 'top-left'" :style="{ color: color, fontSize: `${fontSize}px`, fontFamily: font }">
                                                    {{ prefix }}{{ startNumber }}{{ suffix }}
                                                </span>
                                            </div>
                                            <div class="flex items-start justify-center p-3" :class="{ 'bg-blue-50': position === 'top-center' }">
                                                <span v-if="position === 'top-center'" :style="{ color: color, fontSize: `${fontSize}px`, fontFamily: font }">
                                                    {{ prefix }}{{ startNumber }}{{ suffix }}
                                                </span>
                                            </div>
                                            <div class="flex items-start justify-end p-3" :class="{ 'bg-blue-50': position === 'top-right' }">
                                                <span v-if="position === 'top-right'" :style="{ color: color, fontSize: `${fontSize}px`, fontFamily: font }">
                                                    {{ prefix }}{{ startNumber }}{{ suffix }}
                                                </span>
                                            </div>
                                            <div class="col-span-3 row-span-1"></div>
                                            <div class="flex items-end justify-start p-3" :class="{ 'bg-blue-50': position === 'bottom-left' }">
                                                <span v-if="position === 'bottom-left'" :style="{ color: color, fontSize: `${fontSize}px`, fontFamily: font }">
                                                    {{ prefix }}{{ startNumber }}{{ suffix }}
                                                </span>
                                            </div>
                                            <div class="flex items-end justify-center p-3" :class="{ 'bg-blue-50': position === 'bottom-center' }">
                                                <span v-if="position === 'bottom-center'" :style="{ color: color, fontSize: `${fontSize}px`, fontFamily: font }">
                                                    {{ prefix }}{{ startNumber }}{{ suffix }}
                                                </span>
                                            </div>
                                            <div class="flex items-end justify-end p-3" :class="{ 'bg-blue-50': position === 'bottom-right' }">
                                                <span v-if="position === 'bottom-right'" :style="{ color: color, fontSize: `${fontSize}px`, fontFamily: font }">
                                                    {{ prefix }}{{ startNumber }}{{ suffix }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Document indicator -->
                                    <div class="w-32 h-44 bg-white border border-gray-300 shadow-sm flex items-center justify-center text-gray-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                        </svg>
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
                                        <svg class="animate-spin h-5 w-5 ml-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        جاري المعالجة...
                                    </span>
                                    <span v-else>إضافة أرقام الصفحات</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Instructions -->
                <div class="mt-8 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">كيفية إضافة أرقام الصفحات لملف PDF</h3>
                    <div class="grid md:grid-cols-3 gap-6">
                        <div class="flex flex-col items-center text-center">
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                                <span class="text-blue-600 font-bold">1</span>
                            </div>
                            <h4 class="font-medium mb-2">اختر الملف</h4>
                            <p class="text-gray-600 text-sm">قم بتحميل ملف PDF الذي ترغب في إضافة أرقام الصفحات له.</p>
                        </div>
                        <div class="flex flex-col items-center text-center">
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                                <span class="text-blue-600 font-bold">2</span>
                            </div>
                            <h4 class="font-medium mb-2">ضبط الإعدادات</h4>
                            <p class="text-gray-600 text-sm">اختر موضع الأرقام ونمط الخط وحجمه واللون والبادئة واللاحقة.</p>
                        </div>
                        <div class="flex flex-col items-center text-center">
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                                <span class="text-blue-600 font-bold">3</span>
                            </div>
                            <h4 class="font-medium mb-2">تنزيل الملف المعالج</h4>
                            <p class="text-gray-600 text-sm">انتظر انتهاء العملية وقم بتنزيل ملف PDF مع أرقام الصفحات.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template> 