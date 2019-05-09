<?php

abstract class Transliteration
{
    public static $names = [
        'Кишинёв' => 'Kishinev',
        'Бельцы' => 'Belci',
        'Тирасполь' => 'Tiraspol',
        'Бендеры' => 'Bendery',
        'Рыбница' => 'Rybniza',
        'Кагул' => 'Kahul',
        'Унгень' => 'Unhen',
        'Сороки' => 'Soroca',
        'Орхей' => 'Orhey',
        'Комрат' => 'Komrat',
        'Дубоссары' => 'Dubossary',
        'Чадыр-Лунга' => 'Chadyr-Lunha',
        'Страшены' => 'Strasheny',
        'Дрокия' => 'Drokiya',
        'Каушаны' => 'Kaushany'
    ];

    public static function get_rus_name($eng)
    {
        return array_search($eng, Transliteration::$names);
    }
}

class RouteBuilder
{
    public $distances;
    public $cities;

    function __construct($cities, $distances)
    {
        $this->cities = $cities;
        $this->distances = $distances;
    }

//    ---

    function get_direct_distance($city1, $city2, $is_rus = false)
    {
        if (!$is_rus) {
            $city1 = Transliteration::get_rus_name($city1);
            $city2 = Transliteration::get_rus_name($city2);
        }

        $pos1 = array_search($city1, $this->cities);
        $pos2 = array_search($city2, $this->cities);

        return $this->distances['direct'][$pos1][$pos2];
    }

//    ---

    function build_route($origin, $destination, $output_rus = true)
    {
        $route = $this->build_route_start($origin, $destination);

        echo "\n $origin  ⇨  $destination\n\n";

        $km = $output_rus ? 'км' : 'km';
        $total = $output_rus ? 'Всего' : 'In total';

        if (!$route) {
            echo "There's no such route :/\n";
            return;
        }

        if (!is_array($route)) {
            echo "You're already there :D\n";
            return;
        }

        $total_dist = 0;

        for ($i = 0, $num = count($route); $i < $num; $i++) {
            $city = $route[$i];
            $name = $output_rus ? Transliteration::get_rus_name($city) : $city;

            if ($i == 0 || $i == $num - 1) {
                echo " ⦿ ";
            } else {
                echo " ⦾ ";
            }

            echo $name . "\n";

            if ($i !== count($route) - 1) {
                $dist = $this->distances['by_car'][$city][$route[$i + 1]];
                $total_dist += $dist;

                echo " ◦ \n ◦ $dist$km\n ◦\n";
            } else {
                echo "\n";
            }
        }

        echo " $total: $total_dist$km\n\n";
    }

    function build_route_start($origin, $destination)
    {
        if ($origin == $destination) return $origin;

        return $this->build_route_step(Transliteration::$names[$origin], Transliteration::$names[$destination],
            [Transliteration::$names[$origin]], []);
    }

    function build_route_step($origin, $destination, $route, $dead_end)
    {
        $min = INF;
        $next_city = null;

        if (!$this->distances['by_car'][$origin]) {
            array_pop($route); // deleting null
            $dead_end [] = array_pop($route); // moving dead-end city
            $origin = $route[count($route) - 1];
        }

        foreach ($this->distances['by_car'][$origin] as $city => $dist) {
            if ($city == $destination) {
                $route [] = $city;
                return $route;
            } else if (array_search($city, $route) !== false || array_search($city, $dead_end) !== false) {
                continue;
            }

            $direct = $this->get_direct_distance($city, $destination);

            if ($min > $direct) {
                $min = $direct;
                $next_city = $city;
            }
        }

        $route [] = $next_city;

        return $this->build_route_step($next_city, $destination, $route, $dead_end);
    }

}