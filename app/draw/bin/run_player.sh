#!/bin/bash

source ~/.bashrc
source ../conf/base.conf

if [ -f PROGRESS.run.player ]
then
    exit
fi

touch PROGRESS.run.player

current_monday=`date -d "last Monday" +%Y-%m-%d`
monday1=`date -d "$current_monday -7 days" +%Y-%m-%d`
monday2=`date -d "$current_monday +7 days" +%Y-%m-%d`
monday3=`date -d "$current_monday -14 days" +%Y-%m-%d`
monday4=`date -d "$current_monday +14 days" +%Y-%m-%d`

cat /dev/null > tmp_player

now=`date +%s`
grep -E "$current_monday|$monday1|$monday2|$monday3|$monday4" $STORE/calendar/$year/WT $STORE/calendar/$year/CH $STORE/calendar/$year/ITF | 
while read line
do
	eid=`echo "$line" | cut -f2`
	unix=`echo "$line" | cut -f7`
	year=`echo "$line" | cut -f5`
	weeks=`echo "$line" | cut -f22`

	# 最近三周参加过比赛的
	endtime=$((unix+86400*25))

	starttime=$((unix-86400*4))

	if [[ $now -gt $starttime && $now -lt $endtime ]]
	then
		php ../src/player.php $eid $year >> tmp_player
		echo $eid $year
	fi
done

sort -u tmp_player > players

rm PROGRESS.run.player
