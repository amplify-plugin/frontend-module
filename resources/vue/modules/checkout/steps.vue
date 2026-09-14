<script setup>
import {computed} from 'vue';
import {useCheckoutStore} from './composables/useCheckoutStore';

defineProps({
  active: {
    type: String,
    default: 'account',
  },
});

const store = useCheckoutStore();

function onStepClick(component) {
  store.goToStep(component);
}

/*
 * The reference (amp-theme) implementation writes the steps in reverse
 * document order (4 -> 1) and lays them out with `float: right`. The store
 * keeps the canonical logical order (Account -> Shipping -> Review ->
 * Payment); only the rendering list follows the reference convention.
 */
const displaySteps = computed(() => [...store.orderedSteps].reverse());

function canonicalIndex(item) {
  return store.orderedSteps.indexOf(item);
}

function isActive(item) {
  return store.activeStep === item.component;
}

function isCompleted(item) {
  return store.currentIndex > canonicalIndex(item);
}

function stepNumber(item) {
  return canonicalIndex(item) + 1;
}
</script>

<template>
  <div class="checkout-steps">
    <a v-for="(item, i) in displaySteps"
       :key="item.id + i"
       :href="`#step-${item.component}`"
       :class="{ 'active': isActive(item), 'completed': isCompleted(item) }"
       @click.prevent="onStepClick(item.component)">
      <span class="step-indicator icon-circle-check" v-if="isCompleted(item)"></span>
      <span class="angle" v-if="i > 0"></span>
      {{ stepNumber(item) }}. {{ item.label }}
    </a>
  </div>
</template>

<style scoped>
.checkout-steps > a {
  width: var(--checkout-step-width, 25%);
}

@media (max-width: 576px) {
  .checkout-steps > a {
    width: 100%;
  }
}

</style>
