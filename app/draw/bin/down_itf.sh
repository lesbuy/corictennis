#!/bin/bash

source ~/.bashrc
source ../conf/base.conf

if [ -f down_itf_in_progress ]
then
	exit
fi

touch down_itf_in_progress

now=`date +%s`

# 一站赛事从前周六到下周二下载
cat $STORE/calendar/$year/ITF | awk -v now=$now -F"\t" '$7 - 2 * 86400 <= now && $7 + 9 * 86400 >= now && $1 !~ /^J/' | while read line
#cat $STORE/calendar/$year/ITF | awk -v now=$now -F"\t" '$7 >= 1596211200 && $7 < 1608480000 && $1 ~ /^J/' | while read line
do
	tourid=`echo "$line" | cut -f3`
	year=`echo "$line" | cut -f5`
	sex=`echo "$line" | cut -f4`
	eid=`echo "$line" | cut -f2`
	level=`echo "$line" | cut -f1`
	if [ -z "$tourid" ]
	then
		continue
	fi

	echo process $tourid $eid $year

	if [[ "$level" == "ITF" || ${level:0:1} == "W" || ${level:0:1} == "M" ]]
	then
		echo https://live.itftennis.com//feeds/d/drawsheets.php/en/$eid-$year
		curl "https://live.itftennis.com//feeds/d/drawsheets.php/en/$eid-$year" | php ../src/itf_draw_data_resize.php > $TEMP/draw_${eid}_${year}
		size=`stat $TEMP/draw_${eid}_${year} | grep Size | awk '{print $2}'`

		if [ $size -gt 500 ]
		then
			if [ ! -f $DATA/tour/draw/$year/$eid ]
			then
				mv $TEMP/draw_${eid}_${year} $DATA/tour/draw/$year/$eid
			else
				file_size=`stat $DATA/tour/draw/$year/$eid | grep Size | awk '{print $2}'`
				if [ $size -gt $file_size ]
				then
					mv $TEMP/draw_${eid}_${year} $DATA/tour/draw/$year/$eid
				fi
			fi
		fi
	else
		php ../src/itf_junior_draw_download.php $eid $year J $tourid > $TEMP/draw_${eid}_${year}
		size=`stat $TEMP/draw_${eid}_${year} | grep Size | awk '{print $2}'`

		if [ $size -gt 500 ]
		then
			if [ ! -f $DATA/tour/draw/$year/$eid ]
			then
				mv $TEMP/draw_${eid}_${year} $DATA/tour/draw/$year/$eid
			else
				file_size=`stat $DATA/tour/draw/$year/$eid | grep Size | awk '{print $2}'`
				if [ $size -gt $file_size ]
				then
					mv $TEMP/draw_${eid}_${year} $DATA/tour/draw/$year/$eid
				fi
			fi
		fi
	fi
	sleep 5
done

rm down_itf_in_progress
