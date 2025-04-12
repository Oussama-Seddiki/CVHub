<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
    laravelVersion: {
        type: String,
        required: true,
    },
    phpVersion: {
        type: String,
        required: true,
    },
});

const header = ref(null);

onMounted(() => {
    // Header scroll effect
    const handleScroll = () => {
        if (header.value) {
            if (window.scrollY > 50) {
                header.value.classList.add('scrolled');
            } else {
                header.value.classList.remove('scrolled');
            }
        }
    };
    
    window.addEventListener('scroll', handleScroll);
    
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                window.scrollTo({
                    top: targetElement.offsetTop - 100,
                    behavior: 'smooth'
                });
            }
        });
    });
    
    // Clean up event listeners
    return () => {
        window.removeEventListener('scroll', handleScroll);
    };
});
</script>

<style>
/* Header Styles */
header {
    transition: all 0.3s ease;
}

header.scrolled {
    background-color: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    padding-top: 0.75rem;
    padding-bottom: 0.75rem;
    position: sticky;
    top: 0;
    z-index: 50;
}

/* Hero Section Styles */
.hero-illustration {
    animation: float 6s ease-in-out infinite;
}

.floating-element {
    animation-name: float-element;
    animation-duration: 6s;
    animation-timing-function: ease-in-out;
    animation-iteration-count: infinite;
}

.floating-element:nth-child(1) {
    animation-delay: 0s;
}

.floating-element:nth-child(2) {
    animation-delay: 1s;
}

.floating-element:nth-child(3) {
    animation-delay: 2s;
}

.floating-element:nth-child(4) {
    animation-delay: 3s;
}

/* Particles Animation */
.particles-container {
    position: absolute;
    width: 100%;
    height: 100%;
    overflow: hidden;
}

.particle {
    position: absolute;
    width: 10px;
    height: 10px;
    background-color: rgba(59, 130, 246, 0.1);
    border-radius: 50%;
    animation: particle-float 15s linear infinite;
}

.particle:nth-child(1) {
    top: 10%;
    left: 10%;
    width: 15px;
    height: 15px;
    animation-duration: 25s;
    background-color: rgba(59, 130, 246, 0.15);
}

.particle:nth-child(2) {
    top: 20%;
    left: 80%;
    width: 12px;
    height: 12px;
    animation-duration: 30s;
    animation-delay: 2s;
    background-color: rgba(79, 70, 229, 0.1);
}

.particle:nth-child(3) {
    top: 80%;
    left: 15%;
    width: 8px;
    height: 8px;
    animation-duration: 20s;
    animation-delay: 1s;
    background-color: rgba(99, 102, 241, 0.12);
}

.particle:nth-child(4) {
    top: 40%;
    left: 40%;
    width: 10px;
    height: 10px;
    animation-duration: 22s;
    animation-delay: 0s;
    background-color: rgba(59, 130, 246, 0.08);
}

.particle:nth-child(5) {
    top: 65%;
    left: 70%;
    width: 14px;
    height: 14px;
    animation-duration: 28s;
    animation-delay: 3s;
    background-color: rgba(147, 197, 253, 0.15);
}

.particle:nth-child(6) {
    top: 90%;
    left: 30%;
    width: 9px;
    height: 9px;
    animation-duration: 26s;
    animation-delay: 4s;
    background-color: rgba(191, 219, 254, 0.2);
}

.particle:nth-child(7) {
    top: 30%;
    left: 90%;
    width: 11px;
    height: 11px;
    animation-duration: 24s;
    animation-delay: 2s;
    background-color: rgba(59, 130, 246, 0.1);
}

.particle:nth-child(8) {
    top: 70%;
    left: 50%;
    width: 13px;
    height: 13px;
    animation-duration: 29s;
    animation-delay: 1s;
    background-color: rgba(96, 165, 250, 0.12);
}

.particle:nth-child(9) {
    top: 50%;
    left: 20%;
    width: 10px;
    height: 10px;
    animation-duration: 27s;
    animation-delay: 0s;
    background-color: rgba(59, 130, 246, 0.09);
}

.particle:nth-child(10) {
    top: 15%;
    left: 60%;
    width: 12px;
    height: 12px;
    animation-duration: 23s;
    animation-delay: 3s;
    background-color: rgba(37, 99, 235, 0.1);
}

