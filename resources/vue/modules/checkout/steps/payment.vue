<script setup>
import { ref } from 'vue';
import { useCheckoutStore } from '../composables/useCheckoutStore';

const store = useCheckoutStore();

const openPanel = ref('card');

// Opening a panel selects the payment method in the checkout state.
function toggle(panel, method) {
  openPanel.value = openPanel.value === panel ? '' : panel;
  if (openPanel.value === panel) {
    store.selectPaymentMethod(method);
  }
}
</script>

<template>
  <h4>Choose Payment Method</h4>
  <hr class="padding-bottom-1x">
  <div class="accordion" id="accordion" role="tablist">
    <div class="card">
      <div class="card-header" role="tab">
        <h6><a href="#card" :class="{ collapsed: openPanel !== 'card' }"
               @click.prevent="toggle('card', 'credit_card')"><i class="icon-columns"></i>Pay with Credit Card</a></h6>
      </div>
      <div class="collapse" :class="{ show: openPanel === 'card' }" id="card" role="tabpanel">
        <div class="card-body">
          <p>We accept following credit cards:&nbsp;<img class="d-inline-block align-middle"
              src="/vendor/widget/img/credit-cards.png" style="width: 120px;" alt="Credit Cards"></p>
          <form class="interactive-credit-card row" @submit.prevent>
            <div class="form-group col-sm-6">
              <input class="form-control" type="text" name="number" placeholder="Card Number" required>
            </div>
            <div class="form-group col-sm-6">
              <input class="form-control" type="text" name="name" placeholder="Full Name" required>
            </div>
            <div class="form-group col-sm-3">
              <input class="form-control" type="text" name="expiry" placeholder="MM/YY" required>
            </div>
            <div class="form-group col-sm-3">
              <input class="form-control" type="text" name="cvc" placeholder="CVC" required>
            </div>
            <div class="col-sm-6">
              <button class="btn btn-outline-primary btn-block margin-top-none" type="submit">Submit</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <div class="card">
      <div class="card-header" role="tab">
        <h6><a href="#paypal" :class="{ collapsed: openPanel !== 'paypal' }"
               @click.prevent="toggle('paypal', 'paypal')"><i class="socicon-paypal"></i>Pay with PayPal</a></h6>
      </div>
      <div class="collapse" :class="{ show: openPanel === 'paypal' }" id="paypal" role="tabpanel">
        <div class="card-body">
          <p>PayPal - the safer, easier way to pay</p>
          <form class="row" method="post" @submit.prevent>
            <div class="col-sm-6">
              <div class="form-group">
                <input class="form-control" type="email" placeholder="E-mail" required>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <input class="form-control" type="password" placeholder="Password" required>
              </div>
            </div>
            <div class="col-12">
              <div class="d-flex flex-wrap justify-content-between align-items-center">
                <a class="navi-link" href="#" @click.prevent>Forgot password?</a>
                <button class="btn btn-outline-primary margin-top-none" type="submit">Log In</button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
    <div class="card">
      <div class="card-header" role="tab">
        <h6><a href="#points" :class="{ collapsed: openPanel !== 'points' }"
               @click.prevent="toggle('points', 'reward_points')"><i class="icon-medal"></i>Redeem Reward Points</a></h6>
      </div>
      <div class="collapse" :class="{ show: openPanel === 'points' }" id="points" role="tabpanel">
        <div class="card-body">
          <p>You currently have<span class="text-medium"> 290</span> Reward Points to spend.</p>
          <div class="custom-control custom-checkbox d-block">
            <input class="custom-control-input" type="checkbox" id="use_points">
            <label class="custom-control-label" for="use_points">Use my Reward Points to pay for this order.</label>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="alert alert-danger mt-2" role="alert" v-if="store.validationError">
    {{ store.validationError }}
  </div>
</template>

<style scoped>

</style>
