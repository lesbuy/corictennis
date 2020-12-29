#!/bin/bash

source ~/.bashrc

cat /dev/null > err_wta_activity

#cat $DATA/rank/wta/s/current $DATA/rank/wta/d/current $DATA/activity_current/wta/* | cut -f1 | sort -u | grep -E "^[0-9]{5,6}$" > list_wta_activity

cat list_wta_activity | while read line
do
	echo processing $line
	php ../src/wta_activity.php wta s $line all 0 > $TEMP/activity_wta_s_$line 2>> err_wta_activity
	if [ ! -s $TEMP/activity_wta_s_$line ]
	then
		echo NOTICE $line s no_content >> err_wta_activity
	fi
	sleep 2

	php ../src/wta_activity.php wta d $line all 0 > $TEMP/activity_wta_d_$line 2>> err_wta_activity
	if [ ! -s $TEMP/activity_wta_d_$line ]
	then
		echo NOTICE $line d no_content >> err_wta_activity
	fi
	sleep 2

	cat $TEMP/activity_wta_d_$line >> $TEMP/activity_wta_s_$line
	cat $TEMP/activity_wta_s_$line $DATA/activity/wta/$line | sort -t"	" -k15r,15 -k8gr,8 -k3,3 -s -u > $TEMP/activity_wta_d_$line
	mv $TEMP/activity_wta_d_$line $DATA/activity/wta/$line
	/bin/rm $TEMP/activity_wta_*_$line
done
