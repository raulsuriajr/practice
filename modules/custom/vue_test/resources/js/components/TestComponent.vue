<template>
  <div>
    <h1>{{ text }}</h1>
    <input type="text" v-model="text">
  </div>
</template>

<script>
  export default {
    data() {
      return {
        text: 'Vue Test Template'
      }
    }
  }
</script>
