#!/bin/awk -f

BEGIN {
	FS = OFS = "\t";
}
$18 !~ /W\/O/{
	p1 = $2;
	p2 = $4;
	if ($1 == "D") {
		p1 = p1 "/" $3;
		p2 = p2 "/" $4;
	}
	win[p1 "\t" p2]++;
	lose[p2 "\t" p1]++;
	all_key[p1 "\t" p2] = 1;
	all_key[p2 "\t" p1] = 1;
}
END {
	for (key in all_key) {
		print key, win[key] + 0, lose[key] + 0;
	}
}
