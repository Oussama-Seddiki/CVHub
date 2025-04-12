<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed, onMounted } from 'vue';
import SubscriptionWarning from '@/components/SubscriptionWarning.vue';
import PdfExtractService from '@/services/PdfExtractService';

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
const pageRanges = ref('');

// Use the activeSubscription prop directly
const hasActiveSubscription = computed(() => props.activeSubscription);

// Check if there is a file uploaded
const hasFile = computed(() => file.value !== null);

// Set the file to upload
function setFile(newFile) {
    // Check if active subscription
    if (!hasActiveSubscription.value) {
        errorMessage.value = 'هذه الخدمة متاحة فقط للمستخدمين المشتركين. يرجى الاشتراك للاستفادة من هذه الميزة.';
        return;
    }
    
    // Check if the file is a PDF
    if (newFile.type !== 'application/pdf') {
        errorMessage.value = 'يمكنك فقط إضافة ملفات PDF.';
        return;
    }
    
    // Set the file
    file.value = newFile;
    errorMessage.value = '';
}

// Handle file selection
function handleFileSelect(event) {
    if (event.target.files.length) {
        setFile(event.target.files[0]);
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
        setFile(e.dataTransfer.files[0]);
    }
}

// Reset the file
function removeFile() {
    file.value = null;
}

// Process the PDF to split it into pages
async function processPdf() {
    if (!hasActiveSubscription.value) {
        errorMessage.value = 'هذه الخدمة متاحة فقط للمستخدمين المشتركين. يرجى الاشتراك للاستفادة من هذه الميزة.';
        return;
    }
    
    if (!file.value) {
        errorMessage.value = 'يرجى إضافة ملف PDF أولاً.';
        return;
    }
    
    if (!pageRanges.value) {
        errorMessage.value = 'يرجى تحديد الصفحات التي ترغب في استخراجها.';
        return;
    }
    
    try {
        isProcessing.value = true;
        errorMessage.value = '';
        
        const metadata = {
            title: 'PDF مستخرج الصفحات',
            author: 'CVHub PDF Tool'
        };
        
        const result = await PdfExtractService.extractPages(file.value, pageRanges.value, metadata);
        
        if (result.success) {
            processedFileUrl.value = result.data?.output_url || result.file;
        } else {
            errorMessage.value = result.message || 'حدث خطأ أثناء استخراج الصفحات. يرجى المحاولة مرة أخرى.';
        }
    } catch (error) {
        console.error('Error extracting PDF pages:', error);
        errorMessage.value = 'حدث خطأ أثناء استخراج الصفحات. يرجى المحاولة مرة أخرى.';
    } finally {
        isProcessing.value = false;
    }
}

// Download the processed file
function downloadProcessedFile() {
    if (processedFileUrl.value) {
        window.open(processedFileUrl.value, '_blank');
    }
}

// Reset the form to start a new process
function startNew() {
    file.value = null;
    processedFileUrl.value = null;
    errorMessage.value = '';
    pageRanges.value = '';
}

// Format bytes to a human-readable size (KB, MB, etc.)
function formatBytes(bytes, decimals = 2) {
    if (bytes === 0) return '0 بايت';
    
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['بايت', 'كيلوبايت', 'ميجابايت', 'جيجابايت', 'تيرابايت'];
    
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}

// Check if the API is ready
const apiStatus = ref({
    ready: false,
    loading: true,
    message: 'جاري التحقق من حالة الخدمة...'
});

onMounted(async () => {
    try {
        // Set API status to ready since we're using client-side processing
        apiStatus.value = {
            ready: true,
            loading: false,
            message: 'خدمة استخراج الصفحات جاهزة للاستخدام'
        };
    } catch (error) {
        apiStatus.value = {
            ready: false,
            loading: false,
            message: 'حدث خطأ أثناء التحقق من حالة الخدمة'
        };
        console.error('Error checking API status:', error);
    }
});
</script>

