#!/bin/bash

source ~/.bashrc

gender=atp

cat $DATA/calc/$gender/s/year/unloaded $DATA/calc/$gender/s/year/loaded $DATA/calc/$gender/d/year/unloaded $DATA/calc/$gender/d/year/loaded $DATA/rank/$gender/s/current $DATA/rank/$gender/d/current | cut -f1 | sort -u | grep -v "^#" | grep -v -E "^[0-9]{7,9}$" > $DATA/${gender}_bio_down_list
php ../src/down_bio.php $gender

redis keys ${gender}_profile_* | while read line
do
	pid=${line/${gender}_profile_/}
	redis hmget $line first last ioc birthday l_en s_en l_zh s_zh | awk -F"\t" -v pid=$pid 'BEGIN{OFS="\t"}FNR == 1{first=$0}FNR == 2{last=$0}FNR == 3{ioc=$0}FNR == 4{birthday=$0}FNR == 5{l_en=$0}FNR == 6{s_en=$0}FNR == 7{l_zh=$0}FNR == 8{s_zh=$0}END{print pid, first, last, ioc, birthday, l_en, s_en, l_zh, s_zh}'
done | sort -k1,1 > $TEMP/${gender}_player
mv $TEMP/${gender}_player $DATA/${gender}_player
