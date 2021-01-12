#!/bin/bash

source ~/.bashrc
source $APP/conf/calc

if [ -f PROGRESS.CALC.WTA ]
then
	exit
fi

touch PROGRESS.CALC.WTA

php ../src/wta_calc.php

rm PROGRESS.CALC.WTA
