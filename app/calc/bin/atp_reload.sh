#!/bin/bash

source ~/.bashrc
source $APP/conf/calc

# 算分起始日（以下日期都是记分日期）
calc_start=`date -d "$atp_liveranking_end -357 days" +%Y-%m-%d`
calc_start="2019-03-11"  # 临时操作
itvl=$((atp_this_weeks*7))
itvl=0  #临时操作
# 掉分起始日为算分起始日往前推N周，N为本站赛事持续周数
flop_start=`date -d "$calc_start -$itvl days" +%Y-%m-%d`

# keep_start表示从此日开始的积分全记，在这个之前的积分只记一半
keep_start=`date -d "$atp_liveranking_end -721 days" +%Y-%m-%d`

# 但是如果掉分起始日前一周没有官方排名，则掉分起始日提前一周
flop_start_last_monday=`date -d "$flop_start -7 days" +%Y-%m-%d`
if [ ! -f $DATA/rank/atp/s/history/$flop_start_last_monday ]
then
	flop_start=$flop_start_last_monday
fi
# 如果算分起始日前一周没有官方排名，则算分起始日提前一周
calc_start_last_monday=`date -d "$calc_start -7 days" +%Y-%m-%d`
if [ ! -f $DATA/rank/atp/s/history/$calc_start_last_monday ]
then
	calc_start=$calc_start_last_monday
fi

# 当前赛事起始日是当前已经下载的activity日期的下一周
curr_start=`date -d "$atp_activity_end +7 day" +%Y-%m-%d`

echo FLOP from $flop_start to $calc_start \(excluded\)
echo DOWN from $calc_start to $curr_start \(excluded\)
echo NNEW from $curr_start to $atp_liveranking_end \(included\)

flop=`date -d "$flop_start" +%Y%m%d`
calc=`date -d "$calc_start" +%Y%m%d`
curr=`date -d "$curr_start" +%Y%m%d`
live=`date -d "$atp_liveranking_end" +%Y%m%d`
keep=`date -d "$keep_start" +%Y%m%d`

echo $flop $calc $curr $live

awk -F"\t" -v flop=$flop -v calc=$calc -v keep=$keep '$15 == "s" && $8 >= flop && $8 < keep {$22 = $22 / 2; printf("%s", $1); for (i = 2; i <= NF; ++i) printf("\t%s", $i); printf("\n");}' $DATA/activity/atp/* $DATA/calc/atp/s/mandatory0 > $DATA/calc/atp/s/year/flop
awk -F"\t" -v curr=$curr -v calc=$calc -v keep=$keep '$15 == "s" && $8 >= keep && $8 < curr {print;} $15 == "s" && $8 >= calc && $8 < keep {$22 = $22 / 2; printf("%s", $1); for (i = 2; i <= NF; ++i) printf("\t%s", $i); printf("\n");}' $DATA/activity/atp/* $DATA/calc/atp/s/mandatory0 > $DATA/calc/atp/s/year/loaded

awk -F"\t" -v flop=$flop -v calc=$calc -v keep=$keep '$15 == "d" && $8 >= flop && $8 < keep {$22 = $22 / 2; printf("%s", $1); for (i = 2; i <= NF; ++i) printf("\t%s", $i); printf("\n");}' $DATA/activity/atp/*  > $DATA/calc/atp/d/year/flop
awk -F"\t" -v curr=$curr -v calc=$calc -v keep=$keep '$15 == "d" && $8 >= keep && $8 < curr {print;} $15 == "d" && $8 >= calc && $8 < keep {$22 = $22 / 2; printf("%s", $1); for (i = 2; i <= NF; ++i) printf("\t%s", $i); printf("\n");}' $DATA/activity/atp/*  > $DATA/calc/atp/d/year/loaded
