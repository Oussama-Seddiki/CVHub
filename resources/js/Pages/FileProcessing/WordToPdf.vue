<template>
    <AuthenticatedLayout title="تحويل Word إلى PDF">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                تحويل Word إلى PDF
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
                    <div v-if="!processing && !processingConversion && !downloadUrl">
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
                            <transition name="file-drop">
                            <div v-if="!selectedFile">
                                <svg class="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                    قم بسحب ملف Word هنا أو 
                                        <span @click="openFileDialog" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 cursor-pointer underline">
                                        اضغط للاختيار
                                    </span>
                                </p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-500">DOCX, DOC (الحد الأقصى 20 ميجابايت)</p>
                                <input type="file" ref="fileInput" class="hidden" @change="handleFileChange" accept=".docx,.doc" />
                            </div>
                            </transition>
                            <div v-if="selectedFile" class="text-right">
                                <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-700 p-3 rounded-md file-card">
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
                            </div>
                        </div>

                        <!-- خيارات التحويل -->
                        <transition name="options">
                        <div v-if="selectedFile" class="mt-6">
                            <h3 class="text-md font-medium text-gray-900 dark:text-gray-100 mb-4">خيارات التحويل</h3>
                            
                            <!-- نص توضيحي لخيار OCR -->
                            <div class="mt-2 p-3 bg-blue-50 dark:bg-blue-900 rounded-md text-sm">
                                <p class="text-blue-800 dark:text-blue-200">
                                    استخدم خيار <span class="font-bold">OCR</span> للحصول على أفضل نتائج للمستندات المعقدة
                                    التي تحتوي على جداول وتنسيقات خاصة. هذه الميزة تحسن المستند بعد التحويل
                                    للحفاظ على جميع العناصر بشكل أفضل.
                                </p>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">جودة التحويل</label>
                                    <div class="flex flex-col space-y-2">
                                        <div class="relative inline-block w-full">
                                            <div class="flex items-center justify-between bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 cursor-pointer"
                                                 @click="showQualityDropdown = !showQualityDropdown">
                                                <span>{{ getQualityText(quality) }}</span>
                                                <ChevronDownIcon class="h-5 w-5 text-gray-400" />
                                            </div>
                                            
                                                <transition name="dropdown">
                                            <div v-if="showQualityDropdown"
                                                 class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg">
                                                <div class="py-1">
                                                    <div v-for="option in qualityOptions" :key="option.value"
                                                         class="px-3 py-2 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700"
                                                         @click="setQuality(option.value); showQualityDropdown = false">
                                                        {{ option.label }}
                                                    </div>
                                                </div>
                                            </div>
                                                </transition>
                                        </div>
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">حفظ التنسيق</label>
                                    <div class="relative inline-block w-full">
                                        <label class="inline-flex items-center">
                                            <input type="checkbox" id="preserve_formatting" v-model="preserveFormatting"
                                                   class="rounded text-indigo-600 focus:ring-indigo-500 rtl:ml-3 ltr:mr-3"
                                                       :disabled="apiStatus.value?.checking || !apiStatus.value?.support?.quality_settings">
                                            <span class="mr-2 text-sm text-gray-700 dark:text-gray-300">الاحتفاظ بالتنسيق والخطوط</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- استخدام OCR لتحسين التحويل -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">تحسين النص والجداول</label>
                                <div class="flex items-center">
                                    <input type="checkbox" id="use_ocr" v-model="useOcr"
                                               class="rounded text-indigo-600 focus:ring-indigo-500 rtl:ml-3 ltr:mr-3">
                                    <label for="use_ocr" class="text-sm text-gray-700 dark:text-gray-300">
                                        استخدام OCR لتحسين التنسيق والجداول المعقدة
                                    </label>
                                    
                                    <div class="ltr:ml-1 rtl:mr-1">
                                        <Tooltip content="يستخدم تقنية التعرف الضوئي على النصوص (OCR) لتحسين تحويل المستندات المعقدة والمحافظة على تنسيق الجداول بشكل أفضل">
                                            <QuestionMarkCircleIcon class="w-4 h-4 text-gray-400" />
                                        </Tooltip>
                                    </div>
                                </div>
                                
                                <!-- تحذير زمن المعالجة -->
                                <div v-if="useOcr" class="mt-2 text-xs text-amber-600 dark:text-amber-400">
                                    <InformationCircleIcon class="inline-block w-4 h-4 mr-1" />
                                    قد تستغرق معالجة OCR وقتاً أطول. يرجى الانتظار حتى اكتمال العملية.
                                </div>
                            </div>
                            
                            <!-- خيارات متقدمة -->
                            <div class="mt-4 mb-6">
                                <details class="border border-gray-200 dark:border-gray-700 rounded-md p-2">
                                    <summary class="font-medium text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                                        خيارات متقدمة
                                    </summary>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-3 p-2">
                                        <!-- اتجاه الصفحة -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">اتجاه الصفحة</label>
                                            <select v-model="pageOrientation" 
                                                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                                <option value="default">الافتراضي (حسب المستند الأصلي)</option>
                                                <option value="portrait">عمودي (Portrait)</option>
                                                <option value="landscape">أفقي (Landscape)</option>
                                            </select>
                                        </div>
                                        
                                        <!-- حجم الصفحة -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">حجم الصفحة</label>
                                            <select v-model="pageSize" 
                                                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                                <option value="default">الافتراضي (حسب المستند الأصلي)</option>
                                                <option value="a4">A4</option>
                                                <option value="letter">Letter</option>
                                                <option value="legal">Legal</option>
                                                <option value="a3">A3</option>
                                                <option value="a5">A5</option>
                                            </select>
                                        </div>
                                        
                                        <!-- هوامش الصفحة -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">هوامش الصفحة</label>
                                            <select v-model="margins" 
                                                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-600 dark:bg-gray-700 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                                <option value="default">الافتراضي (حسب المستند الأصلي)</option>
                                                <option value="narrow">ضيق (0.5 بوصة)</option>
                                                <option value="normal">عادي (1 بوصة)</option>
                                                <option value="wide">واسع (2 بوصة)</option>
                                            </select>
                                        </div>
                                        
                                        <!-- تحسين للطباعة -->
                                        <div>
                                            <div class="flex items-center mt-4">
                                                <input type="checkbox" 
                                                       id="optimizeForPrinting" 
                                                       v-model="optimizeForPrinting" 
                                                           class="form-checkbox h-5 w-5 text-indigo-600 dark:text-indigo-400 rounded">
                                                <label for="optimizeForPrinting" class="mr-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                                    تحسين للطباعة
                                                </label>
                                            </div>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mr-7 mt-1">تحسين المستند للحصول على جودة طباعة أفضل</p>
                                        </div>
                                    </div>
                                </details>
                            </div>

                            <button 
                                @click="processConversion" 
                                    :disabled="!selectedFile || processing || processingConversion || apiStatus.value?.checking" 
                                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed btn-hover"
                            >
                                    <svg v-if="apiStatus.value?.checking || processing || processingConversion" class="animate-spin ml-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                    <span>{{ processing || processingConversion ? 'جاري التحويل...' : 'تحويل إلى PDF' }}</span>
                            </button>
                        </div>
                        </transition>
                    </div>

                    <!-- انتظار المعالجة -->
                    <transition name="options">
                        <div v-if="processing || processingConversion" class="mt-6">
                        <div class="flex items-center justify-center">
                            <div class="text-center">
                                <div class="flex items-center justify-center mb-4">
                                        <ClockIcon v-if="useOcr" class="h-12 w-12 text-indigo-500 ocr-pulse" />
                                    <svg v-else class="animate-spin h-10 w-10 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                                <p class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                    {{ useOcr ? 'جاري معالجة الملف باستخدام OCR...' : 'جاري تحويل الملف...' }}
                                </p>
                                <p v-if="useOcr" class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                    قد تستغرق المعالجة مع تقنية OCR من 3-10 دقائق حسب حجم وتعقيد الملف.
                                </p>
                                <div class="mt-4 w-full h-2 bg-gray-200 rounded-full overflow-hidden">
                                    <div class="h-full bg-indigo-500 rounded-full progress-animation"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    </transition>

                    <!-- نتيجة التحويل -->
                    <transition name="options">
                        <div v-if="downloadUrl" class="text-center success-animation">
                        <div class="rounded-md bg-green-50 dark:bg-green-900/20 p-4 mb-6">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414-1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
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
                                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 btn-hover"
                        >
                            <svg class="ml-2 -mr-1 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            تنزيل الملف
                        </a>

                            <!-- OCR status indicator -->
                            <div v-if="conversionResponse" class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/30 rounded-md">
                                <h4 class="font-medium text-blue-800 dark:text-blue-300">تفاصيل التحويل:</h4>
                                <div class="mt-2 text-sm text-blue-700 dark:text-blue-400">
                                    <div v-if="conversionResponse.details?.ocr_applied || conversionResponse.options_used?.use_ocr">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            تم استخدام OCR
                                        </span>
                                    </div>
                                    <div v-else-if="useOcr" class="mt-1">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            تم طلب OCR ولكن لم يتم استخدامه
                                        </span>
                                    </div>
                                    <div class="mt-2" v-if="conversionResponse.details">
                                        <p>طريقة التحويل: {{ conversionResponse.details.conversion_method }}</p>
                                        <p v-if="conversionResponse.details.ocr_applied">تم تطبيق OCR: نعم</p>
                                    </div>
                                </div>
                            </div>

                        <button 
                            @click="reset" 
                                class="mt-4 inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 btn-hover"
                        >
                            تحويل ملف آخر
                        </button>
                    </div>
                    </transition>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, reactive, onMounted, computed, onUnmounted } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import WordToPdfService from '@/services/WordToPdfService';
