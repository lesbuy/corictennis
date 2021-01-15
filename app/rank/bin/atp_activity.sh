#!/bin/bash

source ~/.bashrc

gender=atp

cat /dev/null > err_${gender}_activity

date
year=2021

cat $DATA/calc/$gender/s/year/unloaded $DATA/calc/$gender/s/year/loaded $DATA/calc/$gender/d/year/unloaded $DATA/calc/$gender/d/year/loaded $DATA/rank/$gender/s/current $DATA/rank/$gender/d/current | cut -f1 | sort -u | grep -v "^#" | grep -v -E "^[0-9]{7,9}$" > list_${gender}_activity

cat list_${gender}_activity | while read line
do
	echo processing $line
	php ../src/${gender}_activity.php $line s $year > $TEMP/activity_${gender}_s_$line 2>> err_${gender}_activity
	if [ ! -s $TEMP/activity_${gender}_s_$line ]
	then
		echo NOTICE $line s no_content >> err_${gender}_activity
	fi
	sleep 2

	php ../src/${gender}_activity.php $line d $year > $TEMP/activity_${gender}_d_$line 2>> err_${gender}_activity
	if [ ! -s $TEMP/activity_${gender}_d_$line ]
	then
		echo NOTICE $line d no_content >> err_${gender}_activity
	fi
	sleep 2

	cat $TEMP/activity_${gender}_d_$line >> $TEMP/activity_${gender}_s_$line
	cat $TEMP/activity_${gender}_s_$line $DATA/activity/${gender}/$line | sort -t"	" -k15r,15 -k6gr,6 -k3,3 -s -u > $TEMP/activity_${gender}_d_$line
	mv $TEMP/activity_${gender}_d_$line $DATA/activity/${gender}/$line
	/bin/rm $TEMP/activity_${gender}_*_$line
done

date
