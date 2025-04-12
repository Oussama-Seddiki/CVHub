<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed, onMounted } from 'vue';
import SubscriptionWarning from '@/components/SubscriptionWarning.vue';
import PdfMergeService from '@/services/PdfMergeService';

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
const isUploading = ref(false);
const isProcessing = ref(false);
const processedFileUrl = ref(null);
const errorMessage = ref('');
const dragActive = ref(false);

// Use the activeSubscription prop directly
const hasActiveSubscription = computed(() => props.activeSubscription);

// Check if there are files uploaded
const hasFiles = computed(() => files.value.length > 0);

// Add files to the list
async function addFiles(newFiles) {
    // Check if active subscription
    if (!hasActiveSubscription.value) {
        errorMessage.value = 'هذه الخدمة متاحة فقط للمستخدمين المشتركين. يرجى الاشتراك للاستفادة من هذه الميزة.';
        return;
    }
    
    for (let i = 0; i < newFiles.length; i++) {
        let file = newFiles[i];
        
        // Try to debug file to see what we're dealing with
        await debugFileContent(file, i);
        
        // Check if it's a processed file URL or object
        if (typeof file === 'string' || (file && file.file && typeof file.file === 'string')) {
            // Add the URL or object to our files array
            if (!files.value.some(f => {
                if (typeof f === 'string') return f === file;
                if (f.file) return f.file === file.file;
                return false;
            })) {
                console.log('Adding URL or object to files list');
                files.value.push(file);
            }
            continue;
        }
        
        // Special handling for problematic files
        if (file instanceof Blob) {
            try {
                // Check if it's a JSON blob that contains a PDF URL
                const text = await file.text();
                if (text.trim().startsWith('{') && text.includes('"success"') && text.includes('"file"')) {
                    try {
                        const jsonObj = JSON.parse(text);
                        if (jsonObj.success && jsonObj.file && typeof jsonObj.file === 'string') {
                            console.log('Converting Blob with JSON to URL:', jsonObj.file);
                            // Replace the Blob with just the URL string
                            file = jsonObj.file;
                            
                            // Add it to files if not already there
                            if (!files.value.some(f => typeof f === 'string' && f === file)) {
                                files.value.push(file);
                            }
                            continue;
                        }
                    } catch (e) {
                        console.warn('Failed to parse JSON in Blob:', e);
                    }
                }
            } catch (e) {
                console.warn('Failed to read Blob as text:', e);
            }
        }
        
        // Check if the file is a PDF
        if (file.type !== 'application/pdf') {
            errorMessage.value = 'يمكنك فقط إضافة ملفات PDF.';
            continue;
        }
        
        // Add file to the array if not already included
        if (!files.value.some(f => {
            if (f instanceof File) return f.name === file.name && f.size === file.size;
            return false;
        })) {
            console.log('Adding File to files list:', file.name);
            files.value.push(file);
        }
    }
    
    // Clear error message if files were added successfully
    if (files.value.length > 0) {
        errorMessage.value = '';
    }
}

// Debug file content to help identify issues
async function debugFileContent(file, index) {
    console.log(`Debugging file ${index}:`, file);
    
    // String (URL)
    if (typeof file === 'string') {
        console.log(`File ${index} is a string URL:`, file);
        return;
    }
    
    // Response object with file property
    if (file && typeof file === 'object' && file.file) {
        console.log(`File ${index} is an object with file property:`, file.file);
        return;
    }
    
    // Not a Blob or File
    if (!(file instanceof Blob)) {
        console.log(`File ${index} is not a Blob/File:`, typeof file, file);
        return;
    }
    
    // Log file metadata
    console.log(`File ${index} metadata:`, {
        name: file.name,
        type: file.type,
        size: file.size,
        lastModified: file instanceof File ? new Date(file.lastModified).toISOString() : 'N/A'
    });
    
    // Try to read as text to identify content
    if (file.size > 0) {
        try {
            // Read the first 200 characters to identify content type
            const reader = new FileReader();
            reader.onload = (e) => {
                const text = e.target.result;
                const preview = text.substring(0, 200);
                console.log(`File ${index} content preview:`, preview);
                
                // Try to identify content
                if (preview.startsWith('%PDF-')) {
                    console.log(`File ${index} appears to be a valid PDF`);
                } else if (preview.startsWith('{') || preview.startsWith('[')) {
                    console.log(`File ${index} appears to be JSON`);
                    try {
                        const jsonObj = JSON.parse(text);
                        console.log(`File ${index} parsed JSON:`, jsonObj);
                    } catch (e) {
                        console.warn(`File ${index} contains invalid JSON:`, e);
                    }
                } else {
                    console.log(`File ${index} is of unknown format`);
                }
            };
            reader.readAsText(file.slice(0, 500)); // Read only the first 500 bytes
        } catch (e) {
            console.warn(`Failed to read file ${index} content:`, e);
        }
    }
}

