<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed, onMounted } from 'vue';
import PdfService from '@/services/PdfService.js';
import PdfProcessor from '@/components/PdfProcessor.vue';
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

// Use the activeSubscription prop directly
const hasActiveSubscription = computed(() => props.activeSubscription);

// PDF tool categories
const toolCategories = [
    {
        id: 'organize',
        name: 'تنظيم ملفات PDF',
        tools: [
    {
        id: 'merge',
        name: 'دمج ملفات PDF',
        description: 'دمج عدة ملفات PDF في ملف واحد',
        icon: 'M8 3a.5.5 0 01.5.5v2a.5.5 0 01-.5.5H5.5A1.5 1.5 0 004 7.5v7a1.5 1.5 0 001.5 1.5h7a1.5 1.5 0 001.5-1.5v-7A1.5 1.5 0 0012.5 6H10a.5.5 0 01-.5-.5v-2A.5.5 0 0110 3h2.5A2.5 2.5 0 0115 5.5v7a2.5 2.5 0 01-2.5 2.5h-7A2.5 2.5 0 013 12.5v-7A2.5 2.5 0 015.5 3H8z',
                color: 'emerald',
                route: '/file-processing/merge'
    },
    {
        id: 'split',
        name: 'تقسيم ملف PDF',
        description: 'تقسيم ملف PDF إلى عدة ملفات',
        icon: 'M2 12h5v4H2v-4zm11-4h5v8h-5V8zm-6 4V8h5v4H7z',
                color: 'purple',
                route: '/file-processing/split'
            },
            {
                id: 'remove-pages',
                name: 'حذف صفحات',
                description: 'حذف صفحات محددة من ملف PDF',
                icon: 'M6 18L18 6M6 6l12 12',
                color: 'red',
                route: '/file-processing/remove-pages'
            },
            {
                id: 'extract-pages',
                name: 'استخراج صفحات',
                description: 'استخراج صفحات محددة من ملف PDF',
                icon: 'M11 17l-5-5m0 0l5-5m-5 5h12',
                color: 'blue',
                route: '/file-processing/extract-pages'
            },
            {
                id: 'organize',
                name: 'تنظيم صفحات',
                description: 'إعادة ترتيب صفحات ملف PDF',
                icon: 'M4 6h16M4 10h16M4 14h16M4 18h16',
                color: 'yellow',
                route: '/file-processing/organize-pages'
            }
        ]
    },
    {
        id: 'optimize',
        name: 'تحسين ملفات PDF',
        tools: [
            {
                id: 'compress',
                name: 'ضغط ملف PDF',
                description: 'تقليل حجم ملف PDF مع الحفاظ على الجودة',
                icon: 'M9 12H5.5a.5.5 0 01-.5-.5v-2a.5.5 0 01.5-.5H9v3zm0-4H4.5a.5.5 0 00-.5.5v3a.5.5 0 00.5.5H9V8zm1 4v-4h4.5a.5.5 0 01.5.5v3a.5.5 0 01-.5.5H10z',
                color: 'indigo',
                route: '/file-processing/compress'
    },
    {
        id: 'ocr',
                name: 'تحويل النص',
                description: 'تحويل PDF إلى نص قابل للبحث والنسخ',
        icon: 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4',
                color: 'green',
                route: '/file-processing/ocr'
            }
        ]
    },
    {
        id: 'convert-to',
        name: 'تحويل إلى PDF',
        tools: [
            {
                id: 'jpg-to-pdf',
                name: 'JPG إلى PDF',
                description: 'تحويل صور JPG إلى ملف PDF',
                icon: 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
                color: 'orange',
                route: '/file-processing/jpg-to-pdf'
            },
            {
                id: 'word-to-pdf',
                name: 'WORD إلى PDF',
                description: 'تحويل ملفات Word إلى PDF',
                icon: 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z',
                color: 'blue',
                route: '/file-processing/word-to-pdf'
            },
            {
                id: 'ppt-to-pdf',
                name: 'PPT إلى PDF',
                description: 'تحويل عروض PowerPoint إلى PDF',
                icon: 'M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z',
                color: 'orange',
                route: '/file-processing/ppt-to-pdf'
            },
            {
                id: 'excel-to-pdf',
                name: 'EXCEL إلى PDF',
                description: 'تحويل جداول Excel إلى PDF',
                icon: 'M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z',
                color: 'green',
                route: '/file-processing/excel-to-pdf'
            }
        ]
    },
    {
        id: 'convert-from',
        name: 'تحويل من PDF',
        tools: [
            {
                id: 'pdf-to-jpg',
                name: 'PDF إلى JPG',
                description: 'تحويل صفحات PDF إلى صور JPG',
                icon: 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
                color: 'orange',
                route: '/file-processing/pdf-to-jpg'
            },
            {
                id: 'pdf-to-word',
                name: 'PDF إلى WORD',
                description: 'تحويل PDF إلى مستندات Word',
                icon: 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z',
                color: 'blue',
                route: '/file-processing/pdf-to-word'
            },
            {
                id: 'pdf-to-ppt',
                name: 'PDF إلى PPT',
                description: 'تحويل PDF إلى عروض PowerPoint',
                icon: 'M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z',
                color: 'orange',
                route: '/file-processing/pdf-to-ppt'
            },
            {
                id: 'pdf-to-excel',
                name: 'PDF إلى EXCEL',
                description: 'تحويل PDF إلى جداول Excel',
                icon: 'M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z',
                color: 'green',
                route: '/file-processing/pdf-to-excel'
            }
        ]
    },
    {
        id: 'edit',
        name: 'تحرير PDF',
        tools: [
            {
                id: 'rotate',
                name: 'تدوير PDF',
                description: 'تدوير صفحات PDF',
                icon: 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15',
                color: 'blue',
                route: '/file-processing/rotate'
            },
            {
                id: 'add-page-numbers',
                name: 'إضافة أرقام الصفحات',
                description: 'إضافة أرقام الصفحات إلى ملف PDF',
                icon: 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z',
                color: 'purple',
                route: '/file-processing/add-page-numbers'
            },
            {
                id: 'add-watermark',
                name: 'إضافة علامة مائية',
                description: 'إضافة علامة مائية إلى PDF',
                icon: 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z',
                color: 'indigo',
                route: '/file-processing/add-watermark'
            }
        ]
    },
    {
        id: 'security',
        name: 'أمان PDF',
        tools: [
            {
                id: 'protect',
                name: 'حماية PDF',
                description: 'حماية PDF بكلمة مرور',
                icon: 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z',
                color: 'red',
                route: '/file-processing/protect'
            },
            {
                id: 'unlock',
                name: 'فك حماية PDF',
                description: 'إزالة كلمة المرور من PDF',
                icon: 'M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z',
                color: 'green',
                route: '/file-processing/unlock'
            }
        ]
    }
];

