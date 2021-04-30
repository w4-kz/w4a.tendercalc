<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$DEBUGGER = true;
$entityId = 0;
if($_REQUEST['PLACEMENT_OPTIONS']):

    $PLACEMENT_OPTIONS = json_decode($_REQUEST['PLACEMENT_OPTIONS']);
    $entityId = intval($PLACEMENT_OPTIONS->ID);
    ?>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <!-- Latest compiled and minified CSS -->
        <link rel="stylesheet" href="../../css/app.css">
        <!--link rel="stylesheet" href="http://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css"-->
        <!-- Additional CSS -->
        <link rel="stylesheet" href="../../css/w4a.add.css?ver=<?=time()?>">
        <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.13/jquery.mask.js"></script>
        <!-- Include all compiled plugins (below), or include individual files as needed -->
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
        <script src="//api.bitrix24.com/api/v1/dev/"></script>
        <script src="../../js/js.w4a.js?ver=<?=time()?>"></script>
    </head>
    <body>
    <?php
else:
    if(!$DEBUGGER) {
        exit('Access is denied. Should be required from application!!!');
    }
    $entityId = 18367; // TEST DEAL
endif;
?>

    <div>
        <? $APPLICATION->IncludeComponent(
            "w4a:tendercalc.product.list",
            ".default",
            Array(
                "ENTITY_ID" => $entityId,
                "DATA" => $_REQUEST,
                'CACHE_TIME'=>0, // 0-без кеша
            ),
            false
        );?>
    </div>
<?
//__w4a(array(
//        'VAR_NAME'=>'$_REQUEST', // Variable name
//        'CALLED_FROM'=>__FILE__, // Trace of file path
//        'VAR'=>$_REQUEST // Variable for debugger
//    )
//);
if($_REQUEST['PLACEMENT_OPTIONS']):
?>
    </body>

<?php
    exit();
endif;
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>