<script setup>
import { onMounted } from 'vue';
import ModernNavigation from '@/components/ModernNavigation.vue';
import CursorEffect from '@/components/CursorEffect.vue';
import { Link } from '@inertiajs/vue3';
import AOS from 'aos';

onMounted(() => {
    // Initialize AOS animation library
    AOS.init({
        duration: 800,
        easing: 'ease-out',
        once: true
    });
});
</script>

<template>
    <div>
        <!-- Custom cursor effect -->
        <CursorEffect />
        
        <!-- Modern navigation -->
        <ModernNavigation />
        
        <div class="min-h-screen bg-gradient-to-b from-blue-50 to-white pt-20" dir="rtl">
            <div class="flex flex-col items-center justify-center p-6">
                <div class="w-full max-w-md">
                    <slot />
                </div>
            </div>
            
            <!-- Footer -->
            <footer class="bg-white border-t border-gray-100 py-8 mt-auto">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex flex-col md:flex-row justify-between items-center">
                        <div class="mb-6 md:mb-0">
                            <Link href="/">
                                <div class="text-2xl font-bold text-blue-600">CVHub</div>
                            </Link>
                            <p class="text-gray-500 mt-2">
                                منصة الخدمات الطلابية المتكاملة
                            </p>
                        </div>
                        
                        <div class="flex flex-wrap justify-center gap-6">
                            <Link href="/" class="text-gray-600 hover:text-blue-600 transition">
                                الرئيسية
                            </Link>
                            <Link href="/#services" class="text-gray-600 hover:text-blue-600 transition">
                                خدماتنا
                            </Link>
                            <Link href="/#pricing" class="text-gray-600 hover:text-blue-600 transition">
                                الأسعار
                            </Link>
                            <Link href="/#about" class="text-gray-600 hover:text-blue-600 transition">
                                عن المنصة
                            </Link>
                        </div>
                    </div>
                    
                    <div class="mt-8 border-t border-gray-100 pt-8 text-center text-gray-500">
                        © {{ new Date().getFullYear() }} CVHub. جميع الحقوق محفوظة.
                    </div>
                </div>
            </footer>
        </div>
    </div>
</template>

<style>
@import 'aos/dist/aos.css';
</style>
