<?php
/**
 * Copyright (C) 2011 by Pieter Vogelaar (platinadesigns.nl) and Kees Schepers (keesschepers.nl)
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
 * @copyright  Copyright (C) 2011 by Pieter Vogelaar (platinadesigns.nl) and Kees Schepers (keesschepers.nl)
 * @license    MIT
 */

/**
 * Pike Session SaveHandler for Doctrine. Do you like Doctrine as much as we do? You can
 * use this plugin to make sure your sessions are saved to the database using Doctrine 2 ORM based
 * on entities. In order to make it work correctly follow these steps:
 *
 * 1. Make a entity for your session data table and implement the methods of
 * Pike_Session_Entity_Interface in it.
 *
 * 2. Copy the Pike folder to your library and add Pike as namespace in your application.ini
 * autoloaderNamespaces[] = "Pike"
 *
 * 3. Configure Zend_Application_Resource_Session to make it use this save handler and set some other cool options:
 * resources.session.saveHandler.class = "Pike_Session_SaveHandler_Doctrine"
 * resources.session.saveHandler.options.lifetime = 3600
 * resources.session.saveHandler.options.entityName = "Namespace\To\Your\Entity\Session"
 *
 * And you can enjoy Doctrine even more!
 *
 * @category   PiKe
 * @copyright  Copyright (C) 2011 by Pieter Vogelaar (platinadesigns.nl) and Kees Schepers (keesschepers.nl)
 * @license    MIT
 */
class Pike_Session_SaveHandler_Doctrine implements Zend_Session_SaveHandler_Interface
{
    /**
     * @var Doctrine\ORM\EntityManager
     */
    protected static $em;

    /**
     * @var string
     */
    protected $_entityName = 'session';

    /**
     * Lifetime of your session
     *
     * @var integer
     */
    protected $_lifetime;

    /**
     * Constructor
     *
     * @param Zend_Config|Array $config
     */
    public function __construct($config)
    {
        if ($config instanceof Zend_Config) {
            $config = $config->toArray();
        } else if (!is_array($config)) {
            /**
             * @see Zend_Session_SaveHandler_Exception
             */
            require_once 'Zend/Session/SaveHandler/Exception.php';

            throw new Zend_Session_SaveHandler_Exception(
                '$config must be an instance of Zend_Config or array of key/value pairs containing');
        }

        foreach ($config as $key => $value) {
            switch (strtolower($key)) {
                case 'entityname' :
                    $this->_entityName = $value;
                    break;
            }
        }

        $this->_lifetime = Zend_Session::getOptions('gc_maxlifetime');
    }

    /**
     * Set the EntityManager for Doctrine communication
     *
     * @param Doctrine\ORM\EntityManager $em
     */
    public static function setEntitityManager(Doctrine\ORM\EntityManager $em)
    {
        self::$em = $em;
    }

    /**
     * Returns the Session entity
     *
     * @param  string $id
     * @return Pike_Session_Entity_Interface
     */
    protected function _getEntity($id)
    {
        return self::$em->getRepository($this->_entityName)->find($id);
    }

    public function open($save_path, $name)
    {
        if (!self::$em instanceof Doctrine\ORM\EntityManager) {
            throw new Zend_Session_SaveHandler_Exception('Doctrine EntityMananger must be set');
        }

        return true;
    }

    public function close()
    {
        return true;
    }

    /**
     *
     * Reads the session from the entity. If the session is expired it will be removed
     * and the function will return an empty string (this is very important, otherwise the
     * PHP session handler will fail!).
     *
     * @param  string $id
     * @return string
     */
    public function read($id)
    {
        $return = '';

        $entity = $this->_getEntity($id);

        if ($entity instanceof Pike_Session_Entity_Interface) {
            if ($entity->getModified() instanceof DateTime) {
                $modified = $entity->getModified();
            } else {
                $modified = new DateTime($entity->getModified());
            }

            if ($modified->diff(new DateTime('now'))->s < $this->_lifetime) {
                $return = $entity->getData();
            } else {
                self::$em->remove($entity);
                self::$em->flush();
            }
        }

        return $return;
    }

    /**
     *
     * Writes the session to the entity. If entity doesn't exist it's created.
     *
     * @param  string $id
     * @param  string $data Serialized array data
     * @return boolean
     */
    public function write($id, $data)
    {
        if (null === ($entity = $this->_getEntity($id))) {
            $entity = new $this->_entityName();
            $entity->setId($id);
        }

        $entity->setdata($data);
        $entity->setModified(new DateTime('now'));

        self::$em->persist($entity);
        self::$em->flush();

        return true;
    }

    /**
     *
     * Removes a session entity given by a ID
     *
     * @param  string $id
     * @return boolean
     */
    public function destroy($id)
    {
        $entity = $this->_getEntity($id);

        if ($entity instanceof Pike_Session_Entity_Interface) {
            self::$em->remove($entity);
            self::$em->flush();

            return true;
        }

        return false;
    }

    /**
     * Garbage collector
     *
     * @param type $maxlifetime given by php ini setting gc_maxlifetime
     */
    public function gc($maxlifetime)
    {
        $expired = new DateTime('- ' . $maxlifetime . ' seconds');
        $entityName = $this->_entityName;
        $fieldName = $entityName::getModifiedFieldName();

        $query = self::$em->createQueryBuilder()
            ->delete($this->_entityName, 's')
            ->where(new \Doctrine\ORM\Query\Expr\Comparison('s.' . $fieldName, '<', ':expired'))
            ->setParameter('expired', $expired->format('Y-m-d H:i:s'))
            ->getQuery();

        $query->execute();

        return true;
    }
}