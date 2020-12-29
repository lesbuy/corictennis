<?php
require_once('calc.php');

$calc = new Calc('wta', 's', 'year');
$calc->output_rank_list();

$calc = new Calc('wta', 'd', 'year');
$calc->output_rank_list();
