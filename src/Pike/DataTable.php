<?php

namespace Pike;

use Pike\DataTable\Adapter\AdapterInterface;
use Pike\DataTable\DataSource\DataSourceInterface;
use Zend\ServiceManager\ServiceManager;
use Zend\View\Model\ViewModel;

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
     * @var \Zend\ServiceManager\ServiceManager
     */
    protected $sm;

    /**
     * Constructor
     * 
     * @param AdapterInterface    $adapter
     * @param DataSourceInterface $dataSource
     * @param ServiceManager      $sm
     */
    public function __construct(AdapterInterface $adapter,
            DataSourceInterface $dataSource, ServiceManager $sm
    ) {
        $this->adapter = $adapter;
        $this->dataSource = $dataSource;
        $this->sm = $sm;

        $columnBag = $this->adapter->getColumnBag();
        foreach ($this->dataSource->getFields() as $field) {
            $columnBag->add($field);
        }
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
     * Returns the service manager
     * 
     * @return ServiceManager
     */
    public function getServiceManager()
    {
        return $this->sm;
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
        $response = $this->adapter->render($this);
        if ($response instanceof ViewModel) {
            $response = $this->sm->get('ViewRenderer')->render($response);
        }

        return $response;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }

}