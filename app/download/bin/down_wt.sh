#!/bin/bash

source ~/.bashrc

if [ -f PROGRESS.DOWN.WT ]
then
	exit
fi

touch PROGRESS.DOWN.WT

now=`date +%s`

#php ../src/down.php ao
php ../src/down.php atp
php ../src/down.php wta

rm PROGRESS.DOWN.WT
