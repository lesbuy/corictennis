#!/bin/bash

source ~/.bashrc
source ../conf/base.conf

if [ -f PROGRESS.run.itf_activity ]
then
    exit
fi

touch PROGRESS.run.itf_activity

current_monday=`date -d "last Monday" +%Y-%m-%d`
monday1=`date -d "$current_monday -7 days" +%Y-%m-%d`
monday2=`date -d "$current_monday +7 days" +%Y-%m-%d`
monday3=`date -d "$current_monday -14 days" +%Y-%m-%d`
monday4=`date -d "$current_monday +14 days" +%Y-%m-%d`

now=`date +%s`
grep -E "$current_monday|$monday1|$monday2|$monday3|$monday4" $STORE/calendar/$year/ITF | grep -v "^J" | 
#awk -F"\t" '$7 >= 1596211200 && $7 < 1609084800 && ($1 ~ /^M/ || $1 ~ /^W/)' $STORE/calendar/$year/ITF | 
while read line
do
	eid=`echo "$line" | cut -f2`
	unix=`echo "$line" | cut -f7`
	year=`echo "$line" | cut -f5`
	weeks=`echo "$line" | cut -f22`

	if [[ $weeks == "2" ]]
	then
		endtime=$((unix+86400*15))
	else
		endtime=$((unix+86400*9))
	fi

	starttime=$((unix-86400*4))

	if [[ $now -gt $starttime && $now -lt $endtime ]]
	then
		php ../src/activity.php $eid $year > tmp_itf_act
		grep atp tmp_itf_act | cut -f2- > tmp_itf_actatp
		grep wta tmp_itf_act | cut -f2- > tmp_itf_actwta
		mv tmp_itf_actatp $DATA/activity_current/atp/$eid
		mv tmp_itf_actwta $DATA/activity_current/wta/$eid
		echo $eid $year
		rm tmp_itf_act
	fi
done

rm PROGRESS.run.itf_activity
