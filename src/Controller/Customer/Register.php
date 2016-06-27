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

namespace HaaseIT\HCSF\Controller\Customer;

class Register extends Base
{
    public function __construct($C, $DB, $sLang, $twig, $oItem)
    {
        parent::__construct($C, $DB, $sLang);

        if (\HaaseIT\HCSF\Customer\Helper::getUserData()) {
            $this->P->oPayload->cl_html = \HaaseIT\Textcat::T("denied_default");
        } else {
            $this->P->cb_customcontenttemplate = 'customer/register';

            $aErr = array();
            if (isset($_POST["doRegister"]) && $_POST["doRegister"] == 'yes') {
                $aErr = \HaaseIT\HCSF\Customer\Helper::validateCustomerForm($C, $sLang, $aErr);
                if (count($aErr) == 0) {
                    $sQ = 'SELECT cust_email FROM customer WHERE cust_email = :email';

                    $sEmail = filter_var(trim(\HaaseIT\Tools::getFormfield("email")), FILTER_SANITIZE_EMAIL);
                    $hResult = $DB->prepare($sQ);
                    $hResult->bindValue(':email', $sEmail, \PDO::PARAM_STR);
                    $hResult->execute();
                    $iRows = $hResult->rowCount();

                    if ($iRows == 0) {
                        $sEmailVerificationcode = md5($_POST["email"] . time());
                        $aData = array(
                            'cust_email' => $sEmail,
                            'cust_corp' => filter_var(trim(\HaaseIT\Tools::getFormfield("corpname")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                            'cust_name' => filter_var(trim(\HaaseIT\Tools::getFormfield("name")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                            'cust_street' => filter_var(trim(\HaaseIT\Tools::getFormfield("street")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                            'cust_zip' => filter_var(trim(\HaaseIT\Tools::getFormfield("zip")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                            'cust_town' => filter_var(trim(\HaaseIT\Tools::getFormfield("town")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                            'cust_phone' => filter_var(trim(\HaaseIT\Tools::getFormfield("phone")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                            'cust_cellphone' => filter_var(trim(\HaaseIT\Tools::getFormfield("cellphone")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                            'cust_fax' => filter_var(trim(\HaaseIT\Tools::getFormfield("fax")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                            'cust_country' => filter_var(trim(\HaaseIT\Tools::getFormfield("country")), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
                            'cust_password' => password_hash($_POST["pwd"], PASSWORD_DEFAULT),
                            'cust_tosaccepted' => ((isset($_POST["tos"]) && $_POST["tos"] == 'y') ? 'y' : 'n'),
                            'cust_cancellationdisclaimeraccepted' => ((isset($_POST["cancellationdisclaimer"]) && $_POST["cancellationdisclaimer"] == 'y') ? 'y' : 'n'),
                            'cust_emailverified' => 'n',
                            'cust_emailverificationcode' => $sEmailVerificationcode,
                            'cust_active' => (($C["register_require_manual_activation"]) ? 'n' : 'y'),
                            'cust_registrationtimestamp' => time(),
                        );
                        $sQ = \HaaseIT\DBTools::buildPSInsertQuery($aData, 'customer');

                        $hResult = $DB->prepare($sQ);
                        foreach ($aData as $sKey => $sValue) {
                            $hResult->bindValue(':' . $sKey, $sValue, \PDO::PARAM_STR);
                        }
                        $hResult->execute();

                        \HaaseIT\HCSF\Customer\Helper::sendVerificationMail($sEmailVerificationcode, $sEmail, $C, $twig);
                        \HaaseIT\HCSF\Customer\Helper::sendVerificationMail($sEmailVerificationcode, $sEmail, $C, $twig, true);
                        $aPData["showsuccessmessage"] = true;
                    } else {
                        $aErr["emailalreadytaken"] = true;
                        $this->P->cb_customdata["customerform"] = \HaaseIT\HCSF\Customer\Helper::buildCustomerForm($C, $sLang, 'register', $aErr);
                    }
                } else {
                    $this->P->cb_customdata["customerform"] = \HaaseIT\HCSF\Customer\Helper::buildCustomerForm($C, $sLang, 'register', $aErr);
                }
            } else {
                $this->P->cb_customdata["customerform"] = \HaaseIT\HCSF\Customer\Helper::buildCustomerForm($C, $sLang, 'register');
            }
            if (isset($aPData) && count($aPData)) {
                $this->P->cb_customdata["register"] = $aPData;
            }
        }
    }
}