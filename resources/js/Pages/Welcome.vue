<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import HeroSection from '@/components/HeroSection.vue';
import ServicesSection from '@/components/ServicesSection.vue';
import PricingSection from '@/components/PricingSection.vue';
import TestimonialsSection from '@/components/TestimonialsSection.vue';
import HowItWorksSection from '@/components/HowItWorksSection.vue';
import CursorEffect from '@/components/CursorEffect.vue';
import ModernLogo from '@/components/ModernLogo.vue';
import AOS from 'aos';

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
    // Initialize AOS animation library
    AOS.init({
        duration: 800,
        easing: 'ease-in-out',
        once: true,
        mirror: false
    });
    
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

<template>
    <Head title="CVHub - منصة الخدمات الطلابية المتكاملة" />
    
    <div class="app-container" dir="rtl">
        <!-- Background particles -->
        <div class="particles">
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
        </div>
        
        <!-- Custom cursor effect -->
        <CursorEffect />
        
        <!-- Modern Header -->
        <header ref="header" class="site-header">
            <div class="container mx-auto px-6 py-4">
                <div class="flex justify-between items-center">
                    <Link href="/" class="logo-link">
                        <ModernLogo size="md" />
                    </Link>
                    
                    <nav v-if="canLogin" class="flex items-center space-x-6 space-x-reverse">
                        <template v-if="$page.props.auth && $page.props.auth.user">
                            <Link href="/dashboard" class="nav-link">
                                لوحة التحكم
                            </Link>
                        </template>
                        
                        <template v-else>
                            <Link href="/login" class="nav-link">
                                تسجيل الدخول
                            </Link>
                            
                            <Link v-if="canRegister" href="/register" class="cta-button">
                                إنشاء حساب
                            </Link>
                        </template>
                    </nav>
                </div>
            </div>
        </header>
        
        <main>
            <!-- Hero Section -->
            <HeroSection>
                <template #hero-image>
                    <div class="hero-illustration glow" data-aos="fade-left" data-aos-delay="300">
                        <img src="/images/hero-illustration.svg" alt="CVHub Platform" class="w-full h-auto">
                    </div>
                </template>
                <template #hero-content>
                    <div data-aos="fade-right" data-aos-delay="150">
                        <h1 class="hero-title">منصة <span class="text-gradient">CVHub</span> للخدمات الطلابية المتكاملة</h1>
                        <p class="hero-description">نقدم لك مجموعة متكاملة من الخدمات الطلابية بما في ذلك إنشاء السيرة الذاتية بالذكاء الاصطناعي، معالجة الملفات، والمكتبة الرقمية.</p>
                    </div>
                </template>
                <template #cta-buttons>
                    <div class="flex flex-col md:flex-row gap-4 mt-8" data-aos="fade-up" data-aos-delay="450">
                        <Link v-if="!($page.props.auth && $page.props.auth.user)" href="/register" class="primary-button">
                            إنشاء حساب مجاني
                        </Link>
                        
                        <Link v-else href="/dashboard" class="primary-button">
                            لوحة التحكم
                        </Link>
                        
                        <a href="#services" class="secondary-button">
                            اكتشف خدماتنا
                        </a>
                    </div>
                </template>
            </HeroSection>
            
            <!-- Shape divider -->
            <div class="shape-divider">
                <svg data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
                    <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z" class="shape-fill"></path>
                </svg>
            </div>
            
            <!-- Services Section -->
            <div data-aos="fade-up">
                <ServicesSection />
            </div>
            
            <!-- Testimonials Section -->
            <div data-aos="fade-up">
                <TestimonialsSection />
            </div>
            
            <!-- Shape divider -->
            <div class="shape-divider shape-divider-top">
                <svg data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
                    <path d="M985.66,92.83C906.67,72,823.78,31,743.84,14.19c-82.26-17.34-168.06-16.33-250.45.39-57.84,11.73-114,31.07-172,41.86A600.21,600.21,0,0,1,0,27.35V120H1200V95.8C1132.19,118.92,1055.71,111.31,985.66,92.83Z" class="shape-fill"></path>
                </svg>
            </div>
            
            <!-- Pricing Section -->
            <div data-aos="fade-up">
                <PricingSection />
            </div>
            
            <!-- How It Works Section -->
            <div data-aos="fade-up">
                <HowItWorksSection />
            </div>
            
            <!-- Shape divider for footer -->
            <div class="shape-divider shape-divider-footer">
                <svg data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
                    <path d="M0,0V46.29c47.79,22.2,103.59,32.17,158,28,70.36-5.37,136.33-33.31,206.8-37.5C438.64,32.43,512.34,53.67,583,72.05c69.27,18,138.3,24.88,209.4,13.08,36.15-6,69.85-17.84,104.45-29.34C989.49,25,1113-14.29,1200,52.47V0Z" opacity=".25" class="shape-fill"></path>
                    <path d="M0,0V15.81C13,36.92,27.64,56.86,47.69,72.05,99.41,111.27,165,111,224.58,91.58c31.15-10.15,60.09-26.07,89.67-39.8,40.92-19,84.73-46,130.83-49.67,36.26-2.85,70.9,9.42,98.6,31.56,31.77,25.39,62.32,62,103.63,73,40.44,10.79,81.35-6.69,119.13-24.28s75.16-39,116.92-43.05c59.73-5.85,113.28,22.88,168.9,38.84,30.2,8.66,59,6.17,87.09-7.5,22.43-10.89,48-26.93,60.65-49.24V0Z" opacity=".5" class="shape-fill"></path>
                    <path d="M0,0V5.63C149.93,59,314.09,71.32,475.83,42.57c43-7.64,84.23-20.12,127.61-26.46,59-8.63,112.48,12.24,165.56,35.4C827.93,77.22,886,95.24,951.2,90c86.53-7,172.46-45.71,248.8-84.81V0Z" class="shape-fill"></path>
                </svg>
            </div>
        </main>
        
        <!-- Animated Footer -->
        <footer class="bg-gradient-to-r from-primary-800 to-primary-900 text-white">
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
                    <div class="md:col-span-1" data-aos="fade-up" data-aos-delay="100">
                        <Link href="/" class="inline-block">
                            <div class="text-3xl font-bold text-white">CVHub</div>
                        </Link>
                        <p class="mt-3 text-gray-200">
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
                    <div class="md:col-span-1" data-aos="fade-up" data-aos-delay="200">
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
                    <div class="md:col-span-1" data-aos="fade-up" data-aos-delay="300">
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
                    <div class="md:col-span-1" data-aos="fade-up" data-aos-delay="400">
                        <h3 class="text-lg font-bold mb-4 text-white">تواصل معنا</h3>
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
</template>

