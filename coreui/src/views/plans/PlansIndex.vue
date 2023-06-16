<template>
  <CRow>
    <CCol col="12" xl="12">
      <transition name="slide">
      <CCard>
        <CCardHeader>
          Plaanid
        </CCardHeader>
        <CCol col="3" xl="2">
          <div class="mt-2">
            <CButton color="primary" @click="addPlan()" class="mb-3">Lisab plaanid</CButton>
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
                <CButton color="primary" @click="editPlan( item.id )">Muuda</CButton>
              </td>
            </template>
            <template #delete="{item}">
              <td>
                <CButton color="danger" @click="deleteUser( item.id )">Kustuta</CButton>
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
  name: 'PlansIndex',
  data: () => {
    return {
      items: [],
      fields: [{key: 'id', label: 'ID'}, {key: 'name', label: 'Nimi'}, {key: 'description', label: 'Kirjeldus'}, {key: 'billing_interval', label: 'Arveldusintervall'}, {key: 'price', label: 'Hind'}, {key: 'edit', label: 'Muuda'}, {key: 'delete', label: 'Kustutada'}],
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
    addPlan() {
      this.$router.push({path: 'plans/create'});
    },
    editLink (id) {
      return `plans/${id.toString()}/edit`
    },
    editPlan ( id ) {
      const editLink = this.editLink( id );
      this.$router.push({path: editLink});
    },
    getPlans() {
      let self = this;
      axios.get(  this.$apiAdress + '/api/plans/get_plans?token=' + localStorage.getItem("api_token"))
      .then(function (response) {
        self.items = response.data.plans;
      }).catch(function (error) {
        console.log(error)
        if (error.response.status === 401) {
          self.$router.push({ path: '/login' });
        }
      });
    }
  },
  mounted(){
    this.getPlans();
  }
}
</script>