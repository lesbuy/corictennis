#!/bin/bash

source ~/.bashrc

#从redis查出所有的pid
redis keys wta_profile_* | sed 's/wta_profile_//g' > tmp_list_wta_activity
#列出所有已经下载的pid
ls $DATA/activity/wta > tmp_downloaded_wta_activity
#查出所有新加的还没有下载的pid
awk -F"\t" 'ARGIND == 1 {a[$1] = 1} ARGIND == 2 {if (a[$1] == "") print}' tmp_downloaded_wta_activity tmp_list_wta_activity > tmp_new_wta_activity
#上次下载失败的pid
grep ERROR err_wta_activity | cut -f2 >> tmp_new_wta_activity

sort -u tmp_new_wta_activity > list_wta_activity

rm tmp_*_wta_activity
