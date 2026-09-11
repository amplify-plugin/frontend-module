<script setup>
import {computed, onMounted} from 'vue';
import {useCheckoutStore} from './composables/useCheckoutStore';
import steps from './steps.vue';
import navigation from './navigation.vue';
import account from './steps/account.vue';
import shipping from './steps/shipping.vue';
import payment from './steps/payment.vue';
import review from './steps/review.vue';

const props = defineProps({
  cart: {
    type: Object,
    default: null,
  },
  cartItemCount: {
    type: Number,
    default: 0,
  },
  templateBrandColor: {
    type: String,
    default: '#0da9ef',
  },
  steps: {
    type: Array,
    default: null,
  },
  customer: {
    type: Object,
    default: null,
  },
  addresses: {
    type: Array,
    default: () => [],
  },
  countries: {
    type: Array,
    default: () => [],
  },
  states: {
    type: Array,
    default: () => [],
  },
  shipOptions: {
    type: [Object, Array],
    default: null,
  },
  createFavouriteFromCart: {
    type: Boolean,
    default: true,
  },
  allowRequestQuote: {
    type: Boolean,
    default: true,
  },
  allowDraftOrder: {
    type: Boolean,
    default: false,
  },
  backToShoppingUrl: {
    type: String,
    default: () => {
      return typeof window !== 'undefined' ? window.location.origin : '/';
    },
  },
  contact: {
    type: Object,
    default: null,
  },
  guestCheckout: {
    type: Boolean,
    default: false,
  },
  editable: {
    type: Boolean,
    default: false,
  }
});

const store = useCheckoutStore();

store.init(props);

const stepComponents = {account, shipping, payment, review};

const currentStep = computed(
    () => stepComponents[store.currentStep?.component] ?? account
);

</script>

<template>
  <div class="w-100">
    <steps :active="store.activeStep"/>
    <component :is="currentStep"/>
    <navigation :active="store.activeStep"/>
  </div>
</template>
