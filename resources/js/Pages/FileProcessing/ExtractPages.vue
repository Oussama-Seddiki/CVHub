<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed, onMounted } from 'vue';
import SubscriptionWarning from '@/components/SubscriptionWarning.vue';
import axios from 'axios';

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
const processedFiles = ref([]);
const errorMessage = ref('');
const dragActive = ref(false);
const totalPages = ref(0);
const selectedPages = ref([]);
const previewPages = ref([]);
const isGeneratingPreview = ref(false);
const fileUrl = ref(null);

// Use the activeSubscription prop directly
const hasActiveSubscription = computed(() => props.activeSubscription);

// Check if there is a file uploaded
const hasFile = computed(() => file.value !== null);

// Set the file to upload
async function setFile(newFile) {
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
    
    // Generate preview of the actual PDF pages
    await generatePreview();
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
        try {
            // Load PDF.js if not already loaded
            await loadPdfJs();
            
            // Generate client-side previews
            await generateClientSidePreviews(fileUrl.value);
        } catch (e) {
            console.error('Failed to generate client-side previews:', e);
            // Create placeholders if PDF.js fails
            createPlaceholderPreviews();
        }
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

// Process the PDF to extract pages using client-side processing
async function processPdf() {
    if (!file.value) {
        errorMessage.value = 'يرجى تحميل ملف PDF أولاً.';
        return;
    }
    
    if (selectedPages.value.length === 0) {
        errorMessage.value = 'يرجى تحديد صفحة واحدة على الأقل لاستخراجها.';
        return;
    }
    
    isProcessing.value = true;
    errorMessage.value = '';
    
    try {
        console.log('Processing PDF to extract pages...');
        console.log('Pages to extract:', selectedPages.value);
        
        // Get pages to extract as comma-separated string
        const pagesToExtract = [...selectedPages.value].sort((a, b) => a - b);
        const pageRangesString = pagesToExtract.join(',');
        console.log('Pages to extract (string format):', pageRangesString);
        
        // Use PdfExtractService for actual processing
        const PdfExtractService = await import('@/services/PdfExtractService').then(m => m.default);
        
        // Optional metadata for the extracted PDF
        const metadata = {
            title: `Extracted pages from ${file.value.name}`,
            author: props.auth?.user?.name || 'CVHub User',
            subject: 'Extracted PDF pages',
            keywords: ['PDF', 'pages', 'extract'],
            creator: 'CVHub PDF Extraction Tool',
            producer: 'CVHub'
        };
        
        const result = await PdfExtractService.extractPages(file.value, pageRangesString, metadata);
        
        if (result.success) {
            console.log('PDF extraction completed successfully');
            processedFiles.value = [{
                url: result.file,
                filename: result.filename,
                pages: pagesToExtract.join(', ')
            }];
        } else {
            throw new Error(result.message || 'Failed to extract PDF pages');
        }
        
        // Set success state
        isProcessing.value = false;
    } catch (error) {
        console.error('Error processing PDF:', error);
        errorMessage.value = 'حدث خطأ أثناء معالجة الملف. يرجى المحاولة مرة أخرى.';
        isProcessing.value = false;
    }
}

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

// Generate client-side previews using PDF.js
async function generateClientSidePreviews(pdfUrl) {
    try {
        // Ensure PDF.js is loaded
        await loadPdfJs();
        
        console.log('Loading PDF from URL:', pdfUrl);
        
        // Initialize preview pages array before loading
        previewPages.value = [];
        
        // Load the PDF with proper caching and error handling
        const loadingTask = window.pdfjsLib.getDocument({
            url: pdfUrl,
            cMapUrl: 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/cmaps/',
            cMapPacked: true,
        });
        
        // Set a timeout to prevent hanging
        const timeoutPromise = new Promise((_, reject) => 
            setTimeout(() => reject(new Error('PDF loading timed out')), 30000)
        );
        
        // Race the PDF loading against the timeout
        const pdf = await Promise.race([loadingTask.promise, timeoutPromise]);
        
        // Get total pages
        const pageCount = pdf.numPages;
        totalPages.value = pageCount;
        const pagesToRender = Math.min(pageCount, 20); // Limit to 20 pages
        
        console.log(`PDF loaded successfully. Total pages: ${pageCount}, rendering ${pagesToRender} previews`);
        
        // Create a queue of pages to render to avoid overloading the browser
        const pageQueue = Array.from({ length: pagesToRender }, (_, i) => i + 1);
        const concurrentRenders = 4; // Number of pages to render simultaneously
        let activeRenders = 0;
        let completedRenders = 0;
        
        // Process the queue with limited concurrency
        async function processQueue() {
            while (pageQueue.length > 0 && activeRenders < concurrentRenders) {
                const pageNumber = pageQueue.shift();
                activeRenders++;
                
                // Render the page (don't await here to allow concurrent processing)
                renderPage(pageNumber).finally(() => {
                    activeRenders--;
                    completedRenders++;
                    
                    // Log progress every 5 pages
                    if (completedRenders % 5 === 0 || completedRenders === pagesToRender) {
                        console.log(`PDF preview progress: ${completedRenders}/${pagesToRender} pages`);
                    }
                    
                    // Continue processing the queue
                    processQueue();
                });
            }
        }
        
        // Render a single page
        async function renderPage(pageNumber) {
            try {
                // Get the page
                const page = await pdf.getPage(pageNumber);
                const viewport = page.getViewport({ scale: 0.5 }); // Lower scale for thumbnails
                
                // Create canvas
                const canvas = document.createElement('canvas');
                const context = canvas.getContext('2d');
                canvas.height = viewport.height;
                canvas.width = viewport.width;
                
                // Render the page
                await page.render({
                    canvasContext: context,
                    viewport: viewport
                }).promise;
                
                // Convert to data URL with reduced quality to minimize memory usage
                const dataUrl = canvas.toDataURL('image/jpeg', 0.7);
                
                // Update the thumbnail
                previewPages.value.push({
                    id: pageNumber,
                    number: pageNumber,
                    selected: false,
                    thumbnail: dataUrl
                });
                
                // Sort the pages by page number to ensure correct order
                previewPages.value.sort((a, b) => a.number - b.number);
                
                // Clean up to reduce memory usage
                canvas.width = 1;
                canvas.height = 1;
                canvas.remove();
            } catch (pageError) {
                console.warn(`Error rendering page ${pageNumber}:`, pageError);
                // Add placeholder for failed page
                previewPages.value.push({
                    id: pageNumber,
                    number: pageNumber,
                    selected: false,
                    thumbnail: createPlaceholderThumbnail(pageNumber)
                });
                
                // Sort the pages by page number to ensure correct order
                previewPages.value.sort((a, b) => a.number - b.number);
            }
        }
        
        // Start processing the queue with multiple concurrent renders
        for (let i = 0; i < concurrentRenders; i++) {
            processQueue();
        }
        
        return true;
    } catch (error) {
        console.error('Error generating client-side previews:', error);
        // If preview generation fails, create placeholders
        createPlaceholderPreviews();
        return false;
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
    fileUrl.value = null;
}

// Toggle page selection
function togglePageSelection(page) {
    page.selected = !page.selected;
    
    if (page.selected) {
        selectedPages.value.push(page.number);
    } else {
        const index = selectedPages.value.indexOf(page.number);
        if (index > -1) {
            selectedPages.value.splice(index, 1);
        }
    }
    
    // Sort the array numerically
    selectedPages.value.sort((a, b) => a - b);
}

// Download the processed file
function downloadProcessedFile() {
    if (processedFiles.value.length > 0) {
        for (const file of processedFiles.value) {
            window.open(file.url, '_blank');
        }
    }
}

// Handle image loading errors
function handleImageError(event, page) {
    console.warn(`Error loading thumbnail for page ${page.number}, falling back to placeholder`);
    // Replace with placeholder
    event.target.src = createPlaceholderThumbnail(page.number);
}

// Reset the form to start a new process
function startNew() {
    if (processedFiles.value.length > 0) {
        for (const file of processedFiles.value) {
            URL.revokeObjectURL(file.url);
        }
    }
    file.value = null;
    processedFiles.value = [];
    errorMessage.value = '';
    totalPages.value = 0;
    previewPages.value = [];
    selectedPages.value = [];
    fileUrl.value = null;
}

// Update API status check to always return ready
const apiStatus = ref({
    ready: true, // Always ready since we're using client-side processing
    loading: false,
    checked: true,
    error: null
});

// API status check on mount
onMounted(async () => {
    try {
        console.log('Checking API status...');
        // Always return ready since we're using client-side processing
        apiStatus.value = {
            ready: true,
            loading: false,
            checked: true,
            error: null
        };
    } catch (error) {
        console.error('Error checking API status:', error);
        apiStatus.value = {
            ready: true, // Assume API is ready even if health check fails
            loading: false,
            message: 'تم تفعيل طريقة عرض بديلة للملفات', // Inform the user about the fallback mode
            checked: true,
            error: error
        };
    }
});
</script>

<template>
    <Head title="استخراج صفحات PDF" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">استخراج صفحات PDF</h2>
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

                <!-- Error Message -->
                <div v-if="errorMessage" class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    <span class="block sm:inline">{{ errorMessage }}</span>
                </div>

                <!-- Main Content -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <!-- Success State -->
                    <div v-if="processedFiles.length > 0" class="text-center">
                        <div class="mb-6">
                            <div class="mx-auto w-16 h-16 bg-green-100 rounded-full flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <h3 class="mt-4 text-xl font-medium text-gray-900">تم استخراج الصفحات من ملف PDF بنجاح!</h3>
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
                                تنزيل الملف المعالج
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
                            <h3 class="text-lg font-medium text-gray-900 mb-2">استخراج صفحات من ملف PDF</h3>
                            <p class="text-gray-600">قم بتحميل ملف PDF وحدد الصفحات التي ترغب في استخراجها كملف PDF جديد.</p>
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
                                <h4 class="font-medium text-gray-900 mb-4">حدد الصفحات التي ترغب في استخراجها:</h4>
                                
                                <div v-if="selectedPages.length > 0" class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-md">
                                    <p class="text-sm text-blue-800">
                                        الصفحات المحددة للاستخراج: {{ selectedPages.join(', ') }}
                                    </p>
                                </div>
                                
                                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                                    <div 
                                        v-for="page in previewPages" 
                                        :key="page.id"
                                        class="border rounded-md overflow-hidden cursor-pointer"
                                        :class="{ 'border-blue-500 bg-blue-50': page.selected, 'border-gray-200': !page.selected }"
                                        @click="togglePageSelection(page)"
                                    >
                                        <div class="relative bg-gray-100">
                                            <!-- PDF thumbnail preview -->
                                            <div class="w-full min-h-180 flex items-center justify-center relative">
                                                <img 
                                                    v-if="page.thumbnail" 
                                                    :src="page.thumbnail" 
                                                    :alt="`Page ${page.number}`"
                                                    class="max-h-full max-w-full object-contain"
                                                    @error="handleImageError($event, page)"
                                                />
                                                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                
                                                <!-- Loading indicator while generating preview -->
                                                <div v-if="isGeneratingPreview" class="absolute inset-0 bg-white bg-opacity-70 flex items-center justify-center">
                                                    <svg class="animate-spin h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                </div>
                                                
                                                <!-- Check mark overlay if selected -->
                                                <div v-if="page.selected" class="absolute inset-0 bg-blue-500 bg-opacity-20 flex items-center justify-center">
                                                    <div class="bg-blue-500 rounded-full p-1">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="py-2 px-3 text-center bg-white">
                                            <span class="text-sm font-medium">الصفحة {{ page.number }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Actions -->
                        <div class="flex justify-center mt-8">
                            <button 
                                @click="processPdf" 
                                class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none disabled:opacity-50"
                                :disabled="!hasFile || isProcessing || selectedPages.length === 0 || !apiStatus.ready"
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
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12" />
                                    </svg>
                                    استخراج الصفحات المحددة
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Instructions -->
                <div class="mt-8 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">كيفية استخراج صفحات من ملف PDF</h3>
                    <div class="grid md:grid-cols-3 gap-6">
                        <div class="flex flex-col items-center text-center">
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                                <span class="text-blue-600 font-bold">1</span>
                            </div>
                            <h4 class="font-medium mb-2">اختر الملف</h4>
                            <p class="text-gray-600 text-sm">قم بتحميل ملف PDF الذي ترغب في استخراج صفحات منه.</p>
                        </div>
                        <div class="flex flex-col items-center text-center">
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                                <span class="text-blue-600 font-bold">2</span>
                            </div>
                            <h4 class="font-medium mb-2">حدد الصفحات</h4>
                            <p class="text-gray-600 text-sm">انقر على الصفحات التي ترغب في استخراجها كملف PDF جديد.</p>
                        </div>
                        <div class="flex flex-col items-center text-center">
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                                <span class="text-blue-600 font-bold">3</span>
                            </div>
                            <h4 class="font-medium mb-2">استخراج واستلام</h4>
                            <p class="text-gray-600 text-sm">انقر على "استخراج الصفحات المحددة" واستلم الملف المعالج.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
.min-h-180 {
    min-height: 180px;
}
</style>