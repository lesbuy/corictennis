require('./bootstrap');

window.Vue = require('vue');

// Vue.component('example', require('./components/Example.vue')); // 注释掉
import VueRouter from 'vue-router'
import global_ from './components/Global.vue';
import Menu from './components/Menu.vue'; 
import Result from './components/Result.vue'; 
import Content from './components/Content.vue'; 
import ElementUI from 'element-ui';
import VCalendar from 'v-calendar';
import Datetime from 'vue-datetime';
import 'vue-datetime/dist/vue-datetime.css';
import 'element-ui/lib/theme-chalk/index.css';
Vue.use(ElementUI);
Vue.use(VueRouter);
Vue.use(Datetime);

Vue.prototype.GLOBAL = global_;

import { Settings } from 'luxon';
Settings.defaultLocale = global_.lang;

const router = new VueRouter({
	mode: 'history',
	base: __dirname,
    routes: [
        {
            path: '/:lang/result/:date',
            name: 'result',
            component: Result,
        }
    ],
});

const menu = new Vue({
    el: '#v-menu',
    render: h => h(Menu),
	router,
});
const content = new Vue({
    el: '#v-content',
    render: h => h(Content),
	router,
});
