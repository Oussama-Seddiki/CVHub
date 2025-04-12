<template>
  <section id="services" class="services-section py-16 md:py-24">
    <div class="container mx-auto px-6">
      <h2 class="text-3xl md:text-4xl font-bold text-center text-gray-900 mb-16" data-aos="fade-up">
        خدماتنا <span class="text-gradient">المميزة</span>
      </h2>
      
      <!-- 3 Main Services Cards -->
      <div class="grid md:grid-cols-3 gap-10 mb-20">
        <!-- Service 1: CV Builder -->
        <div class="service-card" data-aos="fade-up" data-aos-delay="100">
          <div class="h-48 service-card-header bg-gradient-to-r from-blue-500 to-indigo-600">
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
              <span class="tech-badge">Resumaker AI</span>
            </div>
          </div>
        </div>
        
        <!-- Service 2: File Processing -->
        <div class="service-card" data-aos="fade-up" data-aos-delay="200">
          <div class="h-48 service-card-header bg-gradient-to-r from-green-500 to-teal-500">
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
              <span class="tech-badge">PDF Processing API</span>
            </div>
          </div>
        </div>
        
        <!-- Service 3: Document Library -->
        <div class="service-card" data-aos="fade-up" data-aos-delay="300">
          <div class="h-48 service-card-header bg-gradient-to-r from-purple-500 to-pink-500">
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
              <span class="tech-badge">Scribd API</span>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Detailed Features -->
      <div class="bg-gray-50 p-8 rounded-2xl shadow-inner" data-aos="fade-up">
        <h3 class="text-2xl font-bold text-center mb-12">مميزات كل خدمة</h3>
        
        <div class="grid gap-12">
          <!-- CV Features -->
          <div>
            <h4 class="text-xl font-bold text-blue-600 mb-6">إنشاء السير الذاتية بالذكاء الاصطناعي</h4>
            <div class="grid md:grid-cols-3 gap-6">
              <div class="feature-card">
                <div class="feature-icon blue-feature-icon">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                  </svg>
                </div>
                <h5 class="feature-title">قوالب احترافية</h5>
                <p class="feature-description">اختر من بين مجموعة متنوعة من القوالب المصممة من قبل خبراء التوظيف</p>
              </div>
              
              <div class="feature-card">
                <div class="feature-icon blue-feature-icon">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                  </svg>
                </div>
                <h5 class="feature-title">تحسين المحتوى بالذكاء الاصطناعي</h5>
                <p class="feature-description">يقوم الذكاء الاصطناعي بتحسين وصياغة المحتوى بشكل جذاب للمسؤولين عن التوظيف</p>
              </div>
              
              <div class="feature-card">
                <div class="feature-icon blue-feature-icon">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                  </svg>
                </div>
                <h5 class="feature-title">تصدير بصيغ متعددة</h5>
                <p class="feature-description">تصدير السيرة الذاتية بصيغتي PDF وWord للاستخدام في مختلف المنصات والتطبيقات</p>
              </div>
            </div>
          </div>
          
          <!-- File Processing Features -->
          <div>
            <h4 class="text-xl font-bold text-green-600 mb-6">معالجة الملفات</h4>
            <div class="grid md:grid-cols-3 gap-6">
              <div class="feature-card">
                <div class="feature-icon green-feature-icon">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                  </svg>
                </div>
                <h5 class="feature-title">تحويل الملفات</h5>
                <p class="feature-description">تحويل الملفات بين صيغ مختلفة مثل PDF و Word و PowerPoint وغيرها</p>
              </div>
              
              <div class="feature-card">
                <div class="feature-icon green-feature-icon">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                  </svg>
                </div>
                <h5 class="feature-title">دمج وتقسيم الملفات</h5>
                <p class="feature-description">دمج ملفات PDF متعددة في ملف واحد أو تقسيم ملف كبير إلى أجزاء أصغر</p>
              </div>
              
              <div class="feature-card">
                <div class="feature-icon green-feature-icon">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                  </svg>
                </div>
                <h5 class="feature-title">ضغط وتحسين</h5>
                <p class="feature-description">ضغط حجم ملفات PDF وتحسين جودتها للمشاركة عبر البريد الإلكتروني أو التخزين</p>
              </div>
            </div>
          </div>
          
          <!-- Library Features -->
          <div>
            <h4 class="text-xl font-bold text-purple-600 mb-6">مكتبة الوثائق والمذكرات</h4>
            <div class="grid md:grid-cols-3 gap-6">
              <div class="feature-card">
                <div class="feature-icon purple-feature-icon">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                </div>
                <h5 class="feature-title">وثائق رسمية</h5>
                <p class="feature-description">وصول لوثائق رسمية مهمة مثل نماذج حكومية واستمارات ومستندات قانونية</p>
              </div>
              
              <div class="feature-card">
                <div class="feature-icon purple-feature-icon">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                  </svg>
                </div>
                <h5 class="feature-title">مذكرات دراسية</h5>
                <p class="feature-description">مجموعة شاملة من المذكرات الدراسية والملخصات في مختلف التخصصات والمجالات</p>
              </div>
              
              <div class="feature-card">
                <div class="feature-icon purple-feature-icon">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                  </svg>
                </div>
                <h5 class="feature-title">بحث متقدم</h5>
                <p class="feature-description">ميزات بحث متقدمة للعثور على الوثائق والمذكرات بسرعة وسهولة</p>
              </div>
            </div>
          </div>
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

.service-card {
  @apply bg-white rounded-xl shadow-lg overflow-hidden transform transition duration-300 hover:-translate-y-2 hover:shadow-xl;
}

.service-card-header {
  @apply flex items-center justify-center;
}

.tech-badge {
  @apply inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full mr-2;
}

.feature-card {
  @apply bg-white p-5 rounded-lg shadow-md transform transition duration-300 hover:-translate-y-1 hover:shadow-lg;
}

.feature-icon {
  @apply w-12 h-12 rounded-full flex items-center justify-center text-white mb-4;
}

.blue-feature-icon {
  @apply bg-gradient-to-r from-blue-500 to-blue-600;
}

.green-feature-icon {
  @apply bg-gradient-to-r from-green-500 to-teal-500;
}

.purple-feature-icon {
  @apply bg-gradient-to-r from-purple-500 to-pink-500;
}

.feature-title {
  @apply text-lg font-semibold text-gray-800 mb-2;
}

.feature-description {
  @apply text-gray-600 text-sm;
}

/* RTL support */
:dir(rtl) .container {
  direction: rtl;
}
</style>
