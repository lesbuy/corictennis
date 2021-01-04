<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<title>UNICODE旗帜查询</title>
<script type="text/javascript" language="javascript" src="/js/jquery.min.js"></script>
<script type="text/javascript" language="javascript" src="/js/tool.js?t={{ time() }}"></script>

<div>
	<table>
		@for ($i = 0; $i < 10; ++$i)
			<tr>
				@for ($j = 0; $j < 25; ++$j)
					@isset($ret[$i][$j])
						<td><img src="{{ url(join("/", ['images', 'flag_svg', $ret[$i][$j] . '.svg'])) }}" /></td>
					@endisset
				@endfor
			</tr>
		@endfor
	</table>
</div>

<br>
<div class="ensign-m ensign jpn"></div>
<div class="ensign-m ensign usa"></div>
<div class="ensign-m ensign uae"></div>
<div class="ensign-m ensign chn"></div>
<div class="ensign-m ensign tpe"></div>

<style>
	td {
		width: 58px;
		height: 38px;
		border: 2px solid transparent;
		padding: 0;
		position: relative;
	}
	img {
		width: 58px;
		height: 38px;
		border-radius: 10px;
	}
	table {
		box-shadow: 0 0 2px rgba(0,0,0,0.5);
		border-collapse: separate;
	}
	td::before {
		content: '　';
		position: absolute;
		top: 0;
		left: 0;
		width: 100%;
		height: 100%;
		background-color: transparent;
	    box-shadow: inset 0 0 20px;
		border-radius: 10px;
	}

	.ensign-m {
		zoom: 50%;
	}

	.ensign {
		background-image: url(/images/tips/flags-1.png);
		background-repeat: no-repeat;
		width: 60px;
		height: 40px;
	}

	.ensign.afg{background-position: 0px 0px;}
	.ensign.aho{background-position: -60px 0px;}
	.ensign.alb{background-position: -120px 0px;}
	.ensign.alg{background-position: -180px 0px;}
	.ensign.and{background-position: -240px 0px;}
	.ensign.ang{background-position: -300px 0px;}
	.ensign.ant{background-position: -360px 0px;}
	.ensign.arg{background-position: -420px 0px;}
	.ensign.arm{background-position: -480px 0px;}
	.ensign.aru{background-position: -540px 0px;}
	.ensign.asa{background-position: -600px 0px;}
	.ensign.aus{background-position: -660px 0px;}
	.ensign.aut{background-position: -720px 0px;}
	.ensign.aze{background-position: -780px 0px;}
	.ensign.bah{background-position: -840px 0px;}
	.ensign.ban{background-position: -900px 0px;}
	.ensign.bar{background-position: -960px 0px;}
	.ensign.bdi{background-position: -1020px 0px;}
	.ensign.bel{background-position: -1080px 0px;}
	.ensign.ben{background-position: -1140px 0px;}
	.ensign.ber{background-position: -1200px 0px;}
	.ensign.bhu{background-position: -1260px 0px;}
	.ensign.bih{background-position: -1320px 0px;}
	.ensign.biz{background-position: -1380px 0px;}
	.ensign.blr{background-position: -1440px 0px;}
	.ensign.bol{background-position: 0px -40px;}
	.ensign.bot{background-position: -60px -40px;}
	.ensign.bra{background-position: -120px -40px;}
	.ensign.brn{background-position: -180px -40px;}
	.ensign.bru{background-position: -240px -40px;}
	.ensign.bul{background-position: -300px -40px;}
	.ensign.bur{background-position: -360px -40px;}
	.ensign.caf{background-position: -420px -40px;}
	.ensign.cal{background-position: -480px -40px;}
	.ensign.cam{background-position: -540px -40px;}
	.ensign.can{background-position: -600px -40px;}
	.ensign.cay{background-position: -660px -40px;}
	.ensign.cgo{background-position: -720px -40px;}
	.ensign.cha{background-position: -780px -40px;}
	.ensign.chi{background-position: -840px -40px;}
	.ensign.chn{background-position: -900px -40px;}
	.ensign.cis{background-position: -960px -40px;}
	.ensign.civ{background-position: -1020px -40px;}
	.ensign.cmr{background-position: -1080px -40px;}
	.ensign.cod{background-position: -1140px -40px;}
	.ensign.cok{background-position: -1200px -40px;}
	.ensign.col{background-position: -1260px -40px;}
	.ensign.com{background-position: -1320px -40px;}
	.ensign.cpv{background-position: -1380px -40px;}
	.ensign.crc{background-position: -1440px -40px;}
	.ensign.cro{background-position: 0px -80px;}
	.ensign.cub{background-position: -60px -80px;}
	.ensign.cuw{background-position: -120px -80px;}
	.ensign.cyp{background-position: -180px -80px;}
	.ensign.cze{background-position: -240px -80px;}
	.ensign.den{background-position: -300px -80px;}
	.ensign.dji{background-position: -360px -80px;}
	.ensign.dma{background-position: -420px -80px;}
	.ensign.dom{background-position: -480px -80px;}
	.ensign.ecu{background-position: -540px -80px;}
	.ensign.egy{background-position: -600px -80px;}
	.ensign.eri{background-position: -660px -80px;}
	.ensign.esa{background-position: -720px -80px;}
	.ensign.esp{background-position: -780px -80px;}
	.ensign.est{background-position: -840px -80px;}
	.ensign.eth{background-position: -900px -80px;}
	.ensign.eur{background-position: -960px -80px;}
	.ensign.fgu{background-position: -1020px -80px;}
	.ensign.fij{background-position: -1080px -80px;}
	.ensign.fin{background-position: -1140px -80px;}
	.ensign.fra{background-position: -1200px -80px;}
	.ensign.frg{background-position: -1260px -80px;}
	.ensign.fro{background-position: -1320px -80px;}
	.ensign.fsm{background-position: -1380px -80px;}
	.ensign.gab{background-position: -1440px -80px;}
	.ensign.gam{background-position: 0px -120px;}
	.ensign.gbr{background-position: -60px -120px;}
	.ensign.gbs{background-position: -120px -120px;}
	.ensign.gdr{background-position: -180px -120px;}
	.ensign.geo{background-position: -240px -120px;}
	.ensign.geq{background-position: -300px -120px;}
	.ensign.ger{background-position: -360px -120px;}
	.ensign.gha{background-position: -420px -120px;}
	.ensign.gre{background-position: -480px -120px;}
	.ensign.grn{background-position: -540px -120px;}
	.ensign.gua{background-position: -600px -120px;}
	.ensign.gud{background-position: -660px -120px;}
	.ensign.gui{background-position: -720px -120px;}
	.ensign.gum{background-position: -780px -120px;}
	.ensign.guy{background-position: -840px -120px;}
	.ensign.hai{background-position: -900px -120px;}
	.ensign.hkg{background-position: -960px -120px;}
	.ensign.hon{background-position: -1020px -120px;}
	.ensign.hun{background-position: -1080px -120px;}
	.ensign.ina{background-position: -1140px -120px;}
	.ensign.ind{background-position: -1200px -120px;}
	.ensign.iri{background-position: -1260px -120px;}
	.ensign.irl{background-position: -1320px -120px;}
	.ensign.irq{background-position: -1380px -120px;}
	.ensign.isl{background-position: -1440px -120px;}
	.ensign.isr{background-position: 0px -160px;}
	.ensign.isv{background-position: -60px -160px;}
	.ensign.ita{background-position: -120px -160px;}
	.ensign.ivb{background-position: -180px -160px;}
	.ensign.jam{background-position: -240px -160px;}
	.ensign.jor{background-position: -300px -160px;}
	.ensign.jpn{background-position: -360px -160px;}
	.ensign.kaz{background-position: -420px -160px;}
	.ensign.ken{background-position: -480px -160px;}
	.ensign.kgz{background-position: -540px -160px;}
	.ensign.kir{background-position: -600px -160px;}
	.ensign.kor{background-position: -660px -160px;}
	.ensign.kos{background-position: -720px -160px;}
	.ensign.ksa{background-position: -780px -160px;}
	.ensign.kuw{background-position: -840px -160px;}
	.ensign.lao{background-position: -900px -160px;}
	.ensign.lat{background-position: -960px -160px;}
	.ensign.lba{background-position: -1020px -160px;}
	.ensign.lbn{background-position: -1080px -160px;}
	.ensign.lbr{background-position: -1140px -160px;}
	.ensign.lca{background-position: -1200px -160px;}
	.ensign.les{background-position: -1260px -160px;}
	.ensign.lib{background-position: -1320px -160px;}
	.ensign.lie{background-position: -1380px -160px;}
	.ensign.ltu{background-position: -1440px -160px;}
	.ensign.lux{background-position: 0px -200px;}
	.ensign.mac{background-position: -60px -200px;}
	.ensign.mad{background-position: -120px -200px;}
	.ensign.mal{background-position: -180px -200px;}
	.ensign.mar{background-position: -240px -200px;}
	.ensign.mas{background-position: -300px -200px;}
	.ensign.maw{background-position: -360px -200px;}
	.ensign.mda{background-position: -420px -200px;}
	.ensign.mdv{background-position: -480px -200px;}
	.ensign.mex{background-position: -540px -200px;}
	.ensign.mgl{background-position: -600px -200px;}
	.ensign.mhl{background-position: -660px -200px;}
	.ensign.mkd{background-position: -720px -200px;}
	.ensign.mli{background-position: -780px -200px;}
	.ensign.mlt{background-position: -840px -200px;}
	.ensign.mne{background-position: -900px -200px;}
	.ensign.mon{background-position: -960px -200px;}
	.ensign.moz{background-position: -1020px -200px;}
	.ensign.mri{background-position: -1080px -200px;}
	.ensign.mrn{background-position: -1140px -200px;}
	.ensign.mtn{background-position: -1200px -200px;}
	.ensign.mya{background-position: -1260px -200px;}
	.ensign.nam{background-position: -1320px -200px;}
	.ensign.nca{background-position: -1380px -200px;}
	.ensign.ncl{background-position: -1440px -200px;}
	.ensign.ned{background-position: 0px -240px;}
	.ensign.nep{background-position: -60px -240px;}
	.ensign.ngr{background-position: -120px -240px;}
	.ensign.nig{background-position: -180px -240px;}
	.ensign.nmi{background-position: -240px -240px;}
	.ensign.nor{background-position: -300px -240px;}
	.ensign.nru{background-position: -360px -240px;}
	.ensign.nzl{background-position: -420px -240px;}
	.ensign.oma{background-position: -480px -240px;}
	.ensign.omn{background-position: -540px -240px;}
	.ensign.pak{background-position: -600px -240px;}
	.ensign.pan{background-position: -660px -240px;}
	.ensign.par{background-position: -720px -240px;}
	.ensign.per{background-position: -780px -240px;}
	.ensign.phi{background-position: -840px -240px;}
	.ensign.ple{background-position: -900px -240px;}
	.ensign.plw{background-position: -960px -240px;}
	.ensign.png{background-position: -1020px -240px;}
	.ensign.pol{background-position: -1080px -240px;}
	.ensign.por{background-position: -1140px -240px;}
	.ensign.prk{background-position: -1200px -240px;}
	.ensign.pur{background-position: -1260px -240px;}
	.ensign.qat{background-position: -1320px -240px;}
	.ensign.reu{background-position: -1380px -240px;}
	.ensign.rho{background-position: -1440px -240px;}
	.ensign.rom{background-position: 0px -280px;}
	.ensign.rou{background-position: -60px -280px;}
	.ensign.rsa{background-position: -120px -280px;}
	.ensign.rus{background-position: -180px -280px;}
	.ensign.rwa{background-position: -240px -280px;}
	.ensign.sam{background-position: -300px -280px;}
	.ensign.scg{background-position: -360px -280px;}
	.ensign.sen{background-position: -420px -280px;}
	.ensign.sey{background-position: -480px -280px;}
	.ensign.sgp{background-position: -540px -280px;}
	.ensign.sin{background-position: -600px -280px;}
	.ensign.skn{background-position: -660px -280px;}
	.ensign.sle{background-position: -720px -280px;}
	.ensign.slo{background-position: -780px -280px;}
	.ensign.smr{background-position: -840px -280px;}
	.ensign.sol{background-position: -900px -280px;}
	.ensign.som{background-position: -960px -280px;}
	.ensign.srb{background-position: -1020px -280px;}
	.ensign.sri{background-position: -1080px -280px;}
	.ensign.ssd{background-position: -1140px -280px;}
	.ensign.stp{background-position: -1200px -280px;}
	.ensign.sud{background-position: -1260px -280px;}
	.ensign.sui{background-position: -1320px -280px;}
	.ensign.sur{background-position: -1380px -280px;}
	.ensign.svk{background-position: -1440px -280px;}
	.ensign.swe{background-position: 0px -320px;}
	.ensign.swz{background-position: -60px -320px;}
	.ensign.syr{background-position: -120px -320px;}
	.ensign.tan{background-position: -180px -320px;}
	.ensign.tch{background-position: -240px -320px;}
	.ensign.tga{background-position: -300px -320px;}
	.ensign.tha{background-position: -360px -320px;}
	.ensign.tjk{background-position: -420px -320px;}
	.ensign.tkm{background-position: -480px -320px;}
	.ensign.tls{background-position: -540px -320px;}
	.ensign.tog{background-position: -600px -320px;}
	.ensign.tpe{background-position: -660px -320px;}
	.ensign.tri{background-position: -720px -320px;}
	.ensign.tto{background-position: -780px -320px;}
	.ensign.tun{background-position: -840px -320px;}
	.ensign.tur{background-position: -900px -320px;}
	.ensign.tuv{background-position: -960px -320px;}
	.ensign.uae{background-position: -1020px -320px;}
	.ensign.uga{background-position: -1080px -320px;}
	.ensign.ukr{background-position: -1140px -320px;}
	.ensign.urs{background-position: -1200px -320px;}
	.ensign.uru{background-position: -1260px -320px;}
	.ensign.usa{background-position: -1320px -320px;}
	.ensign.uzb{background-position: -1380px -320px;}
	.ensign.van{background-position: -1440px -320px;}
	.ensign.ven{background-position: 0px -360px;}
	.ensign.vie{background-position: -60px -360px;}
	.ensign.vin{background-position: -120px -360px;}
	.ensign.wld{background-position: -180px -360px;}
	.ensign.yem{background-position: -240px -360px;}
	.ensign.yug{background-position: -300px -360px;}
	.ensign.zam{background-position: -360px -360px;}
	.ensign.zim{background-position: -420px -360px;}
</style>
