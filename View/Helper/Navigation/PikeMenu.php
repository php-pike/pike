<?php
/**
 * Copyright (C) 2011 by Pieter Vogelaar (platinadesigns.nl) and Kees Schepers (keesschepers.nl)
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @copyright  Copyright (C) 2011 by Pieter Vogelaar (platinadesigns.nl) and Kees Schepers (keesschepers.nl)
 * @license    MIT
 */

/**
 * With PiKe menu you can render a menu with an addidtional permission check. The normal navigation
 * menu helper has support for setting only one role with setRole. Some applications users are
 * allowed to have more roles and when menu is rendered it needs to check the user may see an entry
 * based on __multiple__ roles! Check it out!
 *
 * It work's very simple, in your frontcontroller plugin or bootstrap where you set up
 * your website navigation replace your current menu implementation by the following:
 *
 * <code>
 * $config = new Zend_Config_Xml($fileToANavigationXML, 'nav');
 * $navigation = new Zend_Navigation($config);
 *
 * $arrayOfroles = Zend_Auth::getInstance()->getIdentity()->getRoles();
 *
 * $layoutView->navigation()
 *      ->PikeMenu($navigation)
 *      ->setACL(Zend_Registry::get('acl')) //should be a object of type Zend_ACL
 *       ->setRoles($arrayOfRoles);
 * </code>
 *
 * And make sure u use PikeMenu either in your view (layout script proberly)
 *
 * <?= $this->navigation()->PikeMenu(); ?>
 *
 * Next make sure the Pike library is loaded with autoloaderNamespaces[] = "Pike"  in your
 * application.ini. And beyond to make sure the view navigation helper is found add something like
 * the following to it:
 * 
 * resources.view.helperPath.Pike_View_Helper_Navigation_ =
 *                      APPLICATION_PATH "/../library/Pike/View/Helper/Navigation"
 *
 * @category   PiKe
 * @copyright  Copyright (C) 2011 by Pieter Vogelaar (platinadesigns.nl) and Kees Schepers (keesschepers.nl)
 * @license    MIT
 */
class Pike_View_Helper_Navigation_PikeMenu extends Zend_View_Helper_Navigation_Menu
{

    protected $_roles = array();

    public function PikeMenu(Zend_Navigation_Container $container = null)
    {
        if (null !== $container) {
            $this->setContainer($container);
        }

        return $this;
    }

    protected function _acceptAcl(Zend_Navigation_Page $page)
    {

        if (!$acl = $this->getAcl()) {
            // no acl registered means don't use acl
            return true;
        }

        $roles = $this->getRoles();

        if (count($roles) == 0) {
            return false;
        }

        $resource = $page->getResource();
        $privilege = $page->getPrivilege();


        if ($resource || $privilege) {
            foreach($roles as $role) {
                if ($acl->hasRole($role)) {
                    if (($resource && $acl->has($resource))) {
                        if ($acl->isAllowed($role, $resource, $privilege))
                            return true;
                    }
                }
            }

            return false;
            // determine using helper role and page resource/privilege
        }

        return true;
    }

    public function getRoles()
    {
        return $this->_roles;
    }

    public function addRole($role)
    {
        if (is_string($role) || $role instanceof Zend_Acl_Role_Interface) {
            $this->_roles[] = $role;
        } else {
            require_once 'Zend/View/Exception.php';
            $e = new Zend_View_Exception(sprintf(
                        '$role must be a string, null, or an instance of '
                        . 'Zend_Acl_Role_Interface; %s given', gettype($role)
                ));
            $e->setView($this->view);
            throw $e;
        }

        return $this;
    }

    public function setRoles(array $roles)
    {

        foreach ($roles as $role) {
            $this->addRole($role);
        }

        return $this;
    }

}