<template>
  <section id="pricing" class="pricing-section py-16 md:py-24 bg-gradient-to-b from-white to-blue-50">
    <div class="container mx-auto px-6">
      <div class="text-center mb-16" data-aos="fade-up">
        <h2 class="text-3xl md:text-4xl font-bold text-gray-900">
          اختر <span class="text-gradient">الباقة</span> المناسبة لك
        </h2>
        <p class="mt-4 text-lg text-gray-600 max-w-2xl mx-auto">
          باقات مرنة تناسب احتياجاتك المختلفة مع إمكانية الترقية أو الإلغاء في أي وقت
        </p>
      </div>
      
      <div class="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto">
        <!-- Basic Plan -->
        <div class="pricing-card" data-aos="fade-up" data-aos-delay="100">
          <div class="p-8">
            <h3 class="pricing-title">أساسي</h3>
            <div class="pricing-price">
              <span class="currency">ر.س</span>
              <span class="amount">69</span>
              <span class="period">/شهرياً</span>
            </div>
            
            <div class="pricing-features">
              <div class="pricing-feature">
                <svg class="feature-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>تصميم 5 سير ذاتية شهرياً</span>
              </div>
              
              <div class="pricing-feature">
                <svg class="feature-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>معالجة 10 ملفات شهرياً</span>
              </div>
              
              <div class="pricing-feature">
                <svg class="feature-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>وصول محدود للمكتبة الرقمية</span>
              </div>
              
              <div class="pricing-feature disabled">
                <svg class="feature-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                <span>دعم العملاء عبر البريد فقط</span>
              </div>
              
              <div class="pricing-feature disabled">
                <svg class="feature-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                <span>قوالب حصرية للسير الذاتية</span>
              </div>
            </div>
            
            <button class="pricing-button basic">
              اختر الباقة
            </button>
          </div>
        </div>
        
        <!-- Pro Plan (Featured) -->
        <div class="pricing-card featured" data-aos="fade-up">
          <div class="featured-label">الأكثر شيوعاً</div>
          <div class="p-8">
            <h3 class="pricing-title">احترافي</h3>
            <div class="pricing-price">
              <span class="currency">ر.س</span>
              <span class="amount">129</span>
              <span class="period">/شهرياً</span>
            </div>
            
            <div class="pricing-features">
              <div class="pricing-feature">
                <svg class="feature-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>تصميم 15 سيرة ذاتية شهرياً</span>
              </div>
              
              <div class="pricing-feature">
                <svg class="feature-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>معالجة 30 ملف شهرياً</span>
              </div>
              
              <div class="pricing-feature">
                <svg class="feature-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>وصول كامل للمكتبة الرقمية</span>
              </div>
              
              <div class="pricing-feature">
                <svg class="feature-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>دعم العملاء عبر البريد والدردشة</span>
              </div>
              
              <div class="pricing-feature disabled">
                <svg class="feature-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                <span>قوالب حصرية للسير الذاتية</span>
              </div>
            </div>
            
            <button class="pricing-button featured">
              اختر الباقة
            </button>
          </div>
        </div>
        
        <!-- Premium Plan -->
        <div class="pricing-card" data-aos="fade-up" data-aos-delay="200">
          <div class="p-8">
            <h3 class="pricing-title">بريميوم</h3>
            <div class="pricing-price">
              <span class="currency">ر.س</span>
              <span class="amount">229</span>
              <span class="period">/شهرياً</span>
            </div>
            
            <div class="pricing-features">
              <div class="pricing-feature">
                <svg class="feature-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>تصميم سير ذاتية غير محدودة</span>
              </div>
              
              <div class="pricing-feature">
                <svg class="feature-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>معالجة ملفات غير محدودة</span>
              </div>
              
              <div class="pricing-feature">
                <svg class="feature-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>وصول كامل للمكتبة الرقمية</span>
              </div>
              
              <div class="pricing-feature">
                <svg class="feature-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>دعم عملاء متميز مع أولوية الرد</span>
              </div>
              
              <div class="pricing-feature">
                <svg class="feature-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>قوالب حصرية للسير الذاتية</span>
              </div>
            </div>
            
            <button class="pricing-button premium">
              اختر الباقة
            </button>
          </div>
        </div>
      </div>
      
      <div class="text-center mt-16 text-gray-500" data-aos="fade-up">
        <p>جميع الاشتراكات تشمل تجربة مجانية لمدة 7 أيام. يمكنك الإلغاء في أي وقت.</p>
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

.pricing-card {
  @apply bg-white rounded-xl shadow-lg overflow-hidden transform transition duration-300 relative;
  
  &:hover {
    @apply -translate-y-2 shadow-xl;
  }
  
  &.featured {
    @apply ring-2 ring-blue-500 shadow-xl z-10;
    transform: scale(1.05);
  }
}

.featured-label {
  @apply absolute top-0 right-0 left-0 bg-gradient-to-r from-blue-600 to-blue-400 text-white text-xs font-bold py-1 text-center;
}

.pricing-title {
  @apply text-2xl font-bold text-gray-900 mb-4 text-center;
}

.pricing-price {
  @apply flex justify-center items-baseline mb-6;
  
  .currency {
    @apply text-xl text-gray-500 mr-1;
  }
  
  .amount {
    @apply text-5xl font-bold text-gray-900;
  }
  
  .period {
    @apply text-sm text-gray-500 mr-1;
  }
}

.pricing-features {
  @apply space-y-4 mb-8;
}

.pricing-feature {
  @apply flex items-center;
  
  .feature-icon {
    @apply w-5 h-5 text-green-500 mr-3 flex-shrink-0;
  }
  
  &.disabled {
    @apply text-gray-400;
    
    .feature-icon {
      @apply text-gray-400;
    }
  }
}

.pricing-button {
  @apply w-full py-3 px-6 rounded-lg text-center font-semibold transition duration-300;
  
  &.basic {
    @apply bg-white text-blue-600 border-2 border-blue-600;
    
    &:hover {
      @apply bg-blue-50;
    }
  }
  
  &.featured {
    @apply bg-gradient-to-r from-blue-600 to-blue-400 text-white;
    
    &:hover {
      @apply shadow-lg;
    }
  }
  
  &.premium {
    @apply bg-gradient-to-r from-purple-600 to-indigo-600 text-white;
    
    &:hover {
      @apply shadow-lg;
    }
  }
}

/* RTL support */
:dir(rtl) .container {
  direction: rtl;
}

:dir(rtl) .pricing-feature .feature-icon {
  margin-right: 0;
  margin-left: 0.75rem;
}
</style>
