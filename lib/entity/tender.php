<?php

namespace W4a\Tendercalc\Entity;

use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Entity\StringField;
use Bitrix\Main\Entity\DateField;
use Bitrix\Main\Entity\FloatField;
use Bitrix\Main\UserTable;
use Bitrix\Crm\DealTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Entity;

class TenderTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'w4a_tendercalc_tender';
    }

    public static function getMap(): array
    {
        return array(
            new IntegerField('ID', array('primary' => true, 'autocomplete' => true)),
            new IntegerField('DEAL_ID'),
            new DateField('DEADLINE'), // Срок заполнения
            new StringField('CLIENT_NAME'), // Заказчик
            new StringField('USER_NAME'), // Исполнитель
            new StringField('DELIVERY_ADDRESS'), // Адрес доставки
            new StringField('DELIVERY_PERIOD'), // Период поставки
            new StringField('DELIVERY_CONDITIONS'), // УСЛОВИЯ ПОСТАВКИ
            new StringField('DELIVERY_FREQUENCY'), // Периодичность поставки
            new StringField('CONTRACT_WARRANTY_PAYMENT'), // Обеспечение контракта
            new StringField('CONTRACT_PAYMENT'), // Оплата
            new StringField('TENDER_SITE_CONDITIONS'), // Способ размещения заказа, Площадка
            new StringField('MY_COMPANY_NAME'), // Компания от которой заявляемся
            new FloatField('PRICE_NMCK'), // НМЦК (общ. За весь тендер)
            new FloatField('DELIVERY_PRICE'), // Стоимость доставки (руб/кг):
            'IS_COMPLETED' => array(            // Y|N маркер запоненности таблицы
                'data_type' => 'boolean',
                'values' => array('N', 'Y')
            ),

            new IntegerField('ASSIGNED_BY_ID'),
            new ReferenceField(
                'ASSIGNED_BY',
                UserTable::getEntity(),
                array('=this.ASSIGNED_BY_ID' => 'ref.ID')
            ),

        );
    }

}
