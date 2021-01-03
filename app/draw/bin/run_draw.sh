#!/bin/bash

source ~/.bashrc
source ../conf/base.conf

if [ -f PROGRESS.run.draw ]
then
    exit
fi

touch PROGRESS.run.draw

current_monday=`date -d "last Monday" +%Y-%m-%d`
monday1=`date -d "$current_monday -7 days" +%Y-%m-%d`
monday2=`date -d "$current_monday +7 days" +%Y-%m-%d`
monday3=`date -d "$current_monday -14 days" +%Y-%m-%d`
monday4=`date -d "$current_monday +14 days" +%Y-%m-%d`

now=`date +%s`
grep -E "$current_monday|$monday1|$monday2|$monday3|$monday4" $STORE/calendar/$year/GS $STORE/calendar/$year/WT $STORE/calendar/$year/CH | 
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

	if [[ $weeks == "2" ]]
	then
		endtime=$((unix+86400*15))
	else
		endtime=$((unix+86400*9))
	fi

	if [[ $weeks == "2" ]]
	then
		starttime=$((unix-86400*7))
	else
		starttime=$((unix-86400*4))
	fi

	if [[ $now -gt $starttime && $now -lt $endtime ]]
	then
		php ../src/draw.php $eid $year $asso > tmp_draw
		sort -t"	" -k1r,1 -k2,2 -s -u -o tmp_draw tmp_draw
		mv tmp_draw $STORE/draw/$year/$eid
		echo $eid $year
	fi
done

rm PROGRESS.run.draw
