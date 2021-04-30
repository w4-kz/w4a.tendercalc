<?php

use W4a\Tendercalc\Entity\ConfigTable;

class CW4aTendercalcTest{

    public static function getTableName() {
        return  ConfigTable::getTableName();
    }

    public static function get() {
        $result = ConfigTable::getList(
            array(
                'select' => array('*')
            ));
        return $result->fetch();
    }
    public static function debug($arData,$arName='$arData') {
        echo "<div>$arName</div>";
        echo '<pre>';
        print_r($arData);
        echo '</pre>';
    }
    public static function getTest() {
        return 'CW4aTendercalcTest::getTest(): entity555 tableName: ' . self::getTableName();
    }


}
