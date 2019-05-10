<?php

class PriorityQueue extends SplPriorityQueue
{

    function __construct()
    {
        $this->setExtractFlags(3);
    }

    public function find_point($name)
    {
        $copy = clone $this;
        $copy->setExtractFlags(1);

        foreach ($copy as $item) {
            if ($item->name == $name) {
                unset($copy);
                return $item;
            }
        }

        unset($copy);

        return false;
    }


    public function insert_min($elem, $priority)
    {
        $this->insert($elem, -$priority);
    }
}