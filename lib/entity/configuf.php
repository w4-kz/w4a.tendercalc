<?php
namespace W4a\Tendercalc\Entity;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\IntegerField;
//use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Entity\StringField;
//use Bitrix\Main\Entity\FloatField;


/**
 * Class ConfigUfTable
 *
 * @package \W4a\Tendercalc\Entity\ConfigUf
 **/

class ConfigUfTable extends DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'w4a_tendercalc_config_uf';
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
            new StringField('UF_NAME'),
            new StringField('UF_VALUE'),
            new StringField('UF_OWNER_TYPE'),
            new StringField('UF_DESCRIPTION'),
        );
    }
}
