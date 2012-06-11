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
 */

/**
 * Pike_Grid is the front class. It wants a datasource passed thru the constructor
 * and generates Javascript and HTML for rendering the grid. With a AJAX POST call
 * the data is retrieved.
 *
 * @category   PiKe
 * @copyright  Copyright (C) 2011 by Pieter Vogelaar (pietervogelaar.nl) and Kees Schepers (keesschepers.nl)
 * @license    MIT
 */
class Pike_Grid
{
    /**
     * @var string
     */
    protected $_id;

    /**
     * @var string
     */
    protected $_classes;

    /**
     * @var string
     */
    protected $_pagerId;

    /**
     * @var Pike_Grid_DataSource_Interface
     */
    protected $_dataSource;

    /**
     * Amount of rows per 'page' default is 50
     *
     * @var integer
     */
    protected $_recordsPerPage = 50;

    /**
     * @var string
     */
    protected $_width = 'auto';

    /**
     * @var string
     */
    protected $_height = '100%';

    /**
     * @var string
     */
    protected $_url;

    /**
     * @var array
     */
    protected $_attributes = array();

    /**
     * Grid methods that will be executed after the grid is constructed on the client
     *
     * @var array
     */
    protected $_methods = array();

    /**
     * If filled, only those columns will be visible in the grid
     *
     * @var array
     */
    protected $_showColumns = array();

    /**
     * @var string
     */
    protected $_rowClickEvent;

    /**
     * @var boolean
     */
    protected $_historyEnabled = false;

    /**
     * The JavaScript class that creates the grid on the client side
     *
     * @var string
     */
    protected $_javaScriptClass = 'jqGrid';

    /**
     * If columns must be shown as link
     */
    protected $_showColumnsAsLink = false;

    /**
     * The URL that is used if showColumnsAsLink is TRUE
     *
     * @var string
     */
    protected $_columnLink = false;

    /**
     * Constructor
     *
     * @param Pike_Grid_DataSource_Interface $dataSource
     * @param array                          $options
     */
    public function __construct(Pike_Grid_DataSource_Interface $dataSource = null)
    {
        $id = rand(0, 3000);

        $this->setId('pgrid' . $id);

        if ($dataSource instanceof Pike_Grid_DataSource_Interface) {
            $this->setDataSource($dataSource);
        }

        $this->_url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';

        $this->setDefaults();
    }

    /**
     * Pike_Grid needs to know the data source in order to generate the initial column names etc.
     *
     * @param  Pike_Grid_DataSource_Interface $dataSource
     * @return self
     */
    public function setDataSource(Pike_Grid_DataSource_Interface $dataSource)
    {
        $this->_dataSource = $dataSource;
        return $this;
    }

    /**
     * Returns the data source
     *
     * @return Pike_Grid_DataSource_Interface
     */
    public function getDataSource()
    {
        return $this->_dataSource;
    }

    /**
     * Formats the grid object as string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getHtml();
    }

    /**
     * Sets the default grid attributes
     */
    public function setDefaults()
    {
        $this->setAttribute('hidegrid', false)
            ->setAttribute('autowidth', true);
    }

    /**
     * Sets the (unique) grid ID
     *
     * @param  string $id
     * @return self
     */
    public function setId($id)
    {
        $this->_id = $id;
        $this->_pagerId = $this->_id . '-pager';
        return $this;
    }

    /**
     * Returns the (unique) grid ID
     *
     * @return string
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * Sets the classes in the grid class attribute
     *
     * Seperate multiple classes with a whitespace.
     *
     * @param  string $classes
     * @return self
     */
    public function setClasses($classes)
    {
        $this->_classes = $classes;
        return $this;
    }

    /**
     * Returns an attribute
     *
     * @param  string $attribute
     * @return mixed
     */
    public function getAttribute($attribute)
    {
        if (array_key_exists($attribute, $this->_attributes)) {
            return $this->_attributes[$attribute];
        } else {
            return null;
        }
    }

