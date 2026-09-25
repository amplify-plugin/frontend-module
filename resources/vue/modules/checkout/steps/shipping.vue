<script setup>
import {computed, onMounted, ref} from 'vue';
import {useCheckoutStore} from '../composables/useCheckoutStore';
import NoShipOptions from "./components/no-ship-options.vue";

const store = useCheckoutStore();

const addresses = ref(store.addresses);

const countries = ref(store.countries);

const states = ref(store.states);

const shipOptionLabels = computed(() => {
  return Object.keys(store.shipOptions);
})

const defaultShippingOption = computed(() => {

  for (const [group, methods] of Object.entries(store.shipOptions)) {

    for (const option of methods) {

      if (option.shipvia === store.shipping.method) {
        return group;
      }
    }
  }

  return '';
})

function slugify(value) {
  return String(value)
      .normalize('NFKD')
      .replace(/[\u0300-\u036f]/g, '') // Remove accents
      .toLowerCase()
      .trim()
      .replace(/[^a-z0-9\s-]/g, '')     // Remove special characters
      .replace(/[\s_-]+/g, '-')         // Spaces/underscores → hyphen
      .replace(/^-+|-+$/g, '');         // Trim hyphens
}

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
          <select :class="{'form-control custom-select': true, 'is-invalid': store.shipping.errors.has('number')}"
                  @change="store.selectAddressSelected($event.target.value)"
                  v-model="store.shipping.number"
                  id="shipping-address"
                  :disabled="!store.allowChooseShipping"
          >
            <option value="">Choose Address</option>
            <option v-for="address of addresses"
                    :key="address.ShipToNumber"
                    :value="address.ShipToNumber">
              {{
                address.ShipToName + ' - ' +
                [address.ShipToAddress1, address.ShipToCity, address.ShipToZipCode, address.ShipToState, address.ShipToCountryCode].filter(i => i !== null && i !== '').join(', ')
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
        <span class="invalid-feedback d-block">{{ store.shipping.errors.first('number') }}</span>
      </div>
    </div>
    <div class="col-sm-6">
      <div class="form-group">
        <label for="shipping-contact">Contact<span class="text-danger font-weight-bold">*</span></label>
        <input :class="{'form-control': true, 'is-invalid': store.shipping.errors.has('contact')}"
               :readonly="store.shipping.number"
               type="text"
               size="255"
               min="2"
               max="255"
               maxlength="255"
               placeholder="Enter Shipping Contact Name"
               id="shipping-contact"
               v-model="store.shipping.contact"
        >
        <span class="invalid-feedback d-block">{{ store.shipping.errors.first('contact') }}</span>
      </div>
    </div>
    <div class="col-sm-6">
      <div class="form-group">
        <label for="shipping-phone">Phone</label>
        <input :class="{'form-control': true, 'is-invalid': store.shipping.errors.has('phone')}"
               :readonly="store.shipping.number"
               type="text"
               size="255"
               min="2"
               max="255"
               maxlength="255"
               placeholder="Enter Shipping Phone Number"
               id="shipping-phone"
               v-model="store.shipping.phone"
        >
        <span class="invalid-feedback d-block">{{ store.shipping.errors.first('phone') }}</span>
      </div>
    </div>
    <div class="col-sm-12">
      <div class="form-group">
        <label for="shipping-address">Street Address<span class="text-danger font-weight-bold">*</span></label>
        <input :class="{'form-control mb-1': true, 'is-invalid': store.shipping.errors.has('addressLine1')}"
               :readonly="store.shipping.number"
               type="text"
               size="255"
               min="2"
               max="255"
               maxlength="255"
               placeholder="Enter Address Line 1"
               id="shipping-address"
               v-model="store.shipping.addressLine1">
        <input :class="{'form-control mb-1': true, 'is-invalid': store.shipping.errors.has('addressLine2')}"
               :readonly="store.shipping.number"
               type="text"
               size="255"
               min="2"
               max="255"
               maxlength="255"
               placeholder="Enter Address Line 2"
               id="shipping-address-2"
               v-model="store.shipping.addressLine2">
        <input :class="{'form-control mb-1': true, 'is-invalid': store.shipping.errors.has('addressLine3')}"
               :readonly="store.shipping.number"
               type="text"
               size="255"
               min="2"
               max="255"
               maxlength="255"
               placeholder="Enter Address Line 3"
               id="shipping-address-3"
               v-model="store.shipping.addressLine3">
        <span class="invalid-feedback d-block">
          {{
            [store.shipping.errors.first('addressLine1'),
              store.shipping.errors.first('addressLine2'),
              store.shipping.errors.first('addressLine3')
            ].filter((i) => i != null).join('<br>')
          }}
        </span>
      </div>
    </div>
    <div class="col-sm-6">
      <div class="form-group">
        <label for="shipping-country">Country<span class="text-danger font-weight-bold">*</span></label>
        <select :class="{'form-control custom-select': true, 'is-invalid': store.shipping.errors.has('country')}"
                id="shipping-country"
                v-model="store.shipping.country"
                :disabled="store.shipping.number"
        >
          <option value="" selected>Choose country</option>
          <option v-for="country of countries" :key="country.iso2" :value="country.iso2">
            {{ country.name }}
          </option>
        </select>
        <span class="invalid-feedback d-block">{{ store.shipping.errors.first('country') }}</span>
      </div>
    </div>
    <div class="col-sm-6">
      <div class="form-group">
        <label for="shipping-state">State<span class="text-danger font-weight-bold">*</span></label>
        <select :class="{'form-control custom-select': true, 'is-invalid': store.shipping.errors.has('state')}"
                id="shipping-state"
                v-model="store.shipping.state"
                :disabled="store.shipping.number">
          <option>Choose state</option>
          <option v-for="state of states" :key="state.iso2" :value="state.iso2">
            {{ state.name }}
          </option>
        </select>
        <span class="invalid-feedback d-block">{{ store.shipping.errors.first('state') }}</span>
      </div>
    </div>
    <div class="col-sm-6">
      <div class="form-group">
        <label for="shipping-city">City<span class="text-danger font-weight-bold">*</span></label>
        <input :class="{'form-control': true, 'is-invalid': store.shipping.errors.has('city')}"
               :readonly="store.shipping.number"
               type="text"
               size="255"
               min="2"
               max="255"
               maxlength="255"
               placeholder="Enter City Name"
               id="shipping-city"
               v-model="store.shipping.city"
        >
        <span class="invalid-feedback d-block">{{ store.shipping.errors.first('city') }}</span>
      </div>
    </div>
    <div class="col-sm-6">
      <div class="form-group">
        <label for="shipping-zip">ZIP Code<span class="text-danger font-weight-bold">*</span></label>
        <input :class="{'form-control': true, 'is-invalid': store.shipping.errors.has('zipCode')}"
               :readonly="store.shipping.number"
               type="text"
               size="255"
               min="2"
               max="255"
               maxlength="255"
               placeholder="Enter City Name"
               id="shipping-zip"
               v-model="store.shipping.zipCode"
        >
        <span class="invalid-feedback d-block">{{ store.shipping.errors.first('zipCode') }}</span>
      </div>
    </div>
  </div>

  <h4 class="border-bottom pb-2 mt-4 mb-3">
    <i class="icon-map" style="margin-top: -10px"></i>
    Delivery Method
  </h4>
  <div class="row justify-content-center">
    <div class="col-12" v-if="shipOptionLabels.length > 0">
      <ul class="nav nav-pills" role="tablist" v-if="shipOptionLabels.length > 1">
        <li class="nav-item" v-for="shipOption in shipOptionLabels">
          <a :class="{'nav-link show text-capitalize' : true, 'active' : shipOption === defaultShippingOption}"
             :href="`#${slugify(shipOption)}`"
             data-toggle="tab" role="tab" aria-selected="true">
            {{ shipOption }}
          </a>
        </li>
      </ul>
      <div :class="{'tab-content': true, 'p-0 border-0' : shipOptionLabels.length === 1}">
        <div
            v-for="(methods, name) in store.shipOptions"
            :class="{'tab-pane fade': true, 'active show': name === defaultShippingOption}"
            :id="`${slugify(name)}`"
            role="tabpanel">
          <ul class="list-unstyled" style="max-height: 400px; overflow-y: auto">
            <li :class="{
              'mb-2 rounded border form-check p-3' : true,
              'border-primary': method.shipvia === store.shipping.method }"
                v-for="method in methods">
              <label class="d-flex align-items-center gap-3 mb-0 justify-content-between"
                     :for="`method-${slugify(name)}-option-${slugify(method.shipvia)}`">
                <div class="d-flex align-items-center gap-3">
                  <input type="radio"
                         style="width: 1.25rem; height: 1.25rem"
                         name="method"
                         @change="store.selectShippingMethod(name, method)"
                         v-model="store.shipping.method"
                         :value="method.shipvia"
                         :id="`method-${slugify(name)}-option-${slugify(method.shipvia)}`"/>
                  <div>
                    <p class="font-weight-bold">
                      <strong>{{ method.name }}</strong>
                    </p>
                    <p class="mb-0 text-muted" v-if="method.date !== ''">
                      Estimated Delivery Date: {{ method.date }}
                    </p>
                  </div>
                </div>

                <div v-if="parseFloat(method.amount) !== 0" class="font-weight-bold">
                  {{ store.priceFormatter(method.amount) }}
                </div>
              </label>
            </li>
          </ul>
        </div>
      </div>
      <span class="invalid-feedback d-block">{{ store.shipping.errors.first('method') }}</span>
      <div class="form-group" v-if="store.hasShipInstruction">
        <label for="shipping-ship-ins">
          Shipping Instructions
        </label>
        <textarea :class="{'form-control': true, 'is-invalid': store.shipping.errors.has('instructions')}"
                  placeholder="Write your shipping instructions"
                  id="shipping-ship-ins"
                  v-model="store.shipping.instructions"></textarea>
        <span class="invalid-feedback d-block">
              {{ store.shipping.errors.first('instructions') }}
            </span>
      </div>
    </div>
    <no-ship-options v-else/>
  </div>
  <!--  <div class="form-group" v-if="store.selectedShippingMethod?.frttermscd === 'C'">-->
  <!--    <label for="freight-account-number">Freight Account Number <span-->
  <!--        class="text-danger font-weight-bold">*</span></label>-->
  <!--    <input type="text" class="form-control" id="freight-account-number"-->
  <!--           placeholder="Enter your freight account number" v-model="store.freightAccountNumber">-->
  <!--  </div>-->
</template>
