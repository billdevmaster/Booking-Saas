<template>
  <CRow>
    <CCol col="12" xl="12">
      <transition name="slide">
      <CCard>
        <CCardHeader>
          Rakendused
        </CCardHeader>
        <CCol col="3" xl="2" v-if="roles.includes('admin')">
          <div class="mt-2">
            <CButton color="primary" @click="addApp()" class="mb-3">Lisab rakenduse</CButton>
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
                <CButton color="primary" @click="editApp( item.id )">Muuda</CButton>
              </td>
            </template>
            <template #delete="{item}">
              <td>
                <CButton color="danger" @click="deleteApp( item.id )">Kustuta</CButton>
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
      fields: [{key: 'id', label: 'ID'}, {key: 'APP_NAME', label: 'RAKENDUSE NIMI'}, {key: 'url', label: 'URL'}, {key: 'folder_name', label: 'KAUTA NIMI'}, {key: 'DB_DATABASE', label: 'ANDMEBAAS'}, {key: 'DB_USERNAME', label: "ANDMEBAAS KASUTAJANIMI"}, {key: 'end_date', label: 'LÕPPKUUPÄEV'}, {key: 'edit', label: 'MUUDA'}, {key: 'delete', label: 'KUSTUTA'}],
      currentPage: 1,
      perPage: 5,
      totalRows: 0,
      roles: []
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
    deleteApp ( id ) {
      this.$router.push({path: `apps/${id.toString()}/delete`});
    },
    subscribeApp ( id ) {
      this.$router.push({path: `apps/${id.toString()}/plans`});
    },
    getApps() {
      let self = this;
      axios.get(  this.$apiAdress + '/api/apps?token=' + localStorage.getItem("api_token"))
      .then(function (response) {
        self.items = response.data.apps;
      }).catch(function (error) {
        console.log(error);
        if (error.response.status === 401) {
          self.$router.push({ path: '/login' });
        }
        // self.$router.push({ path: '/login' });
      });
    }
  },
  mounted(){
    this.getApps();
    this.roles = localStorage.getItem("roles").split(",");
    if (!this.roles.includes("admin")) {
      this.fields = [{key: 'id', label: 'ID'}, {key: 'APP_NAME', label: 'RAKENDUSE NIMI'}, {key: 'url', label: 'URL'}, {key: 'folder_name', label: 'KAUTA NIMI'}, {key: 'DB_DATABASE', label: 'ANDMEBAAS'}, {key: 'DB_USERNAME', label: "ANDMEBAAS KASUTAJANIMI"}, {key: 'end_date', label: 'LÕPPKUUPÄEV'}, {key: 'edit', label: 'MUUDA'}]
    }
  }
}
</script>