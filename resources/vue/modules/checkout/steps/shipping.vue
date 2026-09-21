<script setup>
import {computed, onMounted, ref} from 'vue';
import {useCheckoutStore} from '../composables/useCheckoutStore';

const store = useCheckoutStore();

const addresses = ref(store.addresses);

const countries = ref(store.countries);

const states = ref(store.states);

const shipping = ref(store.shipping);

const methods = computed(() => {
  const freightRate = store.shipOptions?.FreightRate ?? {};
  const list = [];
  for (const [group, groupMethods] of Object.entries(freightRate)) {
    for (const methodObj of groupMethods) {
      const key = Object.keys(methodObj)[0];
      list.push({group, ...methodObj[key]});
    }
  }
  return list;
});

function isSelected(method) {
  return store.selectedShippingMethod?.shipvia === method.shipvia;
}

function select(method, group) {
  store.selectShippingMethod(group, method);
}

function addressSelected(shipToNumber) {

  let addressFound = false

  for (const address of addresses.value) {
    if (address.ShipToNumber === shipToNumber) {
      store.fillShippingData(address);
      addressFound = true;
      break;
    }
  }

  if (addressFound === false) {
    store.fillShippingData();
  }
}

onMounted(() => {
  addressSelected(store.customer.DefaultShipTo);
})

</script>

<template>
  <h4 class="border-bottom pb-2 mb-3">
    <i class="icon-book" style="margin-top: -10px"></i>
    Shipping Address
  </h4>
  <div class="row">
    <div class="col-sm-12">
      <div class="form-group">
        <label for="shipping-address">Select Address</label>
        <div :class="{'input-group input-append' : store.allowCreateShipping}">
          <select :class="{'form-control custom-select': true, 'is-invalid': shipping.errors.has('number')}"
                  @change="addressSelected($event.target.value)"
                  v-model="shipping.number"
                  id="shipping-address"
                  :disabled="!store.allowChooseShipping"
          >
            <option value="">Choose Address</option>
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
          <div v-if="store.allowCreateShipping" class="input-group-append">
            <button type="submit" class="btn btn-primary mx-0 my-0">
              <i class="icon-plus font-weight-bolder"></i>
              <span class="d-none d-md-inline-block ml-1">New Ship To</span>
            </button>
          </div>
        </div>
        <span class="invalid-feedback d-block">{{ shipping.errors.first('number') }}</span>
      </div>
    </div>
    <div class="col-sm-6">
      <div class="form-group">
        <label for="shipping-contact">Contact<span class="text-danger font-weight-bold">*</span></label>
        <input :class="{'form-control': true, 'is-invalid': shipping.errors.has('contact')}"
               :readonly="shipping.number"
               type="text"
               size="255"
               min="2"
               max="255"
               maxlength="255"
               placeholder="Enter Shipping Contact Name"
               id="shipping-contact"
               v-model="shipping.contact"
        >
        <span class="invalid-feedback d-block">{{ shipping.errors.first('contact') }}</span>
      </div>
    </div>
    <div class="col-sm-6">
      <div class="form-group">
        <label for="shipping-phone">Phone</label>
        <input :class="{'form-control': true, 'is-invalid': shipping.errors.has('phone')}"
               :readonly="shipping.number"
               type="text"
               size="255"
               min="2"
               max="255"
               maxlength="255"
               placeholder="Enter Shipping Phone Number"
               id="shipping-phone"
               v-model="shipping.phone"
        >
        <span class="invalid-feedback d-block">{{ shipping.errors.first('phone') }}</span>
      </div>
    </div>
    <div class="col-sm-12">
      <div class="form-group">
        <label for="shipping-address">Street Address<span class="text-danger font-weight-bold">*</span></label>
        <input :class="{'form-control mb-1': true, 'is-invalid': shipping.errors.has('addressLine1')}"
               :readonly="shipping.number"
               type="text"
               size="255"
               min="2"
               max="255"
               maxlength="255"
               placeholder="Enter Address Line 1"
               id="shipping-address"
               v-model="shipping.addressLine1">
        <input :class="{'form-control mb-1': true, 'is-invalid': shipping.errors.has('addressLine2')}"
               :readonly="shipping.number"
               type="text"
               size="255"
               min="2"
               max="255"
               maxlength="255"
               placeholder="Enter Address Line 2"
               id="shipping-address-2"
               v-model="shipping.addressLine2">
        <input :class="{'form-control mb-1': true, 'is-invalid': shipping.errors.has('addressLine3')}"
               :readonly="shipping.number"
               type="text"
               size="255"
               min="2"
               max="255"
               maxlength="255"
               placeholder="Enter Address Line 3"
               id="shipping-address-3"
               v-model="shipping.addressLine3">
        <span class="invalid-feedback d-block">
          {{
            [shipping.errors.first('addressLine1'),
              shipping.errors.first('addressLine2'),
              shipping.errors.first('addressLine3')
            ].filter((i) => i != null).join('<br>')
          }}
        </span>
      </div>
    </div>
    <div class="col-sm-6">
      <div class="form-group">
        <label for="shipping-country">Country<span class="text-danger font-weight-bold">*</span></label>
        <select :class="{'form-control custom-select': true, 'is-invalid': shipping.errors.has('country')}"
                id="shipping-country"
                v-model="shipping.country"
                :disabled="shipping.number"
        >
          <option value="" selected>Choose country</option>
          <option v-for="country of countries" :key="country.iso2" :value="country.iso2">
            {{ country.name }}
          </option>
        </select>
        <span class="invalid-feedback d-block">{{ shipping.errors.first('country') }}</span>
      </div>
    </div>
    <div class="col-sm-6">
      <div class="form-group">
        <label for="shipping-state">State<span class="text-danger font-weight-bold">*</span></label>
        <select :class="{'form-control custom-select': true, 'is-invalid': shipping.errors.has('state')}"
                id="shipping-state"
                v-model="shipping.state"
                :disabled="shipping.number">
          <option>Choose state</option>
          <option v-for="state of states" :key="state.iso2" :value="state.iso2">
            {{ state.name }}
          </option>
        </select>
        <span class="invalid-feedback d-block">{{ shipping.errors.first('state') }}</span>
      </div>
    </div>
    <div class="col-sm-6">
      <div class="form-group">
        <label for="shipping-city">City<span class="text-danger font-weight-bold">*</span></label>
        <input :class="{'form-control': true, 'is-invalid': shipping.errors.has('city')}"
               :readonly="shipping.number"
               type="text"
               size="255"
               min="2"
               max="255"
               maxlength="255"
               placeholder="Enter City Name"
               id="shipping-city"
               v-model="shipping.city"
        >
        <span class="invalid-feedback d-block">{{ shipping.errors.first('city') }}</span>
      </div>
    </div>
    <div class="col-sm-6">
      <div class="form-group">
        <label for="shipping-zip">ZIP Code<span class="text-danger font-weight-bold">*</span></label>
        <input :class="{'form-control': true, 'is-invalid': shipping.errors.has('zipCode')}"
               :readonly="shipping.number"
               type="text"
               size="255"
               min="2"
               max="255"
               maxlength="255"
               placeholder="Enter City Name"
               id="shipping-zip"
               v-model="shipping.zipCode"
        >
        <span class="invalid-feedback d-block">{{ shipping.errors.first('zipCode') }}</span>
      </div>
    </div>
  </div>

  <h4 class="border-bottom pb-2 mt-4 mb-3">Choose Shipping Method</h4>
  <div class="card">
    <div class="card-body">
      <ul>
        <li v-for="report in store.shipOptions?.statusReports ?? []" :key="report.statSeq">
          <p>
            {{ report.statLine ?? '' }}
          </p>
        </li>
      </ul>
    </div>
    <div class="card-body">
      <pre>{{ JSON.stringify(store.shipOptions, null, 2) }}</pre>
    </div>
  </div>
