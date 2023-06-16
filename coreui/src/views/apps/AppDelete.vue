<template>
  <CRow>
    <CCol col="6" lg="6">
      <CCard>
        <CCardBody>
          <h4>Kustuta rakendus</h4>
          <p>Oled sa kindel?</p>
          <CAlert
            :show.sync="dismissCountDown"
            color="primary"
            fade
          >
            ({{dismissCountDown}}) {{ message }}
          </CAlert>

          <CButton color="danger" @click="deleteApp()">Kustuta</CButton>
          <CButton color="primary" @click="goBack">tagasi</CButton>
        </CCardBody>
      </CCard>
    </CCol>
  </CRow>
</template>

<script>
import axios from 'axios'
export default {
  name: 'AppDelete',
  data: () => {
    return {
        message: '',
        dismissSecs: 7,
        dismissCountDown: 0,
    }
  },
  methods: {
    goBack() {
      this.$router.go(-1)
    },   
    deleteApp() {
      let self = this;
      axios.get(  this.$apiAdress + '/api/apps/delete?token=' + localStorage.getItem("api_token") + '&app_id=' + self.$route.params.id, {})
      .then(function (response) {
          if(response.data.success == true){
            self.$router.go(-1)
          }else{
            self.message = "Can't delete";
            self.showAlert();
          }
      }).catch(function (error) {
        console.log(error);
        self.$router.push({ path: '/login' });
      });
    },
    showAlert () {
      this.dismissCountDown = this.dismissSecs
    },
  },
  mounted: function(){
  }
}

</script>