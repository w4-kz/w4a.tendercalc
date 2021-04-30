<?php

namespace W4a\Tendercalc;

use W4a\Tendercalc\Entity\ConfigTable;

class Config{

    public static function getTableName() {
        return ConfigTable::getTableName();
    }

    public static function get() {
        $result = ConfigTable::getList(
            array(
                'select' => array('*')
            ));
        return $result->fetch();
    }
    public static function update($id) {
        if(empty($id))
            return false;
        return ConfigTable::update(
            $id,
            array(
                'TITLE' => 'title is updated'
            ));
    }
    public static function getTest() {
        return 'W4a\Tendercalc\Config::getTest(): entity tableName: ' . self::getTableName();
    }

}
