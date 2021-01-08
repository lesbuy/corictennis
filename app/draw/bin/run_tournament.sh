#!/bin/bash

#-------------------------------------
# 处理大满贯、巡回赛、挑战赛、ITF比赛（青少年除外）的签表、参赛记录、h2h、轮次信息（仅巡回赛和挑战赛输出）
# 挑战赛与ITF比赛隔5分钟才处理，时效性不强
#-------------------------------------

source ~/.bashrc
source ../conf/base.conf

if [ -f PROGRESS.run.tournament ]
then
    exit
fi

touch PROGRESS.run.tournament

current_monday=`date -d "last Monday" +%Y-%m-%d`
monday1=`date -d "$current_monday -7 days" +%Y-%m-%d`
monday2=`date -d "$current_monday +7 days" +%Y-%m-%d`
monday3=`date -d "$current_monday -14 days" +%Y-%m-%d`
monday4=`date -d "$current_monday +14 days" +%Y-%m-%d`

# 隔5分钟才处理低级别比赛
if [[ $((`date +%M | awk '{print $0 + 0}'`%5)) == 3 ]]
then
	tourList=`grep -E "$current_monday|$monday1|$monday2|$monday3|$monday4" $STORE/calendar/$year/GS $STORE/calendar/$year/WT $STORE/calendar/$year/CH $STORE/calendar/$year/ITF | grep -v ":J[0-9AB]"`
else
	tourList=`grep -E "$current_monday|$monday1|$monday2|$monday3|$monday4" $STORE/calendar/$year/GS $STORE/calendar/$year/WT`
fi

now=`date +%s`
nowNano=`date +%s%N`

echo "$tourList" | while read line
do
	sex=`echo "$line" | cut -f4`
	asso=""
	if [[ $sex == "W" ]]
	then
		asso="wta"
	elif [[ $sex == "M" ]]
	then
		asso="atp"
	elif [[ $sex == "J" ]]
	then
		continue
	fi

	echo "++++++++++++++++ enter " $(((`date +%s%N`-nowNano)/1000000))ms

	eid=`echo "$line" | cut -f2`
	unix=`echo "$line" | cut -f7`
	year=`echo "$line" | cut -f5`
	weeks=`echo "$line" | cut -f22`

	# 2周的比赛从上周1开始到结束后的周1
	# 1周的比赛从上周4开始到结束后的周3
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
		php ../src/process.php $eid $year $asso
	fi
	echo "++++++++++++++++ leave " $(((`date +%s%N`-nowNano)/1000000))ms
done

echo total $(((`date +%s%N`-nowNano)/1000000))ms

rm PROGRESS.run.tournament
