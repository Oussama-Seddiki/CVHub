<template>
  <div class="logo-container">
    <div class="logo-icon">
      <div class="floating-docs">
        <div class="doc doc1" ref="doc1"></div>
        <div class="doc doc2" ref="doc2"></div>
        <div class="doc doc3" ref="doc3"></div>
      </div>
      <div class="logo-circle"></div>
    </div>
    <div v-if="showText" class="logo-text">CVHub</div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import gsap from 'gsap';

const props = defineProps({
  showText: {
    type: Boolean,
    default: true
  },
  size: {
    type: String,
    default: 'md' // sm, md, lg
  }
});

const doc1 = ref(null);
const doc2 = ref(null);
const doc3 = ref(null);

onMounted(() => {
  const tl = gsap.timeline({ repeat: -1 });
  
  // Animation for doc1
  tl.to(doc1.value, {
    y: '-10px',
    x: '3px',
    rotation: -5,
    duration: 2,
    ease: 'sine.inOut'
  });
  tl.to(doc1.value, {
    y: '0px',
    x: '0px',
    rotation: 0,
    duration: 2,
    ease: 'sine.inOut'
  });
  
  // Animation for doc2
  gsap.to(doc2.value, {
    y: '-8px',
    x: '-4px',
    rotation: 8,
    duration: 2.5,
    ease: 'sine.inOut',
    repeat: -1,
    yoyo: true
  });
  
  // Animation for doc3
  gsap.to(doc3.value, {
    y: '-12px',
    rotation: -3,
    duration: 3,
    ease: 'sine.inOut',
    repeat: -1,
    yoyo: true,
    delay: 0.5
  });
});
</script>

<style scoped>
.logo-container {
  display: inline-flex;
  align-items: center;
  gap: 10px;
}

.logo-icon {
  position: relative;
  width: v-bind('size === "sm" ? "32px" : size === "lg" ? "56px" : "40px"');
  height: v-bind('size === "sm" ? "32px" : size === "lg" ? "56px" : "40px"');
}

.logo-circle {
  position: absolute;
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, #0066ff, #3380ff);
  border-radius: 50%;
  z-index: 1;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.floating-docs {
  position: absolute;
  width: 100%;
  height: 100%;
  z-index: 2;
}

.doc {
  position: absolute;
  background-color: white;
  border-radius: 2px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.doc1 {
  width: 60%;
  height: 70%;
  top: 5%;
  left: 20%;
  z-index: 5;
}

.doc2 {
  width: 50%;
  height: 65%;
  top: 10%;
  left: 30%;
  z-index: 4;
}

.doc3 {
  width: 55%;
  height: 60%;
  top: 15%;
  left: 25%;
  z-index: 3;
}

.logo-text {
  font-weight: 700;
  font-family: 'Tajawal', sans-serif;
  background: linear-gradient(135deg, #0066ff, #33a0ff);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  font-size: v-bind('size === "sm" ? "1.25rem" : size === "lg" ? "2rem" : "1.5rem"');
}

/* RTL support */
:dir(rtl) .logo-container {
  flex-direction: row-reverse;
}
</style>
