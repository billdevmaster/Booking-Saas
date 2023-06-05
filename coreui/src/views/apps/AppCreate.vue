<template>
  <CCol col="12" lg="6">
    <CCard no-header>
      <CCardBody>
        <h3>
          Create Apps
        </h3>
        <CInput label="App Name" type="text" placeholder="Title" v-model="appData.APP_NAME"></CInput>
        <CInput label="Folder Name" type="text" placeholder="foler name" v-model="appData.folder_name"></CInput>
        <CInput label="Database" type="text" placeholder="Database" v-model="appData.DB_DATABASE"></CInput>
        <CInput label="Username" type="text" placeholder="Batabase username" v-model="appData.DB_USERNAME"></CInput>
        <CInput label="Password" type="text" placeholder="Database password" v-model="appData.DB_PASSWORD"></CInput>

        <CButton color="primary" @click="create()">Create</CButton>
        <CButton color="primary" @click="goBack">Back</CButton>

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
        DB_PASSWORD: ""
      },
    }
  },
  methods: {
    goBack() {
      this.$router.go(-1)
      // this.$router.replace({path: '/users'})
    },
    create() {
      let self = this;
      console.log(self.appData.APP_NAME)
      axios.post(this.$apiAdress + '/api/apps/create?token=' + localStorage.getItem("api_token"),
        { 'app_data': self.appData }
      )
      .then(function (response) { 
        console.log(response)
      })
      .catch(function (error) { 
        console.log(error)
      })
    }
  }
}
</script>