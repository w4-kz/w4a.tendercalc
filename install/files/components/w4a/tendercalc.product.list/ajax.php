<?php
const EXTRANET_NO_REDIRECT = true;
define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

$DEBUGGER = true;
/** DEBUGGER */
if($DEBUGGER) {
    $w4aHr = '==01===__w4a_ajax_order_actions_extranet: ================';
    \Bitrix\Main\Diag\Debug::writeToFile("".__FILE__." " . date('d.m.Y H:i:s'), $w4aHr, "__w4a_tendercalc_ajax.log");
    // $_POST
    \Bitrix\Main\Diag\Debug::writeToFile($_POST, '$_POST', "__w4a_tendercalc_ajax.log");
}
/** /DEBUGGER */
if (!CModule::IncludeModule('crm'))
{
    return;
}
use W4a\Tendercalc\Entity\ProductListTable;
use W4a\Tendercalc\Entity\TenderTable;
global $USER, $APPLICATION;
if (!CModule::IncludeModule('w4a.tendercalc'))
{
    return;
}

if(!function_exists('__W4aActionsEndResponse'))
{
    function __W4aActionsEndResponse($result)
    {
        $GLOBALS['APPLICATION']->RestartBuffer();
        header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
        if(!empty($result))
        {
            echo CUtil::PhpToJSObject($result);
        }
        require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
        die();
    }
}
if(!function_exists('__W4aGetProductList')){
    function __W4aGetProductList($dealId): array
    {
        $params = array(
            'filter' => array(
                'DEAL_ID' => $dealId,
            )
        );
        $db = ProductListTable::getList($params);
        $arResult = array();
        while($arRes = $db->fetch())
        {
            $arResult[$arRes['ID']] = $arRes;
        }
        return $arResult;
    }
}
if(!function_exists('__W4aGetTender')){
    function __W4aGetTender($dealId): array
    {
        $params = array(
            'filter' => array(
                'DEAL_ID' => $dealId,
            )
        );
        $db = TenderTable::getList($params);
        $arResult = $db->fetch();
        if(empty($arResult['ID']))
            return array();

        return $arResult;
    }
}
if(!function_exists('__W4aHtmlSpecialCharsArray')){
    /**
     * @param $ar
     * @return array
     */
    function __W4aHtmlSpecialCharsArray($ar): array
    {
        $arResult = array();
        foreach ($ar as $key=>$val)
        {
            if(is_array($val) || is_object($val)) {
                $arResult[$key] = $val;
                continue;
            }

            $arResult[$key] = str_replace('"', '&quot;', $val);
            $arResult[$key] = str_replace("'", '&#039;', $arResult[$key]);
        }
        return $arResult;
    }
}
/**
 * ONLY 'POST' SUPPORTED
 * SUPPORTED MODES: ADD
 * 'TENDER_CALC' - Тендер: Калькулятор
 *
 */

if (!$USER->IsAuthorized() || !check_bitrix_sessid() || $_SERVER['REQUEST_METHOD'] != 'POST')
{
    __W4aActionsEndResponse(array('ERROR' => 'ACCESS_DENIED'));
}

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

CUtil::JSPostUnescape();
$APPLICATION->RestartBuffer();
header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
$mode = $_POST['MODE'] ?? '';
if(!isset($mode[0]))
{
    __W4aActionsEndResponse(array());
}

