import Vue from "vue";
import testComponent from "./components/TestComponent";

Vue.component('test-component', testComponent)

new Vue({ el: '#app' })