    /**
     * Sets an attribute
     *
     * @param  string $attribute
     * @param  string $value
     * @return self
     */
    public function setAttribute($attribute, $value)
    {
        $this->_attributes[$attribute] = $value;
        return $this;
    }

    /**
     * Prepends an attribute
     *
     * @param  string $attribute
     * @param  string $value
     * @return self
     */
    public function prependAttribute($attribute, $value)
    {
        $this->_mergeAttribute($attribute, $value, 'prepend');
        return $this;
    }

    /**
     * Appends an attribute
     *
     * @param  string $attribute
     * @param  string $value
     * @return self
     */
    public function appendAttribute($attribute, $value)
    {
        $this->_mergeAttribute($attribute, $value, 'append');
        return $this;
    }

    /**
     * Removes the specified attribute
     *
     * @param string $attribute
     * @param self
     */
    public function removeAttribute($attribute)
    {
        unset($this->_attributes[$attribute]);
        return $this;
    }

    /**
     * Sets the pager ID
     *
     * @param  string $id
     * @return self
     */
    public function setPagerId($id)
    {
        $this->_pagerId = $id;
        return $this;
    }

    /**
     * Sets the amount of rows to display per page
     *
     * @param  integer $amount For unlimited rows fill in "-1"
     * @return self
     */
    public function setRowsPerPage($amount)
    {
        $amount = (int) $amount;
        if ($amount === -1) {
            $amount = 9999999;
        }

        if (null === $this->_dataSource) {
            throw new Pike_Exception('No data source defined');
        } else {
            $this->_dataSource->setResultsPerPage($amount);
        }

        $this->_recordsPerPage = $amount;

        return $this;
    }

    /**
     * Sets the caption
     *
     * @param      string $caption
     * @return     Pike_Grid
     * @deprecated Use setAttribute instead.
     */
    public function setCaption($caption)
    {
        return $this->setAttribute('caption', $caption);
    }

    /**
     * Sets the JavaScript class that creates the grid on the client side
     *
     * @param string $name
     */
    public function setJavaScriptClass($name)
    {
        $this->_javaScriptClass = $name;
        return $this;
    }

    /**
     * Returns the JavaScript class that creates the grid on the client side
     *
     * @return string
     */
    public function getJavaScriptClass()
    {
        return $this->_javaScriptClass;
    }

    /**
     * Adds a column to the grid
     *
     * @param  string  $name
     * @param  mixed   $data
     * @param  string  $label
     * @param  string  $sidx
     * @param  array   $attributes If an integer is specified, it will be seen as a position for
     *                             backward compatibility reasons. To specifically set a column
     *                             position, define it as an attribute.
     * @return self
     */
    public function addColumn($name, $data, $label = null, $sidx = null, $attributes = array())
    {
        // Determine position
        $position = null;
        if (is_integer($attributes)) {
            $position = $attributes;
            $attributes = array();
        } elseif (is_array($attributes) && isset($attributes['position'])) {
            $position = $attributes['position'];
        }

        // If no specific column position is defined, fallback on the order of the possible
        // specified showColumns property.
        if (!is_numeric($position) && count($this->_showColumns) > 0) {
            $position = array_search($name, $this->_showColumns);
        }

        if (isset($this->_dataSource->columns[$name])) {
            $attributes = array_merge(
                array('data' => $data, 'label' => $label, 'index' => $sidx), $attributes
            );
        } else {
            // Add column to the data source
            $this->_dataSource->columns->add($name, $label, $sidx, $position, $data);
        }


        // Set the sortable attribute to false if no sort index is specified
        if (null === $sidx) {
            $this->_dataSource->columns[$name]['sortable'] = false;
        }

        // Set the specified attributes
        if (is_array($attributes) && count($attributes) > 0) {
            $this->setColumnAttributes($name, $attributes);
        }

        return $this;
    }

