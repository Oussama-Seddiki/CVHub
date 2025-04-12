<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed, onMounted } from 'vue';
import SubscriptionWarning from '@/components/SubscriptionWarning.vue';

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
const totalPages = ref(0);
const selectedPages = ref([]);
const previewPages = ref([]);
const fileUrl = ref(null);

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
    
    // Generate preview from the client-side
    generatePreview();
}

// Generate PDF preview using client-side PDF.js
async function generatePreview() {
    isUploading.value = true;
    errorMessage.value = '';
    previewPages.value = [];
    selectedPages.value = [];
    
    try {
        console.log('Generating preview using client-side processing...');
        
        // Create object URL for the file
        fileUrl.value = URL.createObjectURL(file.value);
        
        // Use PDF.js to get page count and generate previews
        await generateClientSidePreviews(fileUrl.value);
    } catch (error) {
        console.error('Error generating preview:', error);
        errorMessage.value = 'حدث خطأ أثناء توليد المعاينة.';
    } finally {
        isUploading.value = false;
    }
}

// Create placeholder previews if PDF.js fails
function createPlaceholderPreviews() {
    // Estimate page count based on file size (rough approximation)
    const estimatedPageCount = Math.max(1, Math.ceil(file.value.size / 50000));
    totalPages.value = estimatedPageCount;
    
    // Create placeholder previews
    for (let i = 1; i <= estimatedPageCount; i++) {
        previewPages.value.push(createPlaceholderThumbnail(i));
    }
}

// Process the PDF to remove pages using client-side processing
async function processPdf() {
    if (!file.value) {
        errorMessage.value = 'يرجى تحميل ملف PDF أولاً.';
        return;
    }
    
    if (selectedPages.value.length === 0) {
        errorMessage.value = 'يرجى تحديد صفحة واحدة على الأقل لحذفها.';
        return;
    }
    
    if (selectedPages.value.length === totalPages.value) {
        errorMessage.value = 'لا يمكنك حذف جميع صفحات الملف.';
        return;
    }
    
    isProcessing.value = true;
    errorMessage.value = '';
    
    try {
        console.log('Processing PDF to remove pages...');
        console.log('Pages to remove:', selectedPages.value);
        
        // Convert selectedPages to comma-separated string for the service
        const pagesToRemoveString = selectedPages.value.join(',');
        
        // Use PdfRemoveService for actual processing
        import('@/services/PdfRemoveService').then(async (module) => {
            const PdfRemoveService = module.default;
            
            const result = await PdfRemoveService.removePages(file.value, pagesToRemoveString);
            
            if (result.success) {
                processedFileUrl.value = result.file;
                console.log('PDF processing completed successfully');
            } else {
                throw new Error(result.message || 'Failed to process PDF');
            }
            
            isProcessing.value = false;
        }).catch(error => {
            console.error('Error processing PDF:', error);
            errorMessage.value = 'حدث خطأ أثناء معالجة الملف. يرجى المحاولة مرة أخرى.';
            isProcessing.value = false;
        });
    } catch (error) {
        console.error('Error processing PDF:', error);
        errorMessage.value = 'حدث خطأ أثناء معالجة الملف. يرجى المحاولة مرة أخرى.';
        isProcessing.value = false;
    }
}

// Update API status check to always return ready
const apiStatus = ref({
    ready: true, // Always ready since we're using client-side processing
    loading: false,
    checked: true,
    error: null
});

// Load PDF.js if not already loaded
async function loadPdfJs() {
    try {
        // Check if PDF.js is already loaded
        if (typeof window.pdfjsLib !== 'undefined') {
            console.log('PDF.js is already loaded');
            return;
        }
        
        console.log('Loading PDF.js library...');
        
        // Load PDF.js script
        const script = document.createElement('script');
        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js';
        
        // Create a promise to wait for script to load
        const scriptLoaded = new Promise((resolve, reject) => {
            script.onload = () => {
                // Set worker source after script is loaded
                window.pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
                console.log('PDF.js loaded successfully');
                resolve();
            };
            script.onerror = (error) => {
                console.error('Failed to load PDF.js:', error);
                reject(error);
            };
        });
        
        // Add script to document
        document.head.appendChild(script);
        
        // Wait for script to load
        await scriptLoaded;
    } catch (error) {
        console.error('Error loading PDF.js:', error);
        throw error;
    }
}

