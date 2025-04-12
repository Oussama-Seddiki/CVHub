<template>
    <AuthenticatedLayout title="تحويل PDF إلى Excel">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                تحويل PDF إلى Excel
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

                <div v-if="isLoggedIn && !hasActiveSubscription" class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="mr-3">
                            <p class="text-sm text-yellow-700">
                                أنت تستخدم الإصدار المجاني. قم بترقية حسابك للحصول على مزيد من المميزات.
                                <a :href="route('subscription.plans')" class="font-medium underline text-yellow-700 hover:text-yellow-600">
                                    ترقية الحساب
                                </a>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <!-- عملية الرفع -->
                    <div v-if="!processing && !downloadUrl">
                        <!-- رسالة الخطأ -->
                        <div v-if="error" class="bg-red-50 border-l-4 border-red-400 p-4 mb-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="mr-3">
                                    <p class="text-sm text-red-700">{{ error }}</p>
                                </div>
                            </div>
                        </div>

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
                                    قم بسحب ملف PDF هنا أو 
                                    <span @click="openFileDialog" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 cursor-pointer">
                                        اضغط للاختيار
                                    </span>
                                </p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-500">PDF (الحد الأقصى 20 ميجابايت)</p>
                                <input type="file" ref="fileInput" class="hidden" @change="handleFileChange" accept=".pdf" />
                            </div>
                            <div v-else class="text-right">
                                <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-700 p-3 rounded-md">
                                    <button @click="reset" class="text-sm text-red-600 dark:text-red-400 hover:text-red-500">
                                        إزالة
                                    </button>
                                    <div class="flex items-center">
                                        <svg class="h-5 w-5 text-indigo-500 ml-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ selectedFile.name }}</span>
                                    </div>
                                </div>

                                <!-- معلومات حجم الملف -->
                                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400 text-left">
                                    حجم الملف: {{ formatFileSize(selectedFile.size) }}
                                </div>
                            </div>
                        </div>

                        <!-- خيارات التحويل -->
                        <div v-if="selectedFile" class="mt-6">
                            <h3 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-4">خيارات التحويل</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">تنسيق الإخراج</label>
                                    <select v-model="outputFormat" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                        <option value="xlsx">XLSX (Excel الحديث)</option>
                                        <option value="xls">XLS (Excel القديم)</option>
                                        <option value="csv">CSV (قيم مفصولة بفواصل)</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">نمط استخراج البيانات</label>
                                    <select v-model="dataExtractionMode" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                        <option value="tables">الجداول فقط</option>
                                        <option value="all_data">جميع البيانات</option>
                                        <option value="spreadsheet">ورقة عمل لكل صفحة</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">الصفحات للتحويل</label>
                                    <div class="relative inline-block w-full">
                                        <div class="mb-2">
                                            <label class="inline-flex items-center">
                                                <input type="radio" v-model="pageOption" value="all" class="form-radio h-5 w-5 text-indigo-600 dark:text-indigo-400 rounded">
                                                <span class="mr-2 text-sm text-gray-700 dark:text-gray-300">جميع الصفحات</span>
                                            </label>
                                        </div>
                                        <div>
                                            <label class="inline-flex items-center">
                                                <input type="radio" v-model="pageOption" value="range" class="form-radio h-5 w-5 text-indigo-600 dark:text-indigo-400 rounded">
                                                <span class="mr-2 text-sm text-gray-700 dark:text-gray-300">نطاق محدد من الصفحات</span>
                                            </label>
                                        </div>
                                        <div v-if="pageOption === 'range'" class="mt-2">
                                            <input 
                                                type="text" 
                                                v-model="pageRange" 
                                                placeholder="مثال: 1-3,5,7-9" 
                                                class="mt-1 block w-full pl-3 pr-3 py-2 text-base border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                                            />
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">أدخل أرقام الصفحات مفصولة بفواصل أو نطاقات (مثل 1-5)</p>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">دقة التعرف على النص (OCR)</label>
                                    <div class="relative inline-block w-full">
                                        <label class="inline-flex items-center">
                                            <input type="checkbox" v-model="enableOcr" class="form-checkbox h-5 w-5 text-indigo-600 dark:text-indigo-400 rounded">
                                            <span class="mr-2 text-sm text-gray-700 dark:text-gray-300">تفعيل التعرف على النص في PDF</span>
                                        </label>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">مفيد للملفات المفحوصة أو التي تحتوي على صور نصية</p>
                                </div>
                            </div>

                            <button 
                                @click="processPdfToExcel" 
                                :disabled="!selectedFile || apiStatus.checking || (pageOption === 'range' && !pageRange)" 
                                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <svg v-if="apiStatus.checking" class="animate-spin ml-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>تحويل إلى Excel</span>
                            </button>
                        </div>
                    </div>

                    <!-- انتظار المعالجة -->
                    <div v-if="processing && !downloadUrl" class="text-center">
                        <svg class="animate-spin h-12 w-12 text-indigo-600 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="mt-4 text-lg font-medium text-gray-900 dark:text-gray-100">جاري تحويل الملف...</p>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">يرجى الانتظار حتى اكتمال العملية.</p>
                        
                        <!-- مؤشر التقدم -->
                        <div v-if="processingProgress > 0" class="w-full bg-gray-200 rounded-full h-2.5 mt-4 dark:bg-gray-700 max-w-md mx-auto">
                            <div class="bg-indigo-600 h-2.5 rounded-full" :style="{ width: processingProgress + '%' }"></div>
                        </div>
                        <p v-if="processingProgress > 0" class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ processingProgress }}% مكتمل</p>
                    </div>

                    <!-- نتيجة التحويل -->
                    <div v-if="downloadUrl" class="text-center">
                        <div class="rounded-md bg-green-50 dark:bg-green-900/20 p-4 mb-6">
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
import PdfProcessingService from '@/services/PdfProcessingService';

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
const outputFormat = ref('xlsx');
const dataExtractionMode = ref('tables');
const pageOption = ref('all');
const pageRange = ref('');
const enableOcr = ref(false);
const processingInterval = ref(null);

