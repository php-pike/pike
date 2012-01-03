<?php
/*
 * (c) 2011 SimpleThings GmbH
 *
 * @package SimpleThings\EntityAudit
 * @author Benjamin Eberlei <eberlei@simplethings.de>
 * @author Kees Schepers <kees@stichtingsbo.nl>
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

namespace Pike\EntityAudit\EventListener;

use Pike\EntityAudit\AuditManager;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;

class LogRevisionsListener implements EventSubscriber
{
    /**
     * @var \SimpleThings\EntityAudit\AuditConfiguration
     */
    private $config;

    /**
     * @var \SimpleThings\EntityAudit\Metadata\MetadataFactory
     */
    private $metadataFactory;

    /**
     * @var Doctrine\DBAL\Connection
     */
    private $conn;

    /**
     * @var Doctrine\DBAL\Platforms\AbstractPlatform
     */
    private $platform;

    /**
     * @var Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var array
     */
    private $insertRevisionSQL = array();

    /**
     * @var Doctrine\ORM\UnitOfWork
     */
    private $uow;

    /**
     * @var int
     */
    private $revisionId;

    private $originalEntityData = array();


    public function __construct(AuditManager $auditManager)
    {
        $this->config = $auditManager->getConfiguration();
        $this->metadataFactory = $auditManager->getMetadataFactory();
    }

    public function getSubscribedEvents()
    {
        return array(Events::onFlush, Events::postPersist, Events::postUpdate, Events::postLoad);
    }

    /**
     * This is a workaround because $uow->getOriginalEntityData doesn't seem to
     * return the old data of an entity which causes the new data being saved as revision.
     *
     * @link http://github.com/simplethings/EntityAudit/issues/2
     * @param LifecycleEventArgs $eventArgs
     * @author Kees Schepers <kees@stichtingsbo.nl>
     * @return void
     */
    public function postLoad(LifecycleEventArgs $eventArgs) {
        $entity = $eventArgs->getEntity();
        $em = $eventArgs->getEntityManager();
        $uow = $em->getUnitOfWork();
        $oid = spl_object_hash($entity);

        $class = $eventArgs->getEntityManager()->getClassMetadata(get_class($entity));

        if (!$this->metadataFactory->isAudited($class->name)) {
            return;
        }

        $this->originalEntityData[$oid] = $uow->getOriginalEntityData($entity);
    }

    /**
     * Add the new added entity to the database as a revision.
     *
     * @param LifecycleEventArgs $eventArgs
     * @return void
     */
    public function postPersist(LifecycleEventArgs $eventArgs)
    {
        // onFlush was executed before, everything already initialized
        $entity = $eventArgs->getEntity();
        $oid = spl_object_hash($entity);

        $class = $this->em->getClassMetadata(get_class($entity));
        if (!$this->metadataFactory->isAudited($class->name)) {
            return;
        }

        $entityData = $this->uow->getOriginalEntityData($entity);

        $this->saveRevisionEntityData($class, $entityData, 'INS');
    }

    /**
     *
     * Add the old data to the corresponding audit table
     *
     * @param LifecycleEventArgs $eventArgs
     * @return void
     */
    public function postUpdate(LifecycleEventArgs $eventArgs)
    {
        // onFlush was executed before, everything already initialized
        $entity = $eventArgs->getEntity();
        $oid = spl_object_hash($entity);
        $class = $this->em->getClassMetadata(get_class($entity));
        if (!$this->metadataFactory->isAudited($class->name)) {
            return;
        }

        switch($this->config->getRevisionDataType()) {
            case 'new' :
                $entityData = array_merge(
                    $this->uow->getOriginalEntityData($entity), $this->uow->getEntityIdentifier($entity)
                ); //looks wrong but isn't.
                break;
            case 'old' :
                $entityData = array_merge($this->originalEntityData[$oid], $this->uow->getEntityIdentifier($entity));
                break;
        }

        $this->saveRevisionEntityData($class, $entityData, 'UPD');
    }

    /**
     *
     * Handles d
     *
     * @param OnFlushEventArgs $eventArgs
     * @return void
     */
    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $this->em = $eventArgs->getEntityManager();
        $this->conn = $this->em->getConnection();
        $this->uow = $this->em->getUnitOfWork();
        $this->platform = $this->conn->getDatabasePlatform();
        $this->revisionId = null; // reset revision

        foreach ($this->uow->getScheduledEntityDeletions() AS $entity) {
            $class = $this->em->getClassMetadata(get_class($entity));
            if (!$this->metadataFactory->isAudited($class->name)) {
                return;
            }
            $entityData = array_merge($this->uow->getOriginalEntityData($entity), $this->uow->getEntityIdentifier($entity));
            $this->saveRevisionEntityData($class, $entityData, 'DEL');
        }
    }

    private function getRevisionId()
    {
        if ($this->revisionId === null) {
            $date = date_create("now")->format($this->platform->getDateTimeFormatString());
            $this->conn->insert($this->config->getRevisionTableName(), array(
                'timestamp'     => $date,
                'username'      => $this->config->getCurrentUsername(),
            ));
            $this->revisionId = $this->conn->lastInsertId();
        }
        return $this->revisionId;
    }

    private function getInsertRevisionSQL($class)
    {
        if (!isset($this->insertRevisionSQL[$class->name])) {
            $tableName = $this->config->getTablePrefix() . $class->table['name'] . $this->config->getTableSuffix();
            $sql = "INSERT INTO " . $tableName . " (" .
                    $this->config->getRevisionFieldName() . ", " . $this->config->getRevisionTypeFieldName();
            foreach ($class->fieldNames AS $field) {
                $sql .= ', ' . $class->getQuotedColumnName($field, $this->platform);
            }
            $assocs = 0;
            foreach ($class->associationMappings AS $assoc) {
                if ( ($assoc['type'] & ClassMetadata::TO_ONE) > 0 && $assoc['isOwningSide']) {
                    foreach ($assoc['targetToSourceKeyColumns'] as $sourceCol) {
                        $sql .= ', ' . $sourceCol;
                        $assocs++;
                    }
                }
            }
            $sql .= ") VALUES (" . implode(", ", array_fill(0, count($class->fieldNames)+$assocs+2, '?')) . ")";
            $this->insertRevisionSQL[$class->name] = $sql;
        }
        return $this->insertRevisionSQL[$class->name];
    }

    /**
     * @param ClassMetadata $class
     * @param array $entityData
     * @param string $revType
     */
    private function saveRevisionEntityData($class, $entityData, $revType)
    {
        $params = array($this->getRevisionId(), $revType);
        $types = array(\PDO::PARAM_INT, \PDO::PARAM_STR);
        foreach ($class->fieldNames AS $field) {
            $params[] = $entityData[$field];
            $types[] = $class->fieldMappings[$field]['type'];
        }
        foreach ($class->associationMappings AS $field => $assoc) {
            if (($assoc['type'] & ClassMetadata::TO_ONE) > 0 && $assoc['isOwningSide']) {
                $targetClass = $this->em->getClassMetadata($assoc['targetEntity']);

                if ($entityData[$field] !== null) {
                    $relatedId = $this->uow->getEntityIdentifier($entityData[$field]);
                }

                $targetClass = $this->em->getClassMetadata($assoc['targetEntity']);

                foreach ($assoc['sourceToTargetKeyColumns'] as $sourceColumn => $targetColumn) {
                    if ($entityData[$field] === null) {
                        $params[] = null;
                        $types[] = \PDO::PARAM_STR;
                    } else {
                        $params[] = $relatedId[$targetClass->fieldNames[$targetColumn]];
                        $types[] = $targetClass->getTypeOfColumn($targetColumn);
                    }
                }
            }
        }

        $this->conn->executeUpdate($this->getInsertRevisionSQL($class), $params, $types);
    }
}
