#!/bin/bash

source ~/.bashrc

weekday=`date +%u`
a=`echo $weekday-1 | bc`
this_monday=`date -d "$a days ago" +%Y-%m-%d`

echo `date "+%Y-%m-%d %H:%M:%S"` begin
while true
do
	php ../src/rank.php wta s $this_monday > $TEMP/rank/wta_s
	if [ $? -eq 0 ]
	then
		break
	else
		sleep 60
	fi
done

cp $TEMP/rank/wta_s $DATA/rank/wta/s/history/$this_monday
mv $TEMP/rank/wta_s $DATA/rank/wta/s/current
echo `date "+%Y-%m-%d %H:%M:%S"` wta s done

php ../src/rank.php wta race $this_monday > $TEMP/rank/wta_race
mv $TEMP/rank/wta_race $DATA/rank/wta/s/race
echo `date "+%Y-%m-%d %H:%M:%S"` wta race done

while true
do
	php ../src/rank.php wta d $this_monday > $TEMP/rank/wta_d
	if [ $? -eq 0 ]
	then
		break
	else
		sleep 60
	fi
done

cp $TEMP/rank/wta_d $DATA/rank/wta/d/history/$this_monday
mv $TEMP/rank/wta_d $DATA/rank/wta/d/current
echo `date "+%Y-%m-%d %H:%M:%S"` wta d done

php ../src/rank.php wta drace $this_monday > $TEMP/rank/wta_drace
mv $TEMP/rank/wta_drace $DATA/rank/wta/d/race
echo `date "+%Y-%m-%d %H:%M:%S"` wta drace done
