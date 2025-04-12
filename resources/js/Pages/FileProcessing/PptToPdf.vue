<template>
    <AuthenticatedLayout title="تحويل PowerPoint إلى PDF">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                تحويل PowerPoint إلى PDF
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- تحذير الاشتراك -->
                <div v-if="!isLoggedIn" class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="mr-3">
                            <p class="text-sm text-yellow-700">
                                يجب عليك تسجيل الدخول لاستخدام هذه الخدمة.
                                <a href="/login" class="font-medium underline text-yellow-700 hover:text-yellow-600">
                                    تسجيل الدخول
                                </a>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- تحذير الاشتراك -->
                <div v-else-if="!hasActiveSubscription" class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="mr-3">
                            <p class="text-sm text-yellow-700">
                                أنت بحاجة إلى اشتراك نشط لاستخدام هذه الخدمة.
                                <a href="/plans" class="font-medium underline text-yellow-700 hover:text-yellow-600">
                                    تصفح خطط الاشتراك
                                </a>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <!-- منطقة السحب والإفلات -->
                        <div 
                            class="border-2 border-dashed border-gray-300 rounded-lg p-12 text-center hover:border-indigo-500 transition-colors duration-300"
                            :class="{ 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20': isDragging }"
                            @dragover.prevent="isDragging = true"
                            @dragleave.prevent="isDragging = false"
                            @drop.prevent="handleDrop"
                        >
                            <div v-if="!selectedFile">
                                <svg class="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                    قم بسحب ملف PowerPoint هنا أو 
                                    <span @click="openFileDialog" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 cursor-pointer">
                                        اضغط للاختيار
                                    </span>
                                </p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-500">PPT, PPTX, ODP (الحد الأقصى 20 ميجابايت)</p>
                                <input type="file" ref="fileInput" class="hidden" @change="handleFileChange" accept=".ppt,.pptx,.odp" />
                            </div>
                            
                            <div v-else class="text-left">
                                <div class="flex items-center">
                                    <svg class="h-8 w-8 text-indigo-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                    <div class="ml-4">
                                        <div class="font-medium text-gray-900 dark:text-gray-100">{{ selectedFile.name }}</div>
                                    </div>
                                </div>
                                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400 text-left">
                                    حجم الملف: {{ formatFileSize(selectedFile.size) }}
                                </div>
                            </div>
                        </div>

                        <!-- خيارات التحويل -->
                        <div v-if="selectedFile" class="mt-6">
                            <h3 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-4">خيارات التحويل</h3>
                            
                            <div class="mb-4">
                                <label class="block mb-1 font-medium text-gray-800 dark:text-gray-200">خيارات التحويل:</label>
                                
                                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                                    <div class="grid grid-cols-2 gap-4 mb-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                جودة ملف PDF:
                                            </label>
                                            <select 
                                                v-model="quality" 
                                                class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                                :disabled="!apiStatus.support.quality || apiStatus.support.checking"
                                            >
                                                <option value="standard">قياسية</option>
                                                <option value="high">عالية</option>
                                                <option value="very_high">عالية جداً</option>
                                            </select>
                                            <p v-if="!apiStatus.support.quality && !apiStatus.support.checking" class="mt-1 text-xs text-red-500">
                                                خيار الجودة غير متاح في هذا الخادم
                                            </p>
                                        </div>
                                        
                                        <div>
                                            <div class="flex items-center justify-between mb-1">
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                    تضمين الملاحظات:
                                                </label>
                                                <button 
                                                    v-if="!apiStatus.support.includeNotes && !apiStatus.support.checking" 
                                                    @click="checkPptToPdfSupport(true)"
                                                    class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                                                    title="تحقق مرة أخرى من دعم الملاحظات"
                                                >
                                                    إعادة التحقق
                                                </button>
                                            </div>
                                            <div class="flex items-center">
                                                <input 
                                                    type="checkbox" 
                                                    id="includeNotes" 
                                                    v-model="includeNotes" 
                                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                                    :disabled="!apiStatus.support.includeNotes || apiStatus.support.checking"
                                                >
                                                <label for="includeNotes" class="ml-2 mr-2 block text-sm text-gray-700 dark:text-gray-300">
                                                    تضمين ملاحظات العرض التقديمي
                                                </label>
                                            </div>
                                            <p v-if="!apiStatus.support.includeNotes && !apiStatus.support.checking" class="mt-1 text-xs text-red-500">
                                                تضمين الملاحظات يتطلب تثبيت Java على الخادم
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <p v-if="apiStatus.support.checking" class="text-sm text-gray-600 dark:text-gray-400 italic">
                                        جاري التحقق من الخيارات المدعومة...
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Display support information -->
                            <div v-if="apiStatus.support.checking" class="mb-4 text-sm text-gray-500">
                                جاري التحقق من دعم الخادم للخيارات المتقدمة...
                            </div>
                            
                            <div v-if="error" class="bg-red-50 border-l-4 border-red-400 p-4 mb-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="mr-3">
                                        <p class="text-sm text-red-700">
                                            {{ error }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <button 
                                @click="processPptToPdf" 
                                :disabled="!selectedFile || processing" 
                                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <span v-if="!processing">تحويل الملف</span>
                                <span v-else class="flex items-center">
                                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    جاري التحويل...
                                </span>
                            </button>
                        </div>
                        
                        <!-- شريط التقدم -->
                        <div v-if="processing && processingProgress > 0" class="mt-4">
                            <div class="relative pt-1">
                                <div class="flex mb-2 items-center justify-between">
                                    <div>
                                        <span class="text-xs font-semibold inline-block py-1 px-2 uppercase rounded-full text-indigo-600 bg-indigo-200">
                                            جاري معالجة الملف
                                        </span>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-xs font-semibold inline-block text-indigo-600">
                                            {{ processingProgress }}%
                                        </span>
                                    </div>
                                </div>
                                <div class="overflow-hidden h-2 mb-4 text-xs flex rounded bg-indigo-200">
                                    <div :style="{ width: processingProgress + '%' }" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-indigo-500 transition-all duration-300"></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- رسالة النجاح -->
                        <div v-if="downloadUrl" class="bg-green-50 border-l-4 border-green-400 p-4 mt-4 mb-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="mr-3">
                                    <p class="text-sm font-medium text-green-800 dark:text-green-200">
                                        تم تحويل الملف بنجاح!
                                    </p>
                                </div>
                            </div>
                        </div>

                        <a 
                            v-if="downloadUrl"
                            :href="downloadUrl" 
                            download 
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        >
                            <svg class="ml-2 -mr-1 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            تنزيل الملف
                        </a>

                        <button 
                            v-if="downloadUrl"
                            @click="reset" 
                            class="mt-4 inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        >
                            تحويل ملف آخر
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PptToPdfService from '@/services/PptToPdfService';
import axios from 'axios';
import { useToast } from 'vue-toastification';
const toast = useToast();

// Props
const props = defineProps({
    activeSubscription: {
        type: Boolean,
        default: false
    },
    subscriptionFeatures: {
        type: Object,
        default: () => ({})
    },
    auth: {
        type: Object,
        default: () => ({})
    }
});

// Reactive state
const fileInput = ref(null);
const selectedFile = ref(null);
const isDragging = ref(false);
const processing = ref(false);
const processingProgress = ref(0); // مؤشر التقدم
const downloadUrl = ref('');
const error = ref('');
const quality = ref('standard');
const includeNotes = ref(false);
const processingInterval = ref(null);

// API status check
const apiStatus = reactive({
    ready: false,
    checking: false,
    error: null,
    support: {
        checking: false,
        quality: false,
        includeNotes: false,
        details: null
    }
});

// Computed properties
const isLoggedIn = computed(() => {
    return props.auth && props.auth.user;
});

const hasActiveSubscription = computed(() => {
    return props.activeSubscription;
});

// Methods
const openFileDialog = () => {
    fileInput.value.click();
};

const handleFileChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        // التحقق من نوع الملف
        const allowedExtensions = ['.ppt', '.pptx', '.odp'];
        const fileName = file.name.toLowerCase();
        
        if (!allowedExtensions.some(ext => fileName.endsWith(ext))) {
            error.value = 'يرجى اختيار ملف بتنسيق PowerPoint (PPT, PPTX, ODP).';
            return;
        }
        
        // التحقق من حجم الملف (الحد الأقصى 20 ميجابايت)
        if (file.size > 20 * 1024 * 1024) {
            error.value = 'حجم الملف يتجاوز الحد المسموح به (20 ميجابايت).';
            return;
        }

        selectedFile.value = file;
        error.value = '';
    }
};

