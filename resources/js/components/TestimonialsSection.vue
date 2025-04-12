<template>
  <section class="testimonials-section py-16 md:py-24 bg-white">
    <div class="container mx-auto px-6">
      <div class="text-center mb-16" data-aos="fade-up">
        <h2 class="text-3xl md:text-4xl font-bold text-gray-900">
          ماذا يقول <span class="text-gradient">عملاؤنا</span> عنا
        </h2>
        <p class="mt-4 text-lg text-gray-600 max-w-2xl mx-auto">
          اطلع على تجارب طلابنا ومستخدمي المنصة وكيف استفادوا من خدماتنا
        </p>
      </div>
      
      <div class="testimonials-slider relative">
        <div class="swiper-container" ref="swiperRef">
          <div class="swiper-wrapper">
            <!-- Testimonial 1 -->
            <div class="swiper-slide">
              <div class="testimonial-card">
                <div class="testimonial-header">
                  <div class="testimonial-avatar">
                    <div class="avatar-placeholder">م</div>
                  </div>
                  <div class="testimonial-author">
                    <h4 class="author-name">محمد الأحمد</h4>
                    <p class="author-title">طالب هندسة</p>
                  </div>
                </div>
                <div class="testimonial-rating">
                  <div class="stars">
                    <span class="star" v-for="i in 5" :key="i">★</span>
                  </div>
                </div>
                <p class="testimonial-text">
                  "استخدمت خدمة إنشاء السيرة الذاتية بالذكاء الاصطناعي وكانت النتيجة مذهلة! حصلت على أول وظيفة لي خلال أسبوعين من إرسال سيرتي الذاتية."
                </p>
              </div>
            </div>
            
            <!-- Testimonial 2 -->
            <div class="swiper-slide">
              <div class="testimonial-card">
                <div class="testimonial-header">
                  <div class="testimonial-avatar">
                    <div class="avatar-placeholder">س</div>
                  </div>
                  <div class="testimonial-author">
                    <h4 class="author-name">سارة المالكي</h4>
                    <p class="author-title">طالبة دراسات عليا</p>
                  </div>
                </div>
                <div class="testimonial-rating">
                  <div class="stars">
                    <span class="star" v-for="i in 5" :key="i">★</span>
                  </div>
                </div>
                <p class="testimonial-text">
                  "المكتبة الرقمية كانت مصدرًا أساسيًا في إنجاز أبحاثي العلمية. توفر وثائق ومراجع عالية الجودة وصعبة الوصول في أي مكان آخر."
                </p>
              </div>
            </div>
            
            <!-- Testimonial 3 -->
            <div class="swiper-slide">
              <div class="testimonial-card">
                <div class="testimonial-header">
                  <div class="testimonial-avatar">
                    <div class="avatar-placeholder">ع</div>
                  </div>
                  <div class="testimonial-author">
                    <h4 class="author-name">عبدالله السعيد</h4>
                    <p class="author-title">أستاذ جامعي</p>
                  </div>
                </div>
                <div class="testimonial-rating">
                  <div class="stars">
                    <span class="star" v-for="i in 4" :key="i">★</span>
                    <span class="star-empty">★</span>
                  </div>
                </div>
                <p class="testimonial-text">
                  "خدمة معالجة الملفات سهلة الاستخدام ومفيدة جدًا. أستخدمها بشكل متكرر لتحويل ودمج الملفات التعليمية وأوراق البحث."
                </p>
              </div>
            </div>
            
            <!-- Testimonial 4 -->
            <div class="swiper-slide">
              <div class="testimonial-card">
                <div class="testimonial-header">
                  <div class="testimonial-avatar">
                    <div class="avatar-placeholder">ن</div>
                  </div>
                  <div class="testimonial-author">
                    <h4 class="author-name">نورة القحطاني</h4>
                    <p class="author-title">خريجة إدارة أعمال</p>
                  </div>
                </div>
                <div class="testimonial-rating">
                  <div class="stars">
                    <span class="star" v-for="i in 5" :key="i">★</span>
                  </div>
                </div>
                <p class="testimonial-text">
                  "استخدمت الموقع للتقديم لوظائف متعددة، وتلقيت الكثير من ردود الإعجاب بالسيرة الذاتية التي صممتها من خلال المنصة. أنصح بها بشدة!"
                </p>
              </div>
            </div>
            
            <!-- Testimonial 5 -->
            <div class="swiper-slide">
              <div class="testimonial-card">
                <div class="testimonial-header">
                  <div class="testimonial-avatar">
                    <div class="avatar-placeholder">ف</div>
                  </div>
                  <div class="testimonial-author">
                    <h4 class="author-name">فهد العتيبي</h4>
                    <p class="author-title">مصمم جرافيك</p>
                  </div>
                </div>
                <div class="testimonial-rating">
                  <div class="stars">
                    <span class="star" v-for="i in 5" :key="i">★</span>
                  </div>
                </div>
                <p class="testimonial-text">
                  "كمصمم، أستفيد كثيرًا من معالجة ملفات PDF وضغطها للعملاء. خدمة سريعة وموثوقة تساعدني في إنجاز عملي بكفاءة."
                </p>
              </div>
            </div>
          </div>
          
          <!-- Navigation buttons -->
          <div class="swiper-button-next custom-nav-button"></div>
          <div class="swiper-button-prev custom-nav-button"></div>
          
          <!-- Pagination -->
          <div class="swiper-pagination"></div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import Swiper from 'swiper';
