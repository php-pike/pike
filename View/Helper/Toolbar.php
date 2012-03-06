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
 * Pike toolbar view helper
 *
 * A toolbar will be displayed with the config setting "pike.toolbar.enabled = 1";
 *
 * NOTE: Make sure you also read the class comments of Pike_Controller_Plugin_Toolbar
 * to get it working!
 *
 * @category   PiKe
 * @copyright  Copyright (C) 2011 by Pieter Vogelaar (pietervogelaar.nl) and Kees Schepers (keesschepers.nl)
 * @license    MIT
 */
class Pike_View_Helper_Toolbar extends Zend_View_Helper_Abstract
{
    /**
     * Database query times in seconds
     *
     * @var array
     */
    protected static $_databaseQueryTimes = array(
        'verySlow' => 5,
        'slow'     => 1
    );

    /**
     * Returns the PiKe url
     */
    public function pikeUrl()
    {
        return $this->view->baseUrl() . '/pike';
    }

    /**
     * Displays the toolbar
     */
    public function toolbar(array $buttons = array(), $showDefaultButtons = true)
    {
        if (isset(Zend_Registry::get('config')->pike->toolbar->enabled)
            && Zend_Registry::get('config')->pike->toolbar->enabled
        ) {
            $leftSide = '';
            $rightSide = '';
            $dialogs = '';

            if ($showDefaultButtons) {
                $buttons = array_merge($this->_getDefaultButtons(), $buttons);
            }

            foreach ($buttons as $button) {
                if (is_array($button)) {
                    // Set class
                    $class = 'button';
                    if (isset($button['name']) && '' != $button['name']) {
                        $class .= ' button-' . $button['name'];
                    }

                    // Add content
                    if (isset($button['side']) && 'right' == $button['side']) {
                        $rightSide .= '<span class="' . $class . '">' . $button['content'] .'</span>';
                    } else {
                        $leftSide .= '<span class="' . $class . '">' . $button['content'] .'</span>';
                    }

                    // Add dialog
                    if (isset($button['dialog'])) {
                        $class = 'dialog';
                        if (isset($button['name']) && '' != $button['name']) {
                            $class .= ' dialog-' . $button['name'];
                        }

                        $dialogs .= '<div class="' . $class . '">' . $button['dialog'] . '</div>';
                    }
                } else {
                    $leftSide .= $button;
                }
            }

            $content = <<<EOF
                <div class="side-left">
                    <div class="button-container">
                        {$leftSide}
                    </div>
                </div>

                <div class="side-right">
                    <div class="button-container">
                        <span class="button-hide-toolbar">
                            <a href="#"></a>
                        </span>
                        {$rightSide}
                    </div>
                </div>

                {$dialogs}
EOF;

            $toolbar = str_replace('{content}', $content, $this->_getContainer());

            return $toolbar;
        }
    }

    /**
     * Returns the default buttons
     *
     * @return array
     */
    protected function _getDefaultButtons()
    {
        return array(
            'zf'                     => $this->getZendFrameworkButton(),
            'php'                    => $this->getPhpButton(),
            'applicationEnvironment' => $this->getApplicationEnvButton(),
            'currentRequest'         => $this->getCurrentRequestButton(),
            'executionTime'          => $this->getExecutionTimeButton(),
            'memoryUsage'            => $this->getMemoryUsageButton(),
            'databaseQueries'        => $this->getDatabaseQueriesButton(),
            'identity'               => $this->getIdentityButton(),
        );
    }

    /**
     * Returns the Zend Framework button
     *
     * @return array
     */
    public function getZendFrameworkButton()
    {
        $content = '<a class="zf" href="http://framework.zend.com" rel="external" title="Zend Framework">
                <img src="' . $this->pikeUrl() . '/images/zf-logo.png" style="width: 32px; height:32px;" />
            </a>
            ' . Zend_Version::VERSION;

        $button = array(
            'name' => 'zf',
            'side' => 'left',
            'content' => $content
        );

        return $button;
    }

    /**
     * Returns the PHP button
     *
     * @return array
     */
    public function getPhpButton()
    {
        ob_start();
        phpinfo();
        $phpInfo = ob_get_contents(); ob_end_clean();
        $parts = explode('<body>', $phpInfo);
        $phpInfo = str_replace('</body></html>', '', $parts[1]);

        try {
            ini_get_all('xdebug');
            $xdebug = '<span class="status-enabled">xdebug</span>';
        } catch (Exception $e) {
            $xdebug = '<span class="status-disabled">xdebug</span>';
        }

        if ($this->_isAccelerated()) {
            $accel = '<span class="status-enabled">accel</span>';
        } else {
            $accel = '<span class="status-disabled">accel</span>';
        }

        return array(
            'name' => 'php',
            'content' => '<a href="#">PHP ' . PHP_VERSION
                . '<span class="divider">|</span>' . $xdebug
                . '<span class="divider">|</span>' . $accel
                . '</a>',
            'dialog' => $phpInfo
        );
    }

