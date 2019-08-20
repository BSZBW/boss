<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Bsz\AjaxHandler;

use Interop\Container\ContainerInterface;
/**
 * Factory for BSZ AjaxHandlers
 *
 * @author amzar
 */
class Factory {
    
    /**
     * 
     * @param ContainerInterface $container
     * @return \Bsz\AjaxHandler\LibrariesTypeahead
     */
    
    public static function getLibrariesTypeahead(ContainerInterface $container)
    {
        return new LibrariesTypeahead(
            $container->get('Bsz\Config\Libraries')
        );
    }
    
    /**
     * 
     * @param ContainerInterface $container
     * @return \Bsz\AjaxHandler\Dedupcheckbox
     */
    
    public static function getDedupCheckbox(ContainerInterface $container)
    {
        return new DedupCheckbox(
            $container->get('Bsz\Config\Dedup')
        );
    }
    /**
     * 
     * @param ContainerInterface $container
     * @return \Bsz\AjaxHandler\SaveIsil
     */
    public static function getSaveIsil(ContainerInterface $container)
    {
        return new SaveIsil(
            $container->get('Bsz\Config\Libraries'),
            new \Zend\Session\Container(
                'fernleihe',
                $container->get(\Zend\Session\SessionManager::class)
            ),
            $container->get('Response'),
            $container->get('Request')->getUri()->getHost()
        );
    }
    
    
}
