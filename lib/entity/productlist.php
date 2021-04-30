<?php

namespace W4a\Tendercalc\Entity;

use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Entity\StringField;
use Bitrix\Main\Entity\DateField;
use Bitrix\Main\Entity\FloatField;
use Bitrix\Main\UserTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Entity;

class ProductListTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'w4a_tendercalc_products';
    }

    public static function getMap(): array
    {
        return array(
            new IntegerField('ID', array('primary' => true, 'autocomplete' => true)),
            new IntegerField('DEAL_ID'),
            new IntegerField('PRODUCT_ID'),
            new StringField('PRODUCT_NAME_ORIG'),
            new StringField('PRODUCT_NAME_SPEC'),
            new DateField('DELIVERY_DATE'),
            new StringField('DELIVERY_ADDRESS'),
            new IntegerField('MEASURE_ID'),
            new FloatField('QUANTITY_REQUEST'),
            new StringField('PACKING'),
            new FloatField('QUANTITY'),
            new FloatField('PRICE_PURCHASE'),
            new FloatField('PROFIT_RATIO'),
            new FloatField('PRICE_NMCK'),
            new FloatField('PRICE_IN_SPECIAL'),
            new FloatField('PRICE_IN_DISTRIBUTOR'),
            new FloatField('PROFIT_RATIO_SPECIAL'),
            new FloatField('PROFIT_RATIO_DISTRIBUTOR'),



           /* new IntegerField('ASSIGNED_BY_ID'),
            new ReferenceField(
                'ASSIGNED_BY',
                UserTable::getEntity(),
                array('=this.ASSIGNED_BY_ID' => 'ref.ID')
            ),*/
        );
    }

}
