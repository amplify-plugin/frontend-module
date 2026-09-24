<script setup>
import {useCheckoutStore} from "../composables/useCheckoutStore";
import {ref} from "vue";

const store = useCheckoutStore();

const readOnly = !store.editable;

const countries = ref(store.countries);

const states = ref(store.states);

</script>

<template>
  <h4 class="border-bottom pb-2 mb-3">
    <i class="icon-head" style="margin-top: -10px"></i>
    Account Information
  </h4>
  <div class="row">
    <div class="form-group col-sm-6">
      <label for="contact-name">Name<span class="text-danger font-weight-bold">*</span></label>
      <input :class="{'form-control': true, 'is-invalid': store.account.errors.has('name')}"
             :readonly="readOnly"
             type="text"
             size="255"
             min="2"
             max="255"
             required
             maxlength="255"
             placeholder="Enter Contact Name"
             id="contact-name"
             v-model="store.account.name">
      <span class="invalid-feedback d-block">{{ store.account.errors.first('name') }}</span>
    </div>
    <div class="form-group col-sm-6">
      <label for="contact-email">Email Address<span class="text-danger font-weight-bold">*</span></label>
      <input :class="{'form-control': true, 'is-invalid': store.account.errors.has('email')}"
             :readonly="readOnly"
             type="email"
             size="255"
             min="2"
             max="255"
             maxlength="255"
             placeholder="Enter Email Address"
             id="contact-email"
             required
             v-model="store.account.email"
      >
      <span class="invalid-feedback d-block">{{ store.account.errors.first('email') }}</span>
    </div>
    <div class="form-group col-sm-6">
      <label for="contact-phone">Phone Number</label>
      <input :class="{'form-control': true, 'is-invalid': store.account.errors.has('phone')}"
             :readonly="readOnly"
             type="text"
             size="255"
             min="2"
             max="255"
             maxlength="255"
             placeholder="Enter Phone Number"
             id="contact-phone"
             v-model="store.account.phone"
      >
      <span class="invalid-feedback d-block">{{ store.account.errors.first('phone') }}</span>
    </div>
    <div class="form-group col-sm-6">
      <label for="po-number">
        PO Number
        <span v-if="store.customer?.PoRequired === 'Y'" class="text-danger font-weight-bold">*</span>
      </label>
      <input :class="{'form-control': true, 'is-invalid': store.account.errors.has('poNumber')}"
             :readonly="readOnly"
             type="text"
             size="255"
             min="2"
             max="255"
             maxlength="255"
             placeholder="Enter Customer Purchase Order Number"
             id="po-number"
             v-model="store.account.poNumber"
      >
      <span class="invalid-feedback d-block">{{ store.account.errors.first('poNumber') }}</span>
    </div>
  </div>

  <h4 class="border-bottom pb-2 mt-4 mb-3">
    <i class="icon-briefcase" style="margin-top: -10px"></i>
    Billing Information
  </h4>
  <div class="row">
    <div class="form-group col-sm-12">
      <label for="contact-company">Company<span class="text-danger font-weight-bold">*</span></label>
      <input :class="{'form-control': true, 'is-invalid': store.account.errors.has('company')}"
             :readonly="store.customer.CustomerNumber"
             type="text"
             size="255"
             min="2"
             required
             max="255"
             maxlength="255"
             id="contact-company"
             placeholder="Enter Company Name"
             v-model="store.account.company">
      <span class="invalid-feedback d-block">{{ store.account.errors.first('company') }}</span>
    </div>
    <div class="form-group col-sm-12">
      <label for="billing-address">Street Address<span class="text-danger font-weight-bold">*</span></label>
      <input :class="{'form-control mb-1': true, 'is-invalid': store.account.errors.has('addressLine1')}"
             :readonly="store.customer.CustomerNumber"
             type="text"
             size="255"
             min="2"
             required
             max="255"
             maxlength="255"
             placeholder="Enter Address Line 1"
             id="billing-address"
             v-model="store.account.addressLine1">
      <input :class="{'form-control mb-1': true, 'is-invalid': store.account.errors.has('addressLine2')}"
             :readonly="store.customer.CustomerNumber"
             type="text"
             size="255"
             min="2"
             max="255"
             maxlength="255"
             placeholder="Enter Address Line 2"
             id="billing-address-2"
             v-model="store.account.addressLine2">
      <input :class="{'form-control mb-1': true, 'is-invalid': store.account.errors.has('addressLine3')}"
             :readonly="store.customer.CustomerNumber"
             type="text"
             size="255"
             min="2"
             max="255"
             maxlength="255"
             placeholder="Enter Address Line 3"
             id="billing-address-3"
             v-model="store.account.addressLine3">
      <span class="invalid-feedback d-block">
          {{
          [store.account.errors.first('addressLine1'),
            store.account.errors.first('addressLine2'),
            store.account.errors.first('addressLine3')
          ].filter((i) => i != null).join('<br>')
          }}
        </span>
    </div>
    <div class="form-group col-sm-6">
      <label for="billing-country">Country<span class="text-danger font-weight-bold">*</span></label>
      <select :class="{'form-control custom-select': true, 'is-invalid': store.account.errors.has('country')}"
              id="billing-country"
              required
              v-model="store.account.country"
              :disabled="store.customer.CustomerNumber"
      >
        <option value="" selected>Choose country</option>
        <option v-for="country of countries" :key="country.iso2" :value="country.iso2">
          {{ country.name }}
        </option>
      </select>
      <span class="invalid-feedback d-block">{{ store.account.errors.first('country') }}</span>
    </div>
    <div class="form-group col-sm-6">
      <label for="billing-state">State<span class="text-danger font-weight-bold">*</span></label>
      <select :class="{'form-control custom-select': true, 'is-invalid': store.account.errors.has('state')}"
              id="billing-state"
              required
              v-model="store.account.state"
              :disabled="store.customer.CustomerNumber">
        <option>Choose state</option>
        <option v-for="state of states" :key="state.iso2" :value="state.iso2">
          {{ state.name }}
        </option>
      </select>
      <span class="invalid-feedback d-block">{{ store.account.errors.first('state') }}</span>
    </div>
    <div class="form-group col-sm-6">
      <label for="billing-city">City<span class="text-danger font-weight-bold">*</span></label>
      <input :class="{'form-control': true, 'is-invalid': store.account.errors.has('city')}"
             :readonly="store.customer.CustomerNumber"
             type="text"
             size="255"
             min="2"
             required
             max="255"
             maxlength="255"
             placeholder="Enter City Name"
             id="billing-city"
             v-model="store.account.city"
      >
      <span class="invalid-feedback d-block">{{ store.account.errors.first('city') }}</span>
    </div>
    <div class="form-group col-sm-6">
      <label for="billing-zip">ZIP Code<span class="text-danger font-weight-bold">*</span></label>
      <input :class="{'form-control': true, 'is-invalid': store.account.errors.has('zipCode')}"
             :readonly="store.customer.CustomerNumber"
             type="text"
             size="255"
             min="2"
             required
             max="255"
             maxlength="255"
             placeholder="Enter City Name"
             id="billing-zip"
             v-model="store.account.zipCode"
      >
      <span class="invalid-feedback d-block">{{ store.account.errors.first('zipCode') }}</span>
    </div>
  </div>
</template>