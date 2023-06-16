<template>
  <CCol col="12" lg="6">
    <CCard no-header>
      <CCardBody>
        <h3>
          Loo plaane
        </h3>
        <CAlert
            :show.sync="dismissCountDown"
            :color.sync="alertType"
            fade
          >
          ({{dismissCountDown}}) {{ message }}
        </CAlert>

        <CInput label="Plaani nimi" type="text" placeholder="name" v-model="planData.name"></CInput>
        <CInput label="Plaani kirjeldus" type="text" placeholder="description" v-model="planData.description"></CInput>
        <CSelect
          label="Arveldusintervall"
          vertical
          :options="billingIntervals"
          placeholder="Please select"
          :value.sync="planData.billing_interval"
        />
        <CInput label="Plaani hind" type="number" placeholder="price" step="0.01" v-model="planData.price"></CInput>

        <CButton color="primary" @click="update()">Loo</CButton>
        <CButton color="primary" @click="goBack">tagasi</CButton>

      </CCardBody>
    </CCard>
  </CCol>
  
</template>
<script>
import axios from 'axios';
export default {
  name: 'PlanCreate',
  
  data: () => {
    return {
      planData: {
        name: "",
        description: "",
        billing_interval: "",
        price: "",
      },
      billingIntervals: ["Month", "Year"],
      message: '',
      alertType: 'primary',
      dismissSecs: 7,
      dismissCountDown: 0,
      showDismissibleAlert: false,
    }
  },
  methods: {
    goBack() {
      this.$router.go(-1)
      // this.$router.replace({path: '/users'})
    },
    update() {
      let self = this;
      axios.post(this.$apiAdress + '/api/plans/create?token=' + localStorage.getItem("api_token"),
        { 'plan_data': self.planData, id: self.$route.params.id }
      )
      .then(function (response) { 
        self.alertType = 'primary';
        self.message = 'Successfully updated plan';
        self.showAlert();
      })
      .catch(function (error) {
        if (error.response.status === 503) {
          self.alertType = 'danger';
          self.message = error.response.data.message;
          self.showAlert();          
        }
      })
    },
    countDownChanged (dismissCountDown) {
      this.dismissCountDown = dismissCountDown
    },
    showAlert () {
      this.dismissCountDown = this.dismissSecs
    },
  },
  mounted: function () {
    let self = this;
    axios.get(  this.$apiAdress + '/api/plans/get_plan?token=' + localStorage.getItem("api_token") + '&id=' + self.$route.params.id)
    .then(function (response) {
      self.planData = response.data.plan;
    }).catch(function (error) {
        console.log(error);
        // self.$router.push({ path: '/login' });
    });
  }
}
</script>