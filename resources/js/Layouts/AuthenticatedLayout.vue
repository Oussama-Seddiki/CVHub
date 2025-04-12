<script setup>
import { ref, onMounted } from 'vue';
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
            <!-- Page Heading -->
            <header class="bg-white shadow-sm" v-if="$slots.header">
                <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main class="py-6">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <slot />
                </div>
            </main>
            
            <!-- Creative Footer -->
            <footer class="bg-gradient-to-r from-primary-800 to-primary-900 text-white mt-auto">
                <!-- Wave Separator -->
                <div class="w-full">
                    <svg class="waves" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 24 150 28" preserveAspectRatio="none" shape-rendering="auto">
                        <defs>
                            <path id="gentle-wave" d="M-160 44c30 0 58-18 88-18s 58 18 88 18 58-18 88-18 58 18 88 18 v44h-352z" />
                        </defs>
                        <g class="parallax">
                            <use xlink:href="#gentle-wave" x="48" y="0" fill="rgba(255,255,255,0.7)" />
                            <use xlink:href="#gentle-wave" x="48" y="3" fill="rgba(255,255,255,0.5)" />
                            <use xlink:href="#gentle-wave" x="48" y="5" fill="rgba(255,255,255,0.3)" />
                            <use xlink:href="#gentle-wave" x="48" y="7" fill="#fff" />
                        </g>
                    </svg>
                </div>
                
                <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                        <!-- Logo and Description -->
                        <div class="md:col-span-1">
                            <Link href="/" class="inline-block">
                                <div class="text-3xl font-bold cairo cairo-800 text-white">CVHub</div>
                            </Link>
                            <p class="mt-3 text-gray-200 cairo cairo-300">
                                منصة الخدمات الطلابية المتكاملة لإدارة وتحرير ومشاركة السير الذاتية بشكل احترافي
                            </p>
                            <div class="mt-6 flex space-x-4 space-x-reverse">
                                <a href="#" class="text-white hover:text-primary-300 transition-colors">
                                    <i class="fab fa-facebook-f"></i>
                                </a>
                                <a href="#" class="text-white hover:text-primary-300 transition-colors">
                                    <i class="fab fa-twitter"></i>
                                </a>
                                <a href="#" class="text-white hover:text-primary-300 transition-colors">
                                    <i class="fab fa-instagram"></i>
                                </a>
                                <a href="#" class="text-white hover:text-primary-300 transition-colors">
                                    <i class="fab fa-linkedin-in"></i>
                                </a>
                            </div>
                        </div>
                        
                        <!-- Quick Links -->
                        <div class="md:col-span-1">
                            <h3 class="text-lg font-bold cairo cairo-700 mb-4 text-white">روابط سريعة</h3>
                            <ul class="space-y-2">
                                <li>
                                    <Link href="/dashboard" class="text-gray-200 hover:text-white transition-colors flex items-center">
                                        <i class="fas fa-chevron-left ml-2 text-xs"></i>
                                        <span>لوحة التحكم</span>
                                    </Link>
                                </li>
                                <li>
                                    <Link href="/cv" class="text-gray-200 hover:text-white transition-colors flex items-center">
                                        <i class="fas fa-chevron-left ml-2 text-xs"></i>
                                        <span>إنشاء سيرة ذاتية</span>
                                    </Link>
                                </li>
                                <li>
                                    <Link href="/file-processing" class="text-gray-200 hover:text-white transition-colors flex items-center">
                                        <i class="fas fa-chevron-left ml-2 text-xs"></i>
                                        <span>معالجة الملفات</span>
                                    </Link>
                                </li>
                                <li>
                                    <Link href="/library" class="text-gray-200 hover:text-white transition-colors flex items-center">
                                        <i class="fas fa-chevron-left ml-2 text-xs"></i>
                                        <span>مكتبة الوثائق</span>
                                    </Link>
                                </li>
                                <li>
                                    <Link href="/subscription" class="text-gray-200 hover:text-white transition-colors flex items-center">
                                        <i class="fas fa-chevron-left ml-2 text-xs"></i>
                                        <span>الاشتراك</span>
                                    </Link>
                                </li>
                            </ul>
                        </div>
                        
                        <!-- Services -->
                        <div class="md:col-span-1">
                            <h3 class="text-lg font-bold cairo cairo-700 mb-4 text-white">خدماتنا</h3>
                            <ul class="space-y-2">
                                <li>
                                    <Link href="/cv" class="text-gray-200 hover:text-white transition-colors flex items-center">
                                        <i class="fas fa-file-alt ml-2"></i>
                                        <span>إنشاء السيرة الذاتية</span>
                                    </Link>
                                </li>
                                <li>
                                    <Link href="/file-processing" class="text-gray-200 hover:text-white transition-colors flex items-center">
                                        <i class="fas fa-file-pdf ml-2"></i>
                                        <span>تحويل صيغ الملفات</span>
                                    </Link>
                                </li>
                                <li>
                                    <Link href="/library" class="text-gray-200 hover:text-white transition-colors flex items-center">
                                        <i class="fas fa-book ml-2"></i>
                                        <span>مكتبة النماذج</span>
                                    </Link>
                                </li>
                                <li>
                                    <Link href="#" class="text-gray-200 hover:text-white transition-colors flex items-center">
                                        <i class="fas fa-graduation-cap ml-2"></i>
                                        <span>دليل الطالب</span>
                                    </Link>
                                </li>
                            </ul>
                        </div>
                        
                        <!-- Contact -->
                        <div class="md:col-span-1">
                            <h3 class="text-lg font-bold cairo cairo-700 mb-4 text-white">تواصل معنا</h3>
                            <ul class="space-y-3">
                                <li class="flex items-start">
                                    <i class="fas fa-map-marker-alt mt-1 ml-3 text-primary-300"></i>
                                    <span class="text-gray-200">الجزائر، الجزائر العاصمة</span>
                                </li>
                                <li class="flex items-center">
                                    <i class="fas fa-envelope ml-3 text-primary-300"></i>
                                    <span class="text-gray-200">info@cvhub.dz</span>
                                </li>
                                <li class="flex items-center">
                                    <i class="fas fa-phone-alt ml-3 text-primary-300"></i>
                                    <span class="text-gray-200">+213 00 00 00 00</span>
                                </li>
                                <li class="flex items-center">
                                    <i class="fas fa-clock ml-3 text-primary-300"></i>
                                    <span class="text-gray-200">متاح 24/7</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Copyright -->
                    <div class="mt-12 pt-8 border-t border-primary-700 text-center text-gray-300">
                        <p class="cairo cairo-400">{{ new Date().getFullYear() }} © CVHub. جميع الحقوق محفوظة.</p>
                        <div class="mt-4 flex justify-center space-x-6 space-x-reverse text-sm">
                            <a href="#" class="text-gray-300 hover:text-white transition-colors">شروط الاستخدام</a>
                            <a href="#" class="text-gray-300 hover:text-white transition-colors">سياسة الخصوصية</a>
                            <a href="#" class="text-gray-300 hover:text-white transition-colors">الأسئلة الشائعة</a>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>