$ownerType = $_POST['OWNER_TYPE'] ?? '';
if($ownerType!=='TENDER_CALC')
{
    __W4aActionsEndResponse(array('ERROR'=>'OWNER_TYPE_NOT_FOUND'));
}
$dealId = intval($_POST['DEAL_ID']);
if (empty($dealId)) {
    __W4aActionsEndResponse(array('ERROR' => 'NO_DEAL_ID'));
}
$arResult = array();
switch ($mode) {
    case 'ADD':
        $arFields = array('PRODUCT_NAME_ORIG'=>'', 'DEAL_ID' => $dealId);
        $db = ProductListTable::add($arFields);

        if($db->isSuccess()) {
            $arResult['PRODUCT_ID_NEW'] = $db->getID();
        }else {
            __W4aActionsEndResponse(array('ERROR' => 'CAN_NOT_ADD_PRODUCT_ROW', 'ERROR_DESCRIPTION' => $db->getErrors()));
        }
        $params = array(
            'filter' => array(
                'DEAL_ID' => $dealId,
            )
        );
        $db = ProductListTable::getList($params);
        while($arRes = $db->fetch())
        {
            $arResult['PRODUCTS'][$arRes['ID']] = $arRes;
        }
        break;
    case 'SAVE':
        $arProducts = $_POST['PRODUCTS'];
        foreach ($arProducts as $key=>$val)
        {
            $arFields = $val;
            $deliveryDate = CDatabase::FormatDate(
                            $arFields['DELIVERY_DATE'],
                            "YYYY-MM-DD HH:MI:SS",
                            "DD.MM.YYYY HH:MI:SS"
                        );
            $arFields['DELIVERY_DATE'] = new \Bitrix\Main\Type\DateTime($deliveryDate);

            $db = ProductListTable::update($key, __W4aHtmlSpecialCharsArray($arFields));
            if ($db->isSuccess()) {
                $arResult['PRODUCTS'] = __W4aGetProductList($dealId);
            }else {
                __W4aActionsEndResponse(array('ERROR' => 'NO_DEAL_ID', 'ERROR_DESCRIPTION' => $db->getErrors()));
            }
        }
        break;
    case 'ADD_INFO_SAVE':
        $data = $_POST['DATA'];
        $arRes = array();
        $tenderId = 0;
        foreach ($data as $val){
            if($val['name'] == 'ID')
            {
                $tenderId = $val['value'];
                continue;
            }
            if($val['type'] == 'date')
            {
                $date = CDatabase::FormatDate(
                    $val['value'],
                    "YYYY-MM-DD HH:MI:SS",
                    "DD.MM.YYYY HH:MI:SS"
                );
                $arRes[$val['name']] = new \Bitrix\Main\Type\DateTime($date);
                continue;
            }
            $arRes[$val['name']] = $val['value'];
        }
        $arFields = $arRes;
        unset($val, $date, $arRes);

        // calculate action
        $act = 'UPDATE';
        if(empty($tenderId))
        {
            // проверяем, может кто-то уже создал запись
            $arRes = __W4aGetTender($dealId);
            if(empty($arRes['ID']))
                $act = 'ADD';
            else
                $tenderId = $arRes['ID'];
        }

        // actions
        switch ($act){
            case "ADD":
                $arFields = array_merge($arFields, array('DEAL_ID' => $dealId, 'ASSIGNED_BY_ID' => $USER->GetID()));
                $db = TenderTable::add(__W4aHtmlSpecialCharsArray($arFields));
                if($db->isSuccess()) {
                    $arResult['TENDER_ID_NEW'] = $db->getID();
                    $arResult['TENDER'] = __W4aGetTender($dealId);
                }else {
                    __W4aActionsEndResponse(array('ERROR' => 'CAN_NOT_ADD_TENDER_ROW', 'ERROR_DESCRIPTION' => $db->getErrors()));
                }
                break;
            case "UPDATE":
                $db = TenderTable::update($tenderId, __W4aHtmlSpecialCharsArray($arFields));
                if ($db->isSuccess()) {
                    $arResult['TENDER'] = __W4aGetTender($dealId);
                }else {
                    __W4aActionsEndResponse(array('ERROR' => 'NO_DEAL_ID', 'ERROR_DESCRIPTION' => $db->getErrors()));
                }
                break;
        }

        break;

    default:
        __W4aActionsEndResponse(array('ERROR'=>'MODE_NOT_FOUND'));
        break;
}



$arResult = array(
    'RESULT' => $arResult,
    'POST' => $_POST,
    'ERROR' => false,
);


/** DEBUGGER */
if($DEBUGGER)
{
    $w4aHr = '==02===__w4a_ajax_order_actions_extranet: ================';
    \Bitrix\Main\Diag\Debug::writeToFile("".__FILE__." " . date('d.m.Y H:i:s'), $w4aHr, "__w4a_tendercalc_ajax.log");
    // $arOrderFact
    \Bitrix\Main\Diag\Debug::writeToFile($arResult, '$arResult', "__w4a_tendercalc_ajax.log");
}
/** /DEBUGGER */


__W4aActionsEndResponse($arResult);
