<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed, onMounted, nextTick, onBeforeUnmount, watchEffect, watch, onUnmounted, reactive, toRefs } from 'vue';
import draggable from 'vuedraggable';
import axios from 'axios';
import SubscriptionWarning from '@/components/SubscriptionWarning.vue';
import { gsap } from 'gsap';
import AOS from 'aos';
import 'aos/dist/aos.css';

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
const previewPages = ref([]);
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
    
    try {
        isUploading.value = true;
        
        // Generate preview
        await generatePreview();
    } catch (error) {
        console.error('Error processing PDF:', error);
        errorMessage.value = 'حدث خطأ أثناء معالجة الملف. يرجى المحاولة مرة أخرى.';
        isUploading.value = false;
    }
}

// Create a placeholder thumbnail for pages without preview
function createPlaceholderThumbnail(pageNumber) {
    // Use the dynamic placeholder generator PHP script if available
    const baseUrl = window.location.origin;
    return `${baseUrl}/images/pdf-placeholder.php?page=${pageNumber}`;
}

// Generate PDF preview using client-side PDF.js
async function generatePreview() {
    isUploading.value = true;
    errorMessage.value = '';
    previewPages.value = [];
    
    try {
        console.log('Generating preview using client-side processing...');
        
        // Create object URL for the file
        fileUrl.value = URL.createObjectURL(file.value);
        
        // Use PDF.js to get page count and generate previews
        try {
            // Load PDF.js if not already loaded
            if (typeof window.pdfjsLib === 'undefined') {
                await loadPdfJs();
            }
            
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
        previewPages.value.push({
            id: i,
            number: i,
            originalNumber: i,
            thumbnail: createPlaceholderThumbnail(i)
        });
    }
}

// Process the PDF to organize pages using client-side processing
async function processPdf() {
    if (!file.value) {
        errorMessage.value = 'يرجى تحميل ملف PDF أولاً.';
        return;
    }
    
    if (previewPages.value.length === 0) {
        errorMessage.value = 'لا توجد صفحات لإعادة تنظيمها.';
        return;
    }
    
    isProcessing.value = true;
    errorMessage.value = '';
    
    try {
        console.log('Processing PDF to organize pages (client-side)...');
        
        // Create page operations array from the current state of previewPages
        const pageOperations = previewPages.value.map((page, index) => ({
            pageNumber: page.originalNumber,  // The original page number in the PDF
            newPosition: index + 1,           // The new position (1-based)
            rotation: page.rotation || 0      // The rotation angle (0, 90, 180, 270)
        }));
        
        // Get the new page order for logging
        const newPageOrder = previewPages.value.map(page => page.originalNumber);
        console.log('New page order:', newPageOrder);
        
        // Optional metadata for the PDF
        const metadata = {
            title: `Reorganized ${file.value.name}`,
            author: props.auth?.user?.name || 'CVHub User',
            subject: 'Reorganized PDF document',
            keywords: ['PDF', 'organize', 'reorder', 'rotate'],
            creator: 'CVHub PDF Organization Tool',
            producer: 'CVHub'
        };
        
        // Import and use the actual PdfOrganizeService
        const PdfOrganizeService = await import('@/services/PdfOrganizeService').then(m => m.default);
        
        // Process the PDF with the service
        const result = await PdfOrganizeService.organizePdf(file.value, pageOperations, metadata);
        
        if (result.success) {
            // Set the processed file URL
            processedFileUrl.value = result.file;
            console.log('PDF processing completed successfully');
        } else {
            throw new Error(result.message || 'Failed to organize PDF');
        }
        
        // Set success state
        isProcessing.value = false;
        
    } catch (error) {
        console.error('Error organizing PDF pages:', error);
        errorMessage.value = 'حدث خطأ أثناء إعادة تنظيم الصفحات. يرجى المحاولة مرة أخرى.';
        isProcessing.value = false;
    }
}

// Load PDF.js if not already loaded
async function loadPdfJs() {
    if (typeof window.pdfjsLib !== 'undefined') {
        console.log('PDF.js is already loaded');
        return;
    }
    
    console.log('Loading PDF.js library...');
    
    try {
        // Load PDF.js from CDN
        await new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js';
            script.onload = () => {
                // Set worker source
                window.pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
                resolve();
            };
            script.onerror = reject;
            document.head.appendChild(script);
        });
        
        console.log('PDF.js loaded successfully');
    } catch (error) {
        console.error('Failed to load PDF.js:', error);
        throw error;
    }
}

