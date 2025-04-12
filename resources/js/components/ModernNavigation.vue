<template>
  <nav class="modern-nav" :class="{ 'nav-scrolled': scrolled }">
    <div class="nav-container">
      <div class="nav-logo">
        <Link :href="authenticated ? '/dashboard' : '/'">
          <ModernLogo :size="scrolled ? 'sm' : 'md'" />
        </Link>
      </div>
      
      <!-- Desktop Navigation -->
      <div class="nav-links" :class="{ 'nav-hidden': mobileMenuOpen }">
        <template v-if="authenticated">
          <NavLink 
            v-for="(item, index) in navItems" 
            :key="index"
            :href="item.href"
            :active="currentPath.startsWith(item.href)"
            class="nav-link"
          >
            <span class="nav-link-text">{{ item.label }}</span>
          </NavLink>
        </template>
        <template v-else>
          <NavLink 
            v-for="(item, index) in guestNavItems" 
            :key="index"
            :href="item.href"
            :active="currentPath.startsWith(item.href)"
            class="nav-link"
          >
            <span class="nav-link-text">{{ item.label }}</span>
          </NavLink>
        </template>
      </div>
      
      <!-- Auth Section -->
      <div class="nav-auth" v-if="authenticated">
        <Dropdown align="left" width="48">
          <template #trigger>
            <button class="nav-user-button">
              <div class="user-avatar">{{ userInitials }}</div>
              <span class="user-name">{{ userName }}</span>
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
          </template>

          <template #content>
            <DropdownLink href="/profile" class="dropdown-item">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
              <span>الملف الشخصي</span>
            </DropdownLink>
            
            <DropdownLink href="/logout" method="post" as="button" class="dropdown-item">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
              </svg>
              <span>تسجيل الخروج</span>
            </DropdownLink>
          </template>
        </Dropdown>
      </div>
      
      <div class="nav-auth" v-else>
        <Link href="/login" class="login-button">تسجيل الدخول</Link>
        <Link href="/register" class="register-button">إنشاء حساب</Link>
      </div>
      
      <!-- Mobile Menu Button -->
      <button @click="toggleMobileMenu" class="mobile-menu-button">
        <div class="hamburger" :class="{ 'is-active': mobileMenuOpen }">
          <span></span>
          <span></span>
          <span></span>
        </div>
      </button>
    </div>
    
    <!-- Mobile Menu -->
    <div class="mobile-menu" :class="{ 'is-open': mobileMenuOpen }">
      <div class="mobile-menu-container">
        <template v-if="authenticated">
          <Link 
            v-for="(item, index) in navItems" 
            :key="index"
            :href="item.href"
            class="mobile-menu-item"
            @click="closeMobileMenu"
          >
            {{ item.label }}
          </Link>
          
          <div class="mobile-menu-divider"></div>
          
          <Link href="/profile" class="mobile-menu-item" @click="closeMobileMenu">
            الملف الشخصي
          </Link>
          <Link href="/logout" method="post" as="button" class="mobile-menu-item" @click="closeMobileMenu">
            تسجيل الخروج
          </Link>
        </template>
        <template v-else>
          <Link 
            v-for="(item, index) in guestNavItems" 
            :key="index"
            :href="item.href"
            class="mobile-menu-item"
            @click="closeMobileMenu"
          >
            {{ item.label }}
          </Link>
          
          <div class="mobile-menu-divider"></div>
          
          <Link href="/login" class="mobile-menu-item" @click="closeMobileMenu">
            تسجيل الدخول
          </Link>
          <Link href="/register" class="mobile-menu-item mobile-register" @click="closeMobileMenu">
            إنشاء حساب
          </Link>
        </template>
      </div>
    </div>
  </nav>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import NavLink from '@/components/NavLink.vue';
import Dropdown from '@/components/Dropdown.vue';
import DropdownLink from '@/components/DropdownLink.vue';
import ModernLogo from '@/components/ModernLogo.vue';

const page = usePage();
const authenticated = computed(() => page.props.auth?.user);
const userName = computed(() => authenticated.value ? page.props.auth.user.name : '');
const userInitials = computed(() => {
  if (!authenticated.value) return '';
  return userName.value
    .split(' ')
    .map(n => n[0])
    .join('')
    .substring(0, 2)
    .toUpperCase();
});

const currentPath = computed(() => page.url);

const scrolled = ref(false);
const mobileMenuOpen = ref(false);

const navItems = [
  { label: 'لوحة التحكم', href: '/dashboard' },
  { label: 'إنشاء سيرة ذاتية', href: '/cv' },
  { label: 'معالجة الملفات', href: '/file-processing' },
  { label: 'مكتبة الوثائق', href: '/library' },
  { label: 'الاشتراك', href: '/subscription' },
];

const guestNavItems = [
  { label: 'الرئيسية', href: '/' },
  { label: 'خدماتنا', href: '/#services' },
  { label: 'الأسعار', href: '/#pricing' },
  { label: 'عن المنصة', href: '/#about' },
];

const handleScroll = () => {
  scrolled.value = window.scrollY > 20;
};