<template>
    <Head title="استخراج صفحات من PDF" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">استخراج صفحات من PDF</h2>
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
                <div v-if="!activeSubscription" class="mb-6">
                    <SubscriptionWarning 
                        message="للوصول إلى جميع ميزات معالجة الملفات، يرجى ترقية اشتراكك."
                        :subscription-status="subscriptionStatus"
                        :subscription-ends-at="subscriptionEndsAt"
                    />
                </div>
                
                <!-- API Status -->
                <div v-if="!apiStatus.ready && !apiStatus.loading" class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    <span class="block sm:inline">{{ apiStatus.message }}</span>
                </div>

                <!-- Error Message -->
                <div v-if="errorMessage" class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    <span class="block sm:inline">{{ errorMessage }}</span>
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
                            <h3 class="mt-4 text-xl font-medium text-gray-900">تم استخراج صفحات PDF بنجاح!</h3>
                            <p class="mt-1 text-gray-500">الملف جاهز للتنزيل الآن.</p>
                        </div>
                        <div class="flex flex-col sm:flex-row justify-center space-y-3 sm:space-y-0 sm:space-x-4 sm:space-x-reverse">
                            <button 
                                @click="downloadProcessedFile" 
                                class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                تنزيل الملف
                            </button>
                            <button 
                                @click="startNew" 
                                class="inline-flex items-center justify-center px-5 py-3 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                بدء عملية استخراج جديدة
                            </button>
                        </div>
                    </div>

                    <!-- Upload State -->
                    <div v-else>
                        <div class="text-center mb-8">
                            <h3 class="text-lg font-medium text-gray-900 mb-2">استخراج صفحات من ملف PDF</h3>
                            <p class="text-gray-600">قم بتحميل ملف PDF واختر صفحات محددة لاستخراجها كملف PDF جديد.</p>
                        </div>

                        <!-- File Drop Area -->
                        <div 
                            v-if="!hasFile"
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
                                    <input type="file" class="sr-only" accept="application/pdf" @change="handleFileSelect" />
                                </label>
                            </p>
                            <p class="mt-1 text-xs text-gray-500">يمكنك تحميل ملفات PDF فقط</p>
                        </div>
                    
                        <!-- Selected File and Options -->
                        <div v-if="hasFile" class="space-y-6">
                            <!-- Selected File Info -->
                            <div class="flex items-center p-4 bg-gray-50 rounded-md">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-500 ml-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900">{{ file.name }}</p>
                                    <p class="text-xs text-gray-500">{{ formatBytes(file.size) }}</p>
                                </div>
                                <button 
                                    @click="removeFile" 
                                    class="text-red-500 hover:text-red-700"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            
                            <!-- Page Ranges Input -->
                            <div>
                                <label for="page-ranges" class="block text-sm font-medium text-gray-700 mb-1">
                                    حدد الصفحات التي ترغب في استخراجها
                                </label>
                                <input
                                    id="page-ranges"
                                    v-model="pageRanges"
                                    type="text"
                                    placeholder="مثال: 1,3-5,7,10-12"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                />
                                <p class="mt-1 text-sm text-gray-500">
                                    يمكنك تحديد صفحات فردية (1، 3) أو نطاقات (1-5) أو مزيج منهما.
                                </p>
                            </div>
                            
                            <!-- Submit Button -->
                            <button 
                                @click="processPdf" 
                                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed"
                                :disabled="isProcessing || !pageRanges || !apiStatus.ready"
                            >
                                <span v-if="isProcessing" class="flex items-center">
                                    <svg class="animate-spin h-5 w-5 ml-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    جاري المعالجة...
                                </span>
                                <span v-else class="flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                                    </svg>
                                    استخراج الصفحات
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Instructions -->
                <div class="mt-8 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">كيف تستخرج صفحات من ملف PDF</h3>
                    <div class="grid md:grid-cols-3 gap-6">
                        <div class="flex flex-col items-center text-center">
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                                <span class="text-blue-600 font-bold">1</span>
                            </div>
                            <h4 class="font-medium mb-2">اختر الملف</h4>
                            <p class="text-gray-600 text-sm">قم بتحميل ملف PDF الذي تريد استخراج صفحات منه.</p>
                        </div>
                        <div class="flex flex-col items-center text-center">
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                                <span class="text-blue-600 font-bold">2</span>
                            </div>
                            <h4 class="font-medium mb-2">حدد الصفحات</h4>
                            <p class="text-gray-600 text-sm">حدد الصفحات التي تريد استخراجها من الملف الأصلي.</p>
                        </div>
                        <div class="flex flex-col items-center text-center">
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                                <span class="text-blue-600 font-bold">3</span>
                            </div>
                            <h4 class="font-medium mb-2">قم بالاستخراج</h4>
                            <p class="text-gray-600 text-sm">انقر على "استخراج الصفحات" وقم بتنزيل الملف المعالج.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Add any custom styles here */
</style> 