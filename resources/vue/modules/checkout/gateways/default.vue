<script setup>

</script>

<template>
  <div class="accordion" id="accordion" role="tablist">
    <div class="card">
      <div class="card-header" role="tab">
        <h6><a href="#card" :class="{ collapsed: openPanel !== 'card' }"
               @click.prevent="toggle('card', 'credit_card')"><i class="icon-columns"></i>Pay with Credit Card</a></h6>
      </div>
      <div class="collapse" :class="{ show: openPanel === 'card' }" id="card" role="tabpanel">
        <div class="card-body">
          <p>We accept following credit cards:&nbsp;<img class="d-inline-block align-middle"
                                                         src="/vendor/widget/img/credit-cards.png" style="width: 120px;" alt="Credit Cards"></p>
        </div>
      </div>
    </div>
    <div class="card">
      <div class="card-header" role="tab">
        <h6><a href="#paypal" :class="{ collapsed: openPanel !== 'paypal' }"
               @click.prevent="toggle('paypal', 'paypal')"><i class="socicon-paypal"></i>Pay with PayPal</a></h6>
      </div>
      <div class="collapse" :class="{ show: openPanel === 'paypal' }" id="paypal" role="tabpanel">
        <div class="card-body">
          <p>PayPal - the safer, easier way to pay</p>
          <form class="row" method="post" @submit.prevent>
            <div class="col-sm-6">
              <div class="form-group">
                <input class="form-control" type="email" placeholder="E-mail" required>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-group">
                <input class="form-control" type="password" placeholder="Password" required>
              </div>
            </div>
            <div class="col-12">
              <div class="d-flex flex-wrap justify-content-between align-items-center">
                <a class="navi-link" href="#" @click.prevent>Forgot password?</a>
                <button class="btn btn-outline-primary margin-top-none" type="submit">Log In</button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
    <div class="card">
      <div class="card-header" role="tab">
        <h6><a href="#points" :class="{ collapsed: openPanel !== 'points' }"
               @click.prevent="toggle('points', 'reward_points')"><i class="icon-medal"></i>Redeem Reward Points</a></h6>
      </div>
      <div class="collapse" :class="{ show: openPanel === 'points' }" id="points" role="tabpanel">
        <div class="card-body">
          <p>You currently have<span class="text-medium"> 290</span> Reward Points to spend.</p>
          <div class="custom-control custom-checkbox d-block">
            <input class="custom-control-input" type="checkbox" id="use_points">
            <label class="custom-control-label" for="use_points">Use my Reward Points to pay for this order.</label>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>

</style>