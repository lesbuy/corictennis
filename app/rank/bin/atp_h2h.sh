#!/bin/bash

source ~/.bashrc

cat $DATA/activity/atp/* | php ../src/h2h_step1.php atp | sort -t"	" -k1,1 -k2,2 -k3,3 -k4,4 -k5,5 -k20,20 -k21,21 -k22,22 | sort -u -s -t"	" -k1,1 -k2,2 -k3,3 -k4,4 -k5,5 -k20,20 -k21,21 > 1
