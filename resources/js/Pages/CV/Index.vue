<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
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
    }
});

const templates = ref([
    {
        id: 1, 
        name: 'الأساسي',
        image: '/img/templates/basic.jpg',
        description: 'قالب بسيط وأنيق مناسب لجميع المجالات',
        isPremium: false
    },
    {
        id: 2, 
        name: 'الحديث',
        image: '/img/templates/modern.jpg',
        description: 'قالب عصري ومميز مع تصميم جذاب',
        isPremium: true
    },
    {
        id: 3, 
        name: 'الاحترافي',
        image: '/img/templates/professional.jpg',
        description: 'قالب ملائم للمناصب العليا والخبرات المتقدمة',
        isPremium: true
    },
    {
        id: 4, 
        name: 'الابداعي',
        image: '/img/templates/creative.jpg',
        description: 'قالب إبداعي مميز للمجالات الإبداعية',
        isPremium: true
    }
]);

const selectedTemplate = ref(null);
const resumeCreationStep = ref(1);

const cvData = ref({
    personalInfo: {
        name: '',
        email: '',
        phone: '',
        address: '',
        about: ''
    },
    education: [],
    experience: [],
    skills: [],
    languages: []
});

const newEducation = ref({
    institution: '',
    degree: '',
    field: '',
    startDate: '',
    endDate: '',
    description: ''
});

const newExperience = ref({
    company: '',
    position: '',
    startDate: '',
    endDate: '',
    current: false,
    description: ''
});

const newSkill = ref('');
const newLanguage = ref({
    name: '',
    level: 'متوسط'
});

function selectTemplate(template) {
    selectedTemplate.value = template;
    resumeCreationStep.value = 2;
}

function addEducation() {
    cvData.value.education.push({...newEducation.value});
    // إعادة تعيين النموذج
    newEducation.value = {
        institution: '',
        degree: '',
        field: '',
        startDate: '',
        endDate: '',
        description: ''
    };
}

function removeEducation(index) {
    cvData.value.education.splice(index, 1);
}

function addExperience() {
    cvData.value.experience.push({...newExperience.value});
    // إعادة تعيين النموذج
    newExperience.value = {
        company: '',
        position: '',
        startDate: '',
        endDate: '',
        current: false,
        description: ''
    };
}

function removeExperience(index) {
    cvData.value.experience.splice(index, 1);
}

function addSkill() {
    if (newSkill.value.trim()) {
        cvData.value.skills.push(newSkill.value);
        newSkill.value = '';
    }
}

function removeSkill(index) {
    cvData.value.skills.splice(index, 1);
}

function addLanguage() {
    if (newLanguage.value.name.trim()) {
        cvData.value.languages.push({...newLanguage.value});
        newLanguage.value = {
            name: '',
            level: 'متوسط'
        };
    }
}

function removeLanguage(index) {
    cvData.value.languages.splice(index, 1);
}

function nextStep() {
    resumeCreationStep.value++;
}

function prevStep() {
    resumeCreationStep.value--;
}

