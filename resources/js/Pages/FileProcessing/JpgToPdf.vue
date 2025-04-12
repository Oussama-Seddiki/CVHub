<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed, onMounted } from 'vue';
import SubscriptionWarning from '@/components/SubscriptionWarning.vue';
import ImagesToPdfService from '@/services/ImagesToPdfService';
import axios from 'axios';

// Import shared animations CSS
import '@/css/file-processing-animations.css';

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

const files = ref([]);
const isProcessing = ref(false);
const processedFileUrl = ref(null);
const errorMessage = ref('');
const dragActive = ref(false);

// PDF options
const pageSize = ref('A4');
const orientation = ref('portrait');
const margin = ref(10);

// Available options
const pageSizeOptions = [
    { value: 'A4', label: 'A4 (210 × 297 مم)' },
    { value: 'A3', label: 'A3 (297 × 420 مم)' },
    { value: 'Letter', label: 'Letter (216 × 279 مم)' },
    { value: 'Legal', label: 'Legal (216 × 356 مم)' },
];

const orientationOptions = [
    { value: 'portrait', label: 'عمودي (Portrait)' },
    { value: 'landscape', label: 'أفقي (Landscape)' },
];

// Use the activeSubscription prop directly
const hasActiveSubscription = computed(() => props.activeSubscription);

// Handle file selection
function handleFilesSelected(e) {
    const selectedFiles = Array.from(e.target.files);
    if (!selectedFiles.length) return;
    
    // Reset states
    errorMessage.value = '';
    processedFileUrl.value = '';
    
    // Validate file types
    const invalidFiles = selectedFiles.filter(file => !file.type.startsWith('image/'));
    if (invalidFiles.length > 0) {
        errorMessage.value = 'يرجى اختيار ملفات صور فقط (JPG, PNG, GIF)';
        return;
    }
    
    // Add new files to the list with previews
    selectedFiles.forEach(file => {
        const reader = new FileReader();
        reader.onload = (e) => {
            files.value.push({
                file: file,
                name: file.name,
                size: formatFileSize(file.size),
                preview: e.target.result
            });
        };
        reader.readAsDataURL(file);
    });
}

// Format file size
function formatFileSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(1) + ' MB';
}

// Remove a file from the list
function removeFile(index) {
    files.value.splice(index, 1);
}

// Reorder files
function moveFile(index, direction) {
    const newIndex = index + direction;
    if (newIndex < 0 || newIndex >= files.value.length) return;
    
    const temp = files.value[index];
    files.value[index] = files.value[newIndex];
    files.value[newIndex] = temp;
}

// Reset everything
function resetForm() {
    files.value = [];
    processedFileUrl.value = '';
    errorMessage.value = '';
    // Reset options to defaults
    pageSize.value = 'A4';
    orientation.value = 'portrait';
    margin.value = 10;
}

// Process the images to PDF
async function processPdf() {
    if (!hasActiveSubscription.value) {
        errorMessage.value = 'هذه الخدمة متاحة فقط للمستخدمين المشتركين. يرجى الاشتراك للاستفادة من هذه الميزة.';
        return;
    }
    
    if (files.value.length === 0) {
        errorMessage.value = 'يرجى إضافة صورة واحدة على الأقل.';
        return;
    }
    
    try {
        isProcessing.value = true;
        errorMessage.value = '';
        
        const options = {
            pageSize: pageSize.value,
            orientation: orientation.value,
            margin: parseInt(margin.value)
        };
        
        // Extract actual file objects for processing
        const imageFiles = files.value.map(item => item.file);
        
        console.log('Processing PDF conversion with options:', options);
        const result = await ImagesToPdfService.convertImagesToPdf(imageFiles, options);
        
        if (result.success) {
            if (result.isClientSideGenerated) {
                // For client-side generated PDFs (data URLs)
                console.log('Using client-side generated PDF');
                processedFileUrl.value = result.file;
            } else {
                // For server-generated PDFs (URLs)
                console.log('Using server-generated PDF');
                processedFileUrl.value = result.file;
            }
        } else {
            errorMessage.value = result.message || 'حدث خطأ أثناء معالجة الملفات. يرجى المحاولة مرة أخرى.';
        }
    } catch (error) {
        console.error('Error processing images:', error);
        errorMessage.value = error.message || 'حدث خطأ أثناء معالجة الملفات. يرجى المحاولة مرة أخرى.';
    } finally {
        isProcessing.value = false;
    }
}

