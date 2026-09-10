<script setup>
import { computed } from 'vue';
import { useCheckoutStore } from './composables/useCheckoutStore';
import steps from './steps.vue';
import navigation from './navigation.vue';
import account from './steps/account.vue';
import shipping from './steps/shipping.vue';
import payment from './steps/payment.vue';
import review from './steps/review.vue';
import SummarySidebar from './summary.vue';

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
});

const store = useCheckoutStore();
store.initFromProps(props);

const stepComponents = { account, shipping, payment, review };

const currentStepComponent = computed(
  () => stepComponents[store.currentStep?.component] ?? account
);

// Props forwarded to step components; the Customer/Account step keeps its
// existing behavior untouched.
const stepProps = computed(() => ({
  cart: store.cart,
  cartItemCount: props.cartItemCount,
  customer: store.customer,
  addresses: store.addresses,
  countries: store.countries,
  states: store.states,
  contact: store.contact,
}));
</script>

<template>
  <div class="row">
    <!-- Checkout Address-->
    <div class="col-xl-9 col-lg-8">
      <steps :active="store.activeStep"/>
      <component :is="currentStepComponent" v-bind="stepProps"/>
      <navigation :active="store.activeStep"/>
    </div>
    <!-- Sidebar          -->
    <div class="col-xl-3 col-lg-4">
      <SummarySidebar/>
    </div>
  </div>
</template>
