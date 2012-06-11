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
 * @category   PiKe
 * @copyright  Copyright (C) 2011 by Pieter Vogelaar (pietervogelaar.nl) and Kees Schepers (keesschepers.nl)
 * @license    MIT
 */

/**
 * Pike toolbar
 *
 * Add to your application.ini
 *
 * autoloaderNamespaces[] = "Pike"
 * resources.frontController.plugins.Toolbar = "Pike_Controller_Plugin_Toolbar"
 * resources.view.helperPath.Pike_View_Helper = APPLICATION_PATH "/../library/Pike/View/Helper"
 *
 * Add to the the development environment application.ini part:
 * pike.toolbar.enabled = 1
 *
 * Add this line at the bottom of your layout.phtml:
 * <?= $this->toolbar() ?>
 *
 * Or if you use the Pike View Stream wrapper (note the ~):
 * <?=~ $this->toolbar() ?>
 *
 * And make sure the pike assets folder is available in your public folder as "pike". You can
 * accomplish this by copy, symlink, svn external, etc.
 *
 * Read more about how to display database queries in the toolbar that come from AJAX request in
 * the description above Pike_View_Helper_Toolbar::getDatabaseQueriesButton.
 *
 * @category   PiKe
 * @copyright  Copyright (C) 2011 by Pieter Vogelaar (pietervogelaar.nl) and Kees Schepers (keesschepers.nl)
 * @license    MIT
 */
class Pike_Controller_Plugin_Toolbar extends Zend_Controller_Plugin_Abstract
{
    /**
     * Unique key
     *
     * Used to match database queries in AJAX requests
     *
     * @var string
     */
    static $uniqueKey = null;

