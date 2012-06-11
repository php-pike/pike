<?php
/**
 * (c) 2011 SimpleThings GmbH
 *
 * @package SimpleThings\EntityAudit
 * @author Benjamin Eberlei <eberlei@simplethings.de>
 * @link http://www.simplethings.de
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 */

namespace Pike\EntityAudit;

use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManager;
use Pike\EntityAudit\EventListener\CreateSchemaListener;
use Pike\EntityAudit\EventListener\LogRevisionsListener;

/**
 * Audit Manager grants access to metadata and configuration
 * and has a factory method for audit queries.
 *
 * @category   PiKe
 * @copyright  Copyright (C) 2011 by Pieter Vogelaar (pietervogelaar.nl) and Kees Schepers (keesschepers.nl)
 * @license    MIT
 */
class AuditManager
{
    /**
     * @var AuditConfiguration
     */
    private $config;

    /**
     * @var Metadata\MetadataFactory
     */
    private $metadataFactory;

    /**
     * @param AuditConfiguration $config
     */
    public function __construct(AuditConfiguration $config)
    {
        $this->config = $config;
        $this->metadataFactory = $config->createMetadataFactory();
    }

    /**
     * Returns the meta data factory
     *
     * @return Metadata\MetadataFactory
     */
    public function getMetadataFactory()
    {
        return $this->metadataFactory;
    }

    /**
     * Returns the audit configuration
     *
     * @return AuditConfiguration
     */
    public function getConfiguration()
    {
        return $this->config;
    }

    /**
     * Creates and returns an audit reader
     *
     * @param  EntityManager $em
     * @return AuditReader
     */
    public function createAuditReader(EntityManager $em)
    {
        return new AuditReader($em, $this->config, $this->metadataFactory);
    }

    /**
     * Registers Doctrine events
     *
     * @param EventManager $evm
     */
    public function registerEvents(EventManager $evm)
    {
        $evm->addEventSubscriber(new CreateSchemaListener($this));
        $evm->addEventSubscriber(new LogRevisionsListener($this));
    }
}