import axios from 'axios';
import { useForm } from '@inertiajs/vue3';
import Tooltip from '@/components/Tooltip.vue';
import { 
    ChevronDownIcon, 
    QuestionMarkCircleIcon, 
    InformationCircleIcon,
    ClockIcon,
    DocumentTextIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon
} from '@heroicons/vue/24/solid';

// Import shared animations CSS
import '@/css/file-processing-animations.css';

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
const processingConversion = ref(false);
const error = ref(null);
const quality = ref('standard');
const preserveFormatting = ref(true);
const pageOrientation = ref('default');
const pageSize = ref('default');
const margins = ref('default');
const optimizeForPrinting = ref(false);
const convertedFilename = ref('');
const conversionSuccess = ref(false);
const downloadUrl = ref(null);
const useOcr = ref(false);
const showQualityDropdown = ref(false);
const conversionResponse = ref(null);

// API status check
const apiStatus = ref({
    ready: false,
    error: null,
    checking: true,
    support: {
        base_conversion: false,
        quality_settings: false,
        preserve_formatting: false,
        page_orientation: false,
        page_size: false,
        margins: false,
        optimize_for_printing: false,
        ocr_support: false
    }
});

// تعريف خيارات الجودة
const qualityOptions = [
    { value: 'standard', label: 'قياسية' },
    { value: 'high', label: 'عالية' },
    { value: 'very_high', label: 'عالية جداً' }
];

