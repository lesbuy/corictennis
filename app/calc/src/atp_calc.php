<?php
require_once('calc.php');

$calc = new Calc('atp', 's', 'year');
$calc->output_rank_list();

$calc = new Calc('atp', 'd', 'year');
$calc->output_rank_list();
