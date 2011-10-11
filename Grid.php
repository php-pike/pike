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
 */

/**
 * Pike_Grid is the front class. It wants a datasource passed thru the constructor
 * and generates Javascript and HTML for rendering the grid. With a AJAX POST call
 * the data is retrieved.
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
     * @var string
     */
    protected $_caption;

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
     * Pike_Grid needs to know the datasource in order to generate the initial column names etc.
     *
     * @param Pike_Grid_DataSource_Interface $dataSource
     * @param array                          $options
     */
    public function __construct(Pike_Grid_DataSource_Interface $dataSource, array $options = array())
    {
        $id = rand(0, 3000);

        $this->setId('pgrid' . $id);
        $this->_dataSource = $dataSource;
        $this->_url = $_SERVER['REQUEST_URI'];

        $this->setDefaults();
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
        $this->setAttribute('hidegrid', false);
    }

    /**
     * Sets the (unique) grid ID
     *
     * @param  string $id
     * @return Pike_Grid
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
     * Sets a grid attribute
     *
     * @param  string $attribute
     * @param  string $value
     * @return Pike_Grid
     */
    public function setAttribute($attribute, $value)
    {
        $this->_attributes[$attribute] = $value;
        return $this;
    }

    /**
     * Sets the pager ID
     *
     * @param  string $id
     * @return Pike_Grid
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
     * @return Pike_Grid
     */
    public function setRowsPerPage($amount)
    {
        $amount = (int) $amount;
        if ($amount === -1) {
            $amount = 9999999;
        }
        $this->_dataSource->setResultsPerPage($amount);
        $this->_recordsPerPage = $amount;
        return $this;
    }

    /**
     * Sets the caption
     *
     * @param  string $caption
     * @return Pike_Grid
     */
    public function setCaption($caption)
    {
        $this->_caption = $caption;
        return $this;
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
     * @return Pike_Grid
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
        // specified showColumns.
        if (!is_numeric($position) && count($this->_showColumns) > 0) {
            $position = array_search($name, $this->_showColumns);
        }

        // Add column to the data source
        $this->_dataSource->columns->add($name, $label, $sidx, $position, $data);

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
     * @return Pike_Grid
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
        $this->_showColumns = $columns;
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
     * Sets the grid width
     *
     * @param  string $width
     * @return Pike_Grid
     */
    public function setWidth($width = 'auto')
    {
        if (!is_numeric($width)) {
            $width = 'auto';
        }

        return $this;
    }

    /**
     * Sets a grid method that will be exeucted after the grid is constructed in the client
     *
     * @param  string $name
     * @param  array  $options
     * @return Pike_Grid
     */
    public function setMethod($name, $options = array())
    {
        $this->_methods[$name] = $options;
        return $this;
    }

    /**
     * Sets the row click event
     *
     * @param string $data
     *
     * The first argument "rowId" of the javaScript callback will be the value of the identifier
     * column for the particular row if specified.
     *
     * You can specify an identifier column on a data source like: $dataSource->setIdentifierColumn('id');
     * If no identifier column is set, the value of "rowId" will just be the grid row number.
     */
    public function setRowClickEvent($data)
    {
        $this->_rowClickEvent = <<<EOF
$('#{$this->_id}').jqGrid('setGridParam', {
    'onCellSelect' : function(rowId, iCol, cellContent, e) {
        // Only execute click event if the actual cell was clicked and not an element in that cell
        // like a checkbox for example.
        if ('gridcell' == $(e.target).attr('role')) {
            {$data}
        }
    }
}).trigger('reloadGrid');
EOF;

        return $this;
    }

    /**
     * Returns the grid HTML container
     *
     * @return string
     */
    public function getHtml()
    {
        return '<table id="' . $this->_id . '"></table><div id="' . $this->_pagerId . '"></div>';
    }

    /**
     * Returns a jqGrid declaration with all required settings
     */
    public function getJavascript()
    {
        $settings = array(
            'url' => $this->_url,
            'datatype' => 'json',
            'mtype' => 'post',
            'rowNum' => $this->_recordsPerPage,
            'autoWidth' => true,
            'pager' => $this->_pagerId,
            'height' => $this->_height,
            'viewrecords' => true,
        );

        foreach ($this->_dataSource->columns as $column) {
            unset($column['data']);

            // If show columns is set, show only the defined columns
            if (count($this->_showColumns) > 0 && !in_array($column['name'], $this->_showColumns)) {
                $column['hidden'] = true;
            }

            $settings['colModel'][] = $column;
            $settings['colNames'][] = $column['label'];
        }

        if (!is_null($defaultSorting = $this->_dataSource->getDefaultSorting())) {
            $settings['sortname'] = $defaultSorting['index'];
            $settings['sortorder'] = strtolower($defaultSorting['direction']);
        }

        if (!is_null($this->_caption)) {
            $settings['caption'] = $this->_caption;
        }

        switch ($this->_width) {
            case 'auto' :
                $settings['autowidth'] = true;
                break;
            default : //width in pixels?
                $settings['width'] = (int) $this->_width;
                break;
        }

        $settings = array_merge($settings, $this->_attributes);

        $json = Zend_Json::prettyPrint(Zend_Json::encode($settings, false, array('enableJsonExprFinder' => true)));

        $output = 'var lastsel;' . PHP_EOL;
        $output .= '$("#' . $this->_id . '").jqGrid(' . $json . ');' . PHP_EOL;

        // Set possible specified row click event
        if (null !== $this->_rowClickEvent) {
            $output .= PHP_EOL . $this->_rowClickEvent . PHP_EOL;
        }

        $output .= PHP_EOL . $this->_renderMethods();
        $output .= PHP_EOL . $this->_fixCursorForNonSortableColumns() . PHP_EOL;

        return $output;
    }

    /**
     * Renders possible defined grid methods
     */
    protected function _renderMethods()
    {
        $output = '';
        foreach ($this->_methods as $method => $options) {
            $jsonOptions = Zend_Json::prettyPrint(Zend_Json::encode($options, false, array('enableJsonExprFinder' => true)));
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
var cm = $("#{$this->_id}")[0].p.colModel;
$.each($("#{$this->_id}")[0].grid.headers, function(index, value) {
    var cmi = cm[index], colName = cmi.name;
    if (!cmi.sortable && colName !== 'rn' && colName !== 'cb' && colName !== 'subgrid') {
        $('div.ui-jqgrid-sortable', value.el).css({ cursor: "default" });
    }
});
EOF;

        return $javaScript;
    }
}