<style>
@import 'aos/dist/aos.css';

/* Global Styles */
:root {
  --primary-color: #0066ff;
  --primary-dark: #0052cc;
  --primary-light: #3380ff;
  --accent-color: #ff9900;
  --accent-light: #ffb84d;
  --text-color: #1f2937;
  --text-light: #6b7280;
  --bg-light: #f9fafb;
  --bg-gradient: linear-gradient(135deg, #f0f7ff 0%, #ffffff 100%);
}

body {
  font-family: 'Cairo', 'Tajawal', sans-serif;
  color: var(--text-color);
  background: var(--bg-light);
}

.app-container {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  overflow-x: hidden;
  position: relative;
  background: var(--bg-gradient);
}

.app-container::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 100vh;
  background: 
    radial-gradient(circle at 70% 30%, rgba(0, 102, 255, 0.05) 0%, rgba(0, 102, 255, 0) 70%),
    radial-gradient(circle at 30% 70%, rgba(255, 153, 0, 0.05) 0%, rgba(255, 153, 0, 0) 70%);
  pointer-events: none;
  z-index: -1;
}

/* Animated background particles */
.particles {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: -2;
  overflow: hidden;
}

.particle {
  position: absolute;
  border-radius: 50%;
  background: var(--primary-color);
  opacity: 0.3;
}

.particle:nth-child(1) {
  top: 20%;
  left: 10%;
  width: 80px;
  height: 80px;
  animation: float-slow 15s infinite ease-in-out;
}

.particle:nth-child(2) {
  top: 60%;
  left: 80%;
  width: 60px;
  height: 60px;
  animation: float-slow 12s infinite ease-in-out reverse;
}

.particle:nth-child(3) {
  top: 80%;
  left: 30%;
  width: 40px;
  height: 40px;
  background: var(--accent-color);
  animation: float-slow 18s infinite ease-in-out;
}

.particle:nth-child(4) {
  top: 10%;
  left: 70%;
  width: 30px;
  height: 30px;
  background: var(--accent-color);
  animation: float-slow 20s infinite ease-in-out reverse;
}

.particle:nth-child(5) {
  top: 50%;
  left: 50%;
  width: 20px;
  height: 20px;
  animation: float-slow 25s infinite ease-in-out;
}

main {
  flex: 1;
  padding-top: 80px; /* Account for fixed header */
}

.container {
  max-width: 1280px;
  margin: 0 auto;
}

/* Header Styles */
.site-header {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  background: rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  z-index: 1000;
  border-bottom: 1px solid rgba(229, 231, 235, 0.5);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
  transition: all 0.3s ease;
}

.site-header.scrolled {
  background: rgba(255, 255, 255, 0.95);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
  padding: 0.5rem 0;
}

