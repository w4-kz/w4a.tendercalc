BX.namespace("BX.Crm");
if (typeof(BX.W4aCrmTendercalc) === "undefined") {
    BX.W4aCrmTendercalc = function () {
        this._id = "";
        this._settings = {};
        this._serviceUrl = "";
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
            let addInfoSave = BX('w4a-btn-add-info-save');
            if (addInfoSave) {
                BX.bind(
                    addInfoSave,
                    "click",
                    BX.delegate(this.handlerAddInfoSave, this)
                );
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
                onsuccess: BX.delegate(this._onOrderActionRequestSuccess, this),
                onfailure: BX.delegate(this._onOrderActionRequestFailure, this)
            });
        },
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
            BX('w4a-btn-add-info').innerHTML = BX.message("JS_W4A_ACTION_CLOSE_TEXT");;
        },
        hideAddInfoForm: function(){
            BX.hide(BX('w4a-calc-options-container'));
            BX('w4a-btn-add-info').innerHTML = BX.message("JS_W4A_ACTION_OPEN_TEXT");
        },
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
                onsuccess: BX.delegate(this._onOrderActionRequestSuccess, this),
                onfailure: BX.delegate(this._onOrderActionRequestFailure, this)
            });
        },

        handlerEdit:function(){
            this.handlerSave();
            let gridId = this.getSetting('gridId', '');
            this.reloadTable(gridId);
        },
        handlerSave:function(){
             let gridTable = BX(this.getSetting('gridId', '') + '_table');
            let obj = $(gridTable).find('tr.main-grid-row');
            // собираем массив товаров
            let products = {};
            let product = {};
            let productId = 0;
            let key = '';
            $(obj).each(function(i,e)
            {
                if(!$(e).hasClass('main-grid-not-count'))
                {
                    productId = $(e).data("id");
                    let o = $(e).find('input.ui-ctl-element');
                    product = {};
                    $(o).each(function(index,el)
                    {
                        key = $(el).data("name");
                        product[key] = $(el).val();
                    });
                    products[productId] = product;
                }
            });
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
                onsuccess: BX.delegate(this._onOrderActionRequestSuccess, this),
                onfailure: BX.delegate(this._onOrderActionRequestFailure, this)
            });
        },

        _onOrderActionRequestSuccess:function(data) {
            console.log('_onOrderActionRequestSuccess:data');
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
                default:
                    data.ERROR = BX.message("JS_MODE_NOT_FOUND");
                    this.error(data);
                    break;
            }
            let gridId = this.getSetting('gridId', '');
            this.reloadTable(gridId);
            console.log('_onOrderActionRequestSuccess:this');
            console.log(this);
        },
        _onOrderActionRequestFailure:function(data) {
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


