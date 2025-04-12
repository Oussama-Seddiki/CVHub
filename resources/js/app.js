import '../css/app.css';
import './bootstrap';
import { createApp, h } from 'vue';
import { createInertiaApp, Link, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import '@fortawesome/fontawesome-free/css/all.css';
import Vue3Lottie from 'vue3-lottie';
import 'vue3-lottie/dist/style.css';
import { createPinia } from 'pinia';
import AOS from 'aos';
import 'aos/dist/aos.css';

// Initialize AOS globally
AOS.init({
    duration: 800,
    easing: 'ease-in-out',
    once: true,
    mirror: false
});

// The entire app uses Cairo font with variable font capabilities
// Font weights from 200-1000 and slant from -11 to 11
// Usage examples:
// - Basic: class="cairo"
// - With weight: class="cairo cairo-700"
// - With slant: class="cairo cairo-slant-left"

createInertiaApp({
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });
        
        // Initialize and register plugins
        const pinia = createPinia();
        app.use(pinia);
        app.use(plugin);
        app.use(Vue3Lottie);
        
        // Register Inertia Link component globally
        app.component('Link', Link);
        
        // Make route function available globally
        app.config.globalProperties.route = window.route;
        
        return app.mount(el);
    },
    // تعطيل التخزين المؤقت للصفحات للتأكد من إعادة تحميل الصفحة عند كل زيارة
    progress: {
        color: '#4B5563',
    },
    visitOptions: {
        preserveState: false  // عدم الاحتفاظ بحالة الصفحة السابقة
    }
});

// إضافة معالج لأحداث التنقل يضمن تحديث الصفحة
router.on('navigate', (event) => {
    // تنفيذ عملية بعد الانتقال مباشرة
    window.scrollTo(0, 0);  // التمرير إلى أعلى الصفحة
    
    // يمكن إضافة منطق إضافي هنا إذا لزم الأمر
    console.log('Navigation occurred to:', event.detail.page.url);
});
