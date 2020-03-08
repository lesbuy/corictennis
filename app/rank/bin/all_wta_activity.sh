#!/bin/bash

source ~/.bashrc

cat /dev/null > err_wta_activity

cat all_wta_pid | while read line
do
	echo processing $line
	php ../src/wta_activity.php wta s $line > $DATA/activity/wta/$line 2>> err_wta_activity
	sleep 2
	php ../src/wta_activity.php wta d $line >> $DATA/activity/wta/$line 2>> err_wta_activity
	sleep 5
done