// Update API status check to always return ready
const apiStatus = ref({
    ready: true, // Always ready since we're using client-side processing
    loading: false,
    checked: true,
    error: null
});

// Generate client-side previews using PDF.js
async function generateClientSidePreviews(pdfUrl) {
    try {
        // Ensure PDF.js is loaded
        if (typeof window.pdfjsLib === 'undefined') {
            // Try to load PDF.js dynamically
            try {
                await new Promise((resolve, reject) => {
                    const script = document.createElement('script');
                    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js';
                    script.onload = () => {
                        // Set worker source
                        window.pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
                        resolve();
                    };
                    script.onerror = reject;
                    document.head.appendChild(script);
                });
                
                console.log('PDF.js loaded successfully');
            } catch (e) {
                console.error('Failed to load PDF.js:', e);
                return false;
            }
        }
        
        console.log('Loading PDF from URL:', pdfUrl);
        
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
        
        // Initialize the preview pages array with empty placeholders first
        previewPages.value = [];
        for (let i = 1; i <= pageCount; i++) {
            previewPages.value.push({
                id: i,
                number: i,
                originalNumber: i,
                rotation: 0,
                thumbnail: null // Will be populated later
            });
        }
        
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
                // Find the matching page in our array
                const pageIndex = previewPages.value.findIndex(p => p.number === pageNumber);
                if (pageIndex === -1) return;
                
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
                previewPages.value[pageIndex].thumbnail = dataUrl;
                
                // Clean up to reduce memory usage
                canvas.width = 1;
                canvas.height = 1;
                canvas.remove();
            } catch (pageError) {
                console.warn(`Error rendering page ${pageNumber}:`, pageError);
                // Set placeholder for failed page
                const pageIndex = previewPages.value.findIndex(p => p.number === pageNumber);
                if (pageIndex !== -1) {
                    previewPages.value[pageIndex].thumbnail = createPlaceholderThumbnail(pageNumber);
                }
            }
        }
        
        // Start processing the queue with multiple concurrent renders
        for (let i = 0; i < concurrentRenders; i++) {
            processQueue();
        }
        
        return true;
    } catch (error) {
        console.error('Error generating client-side previews:', error);
        // If preview generation fails completely, create placeholders
        createPlaceholderPreviews();
        return false;
    }
}

// Handle image loading errors
function handleImageError(event, page) {
    console.warn(`Error loading thumbnail for page ${page.number}, falling back to placeholder`);
    // Replace with placeholder
    event.target.src = createPlaceholderThumbnail(page.number);
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
}

// Enhanced page movement functions with animations
function movePageUp(index) {
    if (index === 0) return;
    
    const fromElement = document.querySelector(`.page-item-${index}`);
    const toElement = document.querySelector(`.page-item-${index-1}`);
    
    if (fromElement && toElement && window.gsap) {
        // Get positions for animation
        const fromRect = fromElement.getBoundingClientRect();
        const toRect = toElement.getBoundingClientRect();
        const deltaY = toRect.top - fromRect.top;
        
        // Animate both elements
        gsap.to(fromElement, {
            y: deltaY,
            duration: 0.3,
            ease: "power1.out"
        });
        
        gsap.to(toElement, {
            y: -Math.abs(deltaY),
            duration: 0.3,
            ease: "power1.out",
            onComplete: () => {
                // Reset transforms after the swap
                gsap.set([fromElement, toElement], { y: 0 });
                
                // Actually swap the array elements
                const temp = previewPages.value[index];
                previewPages.value[index] = previewPages.value[index - 1];
                previewPages.value[index - 1] = temp;
            }
        });
    } else {
        // Fallback if GSAP isn't available
        const temp = previewPages.value[index];
        previewPages.value[index] = previewPages.value[index - 1];
        previewPages.value[index - 1] = temp;
    }
}

