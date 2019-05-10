<?php

require_once "PriorityQueue.php";

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

/**
 * Class RoutePoint represents city as a part of route
 *
 * @property string $name city name
 * @property int $to_origin - direct distance to route origin city
 * @property int $to_destination - direct distance to route destination
 * @property mixed $prev - link to previous city (== null if RoutePoint is origin city)
 *
 */
class RoutePoint
{
    public $name;
    public $to_origin;
    public $to_destination;
    public $prev;

    function __construct($name, $prev, $to_origin, $to_destination)
    {
        $this->name = $name;
        $this->prev = $prev;
        $this->to_origin = $to_origin;
        $this->to_destination = $to_destination;
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
        if (!isset(Transliteration::$names[$origin]) || !isset(Transliteration::$names[$destination])) {
            echo "No such cities on our map :(\n";

            return false;
        }

        $origin = Transliteration::$names[$origin];
        $destination = Transliteration::$names[$destination];

        $this->output_route_header($origin, $destination, $output_rus);

        $greedy_route = $this->build_route_start($origin, $destination);

        if (!$greedy_route) {
            echo "There's no such route :/\n";
            return false;
        }

        if (!is_array($greedy_route)) {
            echo "You're already there :D\n";
            return false;
        }

        echo " Жадный алгоритм: \n\n";
        $this->output_route($greedy_route);

        $a_route = $this->convert_to_array($this->build_route_a($origin, $destination));

        echo " Алгоритм А* \n\n";
        $this->output_route($a_route);

        return true;
    }

    function output_route_header($origin, $destination, $output_rus = true)
    {
        echo "\n " . ($output_rus ? Transliteration::get_rus_name($origin) : $origin) . ' ⇨ ' .
            ($output_rus ? Transliteration::get_rus_name($destination) : $destination) . "\n\n";

    }

    function output_route($route, $output_rus = true)
    {
        $num = count($route);

        $km = $output_rus ? 'км' : 'km';
        $total = $output_rus ? 'Всего' : 'In total';

        $total_dist = 0;

        for ($i = 0; $i < $num; $i++) {
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

        return $this->build_route_step($origin, $destination, [$origin], []);
    }

    /**
     * @param string $origin
     * @param string $destination
     * @param array $route - array of cities names
     * @param array $dead_end
     * @return array
     */
    function build_route_step($origin, $destination, $route, $dead_end)
    {
        $min = INF;
        $next_city = null;

        if (!isset($this->distances['by_car'][$origin])) {
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

//    ---

    /**
     * @param string $origin
     * @param string $destination
     * @return mixed
     */
    function build_route_a($origin, $destination)
    {
        if ($origin == $destination) return $origin;

        $closed = new PriorityQueue();
        $opened = new PriorityQueue();

        $curr = new RoutePoint($origin, null, 0, $this->get_direct_distance($destination, $origin));
        $opened->insert_min($curr, $curr->to_origin + $curr->to_destination);

        return $this->build_route_a_step($origin, $destination, $closed, $opened);
    }

    /**
     * @param $origin
     * @param $destination
     * @param PriorityQueue $closed
     * @param PriorityQueue $opened
     * @return mixed
     */
    function build_route_a_step($origin, $destination, $closed, $opened)
    {
        $curr = $opened->extract();
        $curr_dist = -$curr['priority'];
        $closed->insert($curr['data'], -$curr_dist);
        /**
         * @var RoutePoint $curr_city
         */
        $curr_city = $curr['data'];
        $curr_name = $curr_city->name;

        foreach ($this->distances['by_car'][$curr_name] as $city => $dist) {

            $to_origin = $this->get_direct_distance($city, $curr_name) + $curr_city->to_origin;
            $to_destination = $this->get_direct_distance($city, $destination);

            /**
             * @var RoutePoint $point
             */
            if ($point = $closed->find_point($city)) {
                continue;
            } else if ($point = $opened->find_point($city)) {
                if ($point->to_origin > $to_origin) {
                    $point->to_origin = $to_origin;
                    $point->prev = $curr_city;
                }
            } else {
                $opened->insert_min(new RoutePoint($city, $curr_city, $to_origin, $to_destination),
                    $to_origin + $to_destination);
            }
        }

        if ($opened->isEmpty() || ($curr_city->name == $destination && -$opened->top()['priority'] > $curr_dist)) {
            return $curr_city;
        }

        return $this->build_route_a_step($origin, $destination, $closed, $opened);
    }

    function convert_to_array($route_list)
    {
        $arr = [];

        $point = $route_list;

        while ($point != null) {
            array_unshift($arr, $point->name);
            $point = $point->prev;
        }

        unset ($route_list);
        return $arr;
    }
}