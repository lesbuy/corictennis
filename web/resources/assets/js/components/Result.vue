<template><div>
<div id="o-datepicker">
	<div class="form-inline">
		<router-link :to="{name: 'result', params:{date: f_time_format(add_day(today, -1), 'yyyy-mm-dd')}}" class="input-group-addon">&lt;</router-link>
		<datetime 
			v-model="today" 
			:input-class="'form-control'"
			:format="'yyyy-MM-dd'" 
			auto
			:input-style="datepicker_style"
			:phrases="{ok: '', cancel: ''}"
			value-zone="Asia/Shanghai"
			zone="Asia/Shanghai"
			:week-start="1"
			@input="dayClicked"
		></datetime>
		<router-link :to="{name: 'result', params:{date: f_time_format(add_day(today, 1), 'yyyy-mm-dd')}}" class="input-group-addon">&gt;</router-link>
	</div>
	<input type=text v-model="today" />
	<div id="o-statuspicker" class="form-inline">
		<input type="radio" name="status-switch" v-model="current_status" id="o-ss-a" value="-1"><label for="o-ss-a" class="form-control col-sm-1 col-3">{{ data ? data.var.all : '' }}</label><input type="radio" name="status-switch" v-model="current_status" id="o-ss-l" value="1"><label for="o-ss-l" class="form-control col-sm-1 col-3">{{ data ? data.var.live : '' }}</label><input type="radio" name="status-switch" v-model="current_status" id="o-ss-c" value="2"><label for="o-ss-c" class="form-control col-sm-1 col-3">{{ data ? data.var.completed : '' }}</label><input type="radio" name="status-switch" v-model="current_status" id="o-ss-u" value="0"><label for="o-ss-u" class="form-control col-sm-1 col-3">{{ data ? data.var.upcoming : '' }}</label>
	</div>
</div>
<div v-if="loading">
	<img class="cLoading" :src="GLOBAL.loading_icon">
