<?php

require_once "data.php";
require_once "RouteBuilder.php";

$builder = new RouteBuilder(Cities, Distances);
$origin = readline('Введите город отправления: ');
$destination = readline('Введите конечный пункт: ');
$builder->build_route($origin, $destination);