    /**
     * Checks if PHP is accelerated
     */
    protected function _isAccelerated()
    {
        // Check if APC is available and enabled
        $apcEnabled = (bool) ini_get('apc.enabled');

        try {
            ini_get_all('xcache');
            $xCacheEnabled = true;
        } catch (Exception $e) {
            $xCacheEnabled = false;
        }

        try {
            ini_get_all('eaccelerator');
            $eAcceleratorEnabled = true;
        } catch (Exception $e) {
            $eAcceleratorEnabled = false;
        }

        return $apcEnabled || $xCacheEnabled || $eAcceleratorEnabled ? true : false;
    }

    /**
     * Returns the application environment button
     *
     * @return array
     */
    public function getApplicationEnvButton()
    {
        $dialog = null;
        if (Zend_Registry::isRegistered('config')) {
            $dialog = '<h2>Configuration for ' . APPLICATION_ENV . ' environment</h2>
                    ' . Pike_Debug::dump(Zend_Registry::get('config')->toArray(), 10, null, false);
        }

        $content = '<a href="#"><img src="' . $this->pikeUrl() . '/images/config.png" title="Environment" />'
            . APPLICATION_ENV . '</a>';

        return array(
            'name' => 'environment',
            'content' => $content,
            'dialog' => $dialog
        );
    }

    /**
     * Returns the identity button
     *
     * @return array
     */
    public function getIdentityButton()
    {
        $content = '<img src="' . $this->pikeUrl() . '/images/identity.png" title="Identity" />';

        if ('' != (string) Zend_Auth::getInstance()->getIdentity()) {
            $content .= (string) Zend_Auth::getInstance()->getIdentity();
        } else {
            $content .= 'not authenticated';
        }

        $button = array(
            'name' => 'identity',
            'side' => 'right',
            'content' => $content
        );

        return $button;
    }

    /**
     * Returns database queries button
     *
     * By default this methods counts Doctrine queries. If you have another database layer you can
     * always overwrite this method by extending this class or overwriting this button by name in
     * the constructor of this class.
     *
     * To log Doctrine queries, make sure you enabled the DebugStack logger. If you used the Bisna
     * implementation (recommended) to integrate Doctrine with Zend Framework you can add the
     * following line to your application.ini:
     *
     * resources.doctrine.dbal.connections.default.sqlLoggerClass = "Doctrine\DBAL\Logging\DebugStack"
     *
     * The Bisna implementation can be downloaded from:
     * http://github.com/guilhermoblanco/ZendFramework1-Doctrine2
     *
     * If you also want to display database queries in AJAX request they must be logged.
     * Add this line to your application.ini:
     *
     * pike.databaseQueryLog = APPLICATION_PATH "/../data/logs/toolbar_db_queries.log"
     *
     * If you want to update the toolbar after each AJAX request automatically add also this:
     * pike.toolbar.reloadAfterEachAjaxComplete = 1
     *
     * Add Pike_Controller_Plugin_Toolbar::uniqueKey as request parameter to your AJAX requests.
     * Make sure the method Pike_Controller_Plugin_Toolbar::logDatabaseQueries() is called
     * just before the AJAX response is returned and the request ends.
     *
     * @return string
     */
    public function getDatabaseQueriesButton()
    {
        $count = 0;
        $dialog = null;

        /* @var $doctrine Container\DoctrineContainer */
        $doctrine = Zend_Registry::get('doctrine');
        $sqlLogger = $doctrine->getConnection()->getConfiguration()->getSQLLogger();

        if (null !== $sqlLogger) {
            $queries = $sqlLogger->queries;
            $count = count($queries);

            $dialog .= '<div class="query-log-container">';
            $dialog .= '<h2>Database queries</h2>';
            $dialog .= '<table class="query-log">';

            $i = 0;
            foreach ($queries as $query) {
                $i++;

                $dialog .= self::renderDatabaseQuery($query, $i);
            }

            $dialog .= '</table>';
            $dialog .= '</div>';

            $ajaxQueryLogContainer = self::getAjaxQueryLogContainer();
            $dialog .= $ajaxQueryLogContainer['header'];
            $dialog .= $ajaxQueryLogContainer['footer'];
        }

        $content = '<a href="#"><img src="' . $this->pikeUrl() . '/images/db.png" title="Database queries" />'
            . '<span class="count">' . $count . '</span>'
            . '<span class="divider">|</span>'
            . '<span class="count-ajax"></span></a>';

        $button = array(
            'name'    => 'databaseQueries',
            'side'    => 'right',
            'content' => $content,
            'dialog'  => $dialog
        );

        return $button;
    }

