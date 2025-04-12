<template>
  <section class="hero-section py-16 md:py-24">
    <div class="container mx-auto px-6">
      <div class="flex flex-col md:flex-row items-center">
        <div class="w-full md:w-1/2 text-center md:text-right" data-aos="fade-left" data-aos-delay="100">
          <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-gray-900 leading-tight">
            المنصة <span class="text-gradient">الطلابية</span> المتكاملة للخدمات الرقمية
          </h1>
          
          <p class="mt-6 text-lg text-gray-600 leading-relaxed">
            نقدم لك ثلاث خدمات أساسية في منصة واحدة: إنشاء السير الذاتية بالذكاء الاصطناعي، معالجة الملفات، ومكتبة رقمية للوثائق والمذكرات التعليمية
          </p>
          
          <div class="mt-10 flex flex-col sm:flex-row justify-center md:justify-end gap-4">
            <slot name="cta-buttons"></slot>
          </div>
        </div>
        
        <div class="w-full md:w-1/2 mt-12 md:mt-0 md:pl-12" data-aos="fade-right" data-aos-delay="200">
          <div class="relative">
            <div class="absolute -inset-1 rounded-lg bg-gradient-to-r from-blue-600 to-blue-400 opacity-30 blur"></div>
            <div class="floating-docs-container relative">
              <div ref="docElement1" class="floating-doc doc1"></div>
              <div ref="docElement2" class="floating-doc doc2"></div>
              <div ref="docElement3" class="floating-doc doc3"></div>
              <img src="/images/hero-cv.svg" alt="CVHub Services" class="relative z-10 w-full h-auto max-w-lg mx-auto rounded-lg shadow-lg">
            </div>
          </div>
        </div>
      </div>
      
      <div class="mt-20 flex justify-center">
        <div class="animate-bounce flex items-center justify-center rounded-full w-12 h-12 bg-white shadow-md">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
          </svg>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import gsap from 'gsap';

const docElement1 = ref(null);
const docElement2 = ref(null);
const docElement3 = ref(null);

onMounted(() => {
  // Floating documents animation
  gsap.to(docElement1.value, {
    y: '-20px',
    x: '10px',
    rotation: -5,
    duration: 3,
    ease: 'sine.inOut',
    repeat: -1,
    yoyo: true
  });
  
  gsap.to(docElement2.value, {
    y: '-15px',
    x: '-12px',
    rotation: 8,
    duration: 2.7,
    ease: 'sine.inOut',
    repeat: -1,
    yoyo: true,
    delay: 0.3
  });
  
  gsap.to(docElement3.value, {
    y: '-25px',
    rotation: -3,
    duration: 4,
    ease: 'sine.inOut',
    repeat: -1,
    yoyo: true,
    delay: 0.5
  });
});
</script>

<style scoped>
.hero-section {
  position: relative;
  overflow: hidden;
}

.text-gradient {
  background: linear-gradient(90deg, #0066ff 0%, #3380ff 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  display: inline;
}

.floating-docs-container {
  position: relative;
  width: 100%;
  height: 100%;
}

.floating-doc {
  position: absolute;
  background-color: white;
  border-radius: 8px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
  z-index: 20;
}

.doc1 {
  width: 35%;
  height: 45%;
  top: -10%;
  right: 5%;
  transform: rotate(-8deg);
  background-image: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
  z-index: 12;
}

.doc2 {
  width: 30%;
  height: 40%;
  top: 60%;
  right: 0%;
  transform: rotate(6deg);
  background-image: linear-gradient(135deg, #f6f9ff 0%, #e6effd 100%);
  z-index: 11;
}

.doc3 {
  width: 25%;
  height: 35%;
  top: 45%;
  left: 5%;
  transform: rotate(-5deg);
  background-image: linear-gradient(135deg, #e6f1ff 0%, #cce3ff 100%);
  z-index: 11;
}

/* Apply this class only on Arabic RTL layout */
:dir(rtl) .container {
  direction: rtl;
}
</style>
