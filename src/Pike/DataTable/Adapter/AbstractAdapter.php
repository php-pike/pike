<?php

namespace Pike\DataTable\Adapter;

abstract class AbstractAdapter
{

    /**
     * @var ParameterBag
     */
    protected $parameterBag;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->parameterBag = new ParameterBag();
    }

    /**
     * @param array $parameters
     */
    public function setParameters(array $parameters = array())
    {
        $this->parameterBag->set($parameters);
    }

}