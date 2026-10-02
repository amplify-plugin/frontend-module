<script setup>
import {computed, onMounted, ref} from "vue";
import {useCheckoutStore} from '../composables/useCheckoutStore';

const store = useCheckoutStore();

const config = store.payment.config;

let apTeanPay = null;
let options = null;

const cardErrors = ref([]);

const creditCardErrors = computed(() => {

  const field = {ccnumber: 'card number', ccexp: 'expiration date', cvv: 'cvv'};

  return  cardErrors.value.map((error, index) => {

    let message = `The ${String(error.message).toLowerCase()}`;

    if (error.message === 'Field is empty') {
      message = `The ${field[error.param]} field is required.`;
    }

    if (index === 0) {
      store.validationError = message;
    }

    return message;

  });
})

async function captureCreditCard() {

  store.selectPaymentMethod('credit_card');

  const cardComponent = options.create('card', {
    customStyle: store.apteanStyle,
    showLabels: true,
    showPlaceholders: true
  });

  cardComponent.mount('capture', '#submit');

  const expirationDate = new Date();

  expirationDate.setMonth(
      expirationDate.getMonth() + 1
  );

  apTeanPay.createClaim(
      cardComponent,
      {
        name: store.payment.biller,
        addressLine1: store.payment.address,
        addressCity: store.payment.city,
        addressState: store.payment.state,
        addressZip: store.payment.zipCode,
        addressCountry: store.payment.country,
        emailAddress: store.account.email,
        recurring: false,
        phoneCode: store.payment.country === 'US' ? '+1' : '',
        phoneNumber: store.payment.phone.replace(/\D/g, ""),
      },
      {
        accountId: config.account_id,
        expirationDate,
        singleUse: false,
      },
      responseHandler
  );

}

function responseHandler(token, error) {
  if (error) {
    store.payment.credentials = {};
    cardErrors.value = error;
    store.loading = false;
    return;
  }

  if (token) {
    store.payment.credentials = JSON.parse(JSON.stringify(token));
    cardErrors.value = [];
    store.goNext();
    store.loading = false;
  }
}

onMounted(async () => {

  apTeanPay = window.ApteanPay(config.api_key, config.product_id, config.tenant_id);

  options = apTeanPay.components({});

  await captureCreditCard();
});

</script>

<template>
<!--  <div class="accordion mt-4" id="apteanAccordion" role="tablist">-->
<!--        data-toggle="collapse"-->
<!--        data-target="#aptean-credit-card"-->
<!--        data-parent="#apteanAccordion"-->
<!--        aria-expanded="true"-->
<!--        aria-controls="aptean-credit-card"-->
    <h4 class="border-bottom pb-2 mb-3">
      <i class="icon-columns mr-0" style="margin-top: -10px"></i>
      Pay with Credit Card
    </h4>
    <div class="row justify-content-between" id="aptean-credit-card">
      <div class="col-12">
        <p>We accept following credit cards:&nbsp;
          <img class="d-inline-block align-middle"
               src="/vendor/widget/img/credit-cards.png"
               style="width: 120px;" alt="Credit Cards">
        </p>
      </div>
      <div class="col-md-6 col-12">
        <div id="capture"></div>
      </div>
      <div class="col-md-6 col-12">
        <ul class="text-danger">
          <li v-for="error in creditCardErrors">{{ error }}</li>
        </ul>
      </div>
    </div>

<!--    <h4 class="border-bottom pb-2 mt-4 mb-3"
        data-toggle="collapse"
        data-target="#aptean-ach"
        data-parent="#apteanAccordion"
        aria-expanded="true"
        aria-controls="aptean-ach"
    >
      <i class="icon-file mr-0" style="margin-top: -10px"></i>
      Pay with ACH/eCheck
    </h4>
    <div class="row justify-content-between" id="aptean-ach">
      <div class="col-lg-6 col-md-8 col-12">

      </div>
    </div>-->
<!--  </div>-->
</template>

<style lang="scss">
#capture {
  #ptt-card-container {
    width: 100%;
  }

  #ptt-card-expiry-container{
    display: inline-block;
    width: calc(50% - 0.5rem) !important;
    max-width: calc(50% - 0.5rem) !important;
  }

  #ptt-card-cvv-container {
    display: inline-block;
    width: calc(50% - 0.5rem) !important;
    max-width: calc(50% - 0.5rem) !important;
  }
}
</style>