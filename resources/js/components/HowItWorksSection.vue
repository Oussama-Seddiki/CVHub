<template>
  <section id="about" class="how-it-works-section py-16 md:py-24">
    <div class="container mx-auto px-6">
      <div class="text-center mb-16" data-aos="fade-up">
        <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
          كيف تعمل منصة <span class="text-gradient">CVHub</span>؟
        </h2>
        <p class="mt-4 text-lg text-gray-600 max-w-2xl mx-auto">
          خطوات بسيطة للاستفادة من جميع خدماتنا بطريقة سهلة وفعالة
        </p>
      </div>
      
      <div class="process-steps">
        <!-- Step 1 -->
        <div class="process-step" data-aos="fade-up" data-aos-delay="100">
          <div class="step-number">١</div>
          <div class="step-content">
            <div class="step-icon blue-gradient">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
            </div>
            <h3 class="step-title">إنشاء حساب</h3>
            <p class="step-description">
              أنشئ حسابك مجاناً في ثوانٍ معدودة باستخدام بريدك الإلكتروني أو عبر حسابات التواصل الاجتماعي
            </p>
          </div>
        </div>
        
        <!-- Step 2 -->
        <div class="process-step" data-aos="fade-up" data-aos-delay="200">
          <div class="step-number">٢</div>
          <div class="step-content">
            <div class="step-icon green-gradient">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <h3 class="step-title">اختر خطة الاشتراك</h3>
            <p class="step-description">
              اختر الباقة المناسبة لاحتياجاتك من بين باقاتنا المتنوعة، أو ابدأ بالنسخة المجانية للتجربة
            </p>
          </div>
        </div>
        
        <!-- Step 3 -->
        <div class="process-step" data-aos="fade-up" data-aos-delay="300">
          <div class="step-number">٣</div>
          <div class="step-content">
            <div class="step-icon purple-gradient">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
            </div>
            <h3 class="step-title">استخدم الخدمات</h3>
            <p class="step-description">
              ابدأ باستخدام خدمات إنشاء السير الذاتية، معالجة الملفات، أو تصفح المكتبة الرقمية فوراً
            </p>
          </div>
        </div>
        
        <!-- Step 4 -->
        <div class="process-step" data-aos="fade-up" data-aos-delay="400">
          <div class="step-number">٤</div>
          <div class="step-content">
            <div class="step-icon orange-gradient">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
              </svg>
            </div>
            <h3 class="step-title">تنزيل وتصدير</h3>
            <p class="step-description">
              قم بتنزيل السير الذاتية والملفات المعالجة بصيغ مختلفة أو حفظها في حسابك للاستخدام المستقبلي
            </p>
          </div>
        </div>
      </div>
      
      <div class="text-center mt-16" data-aos="fade-up">
        <p class="mb-8 text-lg text-gray-600">جاهز للبدء؟ انضم إلى آلاف الطلاب والمهنيين الذين يستخدمون CVHub</p>
        <div class="flex flex-col sm:flex-row justify-center gap-4">
          <button class="cta-button primary">إنشاء حساب مجاني</button>
          <button class="cta-button secondary">اكتشف المزيد</button>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
// Component is purely presentational, no additional scripts needed
</script>

<style scoped>
.text-gradient {
  background: linear-gradient(90deg, #0066ff 0%, #3380ff 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  display: inline;
}

.process-steps {
  @apply max-w-4xl mx-auto;
}

.process-step {
  @apply flex mb-16 last:mb-0 relative;
}

.step-number {
  @apply w-12 h-12 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center text-2xl font-bold mr-6 z-10 flex-shrink-0;
  font-family: 'Tajawal', sans-serif;
}

.step-content {
  @apply flex-1;
}

.step-icon {
  @apply w-16 h-16 rounded-full text-white flex items-center justify-center mb-4;
}

.blue-gradient {
  @apply bg-gradient-to-r from-blue-500 to-blue-600;
}

.green-gradient {
  @apply bg-gradient-to-r from-green-500 to-teal-500;
}

.purple-gradient {
  @apply bg-gradient-to-r from-purple-500 to-pink-500;
}

.orange-gradient {
  @apply bg-gradient-to-r from-orange-500 to-amber-500;
}

.step-title {
  @apply text-xl font-bold text-gray-900 mb-3;
}

.step-description {
  @apply text-gray-600;
}

.process-step::before {
  content: '';
  @apply absolute top-12 bottom-0 right-6 w-0.5 bg-blue-100 -z-10;
  transform: translateX(50%);
}

.process-step:last-child::before {
  display: none;
}

.cta-button {
  @apply px-8 py-3 rounded-lg text-lg font-medium transition duration-300;
}

.cta-button.primary {
  @apply bg-gradient-to-r from-blue-600 to-blue-400 text-white shadow-md;
  
  &:hover {
    @apply shadow-lg transform -translate-y-1;
  }
}

.cta-button.secondary {
  @apply bg-white text-blue-600 border-2 border-blue-200;
  
  &:hover {
    @apply bg-blue-50;
  }
}

/* RTL Support */
:dir(rtl) .process-step {
  direction: rtl;
}

:dir(rtl) .step-number {
  margin-right: 0;
  margin-left: 1.5rem;
}

:dir(rtl) .process-step::before {
  right: 6;
  left: auto;
  transform: translateX(-50%);
}

@media (max-width: 640px) {
  .process-step {
    flex-direction: column;
  }
  
  .step-number {
    margin-bottom: 1rem;
    margin-right: 0;
    margin-left: 0;
  }
  
  .process-step::before {
    left: 1.5rem;
    top: 3rem;
    bottom: -1rem;
    width: 0.125rem;
    height: auto;
  }
  
  :dir(rtl) .step-number {
    margin-left: 0;
  }
  
  :dir(rtl) .process-step::before {
    right: 1.5rem;
    left: auto;
  }
}
</style>
