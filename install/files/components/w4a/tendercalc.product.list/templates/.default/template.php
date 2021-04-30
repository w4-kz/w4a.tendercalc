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
    $rows[] = array(
        'id' => $product['ID'],
        'actions' => $actions,
        'data' => $product,
        'columns' => array(
            'ID' => $product['ID'],
            'PRODUCT_NAME_ORIG' => Loc::getMessage(
                'CRMTENDERS_GRID_ELEMENT_INPUT',
                array(
                    '#VALUE#'=>$product['PRODUCT_NAME_ORIG'],
                    '#NAME#'=>'PRODUCT_NAME_ORIG',
                    '#ID#'=>$product['ID'],
                )
            ),
            'PRODUCT_NAME_SPEC' => Loc::getMessage(
                'CRMTENDERS_GRID_ELEMENT_INPUT',
                array(
                    '#VALUE#'=>$product['PRODUCT_NAME_SPEC'],
                    '#NAME#'=>'PRODUCT_NAME_SPEC',
                    '#ID#'=>$product['ID'],
                )
            ),
            'DELIVERY_DATE' => Loc::getMessage(
                'CRMTENDERS_GRID_ELEMENT_DATE',
                array(
                    '#VALUE#'=>ConvertDateTime($product['DELIVERY_DATE'], "YYYY-MM-DD", "ru"),
                    '#NAME#'=>'DELIVERY_DATE',
                    '#ID#'=>$product['ID'],
                )
            ),
            'DELIVERY_ADDRESS' => Loc::getMessage(
                'CRMTENDERS_GRID_ELEMENT_INPUT',
                array(
                    '#VALUE#'=>$product['DELIVERY_ADDRESS'],
                    '#NAME#'=>'DELIVERY_ADDRESS',
                    '#ID#'=>$product['ID'],
                )
            ),
            'MEASURE' => Loc::getMessage(
                'CRMTENDERS_GRID_ELEMENT_INPUT',
                array(
                    '#VALUE#'=>$product['MEASURE'],
                    '#NAME#'=>'MEASURE',
                    '#ID#'=>$product['ID'],
                )
            ),

            'QUANTITY_REQUEST' => Loc::getMessage(
                'CRMTENDERS_GRID_ELEMENT_INPUT',
                array(
                    '#VALUE#'=>$product['QUANTITY_REQUEST'],
                    '#NAME#'=>'QUANTITY_REQUEST',
                    '#ID#'=>$product['ID'],
                )
            ),
            'PACKING' => Loc::getMessage(
                'CRMTENDERS_GRID_ELEMENT_INPUT',
                array(
                    '#VALUE#'=>$product['PACKING'],
                    '#NAME#'=>'PACKING',
                    '#ID#'=>$product['ID'],
                )
            ),
            'QUANTITY' => Loc::getMessage(
                'CRMTENDERS_GRID_ELEMENT_INPUT',
                array(
                    '#VALUE#'=>$product['QUANTITY'],
                    '#NAME#'=>'QUANTITY',
                    '#ID#'=>$product['ID'],
                )
            ),


            'PRICE_PURCHASE' => Loc::getMessage(
                'CRMTENDERS_GRID_ELEMENT_INPUT',
                array(
                    '#VALUE#'=>$product['PRICE_PURCHASE'],
                    '#NAME#'=>'PRICE_PURCHASE',
                    '#ID#'=>$product['ID'],
                )
            ),
            'PROFIT_RATIO' => Loc::getMessage(
                'CRMTENDERS_GRID_ELEMENT_INPUT',
                array(
                    '#VALUE#'=>$product['PROFIT_RATIO'],
                    '#NAME#'=>'PROFIT_RATIO',
                    '#ID#'=>$product['ID'],
                )
            ),
            'PRICE_NMCK' => Loc::getMessage(
                'CRMTENDERS_GRID_ELEMENT_INPUT',
                array(
                    '#VALUE#'=>$product['PRICE_NMCK'],
                    '#NAME#'=>'PRICE_NMCK',
                    '#ID#'=>$product['ID'],
                )
            ),




            /*'ASSIGNED_BY' => empty($product['ASSIGNED_BY']) ? '' : CCrmViewHelper::PrepareUserBaloonHtml(
                array(
                    'PREFIX' => "TENDER_{$product['ID']}_RESPONSIBLE",
                    'USER_ID' => $product['ASSIGNED_BY_ID'],
                    'USER_NAME'=> CUser::FormatName(CSite::GetNameFormat(), $product['ASSIGNED_BY']),
                    'USER_PROFILE_URL' => Option::get('intranet', 'path_user', '', SITE_ID) . '/'
                )
            ),*/
        )
    );
    $arElementIDs[$product['ID']] = array(
        'ID' => $product['ID'],
        'PRODUCT_NAME_ORIG' => $product['PRODUCT_NAME_ORIG'],
        'PRODUCT_NAME_SPEC' => $product['PRODUCT_NAME_SPEC'],
        'DELIVERY_DATE' => ConvertDateTime($product['DELIVERY_DATE'], "YYYY-MM-DD", "ru"),
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
    <button id="w4a-btn-add-info" class="w4a-right ui-btn ui-btn-primary-dark ui-btn-icon-setting" title="<?=Loc::getMessage('CRMTENDERS_GRID_ACTION_ADD_INFO_TITLE')?>">
        <?=Loc::getMessage('CRMTENDERS_GRID_ACTION_ADD_INFO_TEXT')?>
    </button>

        <div id="w4a-calc-options-container" style="display: block;">
            <div class="w4a-crm-items-table-calc-options" id="crm-top-calc-options-tab">
                <div class="crm-items-table-tab-inner">
                    <div class="w4a-calc-options-tab">
                        <input type="hidden" data-name="ID" value="<?=$arResult['TENDER']['ID']?>">
                        <div style="float:left">
                            <span class="w4a-title"><?=Loc::getMessage('TENDER_DEADLINE_TEXT')?></span>
                            <div class="ui-ctl ui-ctl-textbox">
                                <input type="date" data-name="DEADLINE" class="ui-ctl-element" value="<?=ConvertDateTime($arResult['TENDER']['DEADLINE'], "YYYY-MM-DD", "ru");?>" placeholder="<?=Loc::getMessage('W4A_CALC_ENTER_VALUE_TEXT')?>">
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
        'ENABLE_ROW_COUNT_LOADER' => true,
        'AJAX_ID' => '',
        'AJAX_OPTION_JUMP' => 'N',
        'AJAX_OPTION_HISTORY' => 'N',
        'AJAX_LOADER' => null,
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
    //'DEADLINE' => ConvertDateTime($arResult['TENDER']['DEADLINE'], "YYYY-MM-DD", "ru"),
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
<?php
/** DEBUGGERS */
if(__w4a::isDev()):
    __w4a(array(
            'VAR_NAME'=>'$arParams', // Variable name
            'CALLED_FROM'=>__FILE__, // Trace of file path
            'VAR'=>$arParams // Variable for debugger
        )
    );
    __w4a(array(
            'VAR_NAME'=>'$arResult', // Variable name
            'CALLED_FROM'=>__FILE__, // Trace of file path
            'VAR'=>$arResult // Variable for debugger
        )
    );

endif;
//
//\Bitrix\Main\Diag\Debug::writeToFile('' . date('d.m.Y H:i:s'), '====='.__FILE__.'======', "__w4a.log");
//\Bitrix\Main\Diag\Debug::writeToFile($this->arParams, 'arParams: ' . date('d.m.Y H:i:s'), "__w4a.log");
//
///** DEBUGGERS */