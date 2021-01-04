#!/bin/bash

source ~/.bashrc
source ../conf/conf.sh

#------------------------------------------------------
cat /dev/null > $TEMP/tmp_calendar_WT
php ../src/atp.php >> $TEMP/tmp_calendar_WT
php ../src/wta.php >> $TEMP/tmp_calendar_WT

cat $TEMP/tmp_calendar_WT $STORE/calendar/$year/WT | sort -u -s -k1,1 -k2,2 | sort -s -t"	" -k7g,7 -k21gr,21 -k2,2 > $TEMP/tmp_calendar_WT2
mv $TEMP/tmp_calendar_WT2 $STORE/calendar/$year/WT

#------------------------------------------------------
cat /dev/null > $TEMP/tmp_calendar_CH
php ../src/ch.php >> $TEMP/tmp_calendar_CH

cat $TEMP/tmp_calendar_CH $STORE/calendar/$year/CH | sort -u -s -k1,1 -k2,2 | sort -s -t"	" -k7g,7 -k21gr,21 -k2,2 > $TEMP/tmp_calendar_CH2
mv $TEMP/tmp_calendar_CH2 $STORE/calendar/$year/CH

#------------------------------------------------------
cat /dev/null > $TEMP/tmp_calendar_ITF
php ../src/itf-men.php >> $TEMP/tmp_calendar_ITF
php ../src/itf-women.php >> $TEMP/tmp_calendar_ITF
php ../src/itf-junior.php >> $TEMP/tmp_calendar_ITF

cat $TEMP/tmp_calendar_ITF $STORE/calendar/$year/ITF | awk -F"\t" -v year=$year '$5 == year' | sort -u -s -k1,1 -k2,2 | sort -s -t"	" -k7g,7 -k21gr,21 -k2,2 > $TEMP/tmp_calendar_ITF2
mv $TEMP/tmp_calendar_ITF2 $STORE/calendar/$year/ITF

#------------------------------------------------------

#php ../src/down_calendar.php $year | sort -t"	" -k7g,7 -k21gr,21 -k2,2 > $TEMP/tmp_calender1
#php ../src/down_ch.php $year | sort -t"	" -k7g,7 -k21gr,21 -k2,2 > $TEMP/tmp_calender2

#cat $STORE/calendar/$year/WT $TEMP/tmp_calender1 | sort -u -s -k1,1 -k2,2 | sort -s -t"	" -k7g,7 -k21gr,21 -k2,2 > $TEMP/tmp_calender11
#cat $STORE/calendar/$year/CH $TEMP/tmp_calender2 | sort -u -s -k1,1 -k2,2 | sort -s -t"	" -k7g,7 -k21gr,21 -k2,2 > $TEMP/tmp_calender22

#	awk -f reducer.awk tmp/tmp_calender2 > tmp/tmp_calender1

#	mv tmp/tmp_calender1 $ROOT_PATH/$STORE_PATH/calendar/$year/WT
	

#	php get_match_list.php > main_tmp1
