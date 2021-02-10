#!/bin/bash

source ~/.bashrc
source ../conf/base.conf

if [ -f PROGRESS.run.live ]
then
    exit
fi

touch PROGRESS.run.live

current_monday=`date -d "last Monday" +%Y-%m-%d`
monday1=`date -d "$current_monday -7 days" +%Y-%m-%d`
monday2=`date -d "$current_monday +7 days" +%Y-%m-%d`
monday3=`date -d "$current_monday -14 days" +%Y-%m-%d`
monday4=`date -d "$current_monday +14 days" +%Y-%m-%d`

cat /dev/null > tmp_live

php ../src/live.php AO $year >> tmp_live

now=`date +%s`
grep -E "$current_monday|$monday1|$monday2|$monday3|$monday4" $STORE/calendar/$year/WT $STORE/calendar/$year/CH | 
while read line
do
	eid=`echo "$line" | cut -f2`
	unix=`echo "$line" | cut -f7`
	year=`echo "$line" | cut -f5`
	weeks=`echo "$line" | cut -f22`

	sex=`echo "$line" | cut -f4`

	asso=""
	if [[ $sex == "W" ]]
	then
		asso="wta"
	elif [[ $sex == "M" ]]
	then
		asso="atp"
	fi

	# 2周的比赛从上周1开始到结束后的周1
	# 1周的比赛从上周4开始到结束后的周2
	if [[ $weeks == "2" ]]
	then
		starttime=$((unix-7*86400))
		endtime=$((unix+15*86400))
	else
		starttime=$((unix-4*86400))
		endtime=$((unix+9*86400))
	fi

	if [[ $now -gt $starttime && $now -lt $endtime ]]
	then
		echo $eid $year $asso
		php ../src/live.php $eid $year $asso >> tmp_live
	fi
done

mv tmp_live live

rm PROGRESS.run.live
