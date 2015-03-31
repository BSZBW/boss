<?php
namespace Jusbib;



use Zend\Mvc\MvcEvent;
use VuFind\Config\Reader as ConfigReader;



class Bootstrapper
{

    protected $config;

    protected $event;

    protected $events;
    /**
     * @var \Zend\Mvc\ApplicationInterface
     */
    protected $application;

    /**
     * @var \Zend\ServiceManager\ServiceLocatorInterface
     */
    protected $serviceManager;


    /**
     * @param MvcEvent $event
     */
    public function __construct(MvcEvent $event)
    {
        $this->event  = $event;
    }


    /**
     * Bootstrap
     * Automatically discovers and evokes all class methods with names starting with 'init'
     */
    public function bootstrap()
    {
        $methods = get_class_methods($this);

        foreach ($methods as $method) {
            if (substr($method, 0, 4) == 'init') {
                $this->$method();
            }
        }
    }


    /**
     * Set up plugin managers.
     */
    protected function initPluginManagers()
    {
        $app            = $this->event->getApplication();
        $serviceManager = $app->getServiceManager();
        $config         = $app->getConfig();

        // Use naming conventions to set up a bunch of services based on namespace:
        $namespaces = array(
            'VuFind\Search\Results','VuFind\Search\Options', 'VuFind\Search\Params'
        );

        foreach ($namespaces as $namespace) {
            $plainNamespace	= str_replace('\\', '', $namespace);
            $shortNamespace	= str_replace('VuFind', '', $plainNamespace);
            $configKey		= strtolower(str_replace('\\', '_', $namespace));
            $serviceName	= 'Jusbib\\' . $shortNamespace . 'PluginManager';
            $serviceConfig	= $config['jusbib']['plugin_managers'][$configKey];
            $className		= 'Jusbib\\' . $namespace . '\PluginManager';

            $pluginManagerFactoryService = function ($sm) use ($className, $serviceConfig) {
                return new $className(
                    new \Zend\ServiceManager\Config($serviceConfig)
                );
            };

            $serviceManager->setFactory($serviceName, $pluginManagerFactoryService);
        }
    }

}
