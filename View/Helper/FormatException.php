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
 * @category   PiKe
 * @copyright  Copyright (C) 2011 by Pieter Vogelaar (platinadesigns.nl) and Kees Schepers (keesschepers.nl)
 * @license    MIT
 */

/**
 * Helper for formatting an exception
 *
 * @category   PiKe
 * @copyright  Copyright (C) 2011 by Pieter Vogelaar (platinadesigns.nl) and Kees Schepers (keesschepers.nl)
 * @license    MIT
 */
class Pike_View_Helper_FormatException extends Zend_View_Helper_Abstract
{
    /**
     * @var Exception
     */
    private $exception;

    /**
     * Formats the specified exception
     *
     * @param  Exception $exception
     * @return string
     */
    public function formatException(Exception $e)
    {
        $this->exception = $e;

        $output = '<div id="exception-container">';

        $responseCode = Zend_Controller_Front::getInstance()->getResponse()->getHttpResponseCode();

        $output .= $this->_getIcon();
        $output .= $responseCode . ' | ' . get_class($e) . ': ' . $e->getCode();

        $output .= $this->_renderStackTrace();
        $output .= $this->_renderRequest();
        $output .= $this->_renderIdentity();
        $output .= $this->_renderGlobalVariables();

        $output .= '</div>';

        $this->_addStyles();
        $this->_addScript();

        return $output;
    }

    /**
     * Returns a rendered stack trace
     *
     * @return string
     */
    protected function _renderStackTrace()
    {
        $output = '<h2>Stack trace</h2>';
        $output .= '<ul class="stack-trace">';
        $traces = $this->_getTraces($this->exception, 'html');
        foreach ($traces as $trace) {
            $output .= '<li>' . $trace . '</li>';
        }
        $output .= '</ul>';
        return $output;
    }

    /**
     * Returns a rendered request
     *
     * @return string
     */
    protected function _renderRequest()
    {
        return '<h2>Request <a href="#" onclick="toggle(\'exception-request\'); return false;">...</a></h2>'
            . '<div id="exception-request" style="display: none"><pre>'
            . $this->view->escape(var_export($this->view->request->getParams(), true))
            . '</pre></div>';
    }

    /**
     * Returns a rendered identity
     *
     * @return string
     */
    protected function _renderIdentity()
    {
        $identity = Zend_Auth::getInstance()->getIdentity();
        if ($identity) {
            return '<h2>Identity <a href="#" onclick="toggle(\'exception-identity\'); return false;">...</a></h2>'
                . '<div id="exception-identity" style="display: none"><pre>'
                . $this->view->escape(var_export($identity->toArray(), true))
                . '</pre></div>';
        }
    }

    /**
     * Returns rendered global variables
     *
     * @return string
     */
    protected function _renderGlobalVariables()
    {
        return '<h2>Global variables <a href="#" onclick="toggle(\'exception-global-variables\'); return false;">...</a></h2>'
            . '<div id="exception-global-variables" style="display: none">'
            . '<strong>Cookie</strong>'
            . $this->_renderDefinitionTable($_COOKIE)
            . '<strong>Env</strong>'
            . $this->_renderDefinitionTable($_ENV)
            . '<strong>Server</strong>'
            . $this->_renderDefinitionTable($_SERVER)
            . '</div>';
    }

    /**
     * Renders a definition list
     *
     * @param  array $array
     * @return string
     */
    protected function _renderDefinitionList(Array $array)
    {
        $output = '<dl class="definition-list">';
        foreach ($array as $key => $value) {
            $output .= '<dt class="term">'. $this->view->escape($key) .':</dt>';
            $output .= '<dd class="description">'. $this->view->escape($value) .'</dd>';
        }
		$output .= '</dl>';
        return $output;
    }

    /**
     * Renders a definition table
     *
     * @param  array $array
     * @return string
     */
    protected function _renderDefinitionTable(Array $array)
    {
        $output = '<table class="definition-table">';
        foreach ($array as $key => $value) {
            $output .= '<tr>';
            $output .= '<td class="term">'. $this->view->escape($key) .':</td>';
            $output .= '<td class="description">'. $this->view->escape($value) .'</td>';
            $output .= '</tr>';
        }
		$output .= '</table>';
        return $output;
    }

