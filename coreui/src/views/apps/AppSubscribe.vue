<template>
  <CRow>
    <CCol col="8">
      <div class="card">
        <div class="card-header">
          Tellimuse üksikasjad
        </div>
        <div class="card-body">
          <CAlert
            :show.sync="dismissCountDown"
            :color.sync="alertType"
            fade
          >
            ({{dismissCountDown}}) {{ message }}
          </CAlert>
          <dl class="row">
            <dt class="col-sm-3">Lubatud lõppkuupäev</dt>
            <dd class="col-sm-9">
              {{ appEnd }}
            </dd>
            <dt class="col-sm-3">Nimi</dt>
            <dd class="col-sm-9">
              {{ plan.name }}
            </dd>
            <dt class="col-sm-3">Kirjeldus</dt>
            <dd class="col-sm-9">
              {{ plan.description }}
            </dd>
            <dt class="col-sm-3">Plaan</dt>
            <dd class="col-sm-9">
              {{ plan.price }}$ / {{ plan.billing_interval }}
            </dd>
            <dt class="col-sm-3">{{ plan.billing_interval }}s</dt>
            <dd class="col-sm-9">
              <CInput class="mb-0" type="number" v-model="payMonths" @change="onMonthsChange($event)" />
            </dd>
            <dt class="col-sm-3">Makse summa</dt>
            <dd class="col-sm-9">
             {{ plan.price * payMonths }} $
            </dd>
            <dt class="col-sm-3">Periood</dt>
            <dd class="col-sm-9">
              From {{ formatDate(durationStart) }} To {{ formatDate(durationEnd) }}
            </dd>
          </dl>
          
          <div class="payment-simple mt-3">
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
            
            <CButton color="primary" class="mt-3" @click="pay" type="button">Maksma</CButton>
          </div>
        </div>
      </div>

    </CCol>
  </CRow>
</template>

<script>
import { StripeElements, StripeElement } from 'vue-stripe-elements-plus'
import constants from '../../constants'
import axios from 'axios'

export default {
  name: 'AppSubscribe',

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
      },
      plan: {},
      payMonths: 1,
      appEnd: "",
      durationStart: new Date(),
      durationEnd: new Date(),
      message: '',
      alertType: 'primary',
      dismissSecs: 7,
      dismissCountDown: 0,
      showDismissibleAlert: false,
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
        this.processPayment(result.token)
        // Handle result.error or result.token
      })
    },
    countDownChanged (dismissCountDown) {
      this.dismissCountDown = dismissCountDown
    },
    showAlert () {
      this.dismissCountDown = this.dismissSecs
    },
    processPayment(token) {
      let self = this;
      axios.post(this.$apiAdress + '/api/apps/process_payment?token=' + localStorage.getItem("api_token"), { stripe_token, plan_id: self.$route.params.plan_id, app_id: self.$route.params.app_id, payMonths: self.payMonths })
      .then(response => {
        if (response.data.success) {
          self.message = response.data.message;
          self.showAlert();
        } else {
          self.alertType = 'danger';
          self.message = response.data.message;
          self.showAlert();
        }
      })
      .catch(error => {
        self.alertType = 'danger';
        self.message = "Server Error";
        self.showAlert();
      });
    },
    getPlan() {
      let self = this
      axios.get(this.$apiAdress + '/api/plans/get_plan?token=' + localStorage.getItem("api_token") + '&id=' + self.$route.params.plan_id)
      .then(response => {
        self.plan = response.data.plan;
      })
      .catch(error => {
        console.log(error)
        if (error.response.status === 401) {
          self.$router.push({ path: '/login' });
        }
      })
    },
    onMonthsChange() {
      this.durationEnd = this.getEnddate(this.durationStart, this.payMonths * 1);
    },
    getEnddate(startDate, months) {
      // Create a new Date object using the start date
      var endDate = new Date(startDate);

      // Add the specified number of months to the end date
      if (this.plan.billing_interval === 'Month') {
        endDate.setMonth(endDate.getMonth() + months);
      } else {
        endDate.setFullYear(endDate.getFullYear() + months);
      }

      // Return the end date
      return endDate;
    },
    formatDate(date) {
      let formattedDate = date.getFullYear() + '-' + (date.getMonth() + 1).toString().padStart(2, '0') + '-' + date.getDate().toString().padStart(2, '0');
      return formattedDate;
    },
    getAppEnddate() {
      axios.get(this.$apiAdress + '/api/apps/get_app_end_date?token=' + localStorage.getItem("api_token") + '&app_id=' + this.$route.params.app_id)
      .then(response => {
        if (response.data.end_date) {
          this.appEnd = response.data.end_date;
          this.durationStart = new Date(this.appEnd);
        } else {
          this.appEnd = 'Not Subscribed';
        }
      })
      .catch(error => {
        console.error(error);
      })
    }
  },
  watch: {
    durationStart(newVal, oldVal) {
      this.durationEnd = this.getEnddate(newVal, this.payMonths * 1);
    }
  },
  mounted: function () {
    this.getPlan();
    this.getAppEnddate();
    this.durationEnd = this.getEnddate(this.durationStart, 1);
  }
}
</script>