function movePageDown(index) {
    if (index === previewPages.value.length - 1) return;
    
    const fromElement = document.querySelector(`.page-item-${index}`);
    const toElement = document.querySelector(`.page-item-${index+1}`);
    
    if (fromElement && toElement && window.gsap) {
        // Get positions for animation
        const fromRect = fromElement.getBoundingClientRect();
        const toRect = toElement.getBoundingClientRect();
        const deltaY = toRect.top - fromRect.top;
        
        // Animate both elements
        gsap.to(fromElement, {
            y: deltaY,
            duration: 0.3,
            ease: "power1.out"
        });
        
        gsap.to(toElement, {
            y: -Math.abs(deltaY),
            duration: 0.3,
            ease: "power1.out",
            onComplete: () => {
                // Reset transforms after the swap
                gsap.set([fromElement, toElement], { y: 0 });
                
                // Actually swap the array elements
                const temp = previewPages.value[index];
                previewPages.value[index] = previewPages.value[index + 1];
                previewPages.value[index + 1] = temp;
            }
        });
    } else {
        // Fallback if GSAP isn't available
        const temp = previewPages.value[index];
        previewPages.value[index] = previewPages.value[index + 1];
        previewPages.value[index + 1] = temp;
    }
}

// Delete page with animation
function deletePage(index) {
    // Add delete animation
    const pageElement = document.querySelector(`.page-item-${index}`);
    if (pageElement) {
        // Animate the removal with GSAP
        gsap.to(pageElement, {
            opacity: 0,
            x: 30,
            scale: 0.95,
            duration: 0.3,
            onComplete: () => {
                // Remove from array after animation completes
                previewPages.value.splice(index, 1);
            }
        });
    } else {
        // Fallback if element not found
        previewPages.value.splice(index, 1);
    }
}

// Single optimized rotatePage function with animations
function rotatePage(index) {
    const page = previewPages.value[index];
    
    // Update rotation state (0°, 90°, 180°, 270°)
    page.rotation = (page.rotation + 90) % 360;
    
    // Add animation class
    page.animating = true;
    
    // Use GSAP for smooth rotation
    const thumbnailImg = document.querySelector(`.thumbnail-container-${index} img`);
    if (thumbnailImg) {
        gsap.to(thumbnailImg, {
            rotation: `+=90`,
            duration: 0.5,
            ease: "power2.out",
            onComplete: () => {
                // Remove animation flag when complete
                page.animating = false;
            }
        });
    } else {
        page.animating = false;
    }
}

// Download the processed file
function downloadProcessedFile() {
    if (processedFileUrl.value) {
        const a = document.createElement('a');
        a.href = processedFileUrl.value;
        a.download = `organized-${file.value.name || 'document.pdf'}`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }
}

// Reset the form to start a new process
function startNew() {
    if (processedFileUrl.value && processedFileUrl.value.startsWith('blob:')) {
        URL.revokeObjectURL(processedFileUrl.value);
    }
    file.value = null;
    processedFileUrl.value = null;
    errorMessage.value = '';
    totalPages.value = 0;
    previewPages.value = [];
}

onMounted(async () => {
    try {
        // Initialize AOS for scroll animations
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true
        });
        
        // Check if PDF processing service is available
        // Removed API call
        apiStatus.value.ready = true;
    } catch (error) {
        console.error('Error in mounted hook:', error);
    }
});

// Cleanup on unmount
onUnmounted(() => {
    // Clean up any GSAP animations
    gsap.killAll();
});
</script>

