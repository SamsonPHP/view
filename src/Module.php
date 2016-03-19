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
class Module extends \samson\core\ExternalModule implements \samsonframework\core\CompressInterface
{
    /** @var Generator */
    protected $generator;

    /**
     * This method should be used to override generic compression logic.
     *
     * @param mixed $obj Pointer to compressor instance
     * @param array|null $code Collection of already compressed code
     * @return bool False if generic compression needs to be avoided
     */
    public function beforeCompress(&$obj = null, array &$code = null)
    {

    }

    /**
     * This method is called after generic compression logic has finished.
     *
     * @param mixed $obj Pointer to compressor instance
     * @param array|null $code Collection of already compressed code
     * @return bool False if generic compression needs to be avoided
     */
    public function afterCompress(&$obj = null, array &$code = null)
    {
        // Iterate through generated php code
        foreach ($this->generator->metadata as $metadata) {
            // Compress generated php code
            $obj->compress_php($metadata->path, $this, $code, $metadata->namespace);
        }
    }

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
            : new Generator(
                new \samsonphp\generator\Generator(),
                'view',
                array('\www', '\view'),
                View::class
            );

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
        //$this->generator->scan(__SAMSON_CWD__.'/app');
        $signature = $this->generator->hash();
        if ($this->cache_refresh($signature)) {
            $this->generator->generate($this->cache_path);
            // Store cache file
            file_put_contents($signature, '');
        }

        // Add system static variable to all classes
        require_once 'View.php';
        View::$system = &$this->system;

        // Continue parent logic
        return parent::prepare($params);
    }
}