// Categories for the grid view layout
const categories = ref([
    { id: 'basic', name: 'العمليات الأساسية', expanded: true },
    { id: 'pages', name: 'صفحات PDF', expanded: true }
]);

// Function to navigate to tool page
function navigateToTool(tool) {
    if (!hasActiveSubscription.value) {
        alert('هذه الخدمة متاحة فقط للمستخدمين المشتركين. يرجى الاشتراك للاستفادة من هذه الميزة.');
        return;
    }
    
    // Navigate to the tool page using router
    router.visit(tool.route);
}

const showSubscriptionWarning = ref(false);
const selectedCategory = ref(null);

// API status information
const apiStatus = ref({
    ready: true,
    loading: false,
    message: 'معالجة PDF جاهزة للاستخدام باستخدام مكتبات جانب العميل'
});

// List of tool IDs that are free to use
const freeTools = ['merge', 'split', 'extract-pages'];

// Handle tool click 
function handleToolClick(tool) {
    if (!hasActiveSubscription.value && !freeTools.includes(tool.id)) {
        showSubscriptionWarning.value = true;
        return;
    }
    
    router.visit(tool.route);
}

async function checkApiStatus() {
    try {
        // Always report as ready since we're using client-side processing
        apiStatus.value = {
            ready: true,
            loading: false,
            message: 'معالجة PDF جاهزة للاستخدام باستخدام مكتبات جانب العميل'
        };
    } catch (error) {
        console.error('Error checking status:', error);
    }
}

onMounted(() => {
    checkApiStatus();
});
</script>

