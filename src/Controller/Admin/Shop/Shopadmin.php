<?php

/*
    HCSF - A multilingual CMS and Shopsystem
    Copyright (C) 2014  Marcus Haase - mail@marcus.haase.name

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace HaaseIT\HCSF\Controller\Admin\Shop;

use HaaseIT\HCSF\HardcodedText;
use HaaseIT\HCSF\HelperConfig;
use HaaseIT\Toolbox\Tools;
use Zend\ServiceManager\ServiceManager;

/**
 * Class Shopadmin
 * @package HaaseIT\HCSF\Controller\Admin\Shop
 */
class Shopadmin extends Base
{
    /**
     * @var \PDO
     */
    private $db;

    /**
     * Shopadmin constructor.
     * @param ServiceManager $serviceManager
     */
    public function __construct(ServiceManager $serviceManager)
    {
        parent::__construct($serviceManager);
        $this->db = $serviceManager->get('db');
    }

    /**
     *
     */
    public function preparePage()
    {
        $this->P = new \HaaseIT\HCSF\CorePage($this->serviceManager);
        $this->P->cb_pagetype = 'content';
        $this->P->cb_subnav = 'admin';

        $this->P->cb_customcontenttemplate = 'shop/shopadmin';

        if (isset($_POST["change"])) {
            $iID = filter_var(trim(Tools::getFormfield("id")), FILTER_SANITIZE_NUMBER_INT);
            $aData = [
                'o_lastedit_timestamp' => time(),
                'o_remarks_internal' => filter_var(trim(Tools::getFormfield("remarks_internal")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                'o_transaction_no' => filter_var(trim(Tools::getFormfield("transaction_no")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                'o_paymentcompleted' => filter_var(trim(Tools::getFormfield("order_paymentcompleted")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                'o_ordercompleted' => filter_var(trim(Tools::getFormfield("order_completed")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                'o_lastedit_user' => ((isset($_SERVER["PHP_AUTH_USER"])) ? $_SERVER["PHP_AUTH_USER"] : ''),
                'o_shipping_service' => filter_var(trim(Tools::getFormfield("order_shipping_service")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                'o_shipping_trackingno' => filter_var(trim(Tools::getFormfield("order_shipping_trackingno")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                'o_id' => $iID,
            ];

            $sql = \HaaseIT\Toolbox\DBTools::buildPSUpdateQuery($aData, 'orders', 'o_id');
            $hResult = $this->db->prepare($sql);
            foreach ($aData as $sKey => $sValue) {
                $hResult->bindValue(':'.$sKey, $sValue);
            }
            $hResult->execute();
            header('Location: /_admin/shopadmin.html?action=edit&id='.$iID);
            die();
        }

        $aPData = [
            'searchform_type' => Tools::getFormfield('type', 'openinwork'),
            'searchform_fromday' => Tools::getFormfield('fromday', '01'),
            'searchform_frommonth' => Tools::getFormfield('frommonth', '01'),
            'searchform_fromyear' => Tools::getFormfield('fromyear', '2014'),
            'searchform_today' => Tools::getFormfield('today', date("d")),
            'searchform_tomonth' => Tools::getFormfield('tomonth', date("m")),
            'searchform_toyear' => Tools::getFormfield('toyear', date("Y")),
        ];

        $CSA = [
            'list_orders' => [
                ['title' => '', 'key' => 'o_id', 'width' => 30, 'linked' => false, 'callback' => 'shopadminMakeCheckbox'],
                ['title' => HardcodedText::get('shopadmin_list_orderid'), 'key' => 'o_id', 'width' => 30, 'linked' => false,],
                ['title' => HardcodedText::get('shopadmin_list_customer'), 'key' => 'o_cust', 'width' => 280, 'linked' => false,],
                ['title' => HardcodedText::get('shopadmin_list_sumnettoall'), 'key' => 'o_sumnettoall', 'width' => 75, 'linked' => false,],
                ['title' => HardcodedText::get('shopadmin_list_orderstatus'), 'key' => 'o_order_status', 'width' => 80, 'linked' => false,],
                ['title' => HardcodedText::get('shopadmin_list_ordertimenumber'), 'key' => 'o_ordertime_number', 'width' => 100, 'linked' => false,],
                ['title' => HardcodedText::get('shopadmin_list_hostpayment'), 'key' => 'o_order_host_payment', 'width' => 140, 'linked' => false,],
                [
                    'title' => HardcodedText::get('shopadmin_list_edit'),
                    'key' => 'o_id',
                    'width' => 45,
                    'linked' => true,
                    'ltarget' => '/_admin/shopadmin.html',
                    'lkeyname' => 'id',
                    'lgetvars' => [
                        'action' => 'edit',
                    ],
                ],
            ],
            'list_orderitems' => [
                ['title' => HardcodedText::get('shopadmin_list_itemno'), 'key' => 'oi_itemno', 'width' => 95, 'linked' => false,],
                ['title' => HardcodedText::get('shopadmin_list_itemname'), 'key' => 'oi_itemname', 'width' => 350, 'linked' => false,],
                ['title' => HardcodedText::get('shopadmin_list_itemamount'), 'key' => 'oi_amount', 'width' => 50, 'linked' => false, 'style-data' => 'text-align: center;',],
                ['title' => HardcodedText::get('shopadmin_list_itemnetto'), 'key' => 'oi_price_netto', 'width' => 70, 'linked' => false,],
                ['title' => HardcodedText::get('shopadmin_list_itemsumnetto'), 'key' => 'ges_netto', 'width' => 75, 'linked' => false,],
            ],
        ];

        $aShopadmin = $this->handleShopAdmin($CSA);

        $this->P->cb_customdata = array_merge($aPData, $aShopadmin);
    }

    /**
     * @param $CSA
     * @return array
     */
    private function handleShopAdmin($CSA)
    {
        $aSData = [];
        $aData = [];
        if (!isset($_GET["action"])) {
            $bIgnoreStorno = false;
            $sql = 'SELECT * FROM orders WHERE ';

            if (isset($_REQUEST["type"])) {
                switch ($_REQUEST["type"]) {
                    case 'closed':
                        $sql .= "o_ordercompleted = 'y' ";
                        break;
                    case 'open':
                        $sql .= "o_ordercompleted = 'n' ";
                        break;
                    case 'inwork':
                        $sql .= "o_ordercompleted = 'i' ";
                        break;
                    case 'storno':
                        $sql .= "o_ordercompleted = 's' ";
                        break;
                    case 'deleted':
                        $sql .= "o_ordercompleted = 'd' ";
                        break;
                    case 'all':
                        $sql .= "o_ordercompleted != 'd' ";
                        $bIgnoreStorno = true;
                        break;
                    case 'openinwork':
                    default:
                        $sql .= "(o_ordercompleted = 'n' OR o_ordercompleted = 'i') ";
                }
            } else {
                $sql .= "(o_ordercompleted = 'n' OR o_ordercompleted = 'i') ";
            }

            $bFromTo = false;
            $sFrom = null;
            $sTo = null;
            if (isset($_REQUEST["type"]) && ($_REQUEST["type"] === 'deleted' || $_REQUEST["type"] === 'all' || $_REQUEST["type"] === 'closed')) {
                $sql .= "AND ";
                $sFrom = \filter_var($_REQUEST["fromyear"], FILTER_SANITIZE_NUMBER_INT).'-'.Tools::dateAddLeadingZero(\filter_var($_REQUEST["frommonth"], FILTER_SANITIZE_NUMBER_INT));
                $sFrom .= '-'.Tools::dateAddLeadingZero(\filter_var($_REQUEST["fromday"], FILTER_SANITIZE_NUMBER_INT));
                $sTo = \filter_var($_REQUEST["toyear"], FILTER_SANITIZE_NUMBER_INT).'-'.Tools::dateAddLeadingZero(\filter_var($_REQUEST["tomonth"], FILTER_SANITIZE_NUMBER_INT));
                $sTo .= '-'.Tools::dateAddLeadingZero(\filter_var($_REQUEST["today"], FILTER_SANITIZE_NUMBER_INT));
                $sql .= "o_orderdate >= :from ";
                $sql .= "AND o_orderdate <= :to ";
                $bFromTo = true;
            }
            $sql .= "ORDER BY o_ordertimestamp DESC";

            $hResult = $this->db->prepare($sql);
            if ($bFromTo) {
                $hResult->bindValue(':from', $sFrom);
                $hResult->bindValue(':to', $sTo);
            }
            $hResult->execute();

            if ($hResult->rowCount() != 0) {
                $i = 0;
                $j = 0;
                $k = 0;
                $fGesamtnetto = 0.0;
                while ($aRow = $hResult->fetch()) {
                    switch ($aRow["o_ordercompleted"]) {
                        case 'y':
                            $sStatus = '<span style="color: green; font-weight: bold;">'.HardcodedText::get('shopadmin_orderstatus_completed').'</span>';
                            break;
                        case 'n':
                            $sStatus = '<span style="color: orange; font-weight: bold;">'.HardcodedText::get('shopadmin_orderstatus_open').'</span>';
                            break;
                        case 'i':
                            $sStatus = '<span style="color: orange;">'.HardcodedText::get('shopadmin_orderstatus_inwork').'</span>';
                            break;
                        case 's':
                            $sStatus = '<span style="color: red; font-weight: bold;">'.HardcodedText::get('shopadmin_orderstatus_canceled').'</span>';
                            break;
                        case 'd':
                            $sStatus = HardcodedText::get('shopadmin_orderstatus_deleted');
                            break;
                        default:
                            $sStatus = '';
                    }

                    if ($aRow["o_paymentcompleted"] === 'y') {
                        $sZahlungsmethode = '<span style="color: green;">';
                    } else {
                        $sZahlungsmethode = '<span style="color: red;">';
                    }
                    $mZahlungsmethode = $this->serviceManager->get('textcats')->T("order_paymentmethod_".$aRow["o_paymentmethod"], true);
                    if ($mZahlungsmethode ) {
                        $sZahlungsmethode .= $mZahlungsmethode;
                    } else {
                        $sZahlungsmethode .= ucwords($aRow["o_paymentmethod"]);
                    }
                    $sZahlungsmethode .= '</span>';

                    if (trim($aRow["o_corpname"]) == '') {
                        $sName = $aRow["o_name"];
                    } else {
                        $sName = $aRow["o_corpname"];
                    }

                    $aData[] = [
                        'o_id' => $aRow["o_id"],
                        'o_account_no' => $aRow["o_custno"],
                        'o_email' => $aRow["o_email"],
                        'o_cust' => $sName.'<br>'.$aRow["o_zip"].' '.$aRow["o_town"],
                        'o_authed' => $aRow["o_authed"],
                        'o_sumnettoall' => number_format(
                            $aRow["o_sumnettoall"],
                                HelperConfig::$core['numberformat_decimals'],
                                HelperConfig::$core['numberformat_decimal_point'],
                                HelperConfig::$core['numberformat_thousands_seperator']
                            )
                            .' '.HelperConfig::$shop["waehrungssymbol"]
                            .(
                                ($aRow["o_mindermenge"] != 0 && $aRow["o_mindermenge"] != '')
                                    ? '<br>+'.number_format(
                                        $aRow["o_mindermenge"],
                                        HelperConfig::$core['numberformat_decimals'],
                                        HelperConfig::$core['numberformat_decimal_point'],
                                        HelperConfig::$core['numberformat_thousands_seperator']
                                    ).' '.HelperConfig::$shop["waehrungssymbol"] : ''),
                        'o_order_status' => $sStatus.((trim($aRow["o_lastedit_user"]) != '') ? '<br>'.$aRow["o_lastedit_user"] : ''),
                        'o_ordertime_number' => date(
                                HelperConfig::$core['locale_format_date_time'],
                                $aRow["o_ordertimestamp"]
                            )
                            .((trim($aRow["o_transaction_no"]) != '') ? '<br>'.$aRow["o_transaction_no"] : ''),
                        'o_order_host_payment' => $sZahlungsmethode.'<br>'.$aRow["o_srv_hostname"],
                    ];
                    if (!($aRow["o_ordercompleted"] == 's' && $bIgnoreStorno)) {
                        $fGesamtnetto += $aRow["o_sumnettoall"];
                        $j ++;
                    } else {
                        $k++;
                    }
                    $i++;
                }
                $aSData['listtable_orders'] = Tools::makeListtable($CSA["list_orders"], $aData, $this->serviceManager->get('twig'));
                $aSData['listtable_i'] = $i;
                $aSData['listtable_j'] = $j;
                $aSData['listtable_k'] = $k;
                $aSData['listtable_gesamtnetto'] = $fGesamtnetto;
            } else {
                $aSData['nomatchingordersfound'] = true;
            }
        } elseif (isset($_GET["action"]) && $_GET["action"] === 'edit') {
            $iId = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
            $sql = 'SELECT * FROM orders WHERE o_id = :id';

            /** @var \PDOStatement $hResult */
            $hResult = $this->db->prepare($sql);
            $hResult->bindValue(':id', $iId);
            $hResult->execute();
            if ($hResult->rowCount() == 1) {
                $aSData["orderdata"] = $hResult->fetch();
                $sql = 'SELECT * FROM orders_items WHERE oi_o_id = :id';
                $hResult = $this->db->prepare($sql);
                $hResult->bindValue(':id', $iId);
                $hResult->execute();
                $aItems = $hResult->fetchAll();

                $aUserdata = [
                    'cust_no' => $aSData["orderdata"]["o_custno"],
                    'cust_email' => $aSData["orderdata"]["o_email"],
                    'cust_corp' => $aSData["orderdata"]["o_corpname"],
                    'cust_name' => $aSData["orderdata"]["o_name"],
                    'cust_street' => $aSData["orderdata"]["o_street"],
                    'cust_zip' => $aSData["orderdata"]["o_zip"],
                    'cust_town' => $aSData["orderdata"]["o_town"],
                    'cust_phone' => $aSData["orderdata"]["o_phone"],
                    'cust_cellphone' => $aSData["orderdata"]["o_cellphone"],
                    'cust_fax' => $aSData["orderdata"]["o_fax"],
                    'cust_country' => $aSData["orderdata"]["o_country"],
                    'cust_group' => $aSData["orderdata"]["o_group"],
                ];
                $aSData["customerform"] = \HaaseIT\HCSF\Customer\Helper::buildCustomerForm(
                    HelperConfig::$lang,
                    'shopadmin',
                    '',
                    $aUserdata
                );

                $aSData["orderdata"]["options_shippingservices"] = [''];
                foreach (HelperConfig::$shop["shipping_services"] as $sValue) {
                    $aSData["orderdata"]["options_shippingservices"][] = $sValue;
                }

                $aItemsCarttable = [];
                foreach ($aItems as $aValue) {
                    $aPrice = [
                        'netto_list' => $aValue["oi_price_netto_list"],
                        'netto_sale' => $aValue["oi_price_netto_sale"],
                        'netto_rebated' => $aValue["oi_price_netto_rebated"],
                        'netto_use' => $aValue["oi_price_netto_use"],
                        'brutto_use' => $aValue["oi_price_brutto_use"],
                    ];

                    $aItemsCarttable[$aValue["oi_cartkey"]] = [
                        'amount' => $aValue["oi_amount"],
                        'price' => $aPrice,
                        'vat' => $aValue["oi_vat"],
                        'rg' => $aValue["oi_rg"],
                        'rg_rebate' => $aValue["oi_rg_rebate"],
                        'name' => $aValue["oi_itemname"],
                        'img' => $aValue["oi_img"],
                    ];
                }

                $aSData = array_merge(
                    \HaaseIT\HCSF\Shop\Helper::buildShoppingCartTable(
                        $aItemsCarttable,
                        true,
                        $aSData["orderdata"]["o_group"],
                        '',
                        $aSData["orderdata"]["o_vatfull"],
                        $aSData["orderdata"]["o_vatreduced"]
                    ),
                    $aSData);
            } else {
                $aSData['ordernotfound'] = true;
            }
        }

        return $aSData;
    }
}
