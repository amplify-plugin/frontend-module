<script setup>
import {computed, onMounted, ref} from 'vue';
import {useCheckoutStore} from '../composables/useCheckoutStore';
import cenposGateway from '../gateways/cenpos.vue';
import apteanGateway from '../gateways/aptean.vue';
import defaultGateway from '../gateways/default.vue';
import {useValidate} from "@/composables/useValidate";

const store = useCheckoutStore();

const validator = useValidate();

const gateways = {
  aptean: apteanGateway,
  cenpos: cenposGateway,
  default: defaultGateway
};

const currentGateway = computed(() => gateways[store.payment.driver] ?? defaultGateway);

const countries = ref(store.countries);

const states = ref(store.states);

const form = ref({
  biller: store.payment.biller,
  street_address: store.payment.address,
  city: store.payment.city,
  state: store.payment.state,
  zipCode: store.payment.zipCode,
  country: store.payment.country,
});

let errors = validator.make();

let closeButton = ref(null);

function savePaymentAddress() {
  errors = validator.make(form.value, {
    biller: [store.payment.driver === 'aptean' ? 'required' : 'nullable', 'min:3', 'max:255'],
    address: ['required', 'max:255'],
    zipCode: ['required', 'min:4'],
    city: ['nullable'],
    state: ['required', 'min:2'],
    country: ['required', 'min:2'],
  });

  if (errors.passed()) {
    store.payment.biller = form.value.biller;
    store.payment.stree_address = form.value.street_address;
    store.payment.city = form.value.city;
    store.payment.state = form.value.state;
    store.payment.zipCode = form.value.zipCode;
    store.payment.country = form.value.country;
    store.paymentAddressChanged = !store.paymentAddressChanged;
    closeButton.value?.click();
  }
}

onMounted(() => {
  store.initPaymentGateway();
})

</script>

<template>
  <h4 class="border-bottom pb-2 mb-3" v-if="store.allowChooseBilling">
    <i class="icon-file-add" style="margin-top: -10px"></i>
    Billing Information
  </h4>
  <div class="w-100 d-grid d-md-flex justify-content-between gap-2 align-items-center mb-4"
       v-if="store.allowChooseBilling">
    <span>
      <span>Biller:&nbsp;</span>
      <span class="text-uppercase font-weight-bolder">{{ store.payment.biller }}</span><br>
      <span>Address:&nbsp;</span>
      <span class="text-uppercase font-weight-bolder">
        {{
          [store.payment.address, store.payment.city, store.payment.zipCode, store.payment.state, store.account.country].join(', ')
        }}
    </span>
    </span>
    <span class="d-flex d-md-grid justify-content-start">
      <span>Estimated Total:&nbsp;</span>
      <span class="font-weight-bolder">{{ store.priceFormatter(store.review.total) }}</span>
    </span>
  </div>

  <component :is="currentGateway"/>

  <teleport to="#html-default">
    <div class="modal fade" id="payment-address-modal" tabindex="-1" role="dialog" data-backdrop="static">
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header align-items-center">
            <h4 class="modal-title">Payment Address</h4>
            <button class="close" type="button" data-dismiss="modal" aria-label="Close"><span
                aria-hidden="true">&times;</span></button>
          </div>
          <div class="modal-body">
            <p class="mb-2 text-info">
              <b><i class="icon-bell"></i>&nbsp;Note:</b> This address will be used for payment gateway verification.
            </p>
            <div class="row">
              <div class="form-group col-sm-12" v-if="['aptean'].includes(store.payment.driver)">
                <label for="payment-biller">Biller<span class="text-danger font-weight-bold">*</span></label>
                <input :class="{'form-control': true, 'is-invalid': errors.has('biller')}"
                       type="text"
                       size="255"
                       min="2"
                       required
                       max="255"
                       maxlength="255"
                       id="payment-biller"
                       placeholder="Enter Biller Name/Title"
                       v-model="form.biller">
                <span class="invalid-feedback d-block">{{ errors.first('biller') }}</span>
              </div>
              <div class="form-group col-sm-12">
                <label for="payment-street-address">Street Address<span
                    class="text-danger font-weight-bold">*</span></label>
                <input :class="{'form-control': true, 'is-invalid': errors.has('street_address')}"
                       type="text"
                       size="255"
                       min="2"
                       required
                       max="255"
                       maxlength="255"
                       placeholder="Enter Street Address"
                       id="payment-street-address"
                       v-model="form.street_address">
                <span class="invalid-feedback d-block">{{ errors.first('street_address') }}</span>
              </div>
              <div class="form-group col-sm-6">
                <label for="payment-country">Country<span class="text-danger font-weight-bold">*</span></label>
                <select :class="{'form-control custom-select': true, 'is-invalid': errors.has('country')}"
                        id="payment-country"
                        required disabled
                        v-model="form.country">
                  <option value="" selected>Choose country</option>
                  <option v-for="country of countries" :key="country.iso2" :value="country.iso2">
                    {{ country.name }}
                  </option>
                </select>
                <span class="invalid-feedback d-block">{{ errors.first('country') }}</span>
              </div>
              <div class="form-group col-sm-6">
                <label for="payment-state">State<span class="text-danger font-weight-bold">*</span></label>
                <select :class="{'form-control custom-select': true, 'is-invalid': errors.has('state')}"
                        id="payment-state"
                        required
                        v-model="form.state">
                  <option>Choose state</option>
                  <option v-for="state of states" :key="state.iso2" :value="state.iso2">
                    {{ state.name }}
                  </option>
                </select>
                <span class="invalid-feedback d-block">{{ errors.first('state') }}</span>
              </div>
              <div class="form-group col-sm-6">
                <label for="payment-city">City</label>
                <input :class="{'form-control': true, 'is-invalid': errors.has('city')}"
                       type="text"
                       size="255"
                       min="2"
                       required
                       max="255"
                       maxlength="255"
                       placeholder="Enter City Name"
                       id="payment-city"
                       v-model="form.city"
                >
                <span class="invalid-feedback d-block">{{ errors.first('city') }}</span>
              </div>
              <div class="form-group col-sm-6">
                <label for="payment-zip">ZIP Code<span class="text-danger font-weight-bold">*</span></label>
                <input :class="{'form-control': true, 'is-invalid': errors.has('zipCode')}"
                       type="text"
                       size="255"
                       min="2"
                       required
                       max="255"
                       maxlength="255"
                       placeholder="Enter Zip Code"
                       id="payment-zip"
                       v-model="form.zipCode"
                >
                <span class="invalid-feedback d-block">{{ errors.first('zipCode') }}</span>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-primary btn-sm" type="button" @click.prevent="savePaymentAddress">Save</button>
            <button class="btn btn-outline-secondary btn-sm" type="button" ref="closeButton" data-dismiss="modal">
              Close
            </button>
          </div>
        </div>
      </div>
    </div>
  </teleport>
</template>
