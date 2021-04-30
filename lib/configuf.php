<?php

namespace W4a\Tendercalc;

use W4a\Tendercalc\Entity\ConfigUfTable;

class ConfigUf
{

    public static function getTableName()
    {
        return ConfigUfTable::getTableName();
    }

    public static function get($filter=array())
    {
        $result = ConfigUfTable::getList(
            array(
                'select' => array('*'),
                'filter' => $filter
            ));
        return $result->fetchAll();
    }


}
