<?php
/**
 * Copyright (C) 2011 by Pieter Vogelaar (pietervogelaar.nl) and Kees Schepers (keesschepers.nl)
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
 * @copyright  Copyright (C) 2011 by Pieter Vogelaar (pietervogelaar.nl) and Kees Schepers (keesschepers.nl)
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
 * @copyright  Copyright (C) 2011 by Pieter Vogelaar (pietervogelaar.nl) and Kees Schepers (keesschepers.nl)
 * @license    MIT
 */
class Pike_View_Helper_Navigation_PikeMenu extends Zend_View_Helper_Navigation_Menu
{
    /**
     * Roles
     * 
     * @var array
     */
    protected $_roles = array();

    /**
     * Pike menu
     * 
     * @param  Zend_Navigation_Container $container
     * @return Pike_View_Helper_Navigation_PikeMenu
     */
    public function PikeMenu(Zend_Navigation_Container $container = null)
    {
        if (null !== $container) {
            $this->setContainer($container);
        }

        return $this;
    }

    /**
     * Checks if a role has access to the specified page
     * 
     * @param  Zend_Navigation_Page $page
     * @return boolean
     */
    protected function _acceptAcl(Zend_Navigation_Page $page)
    {
        // Always allow external URLs
        if (strpos($page->getHref(), '://') !== false) {
            return true;
        }
        
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
                        if ($acl->isAllowed($role, $resource, $privilege)) {
                            return true;
                        }
                    }
                }
            }

            return false;
        }

        return true;
    }

    /**
     * Returns the roles
     * 
     * @return array
     */
    public function getRoles()
    {
        return $this->_roles;
    }

    /**
     * Adds the specified role
     * 
     * @param  Zend_Acl_Role_Interface $role
     * @return Pike_View_Helper_Navigation_PikeMenu 
     */
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

    /**
     * Sets the specified roles
     * 
     * @param  array $roles
     * @return Pike_View_Helper_Navigation_PikeMenu
     */
    public function setRoles(array $roles)
    {

        foreach ($roles as $role) {
            $this->addRole($role);
        }

        return $this;
    }
    
    /**
     * Returns an HTML string containing an 'a' element for the given page if
     * the page's href is not empty, and a 'span' element if it is empty
     *
     * Overrides {@link Zend_View_Helper_Navigation_Abstract::htmlify()}.
     * 
     * Support added for rel external and nofollow.
     * @link http://framework.zend.com/issues/browse/ZF-9300
     *
     * @param  Zend_Navigation_Page $page  page to generate HTML for
     * @return string                      HTML string for the given page
     */
    public function htmlify(Zend_Navigation_Page $page)
    {
        // get label and title for translating
        $label = $page->getLabel();
        $title = $page->getTitle();

        // translate label and title?
        if ($this->getUseTranslator() && $t = $this->getTranslator()) {
            if (is_string($label) && !empty($label)) {
                $label = $t->translate($label);
            }
            if (is_string($title) && !empty($title)) {
                $title = $t->translate($title);
            }
        }

        // get attribs for element
        $attribs = array(
            'id'     => $page->getId(),
            'title'  => $title,
            'class'  => $page->getClass()
        );

        // does page have a href?
        if ($href = $page->getHref()) {
            $element = 'a';
            $attribs['href'] = $href;
            $attribs['target'] = $page->getTarget();
            $attribs['accesskey'] = $page->getAccessKey();
            
            // Check if there is a 'nofollow' or 'external' relation
            if ('true' == $page->get('relNofollow')) {
                $attribs['rel'] = isset($attribs['rel']) ? $attribs['rel'] . ' nofollow' : 'nofollow';
            }
            if ('true' == $page->get('relExternal')) {
                $attribs['rel'] = isset($attribs['rel']) ? $attribs['rel'] . ' external' : 'external';
            }
        } else {
            $element = 'span';
        }

        return '<' . $element . $this->_htmlAttribs($attribs) . '>'
             . $this->view->escape($label)
             . '</' . $element . '>';
    }
}