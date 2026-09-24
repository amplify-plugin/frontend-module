<script setup>
import {useCheckoutStore} from '../../composables/useCheckoutStore';

const store = useCheckoutStore();

function formatAddress(customer) {
  return [
    customer?.addressLine1 ?? '',
    customer?.addressLine2 ?? '',
    customer?.addressLine3 ?? '',
    customer?.city ?? '',
    customer?.state ?? '',
    customer?.zipCode ?? '',
    customer?.country ?? '',
  ].filter((part) => part && part !== '').join(', ');
}

</script>

<template>
  <h4 class="border-bottom pb-2 mb-4">
    <i class="icon-pie-graph" style="margin-top: -10px"></i>
    Order Summary
  </h4>

  <section class="widget widget-order-summary">
    <table class="table">
      <tbody>
      <tr v-if="store.review.sub_total != null">
        <td>Cart Subtotal:</td>
        <td class="text-medium">{{ store.priceFormatter(store.review.sub_total) }}</td>
      </tr>
      <tr v-if="store.review.ship_charge != null">
        <td>Shipping:</td>
        <td class="text-medium">{{ store.priceFormatter(store.review.ship_charge) }}</td>
      </tr>
      <tr v-if="store.review.hazmat_charge != null">
        <td>Hazmat Change:</td>
        <td class="text-medium">{{ store.priceFormatter(store.review.hazmat_charge) }}</td>
      </tr>
      <tr v-if="store.review.wire_transfer_fee != null">
        <td>Wire Transfer Fee:</td>
        <td class="text-medium">{{ store.priceFormatter(store.review.wire_transfer_fee) }}</td>
      </tr>
      <tr v-if="store.review.tax_amount != null">
        <td>Estimated tax:</td>
        <td class="text-medium">{{ store.priceFormatter(store.review.tax_amount) }}</td>
      </tr>
      <tr v-if="store.review.total != null">
        <td></td>
        <td class="text-lg text-medium">{{ store.priceFormatter(store.review.total) }}</td>
      </tr>
      </tbody>
    </table>
  </section>

  <section v-if="store.shipping">
    <h5 class="border-bottom pb-2 my-3">Shipping To:</h5>
    <ul class="list-unstyled">
      <li>
        <span>Method: </span>
        <b>{{ store.selectedShippingMethod?.name ?? 'N/A' }}</b>
      </li>
      <li>
        <span>Name: </span>
        <b>{{ store.shipping.name ?? 'N/A' }}</b>
      </li>
      <li>
        <span>Address: </span>
        <b>{{ formatAddress(store.shipping) }}</b>
      </li>
      <li v-if="store.shipping.phone">
        <span>Phone: </span>
        <b>{{ store.shipping.phone ?? 'N/A' }}</b>
      </li>
    </ul>
  </section>

  <section v-if="store.paymentMethod">
    <h5 class="border-bottom pb-2 my-3">Payment Terms:</h5>
    <ul class="list-unstyled">
      <li><span class="text-muted">Name: </span>{{ store.account.company ?? 'N/A' }}</li>
      <li><span class="text-muted">Address: </span>{{ formatAddress(store.account) }}</li>
      <li><span class="text-muted">Phone: </span>{{ store.account.phone ?? 'N/A' }}</li>
      <li><span class="text-muted">Terms: </span>{{ 'COD' }}</li>
    </ul>
  </section>
</template>
