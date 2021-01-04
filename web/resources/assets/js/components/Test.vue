<template><div>
<datetime 
	v-model="morton" 
	:format="'yyyy-MM-dd'" 
	auto
	:phrases="{ok: '', cancel: ''}"
	value-zone="Asia/Shanghai"
	zone="Asia/Shanghai"
	:week-start="1"
></datetime>
<input type=text v-model="morton" />
</div></template>

<script>
import { datetimeFromISO, startOfDay, weekStart } from 'vue-datetime/src/util.js'
export default {
	watch: {
		morton: {
			immediate: true,
			handler: function(val, oldVal) {
				console.log('morton has been changed from "' + oldVal + '" to "' + val + '"')
			},
			deep: true,
		},

	},

	beforeCreate() {
		console.log('beforeCreate, morton is ' + this.morton)
	},

	data() {
		return {
			morton: '2018-07-01',
		}
	},

	created() {
		console.log('created, morton is ' + this.morton)
	},

	beforeMount() {
		console.log('beforeMount, morton is ' + this.morton)
	},

	mounted() {
		this.morton = '2019-10-15T00:00:00.000+08:00'
		console.log('mounted, morton is ' + this.morton)
	},

	beforeUpdate() {
		console.log('beforeUpdate, morton is ' + this.morton)
	},

	updated() {
		console.log('updated, morton is ' + this.morton)
	},

}
</script>

<style>
</style>
