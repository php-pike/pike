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
 * Pike minify head link view helper for minifying style sheets
 *
 * Combines and minifies all the style sheet files available in the head link container to
 * gain client side performance.
 *
 * @category   PiKe
 * @copyright  Copyright (C) 2011 by Pieter Vogelaar (pietervogelaar.nl) and Kees Schepers (keesschepers.nl)
 * @license    MIT
 */
class Pike_View_Helper_MinifyHeadLink extends Zend_View_Helper_HtmlElement
{
    /**
     * $_validAttributes
     *
     * @var array
     */
    protected $_itemKeys = array('charset', 'href', 'hreflang', 'id', 'media', 'rel', 'rev', 'type', 'title', 'extras');
    
    /**
     * Combines all the style sheets available in the head link container for use with Minify
     *
     * If you define a file that must not be minified and is for example the fifth file, the order
     * will still be correct because the minified collections are then split up in parts. Different
     * media types and conditional statements will also be split up with the order preserved.
     *
     * Added to application.ini:
     *   minify.linkExcludes[] = 'css/file5.css'
     *
     * Output will be:
     *   <link rel="stylesheet" media="all" src="/min/?f=css/file1.css" />
     *   <link rel="stylesheet" media="screen" src="/min/?f=css/file2.css,css/file3.css,css/file4.css" />
     *   <link rel="stylesheet" media="screen" src="/js/file5.js" />
     *   <link rel="stylesheet" media="screen" src="/min/?f=css/file6.css,css/file7.css" />
     *
     * @return string
     */
    public function minifyHeadLink()
    {
        $config = Zend_Registry::get('config');
        if (isset($config->minify->enabled) && !$config->minify->enabled) {
            return $this->view->headLink();
        }

        if (isset(Zend_Registry::get('config')->minify->linkExcludes)) {
            $minifyLinkExcludes = Zend_Registry::get('config')->minify->linkExcludes->toArray();
        } else {
            $minifyLinkExcludes = array();
        }

        $output = null;
        $previousMedia = null;
        $previousConditionalStylesheet = null;
        $collection = array();
        $nonStylesheetLinks = array();
        
        $this->view->headLink()->getContainer()->ksort();
        
        foreach ($this->view->headLink()->getContainer() as $offset => $item) {
            if ('stylesheet' != $item->rel) {
                $nonStylesheetLinks[] = $this->_itemToString($item);
                continue;
            }
            
            $media = $item->media;
            $conditionalStylesheet = $item->conditionalStylesheet;

            if (null !== $previousMedia
                && ($media != $previousMedia || $conditionalStylesheet != $previousConditionalStylesheet)) {
                $output .= $this->_combine($collection, $previousMedia, $previousConditionalStylesheet);
                $collection = array();
            }

            if (in_array($this->_getRelativePath($item->href), $minifyLinkExcludes)) {
                if (null !== $previousMedia) {
                    $output .= $this->_combine($collection, $previousMedia, $previousConditionalStylesheet);
                    $collection = array();
                }
                $attributes = isset($item->extras) ? $item->extras : array();
                $output .= $this->_renderTag($item->href, $media, $conditionalStylesheet, $attributes);
            } else {
                $collection[] = $item->href;
            }

            $previousMedia = $media;
            $previousConditionalStylesheet = $conditionalStylesheet;
        }

        if (null !== $previousMedia) {
            $output .= $this->_combine($collection, $previousMedia, $previousConditionalStylesheet);
            $collection = array();
        }

        $output = implode("\n", $nonStylesheetLinks) . "\n" . $output;
        
        return $output;
    }

    /**
     * Combines external styles for use with Minify
     *
     * @param  array  $collection
     * @param  string $media
     * @param  string $conditionalStylesheet
     * @return string
     */
    protected function _combine(array $collection, $media = 'screen', $conditionalStylesheet = null)
    {
        if (count($collection) == 0) {
            return;
        }

        $baseUrl = Zend_Controller_Front::getInstance()->getBaseUrl();
        $path = $baseUrl . '/min/?f=';

        foreach ($collection as $file) {
            $file = $this->_getRelativePath($file);
            $path .= $file . ',';
        }

        return $this->_renderTag(rtrim($path, ','), $media, $conditionalStylesheet);
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
     * Renders the style sheet tag with the specified path attribute
     *
     * @param string $path
     * @param string $media
     * @param string $conditionalStylesheet
     * @param array  $attributes
     */
    protected function _renderTag($path, $media = 'screen', $conditionalStylesheet = null,
        array $attributes = array()
    ) {
        $output = null;

        $link = '<link'
            . ' href="' . $this->view->escape($path) . '"'
            . ' media="' . $this->view->escape($media) .'"'
            . ' rel="stylesheet" type="text/css"'
            . $this->_htmlAttribs($attributes) . $this->getClosingBracket()
            . "\n";

        if ('' != $conditionalStylesheet) {
            $output .= sprintf("<!--[if %s]>\n    %s<![endif]-->\n", $conditionalStylesheet, $link);
        } else {
            $output .= $link;
        }

        return $output;
    }
    
    /**
     * Create HTML link element from data item
     *
     * @param  stdClass $item
     * @return string
     */
    protected function _itemToString(stdClass $item)
    {
        $attributes = (array) $item;
        $link       = '<link ';

        foreach ($this->_itemKeys as $itemKey) {
            if (isset($attributes[$itemKey])) {
                if(is_array($attributes[$itemKey])) {
                    foreach($attributes[$itemKey] as $key => $value) {
                        $link .= sprintf('%s="%s" ', $key, $this->view->escape($value));
                    }
                } else {
                    $link .= sprintf('%s="%s" ', $itemKey, $this->view->escape($attributes[$itemKey]));
                }
            }
        }

        if ($this->view instanceof Zend_View_Abstract) {
            $link .= ($this->view->doctype()->isXhtml()) ? '/>' : '>';
        } else {
            $link .= '/>';
        }

        if (($link == '<link />') || ($link == '<link >')) {
            return '';
        }

        if (isset($attributes['conditionalStylesheet'])
            && !empty($attributes['conditionalStylesheet'])
            && is_string($attributes['conditionalStylesheet']))
        {
            $link = '<!--[if ' . $attributes['conditionalStylesheet'] . ']> ' . $link . '<![endif]-->';
        }

        return $link;
    }
}