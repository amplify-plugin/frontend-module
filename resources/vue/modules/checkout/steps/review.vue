<script setup>
import { onMounted } from 'vue';
import { useCheckoutStore } from '../composables/useCheckoutStore';

const store = useCheckoutStore();

onMounted(() => {
  store.loadCartItems();
});

const paymentLabels = {
  credit_card: 'Credit Card',
  paypal: 'PayPal',
  reward_points: 'Reward Points',
  on_account: 'On Account',
  ach: 'ACH',
};

function formatAddress(customer) {
  return [
    customer?.CustomerAddress1 ?? '',
    customer?.CustomerAddress2 ?? '',
    customer?.CustomerAddress3 ?? '',
    customer?.CustomerCity ?? '',
    customer?.CustomerState ?? '',
    customer?.CustomerZipCode ?? '',
    customer?.CustomerCountry ?? '',
  ].filter((part) => part && part.length > 0).join(', ');
}
</script>

<template>
  <h4>Review Your Order</h4>
  <hr class="padding-bottom-1x">
  <div class="table-responsive shopping-cart">
    <table class="table">
      <thead>
        <tr>
          <th>Product Name</th>
          <th class="text-center">Subtotal</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="item in store.cartItems" :key="item.id ?? item.product_code ?? item.product_name">
          <td>
            <div class="product-item">
              <a class="product-thumb" :href="item.url || '#'" v-if="item.product_image">
                <img :src="item.product_image" alt="Product">
              </a>
              <div class="product-info">
                <h4 class="product-title"><a :href="item.url || '#'" @click.prevent>{{ item.product_name ?? item.name ?? item.product_code }}<small>x {{ item.qty }}</small></a></h4>
                <span v-if="item.uom" class="text-muted text-sm">{{ item.uom }}</span>
              </div>
            </div>
          </td>
          <td class="text-center text-lg text-medium">
            {{ store.priceFormatter(item.subtotal ?? (item.unit_price ?? item.price ?? 0) * item.qty) }}
          </td>
          <td class="text-center"><a class="btn btn-outline-primary btn-sm" href="#" @click.prevent>Edit</a></td>
        </tr>
      </tbody>
    </table>
  </div>
  <div class="shopping-cart-footer">
    <div class="column"></div>
    <div class="column text-lg">Subtotal: <span class="text-medium">{{ store.priceFormatter(store.orderSubtotal) }}</span></div>
  </div>
  <div class="row padding-top-1x mt-3">
    <div class="col-sm-6">
      <h5>Shipping to:</h5>
      <ul class="list-unstyled">
        <li><span class="text-muted">Client:</span>{{ store.customer?.CustomerName ?? 'N/A' }}</li>
        <li><span class="text-muted">Address:</span>{{ formatAddress(store.customer) }}</li>
        <li><span class="text-muted">Phone:</span>{{ store.contact?.phone ?? 'N/A' }}</li>
      </ul>
    </div>
    <div class="col-sm-6">
      <h5>Payment method:</h5>
      <ul class="list-unstyled">
        <li><span class="text-muted">Method:</span>{{ paymentLabels[store.paymentMethod] || 'On Account' }}</li>
      </ul>
    </div>
  </div>
  <div class="alert alert-danger mt-2" role="alert" v-if="store.validationError">
    {{ store.validationError }}
  </div>
</template>

<style scoped>

</style>