    /**
     * Escapes a string value with html entities
     *
     * @param  string  $value
     * @return string
     */
    protected function _escape($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        return $this->view->escape($value);
    }

    /**
     * Adds styles
     */
    protected function _addStyles()
    {
        $styles = <<<EOF
#exception-container {
    color: #000;
    border-top: 1px solid #ccc;
    margin-top: 10px;
    padding-top: 10px;
}
#exception-container .stack-trace,
#exception-container .stack-trace ul {
    list-style: decimal outside none;
    padding-left: 20px;
}
#exception-container .stack-trace li {
    margin: 0;
    padding-bottom: 5px;
}
#exception-container .stack-trace ol {
    list-style-position: inside;
    font-family: monospace;
    margin: 0;
    padding: 10px 0;
    white-space: pre;
}
#exception-container .stack-trace ol li {
    margin: -5px;
    padding: 0;
}
#exception-container a {
    color: #000;
}
#exception-container .selected {
    background-color: #DDDDDD;
    font-weight: bold;
    padding: 2px 0;
}
#exception-container .code {
    overflow: auto;
}
#exception-container .icon-exception {
    float: right;
}
#exception-container .link-toggle {
    text-decoration: underline;
}
#exception-container .link-toggle:hover {
    text-decoration: none;
}
#exception-container .definition-list {
	float: left;
}
#exception-container .definition-list .term {
	clear: left;
	float: left;
	width: 105px;
	color: gray;
	text-align: right;
	margin-bottom: 5px;
}
#exception-container .definition-list .description {
	float: left;
	width: 420px;
	margin-left: 10px;
	margin-bottom: 5px;
}
#exception-container .definition-table .term {
    color: gray;
}
#exception-container .definition-table {
    border-collapse: collapse;
    table-layout: fixed;
}
#exception-container .definition-table td {
    padding: 1px 10px 1px 0;
    word-wrap: break-word;
}
#exception-container .definition-table .term {
    width: 225px;
}
EOF;
        $this->view->headStyle()->prependStyle($styles, 'text/css');
    }

    /**
     * Adds JavaScript
     */
    protected function _addScript()
    {
        $script = <<<EOF
function toggle(id)
{
    element = document.getElementById(id);
    element.style.display = element.style.display == 'none' ? 'block' : 'none';
}
EOF;
        $this->view->headScript()->prependScript($script, 'text/javascript');
    }

    /**
     * Returns an exception icon
     *
     * @return string
     */
    protected function _getIcon()
    {
        return '<img class="icon-exception" src="data:image/png;base64,'
            . 'iVBORw0KGgoAAAANSUhEUgAAABwAAAAZCAYAAAAiwE4nAAAABmJLR0QA/wD/AP+gvaeTAAAACXBIWXMAAAs'
            . 'TAAALEwEAmpwYAAAEfklEQVRIx7VUa0wUVxT+Znd2FxZk0YKACAtaGwEDUhUTBTEIItmKYk3UNqalD7StMS'
            . 'Q1JKatP5omTYyx0VRrjPERX7XWAG2t9GVi3drU2h+gi4BCWV67lOe6O/uYmXtPf0BRrMBK6UlObmbON9935'
            . 'p6HQEQI1o7uXeSy1dsjHn2Xlpr0oKzililoEiIKymvOr9q+pzyZZN894moHcbWDZN892lOeTN9fKHgrWB5N'
            . 'sInZ7joOrtv4JgR2F4r0AxTpRwisEes2bsNtW+eBYHmCEqw8kVsp6oy6jMUFYIoTxFUQqWBqNzIWr4aoC9N'
            . 'VnlxZNSWC1mqLsa6ubd36zbug+m3gXBlypoCYAuavx4Ytu1Fbay+2VluME/GJEwHsnT3WpLlzhbi4Z6D46g'
            . 'BosP/gVQDA669kIzJSRWxcApLnPie0dw3cALBw0k1z5dyKrIqyWHL1/Eye7n3kcX5MH75fRAAIAJUUZ5Cne'
            . 'z9JPYfI1XuDKsriqOZcbtakm6alte/yqsIi6LVt4KobxAIAqSPxwUEJxAPgqgcG0YH8NS+gxT5wZVI1/PrU'
            . '0q1O54OoFfmvQZZsIBYA5zIy0maOYFZmJ4GYAuIyZG8jcvLfgMPhmnHlbG7pUws2NfUeWVvyMpj3d3DVB84'
            . 'C4MyPxNkP+8I0TQRn/qGY6gP316J4w6uob3AceirBzw9nnBD1RmN65nLIUhOIBUBcBjEZ5viQEZx5thFcdQ'
            . '+50o+A5w7SM5dBFHWhFz5bdOpJ3MLjq63mdHrIr7f6PaXbPtBGht4DUwYAQXikyVTkb/gKtbYBNFpzYYoY3'
            . 'egarR6D7jCcPmtly5ZEh6/ZWucfdyycPep3ycmJ2phoAzx9ziERLoMzN4hJAICI8KEkp4VxcCaP+p4zGdHT'
            . 'w2FOiNB2OTzfAMgf80qrjmem1zf256zf9B6kvmvgqgeqrw2qvx1cGQRxBcQV5GRFIGepaeT5cfdJXbAUPY+'
            . '79z15l47MWzDmH7a3P/g2Ly9X4O6LkKUWEPeOMbwMpnANiClPDkOBXteL3OXxQnNL72UA5n/V8NLR9Bdrb/'
            . 'ddLN+5VvD23wTA8d9MgNH0LD759DrS5oeUbN7RWjXqSu//OXi8sCBFkN11IFJAxMZ0e4cP12+6xsUQqZC9n'
            . 'ShclYTWtsDJUTU8cyDlsE7URqTMC4Eiu8fN+/JVF7I3NuGlna2wlDaPi1VkN1LnR0GvF00n95kPAICm+tgc'
            . 'Q9N9V5ll9Tz4JSem2vySE5bCFDS3+t+uPjbHIA64dF/MioU2aoYGXndgQgJLngnWL0PR1iUje0n4hHimBhA'
            . '1XYA5IVz8q1eu0oSGqCc6HV4ihAIQgso6MV4flNhDUR/iYqbBI1GqZtM7zVUzZ4p3rl5rQIgxesqvVCsa0O'
            . '8y4Lc/nGp8rLhcBIA7Df7C7hlKe2ZGojYmZsGUCsqygvOnf6FZsbrtm3bY+wUigiAIC/funlXR0RXYgv/Bz'
            . 'AmGn979qGvXyOALghAJQAtAB0A/fIrDY6MNurj/LBqADW8OFYACQB4+2d80or7Ra0ZtxAAAAABJRU5ErkJg'
            . 'gg==" alt="Exception" />';
    }

    /**
     * Returns an excerpt of a code file around the given line number.
     *
     * @copyright Copyright (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
     * @copyright Copyright (c) 2004-2006 Sean Kerr <sean@code-box.org>
     * @license MIT
     *
     * @param string $file  A file path
     * @param int    $line  The selected line number
     * @return string An HTML string
     */
    protected function _fileExcerpt($file, $line)
    {
        if (is_readable($file)) {
            $content = preg_split('#<br />#', highlight_file($file, true));

            $lines = array();
            for ($i = max($line - 3, 1), $max = min($line + 3, count($content)); $i <= $max; $i++) {
                $lines[] = '<li' . ($i == $line ? ' class="selected"' : '') . '>' . $content[$i - 1] . '</li>';
            }

            return '<ol start="' . max($line - 3, 1) . '">' . implode("\n", $lines) . '</ol>';
        }
    }

    /**
     * Returns an array of exception traces.
     *
     * @copyright Copyright (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
     * @copyright Copyright (c) 2004-2006 Sean Kerr <sean@code-box.org>
     * @license MIT
     *
     * @param Exception $exception  An Exception implementation instance
     * @param string    $format     The trace format (txt or html)
     * @return array An array of traces
     */
    protected function _getTraces($exception, $format = 'txt')
    {
        $traceData = $exception->getTrace();
        array_unshift($traceData, array(
            'function' => '',
            'file' => $exception->getFile() != null ? $exception->getFile() : null,
            'line' => $exception->getLine() != null ? $exception->getLine() : null,
            'args' => array(),
        ));

        $traces = array();
        if ($format == 'html') {
            $lineFormat = 'at <strong>%s%s%s</strong>(%s)<br />in <em>%s</em> line %s'
                . ' <a href="#" class="link-toggle" onclick="toggle(\'%s\'); return false;">...</a>'
                . '<br /><ul class="code" id="%s" style="display: %s">%s</ul>';
        } else {
            $lineFormat = 'at %s%s%s(%s) in %s line %s';
        }

        for ($i = 0, $count = count($traceData); $i < $count; $i++) {
            $line = isset($traceData[$i]['line']) ? $traceData[$i]['line'] : null;
            $file = isset($traceData[$i]['file']) ? $traceData[$i]['file'] : null;
            $args = isset($traceData[$i]['args']) ? $traceData[$i]['args'] : array();
            $traces[] = sprintf($lineFormat,
                (isset($traceData[$i]['class']) ? $traceData[$i]['class'] : ''),
                (isset($traceData[$i]['type']) ? $traceData[$i]['type'] : ''),
                $traceData[$i]['function'],
                $this->_formatArgs($args, false, $format),
                $this->_formatFile($file, $line, $format, null === $file ? 'n/a' : $file),
                null === $line ? 'n/a' : $line,
                'trace_' . $i, 'trace_' . $i, $i == 0 ? 'block' : 'none',
                $this->_fileExcerpt($file, $line)
            );
        }

        return $traces;
    }

    /**
     * Formats an array as a string.
     *
     * @copyright Copyright (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
     * @copyright Copyright (c) 2004-2006 Sean Kerr <sean@code-box.org>
     * @license MIT
     *
     * @param array   $args     The argument array
     * @param boolean $single
     * @param string  $format   The format string (html or txt)
     * @return string
     */
    protected function _formatArgs($args, $single = false, $format = 'html')
    {
        $result = array();

        $single and $args = array($args);

        foreach ($args as $key => $value) {
            if (is_object($value)) {
                $formattedValue = ($format == 'html' ? '<em>object</em>' : 'object')
                    . sprintf("('%s')", get_class($value));
            } else if (is_array($value)) {
                $formattedValue = ($format == 'html' ? '<em>array</em>' : 'array')
                    . sprintf("(%s)", $this->_formatArgs($value));
            } else if (is_string($value)) {
                $formattedValue = ($format == 'html' ? sprintf("'%s'", $this->_escape($value)) : "'$value'");
            } else if (null === $value) {
                $formattedValue = ($format == 'html' ? '<em>null</em>' : 'null');
            } else {
                $formattedValue = $value;
            }

            $result[] = is_int($key) ? $formattedValue : sprintf("'%s' => %s", $this->_escape($key), $formattedValue);
        }

        return implode(', ', $result);
    }

    /**
     * Formats a file path.
     *
     * @copyright Copyright (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
     * @copyright Copyright (c) 2004-2006 Sean Kerr <sean@code-box.org>
     * @license MIT
     *
     * @param  string  $file   An absolute file path
     * @param  integer $line   The line number
     * @param  string  $format The output format (txt or html)
     * @param  string  $text   Use this text for the link rather than the file path
     * @return string
     */
    protected function _formatFile($file, $line, $format = 'html', $text = null)
    {
        if (null === $text) {
            $text = $file;
        }

        if ('html' == $format && $file && $line && $linkFormat = ini_get('xdebug.file_link_format')) {
            $link = strtr($linkFormat, array('%f' => $file, '%l' => $line));
            $text = sprintf('<a href="%s" title="Click to open this file" class="file_link">%s</a>', $link, $text);
        }

        return $text;
    }
}