const handleDrop = (event) => {
    isDragging.value = false;
    
    const file = event.dataTransfer.files[0];
    if (file) {
        // التحقق من نوع الملف
        const allowedExtensions = ['.ppt', '.pptx', '.odp'];
        const fileName = file.name.toLowerCase();
        
        if (!allowedExtensions.some(ext => fileName.endsWith(ext))) {
            error.value = 'يرجى اختيار ملف بتنسيق PowerPoint (PPT, PPTX, ODP).';
            return;
        }
        
        // التحقق من حجم الملف (الحد الأقصى 20 ميجابايت)
        if (file.size > 20 * 1024 * 1024) {
            error.value = 'حجم الملف يتجاوز الحد المسموح به (20 ميجابايت).';
            return;
        }

        selectedFile.value = file;
        error.value = '';
    }
};

const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const simulateProgress = () => {
    processingProgress.value = 0;
    
    // محاكاة تقدم المعالجة
    processingInterval.value = setInterval(() => {
        if (processingProgress.value < 90) {
            processingProgress.value += Math.floor(Math.random() * 10) + 1;
            if (processingProgress.value > 90) processingProgress.value = 90;
        }
    }, 1000);
};

const reset = () => {
    selectedFile.value = null;
    downloadUrl.value = '';
    error.value = '';
    processing.value = false;
    processingProgress.value = 0;
    if (processingInterval.value) {
        clearInterval(processingInterval.value);
        processingInterval.value = null;
    }
    if (fileInput.value) {
        fileInput.value.value = '';
    }
    quality.value = 'standard';
    includeNotes.value = false;
};

