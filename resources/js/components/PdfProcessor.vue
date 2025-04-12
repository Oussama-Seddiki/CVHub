<template>
    <div class="pdf-processor">
        <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
            <h3 class="text-lg font-medium text-gray-900 mb-4">{{ title }}</h3>
            
            <!-- Tool Selection -->
            <div v-if="!selectedTool && tools.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                <div 
                    v-for="tool in availableTools" 
                    :key="tool.id"
                    @click="selectTool(tool)"
                    class="border rounded-lg p-4 cursor-pointer hover:shadow-md transition-shadow duration-200"
                    :class="{'border-blue-500 bg-blue-50': false, 'border-gray-300': true, 'opacity-50 cursor-not-allowed': !tool.available}"
                >
                    <div class="flex items-center">
                        <div class="text-blue-600 mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getIconPath(tool.id)" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="font-medium">{{ tool.name }}</h4>
                            <p class="text-sm text-gray-600">{{ tool.description }}</p>
                            <p v-if="!tool.available" class="text-xs text-red-500 mt-1">
                                غير متوفر حالياً
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Loading -->
            <div v-if="loading" class="flex justify-center items-center py-10">
                <svg class="animate-spin h-8 w-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="ml-3 text-gray-700">جاري التحميل...</span>
            </div>
            
            <!-- Tool Form -->
            <div v-if="selectedTool && !processing" class="border rounded-lg p-4">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <div class="text-blue-600 mr-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getIconPath(selectedTool.id)" />
                            </svg>
                        </div>
                        <h4 class="font-medium">{{ selectedTool.name }}</h4>
                    </div>
                    <button @click="resetTool" class="text-sm text-gray-600 hover:text-gray-900">
                        تغيير الأداة
                    </button>
                </div>
                
                <div class="space-y-4">
                    <!-- Merge PDFs -->
                    <div v-if="selectedTool.id === 'merge_pdf'">
                        <p class="text-gray-700 mb-4">{{ selectedTool.description }}</p>
                        <input 
                            type="file"
                            ref="fileInput"
                            accept=".pdf"
                            multiple
                            class="block w-full text-sm text-gray-900 border border-gray-300 rounded-md cursor-pointer bg-gray-50 focus:outline-none mb-4"
                            @change="handleFileChange"
                        />
                        <div class="flex flex-col space-y-2 mb-4">
                            <input 
                                type="text" 
                                v-model="metadata.title" 
                                placeholder="عنوان الملف (اختياري)" 
                                class="p-2 border border-gray-300 rounded-md"
                            />
                            <input 
                                type="text" 
                                v-model="metadata.author" 
                                placeholder="المؤلف (اختياري)" 
                                class="p-2 border border-gray-300 rounded-md"
                            />
                        </div>
                    </div>
                    
                    <!-- Extract Pages -->
                    <div v-if="selectedTool.id === 'extract_pages'">
                        <p class="text-gray-700 mb-4">{{ selectedTool.description }}</p>
                        <input 
                            type="file"
                            ref="fileInput"
                            accept=".pdf"
                            class="block w-full text-sm text-gray-900 border border-gray-300 rounded-md cursor-pointer bg-gray-50 focus:outline-none mb-4"
                            @change="handleFileChange"
                        />
                        <input 
                            type="text" 
                            v-model="pages" 
                            placeholder="الصفحات (مثال: 1,3-5,7)" 
                            class="p-2 border border-gray-300 rounded-md w-full mb-4"
                        />
                        <div class="flex flex-col space-y-2 mb-4">
                            <input 
                                type="text" 
                                v-model="metadata.title" 
                                placeholder="عنوان الملف (اختياري)" 
                                class="p-2 border border-gray-300 rounded-md"
                            />
                            <input 
                                type="text" 
                                v-model="metadata.author" 
                                placeholder="المؤلف (اختياري)" 
                                class="p-2 border border-gray-300 rounded-md"
                            />
                        </div>
                    </div>
                    
                    <!-- Remove Pages -->
                    <div v-if="selectedTool.id === 'remove_pages'">
                        <p class="text-gray-700 mb-4">{{ selectedTool.description }}</p>
                        <input 
                            type="file"
                            ref="fileInput"
                            accept=".pdf"
                            class="block w-full text-sm text-gray-900 border border-gray-300 rounded-md cursor-pointer bg-gray-50 focus:outline-none mb-4"
                            @change="handleFileChange"
                        />
                        <input 
                            type="text" 
                            v-model="pages" 
                            placeholder="الصفحات للحذف (مثال: 1,3-5,7)" 
                            class="p-2 border border-gray-300 rounded-md w-full mb-4"
                        />
                        <div class="flex flex-col space-y-2 mb-4">
                            <input 
                                type="text" 
                                v-model="metadata.title" 
                                placeholder="عنوان الملف (اختياري)" 
                                class="p-2 border border-gray-300 rounded-md"
                            />
                            <input 
                                type="text" 
                                v-model="metadata.author" 
                                placeholder="المؤلف (اختياري)" 
                                class="p-2 border border-gray-300 rounded-md"
                            />
                        </div>
                    </div>
                    
                    <!-- Organize Pages -->
                    <div v-if="selectedTool.id === 'organize_pages'">
                        <p class="text-gray-700 mb-4">{{ selectedTool.description }}</p>
                        <input 
                            type="file"
                            ref="fileInput"
                            accept=".pdf"
                            class="block w-full text-sm text-gray-900 border border-gray-300 rounded-md cursor-pointer bg-gray-50 focus:outline-none mb-4"
                            @change="handleFileChange"
                        />
                        <p class="text-sm text-gray-600 mb-4">
                            يمكنك تنظيم صفحات PDF من خلال الانتقال إلى صفحة "إعادة ترتيب الصفحات" للحصول على واجهة تفاعلية كاملة.
                        </p>
                    </div>
                    
                    <div class="flex justify-center">
                        <button 
                            @click="processFile"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed"
                            :disabled="!isFormValid"
                        >
                            معالجة الملف
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Processing -->
            <div v-if="processing" class="flex flex-col items-center justify-center py-8">
                <svg class="animate-spin h-10 w-10 text-blue-500 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="text-gray-800">جاري معالجة الملف، يرجى الانتظار...</p>
            </div>
            
            <!-- Result -->
            <div v-if="result" class="border rounded-lg p-4 mt-4">
                <div class="flex items-center mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <h4 class="font-medium text-gray-900">تمت المعالجة بنجاح</h4>
                </div>
                
                <p class="text-gray-700 mb-4">{{ result.message }}</p>
                
                <div v-if="result.file || (result.data && result.data.output_url)" class="flex justify-center">
                    <a 
                        :href="result.file || result.data?.output_url" 
                        target="_blank"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                        :download="result.filename || 'processed-document.pdf'"
                    >
                        تحميل الملف
                    </a>
                </div>
            </div>
            
            <!-- Error -->
            <div v-if="error" class="border border-red-200 bg-red-50 rounded-lg p-4 mt-4">
                <div class="flex items-center mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h4 class="font-medium text-red-700">حدث خطأ</h4>
                </div>
                
                <p class="text-red-700">{{ error }}</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import PdfService from '../services/PdfService';

