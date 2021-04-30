<?php
defined('B_PROLOG_INCLUDED') || die;

use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Main\UserTable;
use Bitrix\Main\Grid;
use Bitrix\Main\UI\Filter;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;

use W4a\Tendercalc\Entity\ConfigTable;
use W4a\Tendercalc\Entity\ConfigUfTable;
use W4a\Tendercalc\Entity\ProductListTable;
use W4a\Tendercalc\Entity\TenderTable;
use W4a\Tendercalc\Entity\UsersTable;


class CW4aTendercalcProductListComponent extends CBitrixComponent
{
    const GRID_ID = 'CRMTENDERS_LIST';
    const SORTABLE_FIELDS = array('ID', 'PRODUCT_NAME_ORIG', 'ASSIGNED_BY_ID', 'DELIVERY_ADDRESS');
    const FILTERABLE_FIELDS = array();
    const SUPPORTED_ACTIONS = array('delete', 'edit');
    const SUPPORTED_SERVICE_ACTIONS = array('GET_ROW_COUNT');

    private static $headers;
    private static $filterFields;
    private static $filterPresets;
    public $arParams;

    public function __construct(CBitrixComponent $component = null)
    {
        global $USER;

        parent::__construct($component);

        self::$headers = array(
            array(
                'id' => 'ID',
                'name' => Loc::getMessage('CRMTENDERS_HEADER_ID'),
                'sort' => 'ID',
                'first_order' => 'desc',
                'type' => 'int',
            ),
            array(
                'id' => 'PRODUCT_NAME_ORIG',
                'name' => Loc::getMessage('CRMTENDERS_HEADER_PRODUCT_NAME_ORIG'),
                'sort' => 'PRODUCT_NAME_ORIG',
                'default' => true,
            ),
            array(
                'id' => 'PRODUCT_NAME_SPEC',
                'name' => Loc::getMessage('CRMTENDERS_HEADER_PRODUCT_NAME_SPEC'),
                'sort' => 'PRODUCT_NAME_SPEC',
                'default' => true,
            ),
            array(
                'id' => 'DELIVERY_DATE',
                'name' => Loc::getMessage('CRMTENDERS_HEADER_DELIVERY_DATE'),
                'sort' => 'DELIVERY_DATE',
                'default' => true,
            ),
            array(
                'id' => 'DELIVERY_ADDRESS',
                'name' => Loc::getMessage('CRMTENDERS_HEADER_DELIVERY_ADDRESS'),
                'sort' => 'DELIVERY_ADDRESS',
                'default' => true,
            ),
            array(
                'id' => 'MEASURE',
                'name' => Loc::getMessage('CRMTENDERS_HEADER_MEASURE'),
                'sort' => 'MEASURE',
                'default' => true,
            ),
            array(
                'id' => 'QUANTITY_REQUEST',
                'name' => Loc::getMessage('CRMTENDERS_HEADER_QUANTITY_REQUEST'),
                'sort' => 'QUANTITY_REQUEST',
                'default' => true,
            ),
            array(
                'id' => 'PACKING',
                'name' => Loc::getMessage('CRMTENDERS_HEADER_PACKING'),
                'sort' => 'PACKING',
                'default' => true,
            ),
            array(
                'id' => 'QUANTITY',
                'name' => Loc::getMessage('CRMTENDERS_HEADER_QUANTITY'),
                'sort' => 'QUANTITY',
                'default' => true,
            ),


            array(
                'id' => 'PRICE_PURCHASE',
                'name' => Loc::getMessage('CRMTENDERS_HEADER_PRICE_PURCHASE'),
                'sort' => 'PRICE_PURCHASE',
                'default' => true,
            ),
            array(
                'id' => 'PROFIT_RATIO',
                'name' => Loc::getMessage('CRMTENDERS_HEADER_PROFIT_RATIO'),
                'sort' => 'PROFIT_RATIO',
                'default' => true,
            ),
            array(
                'id' => 'PRICE_NMCK',
                'name' => Loc::getMessage('CRMTENDERS_HEADER_PRICE_NMCK'),
                'sort' => 'PRICE_NMCK',
                'default' => true,
            ),
            array(
                'id' => 'PRICE_IN_SPECIAL',
                'name' => Loc::getMessage('CRMTENDERS_HEADER_PRICE_IN_SPECIAL'),
                'sort' => 'PRICE_IN_SPECIAL',
                'default' => true,
            ),
            array(
                'id' => 'PRICE_IN_SPECIAL_SUM',
                'name' => Loc::getMessage('CRMTENDERS_HEADER_PRICE_IN_SPECIAL_SUM'),
                'sort' => 'PRICE_IN_SPECIAL_SUM',
                'default' => true,
            ),
            array(
                'id' => 'PRICE_IN_DISTRIBUTOR',
                'name' => Loc::getMessage('CRMTENDERS_HEADER_PRICE_IN_DISTRIBUTOR'),
                'sort' => 'PRICE_IN_DISTRIBUTOR',
                'default' => true,
            ),
            array(
                'id' => 'PRICE_IN_DISTRIBUTOR_SUM',
                'name' => Loc::getMessage('CRMTENDERS_HEADER_PRICE_IN_DISTRIBUTOR_SUM'),
                'sort' => 'PRICE_IN_DISTRIBUTOR_SUM',
                'default' => true,
            ),
            /* ЗАКУП ДИСТРИБЬЮТОРСКАЯ + СТОИМОСТЬ ДОСТАВКИ */
            array(
                'id' => 'PRICE_IN_DISTRIBUTOR_DELIVERY',
                'name' => Loc::getMessage('CRMTENDERS_HEADER_PRICE_IN_DISTRIBUTOR_DELIVERY'),
                'sort' => 'PRICE_IN_DISTRIBUTOR_DELIVERY',
                'default' => true,
            ),
            array(
                'id' => 'PRICE_IN_DISTRIBUTOR_DELIVERY_SUM',
                'name' => Loc::getMessage('CRMTENDERS_HEADER_PRICE_IN_DISTRIBUTOR_DELIVERY_SUM'),
                'sort' => 'PRICE_IN_DISTRIBUTOR_DELIVERY_SUM',
                'default' => true,
            ),
            array(
                'id' => 'PRICE_IN_DISTRIBUTOR_WITH_DELIVERY_SUM',
                'name' => Loc::getMessage('CRMTENDERS_HEADER_PRICE_IN_DISTRIBUTOR_WITH_DELIVERY_SUM'),
                'sort' => 'PRICE_IN_DISTRIBUTOR_WITH_DELIVERY_SUM',
                'default' => true,
            ),
            array(
                'id' => 'PRICE_IN_DISTRIBUTOR_TOTAL',
                'name' => Loc::getMessage('CRMTENDERS_HEADER_PRICE_IN_DISTRIBUTOR_TOTAL'),
                'sort' => 'PRICE_IN_DISTRIBUTOR_TOTAL',
                'default' => true,
            ),
            /* ЗАКУП СПЕЦЦЕНА + СТОИМОСТЬ ДОСТАВКИ */
            array(
                'id' => 'PRICE_IN_SPECIAL_DELIVERY',
                'name' => Loc::getMessage('CRMTENDERS_HEADER_PRICE_IN_SPECIAL_DELIVERY'),
                'sort' => 'PRICE_IN_SPECIAL_DELIVERY',
                'default' => true,
            ),
            array(
                'id' => 'PRICE_IN_SPECIAL_DELIVERY_SUM',
                'name' => Loc::getMessage('CRMTENDERS_HEADER_PRICE_IN_SPECIAL_DELIVERY_SUM'),
                'sort' => 'PRICE_IN_SPECIAL_DELIVERY_SUM',
                'default' => true,
            ),
            array(
                'id' => 'PRICE_IN_SPECIAL_WITH_DELIVERY_SUM',
                'name' => Loc::getMessage('CRMTENDERS_HEADER_PRICE_IN_SPECIAL_WITH_DELIVERY_SUM'),
                'sort' => 'PRICE_IN_SPECIAL_WITH_DELIVERY_SUM',
                'default' => true,
            ),
            array(
                'id' => 'PRICE_IN_SPECIAL_TOTAL',
                'name' => Loc::getMessage('CRMTENDERS_HEADER_PRICE_IN_SPECIAL_TOTAL'),
                'sort' => 'PRICE_IN_SPECIAL_TOTAL',
                'default' => true,
            ),
            /* ПРОДАЖА ПО ДИСТРИБЬЮТОРСКОЙ ЦЕНЕ */
            array(
                'id' => 'PROFIT_RATIO_DISTRIBUTOR',
                'name' => Loc::getMessage('CRMTENDERS_HEADER_PROFIT_RATIO_DISTRIBUTOR'),
                'sort' => 'PROFIT_RATIO_DISTRIBUTOR',
                'default' => true,
            ),
            array(
                'id' => 'PRICE_OUT_DISTRIBUTOR',
                'name' => Loc::getMessage('CRMTENDERS_HEADER_PRICE_OUT_DISTRIBUTOR'),
                'sort' => 'PRICE_OUT_DISTRIBUTOR',
                'default' => true,
            ),
            array(
                'id' => 'PRICE_OUT_DISTRIBUTOR_SUM',
                'name' => Loc::getMessage('CRMTENDERS_HEADER_PRICE_OUT_DISTRIBUTOR_SUM'),
                'sort' => 'PRICE_OUT_DISTRIBUTOR_SUM',
                'default' => true,
            ),
            array(
                'id' => 'PRICE_OUT_DISTRIBUTOR_GROSS_SUM',
                'name' => Loc::getMessage('CRMTENDERS_HEADER_PRICE_OUT_DISTRIBUTOR_GROSS_SUM'),
                'sort' => 'PRICE_OUT_DISTRIBUTOR_GROSS_SUM',
                'default' => true,
            ),
            array(
                'id' => 'PRICE_OUT_DISTRIBUTOR_GROSS_PERCENT',
                'name' => Loc::getMessage('CRMTENDERS_HEADER_PRICE_OUT_DISTRIBUTOR_GROSS_PERCENT'),
                'sort' => 'PRICE_OUT_DISTRIBUTOR_GROSS_PERCENT',
                'default' => true,
            ),



            /* ПРОДАЖА ПО СПЕЦЦЕНЕ ЦЕНЕ */
            array(
                'id' => 'PROFIT_RATIO_SPECIAL',
                'name' => Loc::getMessage('CRMTENDERS_HEADER_PROFIT_RATIO_SPECIAL'),
                'sort' => 'PROFIT_RATIO_SPECIAL',
                'default' => true,
            ),
            array(
                'id' => 'PRICE_OUT_SPECIAL',
                'name' => Loc::getMessage('CRMTENDERS_HEADER_PRICE_OUT_SPECIAL'),
                'sort' => 'PRICE_OUT_SPECIAL',
                'default' => true,
            ),
            array(
                'id' => 'PRICE_OUT_SPECIAL_SUM',
                'name' => Loc::getMessage('CRMTENDERS_HEADER_PRICE_OUT_SPECIAL_SUM'),
                'sort' => 'PRICE_OUT_SPECIAL_SUM',
                'default' => true,
            ),
            array(
                'id' => 'PRICE_OUT_SPECIAL_GROSS_SUM',
                'name' => Loc::getMessage('CRMTENDERS_HEADER_PRICE_OUT_SPECIAL_GROSS_SUM'),
                'sort' => 'PRICE_OUT_SPECIAL_GROSS_SUM',
                'default' => true,
            ),
            array(
                'id' => 'PRICE_OUT_SPECIAL_GROSS_PERCENT',
                'name' => Loc::getMessage('CRMTENDERS_HEADER_PRICE_OUT_SPECIAL_GROSS_PERCENT'),
                'sort' => 'PRICE_OUT_SPECIAL_GROSS_PERCENT',
                'default' => true,
            ),
        );

        self::$filterFields = array();

        self::$filterPresets = array();
    }

