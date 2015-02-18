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

include_once(__DIR__.'/../../../app/init.php');
include_once(__DIR__.'/../../../src/shop/functions.admin.shop.php');
include_once(__DIR__.'/../../../src/shop/functions.shoppingcart.php');

$P = array(
    'base' => array(
        'cb_pagetype' => 'content',
        'cb_pageconfig' => '',
        'cb_subnav' => 'admin',
        'cb_customcontenttemplate' => 'shop/shopadmin',
    ),
    'lang' => array(
        'cl_lang' => $sLang,
    ),
);

$sH = '';

if (isset($_POST["change"])) {
    $aData = array(
        'o_lastedit_timestamp' => time(),
        'o_remarks_internal' => $_POST["remarks_internal"],
        'o_transaction_no' => $_POST["transaction_no"],
        'o_paymentcompleted' => $_POST["order_paymentcompleted"],
        'o_ordercompleted' => $_POST["order_completed"],
        'o_lastedit_user' => ((isset($_SERVER["REMOTE_USER"])) ? $_SERVER["REMOTE_USER"] : ''),
        'o_shipping_service' => $_POST["order_shipping_service"],
        'o_shipping_trackingno' => $_POST["order_shipping_trackingno"],
        'o_id' => $_POST["id"],
    );

    $sQ = \HaaseIT\DBTools::buildPSUpdateQuery($aData, DB_ORDERTABLE, 'o_id');
    //echo debug($sQ, true);
    $hResult = $DB->prepare($sQ);
    foreach ($aData as $sKey => $sValue) $hResult->bindValue(':'.$sKey, $sValue);
    $hResult->execute();
    header('Location: '.$_SERVER["PHP_SELF"].'?action=edit&id='.$_POST["id"]);
    die();
}

$aPData = [
    'searchform_type' => \HaaseIT\Tools::getFormfield('type', 'openinwork'),
    'searchform_fromday' => \HaaseIT\Tools::getFormfield('fromday', '01'),
    'searchform_frommonth' => \HaaseIT\Tools::getFormfield('frommonth', '01'),
    'searchform_fromyear' => \HaaseIT\Tools::getFormfield('fromyear', '2014'),
    'searchform_today' => \HaaseIT\Tools::getFormfield('today', date("d")),
    'searchform_tomonth' => \HaaseIT\Tools::getFormfield('tomonth', date("m")),
    'searchform_toyear' => \HaaseIT\Tools::getFormfield('toyear', date("Y")),
];

$aShopadmin = handleShopAdmin($CSA, $twig, $DB, $C, $sLang);

$P["base"]["cb_customdata"] = array_merge($aPData, $aShopadmin);

/* Druckansicht für Acrylx
$sH .= '<div>
	<a href="#" onclick="return hs.htmlExpand(this, {
			width: 736,
			headingText: \'Acrylx Bestellung\', wrapperClassName: \'titlebar\' })">Druckansicht</a>
	<div class="highslide-maincontent">
		<a class="control" onclick="return hs.getExpander(this).printHtml()" href="#">Drucken</a>
		'.$sShopadmin.'
	</div>
</div>';
*/

$sH .= $aShopadmin["html"];

$P["lang"]["cl_html"] = $sH;

$aP = generatePage($C, $P, $sLang, $DB, $oItem);

echo $twig->render($C["template_base"], $aP);
