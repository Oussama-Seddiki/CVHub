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
const totalPages = ref(0);
const pageRotations = ref([]);
const rotationOptions = [
    { value: 0, label: '0 درجة', icon: '↑' },
    { value: 90, label: '90 درجة', icon: '→' },
    { value: 180, label: '180 درجة', icon: '↓' },
    { value: 270, label: '270 درجة', icon: '←' },
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
    
    // Generate preview and simulate counting pages
    const reader = new FileReader();
    reader.onload = (e) => {
        filePreview.value = e.target.result;
        // Simulate counting pages (in a real app, you would use a PDF library)
        totalPages.value = Math.floor(Math.random() * 10) + 3;
        // Initialize rotation values for each page
        pageRotations.value = Array(totalPages.value).fill(0);
    };
    reader.readAsDataURL(selectedFile);
}

// Reset everything
function resetForm() {
    file.value = null;
    fileName.value = '';
    filePreview.value = null;
    totalPages.value = 0;
    pageRotations.value = [];
    errorMessage.value = '';
    processedFileUrl.value = '';
}

// Rotate a specific page
function rotatePage(pageIndex, degrees) {
    pageRotations.value[pageIndex] = (pageRotations.value[pageIndex] + degrees) % 360;
}

// Process the PDF to apply the rotations
async function processPdf() {
    if (!hasActiveSubscription.value) {
        errorMessage.value = 'هذه الخدمة متاحة فقط للمستخدمين المشتركين. يرجى الاشتراك للاستفادة من هذه الميزة.';
        return;
    }
    
    if (!file.value) {
        errorMessage.value = 'يرجى إضافة ملف PDF أولاً.';
        return;
    }
    
    if (!pageRotations.value.some(rotation => rotation !== 0)) {
        errorMessage.value = 'يرجى تدوير صفحة واحدة على الأقل.';
        return;
    }
    
    try {
        isProcessing.value = true;
        errorMessage.value = '';
        
        // Format the rotation data as required by the API
        const rotations = pageRotations.value.map((rotation, index) => {
            return {
                page: index + 1,
                degrees: rotation
            };
        }).filter(item => item.degrees !== 0);
        
        const result = await PdfProcessingService.processPdf(file.value, 'rotate', {
            rotations: rotations
        });
        
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
    <Head title="تدوير ملف PDF" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">تدوير ملف PDF</h2>
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
                            <h3 class="mt-4 text-xl font-medium text-gray-900">تم تدوير ملف PDF بنجاح!</h3>
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

                    <!-- Upload State -->
                    <div v-else>
                        <div class="text-center mb-8">
                            <h3 class="text-lg font-medium text-gray-900 mb-2">تدوير ملف PDF</h3>
                            <p class="text-gray-600">قم بتحميل ملف PDF لتدوير صفحاته حسب الحاجة.</p>
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

                        <!-- Page Rotation UI (if file selected) -->
                        <div v-if="file" class="mt-6">
                            <div class="flex justify-between items-center bg-gray-50 p-4 rounded-md mb-6">
                                <div class="flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-500 ml-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    </svg>
                                    <div>
                                        <p class="font-medium text-gray-900">{{ fileName }}</p>
                                        <p class="text-sm text-gray-500">{{ totalPages }} صفحات</p>
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

                            <h4 class="font-medium text-gray-900 mb-4">اختر الصفحات التي تريد تدويرها:</h4>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mb-6">
                                <div v-for="(rotation, index) in pageRotations" :key="index" class="border rounded-lg p-4">
                                    <div class="flex justify-between items-center mb-2">
                                        <h5 class="font-medium">صفحة {{ index + 1 }}</h5>
                                        <span class="text-gray-500">{{ rotation }}°</span>
                                    </div>
                                    
                                    <div class="relative h-32 bg-gray-100 rounded-md flex items-center justify-center mb-3">
                                        <!-- Page Preview with Rotation -->
                                        <div 
                                            class="w-16 h-24 bg-white border border-gray-300 flex items-center justify-center text-gray-700 shadow-sm transition-transform duration-300"
                                            :style="{ transform: `rotate(${rotation}deg)` }"
                                        >
                                            <span>{{ index + 1 }}</span>
                                        </div>
                                    </div>
                                    
                                    <div class="grid grid-cols-4 gap-2">
                                        <button 
                                            v-for="option in rotationOptions" :key="option.value"
                                            class="p-1 rounded-md text-center transition-colors"
                                            :class="{ 'bg-blue-100 text-blue-700': rotation === option.value, 'bg-gray-100 text-gray-700 hover:bg-gray-200': rotation !== option.value }"
                                            @click="pageRotations[index] = option.value"
                                        >
                                            {{ option.icon }}
                                        </button>
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
                                    <span v-else>تدوير الصفحات</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Instructions -->
                <div class="mt-8 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">كيفية تدوير ملف PDF</h3>
                    <div class="grid md:grid-cols-3 gap-6">
                        <div class="flex flex-col items-center text-center">
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                                <span class="text-blue-600 font-bold">1</span>
                            </div>
                            <h4 class="font-medium mb-2">اختر الملف</h4>
                            <p class="text-gray-600 text-sm">قم بتحميل ملف PDF الذي ترغب في تدوير صفحاته.</p>
                        </div>
                        <div class="flex flex-col items-center text-center">
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                                <span class="text-blue-600 font-bold">2</span>
                            </div>
                            <h4 class="font-medium mb-2">حدد زاوية التدوير</h4>
                            <p class="text-gray-600 text-sm">اختر زاوية التدوير المناسبة لكل صفحة (0 أو 90 أو 180 أو 270 درجة).</p>
                        </div>
                        <div class="flex flex-col items-center text-center">
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                                <span class="text-blue-600 font-bold">3</span>
                            </div>
                            <h4 class="font-medium mb-2">تنزيل الملف المعالج</h4>
                            <p class="text-gray-600 text-sm">انتظر انتهاء العملية وقم بتنزيل ملف PDF المعدل.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template> 