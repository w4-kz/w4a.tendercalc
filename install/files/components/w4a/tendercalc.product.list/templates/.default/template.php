<?php
defined('B_PROLOG_INCLUDED') || die;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Grid\Panel\Snippet;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Web\Json;

/** @var CBitrixComponentTemplate $this */

if (!Loader::includeModule('crm')) {
    ShowError(Loc::getMessage('CRMTENDERS_NO_CRM_MODULE'));
    return;
}
if (isset($_REQUEST["IFRAME"]) && $_REQUEST["IFRAME"] === "Y"){
    // подгружаем HEADER страницы в слайдере
    $APPLICATION->ShowHead();
}
\Bitrix\Main\UI\Extension::load("ui.forms");
\Bitrix\Main\UI\Extension::load("ui.buttons.icons");
\Bitrix\Main\UI\Extension::load("ui.alerts");

CJSCore::Init(array("jquery"));
$asset = Asset::getInstance();
$asset->addJs('/bitrix/js/crm/interface_grid.js');
$gridManagerId = $arResult['GRID_ID'] . '_MANAGER';
$arParams['VARIABLES']['USER_ROLES']['IS_ADMIN'] = 'Y';
$arElementIDs = array();
$rows = array();
$actions = array();
foreach ($arResult['TENDER_PRODUCTS'] as $product) {

    $viewUrl = CComponentEngine::makePathFromTemplate(
        $arParams['URL_TEMPLATES']['DETAIL'],
        array('TENDER_ID' => $product['ID'])
    );
    $editUrl = CComponentEngine::makePathFromTemplate(
        $arParams['URL_TEMPLATES']['EDIT'],
        array('TENDER_ID' => $product['ID'])
    );

    $deleteUrlParams = http_build_query(array(
        'action_button_' . $arResult['GRID_ID'] => 'delete',
        'ID' => array($product['ID']),
        'sessid' => bitrix_sessid()
    ));
    $deleteUrl = $arParams['SEF_FOLDER'] . '?' . $deleteUrlParams;
    if($arParams['VARIABLES']['USER_ROLES']['IS_ADMIN'] == 'Y')
    {
        $actions = array(
            array(
                'TITLE' => Loc::getMessage('CRMTENDERS_ACTION_VIEW_TITLE'),
                'TEXT' => Loc::getMessage('CRMTENDERS_ACTION_VIEW_TEXT'),
                'ONCLICK' => 'BX.Crm.Page.open(' . Json::encode($viewUrl) . ')',
                'DEFAULT' => true
            ),
            array(
                'TITLE' => Loc::getMessage('CRMTENDERS_ACTION_EDIT_TITLE'),
                'TEXT' => Loc::getMessage('CRMTENDERS_ACTION_EDIT_TEXT'),
                'ONCLICK' => 'BX.Crm.Page.open(' . Json::encode($editUrl) . ')',
            ),
            array(
                'TITLE' => Loc::getMessage('CRMTENDERS_ACTION_DELETE_TITLE'),
                'TEXT' => Loc::getMessage('CRMTENDERS_ACTION_DELETE_TEXT'),
                'ONCLICK' => 'BX.CrmUIGridExtension.processMenuCommand(' . Json::encode($gridManagerId) . ', BX.CrmUIGridMenuCommand.remove, { pathToRemove: ' . Json::encode($deleteUrl) . ' })',
            )
        );
    }
    elseif($arParams['VARIABLES']['USER_ROLES']['IS_TENDER_MANAGER'] == 'Y')
    {
        $actions = array(
            array(
                'TITLE' => Loc::getMessage('CRMTENDERS_ACTION_VIEW_TITLE'),
                'TEXT' => Loc::getMessage('CRMTENDERS_ACTION_VIEW_TEXT'),
                'ONCLICK' => 'BX.Crm.Page.open(' . Json::encode($viewUrl) . ')',
                'DEFAULT' => true
            ),
        );
    }
    elseif($arParams['VARIABLES']['USER_ROLES']['IS_EXT_TENDER_USER'] == 'Y')
    {
        $actions = array(
            array(
                'TITLE' => Loc::getMessage('CRMTENDERS_ACTION_VIEW_TITLE'),
                'TEXT' => Loc::getMessage('CRMTENDERS_ACTION_VIEW_TEXT'),
                'ONCLICK' => 'BX.Crm.Page.open(' . Json::encode($viewUrl) . ')',
                'DEFAULT' => true
            ),
        );
    }
    $DELIVERY_TIME = is_object($product['DELIVERY_DATE'])
            ?ConvertDateTime(
                    $product['DELIVERY_DATE']->toString(),
                    "YYYY-MM-DD",
                    "ru"):($product['DELIVERY_DATE']);

    /** ЗАКУП ДИСТРИБЬЮТОРСКАЯ + СТОИМОСТЬ ДОСТАВКИ */
    // ЦЕНА ЗАКУПА/ДИСТРИБ  (РУБ с НДС за ЕД.)
    $priceInDistrib = $product['PRICE_IN_DISTRIBUTOR'];
    // СПЕЦЦЕНА ЗАКУПА (РУБ С НДС за ЕД.ИЗМЕРЕНИЯ)
    $priceInSpecial = $product['PRICE_IN_SPECIAL'];
    // КОЛИЧЕСТВО
    $qty = $product['QUANTITY'];
    // ФАСОВКА
    $packing = $product['PACKING'];
    // Стоимость доставки (руб/кг):
    $deliveryPrice = $arResult['TENDER']['DELIVERY_PRICE'];
    // Наценка расчетная (по умолчанию)
    $profitRatioDefault = floatval($arResult['CONFIG']['TENDERCALC_PROFIT_RATIO_DEFAULT']);
    // Ставка НДС
    $nds = floatval($arResult['CONFIG']['TENDERCALC_NDS']);

/** region Calculation Data */
    // ЗАКУП ДИСТРИБЬЮТОРСКАЯ + СТОИМОСТЬ ДОСТАВКИ
        // Сумма зак/дистр, с НДС
        $priceInDistribSum = ($priceInDistrib * $qty);
        // Доставка ед.
        $priceInDistribDelivery = ($deliveryPrice * $packing);
        // Доставка сумма
        $priceInDistribDeliverySum = ($priceInDistribDelivery * $qty);
        // Цена с учетом доставки с ндс за ед.
        $priceInDistribWithDeliverySum = ($priceInDistrib + $priceInDistribDelivery);
        // Сумма закуп+доставка до клиента (с ндс)
        $priceInDistribTotal = ($priceInDistribSum + $priceInDistribDeliverySum);

    // ЗАКУП СПЕЦЦЕНА + СТОИМОСТЬ ДОСТАВКИ
        // Сумма зак/дистр, с НДС
        $priceInSpecialSum = ($priceInSpecial * $qty);
        // Доставка ед.
        $priceInSpecialDelivery = ($deliveryPrice * $packing);
        // Доставка сумма
        $priceInSpecialDeliverySum = ($priceInSpecialDelivery * $qty);
        // Цена с учетом доставки с ндс за ед.
        $priceInSpecialWithDeliverySum = ($priceInSpecial + $priceInSpecialDelivery);
        // Сумма закуп+доставка до клиента (с ндс)
        $priceInSpecialTotal = ($priceInSpecialSum + $priceInSpecialDeliverySum);

    // ПРОДАЖА ПО ДИСТРИБЬЮТОРСКОЙ ЦЕНЕ
        // Наценка расчетная
        $profitRatioDistrib = empty(intval($product['PROFIT_RATIO_DISTRIBUTOR']))
            ?$profitRatioDefault
            :floatval($product['PROFIT_RATIO_DISTRIBUTOR']);
        // Цена за ед. с НДС
        $priceOutDistrib = $priceInDistrib * $profitRatioDistrib + $priceInDistribDelivery;
        // Сумма с НДС
        $priceOutDistribSum = $priceOutDistrib * $qty;
        // Вал. прибыль, БЕЗ НДС
        $priceOutDistribGrossSum = ($priceOutDistribSum - $priceInDistribTotal) / ((100 + $nds)/100);
        // %
        $priceOutDistribGrossPercent = empty($priceInDistribTotal)
            ?0.0
            :($priceOutDistribGrossSum / ($priceInDistribTotal/ ((100 + $nds)/100)));
    // ПРОДАЖА ПО СПЕЦЦЕНЕ ЦЕНЕ
        // Наценка расчетная
            $profitRatioSpecial = empty(intval($product['PROFIT_RATIO_SPECIAL']))
                ?$profitRatioDefault
                :floatval($product['PROFIT_RATIO_SPECIAL']);
        // Цена за ед. с НДС
        $priceOutSpecial = $priceInSpecial * $profitRatioSpecial + $priceInSpecialDelivery;
        // Сумма с НДС
        $priceOutSpecialSum = $priceOutSpecial * $qty;
        // Вал. прибыль, БЕЗ НДС
        $priceOutSpecialGrossSum = ($priceOutSpecialSum - $priceInSpecialTotal) / ((100 + $nds)/100);
        // %
        $priceOutSpecialGrossPercent = empty($priceInSpecialTotal)
            ?0.0
            :($priceOutSpecialGrossSum / ($priceInSpecialTotal/ ((100 + $nds)/100)));
/** endregion Calculation Data */

    $rows[] = array(
        'id' => $product['ID'],
        'actions' => $actions,
        'data' => $product,
        'columns' => array(
            'ID' => $product['ID'],
            'PRODUCT_NAME_ORIG' => Loc::getMessage(
                'CRMTENDERS_GRID_ELEMENT_INPUT_PRODUCT_NAME_ORIG_DISABLED',
                array(
                    '#VALUE#'=>$product['PRODUCT_NAME_ORIG'],
                    '#NAME#'=>'PRODUCT_NAME_ORIG',
                    '#ID#'=>$product['ID'],
                    '#TYPE#' => '',
                    '#PRODUCT_ID#' => intval($product['PRODUCT_ID']),
                    '#INPUT_ID#' => strtolower('product_' . $product['ID']),
                )
            ),
            'PRODUCT_NAME_SPEC' => Loc::getMessage(
                'CRMTENDERS_GRID_ELEMENT_INPUT_DISABLED',
                array(
                    '#VALUE#'=>$product['PRODUCT_NAME_SPEC'],
                    '#NAME#'=>'PRODUCT_NAME_SPEC',
                    '#ID#'=>$product['ID'],
                    '#TYPE#' => '',
                )
            ),
            'DELIVERY_DATE' => Loc::getMessage(
                'CRMTENDERS_GRID_ELEMENT_DATE_DISABLED',
                array(
                    '#VALUE#'=>$DELIVERY_TIME,
                    '#NAME#'=>'DELIVERY_DATE',
                    '#ID#'=>$product['ID'],
                    '#TYPE#' => '',
                )
            ),
            'DELIVERY_ADDRESS' => Loc::getMessage(
                'CRMTENDERS_GRID_ELEMENT_INPUT_DISABLED',
                array(
                    '#VALUE#'=>$product['DELIVERY_ADDRESS'],
                    '#NAME#'=>'DELIVERY_ADDRESS',
                    '#ID#'=>$product['ID'],
                    '#TYPE#' => '',
                )
            ),
            'MEASURE' => Loc::getMessage(
                'CRMTENDERS_GRID_ELEMENT_INPUT_DISABLED',
                array(
                    '#VALUE#'=>$product['MEASURE'],
                    '#NAME#'=>'MEASURE',
                    '#ID#'=>$product['ID'],
                    '#TYPE#' => '',
                )
            ),
            'QUANTITY_REQUEST' => Loc::getMessage(
                'CRMTENDERS_GRID_ELEMENT_INPUT_DISABLED',
                array(
                    '#VALUE#'=>$product['QUANTITY_REQUEST'],
                    '#NAME#'=>'QUANTITY_REQUEST',
                    '#ID#'=>$product['ID'],
                    '#TYPE#' => '',
                )
            ),
            'PACKING' => Loc::getMessage(
                'CRMTENDERS_GRID_ELEMENT_INPUT_DISABLED',
                array(
                    '#VALUE#'=>$product['PACKING'],
                    '#NAME#'=>'PACKING',
                    '#ID#'=>$product['ID'],
                    '#TYPE#' => '',
                )
            ),
            'QUANTITY' => Loc::getMessage(
                'CRMTENDERS_GRID_ELEMENT_INPUT_DISABLED',
                array(
                    '#VALUE#'=>$product['QUANTITY'],
                    '#NAME#'=>'QUANTITY',
                    '#ID#'=>$product['ID'],
                    '#TYPE#' => '',
                )
            ),
            'PRICE_PURCHASE' => Loc::getMessage(
                'CRMTENDERS_GRID_ELEMENT_INPUT_DISABLED',
                array(
                    '#VALUE#'=>$product['PRICE_PURCHASE'],
                    '#NAME#'=>'PRICE_PURCHASE',
                    '#ID#'=>$product['ID'],
                    '#TYPE#' => '',
                )
            ),
            'PROFIT_RATIO' => Loc::getMessage(
                'CRMTENDERS_GRID_ELEMENT_INPUT_DISABLED',
                array(
                    '#VALUE#'=>$product['PROFIT_RATIO'],
                    '#NAME#'=>'PROFIT_RATIO',
                    '#ID#'=>$product['ID'],
                    '#TYPE#' => '',
                )
            ),
            'PRICE_NMCK' => Loc::getMessage(
                'CRMTENDERS_GRID_ELEMENT_INPUT_DISABLED',
                array(
                    '#VALUE#'=>$product['PRICE_NMCK'],
                    '#NAME#'=>'PRICE_NMCK',
                    '#ID#'=>$product['ID'],
                    '#TYPE#' => '',
                )
            ),
            // Спец. цена
            'PRICE_IN_SPECIAL' => Loc::getMessage(
                'CRMTENDERS_GRID_ELEMENT_INPUT_DISABLED',
                array(
                    '#VALUE#'=>$product['PRICE_IN_SPECIAL'],
                    '#NAME#'=>'PRICE_IN_SPECIAL',
                    '#ID#'=>$product['ID'],
                    '#TYPE#' => 'SPECIAL_IN',
                )
            ),
            'PRICE_IN_SPECIAL_SUM' => Loc::getMessage(
                'CRMTENDERS_GRID_ELEMENT_INPUT_READONLY',
                array(
                    '#VALUE#'=>CurrencyFormat($priceInSpecialSum, 'RUB'),
                    '#NAME#'=>'PRICE_IN_SPECIAL_SUM',
                    '#ID#'=>$product['ID'],
                    '#TYPE#' => 'SPECIAL_IN',
                )
            ),
            // Дистриб. цена
            'PRICE_IN_DISTRIBUTOR' => Loc::getMessage(
                'CRMTENDERS_GRID_ELEMENT_INPUT_DISABLED',
                array(
                    '#VALUE#'=>$priceInDistrib,
                    '#NAME#'=>'PRICE_IN_DISTRIBUTOR',
                    '#ID#'=>$product['ID'],
                    '#TYPE#' => 'DISTRIB_IN',
                )
            ),
            'PRICE_IN_DISTRIBUTOR_SUM' => Loc::getMessage(
                'CRMTENDERS_GRID_ELEMENT_INPUT_READONLY',
                array(
                    '#VALUE#'=>CurrencyFormat($priceInDistribSum, 'RUB'),
                    '#NAME#'=>'PRICE_IN_DISTRIBUTOR_SUM',
                    '#ID#'=>$product['ID'],
                    '#TYPE#' => 'DISTRIB_IN',
                )
            ),

            // Расчетные поля (калькулятор)
                // ЗАКУП ДИСТРИБЬЮТОРСКАЯ + СТОИМОСТЬ ДОСТАВКИ
            'PRICE_IN_DISTRIBUTOR_DELIVERY' => Loc::getMessage(
                'CRMTENDERS_GRID_ELEMENT_INPUT_READONLY',
                array(
                    '#VALUE#'=>CurrencyFormat($priceInDistribDelivery, 'RUB'),
                    '#NAME#'=>'PRICE_IN_DISTRIBUTOR_DELIVERY',
                    '#ID#'=>$product['ID'],
                    '#TYPE#' => 'DISTRIB_IN',
                )
            ),
            'PRICE_IN_DISTRIBUTOR_DELIVERY_SUM' => Loc::getMessage(
                'CRMTENDERS_GRID_ELEMENT_INPUT_READONLY',
                array(
                    '#VALUE#'=>CurrencyFormat($priceInDistribDeliverySum, 'RUB'),
                    '#NAME#'=>'PRICE_IN_DISTRIBUTOR_DELIVERY_SUM',
                    '#ID#'=>$product['ID'],
                    '#TYPE#' => 'DISTRIB_IN',
                )
            ),
            'PRICE_IN_DISTRIBUTOR_WITH_DELIVERY_SUM' => Loc::getMessage(
                'CRMTENDERS_GRID_ELEMENT_INPUT_READONLY',
                array(
                    '#VALUE#'=>CurrencyFormat($priceInDistribWithDeliverySum, 'RUB'),
                    '#NAME#'=>'PRICE_IN_DISTRIBUTOR_WITH_DELIVERY',
                    '#ID#'=>$product['ID'],
                    '#TYPE#' => 'DISTRIB_IN',
                )
            ),
            'PRICE_IN_DISTRIBUTOR_TOTAL' => Loc::getMessage(
                'CRMTENDERS_GRID_ELEMENT_INPUT_READONLY',
                array(
                    '#VALUE#'=>CurrencyFormat($priceInDistribTotal, 'RUB'),
                    '#NAME#'=>'PRICE_IN_DISTRIBUTOR_TOTAL',
                    '#ID#'=>$product['ID'],
                    '#TYPE#' => 'DISTRIB_IN',
                )
            ),

                // ЗАКУП СПЕЦЦЕНА + СТОИМОСТЬ ДОСТАВКИ
            'PRICE_IN_SPECIAL_DELIVERY' => Loc::getMessage(
                'CRMTENDERS_GRID_ELEMENT_INPUT_READONLY',
                array(
                    '#VALUE#'=>CurrencyFormat($priceInSpecialDelivery, 'RUB'),
                    '#NAME#'=>'PRICE_IN_SPECIAL_DELIVERY',
                    '#ID#'=>$product['ID'],
                    '#TYPE#' => 'SPECIAL_IN',
                )
            ),
            'PRICE_IN_SPECIAL_DELIVERY_SUM' => Loc::getMessage(
                'CRMTENDERS_GRID_ELEMENT_INPUT_READONLY',
                array(
                    '#VALUE#'=>CurrencyFormat($priceInSpecialDeliverySum, 'RUB'),
                    '#NAME#'=>'PRICE_IN_SPECIAL_DELIVERY_SUM',
                    '#ID#'=>$product['ID'],
                    '#TYPE#' => 'SPECIAL_IN',
                )
            ),
            'PRICE_IN_SPECIAL_WITH_DELIVERY_SUM' => Loc::getMessage(
                'CRMTENDERS_GRID_ELEMENT_INPUT_READONLY',
                array(
                    '#VALUE#'=>CurrencyFormat($priceInSpecialWithDeliverySum, 'RUB'),
                    '#NAME#'=>'PRICE_IN_SPECIAL_WITH_DELIVERY',
                    '#ID#'=>$product['ID'],
                    '#TYPE#' => 'SPECIAL_IN',
                )
            ),
            'PRICE_IN_SPECIAL_TOTAL' => Loc::getMessage(
                'CRMTENDERS_GRID_ELEMENT_INPUT_READONLY',
                array(
                    '#VALUE#'=>CurrencyFormat($priceInSpecialTotal, 'RUB'),
                    '#NAME#'=>'PRICE_IN_SPECIAL_TOTAL',
                    '#ID#'=>$product['ID'],
                    '#TYPE#' => 'SPECIAL_IN',
                )
            ),

                // ПРОДАЖА ПО ДИСТРИБЬЮТОРСКОЙ ЦЕНЕ
            'PROFIT_RATIO_DISTRIBUTOR' => Loc::getMessage(
                'CRMTENDERS_GRID_ELEMENT_INPUT_DISABLED',
                array(
                    '#VALUE#'=>($profitRatioDistrib),
                    '#NAME#'=>'PROFIT_RATIO_DISTRIBUTOR',
                    '#ID#'=>$product['ID'],
                    '#TYPE#' => 'DISTRIB_OUT',
                )
            ),
            'PRICE_OUT_DISTRIBUTOR' => Loc::getMessage(
                'CRMTENDERS_GRID_ELEMENT_INPUT_READONLY',
                array(
                    '#VALUE#'=>CurrencyFormat($priceOutDistrib, 'RUB'),
                    '#NAME#'=>'PRICE_OUT_DISTRIBUTOR',
                    '#ID#'=>$product['ID'],
                    '#TYPE#' => 'DISTRIB_OUT',
                )
            ),
            'PRICE_OUT_DISTRIBUTOR_SUM' => Loc::getMessage(
                'CRMTENDERS_GRID_ELEMENT_INPUT_READONLY',
                array(
                    '#VALUE#'=>CurrencyFormat($priceOutDistribSum, 'RUB'),
                    '#NAME#'=>'PRICE_OUT_DISTRIBUTOR_SUM',
                    '#ID#'=>$product['ID'],
                    '#TYPE#' => 'DISTRIB_OUT',
                )
            ),
            'PRICE_OUT_DISTRIBUTOR_GROSS_SUM' => Loc::getMessage(
                'CRMTENDERS_GRID_ELEMENT_INPUT_READONLY',
                array(
                    '#VALUE#'=>CurrencyFormat($priceOutDistribGrossSum, 'RUB'),
                    '#NAME#'=>'PRICE_OUT_DISTRIBUTOR_GROSS_SUM',
                    '#ID#'=>$product['ID'],
                    '#TYPE#' => 'DISTRIB_OUT',
                )
            ),
            'PRICE_OUT_DISTRIBUTOR_GROSS_PERCENT' => Loc::getMessage(
                'CRMTENDERS_GRID_ELEMENT_INPUT_READONLY',
                array(
                    '#VALUE#'=>($priceOutDistribGrossPercent),
                    '#NAME#'=>'PRICE_OUT_DISTRIBUTOR_GROSS_PERCENT',
                    '#ID#'=>$product['ID'],
                    '#TYPE#' => 'DISTRIB_OUT',
                )
            ),

                // ПРОДАЖА ПО СПЕЦЦЕНЕ ЦЕНЕ
            'PROFIT_RATIO_SPECIAL' => Loc::getMessage(
                'CRMTENDERS_GRID_ELEMENT_INPUT_DISABLED',
                array(
                    '#VALUE#'=>($profitRatioSpecial),
                    '#NAME#'=>'PROFIT_RATIO_SPECIAL',
                    '#ID#'=>$product['ID'],
                    '#TYPE#' => 'SPECIAL_OUT',
                )
            ),
            'PRICE_OUT_SPECIAL' => Loc::getMessage(
                'CRMTENDERS_GRID_ELEMENT_INPUT_READONLY',
                array(
                    '#VALUE#'=>CurrencyFormat($priceOutSpecial, 'RUB'),
                    '#NAME#'=>'PRICE_OUT_SPECIAL',
                    '#ID#'=>$product['ID'],
                    '#TYPE#' => 'SPECIAL_OUT',
                )
            ),
            'PRICE_OUT_SPECIAL_SUM' => Loc::getMessage(
                'CRMTENDERS_GRID_ELEMENT_INPUT_READONLY',
                array(
                    '#VALUE#'=>CurrencyFormat($priceOutSpecialSum, 'RUB'),
                    '#NAME#'=>'PRICE_OUT_SPECIAL_SUM',
                    '#ID#'=>$product['ID'],
                    '#TYPE#' => 'SPECIAL_OUT',
                )
            ),
            'PRICE_OUT_SPECIAL_GROSS_SUM' => Loc::getMessage(
                'CRMTENDERS_GRID_ELEMENT_INPUT_READONLY',
                array(
                    '#VALUE#'=>CurrencyFormat($priceOutSpecialGrossSum, 'RUB'),
                    '#NAME#'=>'PRICE_OUT_SPECIAL_GROSS_SUM',
                    '#ID#'=>$product['ID'],
                    '#TYPE#' => 'SPECIAL_OUT',
                )
            ),
            'PRICE_OUT_SPECIAL_GROSS_PERCENT' => Loc::getMessage(
                'CRMTENDERS_GRID_ELEMENT_INPUT_READONLY',
                array(
                    '#VALUE#'=>($priceOutSpecialGrossPercent),
                    '#NAME#'=>'PRICE_OUT_SPECIAL_GROSS_PERCENT',
                    '#ID#'=>$product['ID'],
                    '#TYPE#' => 'SPECIAL_OUT',
                )
            ),

        )
    );
    $arElementIDs[$product['ID']] = array(
        'ID' => $product['ID'],
        'PRODUCT_NAME_ORIG' => $product['PRODUCT_NAME_ORIG'],
        'PRODUCT_NAME_SPEC' => $product['PRODUCT_NAME_SPEC'],
        // 'DELIVERY_DATE' => ConvertDateTime($product['DELIVERY_DATE'], "YYYY-MM-DD", "ru"),
        'DELIVERY_DATE' => $product['DELIVERY_DATE'],
        'DELIVERY_ADDRESS' => $product['DELIVERY_ADDRESS'],
        'MEASURE' => $product['MEASURE'],
        'QUANTITY_REQUEST' => $product['QUANTITY_REQUEST'],
        'PACKING' => $product['PACKING'],
        'QUANTITY' => $product['QUANTITY'],
        'PRICE_PURCHASE' => $product['PRICE_PURCHASE'],
        'PROFIT_RATIO' => $product['PROFIT_RATIO'],
        'PRICE_NMCK' => $product['PRICE_NMCK'],
    );
}
?>
<div id="w4a-button-container">
    <button id="w4a-btn-add" class="ui-btn ui-btn-primary-dark ui-btn-icon-add"><?=Loc::getMessage('CRMTENDERS_GRID_ACTION_ADD_TEXT')?></button>
    <button id="w4a-btn-send" class="ui-btn ui-btn-danger-dark ui-btn-icon-done" title="<?=Loc::getMessage('CRMTENDERS_GRID_ACTION_SEND_TITLE')?>"><?=Loc::getMessage('CRMTENDERS_GRID_ACTION_SEND_TEXT')?></button>
    <button id="w4a-btn-add-info" class="w4a-right ui-btn ui-btn-primary-dark ui-btn-icon-setting" title="<?=Loc::getMessage('CRMTENDERS_GRID_ACTION_ADD_INFO_TITLE')?>">
        <?=Loc::getMessage('CRMTENDERS_GRID_ACTION_ADD_INFO_TEXT')?>
    </button>
    <div id="w4a-calc-options-container" style="display: none;">
            <div class="w4a-crm-items-table-calc-options" id="crm-top-calc-options-tab">
                <div class="crm-items-table-tab-inner">
                    <div class="w4a-calc-options-tab">
                        <input type="hidden" data-name="ID" value="<?=$arResult['TENDER']['ID']?>">
                        <div style="float:left">
                            <span class="w4a-title"><?=Loc::getMessage('TENDER_DEADLINE_TEXT')?></span>
                            <div class="ui-ctl ui-ctl-textbox">
                                <input type="date" data-name="DEADLINE" class="ui-ctl-element" value="<?=ConvertDateTime(
                                        $arResult['TENDER']['DEADLINE'],
                                        "YYYY-MM-DD",
                                        "ru"
                                );?>" placeholder="<?=Loc::getMessage('W4A_CALC_ENTER_VALUE_TEXT')?>">
                            </div>
                            <span class="w4a-title"><?=Loc::getMessage('TENDER_CLIENT_NAME_TEXT')?></span>
                            <div class="ui-ctl ui-ctl-textbox">
                                <input type="text" data-name="CLIENT_NAME" class="ui-ctl-element" value="<?=$arResult['TENDER']['CLIENT_NAME']?>" placeholder="<?=Loc::getMessage('W4A_CALC_ENTER_VALUE_TEXT')?>">
                            </div>
                            <span class="w4a-title"><?=Loc::getMessage('TENDER_USER_NAME_TEXT')?></span>
                            <div class="ui-ctl ui-ctl-textbox">
                                <input type="text" data-name="USER_NAME" class="ui-ctl-element" value="<?=$arResult['TENDER']['USER_NAME']?>" placeholder="<?=Loc::getMessage('W4A_CALC_ENTER_VALUE_TEXT')?>">
                            </div>

                            <span class="w4a-title"><?=Loc::getMessage('TENDER_DELIVERY_ADDRESS_TEXT')?></span>
                            <div class="ui-ctl ui-ctl-textbox">
                                <input type="text" data-name="DELIVERY_ADDRESS" class="ui-ctl-element" value="<?=$arResult['TENDER']['DELIVERY_ADDRESS']?>" placeholder="<?=Loc::getMessage('W4A_CALC_ENTER_VALUE_TEXT')?>">
                            </div>
                            <span class="w4a-title"><?=Loc::getMessage('TENDER_DELIVERY_PERIOD_TEXT')?></span>
                            <div class="ui-ctl ui-ctl-textbox">
                                <input type="text" data-name="DELIVERY_PERIOD" class="ui-ctl-element" value="<?=$arResult['TENDER']['DELIVERY_PERIOD']?>" placeholder="<?=Loc::getMessage('W4A_CALC_ENTER_VALUE_TEXT')?>">
                            </div>
                            <span class="w4a-title"><?=Loc::getMessage('TENDER_DELIVERY_CONDITIONS_TEXT')?></span>
                            <div class="ui-ctl ui-ctl-textbox">
                                <input type="text" data-name="DELIVERY_CONDITIONS" class="ui-ctl-element" value="<?=$arResult['TENDER']['DELIVERY_CONDITIONS']?>" placeholder="<?=Loc::getMessage('W4A_CALC_ENTER_VALUE_TEXT')?>">
                            </div>
                            <span class="w4a-title"><?=Loc::getMessage('TENDER_DELIVERY_PRICE_TEXT')?></span>
                            <div class="ui-ctl ui-ctl-textbox">
                                <input type="text" data-name="DELIVERY_PRICE" class="ui-ctl-element" value="<?=$arResult['TENDER']['DELIVERY_PRICE']?>" placeholder="<?=Loc::getMessage('W4A_CALC_ENTER_VALUE_TEXT')?>">
                            </div>
                        </div>

                        <div style="float:right">
                            <span class="w4a-title"><?=Loc::getMessage('TENDER_DELIVERY_FREQUENCY_TEXT')?></span>
                            <div class="ui-ctl ui-ctl-textbox">
                                <input type="text" data-name="DELIVERY_FREQUENCY" class="ui-ctl-element" value="<?=$arResult['TENDER']['DELIVERY_FREQUENCY']?>" placeholder="<?=Loc::getMessage('W4A_CALC_ENTER_VALUE_TEXT')?>">
                            </div>
                            <span class="w4a-title"><?=Loc::getMessage('TENDER_CONTRACT_WARRANTY_PAYMENT_TEXT')?></span>
                            <div class="ui-ctl ui-ctl-textbox">
                                <input type="text" data-name="CONTRACT_WARRANTY_PAYMENT" class="ui-ctl-element" value="<?=$arResult['TENDER']['CONTRACT_WARRANTY_PAYMENT']?>" placeholder="<?=Loc::getMessage('W4A_CALC_ENTER_VALUE_TEXT')?>">
                            </div>
                            <span class="w4a-title"><?=Loc::getMessage('TENDER_CONTRACT_PAYMENT_TEXT')?></span>
                            <div class="ui-ctl ui-ctl-textbox">
                                <input type="text" data-name="CONTRACT_PAYMENT" class="ui-ctl-element" value="<?=$arResult['TENDER']['CONTRACT_PAYMENT']?>" placeholder="<?=Loc::getMessage('W4A_CALC_ENTER_VALUE_TEXT')?>">
                            </div>

                            <span class="w4a-title"><?=Loc::getMessage('TENDER_SITE_CONDITIONS_TEXT')?></span>
                            <div class="ui-ctl ui-ctl-textbox">
                                <input type="text" data-name="TENDER_SITE_CONDITIONS" class="ui-ctl-element" value="<?=$arResult['TENDER']['TENDER_SITE_CONDITIONS']?>" placeholder="<?=Loc::getMessage('W4A_CALC_ENTER_VALUE_TEXT')?>">
                            </div>
                            <span class="w4a-title"><?=Loc::getMessage('TENDER_MY_COMPANY_NAME_TEXT')?></span>
                            <div class="ui-ctl ui-ctl-textbox">
                                <input type="text" data-name="MY_COMPANY_NAME" class="ui-ctl-element" value="<?=$arResult['TENDER']['MY_COMPANY_NAME']?>" placeholder="<?=Loc::getMessage('W4A_CALC_ENTER_VALUE_TEXT')?>">
                            </div>
                            <span class="w4a-title"><?=Loc::getMessage('TENDER_PRICE_NMCK_TEXT')?></span>
                            <div class="ui-ctl ui-ctl-textbox">
                                <input type="text" data-name="PRICE_NMCK" class="ui-ctl-element" value="<?=$arResult['TENDER']['PRICE_NMCK']?>" placeholder="<?=Loc::getMessage('W4A_CALC_ENTER_VALUE_TEXT')?>">
                            </div>
                        </div>
                         <div style="clear:both;"></div>
                         <button id="w4a-btn-add-info-save" class="ui-btn ui-btn-success"><?=Loc::getMessage('TENDER_ADD_INFO_SAVE_TEXT')?></button>
                    </div>
                </div>
            </div>
        </div>