const toggleMobileMenu = () => {
  mobileMenuOpen.value = !mobileMenuOpen.value;
  document.body.style.overflow = mobileMenuOpen.value ? 'hidden' : '';
};

const closeMobileMenu = () => {
  mobileMenuOpen.value = false;
  document.body.style.overflow = '';
};

onMounted(() => {
  window.addEventListener('scroll', handleScroll);
  handleScroll(); // Check initial scroll position
});

onBeforeUnmount(() => {
  window.removeEventListener('scroll', handleScroll);
  document.body.style.overflow = '';
});
</script>

<style scoped>
.modern-nav {
  position: fixed;
  top: 0;
  right: 0;
  left: 0;
  height: 80px;
  background: rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(10px);
  transition: all 0.3s ease;
  z-index: 1000;
  direction: rtl;
}

.nav-scrolled {
  height: 60px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.nav-container {
  max-width: 1280px;
  margin: 0 auto;
  padding: 0 1.5rem;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.nav-logo {
  flex-shrink: 0;
  transition: all 0.3s ease;
}

.nav-links {
  display: flex;
  gap: 2rem;
}

.nav-link {
  position: relative;
  font-weight: 500;
  font-size: 1rem;
  color: #374151;
  transition: all 0.3s ease;
  padding: 0.5rem 0;
}

.nav-link:hover {
  color: #0066ff;
}

.nav-link-text {
  position: relative;
}

.nav-link-text::after {
  content: '';
  position: absolute;
  bottom: -4px;
  right: 0;
  width: 0;
  height: 2px;
  background-color: #0066ff;
  transition: width 0.3s ease;
}

.nav-link:hover .nav-link-text::after,
.nav-link.active .nav-link-text::after {
  width: 100%;
}

.nav-auth {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.login-button {
  font-weight: 500;
  color: #0066ff;
  transition: all 0.3s ease;
  padding: 0.5rem 1rem;
}

.login-button:hover {
  color: #0052cc;
}

.register-button {
  font-weight: 500;
  color: white;
  background: linear-gradient(to left, #0066ff, #3380ff);
  border-radius: 0.5rem;
  padding: 0.5rem 1.25rem;
  transition: all 0.3s ease;
  box-shadow: 0 2px 10px rgba(0, 102, 255, 0.3);
}

.register-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 15px rgba(0, 102, 255, 0.4);
}

.nav-user-button {
  display: flex;
  align-items: center;
  padding: 0.5rem;
  border-radius: 9999px;
  background-color: #f3f4f6;
  transition: all 0.3s ease;
}

.nav-user-button:hover {
  background-color: #e5e7eb;
}

.user-avatar {
  width: 32px;
  height: 32px;
  border-radius: 9999px;
  background: linear-gradient(to left, #0066ff, #33a0ff);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 500;
  font-size: 0.75rem;
}

.user-name {
  margin: 0 0.75rem;
  font-weight: 500;
  color: #1f2937;
  max-width: 100px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.dropdown-item {
  display: flex;
  align-items: center;
  padding: 0.75rem 1rem;
  transition: all 0.2s ease;
}

.dropdown-item:hover {
  background-color: #f3f4f6;
}

.mobile-menu-button {
  display: none;
}

.hamburger {
  width: 24px;
  height: 24px;
  position: relative;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  justify-content: space-around;
  padding: 2px 0;
}

.hamburger span {
  width: 100%;
  height: 2px;
  background-color: #374151;
  border-radius: 2px;
  transition: all 0.3s ease;
}

.hamburger.is-active span:nth-child(1) {
  transform: translateY(8px) rotate(45deg);
}

.hamburger.is-active span:nth-child(2) {
  opacity: 0;
}

.hamburger.is-active span:nth-child(3) {
  transform: translateY(-8px) rotate(-45deg);
}

.mobile-menu {
  position: fixed;
  top: 60px;
  right: 0;
  left: 0;
  bottom: 0;
  background-color: white;
  z-index: 999;
  transform: translateY(-100%);
  opacity: 0;
  visibility: hidden;
  transition: all 0.3s ease;
  overflow-y: auto;
  direction: rtl;
}

.mobile-menu.is-open {
  transform: translateY(0);
  opacity: 1;
  visibility: visible;
}

.mobile-menu-container {
  padding: 2rem 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.mobile-menu-item {
  padding: 0.75rem 0;
  font-size: 1.125rem;
  font-weight: 500;
  color: #1f2937;
  border-bottom: 1px solid #e5e7eb;
}

.mobile-menu-divider {
  height: 1px;
  background-color: #e5e7eb;
  margin: 0.5rem 0;
}

.mobile-register {
  color: #0066ff;
  font-weight: 600;
}

@media (max-width: 1024px) {
  .nav-links {
    gap: 1.5rem;
  }
}

@media (max-width: 768px) {
  .nav-links, .nav-auth {
    display: none;
  }
  
  .mobile-menu-button {
    display: block;
  }
  
  .modern-nav {
    height: 60px;
  }
}
</style>
