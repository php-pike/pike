<?php

namespace Pike\DataTable\Adapter;

use Pike\DataTable;
use Pike\DataTable\DataSource\DataSourceInterface;
use Zend\View\Model\JsonModel;

class DataTables extends AbstractAdapter
{

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        parent::__construct();

        $this->viewModel->setTemplate('dataTable/dataTables');

        $this->options = array(
            'iDisplayLength' => 10,
            'sDom' => '<"top"iflp<"clear">>rt<"bottom"iflp<"clear">>',
            'bProcessing' => 'true',
            'bServerSide' => 'true',
            'sAjaxSource' => '',
            'sServerMethod' => 'POST'
        );
    }

    /**
     * @return string
     */
    public function render(DataTable $dataTable)
    {
        $this->viewModel->setVariable('id', $this->id);
        $this->viewModel->setVariable('options', $this->options);
        $this->viewModel->setVariable('columns', $this->getColumnBag());

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

        $offset = $this->parameters['iDisplayStart'];
        $limit = $this->parameters['iDisplayLength'];

        $count = count($dataSource);
        $items = $dataSource->getItems($offset, $limit);

        $data = array();
        $data['sEcho'] = (int) $this->parameters['sEcho'];
        $data['iTotalRecords'] = $count;
        $data['iTotalDisplayRecords'] = $count;
        $data['aaData'] = array();

        foreach ($items as $item) {
            $row = array();
            foreach (array_values($item) as $index => $string) {
                $row[] = $this->filter($string, $this->columnBag->getOffset($index));
            }
            $data['aaData'][] = $row;
        }

        return new JsonModel($data);
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

}