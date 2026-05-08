<template>
  <div class="typewriter">
    <span class="text">{{ displayedText }}</span>
    <span class="cursor" v-if="showCursor">|</span>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue'

const props = defineProps({
  texts: {
    type: Array,
    default: () => []
  },
  typeSpeed: {
    type: Number,
    default: 150
  },
  deleteSpeed: {
    type: Number,
    default: 50
  },
  pauseTime: {
    type: Number,
    default: 2000
  },
  loop: {
    type: Boolean,
    default: true
  }
})

const displayedText = ref('')
const showCursor = ref(true)
const currentLineIndex = ref(0)
const currentCharIndex = ref(0)
const isDeleting = ref(false)
let timerId = null

function type() {
  const currentText = props.texts[currentLineIndex.value]

  if (isDeleting.value) {
    displayedText.value = currentText.substring(0, currentCharIndex.value - 1)
    currentCharIndex.value--
  } else {
    displayedText.value = currentText.substring(0, currentCharIndex.value + 1)
    currentCharIndex.value++
  }

  let delay = isDeleting.value ? props.deleteSpeed : props.typeSpeed

  if (!isDeleting.value && currentCharIndex.value === currentText.length) {
    delay = props.pauseTime
    isDeleting.value = true
  } else if (isDeleting.value && currentCharIndex.value === 0) {
    isDeleting.value = false
    currentLineIndex.value = (currentLineIndex.value + 1) % props.texts.length
    
    if (!props.loop && currentLineIndex.value === 0) {
      return
    }
  }

  timerId = setTimeout(type, delay)
}

function restart() {
  clearTimeout(timerId)
  displayedText.value = ''
  currentLineIndex.value = 0
  currentCharIndex.value = 0
  isDeleting.value = false
  type()
}

watch(() => props.texts, () => {
  restart()
}, { deep: true })

onMounted(() => {
  type()
})

onUnmounted(() => {
  clearTimeout(timerId)
})

defineExpose({
  restart
})
</script>

<style scoped>
.typewriter {
  color: white;
  font-size: clamp(1.5rem, 4vw, 2.5rem);
  text-align: center;
}

.text {
  text-shadow: 0 0 10px rgba(255, 255, 255, 0.6);
}

.cursor {
  margin-left: 2px;
  animation: blink 0.7s infinite;
}

@keyframes blink {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0;
  }
}
</style>
