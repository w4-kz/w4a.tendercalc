CREATE TABLE IF NOT EXISTS `w4a_tendercalc_config` (
   `ID` INT(11) NOT NULL AUTO_INCREMENT,
   `NAME` VARCHAR(64) NOT NULL,
   `VALUE` VARCHAR(64) NOT NULL,
   `SORT` INT(64) NOT NULL,
   `DESCRIPTION` VARCHAR(512) NOT NULL,
   PRIMARY KEY(ID)
);
CREATE TABLE IF NOT EXISTS `w4a_tendercalc_config_uf` (
    `ID` INT(11) NOT NULL AUTO_INCREMENT,
    `UF_NAME` VARCHAR(64) NOT NULL,
    `UF_VALUE` VARCHAR(64) NOT NULL,
    `UF_OWNER_TYPE` VARCHAR(3) NOT NULL,
    `UF_DESCRIPTION` VARCHAR(512) NOT NULL,
    PRIMARY KEY(ID)
);
CREATE TABLE IF NOT EXISTS `w4a_tendercalc_tender` (
     `ID` int(11) NOT NULL AUTO_INCREMENT,
     `DEADLINE` datetime NOT NULL,
     `CLIENT_NAME` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
     `USER_NAME` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
     `DELIVERY_ADDRESS` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
     `DELIVERY_PERIOD` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
     `DELIVERY_CONDITIONS` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
     `DELIVERY_FREQUENCY` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
     `CONTRACT_WARRANTY_PAYMENT` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
     `CONTRACT_PAYMENT` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
     `TENDER_SITE_CONDITIONS` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
     `MY_COMPANY_NAME` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
     `PRICE_NMCK` float NOT NULL COLLATE utf8_unicode_ci NOT NULL,
     `ASSIGNED_BY_ID` int(11) NOT NULL,
     `DEAL_ID` int(11) NOT NULL,
     `DELIVERY_PRICE` float NOT NULL,
    `IS_COMPLETED` varchar(1) COLLATE 'utf8_unicode_ci' NOT NULL,
     PRIMARY KEY (`ID`)
);

CREATE TABLE IF NOT EXISTS `w4a_tendercalc_products` (
       `ID` int(11) NOT NULL AUTO_INCREMENT,
       `DEAL_ID` int(11) NOT NULL COMMENT 'ID СДЕЛКИ',
       `PRODUCT_ID` int(11) NOT NULL COMMENT 'id ТОВАРА ИЗ НОМЕКЛАТУРЫ',
       `PRODUCT_NAME_ORIG` varchar(512) COLLATE utf8_unicode_ci NOT NULL COMMENT 'НАИМЕНОВАНИЕ - ЭКВИВАЛЕНТ - выбирает из товаров',
       `PRODUCT_NAME_SPEC` varchar(512) COLLATE utf8_unicode_ci NOT NULL COMMENT 'НАИМЕНОВАНИЕ - ЗАЯВЛЕННОЕ В ТЕХНИЧЕСКОМ ЗАДАНИИ',
       `DELIVERY_DATE` datetime NOT NULL COMMENT 'СРОКИ ПОСТАВКИ',
       `DELIVERY_ADDRESS` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'БАЗИС ПОСТАВКИ(Адрес поставки)',
       `MEASURE_ID` int(11) NOT NULL COMMENT 'ЕДИНИЦЫ ИЗМЕРЕНИЯ (КГ/ЛИТРЫ)',
       `QUANTITY_REQUEST` float NOT NULL COMMENT 'ЗАПРАШИВАЕММЫЙ ОБЪЕМ / КОЛИЧЕСТВО',
       `PACKING` varchar(64) COLLATE utf8_unicode_ci NOT NULL COMMENT 'ФАСОВКА',
       `QUANTITY` float NOT NULL COMMENT 'КОЛИЧЕСТВО',
       `PRICE_PURCHASE` float NOT NULL COMMENT 'ЦЕНА ЗАКУПА/ДИСТРИБ  (РУБ с НДС за ЕД.)',
       `PROFIT_RATIO` float NOT NULL COMMENT 'Наценка расчетная',
       `PRICE_NMCK` float NOT NULL COMMENT 'НМЦК',
        `PRICE_IN_SPECIAL` float NOT NULL COMMENT 'цена закупа (спец.)',
        `PRICE_IN_DISTRIBUTOR` float NOT NULL COMMENT 'цена закупа (дистриб.)',
        `PROFIT_RATIO_SPECIAL` float NOT NULL COMMENT 'Наценка расчетная (спец.цена)',
        `PROFIT_RATIO_DISTRIBUTOR` float NOT NULL COMMENT 'Наценка расчетная (дистриб.)',
       PRIMARY KEY (`ID`)
);

CREATE TABLE IF NOT EXISTS  `w4a_tendercalc_users` (
       `ID` int(11) NOT NULL AUTO_INCREMENT,
       `DEAL_ID` int(11) NOT NULL,
       `PRODUCTION_USER_ID` int(11) NOT NULL,
       `SALES_USER_ID` int(11) NOT NULL,
       `LOGISTICS_USER_ID` int(11) NOT NULL,
       `ASSIGNED_BY_ID` int(11) NOT NULL,
       PRIMARY KEY (`ID`)
);

INSERT INTO `w4a_tendercalc_config` (`ID`, `NAME`, `VALUE`, `SORT`, `DESCRIPTION`) VALUES
(1,	'TENDERCALC_BP_ID',	'0',	10,	'ID БП: Тендер: Калькулятор'),
(2,	'CATALOG_IBLOCK_ID',	'30',	20,	'IBLOCK_ID Товаров'),
(3,	'TENDERCALC_PROFIT_RATIO_DEFAULT',	'1.2',	30,	'Наценка расчетная (по умолчанию)'),
(4,	'TENDERCALC_NDS',	'12',	40,	'ставка НДС (%)'),
(5,	'TENDERCALC_COMPLETED_BP_ID',	'0',	15,	'ID БП: Тендеры: Заполнены');