</div>
<div class="w4a-users">
    <div class="ui-alert ui-alert-icon-info ui-alert-primary">
        <div class="w4a-user">
            <span class="ui-alert-message"><strong><?=Loc::getMessage('CRMTENDERS_DEPT_PRODUCTION_TITLE');?></strong>
            <?=CCrmViewHelper::PrepareUserBaloonHtml(
                array(
                    'PREFIX' => "TENDER_{$arResult['TENDER']['ID']}_PRODUCTION",
                    'USER_ID' => $arResult['USERS']['PRODUCTION_USER_ID'],
                    'USER_NAME'=> CUser::FormatName(CSite::GetNameFormat(), $arResult['USERS']['PRODUCTION_USER']),
                    'USER_PROFILE_URL' => "/company/personal/user/{$arResult['USERS']['PRODUCTION_USER_ID']}/"
                )
            );?>
            </span>
        </div>
        <div class="w4a-user">
            <span class="ui-alert-message"><strong><?=Loc::getMessage('CRMTENDERS_DEPT_SALES_TITLE');?></strong>
            <?=CCrmViewHelper::PrepareUserBaloonHtml(
                array(
                    'PREFIX' => "TENDER_{$arResult['TENDER']['ID']}_SALES",
                    'USER_ID' => $arResult['USERS']['SALES_USER_ID'],
                    'USER_NAME'=> CUser::FormatName(CSite::GetNameFormat(), $arResult['USERS']['SALES_USER']),
                    'USER_PROFILE_URL' => "/company/personal/user/{$arResult['USERS']['SALES_USER_ID']}/"
                )
            );?>
            </span>
        </div>
        <div class="w4a-user">
            <span class="ui-alert-message"><strong><?=Loc::getMessage('CRMTENDERS_DEPT_LOGISTICS_TITLE');?></strong>
            <?=CCrmViewHelper::PrepareUserBaloonHtml(
                array(
                    'PREFIX' => "TENDER_{$arResult['TENDER']['ID']}_LOGISTICS",
                    'USER_ID' => $arResult['USERS']['LOGISTICS_USER_ID'],
                    'USER_NAME'=> CUser::FormatName(CSite::GetNameFormat(), $arResult['USERS']['LOGISTICS_USER']),
                    'USER_PROFILE_URL' => "/company/personal/user/{$arResult['USERS']['LOGISTICS_USER_ID']}/"
                )
            );?>
            </span>
        </div>
    </div>
