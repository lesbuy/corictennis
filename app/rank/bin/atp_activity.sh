#!/bin/bash

source ~/.bashrc

cat /dev/null > err_atp_activity

cat list_atp_activity | while read line
do
	echo processing $line
	php ../src/atp_activity.php atp s $line > $TEMP/activity_atp_s_$line 2>> err_atp_activity
	if [ ! -s $TEMP/activity_atp_s_$line ]
	then
		echo NOTICE $line s no_content >> err_atp_activity
	fi
	sleep 2

	php ../src/atp_activity.php atp d $line > $TEMP/activity_atp_d_$line 2>> err_atp_activity
	if [ ! -s $TEMP/activity_atp_d_$line ]
	then
		echo NOTICE $line d no_content >> err_atp_activity
	fi
	sleep 2

	cat $TEMP/activity_atp_d_$line >> $TEMP/activity_atp_s_$line
	cat $TEMP/activity_atp_s_$line $DATA/activity/atp/$line | sort -t"	" -k15r,15 -k8gr,8 -k3,3 -s -u > $TEMP/activity_atp_d_$line
	mv $TEMP/activity_atp_d_$line $DATA/activity/atp/$line
	/bin/rm $TEMP/activity_atp_*_$line
done