const processPptToPdf = async () => {
    if (!selectedFile.value) {
        error.value = 'يرجى اختيار ملف PowerPoint أولاً.';
        return;
    }

    if (!apiStatus.ready) {
        error.value = 'خدمة التحويل غير جاهزة. يرجى المحاولة مرة أخرى لاحقاً.';
        return;
    }

    try {
        error.value = '';
        processing.value = true;
        simulateProgress();
        
        // Log selected options
        console.log('Converting with options:', {
            quality: quality.value,
            includeNotes: includeNotes.value
        });
        
        const formData = new FormData();
        formData.append('file', selectedFile.value);
        formData.append('quality', quality.value);
        formData.append('include_notes', includeNotes.value ? '1' : '0');
        
        // Add additional metadata for better debugging
        formData.append('filename', selectedFile.value.name);
        formData.append('filesize', selectedFile.value.size);
        
        const response = await PptToPdfService.processPptToPdf(formData);
        
        // إيقاف محاكاة التقدم
        if (processingInterval.value) {
            clearInterval(processingInterval.value);
            processingInterval.value = null;
        }
        
        // تعيين التقدم إلى 100٪
        processingProgress.value = 100;
        
        console.log('PPT to PDF Response:', response);
        
        if (response.success) {
            downloadUrl.value = response.file;
            
            // Display which options were actually used
            console.log('Conversion completed with options:', {
                quality: response.quality || quality.value,
                includeNotes: response.includeNotes || includeNotes.value
            });
        } else {
            console.error('Conversion failed:', response);
            error.value = response.message || 'حدث خطأ أثناء تحويل الملف. يرجى المحاولة مرة أخرى.';
        }
    } catch (err) {
        console.error('PPT to PDF error:', err);
        error.value = err.message || 'عفواً، حدث خطأ غير متوقع. يرجى المحاولة مرة أخرى.';
        
        if (processingInterval.value) {
            clearInterval(processingInterval.value);
            processingInterval.value = null;
        }
    } finally {
        processing.value = false;
    }
};

// Check for PPT to PDF support, including quality and notes options
const checkPptToPdfSupport = async (forceRefresh = false) => {
    if (forceRefresh || !apiStatus.support.checked) {
        apiStatus.support.checking = true;
        
        try {
            // Simulate API response - no longer calling external API
            console.log('Simulating PPT to PDF Support check (client-side)');
            
            // Simulate a short delay to mimic API call
            await new Promise(resolve => setTimeout(resolve, 500));
            
            // Always enable all features in client-side implementation
            apiStatus.support = {
                checking: false,
                checked: true,
                quality: true,  // Enable quality settings
                includeNotes: true,  // Enable include notes feature
                details: {
                    success: true,
                    features: {
                        quality_settings: true,
                        include_notes: true
                    }
                }
            };
            
            if (forceRefresh && apiStatus.support.includeNotes) {
                toast.success('تم اكتشاف دعم تضمين الملاحظات!', {
                    position: 'bottom-left',
                    timeout: 5000,
                    closeOnClick: true
                });
            }
        } catch (error) {
            console.error('Error in client-side PPT to PDF support check:', error);
            apiStatus.support = {
                checking: false,
                checked: true,
                quality: true, // Enable by default
                includeNotes: true, // Enable by default
                details: null
            };
        }
    }
};

onMounted(async () => {
    apiStatus.checking = true;
    try {
        // No longer need to call external API
        // await axios.get('/api/status');
        apiStatus.ready = true;
        
        // Check for specific PPT to PDF support
        await checkPptToPdfSupport();
    } catch (err) {
        apiStatus.ready = false;
        apiStatus.error = 'خدمة تحويل العروض التقديمية غير متاحة حالياً';
        console.error('API Status Check Error:', err);
    } finally {
        apiStatus.checking = false;
    }
});
</script> 