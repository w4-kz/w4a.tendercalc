<?php
/**
 * Created by PhpStorm.
 * User: sv
 * Date: 09.06.2018
 * Time: 14:23
 */
require_once ('./info/description.html');
require_once ('./info/dev_info.html');
require_once ('crest.php');

//echo "w4a-request: <pre>"; print_r($_REQUEST); echo "</pre>";

$placementOptions = array();
if(array_key_exists('PLACEMENT_OPTIONS', $_REQUEST))
{
	$placementOptions = json_decode($_REQUEST['PLACEMENT_OPTIONS'], true);
}

// массив разрешенных мест врезки для приложения
$arAllowPlacements =   array (
    // 'CRM_DEAL_LIST_MENU',
    // 'CRM_DEAL_DETAIL_TOOLBAR',
     'CRM_DEAL_DETAIL_TAB',
    // 'CRM_DEAL_DETAIL_ACTIVITY',

    // 'CRM_CONTACT_LIST_MENU',
    // 'CRM_CONTACT_DETAIL_TOOLBAR',
    //'CRM_CONTACT_DETAIL_TAB',
    // 'CRM_CONTACT_DETAIL_ACTIVITY',

    // 'CRM_COMPANY_LIST_MENU',
    // 'CRM_COMPANY_DETAIL_TOOLBAR',
    //'CRM_COMPANY_DETAIL_TAB',
    // 'CRM_COMPANY_DETAIL_ACTIVITY',

    // 'CRM_LEAD_LIST_MENU',
    // 'CRM_LEAD_DETAIL_TOOLBAR',
    // 'CRM_LEAD_DETAIL_TAB',
    // 'CRM_LEAD_DETAIL_ACTIVITY',

    // 'CRM_INVOICE_LIST_MENU',

    // 'CRM_QUOTE_LIST_MENU',

    // 'CRM_ACTIVITY_LIST_MENU',

    // 'TASK_TOP_MENU',
    // 'TASK_LIST_CONTEXT_MENU',
    // 'TASK_VIEW_MODE',
    // 'TASK_VIEW_TAB',
    // 'TASK_VIEW_SIDEBAR',
    // 'TASK_VIEW_TOP_PANEL',
    // 'TASK_VIEW_MENU_ADD',

    // 'CALENDAR_GRIDVIEW',

);

if (is_array($_REQUEST['placements'])) {

	foreach ($_REQUEST['placements'] as $placement)
    {
        $result = CRest::call('placement.unbind',
            array(
                'PLACEMENT' => $placement,
            )
        );

        if(!in_array($placement, $arAllowPlacements))
            continue;

        $result = CRest::call('placement.bind',
            array(
                'PLACEMENT' => $placement,
                'HANDLER' => 'https://'.$_REQUEST['DOMAIN'].'/w4a_apps/widget/tendercalc/handler.php',
                'TITLE' => 'Приложения'

            )
        );
    }
}
else {

	$result = CRest::call('placement.list',
		array()
	);
	foreach ($result['result'] as $placement)
    {
        // для переустановки: сначала удаляем ранее зарегистрированную врезку
        $result = CRest::call('placement.unbind',
            array(
                'PLACEMENT' => $placement,
            )
        );

        if(!in_array($placement, $arAllowPlacements))
            continue;

        $menuName = '';
        $description = '';

        $handlerFolder = 'https://'.$_REQUEST['DOMAIN'].'/w4a_apps/widget/tendercalc/handlers';
        $handlerFile = "/handler.php";
        $groupName = "";
        switch ($placement) {
                case 'CRM_LEAD_DETAIL_TAB':
                case 'CRM_DEAL_DETAIL_TAB':
                    $handlerFile .= '?tpl=tendercalc';
                    $menuName = 'Калькулятор';
                    $description = 'Калькулятор';
                    $groupName = 'Калькулятор';
                    break;
                default:
                    continue;
        }

        $handlerUrl = $handlerFolder . $handlerFile;
        // регистрируем врезку
        $result = CRest::call('placement.bind',
            array(
                'PLACEMENT' => $placement,
                'HANDLER' => $handlerUrl,
                'TITLE' => $menuName,
                'DESCRIPTION' => $description,
                'GROUP_NAME' => $groupName,

            )
        );
    }

	exit('');

}



