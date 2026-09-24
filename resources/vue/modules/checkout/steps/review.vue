<script setup>
import {useCheckoutStore} from '../composables/useCheckoutStore';
import SummarySidebar from "./components/summary.vue";
import {onBeforeUnmount, onMounted, ref} from "vue";
import axios from 'axios';

const store = useCheckoutStore();

const paymentLabels = {
  credit_card: 'Credit Card',
  paypal: 'PayPal',
  reward_points: 'Reward Points',
  on_account: 'On Account',
  ach: 'ACH',
};

const list = ref(null);
const loadMoreTrigger = ref(null);

const page = ref(0);
const perPage = ref(5);
const count = ref(0);

const loading = ref(false);
const hasMore = ref(true);


let observer = null;

const loadCartItems = async () => {

  if (loading.value || !hasMore.value) {
    return;
  }

  loading.value = true;

  try {

    const nextPage = page.value + 1;

    const response = await axios.get(`/carts/items`, {
      params: {
        page: nextPage,
        per_page: perPage.value,
      }
    });

    const data = response.data;

    if (!data.success) {
      return;
    }

    loadMoreTrigger.value.insertAdjacentHTML(
        'beforebegin',
        data.html
    );

    hasMore.value = data.current < data.total;

    page.value = data.current;
    count.value = count.value + data.count;

  } catch (error) {
    console.log(error);
  } finally {
    loading.value = false;
  }
}
const setupObserver = () => {
  observer = new IntersectionObserver(
      ([entry]) => {
        if (entry.isIntersecting) {
          loadCartItems()
        }
      },
      {
        root: list.value,
        rootMargin: '100px 0px',
        threshold: 0
      }
  );

  if (loadMoreTrigger.value) {
    observer.observe(loadMoreTrigger.value)
  }
}

onMounted(async () => {
  setupObserver()
})

onBeforeUnmount(() => {
  if (observer) {
    observer.disconnect()
  }
})

</script>

<template>
  <div class="row">
    <div class="col-sm-9">
      <h4 class="border-bottom pb-2 mb-4">
        <i class="icon-bag" style="margin-top: -10px"></i>
        Review Your Order
      </h4>
      <div class="row">
        <div class="col-12">
          <ul ref="list" class="list-unstyled border-bottom" style="max-height: 300px; overflow-y: auto;">
            <li ref="loadMoreTrigger" class="text-center py-3">
              <div v-if="loading" class="spinner-border spinner-border-sm" role="status">
                <span class="sr-only">Loading...</span>
              </div>
              <span v-else-if="!hasMore" class="text-muted">
                No more items
              </span>
            </li>
          </ul>
          <h5 class="text-right font-weight-bold pr-4">
            <span class="text-muted">Subtotal: </span>
            {{ store.priceFormatter(store.review.sub_total) }}
          </h5>
        </div>
        <div class="col-12">
          <h4 class="border-bottom pb-2 my-4">
            <i class="icon-archive" style="margin-top: -10px"></i>
            Additional Information
          </h4>
          <div class="form-group">
            <label for="review-order-note">
              Order Comments
            </label>
            <textarea :class="{'form-control': true, 'is-invalid': store.review.errors.has('notes')}"
                      size="255"
                      maxlength="255"
                      placeholder="Enter Order Notes"
                      id="review-order-note"
                      v-model="store.review.notes"></textarea>
            <span class="invalid-feedback d-block">
              {{ store.review.errors.first('notes') }}
            </span>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-3">
      <summary-sidebar/>
    </div>
  </div>
</template>

<style>
.x-product-price span.standard {
  font-size: 100% !important;
  margin-top: 0 !important;
}
</style>