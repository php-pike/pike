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
 *
 * @category   PiKe
 * @copyright  Copyright (C) 2011 by Pieter Vogelaar (pietervogelaar.nl) and Kees Schepers (keesschepers.nl)
 * @author     Kees Schepers <kees@keesschepers.nl>
 * @license    MIT
 */

namespace Pike\DataTable\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Pike\DataTable;

abstract class AbstractDataTableService implements FactoryInterface
{

    /**
     * Zend Framework combined module configuration.
     *
     * @var array
     */
    protected $config;

    /**
     * The service locator.
     *
     * @var ServiceLocatorInterface
     */
    private $serviceLocator;

    /**
     * Must return the name as a string. This is important to make it possible
     * to configure the datatable in the configuration and can be referenced by
     * this name.
     *
     * @return string
     */
    abstract public function getName();

    /**
     * @param ServiceLocatorInterface|ServiceManager $serviceLocator
     * @return DataTable
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $this->config = $serviceLocator->get('config');
        $this->serviceLocator = $serviceLocator;

        return new DataTable($this->getAdapter(), $this->getDataSource(), $serviceLocator);
    }

    /**
     * Verifies and returns the configuration.
     *
     * @return array
     *
     * @throws \RuntimeException
     */
    protected function getConfig()
    {
        if (false === isset($this->config['pike']['datatable'])) {
            throw new \RuntimeException('No [pike][datatable] section configured');
        }

        if (false === isset($this->config['pike']['datatable'][$this->getName()])) {
            throw new \RuntimeException(sprinf('No datatable configuration found for %s', $this->getName()));
        }

        return $this->config['pike']['datatable'][$this->getName()];
    }

    /**
     * Try to obtain the DataTable adapter by configuration. You can override this
     * method to make your own adapter.
     *
     * @return AdapterInterface
     *
     * @throws \RuntimeException
     */
    protected function getAdapter()
    {
        $configuration = $this->getConfig();

        if (false === isset($configuration['adapter'])) {
            throw new \RuntimeException(sprintf('No adapter configured for %s', $this->getName()));
        }

        $strategy = $configuration['adapter'];

        if (false === class_exists($strategy, true)) {
            throw new \RuntimeException(sprintf('%s is not a loadable class', $strategy));
        }

        $adapter = new $strategy();
        $adapter->setParameters($this->getServiceLocator()->get('request')->getQuery()->toArray());

        if (false === $adapter instanceof Pike\DataTable\Adapter\AdapterInterface) {
            throw new \RuntimeException(sprintf('%s is not a valid adapter', $strategy));
        }

        return $adapter;
    }

    /**
     * Try to obtain the datasource by configuration. Override this method
     * to make your own datasource.
     *
     * @return DataSourceInterface
     *
     * @throws \RuntimeException
     */
    protected function getDataSource()
    {
        $configuration = $this->getConfig();

        if (false === isset($configuration['datasource'])) {
            throw new \RuntimeException(sprintf('datasource should be configured for %s', $this->getName()));
        }

        if (false === isset($configuration['datasource_callback'])) {
            throw new \RuntimeException(sprintf('datasource should be configured for %s', $this->getName()));
        }

        $strategy = $configuration['datasource'];

        if (false === class_exists($strategy, true)) {
            throw new \RuntimeException(sprintf('%s is not a loadable class', $strategy));
        }

        $dataSource = new $strategy($configuration['datasource_callback']($this->getServiceLocator()));

        if (false === $dataSource instanceof Pike\DataTable\DataSource\DataSourceInterface) {
            throw new \RuntimeException(sprintf('%s is not a valid datasource', $strategy));
        }

        return $dataSource;
    }

    /**
     * Return the Zend Service Locator
     *
     * @return ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }
}