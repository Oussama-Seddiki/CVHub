<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/components/InputError.vue';
import InputLabel from '@/components/InputLabel.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import TextInput from '@/components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post('/reset-password', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="إعادة تعيين كلمة المرور" />

        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800 mb-1">إنشاء كلمة مرور جديدة</h1>
            <p class="text-gray-600">أدخل كلمة المرور الجديدة للمتابعة</p>
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

            <div class="mt-4">
                <InputLabel for="password" value="كلمة المرور الجديدة" class="text-right block" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full text-right"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                />

                <InputError class="mt-2 text-right" :message="form.errors.password" />
            </div>

            <div class="mt-4">
                <InputLabel for="password_confirmation" value="تأكيد كلمة المرور" class="text-right block" />

                <TextInput
                    id="password_confirmation"
                    type="password"
                    class="mt-1 block w-full text-right"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                />

                <InputError class="mt-2 text-right" :message="form.errors.password_confirmation" />
            </div>

            <div class="mt-6">
                <PrimaryButton class="w-full justify-center py-3" :disabled="form.processing">
                    إعادة تعيين كلمة المرور
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
