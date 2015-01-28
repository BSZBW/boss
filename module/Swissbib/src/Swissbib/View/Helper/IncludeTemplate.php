<?php
namespace Swissbib\View\Helper;

use Zend\View\Helper\AbstractHelper;
use Exception;
use Zend\View\Renderer\PhpRenderer;
use Zend\View\Resolver\AggregateResolver;
use Zend\View\Resolver\TemplateMapResolver;
use Zend\View\Resolver\TemplatePathStack;

class IncludeTemplate extends AbstractHelper {

    /**
     * @param string $templateFile
     * @param string $theme
     *
     * @return string
     */
    public function __invoke($templateFile = '', $theme = '') {
        $filePath =  APPLICATION_PATH . '/themes/' . $theme . '/templates/' . $templateFile . '.phtml';
        if ( !file_exists($filePath) ) return '';

        $phpRenderer    = $this->getView();
        $resolverBackup = $phpRenderer->resolver();
        $resolver       = new AggregateResolver();
        $stack          = new TemplateMapResolver(array($templateFile => $filePath));

        $phpRenderer->setResolver($resolver);
        $resolver->attach($stack)->attach($resolverBackup);

        $renderedTemplate = $phpRenderer->render($templateFile);

        $phpRenderer->setResolver($resolverBackup);

        return $renderedTemplate;
    }

} 