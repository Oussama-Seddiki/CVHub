// Import test file to check casing issues

// Current imports in FileProcessing/Index.vue
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, computed, onMounted } from 'vue';
import PdfService from '@/services/PdfService';
import PdfProcessor from '@/components/PdfProcessor.vue';
import SubscriptionWarning from '@/components/SubscriptionWarning.vue';
import ILoveApiProcessor from '@/components/ILoveApiProcessor.vue';

// Correct imports with proper casing for this project
// import { Head, Link } from '@inertiajs/vue3';
// import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
// import { ref, computed, onMounted } from 'vue';
// import PdfService from '@/services/PdfService.js';
// import PdfProcessor from '@/components/PdfProcessor.vue';
// import SubscriptionWarning from '@/components/SubscriptionWarning.vue';
// import ILoveApiProcessor from '@/components/ILoveApiProcessor.vue'; 