</div>
<div v-else>
	<div v-for="(tour, tour_idx) in data.tours" class=cResultTour :id="'iResult' + tour.eid" :data-year="tour.year" :data-eid="tour.eid">
		<div class="cResultTourTitle" :is-open="tour.is_tour" @click="fold(tour_idx)">
			<img class="cResultTourTitleArrow" :src="data.var.arrow" />
			<div class="cResultTourTitleBlank" :class="'Surface' + tour.sfc"></div>
			<img v-for="logo in tour.logos" :src="logo">
			<div class="cResultTourTitleInfo">
				<div class="cResultTourInfoCity">{{ tour.city }}</div>
				<div class="cResultTourInfoName">{{ tour.title }}</div>
			</div>
		</div>
		<div class=cResultTourContent v-show="tour.is_tour" :loaded="tour.loaded">
			<img v-if="!tour.loaded" class="cLoading" :src="data.var.loading">
			<div v-for="(acourt, court_name) in tour.courts" class=cResultCourt :data-court="revise_court(court_name)">
				<div class="cResultCourtTitle">{{ revise_court(court_name) }}</div>
				<div v-for="amatch in acourt" class="cResultMatch" v-show="current_status == -1 || current_status == amatch.status" :class="[amatch.class_name, {'cResultHidden': amatch.hidden_class}]" :best-of="amatch.bestof" :match-status="amatch.status" :is-double="amatch.is_d" :data-bets-id="amatch.bets_id" :match-id="amatch.matchid" @click="openDetail">
					<div class=cResultMatchMask></div>
					<div class=cResultMatchLeft>
						<div class=cResultMatchTime v-html="GLOBAL.get_local_time(amatch.start, $route.params.date)"></div>
						<div>
							<div class=cResultMatchGender>{{ amatch.sex }}</div>
							<div class=cResultMatchRound>{{ amatch.round }}</div>
						</div>
						<div v-if="amatch.dura" class=cResultMatchDura>{{ amatch.dura }}</div>
						<div v-else>&nbsp;</div>
						<div class=cResultMatchClick>
							<div v-if="amatch.h2h && amatch.h2h_link" class=cResultMatchH2H>{{ amatch.h2h }}</div>
							<div v-if="GLOBAL.lang == 'zh' && amatch.hl_link" class=cResultMatchH2H><a href="amatch.hl_link" target=_blank>{{ data.var.hl }}</a></div>
							<div v-if="GLOBAL.lang == 'zh' && amatch.whole_link" class=cResultMatchH2H><a href="amatch.whole_link" target=_blank>{{ data.var.replay }}</a></div>
						</div>
					</div>
					<div class=cResultMatchMid>
						<div v-show="amatch.flag" class=cResultMatchMidPointFlag>{{ amatch.flag }}</div>
						<table>  
							<tr class="cResultMatchMidTableRowOdd" :class="amatch.winner == 1 ? 'cResultMatchMidTableRowWinner' : (amatch.inserve == 1 ? 'cResultMatchMidTableRowServe' : '')">
								<td>
									<span v-for="p in amatch.team1.p">
										<img v-if="p.ioc" class="cImgPlayerFlag" :src="data.var.flag_path.replace('XXX', p.ioc)">{{ p.name }}<sub v-if="amatch.team1.seed !== null"> [{{ amatch.team1.seed }}]</sub><sub v-if="amatch.team1.rank !== null"> {{ amatch.team1.rank }}</sub><span v-if="amatch.team1.odd" class="cResultMatchOdds"> {{ amatch.team1.odd }}</span><br>
									</span>
									<div>
										<div v-show="amatch.team1.score[0][0] !== null" :class="{'loser': amatch.team1.score[0][2]}">{{ amatch.team1.score[0][0] }}<sup v-if="amatch.team1.score[0][1] !== null">{{ amatch.team1.score[0][1] }}</sup></div>
										<div v-show="amatch.team1.score[1][0] !== null" :class="{'loser': amatch.team1.score[1][2]}">{{ amatch.team1.score[1][0] }}<sup v-if="amatch.team1.score[1][1] !== null">{{ amatch.team1.score[1][1] }}</sup></div>
										<div v-show="amatch.team1.score[2][0] !== null" :class="{'loser': amatch.team1.score[2][2]}">{{ amatch.team1.score[2][0] }}<sup v-if="amatch.team1.score[2][1] !== null">{{ amatch.team1.score[2][1] }}</sup></div>
										<div v-show="amatch.team1.score[3][0] !== null" :class="{'loser': amatch.team1.score[3][2]}">{{ amatch.team1.score[3][0] }}<sup v-if="amatch.team1.score[3][1] !== null">{{ amatch.team1.score[3][1] }}</sup></div>
										<div v-show="amatch.team1.score[4][0] !== null" :class="{'loser': amatch.team1.score[4][2]}">{{ amatch.team1.score[4][0] }}<sup v-if="amatch.team1.score[4][1] !== null">{{ amatch.team1.score[4][1] }}</sup></div>
										<div>{{ amatch.team1.point }}</div>
									</div>
								</td>
							</tr>  
							<tr :class="amatch.winner == 2 ? 'cResultMatchMidTableRowWinner' : (amatch.inserve == 2 ? 'cResultMatchMidTableRowServe' : '')">
								<td>
									<span v-for="p in amatch.team2.p">
										<img v-if="p.ioc" class="cImgPlayerFlag" :src="data.var.flag_path.replace('XXX', p.ioc)">{{ p.name }}<sub v-if="amatch.team2.seed !== null"> [{{ amatch.team2.seed }}]</sub><sub v-if="amatch.team2.rank !== null"> {{ amatch.team2.rank }}</sub><span v-if="amatch.team2.odd" class="cResultMatchOdds"> {{ amatch.team2.odd }}</span><br>
									</span>
									<div>
										<div v-show="amatch.team2.score[0][0] !== null" :class="{'loser': amatch.team2.score[0][2]}">{{ amatch.team2.score[0][0] }}<sup v-if="amatch.team2.score[0][1] !== null">{{ amatch.team2.score[0][1] }}</sup></div>
										<div v-show="amatch.team2.score[1][0] !== null" :class="{'loser': amatch.team2.score[1][2]}">{{ amatch.team2.score[1][0] }}<sup v-if="amatch.team2.score[1][1] !== null">{{ amatch.team2.score[1][1] }}</sup></div>
										<div v-show="amatch.team2.score[2][0] !== null" :class="{'loser': amatch.team2.score[2][2]}">{{ amatch.team2.score[2][0] }}<sup v-if="amatch.team2.score[2][1] !== null">{{ amatch.team2.score[2][1] }}</sup></div>
										<div v-show="amatch.team2.score[3][0] !== null" :class="{'loser': amatch.team2.score[3][2]}">{{ amatch.team2.score[3][0] }}<sup v-if="amatch.team2.score[3][1] !== null">{{ amatch.team2.score[3][1] }}</sup></div>
										<div v-show="amatch.team2.score[4][0] !== null" :class="{'loser': amatch.team2.score[4][2]}">{{ amatch.team2.score[4][0] }}<sup v-if="amatch.team2.score[4][1] !== null">{{ amatch.team2.score[4][1] }}</sup></div>
										<div>{{ amatch.team2.point }}</div>
									</div>
								</td>
							</tr>  
						</table> 
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
</div></template>

