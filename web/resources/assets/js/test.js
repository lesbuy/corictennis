require('./bootstrap');

window.Vue = require('vue');

import Test from './components/Test.vue';
import Datetime from 'vue-datetime';
import 'vue-datetime/dist/vue-datetime.css';
Vue.use(Datetime);

const test = new Vue({
    el: '#test',
    render: h => h(Test),
});
