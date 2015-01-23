<?php

namespace Jhome\PermissionsBundle\Loader;

interface PermissionsLoaderInterface
{
    /**
     * Load permissions for a given class
     *
     * @param string $class the class name
     *
     * @return array<string, string>|null
     */
    public function loadPermissions($class);
}