const props = defineProps({
    title: {
        type: String,
        default: 'معالجة ملفات PDF'
    }
});

const emit = defineEmits(['processed', 'error']);

// State variables
const tools = ref([]);
const selectedTool = ref(null);
const files = ref(null);
const fileInput = ref(null);
const loading = ref(true);
const processing = ref(false);
const result = ref(null);
const error = ref(null);

// Form values
const pages = ref('');
const format = ref('docx');
const metadata = ref({
    title: '',
    author: '',
    subject: '',
    keywords: ''
});

const ocrOptions = ref({
    language: 'eng',
    dpi: '300',
    textOnly: false
});

const protectionOptions = ref({
    userPassword: '',
    ownerPassword: '',
    keyLength: '128',
    restrictions: []
});

// Computed
const availableTools = computed(() => {
    return tools.value;
});

const isFormValid = computed(() => {
    if (!files.value || files.value.length === 0) {
        return false;
    }
    
    if (selectedTool.value.id === 'merge_pdf' && (!files.value || files.value.length < 2)) {
        return false;
    }
    
    if (selectedTool.value.id === 'extract_pages' && !pages.value) {
        return false;
    }
    
    return true;
});

// Methods
function getIconPath(toolId) {
    const icons = {
        'merge_pdf': 'M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2',
        'extract_pages': 'M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2 M12 18v-8 M9 15h6',
        'remove_pages': 'M6 18L18 6M6 6l12 12',
        'organize_pages': 'M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4',
        'preview': 'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z'
    };
    
    return icons[toolId] || 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z';
}