    /**
     * Initialize JS and CSS files in the layout and view
     *
     * @param Zend_Controller_Request_Abstract $request
     */
    public function dispatchLoopStartup(Zend_Controller_Request_Abstract $request)
    {
        if (!isset(Zend_Registry::get('config')->pike->toolbar->enabled)
            || !Zend_Registry::get('config')->pike->toolbar->enabled
        ) {
            return;
        }

        $this->_handleRequest($request);

        $bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');
        $view = $bootstrap->bootstrap('view')->getResource('view');
        $baseUrl = $request->getBaseUrl();

        self::$uniqueKey = sha1(uniqid(null, true) . rand(1000, 1000000));

        $this->_loadAssets($view, $baseUrl);

        $scriptLines = array(
            "$.pike.toolbar.baseUrl = '" . $baseUrl . "'",
            "$.pike.toolbar.uniqueKey = '" . self::$uniqueKey . "';"
        );

        if (isset(Zend_Registry::get('config')->pike->toolbar->databaseQueryLog)
            && '' != Zend_Registry::get('config')->pike->toolbar->databaseQueryLog
        ) {
            $scriptLines[] = "$.pike.toolbar.ajaxDatabaseQueriesEnabled = true;";
        }

        if (isset(Zend_Registry::get('config')->pike->toolbar->reloadAfterEachAjaxComplete)
            && Zend_Registry::get('config')->pike->toolbar->reloadAfterEachAjaxComplete
        ) {
            $scriptLines[] = "$.pike.toolbar.reloadAfterEachAjaxComplete = true;";
        }

        $scriptLines[] = "$.pike.toolbar.init();";

        $view->headScript()->appendScript("$(document).ready(function(){
            " . implode("\n", $scriptLines) . "
        });", 'text/javascript');
    }

    /**
     * This methods is called after Zend_Controller_Front exits its dispatch loop.
     */
    public function dispatchLoopShutdown()
    {
        if (!isset(Zend_Registry::get('config')->pike->toolbar->enabled)
            || !Zend_Registry::get('config')->pike->toolbar->enabled
        ) {
            return;
        }

        self::logDatabaseQueries();
    }

    /**
     * Loads assets
     *
     * @param Zend_View $view
     * @param string    $baseUrl
     */
    protected function _loadAssets($view, $baseUrl)
    {
        $view->headLink()->appendStylesheet($baseUrl . '/pike/css/pike.toolbar.css');
        $view->headScript()->appendFile($baseUrl . '/pike/externals/jquery.cookie.js');
        $view->headScript()->appendFile($baseUrl . '/pike/js/pike.toolbar.js');
    }

    /**
     * Checks the request for PiKe actions and handles them
     *
     * @param Zend_Controller_Request_Abstract $request
     */
    protected function _handleRequest(Zend_Controller_Request_Abstract $request)
    {
        if (stripos($request->getRequestUri(), $request->getBaseUrl() . '/pike') === 0) {
            $strPos = strlen($request->getBaseUrl() . '/pike') + 1;
            list($action) = explode('/', substr($request->getRequestUri(), $strPos), 1);

            $inflector = new Zend_Filter_Inflector(':string');
            $inflector->addRules(array(':string' => array('StringToLower', 'Word_DashToCamelCase')));
            $method = lcfirst($inflector->filter(array('string' => $action)) . 'Action');

            $refClass = new Zend_Reflection_Class($this);
            try {
                $refClass->getMethod($method);
            } catch (Exception $e) {
                throw new Pike_Exception("This action doesn't exist");
            }

            echo $this->$method();
            exit;
        }
    }

    /**
     * Returns database queries for the specified unique key
     */
    public function ajaxDatabaseQueriesAction()
    {
        $request = Zend_Controller_Front::getInstance()->getRequest();
        if ($request->isXmlHttpRequest()) {

            $uniqueKey = $request->getParam('pikeToolbarUniqueKey');
            if ('' == trim($uniqueKey)) {
                return 'The parameter "pikeToolbarUniqueKey" is empty';
            }

            if (isset(Zend_Registry::get('config')->pike->toolbar->databaseQueryLog)
                && '' != Zend_Registry::get('config')->pike->toolbar->databaseQueryLog
            ) {
                $ajaxDatabaseQueries = array();

                $lines = file(Zend_Registry::get('config')->pike->toolbar->databaseQueryLog);
                foreach ($lines as $line) {
                    $parts = explode('|', $line);
                    if (isset($parts[2])) {
                        if ($parts[0] == $uniqueKey && $parts[1] == 'AJAX') {
                            unset($parts[0], $parts[1]);
                            $ajaxDatabaseQueries[] = unserialize(implode('|', $parts));
                        }
                    }
                }

                $data = '';
                $rows = '';
                $i = 0;
                foreach ($ajaxDatabaseQueries as $query) {
                    $i++;
                    $rows .= Pike_View_Helper_Toolbar::renderDatabaseQuery($query, $i);
                }

                $attributes = 'style="display: block"';
                $ajaxQueryLogContainer = Pike_View_Helper_Toolbar::getAjaxQueryLogContainer($attributes);

                $data .= $ajaxQueryLogContainer['header'];
                $data .= $rows;
                $data .= $ajaxQueryLogContainer['footer'];

                return Zend_Json::encode(array('data' => $data, 'count' => count($ajaxDatabaseQueries)));
            } else {
                return 'No databaseQueryLog defined';
            }
        }
    }

    /**
     * Writes database queries to a log file
     *
     * Only required to display database queries in AJAX requests. Make sure this method is called
     * just before the AJAX response is returned and the request ends.
     */
    public static function logDatabaseQueries()
    {
        if (isset(Zend_Registry::get('config')->pike->toolbar->databaseQueryLog)
            && '' != Zend_Registry::get('config')->pike->toolbar->databaseQueryLog
        ) {
            $request = Zend_Controller_Front::getInstance()->getRequest();
            $uniqueKey = $request->getParam('pikeToolbarUniqueKey', self::$uniqueKey);

            /* @var $doctrine Container\DoctrineContainer */
            $doctrine = Zend_Registry::get('doctrine');
            $sqlLogger = $doctrine->getConnection()->getConfiguration()->getSQLLogger();

            $log = Zend_Registry::get('config')->pike->toolbar->databaseQueryLog;

            // Create log file if it doesn't exist
            if (isset(Zend_Registry::get('config')->pike->permission->file->writable)) {
                $chmod = Zend_Registry::get('config')->pike->permission->file->writable;
            } else {
                $chmod = '644';
            }
            if (!file_exists($log)) {
                file_put_contents($log, '');
            }
            @chmod($log, octdec('0'. $chmod));

            // Write to log
            $content = null;

            $requestType = '';
            if ($request->isXmlHttpRequest()) {
                $requestType = 'AJAX';
            }

            if (null !== $sqlLogger) {
                $queries = $sqlLogger->queries;
                foreach ($queries as $query) {
                    $content .= $uniqueKey . '|' . $requestType . '|' . serialize($query) . "\n";
                }
            }

            file_put_contents($log, $content, FILE_APPEND);
        }
    }
}