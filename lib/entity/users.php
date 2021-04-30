<?php

namespace W4a\Tendercalc\Entity;

use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Entity\StringField;
use Bitrix\Main\Entity\DateField;
use Bitrix\Main\Entity\FloatField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\ScalarField;
use Bitrix\Main\UserTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Entity;

class UsersTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'w4a_tendercalc_users';
    }

    public static function getMap(): array
    {
        $userEntity = UserTable::getEntity();
        return array(
            new IntegerField('ID', array('primary' => true, 'autocomplete' => true)),
            new IntegerField('DEAL_ID'),
            new IntegerField('PRODUCTION_USER_ID'), // Сотрудник производство
            new ReferenceField(
                'PRODUCTION_USER',
                $userEntity,
                array('=this.PRODUCTION_USER_ID' => 'ref.ID')
            ),
            new IntegerField('SALES_USER_ID'), // Сотрудник продажи
            new ReferenceField(
                'SALES_USER',
                $userEntity,
                array('=this.SALES_USER_ID' => 'ref.ID')
            ),
            new IntegerField('LOGISTICS_USER_ID'), // Сотрудник производство
            new ReferenceField(
                'LOGISTICS_USER',
                $userEntity,
                array('=this.LOGISTICS_USER_ID' => 'ref.ID')
            ),


            new IntegerField('ASSIGNED_BY_ID'), // ответственный (перспектива)
            new ReferenceField(
                'ASSIGNED_BY',
                UserTable::getEntity(),
                array('=this.ASSIGNED_BY_ID' => 'ref.ID')
            ),

        );
    }

}
