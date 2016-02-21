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
        $this->generator = $generator;

        parent::__construct($path, $resources, $system);
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
        $signature = md5('sdfsdfsdf');
        if ($this->cache_refresh($signature)) {

        }

        $this->generator = new Generator(new \samsonphp\generator\Generator(), 'view');
        $this->generator->scan(__SAMSON_CWD__.'/src');
        $this->generator->generate($this->cache_path);

        return parent::prepare($params);
    }
}