    public function executeComponent()
    {
        global $USER;

        if (!Loader::includeModule('w4a.tendercalc')) {
            ShowError(Loc::getMessage('CRMTENDERS_NO_MODULE'));
            return;
        }
        if(empty($dealId = intval($this->arParams['ENTITY_ID'])))
        {
            ShowError(Loc::getMessage('CRMTENDERS_NO_DEAL_ID'));
        }

        // UsersTable
        $params = array(
            'filter' => array(
                'DEAL_ID' => $dealId,
            )
        );
        $db = UsersTable::getList($params);
        $arUsers = $db->fetch();
        if(
               ($this->arParams['USERS']['IS_PRODUCTION_USER'])
            || ($this->arParams['USERS']['IS_SALES_USER'])
            || ($this->arParams['USERS']['IS_LOGISTICS_USER'])
        )
        {

            if(!empty(intval($arUsers['ID'])))
            {
                $arFields = array();
                foreach ($this->arParams['USERS'] as $key => $val)
                {
                    if($val === true)
                    {
                        $key = str_replace('IS_', '', $key) . '_ID';
                        if(empty(intval($arUsers[$key]))) {
                            $arFields[$key] = $USER->GetID();
                            $arUsers[$key] = $USER->GetID();
                        }
                    }
                }
                if(!empty(count($arFields)))
                    UsersTable::update(intval($arUsers['ID']), $arFields);
            }
        }
        foreach ($arUsers as $key=>$userId)
        {
            if(strpos($key,'_USER_ID') !== false)
            {
                $db = UserTable::getList(array(
                    'filter' => array('=ID' => $userId)
                ));
                $arUsers[str_replace('_ID', '', $key)] = $db->fetch();
            }

        }
        $context = Context::getCurrent();
        $request = $context->getRequest();

        $grid = new Grid\Options(self::GRID_ID);

        //region Sort
        $gridSort = $grid->getSorting();
        $sort = array_filter(
            $gridSort['sort'],
            function ($field) {
                return in_array($field, self::SORTABLE_FIELDS);
            },
            ARRAY_FILTER_USE_KEY
        );
        if (empty($sort)) {
            $sort = array('PRODUCT_NAME_ORIG' => 'asc');
        }
        //endregion

        //region Filter
        $gridFilter = new Filter\Options(self::GRID_ID, self::$filterPresets);
        $gridFilterValues = $gridFilter->getFilter(self::$filterFields);
        $gridFilterValues = array_filter(
            $gridFilterValues,
            function ($fieldName) {
                return in_array($fieldName, self::FILTERABLE_FIELDS);
            },
            ARRAY_FILTER_USE_KEY
        );
        //endregion
        $gridFilterValues = array_merge(array('DEAL_ID' => $dealId, $gridFilterValues));
        $this->processGridActions($gridFilterValues);
        $this->processServiceActions($gridFilterValues);

        //region Pagination
        $gridNav = $grid->GetNavParams();
        $pager = new PageNavigation('');
        $pager->setPageSize($gridNav['nPageSize']);
        $pager->setRecordCount(ProductListTable::getCount($gridFilterValues));
        if ($request->offsetExists('page')) {
            $currentPage = $request->get('page');
            $pager->setCurrentPage($currentPage > 0 ? $currentPage : $pager->getPageCount());
        } else {
            $pager->setCurrentPage(1);
        }
        //endregion
        $tender = self::getTender($dealId);

        $products = $this->getTenderProductList(array(
            'filter' => $gridFilterValues,
            'limit' => $pager->getLimit(),
            'offset' => $pager->getOffset(),
            'order' => $sort
        ));

        $requestUri = new Uri($request->getRequestedPage());
        $requestUri->addParams(array('sessid' => bitrix_sessid()));

        $this->arResult = array(
            'GRID_ID' => self::GRID_ID,
            'TENDER_PRODUCTS' => $products,
            'HEADERS' => self::$headers,
            /*'PAGINATION' => array(
                'PAGE_NUM' => $pager->getCurrentPage(),
                'ENABLE_NEXT_PAGE' => $pager->getCurrentPage() < $pager->getPageCount(),
                'URL' => $request->getRequestedPage(),
            ),*/
            'PAGINATION' => array(
                'PAGE_NUM' => 1,
                'ENABLE_NEXT_PAGE' => false,
                'URL' => $request->getRequestedPage(),
            ),
            'SORT' => $sort,
            'FILTER' => self::$filterFields,
            'FILTER_PRESETS' => self::$filterPresets,
            'ENABLE_LIVE_SEARCH' => false,
            'DISABLE_SEARCH' => true,
            'SERVICE_URL' => $requestUri->getUri(),
            'COMPONENT_ID' => $this->randString(),
            'TENDER' => $tender,
            'USERS' => $arUsers,
            'CONFIG' => self::getConfig(),
            'CONFIG_UF' => self::getConfigUf(),
        );

        $this->includeComponentTemplate();
    }
    private function getTender($dealId)
    {
        if(empty($dealId))
            return false;
        $params = array('filter' => array('DEAL_ID' => $dealId), 'order' => array('ID' => 'desc'));
        $dbTenders = TenderTable::getList($params);
        $arResult = $dbTenders->fetch();

        if(!isset($arResult['ID']))
            return false;

        return $arResult;
    }
    private function getTenderProductList($params = array())
    {
        $dbTenders = ProductListTable::getList($params);
        $products = $dbTenders->fetchAll();

        $userIds = array_column($products, 'ASSIGNED_BY_ID');
        $userIds = array_unique($userIds);
        $userIds = array_filter(
            $userIds,
            function ($userId) {
                return intval($userId) > 0;
            }
        );

        $dbUsers = UserTable::getList(array(
            'filter' => array('=ID' => $userIds)
        ));
        $users = array();
        foreach ($dbUsers as $user) {
            $users[$user['ID']] = $user;
        }

        foreach ($products as &$tender) {
            if (intval($tender['ASSIGNED_BY_ID']) > 0) {
                $tender['ASSIGNED_BY'] = $users[$tender['ASSIGNED_BY_ID']];
            }
        }

        return $products;
    }

