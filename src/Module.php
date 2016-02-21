<?php
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>.
 * on 21.02.16 at 14:14
 */
namespace samsonphp\view;

use samsonframework\core\ResourcesInterface;
use samsonframework\core\SystemInterface;
use samsonframework\view\Generator;

/**
 * SamsonPHP view module
 * @package samsonphp\view
 */
class Module extends \samson\core\ExternalModule
{
    /** @var Generator */
    protected $generator;

    /**
     * Module constructor.
     *
     * @param string             $path
     * @param ResourcesInterface $resources
     * @param SystemInterface    $system
     * @param Generator          $generator
     */
    public function __construct($path, ResourcesInterface $resources, SystemInterface $system, Generator $generator = null)
    {
        parent::__construct($path, $resources, $system);

        $this->generator = isset($generator)
            ? $generator
            : new Generator(new \samsonphp\generator\Generator(), 'view', array('\www', '\view'));

        // Register View class file autoloader
        spl_autoload_register(array($this, 'autoload'));
    }

    /**
     * Help autoloading view classes as we know where we store them.
     *
     * @param string $class View class name for searching
     */
    public function autoload($class)
    {
        $classPath = $this->cache_path.str_replace('\\', '/', $class).'.php';
        if (file_exists($classPath)) {
            require_once($classPath);
        }
    }

    /**
     * Module preparation stage.
     * This function called after module instance creation but before
     * initialization stage.
     *
     * @param array $params Preparation stage parameters
     *
     * @return bool|void Preparation stage result
     */
    public function prepare(array $params = array())
    {
        $this->generator->scan(__SAMSON_CWD__.'/src');
        $signature = $this->generator->hash();
        if ($this->cache_refresh($signature)) {
            $this->generator->generate($this->cache_path);
            // Store cache file
            file_put_contents($signature, '');
        }

        return parent::prepare($params);
    }
}
