<script setup>
// Import components properly
import InputLabel from '@/components/InputLabel.vue';
import InputError from '@/components/InputError.vue';
import TextInput from '@/components/TextInput.vue';
import Checkbox from '@/components/Checkbox.vue';
import PrimaryButton from '@/components/PrimaryButton.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    errors: Object,
    redirect: {
        type: String,
        default: null,
    }
});

const isSubmitting = ref(false);

// Get redirect from URL query parameter if it exists
const redirectPath = computed(() => {
    const urlParams = new URLSearchParams(window.location.search);
    return props.redirect || urlParams.get('redirect') || '/dashboard';
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
    redirect: redirectPath.value
});

const submit = () => {
    if (isSubmitting.value) return;
    
    isSubmitting.value = true;
    console.log('Logging in with redirect path:', redirectPath.value);
    
    router.post('/login', form, {
        preserveScroll: true,
        onSuccess: () => {
            // Server will handle the redirect
            console.log('Login successful');
        },
        onError: (errors) => {
            console.error('Login failed:', errors);
            isSubmitting.value = false;
        },
        onFinish: () => {
            if (form.hasErrors) {
                form.reset('password');
                isSubmitting.value = false;
            }
        },
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="تسجيل الدخول" />

        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-gray-800 mb-1">مرحباً بك في CVHub</h1>
            <p class="text-gray-600">سجل دخولك للاستمرار</p>
        </div>

        <div v-if="status" class="mb-4 text-sm font-medium text-green-600 text-right">
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

            <div class="mt-4">
                <InputLabel for="password" value="كلمة المرور" class="text-right block" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full text-right"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                />

                <InputError class="mt-2 text-right" :message="form.errors.password" />
            </div>

            <div class="mt-4 block">
                <label class="flex items-center justify-end">
                    <span class="text-sm text-gray-600 ml-2">تذكرني</span>
                    <Checkbox name="remember" v-model:checked="form.remember" />
                </label>
            </div>

            <div class="mt-8">
                <PrimaryButton
                    class="w-full justify-center py-3"
                    :class="{ 'opacity-25': isSubmitting }"
                    :disabled="isSubmitting"
                >
                    {{ isSubmitting ? 'جاري تسجيل الدخول...' : 'تسجيل الدخول' }}
                </PrimaryButton>
            </div>

            <div class="mt-6 text-center">
                <div class="flex justify-between items-center">
                    <Link
                        href="/register"
                        class="text-sm text-gray-600 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        حساب جديد
                    </Link>

                    <Link
                        v-if="canResetPassword"
                        href="/forgot-password"
                        class="text-sm text-gray-600 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        نسيت كلمة المرور؟
                    </Link>
                </div>
            </div>
        </form>
    </GuestLayout>
</template>
