<?php

namespace Pike\DataTable\Adapter;

use Pike\DataTable;
use Pike\DataTable\DataSource\DataSourceInterface;
use Zend\View\Model\JsonModel;
use Zend\Json;

class DataTables extends AbstractAdapter
{
    protected $attributes = array();

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        parent::__construct();

        $this->viewModel->setTemplate('dataTable/dataTables');

        $this->options = array(
            'iDisplayLength' => 10,
            'iDeferLoading' => 10,
            'sDom' => '<"top"iflp<"clear">>rt<"bottom"iflp<"clear">>',
            'bProcessing' => 'true',
            'bServerSide' => 'true',
            'sAjaxSource' => '',
            'sServerMethod' => 'POST',
            'fnServerParams' => new Json\Expr('function (aoData) {
                    aoData.push(' . Json\Json::encode(array('id' => $this->id)) . ');
                }')
        );
    }

    /**
     * @return string
     */
    public function render(DataTable $dataTable)
    {
        $optionsEncoded = Json\Json::encode($this->options, false, array(
            'enableJsonExprFinder' => true
        ));

        $this->viewModel->setVariable('options', $this->options);
        $this->viewModel->setVariable('optionsEncoded', $optionsEncoded);
        $this->viewModel->setVariable('columns', $this->getColumnBag());
        $this->viewModel->setVariable('attributes', $this->getAttributes());

        if (null !== $this->getOption('iDeferLoading')) {
            $items = $dataTable->getDataSource()
                ->getItems(0, $this->getOption('iDeferLoading'));
            $items = $this->filterItems($items);
            $this->viewModel->setVariable('items', $items);
        }

        return $this->viewModel;
    }

    /**
     * Returns the JSON response to populate the data table
     *
     * @param  DataSourceInterface $dataSource
     * @return JsonModel
     */
    public function getResponse(DataTable $dataTable)
    {
        $dataSource = $dataTable->getDataSource();

        $offset = $this->parameters['iDisplayStart'];
        $limit = $this->parameters['iDisplayLength'];

        foreach ($this->parameters as $key => $value) {
            if (strpos($key, 'sSearch') === 0 && '' != $value) {
                $this->onFilterEvent($dataSource);
                break;
            }
        }

        // Check if the first sort column is set
        if (isset($this->parameters['iSortCol_0'])) {
            $this->onSortEvent($dataSource);
        }

        $count = count($dataSource);
        $items = $dataSource->getItems($offset, $limit);

        $data = array();
        $data['sEcho'] = (int) $this->parameters['sEcho'];
        $data['iTotalRecords'] = $count;
        $data['iTotalDisplayRecords'] = $count;
        $data['aaData'] = $this->filterItems($items);

        return new JsonModel($data);
    }

    /**
     * @param  array $items
     * @return array
     */
    protected function filterItems($items)
    {
        foreach ($items as &$item) {
            foreach (array_values($item) as $index => $string) {
                $item[$index] = $this->filter($string, $this->columnBag->getOffset($index));
            }
        }

        return $items;
    }

    /**
     * {@inheritdoc}
     */
    protected function onFilterEvent(DataSourceInterface $dataSource)
    {
        // Global search on all columns
        if (isset($this->parameters['sSearch'])) {
            $data = $this->parameters['sSearch'];
            foreach ($this->columnBag->getVisible() as $column) {
                $dataSource->addFilter($column['field'], $data);
                break; // TODO: search on multiple columns with OR clause
            }
        } else {
            // Search per column
            foreach ($this->columnBag->getVisible() as $index => $column) {
                if (isset($this->parameters['sSearch_' . $index])) {
                    $data = $this->parameters['sSearch_' . $index];
                    $dataSource->addFilter($column['field'], $data);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function onSortEvent(DataSourceInterface $dataSource)
    {
        for ($i = 0; $i < count($this->columnBag->getVisible()); $i++) {
            if (isset($this->parameters['iSortCol_' . $i])) {
                $offset = $this->parameters['iSortCol_' . $i];
                $direction = $this->parameters['sSortDir_' . $i];

                $column = $this->columnBag->getOffset($offset);
                $dataSource->addSort($column['field'], $direction);
            }
        }
    }

    /**
     * You can set HTML(5) attributes for the table tag here.
     *
     * @param string $name
     * @param string $value
     */
    public function setAttribute($name, $value)
    {
        $this->attributes[$name] = $value;
    }

    /**
     * Set all attributes at once, this will override the current ones.
     *
     * @param array $params
     */
    public function setAttributes(array $params)
    {
        foreach($params as $name => $value) {
            $this->setAttribute($name, $value);
        }
    }

    /**
     * Retrieve the complete array with attributes including the id.
     *
     * @return array
     */
    public function getAttributes()
    {
        $this->attributes['id'] = $this->getId();

        return $this->attributes;
    }

}