<script>
import axios from 'axios'
export default {
	data() {
		return {
			today: this.today,
			data: this.data,
			loading: this.loading,
			f_get_local_time: this.GLOBAL.get_local_time,
			f_time_format: this.GLOBAL.timeFormat,
			attrs: [{
				key: 'today',
				highlight: true,
				dates: new Date()
			}],
			datepicker_style: {
				'cursor': 'pointer', 
				'text-align': 'center'
			},
			current_status: -1,
		}
	},

	created() {
		this.loading = true;
		this.today = this.$route.params.date;
		axios.get('/api/' + this.GLOBAL.lang + '/result/' + this.$route.params.date)
		.then(response => {
			this.data = response.data;
			this.loading = false;
		})
		.catch(error => {
			console.log(error);
		})
	},

	watch: {
		'$route' (to, from) {
			if (this.$route.params.date){
				this.today = this.$route.params.date;
				this.loading = true;
				axios.get('/api/' + this.GLOBAL.lang + '/result/' + this.$route.params.date)
				.then(response => {
					this.data = response.data;
					this.loading = false;
				})
				.catch(error => {
					console.log(error);
				})
			}
		}
	},

	computed: {
		revise_court() {
			return (court) => {
				return court.split('\t')[1];
			};
		},
	},

	methods: {
		fold(tour_idx) {
			var tour = this.data.tours[tour_idx];
			this.data.tours[tour_idx].is_tour = 1 - tour.is_tour;

			if (!tour.loaded) {
				axios.post('/api/' + this.GLOBAL.lang + '/result/' + this.$route.params.date, {
					eid: tour.eid
				}).then(response => {
					this.data.tours[tour_idx].courts = response.data;
					this.data.tours[tour_idx].loaded = true;
				}).catch(error => {
					console.log(error);
				});
			}
		},

		add_day(_date, _itvl) {
			return new Date(new Date(_date) - (-_itvl) * 86400000);
		},

		dayClicked(e) {
			// input框首次出现以及从消失到出现时同样会激发input事件，所以日期相同时不跳转，没有内容不跳转
			if (e && this.$route.params.date && e.substr(0,10) != this.$route.params.date) {
				this.$router.push({name: 'result', params: {date: e.substr(0,10)}});
			}
		},

		openDetail(e) {
			console.log('---');
			console.log(e);
		},

	},

}
</script>

<style>
#o-datepicker {
font-size: 1.2rem;
margin: 1rem;
}
#o-statuspicker{
margin-top: 1rem;
}
#o-statuspicker > label {
text-align: center;
padding-top: 0.5rem;
padding-bottom: 0.5rem;
border-radius: 0.2rem;
cursor: pointer;
}
.cResultMatch{
cursor: pointer;
position: relative;
}
.cResultMatch:hover {
box-shadow: 0rem 0rem 0.2rem 0.2rem rgba(0, 0, 255, 0.5);
}
.cResultMatchMask {
position: absolute;
width: 100%;
height: 100%;
top: 0;
left: 0;
z-index: 1;
}
</style>
