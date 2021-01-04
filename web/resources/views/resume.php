<title>周振宇的个人简历</title>
<div class=resume>
<div class=block id=info>
	<div id=name>周振宇<span>后端研发工程师</span></div>
	<div>
		<svg class="icon" aria-hidden="true"><use xlink:href="#icon-shouji"></use></svg><svg class="icon" aria-hidden="true"><use xlink:href="#icon-weixin"></use></svg> <a href="tel:18616266072">18616266072</a> &nbsp;
		<svg class="icon" aria-hidden="true"><use xlink:href="#icon-youxiang"></use></svg> <a href="mailto:lesbuy@gmail.com">lesbuy@gmail.com</a> &nbsp;
		<svg class="icon" aria-hidden="true"><use xlink:href="#icon-github"></use></svg> <a href="https://github.com/lesbuy">github.com/lesbuy</a> &nbsp;
	</div>
	<div>
		<svg class="icon" aria-hidden="true"><use xlink:href="#icon-daxue"></use></svg> 上海交通大学 &nbsp;
		<svg class="icon" aria-hidden="true"><use xlink:href="#icon-ditu"></use></svg> 上海市 浦东新区 &nbsp;
		<svg class="icon" aria-hidden="true"><use xlink:href="#icon-chushengriqi"></use></svg> 1988.05 &nbsp;
	</div>
<!--	<img id=head src=/images/tips/head.png />-->
</div>
<div class=block_title><svg class="icon" aria-hidden="true"><use xlink:href="#icon-gongzuojingli"></use></svg> 工作经历</div>
<div class=block id=work>
	<table><tr><td><b>2013.03<br>至今</b><br><br><b>百度上海</b><br><br>资深研发<br>工程师T6</td>
	<td><div>&emsp;&emsp;在百度统计（国内最大的中文网站/APP流量统计与用户行为分析工具，日均覆盖全网160亿PV与50亿APP次启动。流式系统承担32*22000的QPS）产品组担任后端研发工程师以及辅助BD进行系统性的技术支持。</div>
	<div>&emsp;&emsp;早年主要承担产品业务的升级与研发。后期逐步承担产品的平台性建设以及与公司内部数据的融合。主导设计了百度统计接入公司内效果数据的一站式平台的架构和数据流。同时结合公司内部资源整合的要求对后端进行了系统性升级，维护了数据流的稳定。</div>
	<div>&emsp;&emsp;17年，因应市场大环境改变，主导了新产品 - 百度统计高级分析的后端建设，该产品逐步发展成了现在的toB产品“百度统计分析云”。之后伴随分析云产品的推广，带领5人的小团队，主要承担深入了解客户的分析需求，为客户在流量红利耗尽之后的深度用户分析与运营方面提供专业的技术支持，包括详尽的埋码方案、分析方案，以及定制化需求。同时对内独立开发了分析云产品运营平台，为老板的运营决策、运营考核、BD与PM和RD的需求对接提供了有力保障。</div></td></tr></table>