function selectTool(tool) {
    if (!tool.available) {
        return;
    }
    
    selectedTool.value = tool;
    result.value = null;
    error.value = null;
}

function resetTool() {
    selectedTool.value = null;
    files.value = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
    result.value = null;
    error.value = null;
}

function handleFileChange(event) {
    files.value = event.target.files;
}

async function fetchTools() {
    try {
        loading.value = true;
        const response = await PdfService.getAvailableTools();
        
        // Define available tools based on the response
        const availableTools = [
            {
                id: 'merge_pdf',
                name: 'دمج ملفات PDF',
                description: 'دمج عدة ملفات PDF في ملف واحد',
                available: response.tools?.includes('merge')
            },
            {
                id: 'extract_pages',
                name: 'استخراج صفحات',
                description: 'استخراج صفحات محددة من ملف PDF',
                available: response.tools?.includes('extract')
            },
            {
                id: 'remove_pages',
                name: 'حذف صفحات',
                description: 'حذف صفحات محددة من ملف PDF',
                available: response.tools?.includes('remove')
            },
            {
                id: 'organize_pages',
                name: 'إعادة ترتيب الصفحات',
                description: 'تغيير ترتيب الصفحات وتدويرها',
                available: response.tools?.includes('organize')
            }
        ];
        
        tools.value = availableTools;
    } catch (err) {
        console.error('Error loading PDF tools:', err);
        error.value = 'فشل في تحميل أدوات معالجة PDF';
    } finally {
        loading.value = false;
    }
}

async function processFile() {
    if (!isFormValid.value) {
        return;
    }
    
    processing.value = true;
    result.value = null;
    error.value = null;
    
    try {
        let response;
        
        switch (selectedTool.value.id) {
            case 'merge_pdf':
                response = await PdfService.processPdf(files.value[0], 'merge', {
                    files: files.value,
                    metadata: metadata.value
                });
                break;
                
            case 'extract_pages':
                response = await PdfService.processPdf(files.value[0], 'extract', {
                    pages: pages.value,
                    metadata: metadata.value
                });
                break;
                
            case 'remove_pages':
                response = await PdfService.processPdf(files.value[0], 'remove', {
                    pages: pages.value,
                    metadata: metadata.value
                });
                break;
                
            case 'organize_pages':
                // For organize, we would need to create the pageList from UI interactions
                response = await PdfService.processPdf(files.value[0], 'organize', {
                    pageList: [] // This would need to be populated based on UI
                });
                break;
                
            default:
                throw new Error('أداة غير مدعومة');
        }
        
        if (response.success) {
            result.value = response;
            emit('processed', response);
        } else {
            throw new Error(response.message || 'حدث خطأ غير معروف');
        }
    } catch (err) {
        console.error('Error processing file:', err);
        error.value = err.message || 'فشل في معالجة الملف';
        emit('error', error.value);
    } finally {
        processing.value = false;
    }
}

// Fetch available tools on component mount
onMounted(() => {
    fetchTools();
});
</script> 