<?php

return [

	'tip' => '',

	'title' => [
		'schedule' => 'Pick Calendar',
		'rule' => 'Pick Rules',
		'rank' => 'Pick Rankings',
		'sign' => 'Pick Signing',
	],

	'guess' => [
		'sets' => 'Sets',
		'aces' => 'Aces',
		'time' => 'Time',
		'hour' => 'H',
		'minute' => 'M',

		'deadline' => 'Deadline',
		'submit' => 'Submit',
		'total' => 'Score',
		'rank' => 'Day Rank',
		'weekRank' => 'Week Rank',
		'absent' => 'Not Played',
	],

	'dcpk' => [
		'sign' => 'Enter for Knockout Race',
		'week' => 'Week :p1',
		'signup' => 'ENTER',
	],

	'errcode' => [
		'notLogin' => 'Login first Please',
		'noMatch' => 'No Matches',
		'notClosed' => 'No show before deadline',
		'success' => 'Successfully Submitted! Refresh to confirm please',
		'timeout' => 'Submitting deadline passed',
		'failed' => 'Failed. Retry please',
		'wrongInfo' => 'Wrong info',
		'signed' => 'You have entered for the race',
		'ipBlocked' => 'You are not permitted to enter due to maximum limit in your IP',
		'deviceBlock' => 'You are not permitted to enter due to maximum limit on your device',
		'wrongBrowser' => 'Please change your browser to enter for the race',
		'wrongDevice' => 'Your new device should be used to enter for KO Race at least 5 days after first time browsing the site',
		'notPermitted' => 'Your account is not permitted to play the game',
	],

	'rule' => [

		'signDdl' => 'Submit picking results for the first day (:p1) to qualify into the DRAW. The deadline to enter for the KO race is :p2',
		'3hour' => '3 hours before picking deadline',
		'ipRestrict' => 'At most 3 accounts per IP. At most 3 accounts per device. <br>A new device should be used to enter for KO race at least 5 days after first time browsing the site.',

		'pick' => 'Pick matches every day on the <a href="' . url(App::getLocale() . '/guess') . '">PAGE</a>（Pay attention to DEADLINE）<br>',

		'pickRule' => '
You should pick SIX matches at most for WINNER, NUMBER OF SETS, TOTAL ACES and MATCH DURATION. You will get a score every day<br/>
    <br/>
    Scoring rule：</br>
    　① Right winner +1 ，otherwise +0</br>
    　② Right number of sets +0.5 ，otherwise +0</br>
    　③ Right Aces +0.25 ，0.01 deducted for every 4% diff with right Aces，at least +0</br>
    　④ Right match duration(minutes) +0.25 ，0.01 deducted for every 3% diff with right duration，at least +0</br>
    More：<br/>
　①Match cancelled if WALKOVER or player changes<br/>
　②Match cancelled if a wrong deadline takes bad effects on it<br>
　③Match cancelled if it is postponed to next day and still not completed. Score of a postponed match will be calculated on its original day<br>
　④If a player retires in the match, his opponent wins, number of sets will be calculated as if the winner completes best 3 or 5. Aces and match duration will not be calculated in the score<br>',

		'roundPoint' => '
Each week is a tournament（except Grand Slams, Indian Wells and Miami for 2 weeks）<br>Click <a href="' . url(App::getLocale() . '/guess/calendar') . '">HERE</a> for the calendar<br>
Tournament levels are：GS、1000、500、250<br>
<table>
	<tr><td></td><td>GS</td><td>1000</td><td>500</td><td>250</td></tr>
	<tr><td>Winner</td><td>2000</td><td>1000</td><td>500</td><td>250</td></tr>
	<tr><td>Runner-up</td><td>1300</td><td>650</td><td>325</td><td>163</td></tr>
	<tr><td>Semifinal(3-4)</td><td>780</td><td>390</td><td>195</td><td>98</td></tr>
	<tr><td>Quarterfinal(5-8)</td><td>430</td><td>215</td><td>108</td><td>54</td></tr>
	<tr><td>Round 16(9-16)</td><td>240</td><td>120</td><td>60</td><td>30</td></tr>
	<tr><td>Round 32(17-32)</td><td>120</td><td>60</td><td>30</td><td>15</td></tr>
	<tr><td>Round 64(33-64)</td><td>70</td><td>35</td><td>18</td><td>9</td></tr>
	<tr><td>Round 128(64 or more)</td><td>10</td><td>10</td><td>5</td><td>5</td></tr>
	<tr><td>Qualified</td><td>30</td><td>25</td><td>-</td><td>-</td></tr>
	<tr><td>Not Qualified</td><td>10</td><td>8</td><td>-</td><td>-</td></tr>
</table>',

		'ko_itgl' => '
The two RANKINGs are from: Integral Race and Knockout Race<br>
Integral Race means to summarize total scores and get a ranking point for a tournament<br>
Knockout Race means to put you into a draw, and make pairwise contest. The final round determines a ranking point for a tournament (Just like ATP/WTA rule)<br>
As like ATP/WTA, the RANKING points count points in recent 52 weeks<br>
&nbsp;&nbsp;&nbsp;Ranking points of Integral Race count best 20 while Ranking points of Knockout Race count best 15<br>
<table>
	<tr><td></td><td>Integral Race</td><td>Knockout Race</td></tr>
	<tr><td>Enter</td><td>No need</td><td>Click <a href="' . url(App::getLocale() . '/guess/sign') . '">HERE</a> to enter in one week before tournament begins</td></tr>
	<tr><td>Frequency</td><td>Each week</td><td>Only GS, 1000</td></tr>
	<tr><td>Rank points from</td><td>Total score</td><td>Final round in the draw</td></tr>
	<tr><td>Count</td><td>Best 20</td><td>Best 15</td></tr>
</table>',

		'itglNotice' => '
                Notifications for Integral Race：<br>
                1、Submit then you can get score and point<br>
                2、Table above shows standard points for ranking. Your point = Your Score / Highest Score * Standard Point。Highest Score means the score of weekly winner<br>
                3、Minimum point for GS/1000 is 10, while 5 for 500/250<br>',

		'dcpkNotice' => '
				Notifications for Knockout Race：<br>
                1、Only in GS/1000<br>
                2、Enter and pick matches on the first day before the draw is released to participate. Draw will be released one hour before deadline on the first day<br>
                3、If two players have the same score, ranking is determined by A highest-score match，A second-highest-score match,...，Total score this week, Ranking point for Knockout Race, Ranking point for Integral race, entering order<br>
                4、If the number of participants is between 64 and 96(excluded), main draw size is 64 with 16 seeds, and qualifying draw size is not greater than 32 (Only one round)<br>
                5、If the number of participants is between 96 and 128(excluded), main draw size is 128 with 32 seeds(Top seeds will receive BYE 1st round)<br>
                6、If the number of participants is greater than 128, main draw size is 128 with 32 seeds, and qualifying draw size is not greater than 64 (Only one round)<br>
                7、Entry and seed rules:<br>
                &nbsp;&nbsp;&nbsp;Participants mean those who enter and pick matches on 1st day<br>
                &nbsp;&nbsp;&nbsp;All participants are sorted by Integral Race Ranking (previous week, by entering time if same), and cut lists for main draw and qualifying draw are made according to Rule 4-6 <br>
                &nbsp;&nbsp;&nbsp;All participants who are listed in main draw will be sorted by Knockout Race Ranking (previous week, by entering time if same) to be determined as seeds<br>
                8、Lucky Loser: If a player does not attend the first day of first round in main draw, he will be forced to retire. His draw position will be replaced by a Lucky Loser<br>
                &nbsp;&nbsp;&nbsp;LL order: Losers in qualification round (Priority by score of Q round), and those who were not able to be in qualifying draw (Priority by Integral Race Ranking, if same, entering time)<br>
                9、The day number of each round is determined according to tournament process<br>
                10、A player is NOT PERMITTED to participate Knockout Race of next tournament if he is still in the draw but does not complete ALL matches of that round (no influence in this tournament, but next. If two players both do not complete any match in a round, they are both knockouted and their opponent next round will receive a WALKOVER)<br>',
	],

];
