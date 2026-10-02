<script setup>
import {onBeforeUnmount, onMounted} from "vue";
import {useCheckoutStore} from '../composables/useCheckoutStore';
const store = useCheckoutStore();

const config = store.payment.config;

let apTeanPay = null;
let cardComponent = null;

function createClaim() {

  const expirationDate = new Date();

  expirationDate.setMonth(
      expirationDate.getMonth() + 1
  );

  apTeanPay.createClaim(
      cardComponent,
      {
        name: store.account.company,
        addressLine1: store.account.addressLine1,
        addressLine2: store.account.addressLine2,
        addressCity: store.account.city,
        addressState: store.account.state,
        addressZip: store.account.zipCode,
        addressCountry: store.account.country,
        emailAddress: store.account.email,
        recurring: false,
        phoneCode: store.account.country === 'US' ? '+1' : '',
        phoneNumber: store.account.phone.replace(/\D/g, ""),
      },
      {
        accountId: config.account_id,
        expirationDate,
        singleUse: false,
      },

      (paymentMethodClaim, err) => {
        if (err) {
          store.validationError = JSON.stringify(err);
          return;
        }

        if (paymentMethodClaim) {

          store.payment.token = paymentMethodClaim;

          console.log(
              'Payment claim:',
              paymentMethodClaim
          );
        }
      }
  );
}

onMounted(() => {
  apTeanPay = window.ApteanPay(config.api_key, config.product_id, config.tenant_id);
  const components = apTeanPay.components({});

  cardComponent = components.create('card', {});

  cardComponent.mount('capture', '#submit')

  createClaim();

});

onBeforeUnmount(() => {
  cardComponent?.destroy?.();
});

</script>

<template>
  <div class="accordion" id="cenposAccordion" role="tablist">
    <h4 class="border-bottom pb-2 mb-3"
        data-toggle="collapse"
        data-target="#cenpos-credit-card"
        data-parent="#cenposAccordion"
        aria-expanded="true"
        aria-controls="cenpos-credit-card"
    >
      <i class="icon-columns mr-0" style="margin-top: -10px"></i>
      Pay with Credit Card
    </h4>
    <div class="row justify-content-between" id="cenpos-credit-card">
      <div class="col-12">
        <p>We accept following credit cards:&nbsp;
          <img class="d-inline-block align-middle"
               src="/vendor/widget/img/credit-cards.png"
               style="width: 120px;" alt="Credit Cards">
        </p>
      </div>
      <div class="col-lg-6 col-md-8 col-12">
        <div id="capture"></div>
        <button class="btn btn-outline-success" id="submit" type="button">Submit</button>
      </div>
    </div>

    <h4 class="border-bottom pb-2 mt-4 mb-3"
        data-toggle="collapse"
        data-target="#cenpos-ach"
        data-parent="#cenposAccordion"
        aria-expanded="true"
        aria-controls="cenpos-ach"
    >
      <i class="icon-file mr-0" style="margin-top: -10px"></i>
      Pay with ACH/eCheck
    </h4>
    <div class="row justify-content-between" id="cenpos-ach">
      <div class="col-lg-6 col-md-8 col-12">

      </div>
    </div>
  </div>
</template>