// API status check
const apiStatus = reactive({
    ready: false,
    checking: false,
    error: null
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
        if (!file.name.endsWith('.pdf')) {
            error.value = 'يرجى اختيار ملف بتنسيق PDF.';
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
        if (!file.name.endsWith('.pdf')) {
            error.value = 'يرجى اختيار ملف بتنسيق PDF.';
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
    outputFormat.value = 'xlsx';
    dataExtractionMode.value = 'tables';
    pageOption.value = 'all';
    pageRange.value = '';
    enableOcr.value = false;
};

const processPdfToExcel = async () => {
    if (!selectedFile.value) {
        error.value = 'يرجى اختيار ملف PDF أولاً.';
        return;
    }

    if (pageOption.value === 'range' && !pageRange.value) {
        error.value = 'يرجى تحديد نطاق الصفحات.';
        return;
    }

    if (!apiStatus.ready) {
        error.value = 'خدمة التحويل غير جاهزة. يرجى المحاولة مرة أخرى لاحقاً.';
        return;
    }

    try {
        processing.value = true;
        error.value = '';
        
        // بدء محاكاة التقدم
        simulateProgress();

        // خيارات التحويل
        const options = {
            outputFormat: outputFormat.value,
            dataExtractionMode: dataExtractionMode.value,
            pages: pageOption.value === 'all' ? 'all' : pageRange.value,
            enableOcr: enableOcr.value
        };

        // استدعاء API
        const result = await PdfProcessingService.processDocuments(selectedFile.value, 'pdf-to-excel', options);
        
        // إيقاف مؤشر التقدم
        if (processingInterval.value) {
            clearInterval(processingInterval.value);
            processingInterval.value = null;
        }
        processingProgress.value = 100;
        
        if (result && result.success && result.file) {
            downloadUrl.value = result.file;
        } else {
            throw new Error(result.message || 'حدث خطأ أثناء تحويل الملف.');
        }
    } catch (err) {
        console.error('Error processing PDF to Excel:', err);
        error.value = err.message || 'حدث خطأ أثناء تحويل الملف. يرجى المحاولة مرة أخرى.';
        if (processingInterval.value) {
            clearInterval(processingInterval.value);
            processingInterval.value = null;
        }
        processingProgress.value = 0;
        processing.value = false;
    } finally {
        processing.value = false;
    }
};

// فحص حالة API عند تحميل المكون
onMounted(async () => {
    apiStatus.checking = true;
    try {
        const status = await PdfProcessingService.checkApiStatus();
        apiStatus.ready = status.success;
        if (!status.success) {
            apiStatus.error = status.message;
            console.error('API Status Error:', status.message);
        }
    } catch (err) {
        apiStatus.ready = false;
        apiStatus.error = err.message;
        console.error('API Status Check Error:', err);
    } finally {
        apiStatus.checking = false;
    }
});
</script> 