<template>
    <Head title="تنظيم صفحات PDF" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">تنظيم صفحات PDF</h2>
                <Link
                    :href="route('fileprocessing.index')"
                    class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
                >
                    العودة لمعالجة الملفات
                </Link>
            </div>
        </template>

        <div class="py-12 bg-gray-50">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- API Status Warning -->
                <div v-if="!apiStatus.ready && !apiStatus.loading" class="mb-6 bg-yellow-100 border-l-4 border-yellow-400 p-4" data-aos="fade-down">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="mr-3">
                            <p class="text-sm text-yellow-700">
                                {{ apiStatus.message || 'خدمة معالجة PDF غير متوفرة حالياً. يرجى المحاولة لاحقاً.' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Subscription Warning -->
                <SubscriptionWarning v-if="!hasActiveSubscription" class="mb-6" data-aos="fade-down" data-aos-delay="100" />

                <!-- Loading API Status -->
                <div v-if="apiStatus.loading" class="mb-6 bg-blue-50 border-l-4 border-blue-400 p-4" data-aos="fade-in">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="animate-spin h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                        <div class="mr-3">
                            <p class="text-sm text-blue-700">
                                جاري التحقق من توفر خدمة معالجة PDF...
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6" data-aos="fade-up" data-aos-delay="200">
                    <!-- Success State -->
                    <div v-if="processedFileUrl" class="text-center">
                        <div class="mb-6">
                            <div class="mx-auto w-16 h-16 bg-green-100 rounded-full flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <h3 class="mt-3 text-lg font-medium text-gray-900">تم تنظيم ملف PDF بنجاح!</h3>
                            <p class="mt-1 text-sm text-gray-500">يمكنك الآن تنزيل الملف المعالج.</p>
                        </div>
                        
                        <div class="flex justify-center space-x-4 space-x-reverse">
                            <button
                                @click="downloadProcessedFile"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150 page-action-btn"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                تنزيل الملف المعالج
                            </button>
                            
                            <button
                                @click="startNew"
                                class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150 page-action-btn"
                            >
                                بدء عملية جديدة
                            </button>
                        </div>
                    </div>
                    
                    <div v-else>
                        <div class="text-center mb-8">
                            <h3 class="text-lg font-medium text-gray-900 mb-2">تنظيم صفحات ملف PDF</h3>
                            <p class="text-gray-600">قم بتحميل ملف PDF وإعادة ترتيب الصفحات أو تدويرها أو حذفها.</p>
                        </div>
                        
                        <!-- File Upload -->
                        <div 
                            v-if="!hasFile && !isProcessing"
                            @dragenter.prevent="handleDragEnter"
                            @dragleave.prevent="handleDragLeave"
                            @dragover.prevent="handleDragOver"
                            @drop.prevent="handleDrop"
                            class="mt-6 border-2 border-dashed rounded-md px-6 pt-5 pb-6 flex flex-col items-center min-h-180 transition-all duration-300"
                            :class="dragActive ? 'border-blue-400 bg-blue-50' : 'border-gray-300 hover:border-blue-300'"
                            data-aos="fade-up"
                        >
                            <div class="text-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-gray-400 transition-transform duration-300 ease-in-out transform hover:scale-110" :class="{'animate-bounce': dragActive}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                                <p class="mt-4 text-sm text-gray-600">
                                    <span v-if="dragActive">قم بإفلات الملف هنا</span>
                                    <span v-else>قم بسحب وإفلات ملف PDF هنا، أو</span>
                                </p>
                                <div v-if="!dragActive" class="mt-3">
                                    <label for="file-upload" class="cursor-pointer px-4 py-2 rounded-md bg-blue-600 text-white hover:bg-blue-700 transition-colors duration-300">
                                        تصفح الملفات
                                    </label>
                                    <input id="file-upload" type="file" accept=".pdf" class="sr-only" @change="handleFileSelect">
                                </div>
                                <p class="mt-2 text-xs text-gray-500">PDF فقط (الحد الأقصى 10 ميجابايت)</p>
                                
                                <p v-if="errorMessage" class="mt-2 text-sm text-red-600">{{ errorMessage }}</p>
                            </div>
                        </div>
                        
                        <!-- File Details & Preview -->
                        <div v-if="hasFile" class="mt-6 mb-6">
                            <div class="flex justify-between items-center bg-gray-50 p-4 rounded-md mb-4 page-item">
                                <div class="flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-500 ml-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0112.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    </svg>
                                    <div>
                                        <p class="font-medium text-gray-900">{{ file.name }}</p>
                                        <p class="text-sm text-gray-500">{{ formatFileSize(file.size) }} • {{ totalPages }} صفحة</p>
                                    </div>
                                </div>
                                <button 
                                    @click="removeFile" 
                                    class="text-red-600 hover:text-red-900 transition-colors duration-300 page-action-btn"
                                    title="إزالة الملف"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            
                            <!-- Previews Loading -->
                            <div v-if="isGeneratingPreview" class="bg-gray-50 p-6 rounded-md text-center">
                                <svg class="animate-spin h-8 w-8 text-blue-600 mx-auto mb-4 loading-spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <p class="text-gray-700">جاري توليد معاينات الصفحات...</p>
                            </div>
                            
                            <!-- Previews -->
                            <div v-else class="mt-6">
                                <h4 class="font-medium text-gray-900 mb-4">اسحب وأفلت الصفحات لإعادة ترتيبها:</h4>
                                
                                <draggable 
                                    v-model="previewPages" 
                                    group="pages"
                                    handle=".drag-handle"
                                    :animation="300"
                                    ghost-class="sortable-ghost"
                                    chosen-class="sortable-chosen"
                                    drag-class="sortable-drag"
                                >
                                    <template #item="{ element, index }">
                                        <div :class="['flex items-center justify-between bg-gray-50 p-3 rounded-md border border-gray-200 page-item', `page-item-${index}`]" 
                                             :data-aos="'fade-up'" 
                                             :data-aos-delay="parseInt(index) * 50"
                                             :style="{ '--delay': element.animationDelay || '0s' }">
                                            <div class="flex items-center">
                                                <!-- Drag handle -->
                                                <div class="drag-handle cursor-move p-2 mr-2 text-gray-500 hover:text-gray-700">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                                                    </svg>
                                                </div>
                                                
                                                <!-- Page number and thumbnail -->
                                                <div class="flex items-center">
                                                    <div class="w-10 h-10 bg-gray-100 flex items-center justify-center rounded mr-3 page-number">
                                                        <span class="text-gray-600 text-sm font-medium">{{ index + 1 }}</span>
                                                    </div>
                                                    
                                                    <!-- PDF Page Thumbnail -->
                                                    <div :class="['w-20 h-28 flex items-center justify-center mr-3 relative bg-gray-100 border border-gray-300 rounded overflow-hidden thumbnail-container', `thumbnail-container-${index}`]">
                                                        <template v-if="element.thumbnail">
                                                            <div v-if="element.isLoading" class="absolute inset-0 flex items-center justify-center bg-gray-100 bg-opacity-70 z-10">
                                                                <div class="w-full h-1 bg-gray-200 rounded-full overflow-hidden">
                                                                    <div class="h-full bg-blue-500 transition-all duration-300" :style="{ width: `${element.loadProgress || 0}%` }"></div>
                                                                </div>
                                                            </div>
                                                            <img 
                                                                :src="element.thumbnail" 
                                                                alt="Page thumbnail" 
                                                                class="object-contain w-full h-full thumbnail-rotate" 
                                                                :style="{ transform: `rotate(${element.rotation}deg)` }"
                                                                @error="handleImageError($event, element)"
                                                            />
                                                        </template>
                                                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                        </svg>
                                                    </div>
                                                    
                                                    <span class="text-gray-700">صفحة {{ element.number }}</span>
                                                    
                                                    <!-- Rotation indicator -->
                                                    <span v-if="element.rotation" class="mr-2 text-blue-600 text-sm">
                                                        ({{ element.rotation }}° دوران)
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            <!-- Actions -->
                                            <div class="flex space-x-1 space-x-reverse">
                                                <!-- Move Up -->
                                                <button 
                                                    @click="movePageUp(index)"
                                                    :disabled="index === 0"
                                                    class="p-1.5 text-gray-500 hover:bg-gray-200 rounded-md disabled:opacity-50 disabled:cursor-not-allowed page-action-btn"
                                                    title="نقل لأعلى"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                                    </svg>
                                                </button>
                                                
                                                <!-- Move Down -->
                                                <button 
                                                    @click="movePageDown(index)"
                                                    :disabled="index === previewPages.length - 1"
                                                    class="p-1.5 text-gray-500 hover:bg-gray-200 rounded-md disabled:opacity-50 disabled:cursor-not-allowed page-action-btn"
                                                    title="نقل لأسفل"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                                    </svg>
                                                </button>
                                                
                                                <!-- Rotate -->
                                                <button 
                                                    @click="rotatePage(index)"
                                                    class="p-1.5 text-gray-500 hover:bg-gray-200 rounded-md page-action-btn"
                                                    title="تدوير 90 درجة"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                </button>
                                                
                                                <!-- Delete -->
                                                <button 
                                                    @click="deletePage(index)"
                                                    class="p-1.5 text-red-500 hover:bg-red-100 rounded-md page-action-btn"
                                                    title="حذف"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                </draggable>
                            </div>
                        </div>
                        
                        <!-- Actions -->
                        <div class="flex justify-center mt-8">
                            <button 
                                @click="processPdf" 
                                class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none disabled:opacity-50 page-action-btn"
                                :disabled="!hasFile || isProcessing || previewPages.length === 0 || !apiStatus.ready || isUploading"
                            >
                                <span v-if="isProcessing" class="flex items-center">
                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white loading-spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    جاري المعالجة...
                                </span>
                                <span v-else>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-1 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    تنظيم الصفحات
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Instructions -->
                <div class="mt-8 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6" data-aos="fade-up" data-aos-delay="300">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">كيفية تنظيم صفحات ملف PDF</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="p-4 rounded-lg bg-blue-50 transition-all duration-300 hover:shadow-md hover:-translate-y-1">
                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mb-3">
                                <span class="text-blue-600 font-semibold">1</span>
                            </div>
                            <h4 class="font-medium mb-2">تحميل الملف</h4>
                            <p class="text-gray-600 text-sm">قم بتحميل ملف PDF لتنظيم صفحاته.</p>
                        </div>
                        <div class="p-4 rounded-lg bg-blue-50 transition-all duration-300 hover:shadow-md hover:-translate-y-1">
                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mb-3">
                                <span class="text-blue-600 font-semibold">2</span>
                            </div>
                            <h4 class="font-medium mb-2">إعادة ترتيب وتدوير</h4>
                            <p class="text-gray-600 text-sm">اسحب وأفلت الصفحات لإعادة ترتيبها، واستخدم الأزرار لتدويرها أو حذفها.</p>
                        </div>
                        <div class="p-4 rounded-lg bg-blue-50 transition-all duration-300 hover:shadow-md hover:-translate-y-1">
                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mb-3">
                                <span class="text-blue-600 font-semibold">3</span>
                            </div>
                            <h4 class="font-medium mb-2">المعالجة والتنزيل</h4>
                            <p class="text-gray-600 text-sm">انقر على "تنظيم الصفحات" لمعالجة الملف وتنزيل النسخة المنظمة.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Enhanced styles for the PDF preview */
.drag-handle {
    cursor: move;
    cursor: -webkit-grab;
    cursor: grab;
    transition: transform 0.2s ease, color 0.2s ease;
}

.drag-handle:hover {
    color: #3b82f6;
    transform: scale(1.1);
}

.drag-handle:active {
    cursor: -webkit-grabbing;
    cursor: grabbing;
}

.min-h-180 {
    min-height: 180px;
}

/* PDF Page Item Animation */
.sortable-drag {
    opacity: 0.8;
    transform: scale(1.05);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    background-color: #edf2f7;
    z-index: 10;
}

.sortable-ghost {
    background-color: #ebf5ff !important;
    border: 2px dashed #3b82f6 !important;
    opacity: 0.5;
}

/* Button animations and styling */
.page-action-btn {
    transition: all 0.2s ease;
    position: relative;
    overflow: hidden;
}

.page-action-btn:hover:not(:disabled) {
    background-color: #dbeafe;
    color: #2563eb;
    transform: translateY(-2px);
}

.page-action-btn:active:not(:disabled) {
    transform: translateY(0);
}

/* Rotation animation */
.thumbnail-rotate {
    transition: transform 0.5s cubic-bezier(0.68, -0.55, 0.27, 1.55);
}

/* Page item styling */
.page-item {
    transition: all 0.3s ease;
    position: relative;
    border: 1px solid #e5e7eb;
}

.page-item:hover {
    border-color: #3b82f6;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    transform: translateY(-2px);
}

/* Thumbnail container styling */
.thumbnail-container {
    background: linear-gradient(45deg, #f9fafb 25%, #f3f4f6 25%, #f3f4f6 50%, #f9fafb 50%, #f9fafb 75%, #f3f4f6 75%, #f3f4f6 100%);
    background-size: 20px 20px;
    box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
}

.thumbnail-container img {
    transition: transform 0.5s cubic-bezier(0.68, -0.55, 0.27, 1.55);
}

/* Page number styles */
.page-number {
    background: #3b82f6;
    color: white;
    transition: all 0.3s ease;
    border-radius: 9999px;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
}

.page-item:hover .page-number {
    transform: scale(1.1);
}

/* Delete animation */
.page-delete-enter-active, 
.page-delete-leave-active {
    transition: all 0.3s ease;
}

.page-delete-enter-from, 
.page-delete-leave-to {
    opacity: 0;
    transform: translateX(30px);
}

@keyframes rotate360 {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.rotate-anim {
    animation: rotate360 0.5s cubic-bezier(0.68, -0.55, 0.27, 1.55);
}

/* Loading spinner animation */
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.loading-spinner {
    animation: spin 1.5s linear infinite;
}
</style>