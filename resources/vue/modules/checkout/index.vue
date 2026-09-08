<script>
import steps from "./steps.vue";
import navigation from "./navigation.vue";
import account from "./steps/account.vue";
import address from "./steps/address.vue";
import shipping from "./steps/shipping.vue";
import payment from "./steps/payment.vue";
import review from "./steps/review.vue";
import summary from "./summary.vue";

export default {
  name: 'Checkout',
  components: {steps, navigation, account, address, shipping, payment, review, summary},
  props: {
    cart: {
      type: Object,
      required: true
    },
    cartItemCount: {
      type: Number,
      required: true
    },
    templateBrandColor: {
      type: String,
      default: '#0da9ef'
    },
    steps: {
      type: Object,
      required: true
    },
    customer: {
      type: Object,
      required: true
    },
    addresses: {
      type: Array,
      default: []
    },
    countries: {
      type: Object,
      default: {}
    },
    states: {
      type: Object,
      default: {}
    },
    shipOptions: {
      type: Object,
      default: {}
    },
    createFavouriteFromCart: {
      type: Boolean,
      default: true
    },
    allowRequestQuote: {
      type: Boolean,
      default: true
    },
    allowDraftOrder: {
      type: Boolean,
      default: false
    },
    backToShoppingUrl: {
      type: String,
      default: () => {
        return window.location.origin;
      }
    }
  },
  data() {
    return {
      active: 'account',
    };
  },
  computed: {
    currentStep() {
      return address
    }
  }
}
</script>

<template>
  <div class="row">
    <!-- Checkout Address-->
    <div class="col-xl-9 col-lg-8">
      <steps :items="steps" :active/>
      <component :is="currentStep"/>
      <navigation/>
    </div>
    <!-- Sidebar          -->
    <div class="col-xl-3 col-lg-4">
      <summary/>
    </div>
  </div>
</template>