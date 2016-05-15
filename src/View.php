<?php
/**
 * Created by Vitaly Iegorov <egorov@samsonos.com>.
 * on 22.02.16 at 14:04
 */
namespace samsonphp\view;

use samsonframework\core\SystemInterface;
use samsonphp\resource\Resource;

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
     * @param string $path       Path to resource
     * @param string $parentPath Path to parent entity
     * @param string $controller Url to controller for handling resource serving
     *
     * @return string Url for resource serving
     * TODO: Remove dependency from constant from samsonphp/resource
     * TODO: How to point static resource from one vendor module to another,
     * we use this in SamsonCMS to share template images across modules to avoid
     * static resources duplication. Defining path throw vendor is not an option.
     * Specifying identifier as second parameter to view()?
     * @throws \samsonphp\resource\exception\ResourceNotFound
     */
    public function src($path, $parentPath = '', $controller = STATIC_RESOURCE_HANDLER)
    {
        return $controller.'?p='.Resource::getRelativePath($path, $parentPath);
    }
}