    private function processGridActions($currentFilter)
    {

        if(!self::check_bitrix_sessid())
            return;

        $context = Context::getCurrent();
        $request = $context->getRequest();
        $action = $request->get('action_button_' . self::GRID_ID);

        if (!in_array($action, self::SUPPORTED_ACTIONS)) {
            return;
        }

        $allRows = $request->get('action_all_rows_' . self::GRID_ID) == 'Y';
        if ($allRows) {
            $dbTenders = ProductListTable::getList(array(
                'filter' => $currentFilter,
                'select' => array('ID'),
            ));
            $tenderIds = array();
            foreach ($dbTenders as $tender) {
                $tenderIds[] = $tender['ID'];
            }
        } else {
            $tenderIds = $request->get('ID');
            if (!is_array($tenderIds)) {
                $tenderIds = array();
            }
        }

        \Bitrix\Main\Diag\Debug::writeToFile(print_r(bitrix_sessid_get(), true), 'bitrix_sessid_get(): ' . date('d.m.Y H:i:s'), "__w4a_request.log");
        \Bitrix\Main\Diag\Debug::writeToFile(print_r(self::check_bitrix_sessid(), true), 'self::check_bitrix_sessid(): ' . date('d.m.Y H:i:s'), "__w4a_request.log");

        \Bitrix\Main\Diag\Debug::writeToFile(print_r($request, true), '$request: ' . date('d.m.Y H:i:s'), "__w4a_request.log");
        \Bitrix\Main\Diag\Debug::writeToFile(print_r($action, true), '$action: ' . date('d.m.Y H:i:s'), "__w4a_request.log");
        \Bitrix\Main\Diag\Debug::writeToFile(print_r($_REQUEST, true), '$_REQUEST: ' . date('d.m.Y H:i:s'), "__w4a_request.log");
        \Bitrix\Main\Diag\Debug::writeToFile(print_r($this->arParams, true), '$this->arParams: ' . date('d.m.Y H:i:s'), "__w4a_request.log");


        if (empty($tenderIds)) {
            return;
        }

        switch ($action) {
            case 'delete':
                foreach ($tenderIds as $tenderId)
                {
                    ProductListTable::delete($tenderId);
                }
            break;

            default:
            break;
        }
    }
    private function processServiceActions($currentFilter)
    {
        global $APPLICATION;

        if(!self::check_bitrix_sessid())
            return;

        $context = Context::getCurrent();
        $request = $context->getRequest();

        $params = $request->get('PARAMS');

        if (empty($params['GRID_ID']) || $params['GRID_ID'] != self::GRID_ID) {
            return;
        }

        $action = $request->get('ACTION');

        if (!in_array($action, self::SUPPORTED_SERVICE_ACTIONS)) {
            return;
        }

        $APPLICATION->RestartBuffer();
        header('Content-Type: application/json');

        switch ($action) {
            case 'GET_ROW_COUNT':
                $count = ProductListTable::getCount($currentFilter);
                echo Json::encode(array(
                    'DATA' => array(
                        'TEXT' => Loc::getMessage('CRMTENDERS_GRID_ROW_COUNT', array('#COUNT#' => $count))
                    )
                ));
            break;

            default:
            break;
        }

        die;
    }
    private function check_bitrix_sessid(): bool
    {
        if(!check_bitrix_sessid())
        {
            if(bitrix_sessid() != $this->arParams['DATA']['sessid_app'])
                return false;
        }
        return true;
    }

