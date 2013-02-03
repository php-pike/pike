<?php

namespace Pike;

use Pike\DataTable\Adapter\AdapterInterface;
use Pike\DataTable\DataSource\DataSourceInterface;

class DataTable
{

    /**
     * @var \Pike\DataTable\Adapter\AdapterInterface
     */
    protected $adapter;

    /**
     * @var \Pike\DataTable\DataSource\DataSourceInterface
     */
    protected $dataSource;

    /**
     * Item count per page
     *
     * @var int
     */
    protected $itemCountPerPage = 10;

    /**
     * Current page items
     *
     * @var Traversable
     */
    protected $currentItems = null;

    /**
     * Current page number (starting from 1)
     *
     * @var integer
     */
    protected $currentPageNumber = 1;

    /**
     * Number of pages
     *
     * @var integer
     */
    protected $pageCount = null;

    /**
     * Constructor
     * 
     * @param AdapterInterface    $adapter
     * @param DataSourceInterface $dataSource
     */
    public function __construct(AdapterInterface $adapter,
            DataSourceInterface $dataSource
    ) {
        $this->adapter = $adapter;
        $this->dataSource = $dataSource;
    }

    /**
     * Returns the adapter
     *
     * @return AdapterInterface
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * Returns the data source
     *
     * @return DataSourceInterface
     */
    public function getDataSource()
    {
        return $this->dataSource;
    }

    /**
     * @return mixed
     */
    public function getResponse()
    {
        return $this->adapter->getResponse($this);
    }

    /**
     * @return string
     */
    public function render()
    {
        return $this->adapter->render($this);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }

    /**
     * Returns the total number of items available.
     *
     * @return integer
     */
    public function getTotalItemCount()
    {
        return count($this->adapter);
    }

    /**
     * Returns the number of items per page
     * 
     * @return integer
     */
    public function getItemCountPerPage()
    {
        return $this->itemCountPerPage;
    }

    /**
     * Sets the number of items per page
     * 
     * @return integer
     */
    public function setItemCountPerPage($itemCountPerPage)
    {
        $this->itemCountPerPage = $itemCountPerPage;
    }

    /**
     * Returns the number of items for the current page.
     *
     * @return integer
     */
    public function getCurrentItemCount()
    {
        return count($this->currentItems);
    }

    /**
     * Returns the items for the current page.
     *
     * @return Traversable
     */
    public function getCurrentItems()
    {

        return $this->currentItems;
    }

    /**
     * Sets the current page number.
     *
     * @param  integer   $pageNumber Page number
     * @return Paginator $this
     */
    public function setCurrentPageNumber($pageNumber)
    {
        $this->currentPageNumber = (integer) $pageNumber;
        $this->currentItems = null;

        return $this;
    }

}