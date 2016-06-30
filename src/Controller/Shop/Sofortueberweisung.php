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

namespace HaaseIT\HCSF\Controller\Shop;

class Sofortueberweisung extends Base
{
    public function preparePage()
    {
        $this->P = new \HaaseIT\HCSF\CorePage($this->C, $this->sLang);
        $this->P->cb_pagetype = 'content';

        $iId = \filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
        $sQ = 'SELECT * FROM orders '
            . "WHERE o_id = :id AND o_paymentmethod = 'sofortueberweisung' AND o_paymentcompleted = 'n'";

        $hResult = $this->DB->prepare($sQ);
        $hResult->bindValue(':id', $iId, \PDO::PARAM_INT);

        $hResult->execute();

        if ($hResult->rowCount() == 1) {
            $aOrder = $hResult->fetch();
            $fGesamtbrutto = \HaaseIT\HCSF\Shop\Helper::calculateTotalFromDB($aOrder);

            $sPURL = 'https://www.sofortueberweisung.de/payment/start?user_id=' . $this->C["sofortueberweisung"]["user_id"];
            $sPURL .= '&amp;project_id=' . $this->C["sofortueberweisung"]["project_id"] . '&amp;amount=' . number_format($fGesamtbrutto,
                    2, '.', '');
            $sPURL .= '&amp;currency_id=' . $this->C["sofortueberweisung"]["currency_id"] . '&amp;reason_1=';
            $sPURL .= urlencode(\HaaseIT\Textcat::T("misc_paysofortueberweisung_ueberweisungsbetreff") . ' ') . $iId;
            if (isset($this->C["interactive_paymentmethods_redirect_immediately"]) && $this->C["interactive_paymentmethods_redirect_immediately"]) {
                header('Location: ' . $sPURL);
                die();
            }

            $this->P->oPayload->cl_html = \HaaseIT\Textcat::T("misc_paysofortueberweisung_greeting") . '<br><br>';
            $this->P->oPayload->cl_html .= '<a href="' . $sPURL . '">' . \HaaseIT\Textcat::T("misc_paysofortueberweisung") . '</a>';
        } else {
            $this->P->oPayload->cl_html = \HaaseIT\Textcat::T("misc_paysofortueberweisung_paymentnotavailable");
        }
    }
}