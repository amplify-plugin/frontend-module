<script setup>
import {onMounted} from 'vue';
import {useCheckoutStore} from '../composables/useCheckoutStore';
import SummarySidebar from "./components/summary.vue";

const store = useCheckoutStore();

const paymentLabels = {
  credit_card: 'Credit Card',
  paypal: 'PayPal',
  reward_points: 'Reward Points',
  on_account: 'On Account',
  ach: 'ACH',
};

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
            <label for="po-number">
              PO Number
            </label>
            <input class="form-control"
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
</template>