    /**
     * Sets an attribute for the specified column name
     *
     * @param  string $columnName The name of the column
     * @param  string $attribute
     * @param  string $value
     * @return self
     */
    public function setColumnAttribute($columnName, $attribute, $value)
    {
        $this->_dataSource->columns[$columnName][$attribute] = $value;
        return $this;
    }

    /**
     * Sets multiple attributes for the specified column name
     *
     * @param string $columnName The name of the column
     * @param array  $attributes Associative array of attribute / value pairs
     */
    public function setColumnAttributes($columnName, array $attributes)
    {
        foreach ($attributes as $attribute => $value) {
            $this->setColumnAttribute($columnName, $attribute, $value);
        }
        return $this;
    }

    /**
     * Columns to show
     *
     * If set, columns in your query that aren't defined here will be hidden in the grid
     *
     * @param array $columns
     */
    public function showColumns(array $columns)
    {
        $this->_dataSource->columns->showColumns = $columns;
        return $this;
    }

    /**
     * Sets the URL for requesting JSON data
     *
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->_url = $url;
        return $this;
    }

    /**
     * Sets a grid method that will be exeucted after the grid is constructed in the client
     *
     * @param  string $name
     * @param  array  $options
     * @return self
     */
    public function setMethod($name, $options = array())
    {
        $this->_methods[$name] = $options;
        return $this;
    }