</template>

<style>
@import 'aos/dist/aos.css';

/* Set the Cairo font for the entire authenticated layout */
:root {
    font-family: "Cairo", sans-serif;
    font-optical-sizing: auto;
    font-style: normal;
}

/* Default font weight */
body {
    font-weight: 400;
    font-variation-settings: "slnt" 0;
}

/* Headings */
h1, h2, h3, h4, h5, h6 {
    font-weight: 700;
    font-variation-settings: "slnt" 0;
}

/* Font weight classes */
.font-light {
    font-weight: 300;
}

.font-normal {
    font-weight: 400;
}

.font-medium {
    font-weight: 500;
}

.font-semibold {
    font-weight: 600;
}

.font-bold {
    font-weight: 700;
}

.font-extrabold {
    font-weight: 800;
}

/* Slant variations if needed */
.slant-left {
    font-variation-settings: "slnt" -5;
}

.slant-right {
    font-variation-settings: "slnt" 5;
}

/* Footer Wave Animation */
.waves {
    position: relative;
    width: 100%;
    height: 50px;
    margin-bottom: -7px;
    min-height: 50px;
    max-height: 150px;
}

.parallax > use {
    animation: move-forever 25s cubic-bezier(.55,.5,.45,.5) infinite;
}

.parallax > use:nth-child(1) {
    animation-delay: -2s;
    animation-duration: 7s;
}

.parallax > use:nth-child(2) {
    animation-delay: -3s;
    animation-duration: 10s;
}

.parallax > use:nth-child(3) {
    animation-delay: -4s;
    animation-duration: 13s;
}

.parallax > use:nth-child(4) {
    animation-delay: -5s;
    animation-duration: 20s;
}

@keyframes move-forever {
    0% {
        transform: translate3d(-90px,0,0);
    }
    100% { 
        transform: translate3d(85px,0,0);
    }
}
</style>
