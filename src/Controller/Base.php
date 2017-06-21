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

namespace HaaseIT\HCSF\Controller;


use HaaseIT\HCSF\HelperConfig;
use Zend\ServiceManager\ServiceManager;

class Base
{
    /**
     * @var \HaaseIT\HCSF\Page
     */
    protected $P;

    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * @var bool
     */
    protected $requireAdminAuth = false;

    /**
     * @var bool
     */
    protected $requireAdminAuthAdminHome = false;

    /**
     * @var bool
     */
    protected $requireModuleCustomer = false;

    /**
     * @var bool
     */
    protected $requireModuleShop = false;

    /**
     * Base constructor.
     * @param ServiceManager $serviceManager
     */
    public function __construct(ServiceManager $serviceManager)
    {
        $this->serviceManager = $serviceManager;
    }

    /**
     * @return \HaaseIT\HCSF\Page
     * @throws \Exception
     */
    public function getPage()
    {
        if ($this->requireAdminAuth) {
            $this->requireAdminAuth();
        }
        if (
            $this->requireModuleCustomer
            && (
                empty(HelperConfig::$core['enable_module_customer'])
                || !HelperConfig::$core['enable_module_customer']
            )
        ) {
            throw new \Exception(404);
        }
        if (
            $this->requireModuleShop
            && (
                empty(HelperConfig::$core['enable_module_shop'])
                || !HelperConfig::$core['enable_module_shop'])
        ) {
            throw new \Exception(404);
        }
        $this->preparePage();
        return $this->P;
    }

    public function preparePage()
    {

    }

    /**
     * @return bool
     */
    private function requireAdminAuth() {
        if (
            $this->requireAdminAuthAdminHome
            && (
                empty(HelperConfig::$secrets['admin_users'])
                || !count(HelperConfig::$secrets['admin_users'])
            )
        ) {
            return true;
        } elseif (count(HelperConfig::$secrets['admin_users'])) {

            if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) { // fix for php cgi mode
                list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(':' , base64_decode(substr($_SERVER['REDIRECT_HTTP_AUTHORIZATION'], 6)));
            }

            if (isset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
                $user = $_SERVER['PHP_AUTH_USER'];
                $pass = $_SERVER['PHP_AUTH_PW'];

                $validated = !empty(
                    HelperConfig::$secrets['admin_users'][$user])
                    && password_verify($pass, HelperConfig::$secrets['admin_users'][$user]
                );
            } else {
                $validated = false;
            }

            if (!$validated) {
                header('WWW-Authenticate: Basic realm="' . HelperConfig::$secrets['admin_authrealm'] . '"');
                header('HTTP/1.0 401 Unauthorized');
                \HaaseIT\HCSF\Helper::terminateScript('Not authorized');
            }
        } else {
            header('WWW-Authenticate: Basic realm="' . HelperConfig::$secrets['admin_authrealm'] . '"');
            header('HTTP/1.0 401 Unauthorized');
            \HaaseIT\HCSF\Helper::terminateScript('Not authorized');
        }
    }
}