    /**
     * Sets the row click event
     *
     * @param string  $data
     * @param boolean $link Set to FALSE if custom code instead of a link must be executed
     *
     * The first argument "rowId" of the javaScript callback will be the value of the identifier
     * column for the particular row if specified.
     *
     * You can specify an identifier column on a data source like: $dataSource->setIdentifierColumn('id');
     * If no identifier column is set, the value of "rowId" will just be the grid row number.
     */
    public function setRowClickEvent($data, $link = true)
    {
        if (false !== $link) {
            if (strpos($data, 'http') === 0 || strpos($data, '=') === false) {
                if (strpos($data, '+') !== false) {
                    $literalCharacter = "";
                } else {
                    $literalCharacter = "'";
                }
                $data = $literalCharacter . $data . $literalCharacter;

                $data = <<<EOF
    if (e.shiftKey) {
                    window.open({$data}, '_blank');
                } else if (e.ctrlKey) {
                    window.open({$data});
                } else {
                    location.href = {$data};
                }
EOF;
            }
        }

        $this->appendAttribute('onCellSelect', new Zend_Json_Expr("
            function(rowId, iCol, cellContent, e) {
        // Only execute click event if the actual cell was clicked and not an element in that cell
        // like a checkbox for example.
        if ('gridcell' == $(e.target).attr('role')) {
                    " . $data . "
        }
    }
        "));

        return $this;
    }

    /**
     * Wraps the content of columns in anchor tags
     *
     * This enables the user to click with the right mouse button on column text and open the link
     * in a new tab or window. If setRowClickEvent() is used, the user could already click on a grid
     * row and open the link in a new tab (ctrl + left mouse button) or window (shift + left mouse
     * button), with the corresponding key.
     *
     * The link may contain dynamic parts of the cellvalue, options and rowObject.
     *
     * @param string $link
     */
    public function showColumnsAsLinks($link)
    {
        $this->_showColumnsAsLink = true;
        $this->_columnLink = $link;
        return $this;
    }

    /**
     * Returns the grid HTML container
     *
     * @return string
     */
    public function getHtml()
    {
        return '<table id="' . $this->_id . '" class="pike-grid ' . $this->_classes . '"></table>'
            . '<div id="' . $this->_pagerId . '"></div>';
    }

    /**
     * Returns a jqGrid declaration with all required settings
     *
     * @param  boolean $pretty Wether to print the javascript readable
     * @return string
     */
    public function getJavascript($pretty = false)
    {
       if ($this->_historyEnabled) {
           // Set history class if history is enabled and no other than the default class is defined
           if ('jqGrid' == $this->getJavaScriptClass()) {
               $this->setJavaScriptClass('jqGridHistory');
           }
       }

       $settings = array(
            'url'         => $this->_url,
            'datatype'    => 'json',
            'mtype'       => 'post',
            'rowNum'      => $this->_recordsPerPage,
            'autowidth'   => true,
            'pager'       => $this->_pagerId,
            'height'      => $this->_height,
            'viewrecords' => true,
        );

        foreach ($this->_dataSource->columns as $name => $column) {
            unset($column['data']);

            if (!isset($column['name'])) {
                throw new Pike_Exception('Column "' . $name . '" is undefined');
            }

            // If show columns is set, show only the defined columns
            if (count($this->_dataSource->columns->showColumns) > 0
                && !in_array($column['name'], $this->_dataSource->columns->showColumns)) {
                $column['hidden'] = true;
            }

            // Set link formatter if enabled and no column specific formatter is available
            if ($this->_showColumnsAsLink && !isset($column['formatter'])) {
                $column['formatter'] = $this->_getLinkFormatter();
            }

            $settings['colModel'][] = $column;
            $settings['colNames'][] = $column['label'];
        }

        if (!is_null($defaultSorting = $this->_dataSource->getDefaultSorting())) {
            $settings['sortname'] = $defaultSorting['index'];
            $settings['sortorder'] = strtolower($defaultSorting['direction']);
        }

        $output = $this->_render($settings, $pretty);
        return $output;
    }

    /**
     * Returns the link formatter
     */
    protected function _getLinkFormatter()
    {
        $columnLink = $this->_columnLink;
        $columnLink = str_replace('"', "'", $columnLink);

        if (substr($columnLink, 0, 1) !== "'") {
            $columnLink = "' + " . $columnLink;
        } else {
            $columnLink = ltrim($columnLink, "'");
        }

        if (substr($columnLink, strlen($columnLink) - 1, 1) !== "'") {
            $columnLink .= " + '";
        } else {
            $columnLink = rtrim($columnLink, "'");
        }

        return new Zend_Json_Expr("function(cellvalue, options, rowObject) {
            return $.jgrid.pike.linkFormatter(cellvalue, options, rowObject, '" . $columnLink . "');
        }");
    }

    /**
     * Enables history
     *
     * NOTE: This requires three jquery plugins (also included in the assets folder)
     *
     * - jQuery BBQ: Back Button & Query Library - v1.2.1+ http://github.com/cowboy/jquery-bbq
     * - assets/js/jquery.jq-grid.pike.js
     */
    public function enableHistory()
    {
        $this->_historyEnabled = true;
        return $this;
    }

    /**
     * Disables history
     */
    public function disableHistory()
    {
        $this->_historyEnabled = false;
        return $this;
    }

    /**
     * Renders the grid structure
     *
     * @param  array   $settings
     * @param  boolean $pretty
     * @return string
     */
    protected function _render(array $settings, $pretty = false)
    {
        $output = '';

        $settings = array_merge($settings, $this->_attributes);

        if (isset($settings['width']) && '' != $settings['width']) {
            $settings['autowidth'] = false;
        }

        $json = Zend_Json::encode($settings, false, array('enableJsonExprFinder' => true));
        if ($pretty) {
            $json = Zend_Json::prettyPrint($json);
        }

        $output .= 'var lastsel;' . PHP_EOL;
        $output .= '$("#' . $this->_id . '").' . $this->getJavaScriptClass() . '(' . $json . ');' . PHP_EOL;

        // Set possible specified row click event
        if (null !== $this->_rowClickEvent) {
            $output .= PHP_EOL . $this->_rowClickEvent . PHP_EOL;
        }

        $output .= PHP_EOL . $this->_renderMethods();
        $output .= PHP_EOL . $this->_fixCursorForNonSortableColumns() . PHP_EOL;

        return $output;
    }

    /**
     * Merges the specified attribute
     *
     * @param  string $attribute
     * @param  mixed  $value
     * @param  string $method "append" (default) or "prepend"
     * @return self
     */
    protected function _mergeAttribute($attribute, $value, $method = 'append')
    {
        if (!isset($this->_attributes[$attribute])) {
            $this->setAttribute($attribute, $value);
        } else {
            $currentValue = $this->_attributes[$attribute];

            if (($currentValue instanceof Zend_Json_Expr && strpos($currentValue, 'function') !== false)
                || ($value instanceof Zend_Json_Expr && strpos($value, 'function') !== false)
            ) {
                $currentParts = $this->_getFunctionParts($currentValue);
                $parts = $this->_getFunctionParts($value);

                if (null === $currentParts['header']) {
                    $currentParts['header'] = $parts['header'];
                    $currentParts['footer'] = $parts['footer'];
                }

                switch ($method) {
                    case 'prepend':
                        $value = $currentParts['header'] . "\n" . $parts['content'] . "\n"
                            . $currentParts['content'] . $currentParts['footer'];
                        break;
                    case 'append':
                        $value = $currentParts['header'] . "\n" . $currentParts['content'] . "\n"
                            . $parts['content'] . $currentParts['footer'];
                        break;
                }

                $value = new Zend_Json_Expr($value);
            } else {
                // Value is scalar like string or integer
                if ('prepend' == $method) {
                    $value = $value . $currentValue;
                } elseif ('append' == $method) {
                    $value = $currentValue . $value;
                }
            }

            $this->setAttribute($attribute, $value);
        }

        return $this;
    }

    /**
     * Returns the specified function in parts
     *
     * @param  string|Zend_Json_Expr $function
     * @return string
     */
    protected function _getFunctionParts($function)
    {
        $function = (string) $function;
        $function = str_replace("\n", ' ', $function);

        $matches = array();
        preg_match('/(?:function)(.*)(?:{)(.*)/', $function, $matches);

        if (!isset($matches[2])) {
            return array('header' => null, 'content' => rtrim($function, '; ') . ';', 'footer' => null);
        } else {
            return array(
                'header' => 'function' . $matches[1] . '{',
                'content' => trim(rtrim($matches[2], '}; ')) . ';',
                'footer' => '}'
            );
        }
    }

    /**
     * Renders possible defined grid methods
     */
    protected function _renderMethods()
    {
        $output = '';
        foreach ($this->_methods as $method => $options) {
            $jsonOptions = Zend_Json::prettyPrint(Zend_Json::encode($options, false,
                array('enableJsonExprFinder' => true)));
            $output .= sprintf('$("#%s").jqGrid("%s", %s);',
                $this->_id, $method, $jsonOptions) . PHP_EOL;
        }

        return $output;
    }

    /**
     * Fixes the cursor for non sortable columns
     *
     * If you define the attribute "sortable" is FALSE for a column, jqGrid will make it unsortable.
     * However it still has the class "ui-jqgrid-sortable" which causes the wrong cursor and the
     * user will still think it's sortable. Hopefully jqGrid will fix this in the future.
     *
     * For now we implemented a workaround here as described at:
     * http://stackoverflow.com/questions/5639761/change-cursor-style-depending-on-sort-or-not
     */
    protected function _fixCursorForNonSortableColumns()
    {
        $javaScript = <<<EOF
if ($("#{$this->_id}").length) {
    var cm = $("#{$this->_id}")[0].p.colModel;
    $.each($("#{$this->_id}")[0].grid.headers, function(index, value) {
        var cmi = cm[index], colName = cmi.name;
        if (!cmi.sortable && colName !== 'rn' && colName !== 'cb' && colName !== 'subgrid') {
            $('div.ui-jqgrid-sortable', value.el).css({ cursor: "default" });
        }
    });
}
EOF;

        return $javaScript;
    }
}