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
use W4a\Tendercalc\Entity\UsersTable;

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
    __W4aActionsEndResponse(array('ERROR'=>'MODE_NOT_FOUND_0'));
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
CBitrixComponent::includeComponentClass("w4a:tendercalc.product.list");
$componentClass = new CW4aTendercalcProductListComponent;

// настройки модуля: w4a:tendercalc
$arConfig = $componentClass->getConfig();

$arResult = array();
switch ($mode) {
    case 'ADD':
        $arFields = array('PRODUCT_NAME_ORIG'=>'', 'DEAL_ID' => $dealId);
        $db = ProductListTable::add($arFields);

        if($db->isSuccess()) {
            $arResult['PRODUCT_ID_NEW'] = $db->getID();
        }else {
            __W4aActionsEndResponse(array('ERROR' => 'CAN_NOT_ADD_PRODUCT_LIST_TABLE', 'ERROR_DESCRIPTION' => $db->getErrors()));
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
        // UsersTable
        $params = array(
            'filter' => array(
                'DEAL_ID' => $dealId,
            )
        );
        $db =UsersTable::getList($params);
        $arUsers = $db->fetch();
        if(empty($arUsers['ID']))
        {
            $arFields = array('DEAL_ID' => $dealId, 'ASSIGNED_BY_ID' => $USER->GetID());
            $db = UsersTable::add($arFields);
            if($db->isSuccess()) {
                $arResult['USERS'] = array('ID'=>$db->getID() , 'DEAL_ID' => $dealId);
            }else {
                __W4aActionsEndResponse(array('ERROR' => 'CAN_NOT_ADD_USERS_TABLE', 'ERROR_DESCRIPTION' => $db->getErrors()));
            }
        }
        else
        {
            $arResult['USERS'] = $arUsers;
        }
        break;
    case 'SAVE':
        $arProducts = $_POST['PRODUCTS'];
        foreach ($arProducts as $key=>$val)
        {
            $arFields = $val;
            if(!empty($arFields['DELIVERY_DATE']))
                $arFields['DELIVERY_DATE'] = new \Bitrix\Main\Type\DateTime(
                    CDatabase::FormatDate(
                        $arFields['DELIVERY_DATE'],
                        "YYYY-MM-DD HH:MI:SS",
                        CSite::GetDateFormat()
                    )
                );
            else
                $arFields['DELIVERY_DATE'] = new \Bitrix\Main\Type\DateTime(0);

            $db = ProductListTable::update($key, $componentClass->htmlSpecialCharsArray($arFields));
            if ($db->isSuccess()) {
                $arResult['PRODUCTS'] = $componentClass->getProductListByDealId($dealId);
            }else {
                __W4aActionsEndResponse(array('ERROR' => 'CAN_NOT_UPDATE_PRODUCT_LIST_TABLE', 'ERROR_DESCRIPTION' => $db->getErrors()));
            }

            $arResult['arFields'][] = $arFields;
        }
        $isCompleted = $componentClass->checkCompletedSendMsg($dealId);
        if($isCompleted)
        {
            $componentClass->setIsCompleted($dealId, true);

            $bpTemplateID = $arConfig['TENDERCALC_COMPLETED_BP_ID'];
            // BP Start
            // parameters for BP
            $arWorkflowParameters = array();
            $documentId = 'DEAL_' . $dealId;
            $arErrorsTmp = array();
            $wfId = CBPDocument::StartWorkflow(
                $bpTemplateID, // ИД_шаблона_ БП,
                array("bizproc", "CCrmDocumentDeal", $documentId),
                array_merge($arWorkflowParameters/*, array("TargetUser" => "user_".$userId)*/),
                $arErrorsTmp
            );
            $arResult['BP_RESULT'] = array(
                'wfId' => $wfId,
            );
        }
        else{
            $componentClass->setIsCompleted($dealId, false);
        }
        break;
    case 'ADD_INFO_SAVE':
        $arAddInfoFormData = $componentClass->getAddInfoFormData();
        if(empty($arFields = $arAddInfoFormData['DATA']))
        {
            __W4aActionsEndResponse(array('ERROR' => 'NO_ADD_INFO_FORM_DATA'));
        }

        // calculate action
        $act = 'UPDATE';
        $tenderId = $arAddInfoFormData['ID'];
        if(empty($tenderId))
        {
            // проверяем, может кто-то уже создал запись
            $arRes = $componentClass->getTenderByDealId($dealId);
            if(empty($arRes['ID']))
                $act = 'ADD';
            else
                $tenderId = intval($arRes['ID']);
        }

        // actions
        switch ($act){
            case "ADD":
                $arFields = $componentClass->htmlSpecialCharsArray(
                    array_merge(
                        $arFields,
                        array(
                            'DEAL_ID' => $dealId,
                            'ASSIGNED_BY_ID' => $USER->GetID()
                        )
                    )
                );

                $db = TenderTable::add($arFields);
                if($db->isSuccess()) {
                    $arResult['TENDER_ID_NEW'] = $db->getID();
                    $arResult['TENDER'] = $componentClass->getTenderByDealId($dealId);
                }else {
                    __W4aActionsEndResponse(array('ERROR' => 'CAN_NOT_ADD_TENDER_TABLE', 'ERROR_DESCRIPTION' => $db->getErrors()));
                }
                break;
            case "UPDATE":
                $db = TenderTable::update($tenderId, $componentClass->htmlSpecialCharsArray($arFields));
                if ($db->isSuccess()) {
                    $arResult['TENDER'] = $componentClass->getTenderByDealId($dealId);
                }else {
                    __W4aActionsEndResponse(array('ERROR' => 'CAN_NOT_UPDATE_TENDER_TABLE', 'ERROR_DESCRIPTION' => $db->getErrors()));
                }
                break;
        }
        break;
    case 'SEND':
        if (!Bitrix\Main\Loader::includeModule('bizproc')) {
            __W4aActionsEndResponse(array('ERROR'=>'BIZPROC_NO_MODULE'));
        }
        $arResult = $arAddInfoFormData = $componentClass->getAddInfoFormData();
        $bpTemplateID = intval($arConfig['TENDERCALC_BP_ID']);
        if(!empty($bpTemplateID))
        {
            // BP Start
            // parameters for BP
            $arWorkflowParameters = array();
            $documentId = 'DEAL_' . $dealId;
            $arErrorsTmp = array();
            $wfId = CBPDocument::StartWorkflow(
                $bpTemplateID, // ИД_шаблона_ БП,
                array("bizproc", "CCrmDocumentDeal", $documentId),
                array_merge($arWorkflowParameters/*, array("TargetUser" => "user_".$userId)*/),
                $arErrorsTmp
            );
            $arResult = array(
                'wfId' => $wfId,
            );
        }
        else{
            __W4aActionsEndResponse(array('ERROR'=>'BIZPROC_ID_NOT_FOUND'));
        }

        break;
    case 'SEARCH_PRODUCT':
        if (empty($IBLOCK_ID = $arConfig['CATALOG_IBLOCK_ID']))
        {
            __W4aActionsEndResponse(array('ERROR' => 'NO_CATALOG_IBLOCK_ID'));
        }
        $word = $_POST['WORD'];
        $arProducts = array();
        if(!empty(str_replace(' ', '', $word)))
        {
            $arSelect = Array(
                "ID", "NAME"
            );
            $arFilter = Array(
                "IBLOCK_ID"=>$IBLOCK_ID,
                "%NAME"=>"$word",
            );

            $res = CIBlockElement::GetList(Array("NAME"=>"ASC"), $arFilter, false, Array("nPageSize"=>10), $arSelect);
            $arRes = array();
            while($ob = $res->GetNextElement())
            {
                $arRes[] = $ob->GetFields();
            }
            $arProducts = $arRes;
        }
        $arResult['PRODUCTS'] = $arProducts;
        break;

    default:
        __W4aActionsEndResponse(array('ERROR'=>'MODE_NOT_FOUND: ' . $mode));
        break;
}



$arResult = array(
    'RESULT' => $arResult,
    'POST' => $_POST,
    'ERROR' => false,
    '$arConfig' => $arConfig,
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
