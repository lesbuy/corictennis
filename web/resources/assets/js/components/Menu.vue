<template>
<ul id="menu-table" v-if="menu !== undefined && menu.length > 0">
	<li v-for="m1 in menu">
		<a v-if="m1[1] instanceof Array">{{ m1[0] }}<div class="menu_more"></div></a>
		<router-link v-else :to="{name: m1[1], params: m1[2]}">{{ m1[0] }}</router-link>
		<ul v-if="m1[1] !== undefined && m1[1] instanceof Array && m1[1].length > 0">
			<li v-for="m2 in m1[1]">
				<a v-if="m2[1] instanceof Array">{{ m2[0] }}<div class="menu_more"></div></a>
				<a v-else v-bind:href="m2[1]">{{ m2[0] }}</a>
				<ul v-if="m2[1] !== undefined && m2[1] instanceof Array && m2[1].length > 0">
					<li v-for="m3 in m2[1]">
						<a v-if="m3[1] instanceof Array">{{ m3[0] }}</a>
						<a v-else-if="m3[2] !== undefined && m3[2] instanceof Array && m3[2].length > 0" v-bind:href="m3[1]">
							<img v-for="logo in m3[2]" :src="logo" v-bind:class="{'cMenuTourLogo': true}">{{ m3[0] }}
						</a>
						<a v-else v-bind:href="m3[1]">{{ m3[0] }}</a>
					</li>
				</ul>
			</li>
		</ul>
	</li>
</ul>
</template>

<script>
import axios from 'axios'
export default {
	data() {
		return {
			menu: this.menu,
		}
	},

	created() {
		axios.get('/api/' + this.GLOBAL.lang + '/menu')
		.then(response=>{
			this.menu = response.data;
		})
		.catch(error=>{
			console.log(error);
		})
	},
}
</script>

<style>
</style>
