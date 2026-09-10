<script setup>
import { useCheckoutStore } from './composables/useCheckoutStore';

defineProps({
  active: {
    type: String,
    default: 'account',
  },
});

const store = useCheckoutStore();

function back() {
  if (store.isFirstStep) {
    if (typeof window !== 'undefined') {
      window.location.href = '/cart';
    }
    return;
  }
  store.goBack();
}
</script>

<template>
  <div class="checkout-footer margin-top-1x">
    <div class="column">
      <a class="btn btn-outline-secondary" href="#" @click.prevent="back">
        <i class="icon-arrow-left"></i>
        <span class="hidden-xs-down">&nbsp;{{ store.isFirstStep ? 'Back To Cart' : 'Back' }}</span>
      </a>
    </div>
    <div class="column">
      <button type="button" class="btn btn-primary" @click="store.goNext()">
        <span class="hidden-xs-down" v-if="!store.isLastStep">Continue&nbsp;</span>
        <span v-else>Complete Order</span>
        <i class="icon-arrow-right" v-if="!store.isLastStep"></i>
      </button>
    </div>
  </div>
</template>