import { Navigation, Pagination, Autoplay } from 'swiper/modules';

import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';

const swiperRef = ref(null);
let swiper = null;

onMounted(() => {
  // Initialize Swiper
  swiper = new Swiper(swiperRef.value, {
    modules: [Navigation, Pagination, Autoplay],
    slidesPerView: 1,
    spaceBetween: 30,
    loop: true,
    autoplay: {
      delay: 5000,
      disableOnInteraction: false,
    },
    pagination: {
      el: '.swiper-pagination',
      clickable: true,
    },
    navigation: {
      nextEl: '.swiper-button-next',
      prevEl: '.swiper-button-prev',
    },
    breakpoints: {
      640: {
        slidesPerView: 1,
      },
      768: {
        slidesPerView: 2,
      },
      1024: {
        slidesPerView: 3,
      },
    },
  });
});

onBeforeUnmount(() => {
  // Destroy Swiper instance
  if (swiper) {
    swiper.destroy();
    swiper = null;
  }
});
</script>

<style scoped>
.text-gradient {
  background: linear-gradient(90deg, #0066ff 0%, #3380ff 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  display: inline;
}

.testimonials-slider {
  @apply pb-16;
}

.testimonial-card {
  @apply bg-white rounded-xl shadow-lg p-6 h-full transform transition duration-300 hover:-translate-y-2 hover:shadow-xl;
}

.testimonial-header {
  @apply flex items-center mb-4;
}

.testimonial-avatar {
  @apply mr-4 flex-shrink-0;
}

.avatar-placeholder {
  @apply w-12 h-12 rounded-full bg-gradient-to-r from-blue-500 to-blue-600 text-white text-lg flex items-center justify-center font-bold;
}

.testimonial-author {
  @apply flex-1;
}

.author-name {
  @apply font-bold text-gray-800 text-lg;
}

.author-title {
  @apply text-gray-500 text-sm;
}

.testimonial-rating {
  @apply mb-4;
}

.stars {
  @apply flex text-yellow-400 text-xl;
}

.star-empty {
  @apply text-gray-300;
}

.testimonial-text {
  @apply text-gray-600 italic text-sm md:text-base leading-relaxed;
}

/* Custom Swiper Navigation & Pagination */
.custom-nav-button {
  @apply text-blue-600;
}

:deep(.swiper-pagination-bullet) {
  @apply bg-blue-600;
}

:deep(.swiper-pagination-bullet-active) {
  @apply bg-blue-600;
}

/* RTL Support */
:dir(rtl) .testimonial-avatar {
  margin-right: 0;
  margin-left: 1rem;
}

:dir(rtl) .container {
  direction: rtl;
}
</style>
