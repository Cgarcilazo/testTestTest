<template>
  <div>
    <p>En esta sección tendrás la oportunidad de seleccionar los parametros correspondientes para crear tu árbol de decisión <br>
    Para hacerlo tendrás que tener previo conocimiento de para qué sirven dichos parámetros. <br>
    Dichos parámetros son: <br>
     * Si quieres usar sólo la Ganancia de Información o en su lugar la Tasa de Ganancia de Información. <br>
     * El Umbral que te parezca aceptable para que el árbol pueda realizar la clasificación correctamente
    </p>

    <b-form @submit.prevent="executeC45">
      <b-form-group
        id="nameGroup"
        label="Ingresa tu archivo de texto"
        label-for="file"
        description="">
        <b-form-file
            id="file"
            v-model="file"
            ref="file"
            name="file"
            placeholder=""/>
      </b-form-group>

      <b-form-group label="Elije con qué quieres trabajar:">
        <b-form-radio v-model="selected" name="some-radios" value="gain">Ganancia</b-form-radio>
        <b-form-radio v-model="selected" name="some-radios" value="gainRatio">Tasa de Ganancia</b-form-radio>
      </b-form-group>

      <b-form-group label="Ingresa un Umbral que te parezca correcto:">
        <b-form-input v-model="threshold" placeholder="Debe ser un número entre 0 y 1"></b-form-input>
      </b-form-group>

      <b-button type="submit" variant="success" :disabled="loading">
        <span>Crea tu árbol</span>
        <b-spinner
            v-if="loading"
            small
            class="ml-2" />
      </b-button>
    </b-form>

  </div>
</template>

<script>
  export default {
    data() {
      return {
        file: null,
        selected: 'gain',
        threshold: '',
        loading: false,
        results: []
      }
    },
    computed: {
      treeData() {
        if (this.results.length == 0) {
          return []
        }
        let rootNode = {};
        rootNode.isLeaf = this.results.isLeaf;
        rootNode.attributeName = this.results.attributeName;
        rootNode.class = this.results.class;
        rootNode.value = this.results.value;

        if (this.results.isLeaf) {
            rootNode.children = [];
            return rootNode;
        } else {
            rootNode.children = [
                this.makeChild(this.results.leftPartition),
                this.makeChild(this.results.rightPartition)
            ]
        }
        return rootNode;
}
    },
    methods: {
      executeC45() {
        if (this.selected == '' || this.threshold == ''){
          alert('Rellena todos los campos antes de continuar');
          return;
        }

        this.loading = true;
        let formData = new FormData();
          formData.append('file', this.file);
          formData.append('selected', this.selected);
          formData.append('threshold', this.threshold);

        axios.post( '/api/executeC45', formData, {
          headers: {
            'Content-Type': 'multipart/form-data'
            }
        }).then((response) => {
          this.loading = false;
          this.results = response.data.results;
          alert('Resultado obtenido, ve a la sección árbol para verlo');
        }).catch((error) => {
          this.loading = false;
          alert('Algo salió mal, controla todos los campos');
        })
      },

      makeChild(partition) {
        let child = {};
        if (partition.isLeaf) {
          child = {
              isLeaf : partition.isLeaf,
              class : partition.class,
              confidence: partition.confidence,
              children: []
          }
        } else {
          child = {
            isLeaf : partition.isLeaf,
            attributeName : partition.attributeName,
            class : partition.class,
            value : partition.value,
            children: [
                this.makeChild(partition.leftPartition),
                this.makeChild(partition.rightPartition)
            ]
          }
        }
        return child;
    },
    }
  }
</script>
