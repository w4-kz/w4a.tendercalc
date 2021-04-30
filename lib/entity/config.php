<?php
namespace W4a\Tendercalc\Entity;

use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\IntegerField;
//use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Entity\StringField;
//use Bitrix\Main\Entity\FloatField;
use Bitrix\Main\Localization\Loc;


/**
 * Class ConfigTable
 * @package \W4a\Tendercalc\Entity\Config
 **/

class ConfigTable extends DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'w4a_tendercalc_config';
    }

    /**
     * Returns entity map definition.
     *
     * @return array
     */
    public static function getMap()
    {
        return array(
            new IntegerField('ID', array('primary' => true, 'autocomplete' => true)),
            new StringField('NAME'),
            new StringField('VALUE'),
            new IntegerField('SORT'),
            new StringField('DESCRIPTION'),
        );
    }
}