.particle:nth-child(11) {
    top: 85%;
    left: 85%;
    width: 9px;
    height: 9px;
    animation-duration: 21s;
    animation-delay: 2s;
    background-color: rgba(147, 197, 253, 0.18);
}

.particle:nth-child(12) {
    top: 45%;
    left: 75%;
    width: 11px;
    height: 11px;
    animation-duration: 26s;
    animation-delay: 4s;
    background-color: rgba(96, 165, 250, 0.14);
}

/* Wave Divider */
.wave-divider {
    margin-top: 2rem;
    margin-bottom: -1px;
}

/* Animations */
@keyframes float {
    0% {
        transform: translateY(0px);
    }
    50% {
        transform: translateY(-15px);
    }
    100% {
        transform: translateY(0px);
    }
}

@keyframes float-element {
    0% {
        transform: translateY(0) translateX(0);
    }
    33% {
        transform: translateY(-10px) translateX(5px);
    }
    66% {
        transform: translateY(5px) translateX(-5px);
    }
    100% {
        transform: translateY(0) translateX(0);
    }
}

@keyframes particle-float {
    0% {
        transform: translateY(0) translateX(0) rotate(0deg);
        opacity: 0;
    }
    10% {
        opacity: 1;
    }
    90% {
        opacity: 1;
    }
    100% {
        transform: translateY(-100px) translateX(100px) rotate(360deg);
        opacity: 0;
    }
}

/* Service Cards */
.service-card {
    transition: all 0.3s ease;
}

.service-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

