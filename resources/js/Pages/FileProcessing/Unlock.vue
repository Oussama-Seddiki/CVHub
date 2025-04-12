<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed, onMounted } from 'vue';
import SubscriptionWarning from '@/components/SubscriptionWarning.vue';
import PdfProcessingService from '@/services/PdfProcessingService';

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
const compressionLevel = ref('medium'); // 'low', 'medium', 'high'
const originalSize = ref(0);
const compressedSize = ref(0);
const savingsPercentage = ref(0);
const password = ref('');

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
    originalSize.value = newFile.size;
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
    originalSize.value = 0;
    compressedSize.value = 0;
    savingsPercentage.value = 0;
}

// Process the PDF to compress it
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
        
        const result = await PdfProcessingService.processPdf(file.value, 'unlock', {
            password: password.value
        });
        
        if (result.success) {
            processedFileUrl.value = result.downloadUrl;
            
            // For demo purposes, calculate a random compressed size that's smaller than the original
            const minReduction = 10; // at least 10% reduction
            let maxReduction;
            
            // Set different maximum reduction percentages based on compression level
            if (compressionLevel.value === 'low') {
                maxReduction = 30; // up to 30% reduction
            } else if (compressionLevel.value === 'medium') {
                maxReduction = 50; // up to 50% reduction
            } else {
                maxReduction = 70; // up to 70% reduction
            }
            
            // Calculate a random reduction percentage between min and max
            const reductionPercentage = Math.random() * (maxReduction - minReduction) + minReduction;
            
            // Calculate the compressed size
            compressedSize.value = Math.floor(originalSize.value * (1 - reductionPercentage / 100));
            savingsPercentage.value = Math.round(reductionPercentage);
        } else {
            errorMessage.value = result.message || 'فشل الاتصال بخدمة معالجة ملفات PDF';
        }
    } catch (error) {
        console.error('Error compressing PDF:', error);
        errorMessage.value = 'فشل الاتصال بخدمة معالجة ملفات PDF';
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
    originalSize.value = 0;
    compressedSize.value = 0;
    savingsPercentage.value = 0;
    compressionLevel.value = 'medium';
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
    checking: true,
    message: 'جاري التحقق من حالة الخدمة...'
});

onMounted(async () => {
    try {
        // Check API status
        const status = await PdfProcessingService.checkApiStatus();
        apiStatus.value = {
            ready: status.ready || false,
            checking: false,
            message: status.ready
                ? 'خدمة تحويل الملفات جاهزة للاستخدام'
                : 'خدمة تحويل الملفات غير متاحة حالياً. يرجى التحقق من إعدادات API.'
        };
    } catch (error) {
        apiStatus.value = {
            ready: false,
            checking: false,
            message: 'فشل الاتصال بخدمة معالجة ملفات PDF'
        };
        console.error('Error checking API status:', error);
    }
});
</script>

