<?php

namespace Pike\DataTable\Adapter;

use Zend\View\Model\ViewModel;

abstract class AbstractAdapter
{

    /**
     * @var ParameterBag
     */
    protected $parameterBag;

    /**
     * @var ViewModel 
     */
    protected $viewModel;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->parameterBag = new ParameterBag();
        $this->viewModel = new ViewModel();
        $this->viewModel->setTemplate('dataTable/' . $this->getName());
    }

    /**
     * @param array $parameters
     */
    public function setParameters(array $parameters = array())
    {
        $this->parameterBag->set($parameters);
    }

    /**
     * @return string
     */
    abstract public function getName();
}