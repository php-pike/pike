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
 * Pike minify head script view helper
 *
 * Combines and minifies all the files available in the head script container to gain client side
 * performance.
 *
 * @category   PiKe
 * @copyright  Copyright (C) 2011 by Pieter Vogelaar (pietervogelaar.nl) and Kees Schepers (keesschepers.nl)
 * @license    MIT
 */
class Pike_View_Helper_MinifyHeadScript extends Zend_View_Helper_FormElement
{
    /**
     * Combines all the files available in the head script container for use with Minify
     *
     * If you define a file that must not be minified and is for example the fifth file, the order
     * will still be correct because the minified collections are then split up in parts.
     *
     * Added to application.ini:
     *   pike.minify.js.excludes[] = 'js/file5.js'
     *
     * Output will be:
     *   <script type="text/javascript" src="/min/?f=js/file1.js,js/file2.js,js/file3.js,js/file4.js"></script>
     *   <script type="text/javascript" src="/js/file5.js"></script>
     *   <script type="text/javascript" src="/min/?f=js/file6.js,js/file7.js"></script>
     *
     * If you specifiy "pike.minify.js.revision = 10" in your application.ini, than &10 will be appended
     * and Minify will automatically set expiration headers to 1 year in the future. Every time
     * you make changes to your JavaScript files you'll have to raise the revision number with 1
     * so that the browser for every user loads the new JavaScript files.
     *
     * @return string
     */
    public function minifyHeadScript()
    {
        $config = $this->_getConfig();
        $this->view->headScript()->getContainer()->ksort();

        if (isset($config->minify->enabled) && !$config->minify->enabled) {
            return $this->view->headScript();
        }

        if (isset($config->minify->js->excludes)) {
            $minifyJsExcludes = $config->minify->js->excludes->toArray();
        } else {
            $minifyJsExcludes = array();
        }

        $output = null;
        $previousType = null;
        $collection = array();

        foreach ($this->view->headScript()->getContainer() as $offset => $item) {
            $type = isset($item->attributes['src']) ? 'external' : 'inline';

            if (null !== $previousType && $type != $previousType) {
                $output .= $this->_combine($collection, $previousType);
                $collection = array();
            }

            if ('external' == $type) {
                $path = $item->attributes['src'];
                if (in_array($this->_getRelativePath($path), $minifyJsExcludes)) {
                    if (null !== $previousType) {
                        $output .= $this->_combine($collection, $previousType);
                        $collection = array();
                    }
                    $item->attributes['type'] = $item->type;
                    $output .= $this->_renderTag($path, $item->attributes);
                } else {
                    $collection[] = $path;
                }
            } else {
                $collection[] = $item->source;
            }

            $previousType = $type;
        }

        if (null !== $previousType) {
            $output .= $this->_combine($collection, $previousType);
            $collection = array();
        }

        return $output;
    }

    /**
     * Combines JavaScript files or inline scripts for use with Minify
     *
     * @param  array  $collection
     * @param  string $type
     * @return string
     */
    protected function _combine(array $collection, $type = 'external')
    {
        $output = null;

        if (count($collection) > 0) {
            if ($type == 'external') {
                $baseUrl = Zend_Controller_Front::getInstance()->getBaseUrl();
                $path = $baseUrl . '/min/?f=';

                foreach ($collection as $file) {
                    $file = $this->_getRelativePath($file);
                    $path .= $file . ',';
                }

                $output = $this->_renderTag(rtrim($path, ','));
            } else {
                $output .= '<script type="text/javascript">' . "\n" . '//<![CDATA[' . "\n";

                foreach ($collection as $source) {
                     $output .= $source . "\n\n";
                }

                $output .= '//]]>' . "\n" . '</script>' . "\n";
            }
        }

        return $output;
    }

    /**
     * Strips the possible available base url from the specified path and returns it
     *
     * @param string $path
     */
    protected function _getRelativePath($path)
    {
        $baseUrl = Zend_Controller_Front::getInstance()->getBaseUrl();

        // Strip possible available sub directory
        if ('' != $baseUrl && strpos($path, $baseUrl) === 0) {
            $path = substr($path, strlen($baseUrl));
        }

        // Make path relative
        $path = ltrim($path, '/');

        return $path;
    }

    /**
     * Renders the JavaScript tag with the specified path attribute
     *
     * @param  string $path
     * @param  array  $attributes
     * @return string
     */
    protected function _renderTag($path, array $attributes = array())
    {
        $config = $this->_getConfig();

        $path = $this->view->escape($path);
        if (isset($config->minify->js->revision)) {
            $revisionDelimiter = strpos($path, '?') === false ? '?' : '&';
            $path .= $revisionDelimiter . $config->minify->js->revision;
        }

        $type = isset($attributes['type']) ? $attributes['type'] : 'text/javascript';
        unset($attributes['type']);
        unset($attributes['src']);

        return '<script type="' . $type . '" src="' . $path . '"'
            . $this->_htmlAttribs($attributes)
            . '></script>' . "\n";
    }

    /**
     * Returns the PiKe config
     *
     * @return Zend_Config|null
     */
    protected function _getConfig()
    {
        return isset(Zend_Registry::get('config')->pike) ? Zend_Registry::get('config')->pike : null;
    }
}