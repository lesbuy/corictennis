#!/bin/bash

source ~/.bashrc

gender=wta

cat $DATA/activity/$gender/* | php ../src/h2h_step1.php $gender | sort -t"	" -k1,1 -k2,2 -k3,3 -k4,4 -k5,5 -k20,20 -k21,21 -k22,22 | sort -u -s -t"	" -k1,1 -k2,2 -k3,3 -k4,4 -k5,5 -k20,20 -k21,21 | php ../src/h2h_step2.php $gender > $TEMP/${gender}_h2h_detail

mv $TEMP/${gender}_h2h_detail $DATA/h2h/${gender}_detail


cat $DATA/h2h/${gender}_detail | awk -f ../src/h2h_step3.awk > $TEMP/${gender}_h2h_summary
mv $TEMP/${gender}_h2h_summary $DATA/h2h/${gender}_summary


php ../src/h2h_step4.php $gender
