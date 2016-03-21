<?php
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>.
 * on 21.02.16 at 14:14
 */
namespace samsonphp\view;

use samsonframework\core\ResourcesInterface;
use samsonframework\core\SystemInterface;
use samsonframework\view\Generator;
use samsonphp\Event\Event;

/**
 * SamsonPHP view module
 * @package samsonphp\view
 */
class Module extends \samson\core\ExternalModule implements \samsonframework\core\CompressInterface
{
    /** View handling events */
    const EVENT_VIEW_HANDLER = 'samsonphp.view.handler';
    const EVENT_VIEW_COMPRESSION = 'samsonphp.view.compression';

    /** Pattern for compressing $this->src() calls with resource path */
    const SRC_COMPRESSION_PATTERN = '/(<\?=|<\?php\s*echo\s*\(?)\s*\$this->src\(\s*(\'|\")\s*(src|www)\/(?<path>[^\'\"]+)(\'|\")\s*\)\s*\?>/';
    /** Pattern for replacing $this->src() calls with controller url */
    const SRC_PATTERN = '/(<\?=|<\?php\s*echo\s*\(?)\s*\$this->src\(\s*(\'|\")(?<path>[^\'\"]+)(\'|\")\s*\)\s*\?>/';

    /** @var string Module identifier */
    protected $id = STATIC_RESOURCE_HANDLER;

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
     * This method should be used to override generic compression logic.
     *
     * @param mixed $obj Pointer to compressor instance
     * @param array|null $code Collection of already compressed code
     *
     * @return bool False if generic compression needs to be avoided
     */
    public function beforeCompress(&$obj = null, array &$code = null)
    {

    }

    /**
     * This method is called after generic compression logic has finished.
     *
     * @param mixed      $obj  Pointer to compressor instance
     * @param array|null $code Collection of already compressed code
     *
     * @return bool False if generic compression needs to be avoided
     */
    public function afterCompress(&$obj = null, array &$code = null)
    {
        $this->generator->generate($this->cache_path, array($this, 'compressionHandler'));
        // Iterate through generated php code
        foreach ($this->generator->metadata as $file => $metadata) {
            // Compress generated php code
            $obj->compress_php($metadata->generatedPath, $this, $code, $metadata->namespace);
        }
    }

    /**
     * Generator view code handler.
     *
     * @param string $viewCode Source view code
     *
     * @return string Modified view code
     */
    public function viewHandler($viewCode)
    {
        // Fire event
        Event::fire(self::EVENT_VIEW_HANDLER, array(&$viewCode));

        // Find all paths to intermediate controller
        if (preg_match_all(self::SRC_PATTERN, $viewCode, $matches)) {
            for ($i = 0, $size = count($matches['path']); $i < $size; $i++) {
                // Remove function call just leave path related to src(for modules) or www(for local)
                $viewCode = str_replace($matches[0][$i], '/' . STATIC_RESOURCE_HANDLER . '/?p=' . $matches['path'][$i], $viewCode);
            }
        }

        // Return modified view code
        return $viewCode;
    }

    /**
     * Compression view code handler.
     *
     * @param string $viewCode Source view code
     *
     * @return string Modified view code
     */
    public function compressionHandler($viewCode)
    {
        // Fire event
        Event::fire(self::EVENT_VIEW_COMPRESSION, array(&$viewCode));

        // Find all pathes to intermediate controller
        if (preg_match_all(self::SRC_COMPRESSION_PATTERN, $viewCode, $matches)) {
            for ($i = 0, $size = count($matches['path']); $i < $size; $i++) {
                // Remove function call just leave path related to src(for modules) or www(for local)
                $viewCode = str_replace($matches[0][$i], $matches['path'][$i], $viewCode);
            }
        }

        // Return modified view code
        return $viewCode;
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
            $this->generator->generate($this->cache_path, array($this, 'viewHandler'));
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
