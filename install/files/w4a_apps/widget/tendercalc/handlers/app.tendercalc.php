<?
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
$fullData = true;
if($fullData)
    $data = http_build_query($_REQUEST);
else
    $data = 'PLACEMENT=' . $_REQUEST['PLACEMENT'] . '&PLACEMENT_OPTIONS=' . $_REQUEST['PLACEMENT_OPTIONS'];

//ACHTUNG!!! необходимо добавлять sessid, иначе приложение не будет проходить проверку на сессию.
//&sessid=7093b24b5d8ee51e58884e15ef21170b
$data .= '&' . bitrix_sessid_get();
//&sessid_app=7093b24b5d8ee51e58884e15ef21170b
$data .= '&sessid_app=' . bitrix_sessid();

header('Location: https://portal.norsken-oil.ru/w4a_apps/widget/tendercalc/pages/tendercalc/?IFRAME=Y&' . $data);
die();
