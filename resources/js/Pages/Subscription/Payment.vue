<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    subscription: Object,
    paymentUrl: String,
});
</script>

<template>
    <Head title="دفع الاشتراك" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">دفع الاشتراك</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="text-center">
                            <h3 class="text-2xl font-bold mb-6">إتمام عملية الدفع</h3>
                            
                            <div class="mb-8 p-6 bg-gray-50 rounded-lg border border-gray-200 max-w-lg mx-auto">
                                <div class="mb-6">
                                    <h4 class="text-xl font-bold mb-4">تفاصيل الطلب</h4>
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="font-bold">رقم الطلب:</span>
                                        <span class="text-gray-600">{{ subscription.id }}</span>
                                    </div>
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="font-bold">المبلغ:</span>
                                        <span class="text-gray-600">{{ subscription.amount }} دج</span>
                                    </div>
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="font-bold">التاريخ:</span>
                                        <span class="text-gray-600">{{ new Date(subscription.created_at).toLocaleDateString('ar-DZ') }}</span>
                                    </div>
                                </div>
                                
                                <div class="mb-6">
                                    <h4 class="text-lg font-bold mb-4">تعليمات الدفع</h4>
                                    <ol class="text-right space-y-3 list-decimal list-inside">
                                        <li>افتح تطبيق بريدي موب على هاتفك المحمول</li>
                                        <li>انتقل إلى قسم "دفع الفواتير"</li>
                                        <li>اختر "دفع عبر QR Code" وامسح الكود المعروض أدناه</li>
                                        <li>أدخل المبلغ: {{ subscription.amount }} دج</li>
                                        <li>أكد عملية الدفع واحتفظ برقم المعاملة</li>
                                    </ol>
                                </div>
                                
                                <div class="mb-6 flex justify-center">
                                    <div class="p-3 bg-white rounded border border-gray-300">
                                        <img src="/images/qr-code-example.png" alt="QR Code للدفع" class="w-48 h-48" />
                                    </div>
                                </div>
                                
                                <div class="text-xs text-gray-500 mb-6">
                                    صالح لمدة 30 دقيقة من تاريخ الإنشاء
                                </div>
                                
                                <div class="flex justify-center space-x-4 gap-4">
                                    <Link :href="`/subscription/simulate-payment/${subscription.id}`" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                        محاكاة الدفع
                                    </Link>
                                    <Link href="/subscription" class="border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 font-bold py-2 px-4 rounded">
                                        العودة
                                    </Link>
                                </div>
                            </div>
                            
                            <div class="text-gray-600">
                                <h4 class="font-semibold mb-2">طريقة الدفع</h4>
                                <div class="flex justify-center items-center mb-4">
                                    <img src="/images/baridi-mob.png" alt="بريدي موب" class="h-12 mx-2" />
                                </div>
                                <p>للمساعدة، يرجى التواصل معنا عبر البريد الإلكتروني: support@cvhub.dz</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>