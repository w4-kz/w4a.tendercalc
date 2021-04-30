<?php
switch ($_REQUEST['tpl'])
{
    case 'tendercalc':
        include_once ('app.tendercalc.php');
        break;
    default:
        include_once ('.default.php');
        break;
}
