<script setup>
import {computed} from 'vue';
import {useCheckoutStore} from './composables/useCheckoutStore';

defineProps({
  active: {
    type: String,
    default: 'account',
  },
});

const store = useCheckoutStore();

function onStepClick(component) {
  store.goToStep(component);
}

/*
 * The reference (amp-theme) implementation writes the steps in reverse
 * document order (4 -> 1) and lays them out with `float: right`. The store
 * keeps the canonical logical order (Account -> Shipping -> Review ->
 * Payment); only the rendering list follows the reference convention.
 */
const displaySteps = computed(() => [...store.orderedSteps].reverse());

function canonicalIndex(item) {
  return store.orderedSteps.indexOf(item);
}

function isActive(item) {
  return store.activeStep === item.component;
}

function isCompleted(item) {
  return store.currentIndex > canonicalIndex(item);
}

function stepNumber(item) {
  return canonicalIndex(item) + 1;
}
</script>

<template>
  <div class="checkout-steps">
    <a v-for="(item, i) in displaySteps"
       :key="item.id + i"
       :href="`#step-${item.component}`"
       :class="{ 'active': isActive(item), 'completed': isCompleted(item) }"
       @click.prevent="onStepClick(item.component)">
      <span class="step-indicator icon-circle-check" v-if="isCompleted(item)"></span>
      <span class="angle" v-if="i > 0"></span>
      {{ stepNumber(item) }}. {{ item.label }}
    </a>
  </div>
</template>

<style scoped>
/*
 * Original amp-theme `.checkout-steps` stylesheet, reproduced verbatim from
 * the reference pages' styles.min.css (float:right + reversed DOM order is
 * the reference technique). Scoped attribute selectors give these rules
 * precedence over the host theme's own variant.
 */
.checkout-steps {
  margin-bottom: 40px;
}

.checkout-steps:after {
  clear: both;
  content: "";
  display: block;
}

.checkout-steps > a {
  background-color: #fff;
  border-bottom: 1px solid #e1e7ec;
  border-top: 1px solid #e1e7ec;
  color: #606975;
  display: block;
  float: right;
  font-size: 14px;
  font-weight: 500;
  height: 55px;
  line-height: 53px;
  position: relative;
  text-align: center;
  text-decoration: none;
  transition: color .3s;
  width: 25%;
}

.checkout-steps > a > .angle {
  background-color: #fff;
  display: block;
  height: 53px;
  position: absolute;
  right: -13px;
  top: 0;
  width: 27px;
}

.checkout-steps > a > .angle:after,
.checkout-steps > a > .angle:before {
  border: solid transparent;
  content: "";
  height: 0;
  left: 0;
  pointer-events: none;
  position: absolute;
  top: 0;
  width: 0;
}

.checkout-steps > a > .angle:after {
  border-color: transparent transparent transparent #fff;
  border-width: 26px;
}

.checkout-steps > a > .angle:before {
  border-color: transparent transparent transparent #d8e0e6;
  border-width: 27px;
  margin-top: -1px;
}

.checkout-steps > a:hover {
  color: #0da9ef;
}

.checkout-steps > a.active {
  background-color: #0da9ef;
  color: #fff;
  cursor: default;
  pointer-events: none;
}

.checkout-steps > a.active > .angle:after {
  border-left-color: #0da9ef;
}

.checkout-steps > a.active + a > .angle {
  background-color: #0da9ef;
}

.checkout-steps > a.completed > .step-indicator {
  border-radius: 50%;
  color: #43d9a3;
  display: inline-block;
  font-size: 18px;
  line-height: 20px;
  margin-right: 7px;
  margin-top: -5px;
  text-align: center;
  vertical-align: middle;
}

.checkout-steps > a.completed:hover {
  color: #606975;
}

.checkout-steps > a:first-child {
  border-bottom-right-radius: 7px;
  border-right: 1px solid #e1e7ec;
  border-top-right-radius: 7px;
}

.checkout-steps > a:last-child {
  border-bottom-left-radius: 7px;
  border-left: 1px solid #e1e7ec;
  border-top-left-radius: 7px;
}

@media (max-width: 576px) {
  .checkout-steps > a {
    border: 1px solid #e1e7ec;
    border-radius: 7px;
    float: none;
    margin-bottom: 10px;
    width: 100%;
  }

  .checkout-steps > a > .angle {
    display: none;
  }
}
</style>