    /**
     * Returns the execution time button
     *
     * To enable this, set this line as the very first line in your application:
     * define('PIKE_EXECUTION_START_TIME', microtime(true));
     *
     * @return string
     */
    public function getExecutionTimeButton()
    {
        if (defined('PIKE_EXECUTION_START_TIME')) {
            $executionTime = PIKE_EXECUTION_START_TIME;

            // Check if time is below 1 second
            if (microtime(true) - $executionTime < 1) {
                // Set time in miliseconds
                $executionTime = round((microtime(true) - $executionTime) * 1000) .' ms';
            } else {
                // Set time in seconds
                $executionTime = round(microtime(true) - $executionTime, 2) . ' sec';
            }
        } else {
            $executionTime = 'Unknown';
        }

        $content = '<img src="' . $this->pikeUrl() . '/images/timer.png" title="Execution time" />'
            . $executionTime;

        $button = array(
            'name' => 'executionTime',
            'side' => 'right',
            'content' => $content
        );

        return $button;
    }

    /**
     * Returns the memory usage button
     *
     * @return string
     */
    public function getMemoryUsageButton()
    {
        $content = '<img src="' . $this->pikeUrl() . '/images/memory.png" title="Memory usage" />
            ' . (memory_get_usage(true) / 1024 / 1024) . ' MB';

        $button = array(
            'name' => 'memoryUsage',
            'side' => 'right',
            'content' => $content
        );

        return $button;
    }

    /**
     * Returns the current request button
     *
     * @return string
     */
    public function getCurrentRequestButton()
    {
        $request = Zend_Controller_Front::getInstance()->getRequest();
        $currentRequest = lcfirst($this->_dashToCamelCase($request->getModuleName())) . '<span class="divider">|</span>'
            . $this->_dashToCamelCase($request->getControllerName()) . 'Controller::'
            . lcfirst($this->_dashToCamelCase($request->getActionName())) . 'Action';

        $content = '<img src="' . $this->pikeUrl() . '/images/request.png" title="Current request" />'
            . $currentRequest;

        $button = array(
            'name' => 'request',
            'content' => $content
        );

        return $button;
    }

    /**
     * Renders the specified database query
     *
     * @param array   $query
     * @param integer $i
     */
    public static function renderDatabaseQuery($query, $i)
    {
        $params = '';
        foreach ($query['params'] as $index => $param) {
            if ($param instanceof DateTime) {
                $param = $param->format('Y-m-d H:i:s');
            } else if (is_array($param)) {
                $param = var_export($param, true);
            }
            $params .= sprintf('%s, ', $param);
        }
        $params = trim($params, ', ');

        $class = '';
        if ($query['executionMS'] >= self::$_databaseQueryTimes['verySlow']) {
            $class = 'query-very-slow';
        } elseif ($query['executionMS'] >= self::$_databaseQueryTimes['slow']) {
            $class = 'query-slow';
        }

        return sprintf('<tr class="%s"><td>%s</td><td>%s</td><td>%s</td><td style="width: 100px">%s</td></tr>',
            $class, $i, $query['sql'], $params, round($query['executionMS'], 4) .' sec');
    }

    /**
     * Returns the AJAX query log container
     *
     * @param  string $attributes
     * @return array
     */
    public static function getAjaxQueryLogContainer($attributes = null)
    {
        $container['header'] = '<div class="query-log-ajax-container"' . $attributes . '>'
            . '<h2>Database queries (AJAX)'
            . '<a class="reload" href="#">reload</a></h2>'
            . '<table class="query-log query-log-ajax">';
        $container['footer'] = '</table></div>';
        return $container;
    }

    /**
     * Returns the specified string converted from dash to camel case
     *
     * @param string $string
     */
    protected function _dashToCamelCase($string)
    {
        $inflector = new Zend_Filter_Inflector(':string');
        $inflector->addRules(array(':string' => array('StringToLower', 'Word_DashToCamelCase')));
        return $inflector->filter(array('string' => $string));
    }

    /**
     * Returns the container
     *
     * @return string
     */
    protected function _getContainer()
    {
        $content = '<div id="pike-toolbar-clear"></div>

            <div id="pike-toolbar-button">
                <span class="button-show-toolbar"><a href="#">Show toolbar</a></span>
            </div>

            <div id="pike-toolbar">
              {content}
            </div>
        ';

        return $content;
    }
}