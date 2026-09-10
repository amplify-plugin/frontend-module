<script>
export default {
  name: "account",
  props: {
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
    contact: {
      type: Object,
      default: {
        name: null,
        email: null,
        phone: null,
      }
    }
  }
}
</script>

<template>
  <h4>Account Information</h4>
  <hr class="padding-bottom-1x">
  <div class="row">
    <div class="col-sm-6">
      <div class="form-group">
        <label for="contact-name">Name<span class="text-danger font-weight-bold">*</span></label>
        <input class="form-control"
               type="text"
               size="255"
               min="2"
               max="255"
               maxlength="255"
               placeholder="Enter Contact Name"
               id="contact-name" v-model="contact.name">
      </div>
    </div>
    <div class="col-sm-6">
      <div class="form-group">
        <label for="customer-name">Company<span class="text-danger font-weight-bold">*</span></label>
        <input class="form-control"
               type="text"
               size="255"
               min="2"
               max="255"
               maxlength="255"
               id="customer-name"
               placeholder="Enter Company Name"
               v-model="customer.CustomerName">
      </div>
    </div>
    <div class="col-sm-6">
      <div class="form-group">
        <label for="contact-email">Email Address<span class="text-danger font-weight-bold">*</span></label>
        <input class="form-control"
               type="email"
               size="255"
               min="2"
               max="255"
               maxlength="255"
               placeholder="Enter Email Address"
               id="contact-email"
               v-model="contact.email"
        >
      </div>
    </div>
    <div class="col-sm-6">
      <div class="form-group">
        <label for="contact-phone">Phone Number</label>
        <input class="form-control"
               type="email"
               size="255"
               min="2"
               max="255"
               maxlength="255"
               placeholder="Enter Phone Number"
               id="contact-phone"
               v-model="contact.phone"
        >
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
               placeholder="Enter Address Line 1"
               id="billing-address"
               v-model="customer.CustomerAddress1">
        <input class="form-control mb-1"
               type="text"
               size="255"
               min="2"
               max="255"
               maxlength="255"
               placeholder="Enter Address Line 2"
               id="billing-address-2"
               v-model="customer.CustomerAddress2">
        <input class="form-control"
               type="text"
               size="255"
               min="2"
               max="255"
               maxlength="255"
               placeholder="Enter Address Line 3"
               id="billing-address-3"
               v-model="customer.CustomerAddress3">
      </div>
    </div>
    <div class="col-sm-6">
      <div class="form-group">
        <label for="billing-country">Country<span class="text-danger font-weight-bold">*</span></label>
        <select class="form-control" id="billing-country">
          <option>Choose country</option>
          <option v-for="country of countries"
                  :key="country.iso2"
                  :selected="country.iso2 === customer.CustomerCountry"
                  :value="country.iso2">
            {{ country.name }}
          </option>
        </select>
      </div>
    </div>
    <div class="col-sm-6">
      <div class="form-group">
        <label for="billing-state">State<span class="text-danger font-weight-bold">*</span></label>
        <select class="form-control" id="billing-state">
          <option>Choose state</option>
          <option v-for="state of states"
                  :key="state.iso2"
                  :selected="state.iso2 === customer.CustomerState"
                  :value="state.iso2">
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
               maxlength="255"
               placeholder="Enter City Name"
               id="contact-email"
               v-model="customer.CustomerCity"
        >
      </div>
    </div>
    <div class="col-sm-6 padding-bottom-1x">
      <div class="form-group">
        <label for="billing-zip">ZIP Code</label>
        <input class="form-control"
               type="text"
               size="255"
               min="2"
               max="255"
               maxlength="255"
               placeholder="Enter City Name"
               id="billing-zip"
               v-model="customer.CustomerZipCode"
        >
      </div>
    </div>
  </div>
  <h4>Shipping Address</h4>
  <hr class="padding-bottom-1x">
  <div class="row">
    <div class="col-sm-12">
      <div class="form-group">
        <label for="shipping-address">Select Address</label>
        <div class="input-group input-append">
          <select class="form-control" id="shipping-address">
            <option>Choose Address</option>
            <option v-for="address of addresses"
                    :key="address.ShipToNumber"
                    :selected="address.ShipToNumber === customer.CustomerCountry"
                    :value="address.ShipToNumber">
              {{
                [address.ShipToName, address.ShipToAddress1, address.ShipToCity, address.ShipToState].filter(i => length > 0).join(',')
              }}
            </option>
          </select>
          <div class="input-group-append">
            <button type="submit" class="btn btn-primary mx-0 my-0">+ New Address</button>
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
               v-model="customer.CustomerAddress1">
        <input class="form-control mb-1"
               type="text"
               size="255"
               min="2"
               max="255"
               maxlength="255"
               readonly
               placeholder="Enter Address Line 2"
               id="billing-address-2"
               v-model="customer.CustomerAddress2">
        <input class="form-control"
               type="text"
               size="255"
               min="2"
               max="255"
               maxlength="255"
               readonly
               placeholder="Enter Address Line 3"
               id="billing-address-3"
               v-model="customer.CustomerAddress3">
      </div>
    </div>
    <div class="col-sm-6">
      <div class="form-group">
        <label for="billing-country">Country<span class="text-danger font-weight-bold">*</span></label>
        <select class="form-control" id="billing-country" readonly>
          <option>Choose country</option>
          <option v-for="country of countries"
                  :key="country.iso2"
                  :selected="country.iso2 === customer.CustomerCountry"
                  :value="country.iso2">
            {{ country.name }}
          </option>
        </select>
      </div>
    </div>
    <div class="col-sm-6">
      <div class="form-group">
        <label for="billing-state">State<span class="text-danger font-weight-bold">*</span></label>
        <select class="form-control" id="billing-state" readonly>
          <option>Choose state</option>
          <option v-for="state of states"
                  :key="state.iso2"
                  :selected="state.iso2 === customer.CustomerState"
                  :value="state.iso2">
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
               v-model="customer.CustomerCity"
        >
      </div>
    </div>
    <div class="col-sm-6 padding-bottom-1x">
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
               v-model="customer.CustomerZipCode"
        >
      </div>
    </div>
  </div>
</template>

<style scoped>

</style>