<template>
<div>
	<div id="top-nav">
		<div class="tn-user" id="open_menu"><a><div></div></a></div>
		<div class="tn-user"><a target=_self href=""><img id=iImgTopLogo src="" /></a></div>
	</div>

	<div id="top-container">
		<div id="top-menu">
			<ul id="menu-table">
				<li>{{ msg }}</li>
			</ul>
		</div>
	</div>
</div>
</template>

<script>
export default {
	data() {
		return {
			msg: '我太难了',
		}
	}
}
</script>

<style>

</style
