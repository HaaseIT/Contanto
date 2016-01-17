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

class Itemgroupadmin extends Base
{
    public function __construct($C, $DB, $sLang, $twig)
    {
        parent::__construct($C, $DB, $sLang);
        $this->P->cb_customcontenttemplate = 'shop/itemgroupadmin';

        $sH = '';
        if (isset($_REQUEST["action"]) && $_REQUEST["action"] == 'insert_lang') {
            $sQ = "SELECT ".DB_ITEMGROUPTABLE_BASE_PKEY." FROM ".DB_ITEMGROUPTABLE_BASE." WHERE ".DB_ITEMGROUPTABLE_BASE_PKEY." = :gid";
            $hResult = $DB->prepare($sQ);
            $hResult->bindValue(':gid', $_REQUEST["gid"]);
            $hResult->execute();
            $iNumRowsBasis = $hResult->rowCount();

            $sQ = "SELECT ".DB_ITEMGROUPTABLE_TEXT_PKEY." FROM ".DB_ITEMGROUPTABLE_TEXT;
            $sQ .= " WHERE ".DB_ITEMGROUPTABLE_TEXT_PARENTPKEY." = :gid";
            $sQ .= " AND ".DB_ITEMGROUPFIELD_LANGUAGE." = :lang";
            $hResult = $DB->prepare($sQ);
            $hResult->bindValue(':gid', $_REQUEST["gid"]);
            $hResult->bindValue(':lang', $sLang);
            $hResult->execute();
            //HaaseIT\Tools::debug($sQ);
            $iNumRowsLang = $hResult->rowCount();

            //HaaseIT\Tools::debug($iNumRowsBasis.' / '.$iNumRowsLang);

            if ($iNumRowsBasis == 1 && $iNumRowsLang == 0) {
                $iGID = filter_var($_REQUEST["gid"], FILTER_SANITIZE_NUMBER_INT);
                $aData = [
                    DB_ITEMGROUPTABLE_TEXT_PARENTPKEY => $iGID,
                    DB_ITEMGROUPFIELD_LANGUAGE => $sLang,
                ];
                //HaaseIT\Tools::debug($aData);
                $sQ = \HaaseIT\DBTools::buildPSInsertQuery($aData, DB_ITEMGROUPTABLE_TEXT);
                //HaaseIT\Tools::debug($sQ);
                $hResult = $DB->prepare($sQ);
                foreach ($aData as $sKey => $sValue) $hResult->bindValue(':'.$sKey, $sValue);
                $hResult->execute();
                header('Location: /_admin/itemgroupadmin.html?gid='.$iGID.'&action=editgroup');
                die();
            }
            //HaaseIT\Tools::debug($aItemdata);
        }

        if (isset($_REQUEST["action"]) && $_REQUEST["action"] == 'editgroup') {
            if (isset($_REQUEST["do"]) && $_REQUEST["do"] == 'true') {
                $purifier_config = \HTMLPurifier_Config::createDefault();
                $purifier_config->set('Core.Encoding', 'UTF-8');
                $purifier_config->set('Cache.SerializerPath', PATH_PURIFIERCACHE);
                $purifier_config->set('HTML.Doctype', $C['purifier_doctype']);
                if (isset($C['itemgrouptext_unsafe_html_whitelist']) && trim($C['itemgrouptext_unsafe_html_whitelist']) != '') {
                    $purifier_config->set('HTML.Allowed', $C['itemgrouptext_unsafe_html_whitelist']);
                }
                if (isset($C['itemgrouptext_loose_filtering']) && $C['itemgrouptext_loose_filtering']) {
                    $purifier_config->set('HTML.Trusted', true);
                }
                $purifier = new \HTMLPurifier($purifier_config);

                $this->P->cb_customdata["updatestatus"] = $this->admin_updateGroup($purifier);
            }

            $iGID = filter_var($_REQUEST["gid"], FILTER_SANITIZE_NUMBER_INT);
            $aGroup = $this->admin_getItemgroups($iGID);
            if (isset($_REQUEST["added"])) {
                $this->P->cb_customdata["groupjustadded"] = true;
            }
            $this->P->cb_customdata["showform"] = 'edit';
            $this->P->cb_customdata["group"] = $this->admin_prepareGroup('edit', $aGroup[0]);
        } elseif (isset($_REQUEST["action"]) && $_REQUEST["action"] == 'addgroup') {
            $aErr = [];
            if (isset($_REQUEST["do"]) && $_REQUEST["do"] == 'true') {
                $sName = filter_var($_REQUEST["name"], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
                $sGNo = filter_var($_REQUEST["no"], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
                $sImg = filter_var($_REQUEST["img"], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);

                if (strlen($sName) < 3) $aErr["nametooshort"] = true;
                if (strlen($sGNo) < 3) $aErr["grouptooshort"] = true;
                if (count($aErr) == 0) {
                    $sQ = "SELECT ".DB_ITEMGROUPFIELD_NUMBER." FROM ".DB_ITEMGROUPTABLE_BASE;
                    $sQ .= " WHERE ".DB_ITEMGROUPFIELD_NUMBER." = :no";
                    $hResult = $DB->prepare($sQ);
                    $hResult->bindValue(':no', $sGNo);
                    $hResult->execute();
                    if ($hResult->rowCount() > 0) $aErr["duplicateno"] = true;
                }
                if (count($aErr) == 0) {
                    $aData = [
                        DB_ITEMGROUPFIELD_NAME => $sName,
                        DB_ITEMGROUPFIELD_NUMBER => $sGNo,
                        DB_ITEMGROUPFIELD_IMG => $sImg,
                    ];
                    $sQ = \HaaseIT\DBTools::buildPSInsertQuery($aData, DB_ITEMGROUPTABLE_BASE);
                    $hResult = $DB->prepare($sQ);
                    foreach ($aData as $sKey => $sValue) $hResult->bindValue(':'.$sKey, $sValue);
                    $hResult->execute();
                    $iLastInsertID = $DB->lastInsertId();
                    header('Location: /_admin/itemgroupadmin.html?action=editgroup&added&gid='.$iLastInsertID);
                    die();
                } else {
                    $this->P->cb_customdata["err"] = $aErr;
                    $this->P->cb_customdata["showform"] = 'add';
                    $this->P->cb_customdata["group"] = $this->admin_prepareGroup('add');
                }
            } else {
                $this->P->cb_customdata["showform"] = 'add';
                $this->P->cb_customdata["group"] = $this->admin_prepareGroup('add');
            }
        } else {
            if (!$sH .= $this->admin_showItemgroups($this->admin_getItemgroups(''), $twig)) {
                $this->P->cb_customdata["err"]["nogroupsavaliable"] = true;
            }
        }
        $this->P->oPayload->cl_html = $sH;
    }

    private function admin_updateGroup($purifier)
    {
        $sQ = "SELECT * FROM " . DB_ITEMGROUPTABLE_BASE . " WHERE " . DB_ITEMGROUPTABLE_BASE_PKEY . " != :id AND ";
        $sQ .= DB_ITEMGROUPFIELD_NUMBER . " = :gno";
        $hResult = $this->DB->prepare($sQ);
        $iGID = filter_var($_REQUEST["gid"], FILTER_SANITIZE_NUMBER_INT);
        $sGNo = filter_var($_REQUEST["no"], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
        $hResult->bindValue(':id', $iGID);
        $hResult->bindValue(':gno', $sGNo);
        $hResult->execute();
        $iNumRows = $hResult->rowCount();

        if ($iNumRows > 0) return 'duplicateno';

        $aData = [
            DB_ITEMGROUPFIELD_NAME => filter_var($_REQUEST["name"], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
            DB_ITEMGROUPFIELD_NUMBER => $sGNo,
            DB_ITEMGROUPFIELD_IMG => filter_var($_REQUEST["img"], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW),
            DB_ITEMGROUPTABLE_BASE_PKEY => $iGID,
        ];

        $sQ = \HaaseIT\DBTools::buildPSUpdateQuery($aData, DB_ITEMGROUPTABLE_BASE, DB_ITEMGROUPTABLE_BASE_PKEY);
        $hResult = $this->DB->prepare($sQ);
        foreach ($aData as $sKey => $sValue) {
            $hResult->bindValue(':' . $sKey, $sValue);
        }
        $hResult->execute();

        $sQ = "SELECT " . DB_ITEMGROUPTABLE_TEXT_PKEY . " FROM " . DB_ITEMGROUPTABLE_TEXT;
        $sQ .= " WHERE " . DB_ITEMGROUPTABLE_TEXT_PARENTPKEY . " = :gid";
        $sQ .= " AND " . DB_ITEMGROUPFIELD_LANGUAGE . " = :lang";
        $hResult = $this->DB->prepare($sQ);
        $hResult->bindValue(':gid', $iGID);
        $hResult->bindValue(':lang', $this->sLang, \PDO::PARAM_STR);
        $hResult->execute();

        $iNumRows = $hResult->rowCount();

        if ($iNumRows == 1) {
            $aRow = $hResult->fetch();
            $aData = [
                DB_ITEMGROUPFIELD_SHORTTEXT => $purifier->purify($_REQUEST["shorttext"]),
                DB_ITEMGROUPFIELD_DETAILS => $purifier->purify($_REQUEST["details"]),
                DB_ITEMGROUPTABLE_TEXT_PKEY => $aRow[DB_ITEMGROUPTABLE_TEXT_PKEY],
            ];
            $sQ = \HaaseIT\DBTools::buildPSUpdateQuery($aData, DB_ITEMGROUPTABLE_TEXT, DB_ITEMGROUPTABLE_TEXT_PKEY);
            $hResult = $this->DB->prepare($sQ);
            foreach ($aData as $sKey => $sValue) $hResult->bindValue(':' . $sKey, $sValue);
            $hResult->execute();
        }

        return 'success';
    }

    private function admin_prepareGroup($sPurpose = 'none', $aData = [])
    {
        $aGData = [
            'formaction' => \HaaseIT\Tools::makeLinkHRefWithAddedGetVars('/_admin/itemgroupadmin.html'),
            'id' => isset($aData[DB_ITEMGROUPTABLE_BASE_PKEY]) ? $aData[DB_ITEMGROUPTABLE_BASE_PKEY] : '',
            'name' => isset($aData[DB_ITEMGROUPFIELD_NAME]) ? $aData[DB_ITEMGROUPFIELD_NAME] : '',
            'no' => isset($aData[DB_ITEMGROUPFIELD_NUMBER]) ? $aData[DB_ITEMGROUPFIELD_NUMBER] : '',
            'img' => isset($aData[DB_ITEMGROUPFIELD_IMG]) ? $aData[DB_ITEMGROUPFIELD_IMG] : '',
        ];

        if ($sPurpose == 'edit') {
            if ($aData[DB_ITEMGROUPTABLE_TEXT_PKEY] != '') {
                $aGData["lang"] = [
                    'shorttext' => isset($aData[DB_ITEMGROUPFIELD_SHORTTEXT]) ? $aData[DB_ITEMGROUPFIELD_SHORTTEXT] : '',
                    'details' => isset($aData[DB_ITEMGROUPFIELD_DETAILS]) ? $aData[DB_ITEMGROUPFIELD_DETAILS] : '',
                ];
            }
        }

        return $aGData;
    }

    private function admin_getItemgroups($iGID = '')
    {
        $sQ = "SELECT * FROM " . DB_ITEMGROUPTABLE_BASE;
        $sQ .= " LEFT OUTER JOIN " . DB_ITEMGROUPTABLE_TEXT . " ON ";
        $sQ .= DB_ITEMGROUPTABLE_BASE . "." . DB_ITEMGROUPTABLE_BASE_PKEY . " = " . DB_ITEMGROUPTABLE_TEXT . "." . DB_ITEMGROUPTABLE_TEXT_PARENTPKEY;
        $sQ .= " AND " . DB_ITEMGROUPTABLE_TEXT . "." . DB_ITEMGROUPFIELD_LANGUAGE . " = :lang";
        if ($iGID != '') $sQ .= " WHERE " . DB_ITEMGROUPTABLE_BASE_PKEY . " = :gid";
        $sQ .= " ORDER BY " . DB_ITEMGROUPFIELD_NUMBER;
        $hResult = $this->DB->prepare($sQ);
        $hResult->bindValue(':lang', $this->sLang);
        if ($iGID != '') $hResult->bindValue(':gid', $iGID);
        $hResult->execute();

        $aGroups = $hResult->fetchAll();

        return $aGroups;
    }

    private function admin_showItemgroups($aGroups, $twig)
    {
        $aList = [
            ['title' => \HaaseIT\HCSF\HardcodedText::get('itemgroupadmin_list_no'), 'key' => 'gno', 'width' => 80, 'linked' => false, 'style-data' => 'padding: 5px 0;'],
            ['title' => \HaaseIT\HCSF\HardcodedText::get('itemgroupadmin_list_name'), 'key' => 'gname', 'width' => 350, 'linked' => false, 'style-data' => 'padding: 5px 0;'],
            ['title' => \HaaseIT\HCSF\HardcodedText::get('itemgroupadmin_list_edit'), 'key' => 'gid', 'width' => 30, 'linked' => true, 'ltarget' => '/_admin/itemgroupadmin.html', 'lkeyname' => 'gid', 'lgetvars' => ['action' => 'editgroup'], 'style-data' => 'padding: 5px 0;'],
        ];
        if (count($aGroups) > 0) {
            foreach ($aGroups as $aValue) {
                $aData[] = [
                    'gid' => $aValue[DB_ITEMGROUPTABLE_BASE_PKEY],
                    'gno' => $aValue[DB_ITEMGROUPFIELD_NUMBER],
                    'gname' => $aValue[DB_ITEMGROUPFIELD_NAME],
                ];
            }
            return \HaaseIT\Tools::makeListTable($aList, $aData, $twig);
        } else {
            return false;
        }
    }

}