.logo-link {
  display: flex;
  align-items: center;
  transform: translateY(0);
  transition: transform 0.3s ease;
}

.logo-link:hover {
  transform: translateY(-2px);
}

.nav-link {
  font-weight: 500;
  color: var(--text-color);
  transition: all 0.3s ease;
  padding: 0.5rem;
  position: relative;
}

.nav-link::after {
  content: '';
  position: absolute;
  bottom: 0;
  right: 0;
  width: 0;
  height: 2px;
  background: var(--primary-color);
  transition: width 0.3s ease;
}

.nav-link:hover {
  color: var(--primary-color);
}

.nav-link:hover::after {
  width: 100%;
  right: auto;
  left: 0;
}

.cta-button {
  display: inline-block;
  background: linear-gradient(to right, var(--primary-color), var(--primary-light));
  color: white;
  font-weight: 500;
  padding: 0.5rem 1.25rem;
  border-radius: 0.5rem;
  transition: all 0.3s ease;
  box-shadow: 0 4px 6px rgba(0, 102, 255, 0.2);
  position: relative;
  overflow: hidden;
}

.cta-button::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
  transition: left 0.7s ease;
}

.cta-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 10px rgba(0, 102, 255, 0.3);
}

.cta-button:hover::before {
  left: 100%;
}

/* Button Styles */
.primary-button {
  display: inline-block;
  background: linear-gradient(to right, var(--primary-color), var(--primary-light));
  color: white;
  font-weight: 600;
  padding: 0.875rem 1.5rem;
  border-radius: 0.5rem;
  transition: all 0.3s ease;
  box-shadow: 0 4px 6px rgba(0, 102, 255, 0.2);
  position: relative;
  overflow: hidden;
}

.primary-button::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
  transition: left 0.7s ease;
}

.primary-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 15px rgba(0, 102, 255, 0.3);
}

.primary-button:hover::before {
  left: 100%;
}

.secondary-button {
  display: inline-block;
  background: white;
  color: var(--primary-color);
  font-weight: 600;
  padding: 0.875rem 1.5rem;
  border-radius: 0.5rem;
  border: 2px solid rgba(0, 102, 255, 0.2);
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
  z-index: 1;
}

.secondary-button::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 102, 255, 0.05);
  z-index: -1;
  transform: scaleX(0);
  transform-origin: right;
  transition: transform 0.3s ease;
}

.secondary-button:hover {
  border-color: rgba(0, 102, 255, 0.3);
}

.secondary-button:hover::before {
  transform: scaleX(1);
  transform-origin: left;
}

/* Footer Styles */
.site-footer {
  background-color: #f9fafb;
  border-top: 1px solid #e5e7eb;
  position: relative;
  overflow: hidden;
}

.site-footer::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: 
    radial-gradient(circle at 90% 10%, rgba(0, 102, 255, 0.03) 0%, transparent 70%),
    radial-gradient(circle at 10% 90%, rgba(0, 102, 255, 0.03) 0%, transparent 70%);
  pointer-events: none;
}

.footer-col {
  margin-bottom: 2rem;
}

.footer-title {
  font-weight: 700;
  font-size: 1.125rem;
  color: var(--text-color);
  margin-bottom: 1rem;
  position: relative;
  display: inline-block;
}

.footer-title::after {
  content: '';
  position: absolute;
  bottom: -5px;
  right: 0;
  width: 40px;
  height: 2px;
  background: linear-gradient(to right, var(--primary-color), var(--primary-light));
  border-radius: 2px;
}

.footer-text {
  color: var(--text-light);
  font-size: 0.875rem;
  line-height: 1.5;
}

.footer-links {
  list-style: none;
  padding: 0;
  margin: 0;
}

.footer-links li {
  margin-bottom: 0.75rem;
}

.footer-links a {
  color: var(--text-light);
  transition: all 0.3s ease;
  font-size: 0.875rem;
  position: relative;
  display: inline-block;
}

.footer-links a::before {
  content: '•';
  position: absolute;
  right: -12px;
  opacity: 0;
  transition: all 0.3s ease;
}

.footer-links a:hover {
  color: var(--primary-color);
  transform: translateX(-5px);
}

.footer-links a:hover::before {
  opacity: 1;
  right: -8px;
}

.footer-contact {
  list-style: none;
  padding: 0;
  margin: 0;
}

.footer-contact li {
  display: flex;
  align-items: center;
  margin-bottom: 0.75rem;
  color: var(--text-light);
  font-size: 0.875rem;
  transition: all 0.3s ease;
}

.footer-contact li:hover {
  color: var(--primary-color);
}

.footer-icon {
  width: 1.25rem;
  height: 1.25rem;
  margin-left: 0.75rem;
  color: var(--primary-color);
  transition: all 0.3s ease;
}

