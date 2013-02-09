<?php

namespace Pike\DataTable\Adapter;

use Pike\DataTable;

interface AdapterInterface
{

    /**
     * @param  \Pike\DataTable\Adapter\DataTable $dataTable
     * @return string
     */
    public function render(DataTable $dataTable);

    /**
     * Sets the parameters
     * 
     * @param array $parameters
     */
    public function setParameters(array $parameters);

    /**
     * Response for the data table (probably \Zend\View\Model\JsonModel)
     * 
     * @param  \Pike\DataTable\Adapter\DataTable $dataTable
     * @return mixed
     */
    public function getResponse(DataTable $dataTable);
}