</div>
<?php

$snippet = new Snippet();

$APPLICATION->IncludeComponent(
    'bitrix:crm.interface.grid',
    'titleflex',
    array(
        'GRID_ID' => $arResult['GRID_ID'],
        'HEADERS' => $arResult['HEADERS'],
        'ROWS' => $rows,
        'PAGINATION' => $arResult['PAGINATION'],
        'SORT' => $arResult['SORT'],
        'FILTER' => $arResult['FILTER'],
        'FILTER_PRESETS' => $arResult['FILTER_PRESETS'],
        'IS_EXTERNAL_FILTER' => false,
        'ENABLE_LIVE_SEARCH' => $arResult['ENABLE_LIVE_SEARCH'],
        'DISABLE_SEARCH' => $arResult['DISABLE_SEARCH'],
        'ENABLE_ROW_COUNT_LOADER' => false,
        'AJAX_MODE' => 'Y',
        'AJAX_ID' => \CAjax::getComponentID('bitrix:main.ui.grid', '.default', ''),
        'AJAX_OPTION_JUMP' => 'N',
        'AJAX_OPTION_HISTORY' => 'N',
        'AJAX_LOADER' => null,
        'SHOW_CHECK_ALL_CHECKBOXES' => false,
        'ACTION_PANEL' => $arParams['VARIABLES']['USER_ROLES']['IS_ADMIN'] !== 'Y'?'': array(
            'GROUPS' => array(
                array(
                    'ITEMS' => array(
                        //$snippet->getForAllCheckbox(),
                        $snippet->getRemoveButton(),
                        array(
                            'TYPE' => 'BUTTON',
                            'ID' => 'grid_save_button',
                            'NAME' => '',
                            'TITLE' => Loc::getMessage('CRMTENDERS_GRID_ACTION_SAVE_TITLE'),
                            'CLASS' => 'save',
                            'TEXT' => Loc::getMessage('CRMTENDERS_GRID_ACTION_SAVE_TEXT'),
                            'ONCHANGE' => [
                                array(
                                    'ACTION'=> 'SHOW_ALL',
                                    'DATA' => array()
                                ),
                                array(
                                    'ACTION'=> 'CALLBACK',
                                    'DATA' => [
                                        array('JS' => 'BX.W4aCrmTender.handlerSave();'),
                                    ],
                                ),
                                array(
                                    'ACTION'=> 'REMOVE',
                                    'DATA' => [
                                        array('ID' => 'grid_save_button'),
                                        array('ID' => 'grid_cancel_button'),
                                    ],
                                ),
                            ],
                        ),
//                        $snippet->getSaveEditButton(),
                    )
                )
            )
        ),
        'EXTENSION' => array(
            'ID' => $gridManagerId,
            'CONFIG' => array(
                'ownerTypeName' => 'TENDER',
                'gridId' => $arResult['GRID_ID'],
                'serviceUrl' => $arResult['SERVICE_URL'],
            ),
            'MESSAGES' => array(
                'deletionDialogTitle' => Loc::getMessage('CRMTENDERS_DELETE_DIALOG_TITLE'),
                'deletionDialogMessage' => Loc::getMessage('CRMTENDERS_DELETE_DIALOG_MESSAGE'),
                'deletionDialogButtonTitle' => Loc::getMessage('CRMTENDERS_DELETE_DIALOG_BUTTON'),
            )
        ),
    ),
    $this->getComponent(),
    array('HIDE_ICONS' => 'Y',)
);
// массив локализации для JS
$MESS = [
    'JS_W4A_ACTION_ERROR_TITLE',
    'JS_W4A_ACTION_OPEN_TEXT',
    'JS_W4A_ACTION_CLOSE_TEXT',
    'JS_MODE_NOT_FOUND',
    ];
