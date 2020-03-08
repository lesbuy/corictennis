#!/bin/bash

source ~/.bashrc

#从redis查出所有的pid
redis keys atp_profile_* | sed 's/atp_profile_//g' > tmp_list_atp_activity
#列出所有已经下载的pid
ls $DATA/activity/atp > tmp_downloaded_atp_activity
#查出所有新加的还没有下载的pid
awk -F"\t" 'ARGIND == 1 {a[$1] = 1} ARGIND == 2 {if (a[$1] == "") print}' tmp_downloaded_atp_activity tmp_list_atp_activity > tmp_new_atp_activity
#上次下载失败的pid
grep ERROR err_atp_activity | cut -f2 >> tmp_new_atp_activity

sort -u tmp_new_atp_activity > list_atp_activity

rm tmp_*_atp_activity
