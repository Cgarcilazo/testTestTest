<template>
  <div class="container-fluid">
    <h4 class="title mt-3 mb-3">
      👋 Hola, bienvenido al proyecto de Inteligencia Artificial <br>
          perteneciente a los alumnos:<br>
          Garcilazo, Cristian<br>
          Torres, Dafne Virginia
      </h4>
    <b-nav tabs>
      <b-nav-item @click="currentComponent = 'HomeComponent'">Inicio</b-nav-item>
      <b-nav-item @click="currentComponent = 'TreeComponent'">Árbol</b-nav-item>
    </b-nav>
    <component :is="currentComponent" :treeData="treeData" ref="HomeComponent"/>
  </div>
</template>

<script>
  import TreeComponent from './TreeComponent.vue';
  import HomeComponent from './HomeComponent.vue';
  export default {
    components: {
      HomeComponent,
      TreeComponent
    },
    data() {
      return {
        currentComponent: "HomeComponent"
      }
    },
    computed: {
      treeData() {
        return this.currentComponent == "HomeComponent" ? [] : this.$refs.HomeComponent.treeData;
      }
    }
  }
</script>
