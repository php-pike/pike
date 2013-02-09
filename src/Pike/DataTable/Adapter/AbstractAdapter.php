<?php

namespace Pike\DataTable\Adapter;

use Zend\View\Model\ViewModel;
use Pike\DataTable\DataSource\DataSourceInterface;

abstract class AbstractAdapter implements AdapterInterface
{

    /**
     * @var string 
     */
    protected $id;

    /**
     * Response parameters
     * 
     * @var array
     */
    protected $parameters = array();

    /**
     * Data table options
     * 
     * @var array
     */
    protected $options = array();

    /**
     * Filter chain
     * 
     * @var array
     */
    protected $filters = array();

    /**
     * @var ViewModel 
     */
    protected $viewModel;

    /**
     * @var ColumnBag
     */
    protected $columnBag;

    /**
     * Columns that must not be escaped by the auto escape closure
     *
     * @var array
     */
    protected $excludedColumnsFromEscaping = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->id = uniqid('datatable');
        $this->viewModel = new ViewModel();
        $this->columnBag = new ColumnBag();

        $this->setAutoEscapeFilter();
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return ColumnBag
     */
    public function getColumnBag()
    {
        return $this->columnBag;
    }

    /**
     * @param array $parameters
     */
    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param array $options
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param string $name
     * @param mixed  $value
     */
    public function setOption($name, $value)
    {
        $this->options[$name] = $value;
    }

    /**
     * @param  string $name
     * @return mixed
     */
    public function getOption($name)
    {
        return isset($this->options[$name]) ? $this->options[$name] : null;
    }

    /**
     * Sets a filter
     * 
     * @param string   $name
     * @param \Closure $filter
     * @param integer  $priority
     */
    public function setFilter($name, \Closure $filter, $priority = 0)
    {
        $this->filter[$name] = array('filter' => $filter, 'priority' => $priority);
    }

    /**
     * Returns a filter
     * 
     * @param  string $name
     * @return \Closure
     * @throws \Pike\Exception
     */
    public function getFilter($name)
    {
        if (!isset($this->filter[$name])) {
            throw new \Pike\Exception(sprintf('Cannot find a filter with the name "%s"', $name));
        }

        return $this->filter[$name]['filter'];
    }

    /**
     * Sets the filter for auto escaping column strings
     *
     * @param  integer $priority
     * @return AdapterInterface
     */
    public function setAutoEscapeFilter($priority = 25)
    {
        $adapter = $this;

        $filter = function($string, array $column) use ($adapter) {
                    if (!in_array($column['name'], $adapter->getExcludedColumnsForEscaping())) {
                        return htmlspecialchars($string, ENT_COMPAT, 'UTF-8');
                    }
                };

        $this->setFilter('escape', $filter, $priority);

        return $this;
    }

    /**
     * Exludes columns from escaping
     *
     * The specified columns will not be escaped by the auto escape closure.
     * This is relevant for columns that contain an image for example. Make sure
     * that you do escaping for the content inside that column by yourself,
     * otherwise you'll be vulnerable for XSS attacks!
     *
     * @param  array $columns
     * @return AdapterInterface
     */
    public function excludeColumnsFromEscaping(array $columns)
    {
        $this->excludedColumnsFromEscaping = $columns;

        return $this;
    }

    /**
     * Resets the entire list of columns to be excluded from escaping.
     * This will set the datasource to normal behavior.
     *
     * @return AdapterInterface
     */
    public function resetExcludedColumnsFromEscaping()
    {
        $this->excludedColumnsFromEscaping = array();

        return $this;
    }

    /**
     * Returns a list of columns that are excluded for escaping
     * 
     * @return array
     */
    public function getExcludedColumnsForEscaping()
    {
        return $this->excludedColumnsFromEscaping;
    }

    /**
     * Event that fires on filtering
     */
    abstract protected function onFilterEvent(DataSourceInterface $dataSource);

    /**
     * Event that fires on sorting
     */
    abstract protected function onSortEvent(DataSourceInterface $dataSource);
}