// دالة للحصول على نص الجودة المناسب
const getQualityText = (qualityValue) => {
    const option = qualityOptions.find(option => option.value === qualityValue);
    return option ? option.label : 'قياسية';
};

// دالة لتحديد الجودة
const setQuality = (value) => {
    quality.value = value;
};

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
        if (!file.name.endsWith('.docx') && !file.name.endsWith('.doc')) {
            error.value = 'يرجى اختيار ملف Word بتنسيق DOCX أو DOC.';
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
        if (!file.name.endsWith('.docx') && !file.name.endsWith('.doc')) {
            error.value = 'يرجى اختيار ملف Word بتنسيق DOCX أو DOC.';
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

const reset = () => {
    selectedFile.value = null;
    downloadUrl.value = '';
    error.value = '';
    processing.value = false;
    processingConversion.value = false;
    conversionResponse.value = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
    quality.value = 'standard';
    preserveFormatting.value = true;
    pageOrientation.value = 'default';
    pageSize.value = 'default';
    margins.value = 'default';
    optimizeForPrinting.value = false;
    useOcr.value = false;
    showQualityDropdown.value = false;
};

/**
 * Process the selected file for conversion
 */
const processConversion = async () => {
    if (!selectedFile.value || processing.value) {
        return;
    }
    
    processing.value = true;
    processingConversion.value = true;
    error.value = null;
    
    try {
        // Create options object with all conversion settings
        const options = {
            quality: quality.value,
            preserveFormatting: preserveFormatting.value,
            orientation: pageOrientation.value,
            pageSize: pageSize.value,
            margins: margins.value,
            optimizeForPrinting: optimizeForPrinting.value,
            useOcr: useOcr.value,
            ocrLanguage: 'eng+ara' // Explicitly set OCR to support both English and Arabic
        };
        
        // Debug logs for OCR settings
        console.log('OCR enabled:', useOcr.value);
        console.log('OCR support detected:', apiStatus.value?.support?.ocr_support);
        console.log('OCR language:', options.ocrLanguage);
        console.log('Full conversion options:', options);
        
        console.log('Converting Word document with options:', options);
        const response = await WordToPdfService.convertWordToPdf(selectedFile.value, options);
        
        console.log('Conversion response:', response);
        
        if (response.success) {
            conversionSuccess.value = true;
            downloadUrl.value = response.file;
            conversionResponse.value = response;
            
            console.log('Conversion completed with options:', options);
            console.log('Options used by backend:', response.options_used || {});
        } else {
            error.value = response.message || 'فشل تحويل ملف Word إلى PDF';
            console.error('Conversion failed:', response.message);
            if (response.errors) {
                console.error('Detailed errors:', response.errors);
            }
        }
    } catch (error) {
        error.value = error.message || 'حدث خطأ أثناء تحويل ملف Word إلى PDF';
        console.error('Error in conversion process:', error);
    } finally {
        processing.value = false;
        processingConversion.value = false;
    }
};

/**
 * Check if Word to PDF conversion is supported with all options
 */
const checkWordToPdfSupport = async () => {
    apiStatus.value.support.checking = true;
    
    try {
        const response = await WordToPdfService.checkWordToPdfSupport();
        console.log('Word to PDF support check:', response);
        
        if (response.success) {
            // Update support flags
            apiStatus.value.support.base_conversion = response.features?.base_conversion ?? false;
            // Force enable these options regardless of backend detection
            apiStatus.value.support.quality_settings = true; // Force enable
            apiStatus.value.support.preserve_formatting = true; // Force enable
            apiStatus.value.support.page_orientation = true; // Force enable
            apiStatus.value.support.page_size = true; // Force enable
            apiStatus.value.support.margins = true; // Force enable
            apiStatus.value.support.optimize_for_printing = true; // Force enable
            apiStatus.value.support.ocr_support = true; // Force enable OCR
            
            console.log('Forced OCR and advanced features to be enabled');
            
            // Log diagnostics
            if (response.diagnostics) {
                console.log('Feature diagnostics:', response.diagnostics);
                // Check if Tesseract is found but paths are incorrect
                if (response.diagnostics.tesseract_path) {
                    console.log('Tesseract is installed at:', response.diagnostics.tesseract_path);
                }
                if (response.diagnostics.ghostscript_path) {
                    console.log('Ghostscript is installed at:', response.diagnostics.ghostscript_path);
                }
            }
        } else {
            console.error('Failed to check Word to PDF support:', response.message);
            apiStatus.value.support.base_conversion = false;
            
            // Still force enable features for testing
            apiStatus.value.support.quality_settings = true;
            apiStatus.value.support.preserve_formatting = true;
            apiStatus.value.support.page_orientation = true;
            apiStatus.value.support.page_size = true;
            apiStatus.value.support.margins = true;
            apiStatus.value.support.optimize_for_printing = true;
            apiStatus.value.support.ocr_support = true;
        }
    } catch (error) {
        console.error('Error checking Word to PDF support:', error);
        apiStatus.value.support.base_conversion = false;
        
        // Still force enable features for testing
        apiStatus.value.support.quality_settings = true;
        apiStatus.value.support.preserve_formatting = true;
        apiStatus.value.support.page_orientation = true;
        apiStatus.value.support.page_size = true;
        apiStatus.value.support.margins = true;
        apiStatus.value.support.optimize_for_printing = true;
        apiStatus.value.support.ocr_support = true;
    } finally {
        apiStatus.value.support.checking = false;
    }
};

onMounted(async () => {
    apiStatus.value.checking = true;
    try {
        // No longer need to call external API
        // await axios.get('/api/status');
        apiStatus.value.ready = true;
        
        // Check for specific Word to PDF support
        await checkWordToPdfSupport();
    } catch (err) {
        apiStatus.value.ready = false;
        apiStatus.value.error = 'خدمة تحويل المستندات غير متاحة حالياً';
        console.error('API Status Check Error:', err);
    } finally {
        apiStatus.value.checking = false;
    }
});
</script>

<style>
/* Styles moved to shared file resources/js/css/file-processing-animations.css */
</style>