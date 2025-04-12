<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';

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
</script>

<template>
    <Head title="لوحة التحكم" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">لوحة التحكم</h2>
        </template>

        <div class="py-12" style="direction: rtl">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- رسالة ترحيبية -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 mb-6">
                    <h1 class="text-2xl font-bold text-gray-900 mb-4">أهلا بك في منصة CVHub!</h1>
                    
                    <template v-if="activeSubscription">
                        <p class="text-lg text-gray-600">
                            يمكنك الآن الاستفادة من جميع خدماتنا المتميزة. استخدم لوحة التحكم للوصول السريع إلى أي خدمة تحتاجها.
                        </p>
                    </template>
                    
                    <template v-else>
                        <p class="text-lg text-gray-600 mb-4">
                            لم تقم بالاشتراك بعد. قم بالاشتراك الآن للاستفادة من جميع الخدمات المتاحة.
                        </p>
                        
                        <Link href="/subscription" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-white hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus">
                            اشترك الآن
                        </Link>
                    </template>
                </div>
                
                <!-- البطاقات الرئيسية للخدمات -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <!-- بطاقة إنشاء السيرة الذاتية -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-lg transition-shadow duration-300">
                        <div class="p-1">
                            <div class="h-2 w-full bg-blue-600 rounded-t-lg"></div>
                        </div>
                        <div class="p-6">
                            <div class="flex items-center justify-center w-16 h-16 bg-blue-100 text-blue-600 rounded-lg mb-4">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            
                            <h2 class="text-xl font-semibold text-gray-900 mb-2">إنشاء السيرة الذاتية</h2>
                            
                            <p class="text-gray-600 mb-4">
                                قم بإنشاء سيرتك الذاتية بسهولة مع قوالب احترافية متعددة وتحسين بالذكاء الاصطناعي.
                            </p>
                            
                            <Link href="/cv" class="inline-flex items-center text-blue-600 hover:text-blue-800">
                                <span>إنشاء سيرة ذاتية</span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </Link>
                        </div>
                    </div>
                    
                    <!-- بطاقة معالجة الملفات -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-lg transition-shadow duration-300">
                        <div class="p-1">
                            <div class="h-2 w-full bg-green-600 rounded-t-lg"></div>
                        </div>
                        <div class="p-6">
                            <div class="flex items-center justify-center w-16 h-16 bg-green-100 text-green-600 rounded-lg mb-4">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                                </svg>
                            </div>
                            
                            <h2 class="text-xl font-semibold text-gray-900 mb-2">معالجة الملفات</h2>
                            
                            <p class="text-gray-600 mb-4">
                                تحويل، دمج، تقسيم وضغط ملفات PDF وأنواع أخرى من الملفات بخطوات بسيطة.
                            </p>
                            
                            <Link href="/file-processing" class="inline-flex items-center text-green-600 hover:text-green-800">
                                <span>معالجة الملفات</span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </Link>
                        </div>
                    </div>
                    
                    <!-- بطاقة مكتبة الوثائق -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-lg transition-shadow duration-300">
                        <div class="p-1">
                            <div class="h-2 w-full bg-purple-600 rounded-t-lg"></div>
                        </div>
                        <div class="p-6">
                            <div class="flex items-center justify-center w-16 h-16 bg-purple-100 text-purple-600 rounded-lg mb-4">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                            </div>
                            
                            <h2 class="text-xl font-semibold text-gray-900 mb-2">مكتبة الوثائق</h2>
                            
                            <p class="text-gray-600 mb-4">
                                وصول سهل لمكتبة رقمية شاملة من الوثائق الرسمية والمذكرات الدراسية.
                            </p>
                            
                            <Link href="/library" class="inline-flex items-center text-purple-600 hover:text-purple-800">
                                <span>استعراض المكتبة</span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </Link>
                        </div>
                    </div>
                </div>
                
                <!-- معلومات الاشتراك -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">معلومات الاشتراك</h2>
                    
                    <div v-if="activeSubscription" class="bg-green-50 p-4 rounded-md">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="mr-3">
                                <p class="text-sm font-medium text-green-800">
                                    لديك اشتراك فعال حتى {{ formatDate(subscriptionEndsAt) }}
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div v-else class="bg-yellow-50 p-4 rounded-md">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="mr-3">
                                <p class="text-sm font-medium text-yellow-800">
                                    ليس لديك اشتراك نشط. <Link href="/subscription" class="underline">اشترك الآن</Link> للاستفادة من جميع الخدمات.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- النصائح والإرشادات -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">نصائح ومساعدة</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="border border-gray-200 rounded-md p-4 hover:bg-gray-50 transition-colors duration-300">
                            <h3 class="font-medium text-gray-900 mb-2">إنشاء سيرة ذاتية احترافية</h3>
                            <p class="text-gray-600 text-sm mb-2">تأكد من إضافة جميع المعلومات الضرورية وتجنب الأخطاء اللغوية والإملائية.</p>
                            <Link href="/cv" class="text-blue-600 text-sm hover:text-blue-800">المزيد من النصائح</Link>
                        </div>
                        
                        <div class="border border-gray-200 rounded-md p-4 hover:bg-gray-50 transition-colors duration-300">
                            <h3 class="font-medium text-gray-900 mb-2">معالجة ملفات PDF</h3>
                            <p class="text-gray-600 text-sm mb-2">استخدم أداة ضغط الملفات لتقليل حجم ملفات PDF دون التأثير على جودتها.</p>
                            <Link href="/file-processing" class="text-blue-600 text-sm hover:text-blue-800">المزيد من النصائح</Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
