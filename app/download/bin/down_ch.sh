#!/bin/bash

source ~/.bashrc

if [ -f PROGRESS.DOWN.CH ]
then
	exit
fi

touch PROGRESS.DOWN.CH

now=`date +%s`

php ../src/down.php ch

rm PROGRESS.DOWN.CH
