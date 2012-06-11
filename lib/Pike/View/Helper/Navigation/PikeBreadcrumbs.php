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
 * Helper for printing breadcrumbs
 *
 * This helper extends the functionality of the default breadcrumbs helper and makes it possible
 * to append dynamic breadcrumbs to the ones available from your (static) Zend_Navigation_Container.
 *
 * To use this view helper add the following lines to your application.ini:
 *
 * autoloaderNamespaces[] = "Pike"
 * resources.view.helperPath.Pike_View_Helper = APPLICATION_PATH "/../library/Pike/View/Helper"
 *
 * Append a Zend_Navigation_Page, Zend_Navigation_Page_Mvc or Zend_Navigation_Page_Uri to the
 * breadcrumbs with the following code in your controller action:
 *
 * $page = new Zend_Navigation_Page_Mvc();
 *       $page->setController('index')
 *            ->setAction('index')
 *            ->setLabel('Home');
 *
 * $this->view->navigation()->pikeBreadcrumbs()->appendPage($page);
 *
 * Add this line to your layout.phtml for rendering:
 * <?=~ $this->navigation()->pikeBreadcrumbs()->setLinkLast(false)->setMinDepth(0)->render(); ?>
 *
 * @category   PiKe
 * @copyright  Copyright (C) 2011 by Pieter Vogelaar (pietervogelaar.nl) and Kees Schepers (keesschepers.nl)
 * @license    MIT
 */
class Pike_View_Helper_Navigation_PikeBreadcrumbs extends Zend_View_Helper_Navigation_Breadcrumbs
{
    /**
     * Array of Zend_Navigation_Page objects
     *
     * @var array
     */
    protected $_appendedPages = array();

    /**
     * View helper entry point:
     * Retrieves helper and optionally sets container to operate on
     *
     * @param  Zend_Navigation_Container $container     [optional] container to
     *                                                  operate on
     * @return Zend_View_Helper_Navigation_Breadcrumbs  fluent interface,
     *                                                  returns self
     */
    public function pikeBreadcrumbs(Zend_Navigation_Container $container = null)
    {
        if (null !== $container) {
            $this->setContainer($container);
        }

        return $this;
    }

    /**
     * Renders helper
     *
     * Implements {@link Zend_View_Helper_Navigation_Helper::render()}.
     *
     * @param  Zend_Navigation_Container $container  [optional] container to
     *                                               render. Default is to
     *                                               render the container
     *                                               registered in the helper.
     * @return string                                helper output
     */
    public function render(Zend_Navigation_Container $container = null)
    {
        $content = '';

        if (null === $container) {
            $container = $this->getContainer();
        }

        // Find deepest active
        if (!$active = $this->findActive($container)) {
            return '';
        }

        $activePage = $active['page'];
        $activeNavigationPage = $activePage;

        $appendedPages = array();

        // Append pages if specified
        if (count($this->_appendedPages) > 0) {
            foreach ($this->_appendedPages as $appendedPage) {
                $activePage->addPage($appendedPage);
                $activePage = $appendedPage;
                $activePage->setActive(true);
                $appendedPages[] = $activePage;
            }
        }

        // Render
        if ($partial = $this->getPartial()) {
            $content .= $this->renderPartial($container, $partial);
        } else {
            $content .= $this->renderStraight($container);
        }

        // Remove the appended pages to keep the navigation menu in tact
        foreach ($appendedPages as $appendedPage) {
            $activeNavigationPage->removePage($appendedPage);
        }

        return $content;
    }

    /**
     * Appends an array of Zend_Navigation_Page objects
     *
     * @param  array $pages
     * @return self
     */
    public function appendPages(array $pages)
    {
        foreach ($pages as $page) {
            $this->appendPage($page);
        }
        
        return $this;
    }

    /**
     * Appends a page
     *
     * @param  Zend_Navigation_Page $page
     * @return self
     */
    public function appendPage(Zend_Navigation_Page $page)
    {
        $this->_appendedPages[] = $page;
        return $this;
    }

    /**
     * Returns the appended pages
     *
     * @return array
     */
    public function getAppendedPages()
    {
        return $this->_appendedPages;
    }
}