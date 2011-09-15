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
 *
 * When you use various libraries for adding vendor specific functions to your
 * Doctrine2 ORM this resource helps you to combine dees functions and
 * adds them to the configuration of Doctrin2
 * 
 *
 * @category   PiKe
 * @copyright  Copyright (C) 2011 by Pieter Vogelaar (platinadesigns.nl) and Kees Schepers (keesschepers.nl)
 * @license    MIT
 */
class Pike_Application_Resource_DoctrineFunctions extends Zend_Application_Resource_ResourceAbstract {

    public function init() {
        $this->getBootstrap()->bootstrap('doctrine');
        
        $doctrine = $this->getBootstrap()->getResource('doctrine'); //bisna doctrine container        
        $em = $doctrine->getEntityManager();
        $config = $em->getConfiguration();        
        $options = $this->getOptions();
        $applicationConfig = $this->getBootstrap()->getApplication()->getOptions();
        $libraryPath = realpath($applicationConfig['includePaths']['library']);

        foreach ($options['path'] as $prefix => $path) {
            $files = new DirectoryIterator($path);
            
            foreach ($files as $file) {
                /* @var $file DirectoryIterator */
                if (!$file->isDot() && $file->isFile()) {
                    require_once $file->getPathname();

                    $className = str_replace($libraryPath . '/', '', realpath($file->getPathname()));
                    $className = substr($className, 0, strpos($className, '.php'));

                    $function = $file->getBasename('.php');
                    
                    /**
                     * Support underscore (_) prefixed classnames (ZF1.x style) and Doctrine
                     * namespace classnames.
                     */
                    if (class_exists(str_replace('/', '\\', $className))) {
                        $className = str_replace('/', '\\', $className);
                        $classReflection = new Zend_Reflection_Class(str_replace('/', '\\', $className));
                        
                        if($classReflection->isSubclassOf('Doctrine\ORM\Query\AST\Functions\FunctionNode')) {                            
                            $config->addCustomStringFunction(strtoupper($function), $className);
                        }
                    } elseif(class_exists(str_replace('/', '_', $className))) {
                        $className = str_replace('/', '_', $className);
                        $classReflection = new Zend_Reflection_Class(str_replace('/', '_', $className));
                        
                        if($classReflection->isSubclassOf('Doctrine\ORM\Query\AST\Functions\FunctionNode')) {
                            $config->addCustomStringFunction(strtoupper($function), $className);
                        }
                    }
                }
            }
        }
    }

}
