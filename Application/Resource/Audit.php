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
 * @license    MIT
 */

namespace Pike\EntityAudit\Application\Resource;

use Pike\EntityAudit\AuditException,
    Pike\EntityAudit\AuditConfiguration,
    Pike\EntityAudit\AuditManager;

/**
 * PiKe entity audit application resource
 *
 * Add the following lines to your application.ini to enable the auditing of Doctrine entities
 *
 * pluginPaths.Pike_Application_Resource = "Pike/Application/Resource"
 * pluginPaths.Pike\EntityAudit\Application\Resource\ = "Pike/Application/Resource"
 *
 * resources.Audit.TableSuffix = "Audit"
 * resources.Audit.RevisionTableName = "Revision"
 * resources.Audit.RevisionTypeFieldName = "revision_type"
 * resources.Audit.RevisionFieldName = "revision"
 * resources.Audit.RevisionDataType = "new"
 * resources.Audit.auditedEntityClasses[] = "Application\Entity\ExampleOne"
 * resources.Audit.auditedEntityClasses[] = "Application\Entity\ExampleTwo"
 *
 * @category   PiKe
 * @copyright  Copyright (C) 2011 by Pieter Vogelaar (pietervogelaar.nl) and Kees Schepers (keesschepers.nl)
 * @license    MIT
 */
class Audit extends \Zend_Application_Resource_ResourceAbstract
{
    /**
     * Initialize
     *
     * @return AuditConfiguration
     */
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