/* Pricing Cards */
.pricing-card {
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.pricing-card:hover {
    transform: scale(1.03);
    border-color: #3B82F6;
}

.pricing-card.featured {
    border-color: #3B82F6;
    box-shadow: 0 20px 25px -5px rgba(59, 130, 246, 0.1), 0 10px 10px -5px rgba(59, 130, 246, 0.04);
}

/* Footer Wave */
.footer-wave {
    margin-bottom: -1px;
    transform: rotate(180deg);
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

/* Dashboard Link Styles */
.dashboard-link {
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.dashboard-link::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.7s ease;
}

.dashboard-link:hover::before {
    left: 100%;
}

.dashboard-cta {
    border: 2px solid #3B82F6;
}

.dashboard-cta span {
    position: relative;
    z-index: 1;
}
</style>

<template>
    <Head title="CVHub - منصة الخدمات الطلابية المتكاملة" />
    
    <div class="bg-gradient-to-b from-blue-50 to-white min-h-screen" style="direction: rtl">
        <div class="relative mx-auto max-w-7xl px-6 py-6">
            <!-- Header -->
            <header class="flex justify-between items-center py-4" ref="header">
                <div class="flex items-center">
                    <div class="text-3xl font-bold text-blue-600">CVHub</div>
                </div>
                
                <nav v-if="canLogin" class="space-x-4 flex items-center space-x-reverse">
                    <template v-if="$page.props.auth && $page.props.auth.user">
                        <Link href="/dashboard" class="dashboard-link px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-lg flex items-center transition-all duration-300 transform hover:translate-y-[-2px] hover:shadow-md">
                            <i class="fas fa-tachometer-alt ml-2"></i>
                            <span>لوحة التحكم</span>
                        </Link>
                    </template>
                    
                    <template v-else>
                        <Link href="/login" class="px-4 py-2 text-gray-700 hover:text-blue-600 transition">
                            تسجيل الدخول
                        </Link>
                        
                        <Link v-if="canRegister" href="/register" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                            إنشاء حساب
                        </Link>
                    </template>
                </nav>
            </header>
            
            <!-- Hero Section -->
            <div class="py-16 md:py-24">
                <div class="grid md:grid-cols-2 gap-10 items-center">
                    <div data-aos="fade-right" data-aos-delay="100">
                        <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-6">
                            <span class="relative inline-block">
                                <span class="relative z-10 bg-clip-text text-transparent bg-gradient-to-r from-blue-600 to-indigo-500">منصة CVHub</span>
                                <span class="absolute bottom-1 left-0 w-full h-3 bg-blue-100 -z-10 transform -rotate-1"></span>
                            </span>
                            <br />الخدمات الطلابية المتكاملة
                        </h1>
                        <p class="text-xl text-gray-600 mb-8">نقدم لك مجموعة من الخدمات المميزة لمساعدتك في مسيرتك الدراسية والمهنية بطريقة سهلة وفعالة.</p>
                        <div class="flex flex-wrap gap-4">
                            <Link href="/register" class="px-8 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-lg hover:shadow-lg transform hover:-translate-y-1 transition duration-300 relative overflow-hidden group">
                                <span class="relative z-10">ابدأ الآن مجاناً</span>
                                <span class="absolute top-0 left-0 w-full h-full bg-white opacity-20 transform -translate-x-full group-hover:translate-x-0 transition-transform duration-300"></span>
                            </Link>
                            <a href="#services" class="px-8 py-3 bg-white text-blue-600 border-2 border-blue-600 rounded-lg hover:bg-blue-50 transition duration-300">
                                تعرف على خدماتنا
                            </a>
                        </div>
                    </div>
                    <div class="relative" data-aos="fade-left" data-aos-delay="300">
                        <!-- Custom SVG Illustration -->
                        <div class="relative z-10 hero-illustration">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 600" class="w-full h-auto">
                                <!-- Background Shape -->
                                <path d="M400,50 C550,50 700,150 700,300 C700,450 550,550 400,550 C250,550 100,450 100,300 C100,150 250,50 400,50 Z" fill="#EBF4FF" />
                                
                                <!-- Document Stack -->
                                <g class="document-stack">
                                    <rect x="250" y="200" width="300" height="350" rx="10" fill="#FFFFFF" stroke="#3B82F6" stroke-width="2" />
                                    <rect x="270" y="230" width="260" height="30" rx="5" fill="#DBEAFE" />
                                    <rect x="270" y="280" width="260" height="15" rx="5" fill="#EFF6FF" />
                                    <rect x="270" y="310" width="200" height="15" rx="5" fill="#EFF6FF" />
                                    <rect x="270" y="340" width="230" height="15" rx="5" fill="#EFF6FF" />
                                    <rect x="270" y="370" width="180" height="15" rx="5" fill="#EFF6FF" />
                                    <rect x="270" y="400" width="260" height="15" rx="5" fill="#EFF6FF" />
                                    <rect x="270" y="430" width="220" height="15" rx="5" fill="#EFF6FF" />
                                    <rect x="270" y="460" width="190" height="15" rx="5" fill="#EFF6FF" />
                                    <rect x="270" y="490" width="240" height="15" rx="5" fill="#EFF6FF" />
                                </g>
                                
                                <!-- Floating Elements -->
                                <circle class="floating-element" cx="200" cy="150" r="25" fill="#93C5FD" />
                                <circle class="floating-element" cx="600" cy="200" r="20" fill="#60A5FA" />
                                <circle class="floating-element" cx="550" cy="450" r="15" fill="#3B82F6" />
                                <circle class="floating-element" cx="250" cy="450" r="10" fill="#2563EB" />
                                
                                <!-- Decorative Lines -->
                                <path d="M150,250 C200,200 250,300 300,250" stroke="#BFDBFE" stroke-width="3" fill="none" />
                                <path d="M500,150 C550,100 600,200 650,150" stroke="#BFDBFE" stroke-width="3" fill="none" />
                            </svg>
                        </div>
                        
                        <!-- Background Particles -->
                        <div class="particles-container absolute inset-0 -z-10">
                            <div class="particle"></div>
                            <div class="particle"></div>
                            <div class="particle"></div>
                            <div class="particle"></div>
                            <div class="particle"></div>
                            <div class="particle"></div>
                            <div class="particle"></div>
                            <div class="particle"></div>
                            <div class="particle"></div>
                            <div class="particle"></div>
                            <div class="particle"></div>
                            <div class="particle"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Wave Divider -->
                <div class="wave-divider mt-16">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 100" class="w-full h-auto">
                        <path fill="#f3f4f6" fill-opacity="1" d="M0,32L60,37.3C120,43,240,53,360,58.7C480,64,600,64,720,53.3C840,43,960,21,1080,16C1200,11,1320,21,1380,26.7L1440,32L1440,100L1380,100C1320,100,1200,100,1080,100C960,100,840,100,720,100C600,100,480,100,360,100C240,100,120,100,60,100L0,100Z"></path>
                    </svg>
                </div>
            </div>
            
            <!-- Services Section -->
            <div id="services" class="py-16 md:py-24">
                <h2 class="text-3xl md:text-4xl font-bold text-center text-gray-900 mb-16">
                    خدماتنا <span class="text-blue-600">المميزة</span>
                </h2>
                
                <!-- 3 Main Services Cards -->
                <div class="grid md:grid-cols-3 gap-10 mb-20">
                    <!-- Service 1: CV Builder -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden transform transition duration-300 hover:-translate-y-2 hover:shadow-xl">
                        <div class="h-48 bg-gradient-to-r from-blue-500 to-indigo-600 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-20 w-20 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div class="p-6">
                            <h3 class="text-2xl font-bold text-gray-900 mb-3">إنشاء السير الذاتية بالذكاء الاصطناعي</h3>
                            <p class="text-gray-600 mb-4">
                                قم بإنشاء سيرة ذاتية احترافية بخطوات بسيطة. أدخل بياناتك واختر القالب المناسب، وسيقوم الذكاء الاصطناعي بتوليد سيرة ذاتية متميزة بصيغتي PDF وWord.
                            </p>
                            <div class="font-semibold text-blue-600">
                                <span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full mr-2">Resumaker AI</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Service 2: File Processing -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden transform transition duration-300 hover:-translate-y-2 hover:shadow-xl">
                        <div class="h-48 bg-gradient-to-r from-green-500 to-teal-500 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-20 w-20 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                            </svg>
                        </div>
                        <div class="p-6">
                            <h3 class="text-2xl font-bold text-gray-900 mb-3">معالجة الملفات</h3>
                            <p class="text-gray-600 mb-4">
                                معالجة احترافية للملفات بما في ذلك تحويل، دمج، تقسيم وضغط PDF وأنواع أخرى من الملفات بجودة عالية وسرعة ممتازة.
                            </p>
                            <div class="font-semibold text-blue-600">
                                <span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full mr-2">PDF Processing API</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Service 3: Document Library -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden transform transition duration-300 hover:-translate-y-2 hover:shadow-xl">
                        <div class="h-48 bg-gradient-to-r from-purple-500 to-pink-500 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-20 w-20 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        </div>
                        <div class="p-6">
                            <h3 class="text-2xl font-bold text-gray-900 mb-3">مكتبة الوثائق والمذكرات</h3>
                            <p class="text-gray-600 mb-4">
                                وصول سهل لمكتبة رقمية شاملة تضم وثائق رسمية، مذكرات دراسية ومواد تعليمية متنوعة مع تجربة تصفح متطورة.
                            </p>
                            <div class="font-semibold text-blue-600">
                                <span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full mr-2">Scribd API</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- الميزات التفصيلية -->
                <div class="bg-gray-50 p-8 rounded-2xl shadow-inner">
                    <h3 class="text-2xl font-bold text-center mb-12">مميزات كل خدمة</h3>
                    
                    <!-- Service 1 Features -->
                    <div class="mb-16">
                        <h4 class="text-xl font-bold text-blue-600 mb-6">إنشاء السير الذاتية بالذكاء الاصطناعي</h4>
                        <div class="grid md:grid-cols-3 gap-6">
                            <div class="bg-white p-5 rounded-lg shadow-md">
                                <div class="flex items-center mb-3">
                                    <div class="bg-blue-100 p-2 rounded-full">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" viewBox="0 0 20 20" fill="none" stroke="currentColor">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <h5 class="mr-3 font-bold">تحسين محتوى النص تلقائياً</h5>
                                </div>
                                <p class="text-gray-600 text-sm">استخدام الذكاء الاصطناعي لتحسين محتوى سيرتك الذاتية وجعلها أكثر احترافية.</p>
                            </div>
                            
                            <div class="bg-white p-5 rounded-lg shadow-md">
                                <div class="flex items-center mb-3">
                                    <div class="bg-blue-100 p-2 rounded-full">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" viewBox="0 0 20 20" fill="none" stroke="currentColor">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <h5 class="mr-3 font-bold">قوالب احترافية متعددة</h5>
                                </div>
                                <p class="text-gray-600 text-sm">اختيار من بين مجموعة متنوعة من القوالب المهنية لمختلف المجالات</p>
                            </div>
                            
                            <div class="bg-white p-5 rounded-lg shadow-md">
                                <div class="flex items-center mb-3">
                                    <div class="bg-blue-100 p-2 rounded-full">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" viewBox="0 0 20 20" fill="none" stroke="currentColor">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <h5 class="mr-3 font-bold">تصدير بصيغ متعددة</h5>
                                </div>
                                <p class="text-gray-600 text-sm">الحصول على السيرة الذاتية بصيغتي PDF وWord عالية الجودة</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Service 2 Features -->
                    <div class="mb-16">
                        <h4 class="text-xl font-bold text-green-600 mb-6">معالجة الملفات</h4>
                        <div class="grid md:grid-cols-3 gap-6">
                            <div class="bg-white p-5 rounded-lg shadow-md">
                                <div class="flex items-center mb-3">
                                    <div class="bg-green-100 p-2 rounded-full">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600" viewBox="0 0 20 20" fill="none" stroke="currentColor">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <h5 class="mr-3 font-bold">تحويل الملفات</h5>
                                </div>
                                <p class="text-gray-600 text-sm">تحويل الملفات بين صيغ متعددة مثل PDF وWord وExcel وغيرها</p>
                            </div>
                            
                            <div class="bg-white p-5 rounded-lg shadow-md">
                                <div class="flex items-center mb-3">
                                    <div class="bg-green-100 p-2 rounded-full">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600" viewBox="0 0 20 20" fill="none" stroke="currentColor">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <h5 class="mr-3 font-bold">دمج وتقسيم الملفات</h5>
                                </div>
                                <p class="text-gray-600 text-sm">دمج عدة ملفات في ملف واحد أو تقسيم ملف كبير إلى أجزاء</p>
                            </div>
                            
                            <div class="bg-white p-5 rounded-lg shadow-md">
                                <div class="flex items-center mb-3">
                                    <div class="bg-green-100 p-2 rounded-full">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600" viewBox="0 0 20 20" fill="none" stroke="currentColor">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <h5 class="mr-3 font-bold">ضغط وتحسين الملفات</h5>
                                </div>
                                <p class="text-gray-600 text-sm">تقليل حجم الملفات مع الحفاظ على الجودة وتحسين الأداء</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Service 3 Features -->
                    <div>
                        <h4 class="text-xl font-bold text-purple-600 mb-6">مكتبة الوثائق والمذكرات</h4>
                        <div class="grid md:grid-cols-3 gap-6">
                            <div class="bg-white p-5 rounded-lg shadow-md">
                                <div class="flex items-center mb-3">
                                    <div class="bg-purple-100 p-2 rounded-full">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-600" viewBox="0 0 20 20" fill="none" stroke="currentColor">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <h5 class="mr-3 font-bold">وثائق ومذكرات متنوعة</h5>
                                </div>
                                <p class="text-gray-600 text-sm">مكتبة شاملة من الوثائق الرسمية والمذكرات الدراسية والمواد التعليمية</p>
                            </div>
                            
                            <div class="bg-white p-5 rounded-lg shadow-md">
                                <div class="flex items-center mb-3">
                                    <div class="bg-purple-100 p-2 rounded-full">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-600" viewBox="0 0 20 20" fill="none" stroke="currentColor">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <h5 class="mr-3 font-bold">تجربة تصفح متطورة</h5>
                                </div>
                                <p class="text-gray-600 text-sm">واجهة تصفح سهلة الاستخدام مع خيارات بحث متقدمة داخل المستندات</p>
                            </div>
                            
                            <div class="bg-white p-5 rounded-lg shadow-md">
                                <div class="flex items-center mb-3">
                                    <div class="bg-purple-100 p-2 rounded-full">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-600" viewBox="0 0 20 20" fill="none" stroke="currentColor">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <h5 class="mr-3 font-bold">وصول آمن وسهل</h5>
                                </div>
                                <p class="text-gray-600 text-sm">وصول حصري للمشتركين مع حماية الملفات باستخدام تقنيات Signed URLs</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Subscription Section -->
            <div class="py-16 bg-white rounded-2xl shadow-lg">
                <div class="max-w-5xl mx-auto px-6">
                    <h2 class="text-3xl md:text-4xl font-bold text-center text-gray-900 mb-8">
                        اشترك الآن واستفد من جميع الخدمات
                    </h2>
                    
                    <p class="text-xl text-center text-gray-600 mb-12">
                        نظام اشتراك شهري بسيط يمنحك وصولاً كاملاً لجميع خدماتنا
                    </p>
                    
                    <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl p-8 text-center text-white">
                        <div class="text-5xl font-bold mb-4">2000 <span class="text-2xl">دج</span></div>
                        <div class="text-xl mb-6">اشتراك سنوي</div>
                        
                        <ul class="mb-8 space-y-3 text-right max-w-md mx-auto">
                            <li class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                                إنشاء سير ذاتية غير محدودة باستخدام الذكاء الاصطناعي
                            </li>
                            <li class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                                معالجة ملفات غير محدودة (تحويل، دمج، تقسيم، ضغط)
                            </li>
                            <li class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                                وصول كامل لمكتبة الوثائق والمذكرات
                            </li>
                            <li class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                                دعم فني على مدار الساعة
                            </li>
                        </ul>
                        
                        <Link v-if="!($page.props.auth && $page.props.auth.user)" href="/register" class="inline-block px-8 py-3 bg-white text-blue-600 font-bold rounded-lg hover:bg-gray-100 transition">
                            أنشئ حسابك الآن
                        </Link>
                        <Link v-else href="/subscription" class="inline-block px-8 py-3 bg-white text-blue-600 font-bold rounded-lg hover:bg-gray-100 transition">
                            اشترك الآن
                        </Link>
                    </div>
                </div>
            </div>
            
            <!-- How It Works Section -->
            <div class="py-16">
                <h2 class="text-3xl md:text-4xl font-bold text-center text-gray-900 mb-16">
                    كيف تعمل منصة <span class="text-blue-600">CVHub</span>؟
                </h2>
                
                <div class="grid md:grid-cols-4 gap-8">
                    <div class="text-center">
                        <div class="w-16 h-16 bg-blue-600 text-white rounded-full flex items-center justify-center text-2xl font-bold mx-auto mb-4">1</div>
                        <h3 class="text-xl font-bold mb-2">سجل حساباً</h3>
                        <p class="text-gray-600">أنشئ حساباً جديداً في بضع ثوانٍ</p>
                    </div>
                    
                    <div class="text-center">
                        <div class="w-16 h-16 bg-blue-600 text-white rounded-full flex items-center justify-center text-2xl font-bold mx-auto mb-4">2</div>
                        <h3 class="text-xl font-bold mb-2">اشترك في الخدمة</h3>
                        <p class="text-gray-600">اختر الاشتراك السنوي وادفع بسهولة عبر بريدي موب</p>
                    </div>
                    
                    <div class="text-center">
                        <div class="w-16 h-16 bg-blue-600 text-white rounded-full flex items-center justify-center text-2xl font-bold mx-auto mb-4">3</div>
                        <h3 class="text-xl font-bold mb-2">استخدم الخدمات</h3>
                        <p class="text-gray-600">استمتع بجميع الخدمات المتاحة على المنصة</p>
                    </div>
                    
                    <div class="text-center">
                        <div class="w-16 h-16 bg-blue-600 text-white rounded-full flex items-center justify-center text-2xl font-bold mx-auto mb-4">4</div>
                        <h3 class="text-xl font-bold mb-2">نمِّ مهاراتك</h3>
                        <p class="text-gray-600">استفد من المكتبة الرقمية لتطوير معارفك ومهاراتك</p>
                    </div>
                </div>
            </div>
            
            <!-- CTA Section -->
            <div class="py-16 text-center">
                <div class="max-w-3xl mx-auto bg-blue-600 rounded-xl py-10 px-6 md:px-10 shadow-xl">
                    <h2 class="text-2xl md:text-3xl font-bold text-white mb-4">
                        ابدأ في رحلتك التعليمية والمهنية اليوم
                    </h2>
                    
                    <p class="text-blue-100 mb-8 md:text-lg">
                        انضم إلى آلاف الطلاب والمهنيين الذين يثقون بـ CVHub لتطوير مهاراتهم وتحقيق النجاح
                    </p>
                    
                    <Link v-if="!($page.props.auth && $page.props.auth.user)" href="/register" class="px-8 py-3 bg-white text-blue-600 rounded-lg text-lg font-medium hover:bg-blue-50 transition">
                        سجل مجاناً الآن
                    </Link>
                    
                    <Link v-else href="/dashboard" class="dashboard-cta px-8 py-3 bg-white text-blue-600 rounded-lg text-lg font-medium relative overflow-hidden group transition-all duration-300 hover:shadow-lg">
                        <span class="flex items-center justify-center">
                            <i class="fas fa-tachometer-alt ml-2"></i>
                            <span>الذهاب إلى لوحة التحكم</span>
                        </span>
                        <span class="absolute inset-0 bg-blue-50 transform scale-x-0 group-hover:scale-x-100 transition-transform origin-left duration-300"></span>
                    </Link>
                </div>
            </div>
            
            <!-- Animated Footer -->
            <footer class="bg-gradient-to-r from-blue-800 to-indigo-900 text-white relative">
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
                
                <div class="container mx-auto px-6 py-12">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                        <!-- Logo and Description -->
                        <div class="md:col-span-1">
                            <div class="text-3xl font-bold text-white">CVHub</div>
                            <p class="mt-3 text-gray-200">
                                منصة الخدمات الطلابية المتكاملة لإدارة وتحرير ومشاركة السير الذاتية بشكل احترافي
                            </p>
                            <div class="mt-6 flex space-x-4 space-x-reverse">
                                <a href="#" class="text-white hover:text-blue-300 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"></path>
                                    </svg>
                                </a>
                                <a href="#" class="text-white hover:text-blue-300 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"></path>
                                    </svg>
                                </a>
                                <a href="#" class="text-white hover:text-blue-300 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M9 8h-3v4h3v12h5v-12h3.642l.358-4h-4v-1.667c0-.955.192-1.333 1.115-1.333h2.885v-5h-3.808c-3.596 0-5.192 1.583-5.192 4.615v3.385z"></path>
                                    </svg>
                                </a>
                            </div>
                        </div>
                        
                        <!-- Quick Links -->
                        <div class="md:col-span-1">
                            <h3 class="text-lg font-bold mb-4 text-white">روابط سريعة</h3>
                            <ul class="space-y-2">
                                <li>
                                    <a href="#services" class="text-gray-200 hover:text-white transition-colors flex items-center">
                                        <i class="fas fa-chevron-left ml-2 text-xs"></i>
                                        <span>خدماتنا</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="#pricing" class="text-gray-200 hover:text-white transition-colors flex items-center">
                                        <i class="fas fa-chevron-left ml-2 text-xs"></i>
                                        <span>الأسعار</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="#about" class="text-gray-200 hover:text-white transition-colors flex items-center">
                                        <i class="fas fa-chevron-left ml-2 text-xs"></i>
                                        <span>كيف تعمل المنصة</span>
                                    </a>
                                </li>
                                <li>
                                    <Link href="/login" class="text-gray-200 hover:text-white transition-colors flex items-center">
                                        <i class="fas fa-chevron-left ml-2 text-xs"></i>
                                        <span>تسجيل الدخول</span>
                                    </Link>
                                </li>
                                <li>
                                    <Link href="/register" class="text-gray-200 hover:text-white transition-colors flex items-center">
                                        <i class="fas fa-chevron-left ml-2 text-xs"></i>
                                        <span>إنشاء حساب</span>
                                    </Link>
                                </li>
                            </ul>
                        </div>
                        
                        <!-- Services -->
                        <div class="md:col-span-1">
                            <h3 class="text-lg font-bold mb-4 text-white">خدماتنا</h3>
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
                            <h3 class="text-lg font-bold mb-4 text-white">تواصل معنا</h3>
                            <ul class="space-y-3">
                                <li class="flex items-start">
                                    <i class="fas fa-map-marker-alt mt-1 ml-3 text-blue-300"></i>
                                    <span class="text-gray-200">الجزائر، الجزائر العاصمة</span>
                                </li>
                                <li class="flex items-center">
                                    <i class="fas fa-envelope ml-3 text-blue-300"></i>
                                    <span class="text-gray-200">info@cvhub.dz</span>
                                </li>
                                <li class="flex items-center">
                                    <i class="fas fa-phone-alt ml-3 text-blue-300"></i>
                                    <span class="text-gray-200">+213 00 00 00 00</span>
                                </li>
                                <li class="flex items-center">
                                    <i class="fas fa-clock ml-3 text-blue-300"></i>
                                    <span class="text-gray-200">متاح 24/7</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Copyright -->
                    <div class="mt-12 pt-8 border-t border-blue-700 text-center text-gray-300">
                        <p>&copy; {{ new Date().getFullYear() }} CVHub. جميع الحقوق محفوظة.</p>
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