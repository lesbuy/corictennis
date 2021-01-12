#!/bin/bash

source ~/.bashrc
source $APP/conf/calc

if [ -f PROGRESS.CALC.ATP ]
then
	exit
fi

touch PROGRESS.CALC.ATP

php ../src/atp_calc.php

rm PROGRESS.CALC.ATP
