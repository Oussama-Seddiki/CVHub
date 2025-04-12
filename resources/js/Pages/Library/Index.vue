<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import SubscriptionWarning from '@/components/SubscriptionWarning.vue';

const props = defineProps({
    activeSubscription: {
        type: Boolean,
        required: true,
    },
    subscriptionStatus: {
        type: String,
        required: true
    },
    subscriptionEndsAt: {
        type: String,
        required: false
    },
    documents: Array,
    categories: Array
});

// Destructure props to make activeSubscription directly accessible
const { activeSubscription, subscriptionStatus, subscriptionEndsAt } = props;

const categories = ref([
    { id: 'all', name: 'الكل', count: 0 },
    { id: 'documents', name: 'وثائق رسمية', count: 0 },
    { id: 'studies', name: 'مذكرات دراسية', count: 0 },
    { id: 'templates', name: 'نماذج وقوالب', count: 0 },
    { id: 'guides', name: 'أدلة وكتيبات', count: 0 }
]);

const activeCategory = ref('all');
const selectedCategory = ref('all');

const searchQuery = ref('');

const filteredDocuments = computed(() => {
    // تصفية حسب الفئة
    let filtered = activeCategory.value === 'all' 
        ? props.documents 
        : props.documents.filter(doc => doc.category === activeCategory.value);
    
    // تصفية حسب البحث إذا كان هناك عبارة بحث
    if (searchQuery.value.trim()) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(doc => 
            doc.title.toLowerCase().includes(query) || 
            doc.description.toLowerCase().includes(query)
        );
    }
    
    return filtered;
});

// حساب عدد العناصر في كل فئة
onMounted(() => {
    categories.value.forEach(category => {
        if (category.id === 'all') {
            category.count = props.documents.length;
        } else {
            category.count = props.documents.filter(doc => doc.category === category.id).length;
        }
    });
});

function setCategory(categoryId) {
    activeCategory.value = categoryId;
    selectedCategory.value = categoryId;
}

function selectCategory(categoryId) {
    activeCategory.value = categoryId;
    selectedCategory.value = categoryId;
}

function handleSearch() {
    console.log('Searching for:', searchQuery.value);
    // البحث يتم تلقائيًا من خلال computed property
}

function downloadDocument(doc) {
    if (!activeSubscription) {
        alert('عذرًا، هذه الخدمة متاحة فقط للمشتركين. يرجى الاشتراك للاستفادة من الخدمة.');
        return;
    }
    
    // هنا سيتم إرسال طلب التنزيل إلى Scribd API
    window.open(doc.downloadUrl, '_blank');
}

const formatDate = (dateString) => {
    if (!dateString) return 'غير محدد';
    
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) {
            return 'غير محدد';
        }
        return date.toLocaleDateString('ar-DZ');
    } catch (e) {
        return 'غير محدد';
    }
};

function formatFileSize(size) {
    if (!size) return 'غير محدد';
    
    try {
        // Just return the size string for now
        return size;
    } catch (e) {
        return 'غير محدد';
    }
}

// Add function to check subscription before downloading
function checkSubscriptionBeforeDownload(document) {
    if (!props.activeSubscription) {
        // Show subscription required message
        alert('تحميل المستندات متاح للمشتركين فقط. يرجى الاشتراك للاستفادة من جميع المميزات.');
        return false;
    }
    return true;
}
</script>

<template>
    <Head title="المكتبة" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">المكتبة</h2>
        </template>

        <div class="py-12 rtl">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Subscription Warning -->
                <SubscriptionWarning v-if="!activeSubscription" />

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-medium text-gray-900">مكتبة المستندات</h3>
                        
                        <div v-if="!activeSubscription" class="flex items-center text-sm text-indigo-600">
                            <svg class="h-5 w-5 mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            <span>يلزم الاشتراك لتحميل المستندات</span>
                        </div>
                    </div>

                    <!-- Filter Categories -->
                    <div class="flex flex-wrap gap-2 mb-6">
                        <button
                            v-for="category in categories"
                            :key="category.id"
                            @click="selectedCategory = category.id"
                            :class="[
                                'px-4 py-2 rounded-full text-sm font-medium',
                                selectedCategory === category.id
                                    ? 'bg-indigo-100 text-indigo-800'
                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                            ]"
                        >
                            {{ category.name }}
                        </button>
                    </div>

                    <!-- Documents Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div
                            v-for="document in filteredDocuments"
                            :key="document.id"
                            class="border rounded-lg overflow-hidden hover:shadow-md transition-shadow duration-200"
                            :class="{ 'opacity-75': !activeSubscription }"
                        >
                            <div class="relative h-48 bg-gray-100">
                                <img
                                    :src="document.thumbnail"
                                    :alt="document.title"
                                    class="w-full h-full object-cover"
                                />
                                <div class="absolute top-2 right-2 px-2 py-1 text-xs bg-slate-700 text-white font-semibold rounded-full">
                                    {{ document.type }}
                                </div>
                            </div>
                            <div class="p-4">
                                <h4 class="font-medium text-gray-900 mb-1">{{ document.title }}</h4>
                                <p class="text-sm text-gray-600 mb-3">{{ document.description }}</p>
                                <div class="flex justify-between text-xs text-gray-500 mb-4">
                                    <span>{{ document.size }}</span>
                                    <span>{{ document.pages }} صفحة</span>
                                </div>
                                <button
                                    @click="() => { if (checkSubscriptionBeforeDownload(document)) { window.location.href = document.downloadUrl } }"
                                    class="w-full px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-700 hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                    :class="{ 'cursor-not-allowed': !activeSubscription }"
                                >
                                    <span v-if="activeSubscription">تحميل</span>
                                    <span v-else>تحميل (يتطلب اشتراك)</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-if="filteredDocuments.length === 0" class="text-center py-12">
                        <svg class="h-12 w-12 text-gray-400 mx-auto mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 mb-1">لا توجد مستندات</h3>
                        <p class="text-gray-500">لا توجد مستندات متاحة في هذه الفئة حالياً.</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template> 