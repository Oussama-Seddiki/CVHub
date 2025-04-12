<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    activeSubscription: Object,
    subscriptionCost: Number,
});

const hasActiveSubscription = computed(() => props.activeSubscription !== null);
</script>

<template>
    <Head title="الاشتراك" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">الاشتراك</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div v-if="hasActiveSubscription" class="text-center">
                            <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg">
                                <h3 class="text-xl font-bold mb-2">اشتراكك نشط</h3>
                                <p>ينتهي اشتراكك بتاريخ: {{ new Date(activeSubscription.expires_at).toLocaleDateString('ar-DZ') }}</p>
                            </div>
                            <p class="mb-4">يمكنك الآن استخدام جميع ميزات المنصة</p>
                            <div class="flex justify-center space-x-4 gap-4">
                                <Link href="/cv" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                    إنشاء سيرة ذاتية
                                </Link>
                                <Link href="/library" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                    مكتبة الوثائق
                                </Link>
                                <Link href="/file-processing" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                    معالجة الملفات
                                </Link>
                            </div>
                        </div>
                        <div v-else class="text-center">
                            <h3 class="text-2xl font-bold mb-6">اشترك الآن للوصول إلى جميع الميزات</h3>
                            
                            <div class="mb-8 mx-auto max-w-lg">
                                <div class="border border-gray-200 rounded-lg p-6 bg-gray-50">
                                    <h4 class="text-xl font-bold mb-4">اشتراك سنوي</h4>
                                    <p class="text-3xl font-bold mb-4">{{ subscriptionCost }} دج</p>
                                    <ul class="text-right mb-6">
                                        <li class="mb-2 flex items-center justify-end">
                                            <span>إنشاء سير ذاتية احترافية</span>
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500 ml-2" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                            </svg>
                                        </li>
                                        <li class="mb-2 flex items-center justify-end">
                                            <span>مكتبة وثائق متكاملة</span>
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500 ml-2" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                            </svg>
                                        </li>
                                        <li class="mb-2 flex items-center justify-end">
                                            <span>معالجة ملفات PDF</span>
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500 ml-2" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                            </svg>
                                        </li>
                                        <li class="mb-2 flex items-center justify-end">
                                            <span>دعم فني دائم</span>
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500 ml-2" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                            </svg>
                                        </li>
                                    </ul>
                                    <Link href="/subscription/create-payment" method="post" as="button" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded">
                                        اشترك الآن
                                    </Link>
                                </div>
                            </div>
                            
                            <div class="text-gray-600">
                                <h4 class="font-semibold mb-2">طريقة الدفع</h4>
                                <div class="flex justify-center items-center mb-4">
                                    <img src="/images/baridi-mob.png" alt="بريدي موب" class="h-12 mx-2" />
                                </div>
                                <p>يتم الدفع عبر تطبيق بريدي موب - المتوفر على منصات الهواتف الذكية</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template> 