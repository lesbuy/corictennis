#!/bin/bash

source ~/.bashrc
source ../conf/base.conf

if [ -f PROGRESS.run.schedule ]
then
    exit
fi

touch PROGRESS.run.schedule

current_monday=`date -d "last Monday" +%Y-%m-%d`
monday1=`date -d "$current_monday -7 days" +%Y-%m-%d`
monday2=`date -d "$current_monday +7 days" +%Y-%m-%d`
monday3=`date -d "$current_monday -14 days" +%Y-%m-%d`
monday4=`date -d "$current_monday +14 days" +%Y-%m-%d`

cat /dev/null > tmp_schedule

now=`date +%s`
grep -E "$current_monday|$monday1|$monday2|$monday3|$monday4" $STORE/calendar/$year/GS $STORE/calendar/$year/WT $STORE/calendar/$year/CH | 
while read line
do
	eid=`echo "$line" | cut -f3`
	unix=`echo "$line" | cut -f7`
	year=`echo "$line" | cut -f5`
	weeks=`echo "$line" | cut -f22`

	if [[ $weeks == "2" ]]
	then
		endtime=$((unix+86400*15))
	else
		endtime=$((unix+86400*8))
	fi

	if [[ $weeks == "2" ]]
	then
		starttime=$((unix-86400*7))
	else
		starttime=$((unix-86400*4))
	fi

	if [[ $now -gt $starttime && $now -lt $endtime ]]
	then
		php ../src/oop.php $eid $year >> tmp_schedule
		echo $eid $year
	fi
done

mv tmp_schedule completed

rm PROGRESS.run.schedule
