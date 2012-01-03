<?php

namespace Pike\EntityAudit\Application\Resource;

use Pike\EntityAudit\AuditException,
    Pike\EntityAudit\AuditConfiguration,
    Pike\EntityAudit\AuditManager;

class Audit extends \Zend_Application_Resource_ResourceAbstract
{

    public function init()
    {
        $bootstrap = $this->getBootstrap()->getApplication()->getBootstrap();
        $options = $this->getOptions();

        if (!isset($options['auditedEntityClasses'])) {
            throw new AuditException('auditedEntityClasses isn\'t is a neccasary configuration option');
        }

        if (!$bootstrap->hasPluginResource('doctrine')) {
            throw new AuditException('Doctrine should be a registered resource!');
        }

        $bootstrap->bootstrap('doctrine');

        $doctrineContainer = $bootstrap->getResource('doctrine');
        $entityManager = $doctrineContainer->getEntityManager();

        $auditConfig = new AuditConfiguration();

        foreach ($options as $key => $value) {
            switch (strtolower($key)) {
                case 'auditedentityclasses' :
                    if (is_array($value)) {
                        $auditConfig->setAuditedEntityClasses($value);
                    } else {
                        throw new AuditException($key . ' should be an array with classnames');
                    }
                    break;
                default :
                    if (method_exists($auditConfig, 'set' . $key)) {
                        $auditConfig->{'set' . $key}($value);
                    } else {
                        throw new AuditException('Unknown configuration option "' . $key . '"');
                    }
                    break;
            }
        }

        /**
         * Set the current user if registered. If the identity is something else
         * then a string it's unpredictable where the username is stored.
         */
        $auth = \Zend_Auth::getInstance();

        if ($auth->hasIdentity()) {
            if (is_string($auth->getIdentity())) {
                $auditConfig->setCurrentUsername($auth->getIdentity());
            } elseif (is_object($auth->getIdentity()) && method_exists($auth->getIdentity(), '__toString')) {
                $auditConfig->setCurrentUsername((string)$auth->getIdentity());
            }
        }


        $auditManager = new AuditManager($auditConfig);
        $auditManager->registerEvents($entityManager->getEventManager());

        return $auditConfig;
    }
}
