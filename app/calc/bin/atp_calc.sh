#!/bin/bash

source ~/.bashrc
source $APP/conf/calc

if [ -f PROGRESS.CALC.ATP ]
then
	exit
fi

touch PROGRESS.CALC.ATP

php ../src/atp_calc.php

curl "https://www.rank-tennis.com/zh/dc/AO/2021/MS/calcrank" &> /dev/null

rm PROGRESS.CALC.ATP
