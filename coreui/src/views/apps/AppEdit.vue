<template>
  <CCol col="12" lg="6">
    <CCard no-header>
      <CCardBody>
        <h3>
          Edit App
        </h3>
        <CAlert
            :show.sync="dismissCountDown"
            :color.sync="alertType"
            fade
          >
          ({{dismissCountDown}}) {{ message }}
        </CAlert>
        <CInput label="App Name" type="text" placeholder="Title" v-model="appData.APP_NAME"></CInput>
        <CInput label="Folder Name" type="text" placeholder="foler name" v-model="appData.folder_name"></CInput>
        <CInput label="Database" type="text" placeholder="Database" v-model="appData.DB_DATABASE"></CInput>
        <CInput label="Username" type="text" placeholder="Batabase username" v-model="appData.DB_USERNAME"></CInput>
        <CInput label="Password" type="text" placeholder="Database password" v-model="appData.DB_PASSWORD"></CInput>

        <CButton color="primary" @click="update()">Update</CButton>
        <CButton color="primary" @click="goBack">Back</CButton>

      </CCardBody>
    </CCard>
  </CCol>
  
</template>
<script>
import axios from 'axios';
export default {
  name: 'AppEdit',
  data: () => {
    return {
      appData: {
        APP_NAME: "",
        folder_name: "",
        DB_DATABASE: "",
        DB_USERNAME: "",
        DB_PASSWORD: ""
      },
      message: '',
      alertType: 'primary',
      dismissSecs: 7,
      dismissCountDown: 0,
      showDismissibleAlert: false
    }
  },
  methods: {
    goBack() {
      this.$router.go(-1)
      // this.$router.replace({path: '/users'})
    },
    update() {
      let self = this;
      axios.post(this.$apiAdress + '/api/apps/update?token=' + localStorage.getItem("api_token"),
        { 'app_data': self.appData, id: self.$route.params.id }
      )
      .then(function (response) { 
        self.alertType = 'primary';
        self.message = 'Successfully created note. Please visit https://bookid.ee/' + self.appData.folder_name;
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
  mounted: function(){
    let self = this;
    axios.get(  this.$apiAdress + '/api/apps/get_app?token=' + localStorage.getItem("api_token") + '&id=' + self.$route.params.id)
    .then(function (response) {
      console.log(response)
      self.appData = response.data.app;
    }).catch(function (error) {
        console.log(error);
        // self.$router.push({ path: '/login' });
    });
  }
}
</script>