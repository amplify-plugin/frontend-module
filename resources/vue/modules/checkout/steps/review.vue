<script setup>
import {onMounted} from 'vue';
import {useCheckoutStore} from '../composables/useCheckoutStore';
import SummarySidebar from "../summary.vue";

const store = useCheckoutStore();

const paymentLabels = {
  credit_card: 'Credit Card',
  paypal: 'PayPal',
  reward_points: 'Reward Points',
  on_account: 'On Account',
  ach: 'ACH',
};

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

onMounted(() => {
  window.Amplify.loadCartSummary();
  // store.loadCartItems();
});

</script>

<template>
  <div class="row">
    <div class="col-sm-9">
      <div class="row">
        <div class="col-12">
          <h4 class="border-bottom pb-2 mb-4">
            <i class="icon-bag" style="margin-top: -10px"></i>
            Review Your Order
          </h4>
          <div id="cart-summary">
            <div class="table-responsive shopping-cart mb-2">
              <table class="table table-hover table-striped">
                <thead>
                <tr>
                  <th class="py-2 text-center">Product</th>
                  <th class="py-2 text-center" width="100">Quantity</th>
                  <th class="py-2 text-center">Price</th>
                  <th class="py-2 text-center" width="100">Total</th>
                </tr>
                </thead>
                <tbody id="cart-item-summary"></tbody>
                <tfoot>
                <tr>
                  <td colspan="4" class="text-right">
                    Subtotal: <span class="font-weight-bold" id="order-subtotal">$0.00</span>
                  </td>
                </tr>
                </tfoot>
              </table>
            </div>
          </div>
          <!--          <div class="table-responsive shopping-cart">-->
          <!--            <table class="table">-->
          <!--              <thead>-->
          <!--              <tr>-->
          <!--                <th>Product Name</th>-->
          <!--                <th class="text-center">Subtotal</th>-->
          <!--                <th></th>-->
          <!--              </tr>-->
          <!--              </thead>-->
          <!--              <tbody>-->
          <!--              <tr v-for="item in store.cartItems" :key="item.id ?? item.product_code ?? item.product_name">-->
          <!--                <td>-->
          <!--                  <div class="product-item">-->
          <!--                    <a class="product-thumb" :href="item.url || '#'" v-if="item.product_image">-->
          <!--                      <img :src="item.product_image" alt="Product">-->
          <!--                    </a>-->
          <!--                    <div class="product-info">-->
          <!--                      <h4 class="product-title"><a :href="item.url || '#'" @click.prevent>{{-->
          <!--                          item.product_name ?? item.name ?? item.product_code-->
          <!--                        }}<small>x {{ item.qty }}</small></a></h4>-->
          <!--                      <span v-if="item.uom" class="text-muted text-sm">{{ item.uom }}</span>-->
          <!--                    </div>-->
          <!--                  </div>-->
          <!--                </td>-->
          <!--                <td class="text-center text-lg text-medium">-->
          <!--                  {{ store.priceFormatter(item.subtotal ?? (item.unit_price ?? item.price ?? 0) * item.qty) }}-->
          <!--                </td>-->
          <!--                <td class="text-center"><a class="btn btn-outline-primary btn-sm" href="#" @click.prevent>Edit</a></td>-->
          <!--              </tr>-->
          <!--              </tbody>-->
          <!--            </table>-->
          <!--          </div>-->
          <!--          <div class="shopping-cart-footer">-->
          <!--            <div class="column"></div>-->
          <!--            <div class="column text-lg">Subtotal: <span class="text-medium">{{-->
          <!--                store.priceFormatter(store.orderSubtotal)-->
          <!--              }}</span></div>-->
          <!--          </div>-->
        </div>
        <div class="col-12">
          <h4 class="border-bottom pb-2 my-4">
            <i class="icon-archive" style="margin-top: -10px"></i>
            Additional Information
          </h4>
          <div class="form-group">
            <label for="po-number">PO Number
              <span data-toggle="popover" data-placement="top"
                    data-trigger="hover" data-orginal-title="Purchase Order Number"
                    data-content="PO numbers are an unique number given to customer beforehand."
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     class="feather feather-info">
                  <circle cx="12" cy="12" r="10"></circle>
                  <line x1="12" y1="16" x2="12" y2="12"></line>
                  <line x1="12" y1="8" x2="12.01" y2="8"></line>
                </svg>
              </span>
            </label>
            <input class="form-control"
                   :readonly="readOnly"
                   type="text"
                   size="255"
                   min="2"
                   max="255"
                   maxlength="255"
                   placeholder="Enter Contact Name"
                   id="po-number" v-model="store.poNumber">
            <span class="invalid-feedback d-block" id="po-number-error"></span>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-3">
      <summary-sidebar/>
    </div>
  </div>
  <div class="row padding-top-1x mt-3">
    <div class="col-sm-6">
      <h5>Shipping to:</h5>
      <ul class="list-unstyled">
        <li><span class="text-muted">Namw: </span>{{ store.shipping.ShipToName ?? 'N/A' }}</li>
        <li><span class="text-muted">Address: </span>{{ formatAddress(store.shipping) }}</li>
        <li><span class="text-muted">Phone: </span>{{ store.shipping.ShipToPhoneNumber ?? 'N/A' }}</li>
      </ul>
    </div>
    <div class="col-sm-6">
      <h5>Payment method:</h5>
      <ul class="list-unstyled">
        <li><span class="text-muted">Method:</span>{{ paymentLabels[store.paymentMethod] || 'On Account' }}</li>
      </ul>
    </div>
  </div>
</template>