foreach ($MESS as $key)
{
    if(!empty(Loc::getMessage($key)))
        $arMessages[$key] = Loc::getMessage($key);
}
$arMessages['bitrix_sessid'] = bitrix_sessid_get();
$arMessages['sessid'] = bitrix_sessid();
$jsMessage = CUtil::PhpToJSObject($arMessages);
?>
    <script>BX.message(<?=$jsMessage;?>);</script>
<?php
foreach ($arResult['TENDER'] as $key=>$val)
{
}

$arTender = array (
    'ID' => $arResult['TENDER']['ID'],
    'DEAL_ID' => $arResult['TENDER']['DEAL_ID'],
//    'DEADLINE' => ConvertDateTime($arResult['TENDER']['DEADLINE'], "YYYY-MM-DD", "ru"),
    'DEADLINE' => $arResult['TENDER']['DEADLINE'],
    'USER_NAME' => $arResult['TENDER']['USER_NAME'],
    'DELIVERY_ADDRESS' => $arResult['TENDER']['DELIVERY_ADDRESS'],
    'DELIVERY_PERIOD' => $arResult['TENDER']['DELIVERY_PERIOD'],
    'DELIVERY_CONDITIONS' => $arResult['TENDER']['DELIVERY_CONDITIONS'],
    'DELIVERY_FREQUENCY' => $arResult['TENDER']['DELIVERY_FREQUENCY'],
    'CONTRACT_WARRANTY_PAYMENT' => $arResult['TENDER']['CONTRACT_WARRANTY_PAYMENT'],
    'CONTRACT_PAYMENT' => $arResult['TENDER']['CONTRACT_PAYMENT'],
    'TENDER_SITE_CONDITIONS' => $arResult['TENDER']['TENDER_SITE_CONDITIONS'],
    'MY_COMPANY_NAME' => $arResult['TENDER']['MY_COMPANY_NAME'],
    'PRICE_NMCK' => $arResult['TENDER']['PRICE_NMCK'],
    'ASSIGNED_BY_ID' => $arResult['TENDER']['ASSIGNED_BY_ID'],
);
$orderEditorCfg = array(
    'id' => $arParams['ENTITY_ID'],
    'gridId' => $arResult['GRID_ID'],
    'sessId' => bitrix_sessid(),
    'permissionEntityType' => $arResult['ENTITY_TYPE'],
    'serviceUrl'=> '/local/components/w4a/tendercalc.product.list/ajax.php?'.bitrix_sessid_get(),

    'dealId' => $arParams['ENTITY_ID'],
    'ownerType' => 'TENDER_CALC', //
    'componentID' => $arResult['COMPONENT_ID'],
    'items' => $arElementIDs,
    'tender' => $arTender,
    'siteId' => SITE_ID,
);
?>
    <script type="text/javascript">
        BX.namespace("BX.Crm");
        BX.W4aCrmTender = {};
        BX.ready(
            function() {
                let editor = BX.W4aCrmTendercalc.create(
                    "<?=$arParams['ENTITY_ID']?>",
                    <?=CUtil::PhpToJSObject($orderEditorCfg)?>
                );
                BX.W4aCrmTender = editor;
            }
        );
    </script>
