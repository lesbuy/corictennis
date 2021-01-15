#!/bin/bash

source ~/.bashrc
source $APP/conf/calc

# 当前赛事起始日是当前已经下载的activity日期的下一周
curr_start=`date -d "$wta_activity_end +7 day" +%Y-%m-%d`

curr=`date -d "$curr_start" +%Y%m%d`
live=`date -d "$wta_liveranking_end" +%Y%m%d`

awk -F"\t" -v curr=$curr -v live=$live '$15 == "s" && $8 >= curr && $8 <= live' $DATA/activity_current/wta/* $DATA/calc/wta/s/mandatory0 > $DATA/calc/wta/s/year/unloaded
awk -F"\t" -v curr=$curr -v live=$live '$15 == "d" && $8 >= curr && $8 <= live' $DATA/activity_current/wta/*  > $DATA/calc/wta/d/year/unloaded

awk -F"\t" -v curr=$curr -v live=$live '$15 == "s" && $8 > live' $DATA/activity_current/wta/*  > $DATA/calc/wta/s/year/comingup
awk -F"\t" -v curr=$curr -v live=$live '$15 == "d" && $8 > live' $DATA/activity_current/wta/*  > $DATA/calc/wta/d/year/comingup

awk -F"\t" -v curr=$curr -v live=$live '$31 >= curr' $DATA/h2h_current/wta/*  > $DATA/h2h/wta_detail_current