// Use PDF.js to generate client-side previews
async function generateClientSidePreviews(pdfUrl) {
    try {
        await loadPdfJs(); // Ensure PDF.js is loaded
        
        console.log('Starting client-side preview generation...');
        
        // Load the PDF document
        const loadingTask = window.pdfjsLib.getDocument(pdfUrl);
        const pdfDocument = await loadingTask.promise;
        
        // Get total pages
        totalPages.value = pdfDocument.numPages;
        console.log(`PDF loaded with ${totalPages.value} pages`);
        
        // Initialize preview pages array
        previewPages.value = [];
        
        // Generate previews for all pages (limit to 100 pages max)
        const maxPages = Math.min(totalPages.value, 100);
        
        for (let i = 1; i <= maxPages; i++) {
            // Get the page
            const page = await pdfDocument.getPage(i);
            
            // Set scale for thumbnail (adjust as needed)
            const scale = 0.5;
            const viewport = page.getViewport({ scale });
            
            // Prepare canvas for rendering
            const canvas = document.createElement('canvas');
            const context = canvas.getContext('2d');
            canvas.height = viewport.height;
            canvas.width = viewport.width;
            
            // Render PDF page into canvas context
            const renderContext = {
                canvasContext: context,
                viewport: viewport
            };
            
            try {
                await page.render(renderContext).promise;
                
                // Convert canvas to data URL
                const dataUrl = canvas.toDataURL('image/jpeg', 0.8);
                
                // Add to previews
                previewPages.value.push({
                    id: i,
                    number: i,
                    selected: false,
                    thumbnail: dataUrl
                });
                
                console.log(`Generated preview for page ${i}`);
            } catch (renderError) {
                console.error(`Error rendering page ${i}:`, renderError);
                
                // Add placeholder instead
                previewPages.value.push(createPlaceholderThumbnail(i));
            }
        }
        
        console.log(`Successfully generated ${previewPages.value.length} previews`);
    } catch (error) {
        console.error('Error in client-side preview generation:', error);
        throw error;
    }
}

// Create a placeholder thumbnail for pages without preview
function createPlaceholderThumbnail(pageNumber) {
    // Use the dynamic placeholder generator PHP script if available
    const baseUrl = window.location.origin;
    return `${baseUrl}/images/pdf-placeholder.php?page=${pageNumber}`;
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
    totalPages.value = 0;
    previewPages.value = [];
    selectedPages.value = [];
}

// Toggle page selection for removal
function togglePageSelection(pageNumber) {
    const index = selectedPages.value.indexOf(pageNumber);
    if (index === -1) {
        // Add page to selection
        selectedPages.value.push(pageNumber);
    } else {
        // Remove page from selection
        selectedPages.value.splice(index, 1);
    }
}

// Check if a page is selected
function isPageSelected(pageNumber) {
    return selectedPages.value.includes(pageNumber);
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
    totalPages.value = 0;
    previewPages.value = [];
    selectedPages.value = [];
    fileUrl.value = null;
}

// API status check on mount
onMounted(async () => {
    // Update API status check to always return ready
    apiStatus.value = {
        ready: true, // Always ready since we're using client-side processing
        loading: false,
        checked: true,
        error: null
    };
});
</script>

