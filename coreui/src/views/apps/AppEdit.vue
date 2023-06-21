<template>
  <div>
    <CRow>
      <CCol col="12" lg="6">
        <CCard no-header>
          <CCardBody>
            <h3>
              Redigeeri rakendust
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
            
            <CButton color="primary" @click="update()">Värskenda</CButton>
            <CButton color="primary" @click="goBack">tagasi</CButton>
    
          </CCardBody>
        </CCard>
      </CCol>
      <!-- <CCol col="12" lg="6">
        <CCard no-header>
          <CCardBody>
            <h3>
              Rakenduse tellimine
            </h3>
            <CRow class="mt-4">
              <CCol col="12">
                <div class="row">
                  <dt class="col-sm-4">Lubatud lõppkuupäev</dt>
                  <dd class="col-sm-8">
                    {{ appEnd }}
                  </dd>
                </div>
              </CCol>
              <CCol col="6" sm="12" md="6" lg="6" v-for="(plan, index) in plans" v-bind:key="index">
                <CCard no-header>
                  <CCardBody class="text-center">
                    <h3>
                      <span class="text-warning">{{ plan.price }}$</span> / {{ plan.billing_interval }}
                    </h3>
                    <h5>
                      {{ plan.name }}
                    </h5>
                    <CButton color="warning" @click="subscribe(plan.id)">Telli</CButton>
                  </CCardBody>
                </CCard>
              </CCol>
            </CRow>
          </CCardBody>
        </CCard>
      </CCol> -->
    </CRow>
  </div>
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
        DB_PASSWORD: "",
        user_id: null
      },
      message: '',
      alertType: 'primary',
      dismissSecs: 7,
      dismissCountDown: 0,
      showDismissibleAlert: false,
      users: [],
      plans: [],
      appEnd: "",
      roles: []
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
        }
      })
    },
    getPlans() {
      let self = this;
      axios.get(this.$apiAdress + '/api/plans/get_plans?token=' + localStorage.getItem("api_token"))
      .then(function (response) {
        self.plans = response.data.plans;
      }).catch(function (error) {
        console.log(error);
        // self.$router.push({ path: '/login' });
      });
    },
    subscribe(plan_id) {
      console.log(plan_id);
      let self = this;
      self.$router.push({ path: `${plan_id}/subscribe` });
    },
    getAppEnddate() {
      axios.get(this.$apiAdress + '/api/apps/get_app_end_date?token=' + localStorage.getItem("api_token") + '&app_id=' + this.$route.params.id)
      .then(response => {
        if (response.data.end_date) {
          this.appEnd = response.data.end_date;
        } else {
          this.appEnd = 'Not Subscribed';
        }
      })
      .catch(error => {
        console.error(error);
      })
    }
  },
  mounted: function () {
    this.getUsers();
    // this.getPlans();
    // this.getAppEnddate();
    let self = this;
    this.roles = localStorage.getItem("roles").split(",");
    axios.get(  this.$apiAdress + '/api/apps/get_app?token=' + localStorage.getItem("api_token") + '&id=' + self.$route.params.id)
    .then(function (response) {
      console.log(response)
      self.appData = response.data.app;
    }).catch(function (error) {
      if (error.response.status === 401) {
        self.$router.push({ path: '/login' });
      }
        // self.$router.push({ path: '/login' });
    });
  }
}
</script>