<template>
    <Head title="معالجة الملفات" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">معالجة الملفات</h2>
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
                <div class="mb-6 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6" :class="{'text-gray-900': !apiStatus.loading, 'text-gray-600': apiStatus.loading}">
                        <div class="flex items-center">
                            <div v-if="apiStatus.loading" class="animate-spin mr-3 h-5 w-5 text-gray-600">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                            <div v-else-if="apiStatus.ready" class="mr-3 h-5 w-5 text-green-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div v-else class="mr-3 h-5 w-5 text-red-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div>{{ apiStatus.message }}</div>
                        </div>
                    </div>
                </div>

                <!-- Hero Section -->
                <div class="mb-8 bg-gradient-to-r from-blue-700 to-indigo-900 text-white overflow-hidden shadow-lg sm:rounded-lg">
                    <div class="p-8 text-center">
                        <h1 class="text-3xl font-bold mb-4">جميع أدوات PDF في مكان واحد</h1>
                        <p class="text-xl mb-6">
                            كل الأدوات التي تحتاجها للعمل مع ملفات PDF، في متناول يدك. جميعها سهلة الاستخدام!
                        </p>
                    </div>
                </div>

                <!-- Tool Categories -->
                <div v-for="category in toolCategories" :key="category.id" class="mb-8">
                    <h2 class="text-2xl font-bold mb-4 px-4 sm:px-0">{{ category.name }}</h2>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                        <div 
                            v-for="tool in category.tools" 
                            :key="tool.id"
                            class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow duration-300 overflow-hidden cursor-pointer"
                            @click="handleToolClick(tool)"
                        >
                            <div class="p-6">
                                <div class="flex items-center mb-4">
                                    <div :class="`text-${tool.color}-500 bg-${tool.color}-100 p-3 rounded-full mr-4`">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="tool.icon" />
                                </svg>
                                    </div>
                                    <h3 class="text-lg font-medium text-gray-900">{{ tool.name }}</h3>
                                </div>
                                <p class="text-gray-600">{{ tool.description }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PDF Basic Operations tools row -->
                <div v-if="categories[0].expanded" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mt-4">
                    <Link href="/file-processing/merge" class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-shadow flex flex-col items-center justify-center text-center">
                        <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                </svg>
                        </div>
                        <h3 class="text-lg font-medium mb-1">دمج ملفات PDF</h3>
                        <p class="text-sm text-gray-500">دمج عدة ملفات PDF في ملف واحد</p>
                    </Link>
                    
                    <Link href="/file-processing/split" class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-shadow flex flex-col items-center justify-center text-center">
                        <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                            </svg>
                    </div>
                        <h3 class="text-lg font-medium mb-1">تقسيم ملف PDF</h3>
                        <p class="text-sm text-gray-500">تقسيم ملف PDF إلى عدة ملفات</p>
                    </Link>
                    
                    <Link href="/file-processing/compress-pdf" class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-shadow flex flex-col items-center justify-center text-center">
                        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                </svg>
                        </div>
                        <h3 class="text-lg font-medium mb-1">ضغط ملف PDF</h3>
                        <p class="text-sm text-gray-500">تقليل حجم ملف PDF مع الحفاظ على الجودة</p>
                                </Link>
                            </div>

                <!-- PDF Pages tools row -->
                <div v-if="categories[1].expanded" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mt-4">
                    <Link href="/file-processing/remove-pages" class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-shadow flex flex-col items-center justify-center text-center">
                        <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium mb-1">حذف صفحات</h3>
                        <p class="text-sm text-gray-500">حذف صفحات محددة من ملف PDF</p>
                    </Link>
                    
                    <Link href="/file-processing/extract-pages" class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-shadow flex flex-col items-center justify-center text-center">
                        <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium mb-1">استخراج صفحات</h3>
                        <p class="text-sm text-gray-500">استخراج صفحات محددة من ملف PDF</p>
                    </Link>
                    
                    <Link href="/file-processing/organize-pages" class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-shadow flex flex-col items-center justify-center text-center">
                        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                            </svg>
                    </div>
                        <h3 class="text-lg font-medium mb-1">تنظيم الصفحات</h3>
                        <p class="text-sm text-gray-500">إعادة ترتيب، تدوير أو حذف صفحات</p>
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template> 

<style scoped>
/* Add styles for colored backgrounds, making sure they're properly applied */
.text-emerald-500 { color: rgb(16, 185, 129); }
.bg-emerald-100 { background-color: rgb(209, 250, 229); }

.text-purple-500 { color: rgb(168, 85, 247); }
.bg-purple-100 { background-color: rgb(243, 232, 255); }

.text-red-500 { color: rgb(239, 68, 68); }
.bg-red-100 { background-color: rgb(254, 226, 226); }

.text-blue-500 { color: rgb(59, 130, 246); }
.bg-blue-100 { background-color: rgb(219, 234, 254); }

.text-yellow-500 { color: rgb(245, 158, 11); }
.bg-yellow-100 { background-color: rgb(254, 243, 199); }

.text-indigo-500 { color: rgb(99, 102, 241); }
.bg-indigo-100 { background-color: rgb(224, 231, 255); }

.text-green-500 { color: rgb(16, 185, 129); }
.bg-green-100 { background-color: rgb(209, 250, 229); }

.text-orange-500 { color: rgb(249, 115, 22); }
.bg-orange-100 { background-color: rgb(255, 237, 213); }
</style> 