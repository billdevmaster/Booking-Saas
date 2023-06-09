<template>
  <div class="payment-simple">
    <StripeElements
      :stripe-key="stripeKey"
      :instance-options="instanceOptions"
      :elements-options="elementsOptions"
      #default="{ elements }" 
      ref="elms"
    >
      <StripeElement
        type="card"
        :elements="elements"
        :options="cardOptions"
        ref="card"
      />
    </StripeElements>
    <button @click="pay" type="button">Pay</button>
  </div>
</template>

<script>
import { StripeElements, StripeElement } from 'vue-stripe-elements-plus'
import constants from '../../constants';

export default {
  name: 'PaymentSimple',

  components: {
    StripeElements,
    StripeElement
  },

  data () {
    return {
      stripeKey: constants.STRIPE_PUBLISHABLE_KEY, // test key, don't hardcode
      instanceOptions: {
        // https://stripe.com/docs/js/initializing#init_stripe_js-options
      },
      elementsOptions: {
        // https://stripe.com/docs/js/elements_object/create#stripe_elements-options
      },
      cardOptions: {
        // reactive
        // remember about Vue 2 reactivity limitations when dealing with options
        value: {
          postalCode: ''
        }
        // https://stripe.com/docs/stripe.js#element-options
      }
    }
  },

  methods: {
    pay () {
      // ref in template
      const groupComponent = this.$refs.elms
      const cardComponent = this.$refs.card
      // Get stripe element
      const cardElement = cardComponent.stripeElement

      // Access instance methods, e.g. createToken()
      groupComponent.instance.createToken(cardElement).then(result => {
        // Handle result.error or result.token
      })
    }
  }
}
</script>