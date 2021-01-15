#!/bin/bash

source ~/.bashrc
source $APP/conf/calc

# 当前赛事起始日是当前已经下载的activity日期的下一周
curr_start=`date -d "$atp_activity_end +7 day" +%Y-%m-%d`

curr=`date -d "$curr_start" +%Y%m%d`
live=`date -d "$atp_liveranking_end" +%Y%m%d`

awk -F"\t" -v curr=$curr -v live=$live '$15 == "s" && $8 >= curr && $8 <= live' $DATA/activity_current/atp/* $DATA/calc/atp/s/mandatory0 > $DATA/calc/atp/s/year/unloaded
awk -F"\t" -v curr=$curr -v live=$live '$15 == "d" && $8 >= curr && $8 <= live' $DATA/activity_current/atp/*  > $DATA/calc/atp/d/year/unloaded

awk -F"\t" -v curr=$curr -v live=$live '$15 == "s" && $8 > live' $DATA/activity_current/atp/*  > $DATA/calc/atp/s/year/comingup
awk -F"\t" -v curr=$curr -v live=$live '$15 == "d" && $8 > live' $DATA/activity_current/atp/*  > $DATA/calc/atp/d/year/comingup

awk -F"\t" -v curr=$curr -v live=$live '$31 >= curr' $DATA/h2h_current/atp/*  > $DATA/h2h/atp_detail_current
