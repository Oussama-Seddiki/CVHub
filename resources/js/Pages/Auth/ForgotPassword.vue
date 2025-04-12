<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/components/InputError.vue';
import InputLabel from '@/components/InputLabel.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import TextInput from '@/components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post('/forgot-password');
};
</script>

<template>
    <GuestLayout>
        <Head title="استعادة كلمة المرور" />

        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800 mb-1">استعادة كلمة المرور</h1>
            <p class="text-gray-600">أدخل بريدك الإلكتروني لإرسال رابط استعادة كلمة المرور</p>
        </div>

        <div v-if="status" class="mb-4 text-sm font-medium text-green-600 p-4 bg-green-50 rounded-md text-right">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="rtl">
            <div>
                <InputLabel for="email" value="البريد الإلكتروني" class="text-right block" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full text-right"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-2 text-right" :message="form.errors.email" />
            </div>

            <div class="mt-6">
                <PrimaryButton class="w-full justify-center py-3" :disabled="form.processing">
                    إرسال رابط استعادة كلمة المرور
                </PrimaryButton>
            </div>

            <div class="flex items-center justify-end mt-4">
                <Link
                    href="/login"
                    class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    العودة لتسجيل الدخول
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
