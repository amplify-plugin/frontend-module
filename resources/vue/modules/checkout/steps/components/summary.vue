<script setup>
import {useCheckoutStore} from '../../composables/useCheckoutStore';

const store = useCheckoutStore();

function formatAddress(customer) {
  return [
    customer?.ShipToAddress1 ?? '',
    customer?.ShipToAddress2 ?? '',
    customer?.ShipToAddress3 ?? '',
    customer?.ShipToCity ?? '',
    customer?.ShipToState ?? '',
    customer?.ShipToZipCode ?? '',
    customer?.ShipToCountryCode ?? '',
  ].filter((part) => part && part !== '').join(', ');
}

</script>

<template>
  <h4 class="border-bottom pb-2 mb-4">
    <i class="icon-pie-graph" style="margin-top: -10px"></i>
    Order Summary
  </h4>

  <section class="widget widget-order-summary">
    <h3 class="widget-title">Order Summary</h3>
    <table class="table">
      <tbody>
      <tr>
        <td>Cart Subtotal:</td>
        <td class="text-medium">{{ store.priceFormatter(store.orderSubtotal) }}</td>
      </tr>
      <tr>
        <td>Shipping:</td>
        <td class="text-medium">{{ store.priceFormatter(store.shippingAmount) }}</td>
      </tr>
      <tr>
        <td>Estimated tax:</td>
        <td class="text-medium">{{ store.priceFormatter(store.salesTax) }}</td>
      </tr>
      <tr>
        <td></td>
        <td class="text-lg text-medium">{{ store.priceFormatter(store.orderTotal) }}</td>
      </tr>
      </tbody>
    </table>
  </section>

  <section v-if="store.shipping">
    <h5 class="border-bottom pb-2 my-3">Shipping To:</h5>
    <ul class="list-unstyled">
      <li>
        <span>Method: </span>
        <b>{{ store.selectedShippingMethod ?? 'N/A' }}</b>
      </li>
      <li>
        <span>Name: </span>
        <b>{{ store.shipping.ShipToName ?? 'N/A' }}</b>
      </li>
      <li>
        <span>Address: </span>
        <b>{{ formatAddress(store.shipping) }}</b>
      </li>
      <li v-if="store.shipping.ShipToPhoneNumber">
        <span>Phone: </span>
        <b>{{ store.shipping.ShipToPhoneNumber ?? 'N/A' }}</b>
      </li>
    </ul>
  </section>

  <section v-if="store.paymentMethod">
    <h5 class="border-bottom pb-2 my-3">Payment Terms:</h5>
    <ul class="list-unstyled">
      <li><span class="text-muted">Name: </span>{{ store.shipping.ShipToName ?? 'N/A' }}</li>
      <li><span class="text-muted">Address: </span>{{ formatAddress(store.shipping) }}</li>
      <li><span class="text-muted">Phone: </span>{{ store.shipping.ShipToPhoneNumber ?? 'N/A' }}</li>
    </ul>
  </section>
</template>

<style scoped>
/*
 * Original amp-theme Order Summary widget styling (verbatim from the
 * reference pages' styles.min.css), overriding the host widget box variant.
 */
.widget-order-summary {
  background: #f6f7f8;
  border: 1px solid #e1e7ec;
  border-radius: 7px;
  padding: 20px;
  box-shadow: 0 1px 4px rgba(45, 62, 80, .10);
}

.widget-order-summary .widget-title {
  border-bottom: 1px solid #e1e7ec;
  color: #9da9b9;
  font-size: 14px;
  font-weight: 500;
  margin-bottom: 20px;
  padding-bottom: 12px;
  text-transform: uppercase;
}

.widget-order-summary .table {
  background: transparent;
}

.widget-order-summary .table td {
  border: 0;
  padding: 6px 0;
}

.widget-order-summary .table td:last-child {
  text-align: right;
}

.widget-order-summary .table tr:first-child > td {
  padding-top: 0;
}

.widget-order-summary .table tr:last-child > td {
  border-top: 1px solid #e1e7ec;
  padding-top: 12px;
}

.widget-order-summary .table tr:nth-last-child(2) > td {
  padding-bottom: 12px;
}
</style>