.footer-contact li:hover .footer-icon {
  transform: scale(1.1);
}

.social-links {
  display: flex;
  gap: 1rem;
  margin-top: 1.5rem;
}

.social-link {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 9999px;
  background-color: white;
  color: var(--primary-color);
  border: 1px solid #e5e7eb;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.social-link::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: var(--primary-color);
  border-radius: 9999px;
  transform: scale(0);
  transition: transform 0.3s ease;
  z-index: -1;
}

.social-link:hover {
  color: white;
  border-color: var(--primary-color);
  transform: translateY(-2px);
}

.social-link:hover::before {
  transform: scale(1);
}

.social-icon {
  width: 1.25rem;
  height: 1.25rem;
  fill: currentColor;
  transition: all 0.3s ease;
  z-index: 1;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
  .footer-col {
    margin-bottom: 2rem;
  }
  
  .nav-link, .cta-button {
    font-size: 0.875rem;
  }
  
  .primary-button, .secondary-button {
    width: 100%;
    text-align: center;
    margin-bottom: 0.75rem;
  }
  
  .footer-title::after {
    width: 30px;
  }
}

/* Additional Hero Styles */
.hero-title {
  font-size: 2.5rem;
  font-weight: 800;
  line-height: 1.2;
  margin-bottom: 1rem;
  color: var(--text-color);
  position: relative;
}

.hero-title::after {
  content: '';
  position: absolute;
  width: 60px;
  height: 4px;
  background: linear-gradient(to right, var(--accent-color), var(--accent-light));
  bottom: -10px;
  right: 0;
  border-radius: 2px;
}

.hero-description {
  font-size: 1.125rem;
  line-height: 1.6;
  color: var(--text-light);
  margin-bottom: 2rem;
  position: relative;
}

.text-gradient {
  background: linear-gradient(to right, var(--primary-color), var(--primary-light));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  color: transparent;
  position: relative;
  display: inline-block;
}

.text-gradient::after {
  content: '';
  position: absolute;
  bottom: -3px;
  left: 0;
  width: 100%;
  height: 3px;
  background: linear-gradient(to right, var(--primary-color), var(--primary-light));
  border-radius: 3px;
}

.hero-illustration {
  position: relative;
  animation: float 6s ease-in-out infinite;
  filter: drop-shadow(0 10px 15px rgba(0, 0, 0, 0.1));
}

.hero-illustration::before {
  content: '';
  position: absolute;
  bottom: -20px;
  left: 50%;
  transform: translateX(-50%);
  width: 70%;
  height: 20px;
  background: radial-gradient(ellipse at center, rgba(0, 0, 0, 0.1) 0%, rgba(0, 0, 0, 0) 70%);
  border-radius: 50%;
}

/* Custom shape dividers */
.shape-divider {
  position: absolute;
  bottom: 0;
  left: 0;
  width: 100%;
  overflow: hidden;
  line-height: 0;
  z-index: -1;
}

.shape-divider svg {
  position: relative;
  display: block;
  width: calc(100% + 1.3px);
  height: 60px;
}

.shape-divider .shape-fill {
  fill: #FFFFFF;
}

.shape-divider-top {
  top: 0;
  bottom: auto;
  transform: rotate(180deg);
}

.shape-divider-footer {
  top: -60px;
  bottom: auto;
}

.shape-divider-footer .shape-fill {
  fill: #f9fafb;
}

/* Animation Keyframes */
@keyframes float {
  0% {
    transform: translateY(0px);
  }
  50% {
    transform: translateY(-10px);
  }
  100% {
    transform: translateY(0px);
  }
}

@keyframes float-slow {
  0% {
    transform: translate(0, 0);
  }
  50% {
    transform: translate(10px, 10px);
  }
  100% {
    transform: translate(0, 0);
  }
}

@keyframes pulse {
  0% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.05);
  }
  100% {
    transform: scale(1);
  }
}

@keyframes shine {
  0% {
    background-position: -100% 0;
  }
  100% {
    background-position: 200% 0;
  }
}

/* Glowing effect */
.glow {
  position: relative;
}

.glow::after {
  content: '';
  position: absolute;
  top: -20px;
  left: -20px;
  right: -20px;
  bottom: -20px;
  background: radial-gradient(circle at center, rgba(0, 102, 255, 0.1) 0%, rgba(0, 102, 255, 0) 70%);
  border-radius: 50%;
  z-index: -1;
  animation: pulse 3s infinite ease-in-out;
}

@media (max-width: 768px) {
  .hero-title {
    font-size: 2rem;
  }
  
  .hero-description {
    font-size: 1rem;
  }
  
  .shape-divider svg {
    height: 40px;
  }
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