    /** Using into ajax */
    public function getAddInfoFormData()
    {
        if(!isset($_POST['DATA']))
            return false;

        $data = $_POST['DATA'];
        $arRes = array();
        $tenderId = 0;
        foreach ($data as $val){
            if($val['name'] == 'ID')
            {
                $tenderId = intval($val['value']);
                continue;
            }
            if($val['type'] == 'date')
            {
                if(!empty($val['value'])) {
                    $arRes[$val['name']] = new \Bitrix\Main\Type\DateTime(
                        CDatabase::FormatDate(
                            $val['value'],
                            "YYYY-MM-DD HH:MI:SS",
                            CSite::GetDateFormat()
                        )
                    );
                }else {
                    $arRes[$val['name']] = new \Bitrix\Main\Type\DateTime(0);
                }
                continue;
            }
            $arRes[$val['name']] = $val['value'];
        }
        return array('ID' => $tenderId, 'DATA' => $arRes);
    }
    public function getTenderByDealId($dealId)
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
    public function getProductListByDealId($dealId): array
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
    public function htmlSpecialCharsArray($ar): array
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
    public function checkCompletedSendMsg($dealId){
        $checkFields = array(
            'PRICE_IN_SPECIAL',
            'PRICE_IN_DISTRIBUTOR'
        );
        $tender = self::getTenderByDealId($dealId);
        $isCompleted = ($tender['IS_COMPLETED'] == 'Y');
        if($isCompleted)
            return false;

        $arRes = self::getProductListByDealId($dealId);
        if(empty(count($arRes)))
            return false;
        foreach ($arRes as $val)
        {
            foreach ($checkFields as $field)
                if(empty($val[$field]))
                    return false;
        }
        return true;
    }
    public function setIsCompleted($dealId, $isCompleted = true)
    {
        $tender = self::getTenderByDealId($dealId);

        if(!empty($id = $tender['ID']))
        {
            TenderTable::update($id, array('IS_COMPLETED' => $isCompleted));
        }
        return true;
    }
    public function getConfig(){
        $params = array(
            'filter' => array()
        );
        $db = ConfigTable::getList($params);
        $arResult = array();
        while($arRes = $db->fetch())
        {
            $arResult[$arRes['NAME']] = $arRes['VALUE'];
        }
        return $arResult;
    }
    public function getConfigUf(){
        $params = array(
            'filter' => array()
        );
        $db = ConfigUfTable::getList($params);
        $arResult = array();
        while($arRes = $db->fetch())
        {
            $arResult[$arRes['NAME']] = $arRes['VALUE'];
        }
        return $arResult;
    }

}