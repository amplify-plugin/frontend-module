<script>
export default {
  name: "steps",
  props: {
    items: {
      type: Array,
      required: true
    },
    active: {
      type: String,
      default: 'account'
    }
  },
  computed: {
    lastStep() {

    }
  }
}
</script>

<template>
  <div class="checkout-steps">
    <a v-for="(item, index) in items"
       :key="index"
       :href="`#step-${item.component}`"
       :class="{'active': active === item.component}">
      <span class="angle" v-if="index > (items.length - 1)"></span>
      {{ items.length - index }}. {{ item.label }}
    </a>
  </div>
</template>

<style scoped>

</style>