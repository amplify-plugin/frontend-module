<script setup>
import {computed, onMounted, ref} from "vue";
import {useCheckoutStore} from '../composables/useCheckoutStore';
import axios from 'axios';

const store = useCheckoutStore();

const config = store.payment.config;

const method = ref('credit_card');

const captureUrl = computed(() => {

  let prefix = window.Amplify.config.debug ? 'webstaging' : 'www';

  return method.value === 'credit_card'
      ? `https://${prefix}.cenpos.net/simplewebpay/cards/`
      : `https://${prefix}.cenpos.net/simplewebpay/checks/`;
});

const ip = ref('127.0.0.1');

const siteVerifyToken = ref(null);

const cardErrors = ref([]);

const creditCardErrors = computed(() => {

  const field = {ccnumber: 'card number', ccexp: 'expiration date', cvv: 'cvv'};

  return cardErrors.value.map((error, index) => {

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

async function siteVerify() {

  let payload = {
    merchant: config.cenpos_encrypted_mid,
    secretKey: config.secret_key,
    email: store.account.email,
    customerCode: store.customer.CustomerNumber,
    amount: store.review.total,
    tokenid: '',
    invoicenumber: '',
    type: 'Auth',
    ip: ip.value,
    address: store.payment.address,
    zipcode: store.payment.zipCode,
  };

  const response = await axios.post(
      captureUrl.value + '?app=genericcontroller&action=siteVerify',
      payload,
      {
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          Accept: 'application/json'
        },
      });

  const data = response.data;

  if (data.Result === "-1") {
    store.validationError = data.Message;
    return;
  }
  siteVerifyToken.value = data.Data ?? null;
}

async function captureCreditCard() {

  let payload = {
    isEmail: 'true',
    type: 'Sale',
    customerCode: store.customer.CustomerNumber,
    issubmit: 'false',
    amount: store.review.total,
    isCvv: 'true',
    autologin: 'false',
    disabledalert: 'false',
    verifyingpost: siteVerifyToken.value,
  };

  let encoded = new URLSearchParams(payload).toString();

  console.log(payload, encoded);

  window.$('#card-capture').createWebpay({
    url: captureUrl.value,
    params: encoded,
    width: '100%',
    sessionToken: false,
    success: (response) => {
      console.log(response);
    },
    cancel: (response) => {
      console.log(response);
    },
  })
}

onMounted(async () => {

  ip.value = await store.setIpAddress();

  await siteVerify();

  await captureCreditCard();
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
      <div class="col-md-6 col-12">
        <div id="card-capture"></div>
      </div>
      <div class="col-md-6 col-12">
        <ul class="text-danger">
          <li v-for="error in creditCardErrors">{{ error }}</li>
        </ul>
      </div>
      <div class="col-lg-6 col-md-8 col-12">

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
        <div id="ach-capture"></div>
      </div>
    </div>
  </div>
</template>