</div>
<div class=block_title><svg class="icon" aria-hidden="true"><use xlink:href="#icon-xiangmu"></use></svg> 项目经验</div>
<div class=block id=project>
	<table class=project>
		<tr class=project_title><td class=project-time rowspan=2>2015.03<br>2015.08</td><td>百度统计一站式效果平台</td></tr>
		<tr><td class=project_content>该一站式平台将百度内其他产品线（移动建站、商桥、离线宝、移动搜索等）的效果数据通过统一格式、时效性接入百度统计，使得客户通过百度统计同时获取广告前后链路数据与效果转化数据。后端采用nginx落地实时流+基于Hadoop的分布式离线计算组成。实时流落地事实访问数据后接入效果数据统一ETL处理。上线后百度统计日均UV提升了30%以上。并且一站式效果数据成为百度搜索推广（凤巢）OLAP里的效果数据源。</td></tr>
	</table>
	<table class=project>
		<tr class=project_title><td class=project-time rowspan=2>2015.07<br>2016.06</td><td>MOLA迁移与百度统计系统的升级改造</td></tr>
		<tr><td class=project_content>MOLA是百度内部基于Comdb的高效KV查询系统。百度统计通过MOLA存储字面与签名的关系，以应对数据库不能存字面之苦。由于MOLA数据急剧膨胀（日均10亿条KV）并且新机器只有SATA盘并且无法进行高效内存查询，以及集群分居两地的现实，推动原有MOLA集群升级为Comdb+Rocksdb双集群，利用MOLA搭建百度统计KV流的Cache集群，抛弃长尾KV，并保障了线上主要报告的字面数据充足且稳定。历时一年完成了该部分迁移，将百度统计对MOLA的资源依赖下降了2/3。</td></tr>
	</table>
	<table class=project>
		<tr class=project_title><td class=project-time rowspan=2>2016.09<br>2018.05</td><td>百度统计高级分析</td></tr>
		<tr><td class=project_content>由于市场情况变化，单一报表查询已经无法满足需求，网站主/APP开发者需要更多个性化的分析需求，以及更好地了解自己用户的使用习惯和兴趣。主导设计了百度统计高级分析后端的架构与数据流，系统设计为全维度的最细粒度数据+定制维度报表数据共存。为应对数据量急剧膨胀，增加了Partition设计、数据采样与还原算法的设计，通过Meta Server有效管理自定义事件和看板配置，并通过Spark+Kafka提升了入库时效。产品入库时效性提升至分钟级，并可满足日均万级用户的查询。</td></tr>
	</table>
	<table class=project>
		<tr class=project_title><td class=project-time rowspan=2>2018.07<br>2019.12</td><td>组建营销分析解决方案团队</td></tr>
		<tr><td class=project_content>结合分析云产品的推广，组建5人小团队为客户提供差异化的营销分析。服务于皇家加勒比、宝格丽、业之峰装修、39健康等大中客户。利用分析云为客户制定营销全链路的数据分析方案与投放建议。内容涵盖技术埋点、人群包训练、转化分析、用户行为分析等，打造了投放端到效果端的数据闭环。提升客户对投放和效果数据的满意度。客户总体满意度高，80%以上的服务客户最终购买了分析云产品。</td></tr>
	</table>
	<table class=project>
		<tr class=project_title><td class=project-time rowspan=2>2019.06<br>2019.08</td><td>分析云运营平台</td></tr>
		<tr><td class=project_content>因应产品运营的发展需要，在内网中搭建基于Django+Nginx+uWSGI+SQLite3的数据平台。便于运营人员实时记录客户需求，并流程化记录PM、RD的跟进情况。为老板直观展现BD工作效率、运营漏斗，提升决策效率。</td></tr>
	</table>
	<table class=project>
		<tr class=project_title><td class=project-time rowspan=2>2014.03<br>至今</td><td>基于网球数据的个人网站（非公司项目）</td></tr>
		<tr><td class=project_content>循个人的网球爱好，纯独立开发 <a href="https://www.rank-tennis.com">https://www.rank-tennis.com</a> 网站。主要业务为全球的男女网球职业选手排名即时计算。系统前端采用Laravel + Apache2（现正使用Django+Vue进行单页化改造），后端通过Shell，PHP，Python等脚本进行实时计算（比赛结束后10s内出结果）。目前峰值日UV为2.1万，峰值日PV达到10万+。百度和Google搜索关键词“网球即时排名”均排名首位。流量覆盖全球180多个国家和地区，40%为境外流量。</td></tr>
	</table>
</div>
<div class=block_title><svg class="icon" aria-hidden="true"><use xlink:href="#icon-jiaoyujingli"></use></svg> 教育经历</div>
<div class=block id=edu>
	<table><tr><td>2010.09 ~ 2013.03</td><td>上海交通大学（硕士）</td><td>计算机科学与技术</td><td>主研方向：自然语言处理，LDA</td></tr>
	<tr><td>2006.09 ~ 2010.06</td><td>暨南大学    （本科）</td><td>数学与应用数学（软工方向）</td><td></td></tr></table>
</div>
<div class=block_title><svg class="icon" aria-hidden="true"><use xlink:href="#icon-jitirongyuchenghaoshenqingchaxun"></use></svg> 个人能力</div>
<div class=block id=skill>
熟练使用C++；熟练使用Shell、PHP等脚本语言。使用过Python、Objective-C、Java 进行短期开发<br>
熟练使用Hadoop等分布式平台计算。使用过Kubernetes进行容器部署。使用过Kafka进行消息订阅<br>
CET6 552分，CET口语 B+  |  头脑灵活，热情且富有行动力  |  认定的目标会持之以恒地钻研
</div>

</div>

<script src="//at.alicdn.com/t/font_70586_a5lhlr60jov.js"></script>

<style>
.icon {
   width: 1em; height: 1em;
   vertical-align: -0.15em;
   fill: currentColor;
   overflow: hidden;
}
.resume {
	font-size: 16px;
	width: 1050px;
	height: 1485px;
	margin: 0 auto;
}

.block_title {
	font-size: 1.2em;
	margin-top: 0.7em;
	border-bottom: 1px solid;
	padding-left: 2em;
	font-weight: 700;
}	

#info {
	line-height: 1.8;
	position: relative;
}
#head {
	position: absolute;
	height: 150px;
    width: 150px;
    right: 0;
    top: 0;
    border-radius: 100px;
    border: 1px solid rgba(0,0,0,0.1);
}
#name {
	font-family: 华文宋体;
    font-weight: 700;
	font-size: 2.5em;
}
#name span {
	font-size: 0.65em;
	margin-left: 2em;
}

#work td:first-child {
	padding: 0 0.5em;
	white-space: nowrap;
	text-align: center;
	border-right: 1px solid;
}

#work td:last-child {
	padding: 0 0.5em;
}
#work td:last-child div {
	margin: 5px auto;
	line-height: 1.6;
}

.project {
	margin: .8em auto;
}
.project-time {
	text-align: center;
	padding-right: 1em;
	border-right: 1px solid;
}
.project_title {
	font-weight: 700;
	font-size: 1.1em;
}
.project_title td {
	padding-left: .5em;
}
.project_content {
	padding-left: 1em;
	line-height: 1.6;
}

#edu table {
	width: 100%;
}
#skill {
	line-height: 1.5;
}
</style>
