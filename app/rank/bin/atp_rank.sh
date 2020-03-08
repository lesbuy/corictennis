#!/bin/bash

source ~/.bashrc

weekday=`date +%u`
a=`echo $weekday-1 | bc`
this_monday=`date -d "$a days ago" +%Y-%m-%d`

echo `date "+%Y-%m-%d %H:%M:%S"` begin
while true
do
	php ../src/rank.php atp s $this_monday > $TEMP/rank/atp_s
	if [ $? -eq 0 ]
	then
		break
	else
		sleep 60
	fi
done

cp $TEMP/rank/atp_s $DATA/rank/atp/s/history/$this_monday
mv $TEMP/rank/atp_s $DATA/rank/atp/s/current
echo `date "+%Y-%m-%d %H:%M:%S"` atp s done

php ../src/rank.php atp race $this_monday > $TEMP/rank/atp_race
mv $TEMP/rank/atp_race $DATA/rank/atp/s/race
echo `date "+%Y-%m-%d %H:%M:%S"` atp race done

php ../src/rank.php atp u21 $this_monday > $TEMP/rank/atp_u21
mv $TEMP/rank/atp_u21 $DATA/rank/atp/s/u21
echo `date "+%Y-%m-%d %H:%M:%S"` atp u21 done

while true
do
	php ../src/rank.php atp d $this_monday > $TEMP/rank/atp_d
	if [ $? -eq 0 ]
	then
		break
	else
		sleep 60
	fi
done

cp $TEMP/rank/atp_d $DATA/rank/atp/d/history/$this_monday
mv $TEMP/rank/atp_d $DATA/rank/atp/d/current
echo `date "+%Y-%m-%d %H:%M:%S"` atp d done

php ../src/rank.php atp drace $this_monday > $TEMP/rank/atp_drace
mv $TEMP/rank/atp_drace $DATA/rank/atp/d/race
echo `date "+%Y-%m-%d %H:%M:%S"` atp drace done
