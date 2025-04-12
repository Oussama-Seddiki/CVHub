<template>
  <div class="cursor-container">
    <div ref="cursor" class="cursor"></div>
    <div ref="cursorFollower" class="cursor-follower"></div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import gsap from 'gsap';

const cursor = ref(null);
const cursorFollower = ref(null);

const onMouseMove = (e) => {
  gsap.to(cursor.value, {
    x: e.clientX,
    y: e.clientY,
    duration: 0.1
  });
  
  gsap.to(cursorFollower.value, {
    x: e.clientX,
    y: e.clientY,
    duration: 0.5
  });
};

const onMouseOver = () => {
  gsap.to(cursor.value, {
    scale: 1.5,
    opacity: 0.5,
    duration: 0.3
  });
  
  gsap.to(cursorFollower.value, {
    scale: 3,
    opacity: 0.15,
    duration: 0.3
  });
};

const onMouseOut = () => {
  gsap.to(cursor.value, {
    scale: 1,
    opacity: 1,
    duration: 0.3
  });
  
  gsap.to(cursorFollower.value, {
    scale: 1,
    opacity: 0.3,
    duration: 0.3
  });
};

const setupInteractiveElements = () => {
  const interactiveElements = document.querySelectorAll('a, button, .interactive');
  
  interactiveElements.forEach(el => {
    el.addEventListener('mouseover', onMouseOver);
    el.addEventListener('mouseout', onMouseOut);
  });
};

onMounted(() => {
  document.addEventListener('mousemove', onMouseMove);
  
  // Initialize position
  gsap.set(cursor.value, { x: -100, y: -100 });
  gsap.set(cursorFollower.value, { x: -100, y: -100 });
  
  // Setup interactive elements with a small delay to ensure DOM is ready
  setTimeout(setupInteractiveElements, 500);
});

onBeforeUnmount(() => {
  document.removeEventListener('mousemove', onMouseMove);
  
  const interactiveElements = document.querySelectorAll('a, button, .interactive');
  interactiveElements.forEach(el => {
    el.removeEventListener('mouseover', onMouseOver);
    el.removeEventListener('mouseout', onMouseOut);
  });
});
</script>

<style scoped>
.cursor-container {
  position: fixed;
  pointer-events: none;
  z-index: 9999;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
}

.cursor {
  position: absolute;
  width: 12px;
  height: 12px;
  background-color: #0066ff;
  border-radius: 50%;
  transform: translate(-50%, -50%);
  transition: width 0.2s, height 0.2s;
  pointer-events: none;
  z-index: 10001;
}

.cursor-follower {
  position: absolute;
  width: 40px;
  height: 40px;
  background-color: rgba(0, 102, 255, 0.3);
  border-radius: 50%;
  transform: translate(-50%, -50%);
  transition: width 0.2s, height 0.2s;
  pointer-events: none;
  z-index: 10000;
}

@media (max-width: 768px) {
  .cursor, .cursor-follower {
    display: none;
  }
}
</style>
