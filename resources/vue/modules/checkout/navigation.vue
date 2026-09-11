<script setup>
import {useCheckoutStore} from './composables/useCheckoutStore';

const store = useCheckoutStore();

function handleRequestForQuote(element) {
  window.Amplify.submitCartAsQuote(element);
}

function handleCreateOrderListFromCart(element) {
  window.Amplify.addToNewOrderList(store.cartId, 'cart');
}

function handleSaveOrderAsDraft() {}

</script>

<template>
  <div class="checkout-footer">
    <div class="column">
      <a class="btn btn-outline-secondary" href="#" @click.prevent="store.goBack()">
        <i class="icon-arrow-left"></i>
        <span class="hidden-xs-down">&nbsp;{{ store.isFirstStep ? 'Back To Cart' : 'Back' }}</span>
      </a>
    </div>
    <div class="column d-flex justify-content-center" v-if="store.activeStep === 'review'">
      <button v-if="store.allowRequestQuote"
              class="btn btn-primary"
              data-submitting="false"
              @click.prevent="handleRequestForQuote($event.target)">
        <i class="icon-file"></i>
        <span class="hidden-xs-down">&nbsp;{{ 'Request For Quote' }}</span>
      </button>
      <button class="btn btn-primary"
              v-if="!store.allowCreateOrderList"
              @click.prevent="handleCreateOrderListFromCart($event.target)">
        <i class="icon-file-add"></i>
        <span class="hidden-xs-down">&nbsp;{{ 'Create' }}</span>
      </button>
    </div>
    <div class="column">
      <button type="button"
              :class="{'btn': true, 'btn-primary': !store.isLastStep, 'btn-success' : store.isLastStep }"
              @click.prevent="store.goNext()">
        <span class="hidden-xs-down" v-if="!store.isLastStep">Continue&nbsp;</span>
        <span v-else>Complete Order</span>
        <i class="icon-arrow-right" v-if="!store.isLastStep"></i>
      </button>
    </div>
  </div>
</template>