// Handle file selection
async function handleFileSelect(event) {
    if (event.target.files.length) {
        // Try to check if files might be result objects
        const processedFiles = await Promise.all(Array.from(event.target.files).map(async (file) => {
            // Check if it's a JSON file or text file that might contain a PDF URL
            if (file.type === 'application/json' || file.type === 'text/plain' || file.name.endsWith('.json')) {
                try {
                    const text = await file.text();
                    if (text.trim().startsWith('{') && text.includes('"success"')) {
                        const jsonObj = JSON.parse(text);
                        if (jsonObj.success && jsonObj.file && typeof jsonObj.file === 'string') {
                            console.log('Detected JSON result with PDF URL:', jsonObj.file);
                            return jsonObj;
                        }
                    }
                } catch (e) {
                    console.warn('Failed to parse potential JSON file:', e);
                }
            }
            return file;
        }));
        
        addFiles(processedFiles);
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

async function handleDrop(e) {
    e.preventDefault();
    e.stopPropagation();
    dragActive.value = false;
    
    if (e.dataTransfer.files.length) {
        // Try to check if files might be result objects
        const processedFiles = await Promise.all(Array.from(e.dataTransfer.files).map(async (file) => {
            // Check if it's a JSON file or text file that might contain a PDF URL
            if (file.type === 'application/json' || file.type === 'text/plain' || file.name.endsWith('.json')) {
                try {
                    const text = await file.text();
                    if (text.trim().startsWith('{') && text.includes('"success"')) {
                        const jsonObj = JSON.parse(text);
                        if (jsonObj.success && jsonObj.file && typeof jsonObj.file === 'string') {
                            console.log('Detected JSON result with PDF URL:', jsonObj.file);
                            return jsonObj;
                        }
                    }
                } catch (e) {
                    console.warn('Failed to parse potential JSON file:', e);
                }
            }
            return file;
        }));
        
        addFiles(processedFiles);
    }
}

// Remove a file from the list
function removeFile(index) {
    files.value.splice(index, 1);
}

// Move file up in the list
function moveFileUp(index) {
    if (index > 0) {
        const temp = files.value[index];
        files.value[index] = files.value[index - 1];
        files.value[index - 1] = temp;
    }
}

// Move file down in the list
function moveFileDown(index) {
    if (index < files.value.length - 1) {
        const temp = files.value[index];
        files.value[index] = files.value[index + 1];
        files.value[index + 1] = temp;
    }
}

// Merge PDF files
async function mergePdfs() {
    if (!hasActiveSubscription.value) {
        errorMessage.value = 'هذه الخدمة متاحة فقط للمستخدمين المشتركين. يرجى الاشتراك للاستفادة من هذه الميزة.';
        return;
    }
    
    if (files.value.length < 2) {
        errorMessage.value = 'يرجى إضافة ملفين PDF على الأقل للدمج.';
        return;
    }
    
    try {
        isProcessing.value = true;
        errorMessage.value = '';
        
        // Process file list to handle any result objects from previous operations
        const processedFiles = await Promise.all(files.value.map(async (file, index) => {
            // Debug the file
            console.log(`Pre-processing file ${index} for merge:`, file);
            
            // If it's a string (URL), return it directly
            if (typeof file === 'string') {
                console.log(`Item ${index} is a URL string:`, file);
                return file;
            }
            
            // If it's an object with a "file" property, extract the URL
            if (file && typeof file === 'object' && file.file) {
                if (typeof file.file === 'string') {
                    console.log(`Item ${index} is a result object with file URL:`, file.file);
                    return file.file;
                }
                return file;
            }
            
            // If it's a Blob that might be a JSON string
            if (file instanceof Blob) {
                try {
                    // Try to read as text
                    console.log(`Checking if Blob ${index} contains JSON...`);
                    const text = await new Promise((resolve, reject) => {
                        const reader = new FileReader();
                        reader.onload = e => resolve(e.target.result);
                        reader.onerror = e => reject(e);
                        reader.readAsText(file);
                    });
                    
                    // Check if it looks like JSON
                    if (text.trim().startsWith('{')) {
                        console.log(`Item ${index} appears to be JSON:`, text.substring(0, 100) + '...');
                        
                        // Try to parse it
                        try {
                            const jsonObj = JSON.parse(text);
                            console.log(`Successfully parsed JSON from Blob ${index}:`, jsonObj);
                            
                            // If it has a file URL, return that
                            if (jsonObj.success && jsonObj.file && typeof jsonObj.file === 'string') {
                                console.log(`Extracted file URL from JSON in Blob ${index}:`, jsonObj.file);
                                return jsonObj.file;
                            }
                        } catch (e) {
                            console.warn(`Failed to parse potential JSON in Blob ${index}:`, e);
                        }
                    } else {
                        console.log(`Blob ${index} doesn't appear to be JSON`);
                    }
                } catch (e) {
                    console.warn(`Failed to process potential JSON in Blob ${index}:`, e);
                }
            }
            
            // Do a final MIME type check
            if (file instanceof File && file.type !== 'application/pdf') {
                console.warn(`File ${index} (${file.name}) is not a PDF, type: ${file.type}`);
                throw new Error(`الملف "${file.name}" ليس ملف PDF صالح.`);
            }
            
            // Return the file as is if we couldn't extract a URL
            return file;
        }));
        
        console.log('Processed files for merging:', processedFiles);
        
        // Extract File objects or URL strings only
        const validFiles = processedFiles.filter(file => {
            // Keep URL strings
            if (typeof file === 'string') return true;
            
            // Keep File/Blob objects
            if (file instanceof Blob) return true;
            
            // Check for objects with file URLs
            if (file && typeof file === 'object' && file.file && typeof file.file === 'string') return true;
            
            console.warn('Filtering out invalid file type:', file);
            return false;
        });
        
        if (validFiles.length < 2) {
            throw new Error('يلزم توفر ملفين PDF صالحين على الأقل للدمج.');
        }
        
        console.log('Sending files to merge service:', validFiles);
        
        const result = await PdfMergeService.mergePdfs(validFiles);
        
        if (result.success) {
            processedFileUrl.value = result.data?.output_url || result.file;
        } else {
            errorMessage.value = result.message || 'حدث خطأ أثناء معالجة الملفات. يرجى المحاولة مرة أخرى.';
        }
    } catch (error) {
        console.error('Error merging PDFs:', error);
        errorMessage.value = error.message || 'حدث خطأ أثناء معالجة الملفات. يرجى المحاولة مرة أخرى.';
    } finally {
        isProcessing.value = false;
    }
}

// Download the merged file
function downloadMergedFile() {
    if (processedFileUrl.value) {
        window.open(processedFileUrl.value, '_blank');
    }
}

// Reset the form to start a new merger
function startNew() {
    files.value = [];
    processedFileUrl.value = null;
    errorMessage.value = '';
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
            message: 'خدمة دمج الملفات جاهزة للاستخدام'
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
    <Head title="دمج ملفات PDF" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">دمج ملفات PDF</h2>
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
                            <h3 class="mt-4 text-xl font-medium text-gray-900">تم دمج ملفات PDF بنجاح!</h3>
                            <p class="mt-1 text-gray-500">الملف جاهز للتنزيل الآن.</p>
                        </div>
                        <div class="flex flex-col sm:flex-row justify-center space-y-3 sm:space-y-0 sm:space-x-4 sm:space-x-reverse">
                            <button 
                                @click="downloadMergedFile" 
                                class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                تنزيل الملف المدمج
                            </button>
                            <button 
                                @click="startNew" 
                                class="inline-flex items-center justify-center px-5 py-3 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                بدء عملية دمج جديدة
                            </button>
                        </div>
                    </div>

                    <!-- Upload State -->
                    <div v-else>
                        <div class="text-center mb-8">
                            <h3 class="text-lg font-medium text-gray-900 mb-2">دمج ملفات PDF</h3>
                            <p class="text-gray-600">قم بتحميل ملفات PDF التي ترغب في دمجها. يمكنك إعادة ترتيبها قبل الدمج.</p>
                        </div>

                        <!-- File Drop Area -->
                        <div 
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
                                اسحب وأفلت ملفات PDF هنا، أو
                                <label class="relative cursor-pointer text-blue-600 hover:text-blue-800">
                                    <span>انقر للاختيار</span>
                                    <input type="file" class="sr-only" multiple accept="application/pdf" @change="handleFileSelect" />
                                </label>
                            </p>
                            <p class="mt-1 text-xs text-gray-500">يمكنك تحميل ملفات PDF فقط</p>
                        </div>
                    
                        <!-- File List -->
                        <div v-if="hasFiles" class="mt-6 mb-6">
                            <h4 class="text-sm font-medium text-gray-700 mb-3">الملفات المحددة ({{ files.length }})</h4>
                            <div class="space-y-2">
                                <div 
                                    v-for="(file, index) in files" 
                                    :key="index"
                                    class="flex items-center justify-between bg-gray-50 p-3 rounded-md"
                                >
                                    <div class="flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-500 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                        </svg>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">{{ file.name }}</p>
                                            <p class="text-xs text-gray-500">{{ (file.size / 1024 / 1024).toFixed(2) }} MB</p>
                                        </div>
                                    </div>
                                    <div class="flex space-x-2 space-x-reverse">
                                        <button 
                                            @click="moveFileUp(index)" 
                                            :disabled="index === 0"
                                            class="text-gray-400 hover:text-gray-600 disabled:opacity-50"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                            </svg>
                                        </button>
                                        <button 
                                            @click="moveFileDown(index)" 
                                            :disabled="index === files.length - 1"
                                            class="text-gray-400 hover:text-gray-600 disabled:opacity-50"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>
                                        <button 
                                            @click="removeFile(index)" 
                                            class="text-red-400 hover:text-red-600"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Actions -->
                        <div class="flex justify-center mt-8">
                            <button 
                                @click="mergePdfs" 
                                class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none disabled:opacity-50"
                                :disabled="!hasFiles || isProcessing || files.length < 2 || !apiStatus.ready"
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
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2" />
                                    </svg>
                                    دمج الملفات
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Instructions -->
                <div class="mt-8 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">كيفية دمج ملفات PDF</h3>
                    <div class="grid md:grid-cols-3 gap-6">
                        <div class="flex flex-col items-center text-center">
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                                <span class="text-blue-600 font-bold">1</span>
                            </div>
                            <h4 class="font-medium mb-2">اختر الملفات</h4>
                            <p class="text-gray-600 text-sm">قم بتحميل ملفات PDF التي ترغب في دمجها.</p>
                        </div>
                        <div class="flex flex-col items-center text-center">
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                                <span class="text-blue-600 font-bold">2</span>
                            </div>
                            <h4 class="font-medium mb-2">رتب الملفات</h4>
                            <p class="text-gray-600 text-sm">قم بترتيب الملفات حسب التسلسل الذي تريده.</p>
                        </div>
                        <div class="flex flex-col items-center text-center">
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                                <span class="text-blue-600 font-bold">3</span>
                            </div>
                            <h4 class="font-medium mb-2">دمج واستلام</h4>
                            <p class="text-gray-600 text-sm">انقر على "دمج الملفات" واستلم الملف المدمج.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Custom styles if needed */
</style> 