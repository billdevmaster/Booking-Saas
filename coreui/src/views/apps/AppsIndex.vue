<template>
  <CRow>
    <CCol col="12" xl="12">
      <transition name="slide">
      <CCard>
        <CCardHeader>
          Apps
        </CCardHeader>
        <CCol col="3" xl="2">
          <div class="mt-2">
            <CButton color="primary" @click="addApp()" class="mb-3">Adds App</CButton>
          </div>
        </CCol>
        <CCardBody>
          <CDataTable
            hover
            striped
            :items="items"
            :fields="fields"
            :items-per-page="5"
            pagination
          >
            <template #url="{item}">
              <td>
                <a :href.sync="item.url" target="_blank">{{ item.url }}</a>
              </td>
            </template>
            <template #edit="{item}">
              <td>
                <CButton color="primary" @click="editApp( item.id )">Edit</CButton>
              </td>
            </template>
            <template #delete="{item}">
              <td>
                <CButton color="danger" @click="deleteUser( item.id )">Delete</CButton>
              </td>
            </template>
          </CDataTable>
        </CCardBody>
      </CCard>
      </transition>
    </CCol>
  </CRow>
</template>

<script>
import axios from 'axios'

export default {
  name: 'AppsIndex',
  data: () => {
    return {
      items: [],
      fields: ['id', 'APP_NAME', 'folder_name', 'DB_DATABASE', 'DB_USERNAME', 'edit', 'delete'],
      currentPage: 1,
      perPage: 5,
      totalRows: 0,
    }
  },
  paginationProps: {
    align: 'center',
    doubleArrows: false,
    previousButtonHtml: 'prev',
    nextButtonHtml: 'next'
  },
  methods: {
    addApp() {
      this.$router.push({path: 'apps/create'});
    },
    editLink (id) {
      return `apps/${id.toString()}/edit`
    },
    editApp ( id ) {
      const editLink = this.editLink( id );
      this.$router.push({path: editLink});
    },
    getApps() {
      let self = this;
      axios.get(  this.$apiAdress + '/api/apps?token=' + localStorage.getItem("api_token"))
      .then(function (response) {
        self.items = response.data.apps;
      }).catch(function (error) {
        console.log(error);
        // self.$router.push({ path: '/login' });
      });
    }
  },
  mounted(){
    this.getApps();
  }
}
</script>