<script setup>
import { computed } from 'vue';
import { useCheckoutStore } from '../composables/useCheckoutStore';

const store = useCheckoutStore();

const methods = computed(() => {
  const freightRate = store.shipOptions?.FreightRate ?? {};
  const list = [];
  for (const [group, groupMethods] of Object.entries(freightRate)) {
    for (const methodObj of groupMethods) {
      const key = Object.keys(methodObj)[0];
      list.push({ group, ...methodObj[key] });
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
</script>

<template>
  <h4 class="border-bottom pb-2 mb-4">Choose Shipping Method</h4>
  <div class="table-responsive">
    <table class="table table-hover">
      <thead class="thead-default">
        <tr>
          <th></th>
          <th>Shipping method</th>
          <th>Delivery time</th>
          <th>Handling fee</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(method, index) in methods" :key="method.shipvia + index" @click="select(method, method.group)">
          <td class="align-middle">
            <div class="custom-control custom-radio mb-0">
              <input class="custom-control-input" type="radio" name="shipping-method"
                     :id="`shipping-method-${index}`" :value="method"
                     :checked="isSelected(method)" @change="select(method, method.group)">
              <label class="custom-control-label" :for="`shipping-method-${index}`"></label>
            </div>
          </td>
          <td class="align-middle">
            <span class="text-medium">{{ method.name || method.shipvia }}</span><br>
            <span class="text-muted text-sm">{{ method.group }}</span>
          </td>
          <td class="align-middle">&mdash;</td>
          <td class="align-middle">{{ store.priceFormatter(method.amount) }}</td>
        </tr>
      </tbody>
    </table>
  </div>
  <div class="form-group" v-if="store.selectedShippingMethod?.frttermscd === 'C'">
    <label for="freight-account-number">Freight Account Number <span class="text-danger font-weight-bold">*</span></label>
    <input type="text" class="form-control" id="freight-account-number"
           placeholder="Enter your freight account number" v-model="store.freightAccountNumber">
  </div>
  <div class="alert alert-danger mt-2" role="alert" v-if="store.validationError">
    {{ store.validationError }}
  </div>
</template>
