<template>
  <CDropdown
    inNav
    class="c-header-nav-items"
    placement="bottom-end"
    add-menu-classes="pt-0"
  >
    <template #toggler>
      <CHeaderNavLink>
        <div class="c-avatar">
          <img
            src="img/avatars/6.jpg"
            class="c-avatar-img "
          />
        </div>
      </CHeaderNavLink>
    </template>
    <CDropdownItem @click="logout()">
      <CIcon name="cil-lock-locked" /> Logout
    </CDropdownItem>
  </CDropdown>
</template>

<script>
import axios from 'axios'
export default {
  name: 'TheHeaderDropdownAccnt',
  data () {
    return { 
      itemsCount: 42,
    }
  },
  methods:{
    logout(){
      let self = this;
      axios.post(this.$apiAdress + '/api/logout?token=' + localStorage.getItem("api_token"),{})
      .then(function (response) {
        localStorage.setItem('roles', '');
        self.$router.push({ path: '/login' });
      }).catch(function (error) {
        if (error.response.status === 401) {
          localStorage.setItem('roles', '');
          self.$router.push({ path: '/login' });
        } 
      });
    }
  }
}

</script>
<style scoped>
  .c-icon {
    margin-right: 0.3rem;
  }
</style>