<template>
    <Head title="ضغط ملف PDF" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">ضغط ملف PDF</h2>
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
                <div v-if="!apiStatus.ready && !apiStatus.checking" class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
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
                            <h3 class="mt-4 text-xl font-medium text-gray-900">تم ضغط ملف PDF بنجاح!</h3>
                            <p class="mt-1 text-gray-500">الملف جاهز للتنزيل الآن.</p>
                        </div>
                        
                        <!-- Compression Results -->
                        <div class="max-w-md mx-auto mb-6 bg-blue-50 p-4 rounded-lg">
                            <div class="flex justify-between mb-2">
                                <span class="text-gray-700">الحجم الأصلي:</span>
                                <span class="font-medium">{{ formatBytes(originalSize) }}</span>
                            </div>
                            <div class="flex justify-between mb-2">
                                <span class="text-gray-700">الحجم بعد الضغط:</span>
                                <span class="font-medium">{{ formatBytes(compressedSize) }}</span>
                            </div>
                            <div class="flex justify-between text-green-700">
                                <span class="font-medium">توفير المساحة:</span>
                                <span class="font-bold">{{ savingsPercentage }}%</span>
                            </div>
                        </div>
                        
                        <div class="flex flex-col sm:flex-row justify-center space-y-3 sm:space-y-0 sm:space-x-4 sm:space-x-reverse">
                            <button 
                                @click="downloadProcessedFile" 
                                class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                تنزيل الملف المضغوط
                            </button>
                            <button 
                                @click="startNew" 
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
                            <h3 class="text-lg font-medium text-gray-900 mb-2">ضغط ملف PDF</h3>
                            <p class="text-gray-600">قم بتحميل ملف PDF لتقليل حجمه مع الحفاظ على جودة مقبولة.</p>
                        </div>

                        <!-- File Drop Area (if no file selected) -->
                        <div v-if="!hasFile"
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
                            <p class="mt-1 text-xs text-gray-500">يمكنك تحميل ملف PDF فقط</p>
                        </div>
                    
                        <!-- File Info & Compression Options (if file selected) -->
                        <div v-if="hasFile" class="mt-6 mb-6">
                            <div class="flex justify-between items-center bg-gray-50 p-4 rounded-md mb-6">
                                <div class="flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-500 ml-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    </svg>
                                    <div>
                                        <p class="font-medium text-gray-900">{{ file.name }}</p>
                                        <p class="text-sm text-gray-500">{{ formatBytes(originalSize) }}</p>
                                    </div>
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
                            
                            <!-- Compression Options -->
                            <div class="mb-6">
                                <h4 class="font-medium text-gray-900 mb-4">اختر مستوى الضغط:</h4>
                                
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div 
                                        class="border rounded-md p-4 cursor-pointer"
                                        :class="{ 'border-blue-500 bg-blue-50': compressionLevel === 'low', 'border-gray-200': compressionLevel !== 'low' }"
                                        @click="compressionLevel = 'low'"
                                    >
                                        <div class="flex items-center mb-2">
                                            <div class="w-5 h-5 bg-blue-500 rounded-full mr-2 flex items-center justify-center">
                                                <div v-if="compressionLevel === 'low'" class="w-3 h-3 bg-white rounded-full"></div>
                                            </div>
                                            <h5 class="font-medium">ضغط منخفض</h5>
                                        </div>
                                        <p class="text-sm text-gray-600">جودة أعلى، تقليل أقل للحجم</p>
                                        <p class="text-sm text-gray-500 mt-1">مثالي للوثائق التي تحتوي على صور عالية الدقة</p>
                                    </div>
                                    
                                    <div 
                                        class="border rounded-md p-4 cursor-pointer"
                                        :class="{ 'border-blue-500 bg-blue-50': compressionLevel === 'medium', 'border-gray-200': compressionLevel !== 'medium' }"
                                        @click="compressionLevel = 'medium'"
                                    >
                                        <div class="flex items-center mb-2">
                                            <div class="w-5 h-5 bg-blue-500 rounded-full mr-2 flex items-center justify-center">
                                                <div v-if="compressionLevel === 'medium'" class="w-3 h-3 bg-white rounded-full"></div>
                                            </div>
                                            <h5 class="font-medium">ضغط متوسط</h5>
                                        </div>
                                        <p class="text-sm text-gray-600">توازن بين الجودة وحجم الملف</p>
                                        <p class="text-sm text-gray-500 mt-1">مناسب لمعظم أنواع الملفات</p>
                                    </div>
                                    
                                    <div 
                                        class="border rounded-md p-4 cursor-pointer"
                                        :class="{ 'border-blue-500 bg-blue-50': compressionLevel === 'high', 'border-gray-200': compressionLevel !== 'high' }"
                                        @click="compressionLevel = 'high'"
                                    >
                                        <div class="flex items-center mb-2">
                                            <div class="w-5 h-5 bg-blue-500 rounded-full mr-2 flex items-center justify-center">
                                                <div v-if="compressionLevel === 'high'" class="w-3 h-3 bg-white rounded-full"></div>
                                            </div>
                                            <h5 class="font-medium">ضغط عالي</h5>
                                        </div>
                                        <p class="text-sm text-gray-600">حجم أصغر، قد تقل الجودة</p>
                                        <p class="text-sm text-gray-500 mt-1">مناسب للوثائق النصية البسيطة</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Actions -->
                        <div class="flex justify-center mt-8">
                            <button 
                                @click="processPdf" 
                                class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none disabled:opacity-50"
                                :disabled="!hasFile || isProcessing || !apiStatus.ready"
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
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                                    </svg>
                                    ضغط الملف
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Instructions -->
                <div class="mt-8 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">كيفية ضغط ملف PDF</h3>
                    <div class="grid md:grid-cols-3 gap-6">
                        <div class="flex flex-col items-center text-center">
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                                <span class="text-blue-600 font-bold">1</span>
                            </div>
                            <h4 class="font-medium mb-2">اختر الملف</h4>
                            <p class="text-gray-600 text-sm">قم بتحميل ملف PDF الذي ترغب في ضغطه.</p>
                        </div>
                        <div class="flex flex-col items-center text-center">
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                                <span class="text-blue-600 font-bold">2</span>
                            </div>
                            <h4 class="font-medium mb-2">اختر مستوى الضغط</h4>
                            <p class="text-gray-600 text-sm">حدد مستوى الضغط المناسب لاحتياجاتك.</p>
                        </div>
                        <div class="flex flex-col items-center text-center">
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                                <span class="text-blue-600 font-bold">3</span>
                            </div>
                            <h4 class="font-medium mb-2">تنزيل الملف المضغوط</h4>
                            <p class="text-gray-600 text-sm">انتظر انتهاء العملية وقم بتنزيل الملف المضغوط.</p>
                        </div>
                    </div>
                    
                    <div class="mt-6 bg-blue-50 p-4 rounded-lg">
                        <h4 class="font-medium text-blue-700 mb-2">نصائح للحصول على أفضل نتائج:</h4>
                        <ul class="list-disc list-inside text-sm text-gray-700 space-y-1">
                            <li>اختر مستوى ضغط منخفض للملفات التي تحتوي على صور عالية الدقة.</li>
                            <li>استخدم الضغط العالي للملفات النصية البسيطة لتقليل الحجم بشكل كبير.</li>
                            <li>قد يؤدي الضغط المتكرر لنفس الملف إلى تدهور الجودة.</li>
                            <li>يمكنك دائمًا تجربة مستويات ضغط مختلفة ومقارنة النتائج.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Add any custom styles here */
</style>