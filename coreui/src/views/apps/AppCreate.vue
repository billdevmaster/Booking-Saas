<template>
  <CCol col="12" lg="6">
    <CCard no-header>
      <CCardBody>
        <h3>
          Looge rakendusi
        </h3>
        <CAlert
            :show.sync="dismissCountDown"
            :color.sync="alertType"
            fade
          >
          ({{dismissCountDown}}) {{ message }}
        </CAlert>
        <CInput label="Rakenduse nimi" type="text" placeholder="Title" v-model="appData.APP_NAME"></CInput>
        <CInput label="Kausta nimi" type="text" placeholder="foler name" v-model="appData.folder_name"></CInput>
        <CInput label="Andmebaas" type="text" placeholder="Database" v-model="appData.DB_DATABASE"></CInput>
        <CInput label="Kasutajanimi" type="text" placeholder="Batabase username" v-model="appData.DB_USERNAME"></CInput>
        <CInput label="Parool" type="text" placeholder="Database password" v-model="appData.DB_PASSWORD"></CInput>
        <CSelect
          v-if="roles.includes('admin')"
          label="User"
          vertical
          :options="users"
          placeholder="Please select"
          :value.sync="appData.user_id"
        />

        <CButton color="primary" @click="create()">Loo</CButton>
        <CButton color="primary" @click="goBack">tagasi</CButton>

      </CCardBody>
    </CCard>
  </CCol>
  
</template>
<script>
import axios from 'axios';
export default {
  name: 'AppCreate',
  data: () => {
    return {
      appData: {
        APP_NAME: "",
        folder_name: "",
        DB_DATABASE: "",
        DB_USERNAME: "",
        DB_PASSWORD: "",
        user_id: null
      },
      message: '',
      alertType: 'primary',
      dismissSecs: 7,
      dismissCountDown: 0,
      showDismissibleAlert: false,
      users: [],
      roles: []
    }
  },
  methods: {
    goBack() {
      this.$router.go(-1)
      // this.$router.replace({path: '/users'})
    },
    create() {
      let self = this;
      axios.post(this.$apiAdress + '/api/apps/create?token=' + localStorage.getItem("api_token"),
        { 'app_data': self.appData }
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
    getUsers() {
      let self = this;
      axios.get(this.$apiAdress + '/api/apps/get_clients?token=' + localStorage.getItem("api_token"))
      .then(function (response) { 
        let tmpVal = [];
        for (let i = 0; i < response.data.users.length; i++) {
          tmpVal.push({ label: response.data.users[i].email, value: response.data.users[i].id });
        }
        self.users = tmpVal;
      })
      .catch(function (error) {
        if (error.response.status === 503) {
          self.alertType = 'danger';
          self.message = error.response.data.message;
          self.showAlert();          
        } else if (error.response.status === 401) {
          self.$router.push({ path: '/login' });
        }
      })
    }
  },
  mounted: function () {
    this.getUsers();
    this.roles = localStorage.getItem("roles").split(",");
  }
}
</script>