<script setup>
import {useCheckoutStore} from './composables/useCheckoutStore';
import {storeToRefs} from "pinia";

const store = useCheckoutStore();

const processRunning = storeToRefs(store.loading);

function handleRequestForQuote(element) {
  Amplify.submitCartAsQuote(element);
}

function handleCreateOrderList() {
  Amplify.addToNewOrderList(store.cartId, 'cart', store.orderListTitle);
}

</script>

<template>
  <svg width="64px" height="64px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round" stroke="#CCCCCC" stroke-width="4.8"><path d="M12.39 20.87a.696.696 0 0 1-.78 0C9.764 19.637 2 14.15 2 8.973c0-6.68 7.85-7.75 10-3.25 2.15-4.5 10-3.43 10 3.25 0 5.178-7.764 10.664-9.61 11.895z" fill="#000000"></path></g><g id="SVGRepo_iconCarrier"><path d="M12.39 20.87a.696.696 0 0 1-.78 0C9.764 19.637 2 14.15 2 8.973c0-6.68 7.85-7.75 10-3.25 2.15-4.5 10-3.43 10 3.25 0 5.178-7.764 10.664-9.61 11.895z" fill="#000000"></path></g></svg>
  <div class="checkout-footer">
    <div class="column">
      <a class="btn btn-outline-secondary" href="#" @click.prevent="store.goBack()">
        <i class="icon-arrow-left"></i>
        <span class="hidden-xs-down">&nbsp;{{ store.isFirstStep ? 'Back To Cart' : 'Back' }}</span>
      </a>
    </div>
    <div class="column d-flex justify-content-center gap-3" v-if="store.activeStep === 'review'">
      <button v-if="store.allowRequestQuote"
              class="btn btn-primary"
              data-submitting="false"
              @click.prevent="handleRequestForQuote($event.target)">
        <i class="icon-file"></i>
        <span class="hidden-xs-down">&nbsp;{{ 'Request For Quote' }}</span>
      </button>
      <button class="btn btn-primary"
              v-if="store.allowCreateOrderList"
              @click.prevent="handleCreateOrderList">
        <i class="icon-file-add"></i>
        <span class="hidden-xs-down">&nbsp;{{ `Create ${store.orderListTitle}` }}</span>
      </button>
    </div>
    <div class="column">
      <button type="button"
              id="submit"
              :disabled="processRunning"
              :class="{'btn': true, 'btn-primary': !store.isLastStep, 'btn-success' : store.isLastStep, 'disabled': store.newShipping }"
              @click.prevent="store.goNext()">
        <span class="hidden-xs-down" v-if="!store.isLastStep">Continue&nbsp;</span>
        <span v-else>Complete Order</span>
        <i class="icon-arrow-right" v-if="!store.isLastStep"></i>
      </button>
    </div>
  </div>
</template>
