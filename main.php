<?php

require_once "data.php";
require_once "RouteBuilder.php";

$builder = new RouteBuilder(Cities, Distances);
$builder->build_route('Кагул', 'Дрокия');