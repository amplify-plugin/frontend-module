<script setup>
import {useCheckoutStore} from "../composables/useCheckoutStore";
import {onMounted, ref} from "vue";

const store = useCheckoutStore();

const addresses = ref(store.addresses);

const countries = ref(store.countries);

const states = ref(store.states);

const readOnly = !store.editable;

function addressChanged(shipToNumber) {

  let addressFound = false

  for (const address of addresses.value) {
    if (address.ShipToNumber === shipToNumber) {
      store.shipping = address;
      addressFound = true;
      break;
    }
  }

  if (addressFound === false) {
    store.shipping = {};
  }
}

onMounted(() => {
  addressChanged(store.customer.DefaultShipTo);
})

</script>

<template>
  <div class="row">
    <div class="col-12">
      <h4 class="border-bottom pb-2 mb-4">
        <i class="icon-head" style="margin-top: -10px"></i>
        Account Information
      </h4>
      <div class="row">
        <div class="col-sm-12">
          <div class="form-group">
            <label for="contact-name">Name<span class="text-danger font-weight-bold">*</span></label>
            <input class="form-control"
                   :readonly="readOnly"
                   type="text"
                   size="255"
                   min="2"
                   max="255"
                   maxlength="255"
                   placeholder="Enter Contact Name"
                   id="contact-name" v-model="store.contact.name">
            <span class="invalid-feedback d-block" id="contact-name-error"></span>
          </div>
        </div>
        <div class="col-sm-6">
          <div class="form-group">
            <label for="contact-email">Email Address<span class="text-danger font-weight-bold">*</span></label>
            <input class="form-control"
                   :readonly="readOnly"
                   type="email"
                   size="255"
                   min="2"
                   max="255"
                   maxlength="255"
                   placeholder="Enter Email Address"
                   id="contact-email"
                   v-model="store.contact.email"
            >
            <span class="invalid-feedback d-block" id="contact-email-error"></span>
          </div>
        </div>
        <div class="col-sm-6">
          <div class="form-group">
            <label for="contact-phone">Phone Number</label>
            <input class="form-control"
                   :readonly="readOnly"
                   type="text"
                   size="255"
                   min="2"
                   max="255"
                   maxlength="255"
                   placeholder="Enter Phone Number"
                   id="contact-phone"
                   v-model="store.contact.phone"
            >
            <span class="invalid-feedback d-block" id="contact-phone-error"></span>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6">
      <h4 class="border-bottom pb-2 my-4">
        <i class="icon-briefcase" style="margin-top: -10px"></i>
        Billing Information
      </h4>
      <div class="row">
        <div class="col-sm-12">
          <div class="form-group">
            <label for="customer-name">Company<span class="text-danger font-weight-bold">*</span></label>
            <input class="form-control"
                   :readonly="store.customer.CustomerNumber"
                   type="text"
                   size="255"
                   min="2"
                   max="255"
                   maxlength="255"
                   id="customer-name"
                   placeholder="Enter Company Name"
                   v-model="store.customer.CustomerName">
          </div>
        </div>
        <div class="col-sm-12">
          <div class="form-group">
            <label for="billing-address">Address</label>
            <input class="form-control mb-1"
                   :readonly="store.customer.CustomerNumber"
                   type="text"
                   size="255"
                   min="2"
                   max="255"
                   maxlength="255"
                   placeholder="Enter Address Line 1"
                   id="billing-address"
                   v-model="store.customer.CustomerAddress1">
            <input class="form-control mb-1"
                   :readonly="store.customer.CustomerNumber"
                   type="text"
                   size="255"
                   min="2"
                   max="255"
                   maxlength="255"
                   placeholder="Enter Address Line 2"
                   id="billing-address-2"
                   v-model="store.customer.CustomerAddress2">
            <input class="form-control"
                   :readonly="store.customer.CustomerNumber"
                   type="text"
                   size="255"
                   min="2"
                   max="255"
                   maxlength="255"
                   placeholder="Enter Address Line 3"
                   id="billing-address-3"
                   v-model="store.customer.CustomerAddress3">
          </div>
        </div>
        <div class="col-sm-6">
          <div class="form-group">
            <label for="billing-country">Country<span class="text-danger font-weight-bold">*</span></label>
            <select class="form-control custom-select"
                    id="billing-country"
                    v-model="store.customer.CustomerCountry"
                    :disabled="store.customer.CustomerNumber"
            >
              <option value="" selected>Choose country</option>
              <option v-for="country of countries" :key="country.iso2" :value="country.iso2">
                {{ country.name }}
              </option>
            </select>
          </div>
        </div>
        <div class="col-sm-6">
          <div class="form-group">
            <label for="billing-state">State<span class="text-danger font-weight-bold">*</span></label>
            <select class="form-control custom-select"
                    id="billing-state"
                    v-model="store.customer.CustomerState"
                    :disabled="store.customer.CustomerNumber">
              <option>Choose state</option>
              <option v-for="state of states" :key="state.iso2" :value="state.iso2">
                {{ state.name }}
              </option>
            </select>
          </div>
        </div>
        <div class="col-sm-6">
          <div class="form-group">
            <label for="checkout-zip">City</label>
            <input class="form-control"
                   :readonly="store.customer.CustomerNumber"
                   type="text"
                   size="255"
                   min="2"
                   max="255"
                   maxlength="255"
                   placeholder="Enter City Name"
                   id="contact-email"
                   v-model="store.customer.CustomerCity"
            >
          </div>
        </div>
        <div class="col-sm-6">
          <div class="form-group">
            <label for="billing-zip">ZIP Code</label>
            <input class="form-control"
                   :readonly="store.customer.CustomerNumber"
                   type="text"
                   size="255"
                   min="2"
                   max="255"
                   maxlength="255"
                   placeholder="Enter City Name"
                   id="billing-zip"
                   v-model="store.customer.CustomerZipCode"
            >
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6">
      <h4 class="border-bottom pb-2 my-4">
        <i class="icon-book" style="margin-top: -10px"></i>
        Shipping Address
      </h4>
      <div class="row">
        <div class="col-sm-12">
          <div class="form-group">
            <label for="shipping-address">Select Address</label>
            <div class="input-group input-append">
              <select class="form-control custom-select"
                      @change="addressChanged($event.target.value)"
                      v-model="store.shipping.ShipToNumber"
                      id="shipping-address">
                <option value="" selected>Choose Address</option>
                <option v-for="address of addresses"
                        :key="address.ShipToNumber"
                        :selected="address.ShipToNumber === store.customer.DefaultShipTo"
                        :value="address.ShipToNumber">
                  {{
                    address.ShipToName + ' - ' +
                    [address.ShipToAddress1, address.ShipToCity, address.ShipToState].filter(i => i !== null && i !== '').join(', ')
                  }}
                </option>
              </select>
              <div class="input-group-append">
                <button type="submit" class="btn btn-primary mx-0 my-0">
                  <i class="icon-plus font-weight-bolder"></i>
                  <span class="d-none d-md-inline-block ml-1">New Ship To</span>
                </button>
              </div>
            </div>
          </div>
        </div>
        <div class="col-sm-12">
          <div class="form-group">
            <label for="billing-address">Address</label>
            <input class="form-control mb-1"
                   type="text"
                   size="255"
                   min="2"
                   max="255"
                   maxlength="255"
                   readonly
                   placeholder="Enter Address Line 1"
                   id="billing-address"
                   v-model="store.shipping.ShipToAddress1">
            <input class="form-control mb-1"
                   type="text"
                   size="255"
                   min="2"
                   max="255"
                   maxlength="255"
                   readonly
                   placeholder="Enter Address Line 2"
                   id="billing-address-2"
                   v-model="store.shipping.ShipToAddress2">
            <input class="form-control"
                   type="text"
                   size="255"
                   min="2"
                   max="255"
                   maxlength="255"
                   readonly
                   placeholder="Enter Address Line 3"
                   id="billing-address-3"
                   v-model="store.shipping.ShipToAddress3">
          </div>
        </div>
        <div class="col-sm-6">
          <div class="form-group">
            <label for="billing-country">Country<span class="text-danger font-weight-bold">*</span></label>
            <select class="form-control"
                    v-model="store.shipping.ShipToCountryCode"
                    id="billing-country" disabled>
              <option value="" selected>Choose country</option>
              <option v-for="country of countries" :key="country.iso2" :value="country.iso2">
                {{ country.name }}
              </option>
            </select>
          </div>
        </div>
        <div class="col-sm-6">
          <div class="form-group">
            <label for="billing-state">State<span class="text-danger font-weight-bold">*</span></label>
            <select class="form-control"
                    v-model="store.shipping.ShipToState"
                    id="billing-state"
                    disabled>
              <option>Choose state</option>
              <option v-for="state of states" :key="state.iso2" :value="state.iso2">
                {{ state.name }}
              </option>
            </select>
          </div>
        </div>
        <div class="col-sm-6">
          <div class="form-group">
            <label for="checkout-zip">City</label>
            <input class="form-control"
                   type="text"
                   size="255"
                   min="2"
                   max="255"
                   readonly
                   maxlength="255"
                   placeholder="Enter City Name"
                   id="contact-email"
                   v-model="store.shipping.ShipToCity"
            >
          </div>
        </div>
        <div class="col-sm-6">
          <div class="form-group">
            <label for="billing-zip">ZIP Code</label>
            <input class="form-control"
                   type="text"
                   size="255"
                   min="2"
                   max="255"
                   readonly
                   maxlength="255"
                   placeholder="Enter City Name"
                   id="billing-zip"
                   v-model="store.shipping.ShipToZipCode"
            >
          </div>
        </div>
      </div>
    </div>
  </div>
</template>