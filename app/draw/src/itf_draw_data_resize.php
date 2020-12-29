<?php

$content = "";

while ($line = trim(fgets(STDIN))) {
	if (strpos($line, "\"StartDateAndTime\"") !== false) continue;
	else if (strpos($line, "\"EndDateAndTime\"") !== false) continue;
	else if (strpos($line, "\"DrawsheetPosition\"") !== false) continue;
	else if (strpos($line, "\"st_event_id\"") !== false) continue;
	else if (strpos($line, "\"st_match_id\"") !== false) continue;
	else if (strpos($line, "\"created\"") !== false) continue;
	else if (strpos($line, "\"updated\"") !== false) continue;
	else if (strpos($line, "\"RoundNumber\"") !== false) continue;
	else if (strpos($line, "\"IsLiveScoringProvided\"") !== false) continue;
	else if (strpos($line, "null") !== false) continue;
	else if (strpos($line, "\"maxUpdated\"") !== false) continue;
	else if (strpos($line, "\"lastlivetime\"") !== false) continue;

	$content .= str_replace("Side", "S", str_replace("Player", "P", str_replace("Score", "Sc", str_replace("TieBreak", "TB", $line))));
}
echo json_encode(json_decode($content, true)) . "\n";
