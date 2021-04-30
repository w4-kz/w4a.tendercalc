BX.namespace("BX.Crm");
if (typeof(BX.W4aCrmTendercalc) === "undefined") {
    BX.W4aCrmTendercalc = function () {
        this._id = "";
        this._settings = {};
        this._serviceUrl = "";
        this._selectedProducts = [];
        this._inputTypes = ['text', 'date']; // типы используемых полей
    };
    BX.W4aCrmTendercalc.prototype = {
        initialize: function (id, config) {
            this._id = id;
            this._settings = config ? config : {};
            this._serviceUrl = this.getSetting('serviceUrl', '');
            if (!BX.type.isNotEmptyString(this._serviceUrl)) {
                throw 'W4aCrmTendercalc: could not find service URL.';
            }
            let saveBtn = BX('w4a-btn-add');
            if (saveBtn) {
                BX.bind(
                    saveBtn,
                    "click",
                    BX.delegate(this.handlerAdd, this)
                );
            }
            // дополнительная информация
            let addInfoBtn = BX('w4a-btn-add-info');
            if (addInfoBtn) {
                BX.bind(
                    addInfoBtn,
                    "click",
                    BX.delegate(this.actionAddInfoForm, this)
                );
            }
            // дополнительная информация
            let addInfoSaveBtn = BX('w4a-btn-add-info-save');
            if (addInfoSaveBtn) {
                BX.bind(
                    addInfoSaveBtn,
                    "click",
                    BX.delegate(this.handlerAddInfoSave, this)
                );
            }
            // отправить на расчеты
            let sendBtn = BX('w4a-btn-send');
            if (sendBtn) {
                BX.bind(
                    sendBtn,
                    "click",
                    BX.delegate(this.handlerSend, this)
                );
            }
            // Event: Grid::selectRow
            BX.addCustomEvent(
                'Grid::selectRow',
                BX.delegate(this._eventGridSelectRow, this)
            );
            // Event: Grid::unselectRow
            BX.addCustomEvent(
                'Grid::unselectRow',
                BX.delegate(this._eventGridUnselectRow, this)
            );

            // Event: Grid::allRowsSelected
            BX.addCustomEvent(
                'Grid::allRowsSelected',
                BX.delegate(this._eventGridAllRowsSelected, this)
            );
            // Event: Grid::allRowsUnselected
            BX.addCustomEvent(
                'Grid::allRowsUnselected',
                BX.delegate(this._eventGridAllRowsUnselected, this)
            );

            console.log('this');
            console.log(this);
        },
        // Grid events
        _eventGridAllRowsSelected: function(obj){
            let productIDs = [];
            let gridId = this.getSetting('gridId', '')
            let rows = obj.rows['rows'];
            for(let i=0; i<rows.length; i++)
            {
                let checkboxId = String(rows[i].checkbox.id);
                let id = parseInt(checkboxId.replace('checkbox_'+gridId+'_', ''));
                if(id > 0) {
                    productIDs.push(id);
                }
            }
            this._selectedProducts = productIDs;
            this.selectedRowEditable();
        },
        _eventGridAllRowsUnselected: function(){
            this.selectedRowUneditable();
            this._selectedProducts = [];
        },
        _eventGridSelectRow: function(obj){
            let productId = parseInt(obj.checkbox.attributes.value['value']);
            let productIDs = this._selectedProducts;
            productIDs.push(productId);
            this._selectedProducts = productIDs;
            this.productSelect(productId);
        },
        _eventGridUnselectRow: function(obj){
            let productIDs = this._selectedProducts;
            let productId = parseInt(obj.checkbox.attributes.value['value']);
            for (let i=0; i<productIDs.length; i++)
            {
                let prodId = productIDs[i];
                if(prodId === productId)
                {
                    productIDs.splice(i, 1);
                }
            }
            this._selectedProducts = productIDs;
            this.productUnselect(productId);
            this.hideProductPopup(productId);
        },
        selectedRowEditable: function(){
            let productIDs = this._selectedProducts;
            for(let i=0; i<productIDs.length; i++)
            {
                let productId = productIDs[i];
                this.productSelect(productId);
            }
        },
        selectedRowUneditable: function(){
            let productIDs = this._selectedProducts;
            for(let i=0; i<productIDs.length; i++)
            {
                let productId = productIDs[i];
                this.productUnselect(productId);
            }
        },

        getSetting: function (name, defaultval) {
            return typeof (this._settings[name]) != 'undefined' ? this._settings[name] : defaultval;
        },
        setSetting:function(name, value){
            this._settings[name] = value;
        },
        setProducts:function(products){
            this.setSetting('items', products);
        },
        setTender:function(tender){
            this.setSetting('tender', tender);
        },
        // products form
        getProducts: function (){
            let gridTable = BX(this.getSetting('gridId', '') + '_table');
            let obj = $(gridTable).find('tr.main-grid-row');
            // собираем массив товаров
            let products = {};
            $(obj).each(function(i,e)
            {
                let product = {};
                let productId = 0;
                let key = '';
                if(!$(e).hasClass('main-grid-not-count'))
                {
                    productId = $(e).data("id");
                    let o = $(e).find('input.ui-ctl-element');
                    // product = {};
                    $(o).each(function(index,el)
                    {
                        key = $(el).data("name");
                        product[key] = $(el).val();
                        if(key === 'PRODUCT_NAME_ORIG')
                            product['PRODUCT_ID'] = $(el).data('product-id');
                    });
                    products[productId] = product;
                }
            });
            return products;
        },
        getSelectedProducts: function (){
            let selectedProducts = {};
            let selectedIDs = this._selectedProducts;
            let products = this.getProducts();
            for(let i=0; i<selectedIDs.length; i++)
            {
                let productId = selectedIDs[i];
                selectedProducts[productId] = products[productId];
            }
            return selectedProducts;
        },
        productSelect: function(productId){
            let self = this;
            for (let i=0; i<this._inputTypes.length; i++)
            {
                let inputType = this._inputTypes[i];
                let obj = $('tr.main-grid-row[data-id="'+productId+'"]').find('input[type="'+inputType+'"]');
                $(obj).each(function(i, e){
                    if(e.id === 'product_' + productId)
                    {
                        let productInput = BX('product_' + productId);
                        BX.bind(
                            productInput,
                            'keyup',
                            BX.proxy(self.handlerSearchProducts, self)
                        );

                    }
                    $(e).removeAttr('disabled');
                });
            }
        },
        productUnselect: function(productId){
            let self = this;
            for (let i=0; i<this._inputTypes.length; i++)
            {
                let inputType = this._inputTypes[i];
                let obj = $('tr.main-grid-row[data-id="'+productId+'"]').find('input[type="'+inputType+'"]');
                $(obj).each(function(i, e){
                    if(e.id === 'product_' + productId)
                    {
                        let productInput = BX('product_' + productId);
                        BX.unbind(
                            productInput,
                            "keyup"
                        );
                    }
                    $(e).attr('disabled', 'disabled');
                });
            }

        },
        hideProductPopupOthers: function(productId){
            let selectedProducts = this._selectedProducts;
            for(let i=0; i<selectedProducts.length; i++)
            {
                if(selectedProducts[i] !== productId) {
                    this.hideProductPopup(selectedProducts[i]);
                }
            }
        },
        hideProductPopup: function(productId){
             let objPopup = BX('menu-popup-w4a-search-product_' + productId);
             if(typeof $(objPopup).attr('id') !== 'undefined')
             {
                 BX.hide(objPopup);
             }
        },
        getProductIdByElementId: function(elementId)
        {
            return parseInt(elementId.replace('product_', ''));
        },
        choiceProduct: function(elementId, data)
        {
            let productId = this.getProductIdByElementId(elementId);
            let obj = $('#' + elementId);
            $(obj).val(data['NAME']);
            $(obj).data('product-id', data['ID']);
        },
        searchProductsPopup: function (data)
        {
            let self = this;
            let products = data['RESULT']['PRODUCTS'];
            let elementId = data['POST']['ELEMENT_ID'];
            let productId = this.getProductIdByElementId(elementId);
            // ID-ники
            let uniqId = 'w4a-search-' + elementId;
            let popupContainer = 'menu-popup-' + uniqId;
            let objPopup = BX('popup-window-content-menu-popup-' + uniqId);

            // закрываем все открытые popup-ы кроме по текущему товару (productId)
            this.hideProductPopupOthers(productId);
            if(products.length > 0)
            {
                // проверка на наличие созданного объекта
                if(typeof $(objPopup).attr('id') === 'undefined')
                {
                    let menu = [];
                    for(let i=0; i<products.length; i++)
                    {
                        menu.push(
                            {
                                text: products[i]['NAME'], // Название пункта
                                href: '#', // Ссылка
                                id: products[i]['ID'],
                                className: 'menu-popup-item menu-popup-no-icon', // Дополнительные классы
                                onclick: function(e, item){
                                    BX.PreventDefault(e);
                                    // Событие при клике на пункт
                                    self.choiceProduct(elementId, products[i]);
                                }
                            }
                        );
                    }

                    BX.PopupMenu.show(uniqId, BX(elementId), menu, {
                        autoHide : true, // Закрытие меню при клике вне меню
                        offsetTop: 0, // смещение от элемента по Y
                        zIndex: 10000, // z-index
                        offsetLeft: 100,  // смещение от элемента по X
                        angle: { offset: 45 }, // Описание уголка, при null – уголка не будет
                        events: {
                            onPopupShow: function() {
                                // Событие при показе меню
                            },
                            onPopupClose : function(){
                                // Событие при закрытии меню
                            },
                            onPopupClose : function(){
                                // Событие при уничтожении объекта меню
                            }
                        }
                    });
                }
                else
                {
                    let htmlMenuItems = '';
                    for(let i=0; i<products.length; i++)
                    {
                        let text = products[i]['NAME'];
                        let prodId = products[i]['ID'];
                        let href = '#';
                        htmlMenuItems += `
                        <a onclick="BX.W4aCrmTender.choiceProduct('` + elementId + `', {'ID':'` + products[i]['ID'] + `','NAME':'` + products[i]['NAME'] + `'}); return false;" class="menu-popup-item menu-popup-item menu-popup-no-icon " href="`+href+`">
                        <span class="menu-popup-item-icon">
                        </span>
                        <span class="menu-popup-item-text">`+text+`</span>
                        </a>                
                    `;
                    }
                    let htmlMenu = `
                <div class="menu-popup" style="display: block;">
                    <div class="menu-popup-items">
                        `+htmlMenuItems+`
                    </div>
                    </div>
                `;
                    $(objPopup).html(htmlMenu);
                    if(products.length > 0)
                        BX.show(BX(popupContainer));
                }
            }
            else
            {
                this.hideProductPopup(productId);
            }
        },
        // add info form
        getAddInfoFormData: function (){
            let data = [];
            let objForm = BX('w4a-calc-options-container');
            let objInput = $(objForm).find('input');
            $(objInput).each(function(i,e){
                if($(e).data('name'))
                {
                    let dat = {
                        "type" : $(e).attr('type'),
                        "name" : $(e).data('name'),
                        "value" : $(e).val(),
                    };
                    data.push(dat);
                }
            });
            return data;
        },
        actionAddInfoForm: function(){
            let addInfoBtn = BX('w4a-btn-add-info');
            if(BX.hasClass(addInfoBtn, 'ui-btn-primary-dark')) {
                BX.addClass(addInfoBtn, 'ui-btn-danger-dark');
                BX.removeClass(addInfoBtn, 'ui-btn-primary-dark');
                this.showAddInfoForm();
            }
            else{
                BX.addClass(addInfoBtn, 'ui-btn-primary-dark');
                BX.removeClass(addInfoBtn, 'ui-btn-danger-dark');
                this.hideAddInfoForm();
            }
        },
        showAddInfoForm: function(){
            BX.show(BX('w4a-calc-options-container'));
            BX('w4a-btn-add-info').innerHTML = BX.message("JS_W4A_ACTION_CLOSE_TEXT");
        },
        hideAddInfoForm: function(){
            BX.hide(BX('w4a-calc-options-container'));
            BX('w4a-btn-add-info').innerHTML = BX.message("JS_W4A_ACTION_OPEN_TEXT");
        },
        // handlers
        handlerAdd:function(){
            let postData = {
                'MODE': 'ADD',
                'OWNER_TYPE': this.getSetting('ownerType', ''),
                'OWNER_ID': this.getSetting('id', 0),
                'DEAL_ID': this.getSetting('dealId', 0),
                'PERMISSION_ENTITY_TYPE': this.getSetting('permissionEntityType', ''),
                'SITE_ID': this.getSetting('siteId', ''),
                'sessId': this.getSetting('sessId', ''),
            };
            BX.ajax({
                'url': this._serviceUrl,
                'method': "POST",
                'dataType': "json",
                'data': postData,
                onsuccess: BX.delegate(this._onActionRequestSuccess, this),
                onfailure: BX.delegate(this._onActionRequestFailure, this)
            });
        },
        handlerEdit:function(){
            this.handlerSave();
            let gridId = this.getSetting('gridId', '');
            this.reloadTable(gridId);
        },
        handlerSave:function(){
            let products = [];
            products = this.getSelectedProducts();
            let postData = {
                'MODE': 'SAVE',
                'OWNER_TYPE': this.getSetting('ownerType', ''),
                'OWNER_ID': this.getSetting('id', 0),
                'DEAL_ID': this.getSetting('dealId', 0),
                'PERMISSION_ENTITY_TYPE': this.getSetting('permissionEntityType', ''),
                'SITE_ID': this.getSetting('siteId', ''),
                'sessId': this.getSetting('sessId', ''),
                'PRODUCTS': products,
            };
            BX.ajax({
                'url': this._serviceUrl,
                'method': "POST",
                'dataType': "json",
                'data': postData,
                onsuccess: BX.delegate(this._onActionRequestSuccess, this),
                onfailure: BX.delegate(this._onActionRequestFailure, this)
            });
        },
        handlerAddInfoSave: function (){
            let data = [];
            data = this.getAddInfoFormData();
            let postData = {
                'MODE': 'ADD_INFO_SAVE',
                'OWNER_TYPE': this.getSetting('ownerType', ''),
                'OWNER_ID': this.getSetting('id', 0),
                'DEAL_ID': this.getSetting('dealId', 0),
                'PERMISSION_ENTITY_TYPE': this.getSetting('permissionEntityType', ''),
                'SITE_ID': this.getSetting('siteId', ''),
                'sessId': this.getSetting('sessId', ''),
                'DATA' : data
            };
            BX.ajax({
                'url': this._serviceUrl,
                'method': "POST",
                'dataType': "json",
                'data': postData,
                onsuccess: BX.delegate(this._onActionRequestSuccess, this),
                onfailure: BX.delegate(this._onActionRequestFailure, this)
            });
        },
        handlerSend:function(){
            let products = [];
            products = this.getProducts();
            let data = [];
            data = this.getAddInfoFormData();
            let postData = {
                'MODE': 'SEND',
                'OWNER_TYPE': this.getSetting('ownerType', ''),
                'OWNER_ID': this.getSetting('id', 0),
                'DEAL_ID': this.getSetting('dealId', 0),
                'PERMISSION_ENTITY_TYPE': this.getSetting('permissionEntityType', ''),
                'SITE_ID': this.getSetting('siteId', ''),
                'sessId': this.getSetting('sessId', ''),
                'DATA' : data,
                'PRODUCTS': products,
            };
            BX.ajax({
                'url': this._serviceUrl,
                'method': "POST",
                'dataType': "json",
                'data': postData,
                onsuccess: BX.delegate(this._onActionRequestSuccess, this),
                onfailure: BX.delegate(this._onActionRequestFailure, this)
            });
        },
        handlerSearchProducts: function(data){
            let element = BX(data.target.id);
            let elementId = $(element).attr('id');
            let productId = this.getProductIdByElementId(elementId);
            let word = $(element).val();
            this.hideProductPopupOthers(productId);
            if(word.length >=3)
            {
                let postData = {
                    'MODE': 'SEARCH_PRODUCT',
                    'OWNER_TYPE': this.getSetting('ownerType', ''),
                    'OWNER_ID': this.getSetting('id', 0),
                    'DEAL_ID': this.getSetting('dealId', 0),
                    'PERMISSION_ENTITY_TYPE': this.getSetting('permissionEntityType', ''),
                    'SITE_ID': this.getSetting('siteId', ''),
                    'sessId': this.getSetting('sessId', ''),
                    'WORD': word,
                    'ELEMENT_ID': elementId,
                };
                BX.ajax({
                    'url': this._serviceUrl,
                    'method': "POST",
                    'dataType': "json",
                    'data': postData,
                    onsuccess: BX.delegate(this.searchProductsPopup, this),
                    onfailure: BX.delegate(this._onActionRequestFailure, this)
                });
            }
            else{
                this.hideProductPopup(productId);
            }
        },
        // results handlers
        _onActionRequestSuccess:function(data) {
            console.log('_onActionRequestSuccess:data');
            console.log(data);
            if (data.ERROR) {
                this.error(data);
                return;
            }
            let products = {};
            let tender = {};
            switch (data['POST']['MODE'])
            {
                case 'ADD':
                    products = data['RESULT']['PRODUCTS'];
                    this.setProducts(products);
                    break;
                case 'SAVE':
                    products = data['RESULT']['PRODUCTS'];
                    this.setProducts(products);
                    break;
                case 'ADD_INFO_SAVE':
                    tender = data['RESULT']['TENDER'];
                    this.setTender(tender);
                    break;
                case 'SEND':
                    break;
                default:
                    data.ERROR = BX.message("JS_MODE_NOT_FOUND") + ' ' + data['POST']['MODE'];
                    this.error(data);
                    break;
            }
            let gridId = this.getSetting('gridId', '');
            this.reloadTable(gridId);
            console.log('_onActionRequestSuccess:this');
            console.log(this);
        },
        _onActionRequestFailure:function(data) {
            console.log('dataFailure');
            console.log(data);
        },
        error: function (data){
            alert(BX.message("JS_W4A_ACTION_ERROR_TITLE") + data.ERROR);
        },
        reloadTable:function(gridId){
            let reloadParams = { apply_filter: 'N', clear_nav: 'Y' };
            let gridObject = BX.Main.gridManager.getById(gridId);
            if (gridObject.hasOwnProperty('instance')){
                gridObject.instance.reloadTable('POST', reloadParams);
            }
        }
    };
    BX.W4aCrmTendercalc.create = function (id, config)
    {
        let self = new BX.W4aCrmTendercalc();
        self.initialize(id, config);
        return self;
    };

}


