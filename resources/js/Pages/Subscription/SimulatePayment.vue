<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    subscription: Object,
});

const form = useForm({});

function confirmPayment() {
    form.post(`/subscription/confirm-payment/${props.subscription.id}`);
}
</script>

<template>
    <Head title="محاكاة الدفع" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">محاكاة الدفع</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="text-center">
                            <div class="mb-8 p-6 bg-yellow-50 rounded-lg border border-yellow-200 max-w-lg mx-auto">
                                <h3 class="text-2xl font-bold mb-4 text-yellow-700">محاكاة الدفع</h3>
                                <div class="bg-white p-4 rounded-lg mb-6 border border-yellow-100">
                                    <p class="font-bold mb-2">ملاحظة هامة</p>
                                    <p class="text-yellow-700 mb-2">هذه الصفحة للتجربة فقط في بيئة التطوير.</p>
                                    <p class="text-yellow-800">في البيئة الإنتاجية، سيتم استخدام واجهة دفع حقيقية.</p>
                                </div>
                                
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
                                
                                <div class="flex justify-center">
                                    <button 
                                        @click="confirmPayment" 
                                        type="button" 
                                        class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded"
                                        :disabled="form.processing"
                                    >
                                        تأكيد الدفع الوهمي
                                    </button>
                                </div>
                            </div>
                            
                            <div class="text-gray-600">
                                <p>هذه المحاكاة مخصصة لأغراض التطوير فقط.</p>
                                <p>في البيئة الحقيقية، سيتم استخدام بوابة دفع موثوقة.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template> 