function generateResume() {
    // هنا سيتم إرسال البيانات إلى API لإنشاء السيرة الذاتية
    console.log('Generating Resume with template:', selectedTemplate.value);
    console.log('CV Data:', cvData.value);
    
    // تحويل إلى صفحة المعاينة
    resumeCreationStep.value = 5;
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

// Add a computed property to check if current template requires subscription
const requiresSubscription = computed(() => {
    return selectedTemplate.value?.isPremium && !props.activeSubscription;
});
</script>

<template>
    <Head title="إنشاء سيرة ذاتية" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">إنشاء سيرة ذاتية</h2>
        </template>

        <div class="py-12 rtl">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Subscription Warning -->
                <SubscriptionWarning v-if="!activeSubscription" />

                <!-- Step 1: Template Selection -->
                <div v-if="resumeCreationStep === 1" class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">اختر قالب السيرة الذاتية</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div v-for="template in templates" :key="template.id" class="border rounded-lg overflow-hidden hover:shadow-md transition-shadow duration-200">
                            <div class="relative h-48 bg-gray-100">
                                <img :src="template.image" :alt="template.name" class="w-full h-full object-cover">
                                <div v-if="template.isPremium" class="absolute top-2 right-2 px-2 py-1 text-xs bg-purple-600 text-white font-semibold rounded-full">
                                    اشتراك
                                </div>
                            </div>
                            <div class="p-4">
                                <h4 class="font-medium text-gray-900">{{ template.name }}</h4>
                                <p class="text-sm text-gray-600 mt-1">{{ template.description }}</p>
                                <button 
                                    @click="selectTemplate(template)" 
                                    class="mt-3 px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-700 hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 w-full"
                                >
                                    {{ template.isPremium && !activeSubscription ? 'استعراض' : 'اختيار' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 2-5: CV Building Forms -->
                <div v-if="resumeCreationStep > 1" class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <!-- Premium Template Warning -->
                    <div v-if="requiresSubscription" class="mb-6 bg-indigo-50 border-l-4 border-indigo-400 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-indigo-500" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-indigo-700">
                                    هذا القالب متاح للمشتركين فقط. يمكنك معاينة القالب ولكن لن تتمكن من حفظ أو تصدير السيرة الذاتية.
                                    <Link href="/subscription" class="font-medium underline text-indigo-700 hover:text-indigo-600">اشترك الآن</Link> للاستفادة من جميع الميزات.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Steps Progress -->
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ selectedTemplate.name }} - خطوة {{ resumeCreationStep - 1 }} من 4</h3>
                        <div class="flex justify-between items-center">
                            <div class="space-x-1 rtl:space-x-reverse flex">
                                <button 
                                    v-for="step in 4" 
                                    :key="step"
                                    @click="resumeCreationStep = step + 1"
                                    :class="[
                                        'h-2 w-10 rounded-full transform transition-all',
                                        resumeCreationStep - 1 === step 
                                            ? 'bg-indigo-700' 
                                            : resumeCreationStep - 1 > step
                                                ? 'bg-indigo-300' 
                                                : 'bg-gray-200'
                                    ]"
                                ></button>
                            </div>
                            <div>
                                <span class="text-sm text-gray-500">{{ resumeCreationStep === 5 ? 'المعاينة' : 'الخطوة ' + (resumeCreationStep - 1) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Form Content -->
                    <!-- ... existing form content ... -->

                    <!-- Form Navigation -->
                    <div class="mt-6 flex justify-between">
                        <button 
                            v-if="resumeCreationStep > 2" 
                            @click="prevStep" 
                            class="px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        >
                            السابق
                        </button>
                        <div></div>
                        <button 
                            v-if="resumeCreationStep < 5" 
                            @click="nextStep" 
                            class="px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-700 hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            :disabled="requiresSubscription && resumeCreationStep === 4"
                            :class="{ 'opacity-50 cursor-not-allowed': requiresSubscription && resumeCreationStep === 4 }"
                        >
                            {{ resumeCreationStep === 4 ? 'إنشاء السيرة الذاتية' : 'التالي' }}
                        </button>
                    </div>
                </div>

                <!-- Resume Preview -->
                <div v-if="resumeCreationStep === 5" class="mt-6">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="flex justify-between mb-6">
                            <h3 class="text-lg font-medium text-gray-900">معاينة السيرة الذاتية</h3>
                            
                            <div class="flex space-x-4 rtl:space-x-reverse">
                                <button 
                                    @click="prevStep" 
                                    class="px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                >
                                    تعديل
                                </button>
                                <button 
                                    class="px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-700 hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                    :disabled="requiresSubscription"
                                    :class="{ 'opacity-50 cursor-not-allowed': requiresSubscription }"
                                >
                                    تحميل PDF
                                </button>
                                <button 
                                    class="px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500"
                                    :disabled="requiresSubscription"
                                    :class="{ 'opacity-50 cursor-not-allowed': requiresSubscription }"
                                >
                                    حفظ
                                </button>
                            </div>
                        </div>
                        
                        <!-- Subscription Required Message -->
                        <div v-if="requiresSubscription" class="bg-indigo-50 border border-indigo-200 rounded-md p-4 mb-6">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-indigo-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="mr-3">
                                    <p class="text-sm text-indigo-700">
                                        لتنزيل وحفظ السيرة الذاتية بهذا القالب المميز، يرجى 
                                        <Link href="/subscription" class="font-medium underline text-indigo-700 hover:text-indigo-600">الاشتراك الآن</Link>.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- CV Preview -->
                        <div class="mt-4 border rounded-lg p-6">
                            <!-- Existing CV preview content -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template> 