<!--  <div class="table-responsive">-->
<!--    <table class="table table-hover">-->
<!--      <thead class="thead-default">-->
<!--      <tr>-->
<!--        <th></th>-->
<!--        <th>Shipping method</th>-->
<!--        <th>Delivery time</th>-->
<!--        <th>Handling fee</th>-->
<!--      </tr>-->
<!--      </thead>-->
<!--      <tbody>-->
<!--      <tr v-for="(method, index) in methods" :key="method.shipvia + index" @click="select(method, method.group)">-->
<!--        <td class="align-middle">-->
<!--          <div class="custom-control custom-radio mb-0">-->
<!--            <input class="custom-control-input" type="radio" name="shipping-method"-->
<!--                   :id="`shipping-method-${index}`" :value="method"-->
<!--                   :checked="isSelected(method)" @change="select(method, method.group)">-->
<!--            <label class="custom-control-label" :for="`shipping-method-${index}`"></label>-->
<!--          </div>-->
<!--        </td>-->
<!--        <td class="align-middle">-->
<!--          <span class="text-medium">{{ method.name || method.shipvia }}</span><br>-->
<!--          <span class="text-muted text-sm">{{ method.group }}</span>-->
<!--        </td>-->
<!--        <td class="align-middle">&mdash;</td>-->
<!--        <td class="align-middle">{{ store.priceFormatter(method.amount) }}</td>-->
<!--      </tr>-->
<!--      </tbody>-->
<!--    </table>-->
<!--  </div>-->
<!--  <div class="form-group" v-if="store.selectedShippingMethod?.frttermscd === 'C'">-->
<!--    <label for="freight-account-number">Freight Account Number <span-->
<!--        class="text-danger font-weight-bold">*</span></label>-->
<!--    <input type="text" class="form-control" id="freight-account-number"-->
<!--           placeholder="Enter your freight account number" v-model="store.freightAccountNumber">-->
<!--  </div>-->
  <div class="alert alert-danger mt-2" role="alert" v-if="store.validationError">
    {{ store.validationError }}
  </div>
</template>