<template>
    <Head title="حذف صفحات PDF" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                حذف صفحات PDF
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- API Status Warning -->
                <div v-if="!apiStatus.ready && apiStatus.checked" class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="mr-3">
                            <p class="text-sm text-yellow-700">
                                {{ apiStatus.message }}
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Subscription Warning -->
                <SubscriptionWarning 
                    v-if="!activeSubscription" 
                    :status="subscriptionStatus"
                    :ends-at="subscriptionEndsAt"
                />
                
                <!-- Main content -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div v-if="processedFileUrl">
                        <div class="p-6 bg-green-50 border border-green-200 rounded-lg text-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-green-500 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <h3 class="text-xl font-medium text-green-800 mb-2">تمت المعالجة بنجاح!</h3>
                            <p class="text-green-700 mb-6">تم حذف الصفحات المحددة من ملف PDF بنجاح.</p>
                            
                            <div class="flex flex-col sm:flex-row space-y-3 sm:space-y-0 sm:space-x-3 sm:space-x-reverse justify-center">
                                <button 
                                    @click="downloadProcessedFile" 
                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded shadow-sm"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                    تنزيل الملف
                                </button>
                                <button 
                                    @click="startNew" 
                                    class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded shadow-sm"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    عملية جديدة
                                </button>
                            </div>
                        </div>
                    </div>
                
                    <div v-else>
                        <h3 class="text-lg font-medium text-gray-900 mb-6">حذف صفحات من ملف PDF</h3>
                        
                        <!-- Error message if any -->
                        <div v-if="errorMessage" class="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="mr-3">
                                    <p class="text-sm text-red-700">
                                        {{ errorMessage }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- File Upload Area (if no file selected) -->
                        <div v-if="!hasFile" class="mt-4">
                            <div 
                                class="border-2 border-dashed border-gray-300 rounded-lg p-12 text-center"
                                :class="{ 'border-blue-500 bg-blue-50': dragActive }"
                                @dragenter="handleDragEnter"
                                @dragleave="handleDragLeave"
                                @dragover="handleDragOver"
                                @drop="handleDrop"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                                <p class="mt-4 text-lg font-medium text-gray-900">
                                    قم بتحميل ملف PDF الذي ترغب في حذف صفحات منه.
                                </p>
                                <p class="mt-2 text-sm text-gray-600">
                                    قم بسحب وإسقاط الملف هنا، أو 
                                    <label class="text-blue-600 hover:text-blue-700 cursor-pointer">
                                        <span>اضغط لاختيار ملف</span>
                                        <input type="file" class="hidden" accept="application/pdf" @change="handleFileSelect">
                                    </label>
                                </p>
                                <p class="mt-2 text-xs text-gray-500">
                                    PDF (الحد الأقصى 20 ميغابايت)
                                </p>
                            </div>
                        </div>
                        
                        <!-- File Info & Page Selection (if file selected) -->
                        <div v-if="hasFile" class="mt-6 mb-6">
                            <div class="flex justify-between items-center bg-gray-50 p-4 rounded-md mb-4">
                                <div class="flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-500 ml-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0112.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    </svg>
                                    <div>
                                        <p class="font-medium text-gray-900">{{ file.name }}</p>
                                        <div class="flex text-sm text-gray-500">
                                            <span>{{ (file.size / 1024 / 1024).toFixed(2) }} MB</span>
                                            <span class="px-2">•</span>
                                            <span>{{ totalPages }} صفحة</span>
                                        </div>
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
                            
                            <!-- Page Selection -->
                            <div class="mt-6">
                                <h4 class="font-medium text-gray-900 mb-4">حدد الصفحات التي ترغب في حذفها:</h4>
                                
                                <div v-if="selectedPages.length > 0" class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-md">
                                    <p class="text-sm text-blue-800">
                                        الصفحات المحددة للحذف: {{ selectedPages.join(', ') }}
                                    </p>
                                </div>
                                
                                <!-- Page Grid with Thumbnails -->
                                <div class="bg-white rounded-lg p-4 mb-4 border">
                                    <div v-if="previewPages.length > 0" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                                        <div v-for="page in previewPages" :key="page.id" 
                                             class="relative cursor-pointer transition-transform hover:scale-105"
                                             @click="togglePageSelection(page.number)">
                                            <div class="aspect-w-8 aspect-h-11 shadow-sm border relative bg-white overflow-hidden flex items-center justify-center"
                                                 :class="{'border-4 border-blue-500 shadow-lg': isPageSelected(page.number)}">
                                                
                                                <!-- PDF preview image -->
                                                <img v-if="page.thumbnail" 
                                                    :src="page.thumbnail" 
                                                    :alt="`صفحة ${page.number}`"
                                                    class="w-full h-full object-contain" 
                                                    loading="lazy"
                                                />
                                                
                                                <!-- Loading spinner for pages being rendered -->
                                                <div v-else-if="isUploading" class="absolute inset-0 flex items-center justify-center bg-gray-50">
                                                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                                                </div>
                                                
                                                <!-- Placeholder when no thumbnail is available -->
                                                <div v-else class="w-full h-full">
                                                    <img 
                                                        :src="createPlaceholderThumbnail(page.number)" 
                                                        :alt="`صفحة ${page.number}`"
                                                        class="w-full h-full object-contain"
                                                        loading="lazy"
                                                    />
                                                </div>
                                            </div>
                                            
                                            <!-- Checkbox -->
                                            <div class="absolute top-1 right-1 bg-white rounded-full border shadow-sm p-1 transition-transform z-10"
                                                 :class="{'scale-125': isPageSelected(page.number)}">
                                                <input type="checkbox" :checked="isPageSelected(page.number)" @click.stop />
                                            </div>
                                            
                                            <!-- Page number -->
                                            <span class="absolute bottom-1 right-1 bg-white rounded-md text-xs px-2 py-1 shadow-sm z-10">
                                                {{ page.number }}
                                            </span>
                                        </div>
                                    </div>
                                    <div v-else-if="isUploading" class="text-center py-8">
                                        <div class="flex justify-center items-center space-x-2 rtl:space-x-reverse">
                                            <span class="loading loading-spinner text-primary"></span>
                                            <span>جارِ معالجة الملف...</span>
                                        </div>
                                    </div>
                                    <div v-else class="border-dashed border-2 border-gray-300 rounded-lg p-8 text-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-400 mx-auto mb-2" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V8a2 2 0 00-2-2h-5L9 4H4zm7 5a1 1 0 10-2 0v1H8a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V9z" clip-rule="evenodd" />
                                        </svg>
                                        <p class="text-gray-600">اسحب ملف PDF هنا أو انقر لتحديد الملف</p>
                                        <p class="text-xs text-gray-500 mt-1">الحد الأقصى: 20 ميجابايت</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Actions -->
                        <div class="flex justify-center mt-8">
                            <button
                                @click="processPdf"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
                                :disabled="!file || isProcessing || selectedPages.length === 0 || selectedPages.length === totalPages || !hasActiveSubscription || !apiStatus.ready"
                            >
                                <span v-if="isProcessing">
                                    <svg class="inline w-4 h-4 text-white animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    جاري المعالجة...
                                </span>
                                <span v-else>حذف الصفحات المحددة</span>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Instructions -->
                <div class="mt-8 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">كيفية حذف صفحات من ملف PDF</h3>
                    <div class="grid md:grid-cols-3 gap-6">
                        <div class="flex flex-col items-center text-center">
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                                <span class="text-blue-600 font-bold">1</span>
                            </div>
                            <h4 class="font-medium mb-2">اختر الملف</h4>
                            <p class="text-gray-600 text-sm">قم بتحميل ملف PDF الذي ترغب في حذف صفحات منه.</p>
                        </div>
                        <div class="flex flex-col items-center text-center">
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                                <span class="text-blue-600 font-bold">2</span>
                            </div>
                            <h4 class="font-medium mb-2">حدد الصفحات</h4>
                            <p class="text-gray-600 text-sm">انقر على الصفحات التي ترغب في حذفها من الملف.</p>
                        </div>
                        <div class="flex flex-col items-center text-center">
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                                <span class="text-blue-600 font-bold">3</span>
                            </div>
                            <h4 class="font-medium mb-2">حذف واستلام</h4>
                            <p class="text-gray-600 text-sm">انقر على "حذف الصفحات المحددة" واستلم الملف المعالج.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Custom styles for page preview */
.aspect-w-8 {
    position: relative;
    padding-bottom: calc(11 / 8 * 100%);
}
.aspect-w-8 > * {
    position: absolute;
    height: 100%;
    width: 100%;
    top: 0;
    right: 0;
    bottom: 0;
    left: 0;
}
</style>