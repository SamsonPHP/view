<?php
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>.
 * on 22.02.16 at 14:04
 */
namespace samsonphp\view;

use samsonframework\core\SystemInterface;

/**
 * SamsonPHP View class.
 *
 * @package samsonphp\view
 */
class View extends \samsonframework\view\View
{
    /** @var SystemInterface Pointer to system interface */
    public static $system;

    /**
     * Generate url for resource path that is not accessible by web-server.
     *
     * @param string       $path Path to resource
     * @param string $controller Url to controller for handling resource serving
     * @return string Url for resource serving
     */
    public function src($path, $controller = '/view/')
    {
        return $controller.'?p='.$path;
    }
}
