<?php

namespace Pike;

use Zend\ServiceManager\ServiceLocatorInterface;

return array(
    'initializers' => array(),
    'factories' => array(
        'Pike\DataTable\DataSource\Doctrine' => function (ServiceLocatorInterface $serviceLocator) {
            /* @var $em \Doctrine\ORM\EntityManager */
            $em = $serviceLocator->get('zfcuser_user_service')->get('doctrine.entitymanager.orm_default');

            return new DataTable\DataSource\Doctrine($em);
        }
    ),
);