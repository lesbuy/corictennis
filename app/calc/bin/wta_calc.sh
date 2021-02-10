#!/bin/bash

source ~/.bashrc
source $APP/conf/calc

if [ -f PROGRESS.CALC.WTA ]
then
	exit
fi

touch PROGRESS.CALC.WTA

php ../src/wta_calc.php

curl "https://www.rank-tennis.com/zh/dc/AO/2021/WS/calcrank" &> /dev/null

rm PROGRESS.CALC.WTA
