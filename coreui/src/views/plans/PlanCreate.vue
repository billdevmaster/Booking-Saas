<template>
  <CCol col="12" lg="6">
    <CCard no-header>
      <CCardBody>
        <h3>
          Create Plans
        </h3>
        <CAlert
            :show.sync="dismissCountDown"
            :color.sync="alertType"
            fade
          >
          ({{dismissCountDown}}) {{ message }}
        </CAlert>

        <CInput label="Plan Name" type="text" placeholder="name" v-model="planData.name"></CInput>
        <CInput label="Plan Description" type="text" placeholder="description" v-model="planData.description"></CInput>
        <CInput label="Plan Duration(days)" type="number" placeholder="duration" v-model="planData.duration"></CInput>
        <CInput label="Plan Price" type="number" placeholder="price" step="0.01" v-model="planData.price"></CInput>

        <CButton color="primary" @click="create()">Create</CButton>
        <CButton color="primary" @click="goBack">Back</CButton>

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
        duration: "",
        price: "",
      },
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
    create() {
      let self = this;
      axios.post(this.$apiAdress + '/api/plans/create?token=' + localStorage.getItem("api_token"),
        { 'plan_data': self.planData, id: 0 }
      )
      .then(function (response) { 
        self.alertType = 'primary';
        self.message = 'Successfully created plan';
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
  }
}
</script>