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
 *      ->pikeMenu($navigation)
 *      ->setACL(Zend_Registry::get('acl')) //should be a object of type Zend_ACL
 *       ->setRoles($arrayOfRoles);
 * </code>
 *
 * And make sure u use pikeMenu either in your view (layout script proberly)
 *
 * <?= $this->navigation()->pikeMenu(); ?>
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
    public function pikeMenu(Zend_Navigation_Container $container = null)
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
        // Always allow external URLs or a dash character
        if (strpos($page->getHref(), '://') !== false
            || strpos($page->getHref(), '#') !== false
        ) {
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
     * Pieter Vogelaar:
     * Support added for rel external and nofollow.
     * @link http://framework.zend.com/issues/browse/ZF-9300
     * 
     * Added support for markup list item
     * Added RAW label
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

        if (null !== $page->get('markup')) {
            // No escaping here, so make sure you supply safe input
            return $page->get('markup');
        }
        
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

        $label = '' != $page->get('rawLabel') ? $page->get('rawLabel') : $this->view->escape($label);
        
        return '<' . $element . $this->_htmlAttribs($attribs) . '>'
             . $label
             . '</' . $element . '>';
    }
    
    /**
     * Renders a normal menu (called from {@link renderMenu()})
     *
     * Pieter Vogelaar: Added appendLiClass method call
     * 
     * @param  Zend_Navigation_Container $container   container to render
     * @param  string                    $ulClass     CSS class for first UL
     * @param  string                    $indent      initial indentation
     * @param  int|null                  $minDepth    minimum depth
     * @param  int|null                  $maxDepth    maximum depth
     * @param  bool                      $onlyActive  render only active branch?
     * @return string
     */
    protected function _renderMenu(Zend_Navigation_Container $container,
                                   $ulClass,
                                   $indent,
                                   $minDepth,
                                   $maxDepth,
                                   $onlyActive)
    {
        $html = '';

        // find deepest active
        if ($found = $this->findActive($container, $minDepth, $maxDepth)) {
            $foundPage = $found['page'];
            $foundDepth = $found['depth'];
        } else {
            $foundPage = null;
        }

        // create iterator
        $iterator = new RecursiveIteratorIterator($container,
                            RecursiveIteratorIterator::SELF_FIRST);
        if (is_int($maxDepth)) {
            $iterator->setMaxDepth($maxDepth);
        }

        // iterate container
        $prevDepth = -1;
        foreach ($iterator as $page) {
            $depth = $iterator->getDepth();
            $isActive = $page->isActive(true);
            if ($depth < $minDepth || !$this->accept($page)) {
                // page is below minDepth or not accepted by acl/visibilty
                continue;
            } else if ($onlyActive && !$isActive) {
                // page is not active itself, but might be in the active branch
                $accept = false;
                if ($foundPage) {
                    if ($foundPage->hasPage($page)) {
                        // accept if page is a direct child of the active page
                        $accept = true;
                    } else if ($foundPage->getParent()->hasPage($page)) {
                        // page is a sibling of the active page...
                        if (!$foundPage->hasPages() ||
                            is_int($maxDepth) && $foundDepth + 1 > $maxDepth) {
                            // accept if active page has no children, or the
                            // children are too deep to be rendered
                            $accept = true;
                        }
                    }
                }

                if (!$accept) {
                    continue;
                }
            }

            // make sure indentation is correct
            $depth -= $minDepth;
            $myIndent = $indent . str_repeat('        ', $depth);

            if ($depth > $prevDepth) {
                // start new ul tag
                if ($ulClass && $depth ==  0) {
                    $ulClass = ' class="' . $ulClass . '"';
                } else {
                    $ulClass = '';
                }
                $html .= $myIndent . '<ul' . $ulClass . '>' . self::EOL;
            } else if ($prevDepth > $depth) {
                // close li/ul tags until we're at current depth
                for ($i = $prevDepth; $i > $depth; $i--) {
                    $ind = $indent . str_repeat('        ', $i);
                    $html .= $ind . '    </li>' . self::EOL;
                    $html .= $ind . '</ul>' . self::EOL;
                }
                // close previous li tag
                $html .= $myIndent . '    </li>' . self::EOL;
            } else {
                // close previous li tag
                $html .= $myIndent . '    </li>' . self::EOL;
            }

            // render li tag and page
            $liClass = $isActive ? ' class="active"' : '';
            
            $html .= $myIndent . '    <li' . $this->_appendLiClass($liClass, $page) . '>' . self::EOL
                   . $myIndent . '        ' . $this->htmlify($page) . self::EOL;

            // store as previous depth for next iteration
            $prevDepth = $depth;
        }

        if ($html) {
            // done iterating container; close open ul/li tags
            for ($i = $prevDepth+1; $i > 0; $i--) {
                $myIndent = $indent . str_repeat('        ', $i-1);
                $html .= $myIndent . '    </li>' . self::EOL
                       . $myIndent . '</ul>' . self::EOL;
            }
            $html = rtrim($html, self::EOL);
        }

        return $html;
    }
    
    /**
     * Renders the deepest active menu within [$minDepth, $maxDeth], (called
     * from {@link renderMenu()})
     *
     * Pieter Vogelaar: Added appendLiClass method call
     * 
     * @param  Zend_Navigation_Container $container  container to render
     * @param  array                     $active     active page and depth
     * @param  string                    $ulClass    CSS class for first UL
     * @param  string                    $indent     initial indentation
     * @param  int|null                  $minDepth   minimum depth
     * @param  int|null                  $maxDepth   maximum depth
     * @return string                                rendered menu
     */
    protected function _renderDeepestMenu(Zend_Navigation_Container $container,
                                          $ulClass,
                                          $indent,
                                          $minDepth,
                                          $maxDepth)
    {
        if (!$active = $this->findActive($container, $minDepth - 1, $maxDepth)) {
            return '';
        }

        // special case if active page is one below minDepth
        if ($active['depth'] < $minDepth) {
            if (!$active['page']->hasPages()) {
                return '';
            }
        } else if (!$active['page']->hasPages()) {
            // found pages has no children; render siblings
            $active['page'] = $active['page']->getParent();
        } else if (is_int($maxDepth) && $active['depth'] +1 > $maxDepth) {
            // children are below max depth; render siblings
            $active['page'] = $active['page']->getParent();
        }

        $ulClass = $ulClass ? ' class="' . $ulClass . '"' : '';
        $html = $indent . '<ul' . $ulClass . '>' . self::EOL;

        foreach ($active['page'] as $subPage) {
            if (!$this->accept($subPage)) {
                continue;
            }
            $liClass = $subPage->isActive(true) ? ' class="active"' : '';
            $html .= $indent . '    <li' . $this->_appendLiClass($liClass, $subPage) . '>' . self::EOL;
            $html .= $indent . '        ' . $this->htmlify($subPage) . self::EOL;
            $html .= $indent . '    </li>' . self::EOL;
        }

        $html .= $indent . '</ul>';

        return $html;
    }
    
    /**
     * Append CSS classes to the specified class from the specified page
     * 
     * @param string               $classAttribute Class attribute
     * @param Zend_Navigation_Page $page
     */
    protected function _appendLiClass($classAttribute, Zend_Navigation_Page $page)
    {
        if ('' != $page->get('liClass')) {
            $classes = array();
            
            if ('' != $classAttribute) {
                $classes = explode(' ', rtrim(str_replace('class="', '', trim($classAttribute)), '"'));
            }
            
            $classes = array_merge($classes, explode(' ', $page->get('liClass')));
            
            $classAttribute = ' class="' . implode(' ', $classes) . '"';
        }
        
        return $classAttribute;
    }
}