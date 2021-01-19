#!/bin/bash

source ~/.bashrc
source ../conf/base.conf

if [ -f PROGRESS.run.itf_live ]
then
    exit
fi

touch PROGRESS.run.itf_live

year=2021

cat /dev/null > tmp_itf_live

YDAY=`date +%Y-%m-%d`
LDAY=`date -d "$YDAY -1 day" +%Y-%m-%d`
NDAY=`date -d "$YDAY +1 day" +%Y-%m-%d`

php ../src/live.php W-ITF-TUR-01A $year >> tmp_itf_live

cat $SHARE/itf_completed/$LDAY $SHARE/itf_completed/$YDAY $SHARE/itf_completed/$NDAY | awk 'BEGIN {
	FS = OFS = "\t";
}
{
	matchid2eid[$9] = $22;
}
END {
	while ((getline < "tmp_itf_live") > 0) {
		$2 = matchid2eid[$1];
		$22 = matchid2eid[$1];
		for (i = 1; i <= NF; ++i) {
			printf("%s\t", $i);
		}
		printf("\n");
	}
}' > tmp_itf_live2

mv tmp_itf_live2 itf_live
rm tmp_itf_live

rm PROGRESS.run.itf_live
