#!/bin/bash

source ~/.bashrc
source ../conf/conf.sh

# 获取年历
curl "http://ws.protennislive.com/LiveScoreSystem/F/Long/GetCalendarCrypt.aspx" 2> /dev/null | php $SHARE/tools/get_whole.php > c_tmp1
size=`ls -l c_tmp1 | awk '{print $5}'`
if [ $size -gt 10 ]
then
	mv c_tmp1 $TEMP/calendar_decrypt
fi
curl "http://ws.protennislive.com/LiveScoreSystem/M/Long/GetCalendar.aspx?lang=zh" 2> /dev/null > c_tmp1
size=`ls -l c_tmp1 | awk '{print $5}'`
if [ $size -gt 10 ]
then
	mv c_tmp1 $TEMP/calendar_decrypt_m
fi
curl "http://ws.protennislive.com/LiveScoreSystem/F/long/GetConfigCrypt.aspx?intYear=2020&strWeekGroup=99&strLangId=zh-Hans" 2> /dev/null | php $SHARE/tools/get_whole.php > c_tmp1
size=`ls -l c_tmp1 | awk '{print $5}'`
if [ $size -gt 10 ]
then
	mv c_tmp1 $TEMP/config_decrypt
fi

php ../src/down_calendar.php $year | sort -t"	" -k7g,7 -k21gr,21 -k2,2 > $TEMP/tmp_calender1
php ../src/down_ch.php $year | sort -t"	" -k7g,7 -k21gr,21 -k2,2 > $TEMP/tmp_calender2

#cat $STORE/calendar/$year/WT $TEMP/tmp_calender1 | sort -u -s -k1,1 -k2,2 | sort -s -t"	" -k7g,7 -k21gr,21 -k2,2 > $TEMP/tmp_calender11
cat $STORE/calendar/$year/CH $TEMP/tmp_calender2 | sort -u -s -k1,1 -k2,2 | sort -s -t"	" -k7g,7 -k21gr,21 -k2,2 > $TEMP/tmp_calender22

#	awk -f reducer.awk tmp/tmp_calender2 > tmp/tmp_calender1

#	mv tmp/tmp_calender1 $ROOT_PATH/$STORE_PATH/calendar/$year/WT
	

#	php get_match_list.php > main_tmp1
