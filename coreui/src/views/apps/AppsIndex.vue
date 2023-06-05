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
      </CCard>
      </transition>
    </CCol>
  </CRow>
</template>

<script>
export default {
  name: 'AppsIndex',
  methods: {
    addApp() {
      this.$router.push({path: 'apps/create'});
    }
  }
}
</script>