// Handle drag events for image upload
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
        handleFilesSelected({ target: { files: e.dataTransfer.files } });
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
        // No longer need to call external API
        // await axios.get('/api/status');
        apiStatus.value = {
            ready: true,
            message: '',
            checked: true
        };
    } catch (error) {
        console.error('Error checking API status:', error);
        apiStatus.value = {
            ready: false,
            message: 'خدمة تحويل الصور غير متاحة حالياً',
            checked: true
        };
    }
});
</script>

<template>
    <Head title="تحويل صور JPG إلى PDF" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">تحويل صور JPG إلى PDF</h2>
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
                    <transition name="options">
                        <div v-if="processedFileUrl" class="text-center success-animation">
                            <div class="mb-6">
                                <div class="mx-auto w-16 h-16 bg-green-100 rounded-full flex items-center justify-center badge">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <h3 class="mt-4 text-xl font-medium text-gray-900">تم تحويل الصور إلى PDF بنجاح!</h3>
                                <p class="mt-1 text-gray-500">الملف جاهز للتنزيل الآن.</p>
                            </div>
                            
                            <div class="flex flex-col sm:flex-row justify-center space-y-3 sm:space-y-0 sm:space-x-4 sm:space-x-reverse">
                                <a 
                                    :href="processedFileUrl" 
                                    class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 btn-hover"
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
                                    class="inline-flex items-center justify-center px-5 py-3 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 btn-hover"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    بدء عملية جديدة
                                </button>
                            </div>
                        </div>
                    </transition>

                    <!-- Upload & Settings State -->
                    <div v-if="!processedFileUrl">
                        <div class="text-center mb-8">
                            <h3 class="text-lg font-medium text-gray-900 mb-2">تحويل صور JPG إلى PDF</h3>
                            <p class="text-gray-600">قم بتحميل صور بتنسيق JPG, PNG أو GIF وتحويلها إلى ملف PDF واحد.</p>
                        </div>

                        <!-- Image Drop Area -->
                        <div
                            class="mb-6 border-2 border-dashed rounded-lg p-10 text-center"
                            :class="{ 'border-blue-400 bg-blue-50': dragActive, 'border-gray-300': !dragActive }"
                            @dragenter="handleDragEnter"
                            @dragleave="handleDragLeave"
                            @dragover="handleDragOver"
                            @drop="handleDrop"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-gray-400 upload-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <p class="mt-1 text-sm text-gray-600">
                                اسحب وأفلت الصور هنا، أو
                                <label class="relative cursor-pointer text-blue-600 hover:text-blue-800 underline">
                                    <span>انقر للاختيار</span>
                                    <input type="file" class="sr-only" accept="image/*" multiple @change="handleFilesSelected" />
                                </label>
                            </p>
                            <p class="mt-1 text-xs text-gray-500">يمكنك تحميل ملفات صور JPG, PNG, GIF</p>
                        </div>

                        <!-- Uploaded Images List -->
                        <transition-group name="options" tag="div" class="mb-6" v-if="files.length > 0">
                            <h4 class="font-medium text-gray-900 mb-4" key="title">الصور المحددة:</h4>
                            
                            <div class="space-y-3" key="images-list">
                                <div v-for="(file, index) in files" :key="index" class="flex items-center bg-gray-50 p-3 rounded-md file-card">
                                    <div class="h-16 w-16 flex-shrink-0 bg-gray-200 rounded-md overflow-hidden mr-3">
                                        <img :src="file.preview" class="h-full w-full object-cover" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-gray-900 truncate">{{ file.name }}</p>
                                        <p class="text-sm text-gray-500">{{ file.size }}</p>
                                    </div>
                                    <div class="flex items-center space-x-2 space-x-reverse">
                                        <button 
                                            @click="moveFile(index, -1)" 
                                            class="p-1 rounded-full text-gray-600 hover:text-gray-900 hover:bg-gray-200 btn-hover"
                                            :disabled="index === 0"
                                            :class="{ 'opacity-50 cursor-not-allowed': index === 0 }"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                            </svg>
                                        </button>
                                        <button 
                                            @click="moveFile(index, 1)" 
                                            class="p-1 rounded-full text-gray-600 hover:text-gray-900 hover:bg-gray-200 btn-hover"
                                            :disabled="index === files.length - 1"
                                            :class="{ 'opacity-50 cursor-not-allowed': index === files.length - 1 }"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                        <button 
                                            @click="removeFile(index)" 
                                            class="p-1 rounded-full text-red-600 hover:text-red-900 hover:bg-red-100 btn-hover"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </transition-group>
                            
                        <div class="mt-3 flex justify-center" v-if="files.length > 0">
                            <label class="inline-flex items-center px-4 py-2 border border-blue-500 rounded-md font-medium text-blue-600 bg-white hover:bg-blue-50 cursor-pointer btn-hover">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                إضافة المزيد من الصور
                                <input type="file" class="sr-only" accept="image/*" multiple @change="handleFilesSelected" />
                            </label>
                        </div>
                    </div>

                    <!-- PDF Settings -->
                    <div class="mb-6">
                        <h4 class="font-medium text-gray-900 mb-4">إعدادات ملف PDF:</h4>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div>
                                <label for="pageSize" class="block text-sm font-medium text-gray-700 mb-1">حجم الصفحة</label>
                                <select 
                                    id="pageSize" 
                                    v-model="pageSize" 
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                >
                                    <option v-for="option in pageSizeOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="orientation" class="block text-sm font-medium text-gray-700 mb-1">اتجاه الصفحة</label>
                                <select 
                                    id="orientation" 
                                    v-model="orientation" 
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                >
                                    <option v-for="option in orientationOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="margin" class="block text-sm font-medium text-gray-700 mb-1">الهامش (مم)</label>
                                <input 
                                    type="number" 
                                    id="margin" 
                                    v-model="margin" 
                                    min="0" 
                                    max="50" 
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                />
                            </div>
                        </div>
                    </div>
                    
                    <!-- Process Button -->
                    <div class="flex justify-center mt-6">
                        <button 
                            @click="processPdf" 
                            class="px-6 py-3 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 btn-hover"
                            :disabled="isProcessing || files.length === 0 || !apiStatus.ready || !hasActiveSubscription"
                        >
                            <span v-if="isProcessing" class="flex items-center">
                                <svg class="animate-spin h-5 w-5 ml-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                جاري المعالجة...
                            </span>
                            <span v-else>تحويل إلى PDF</span>
                        </button>
                    </div>
                </div>
                
                <!-- Instructions -->
                <div class="mt-8 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">كيفية تحويل صور JPG إلى PDF</h3>
                    <div class="grid md:grid-cols-4 gap-6">
                        <div class="flex flex-col items-center text-center">
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mb-4 badge">
                                <span class="text-blue-600 font-bold">1</span>
                            </div>
                            <h4 class="font-medium mb-2">اختر الصور</h4>
                            <p class="text-gray-600 text-sm">قم بتحميل صور JPG أو PNG أو GIF التي ترغب في تحويلها.</p>
                        </div>
                        <div class="flex flex-col items-center text-center">
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mb-4 badge">
                                <span class="text-blue-600 font-bold">2</span>
                            </div>
                            <h4 class="font-medium mb-2">إعادة ترتيب</h4>
                            <p class="text-gray-600 text-sm">أعد ترتيب الصور عن طريق النقر على أسهم الأعلى والأسفل.</p>
                        </div>
                        <div class="flex flex-col items-center text-center">
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mb-4 badge">
                                <span class="text-blue-600 font-bold">3</span>
                            </div>
                            <h4 class="font-medium mb-2">ضبط الإعدادات</h4>
                            <p class="text-gray-600 text-sm">اختر حجم الصفحة واتجاهها والهوامش.</p>
                        </div>
                        <div class="flex flex-col items-center text-center">
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mb-4 badge">
                                <span class="text-blue-600 font-bold">4</span>
                            </div>
                            <h4 class="font-medium mb-2">تحويل وتنزيل</h4>
                            <p class="text-gray-600 text-sm">اضغط على زر التحويل وقم بتنزيل ملف PDF الناتج.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template> 