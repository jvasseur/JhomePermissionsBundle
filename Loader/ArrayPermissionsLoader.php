<?php

namespace Jhome\PermissionsBundle\Loader;

class ArrayPermissionsLoader implements PermissionsLoaderInterface
{
    private $permissions = null;

    /**
     * @param array<string, array<string, string>> $bundles
     */
    public function __construct(array $permissions)
    {
        $this->permissions = $permissions;
    }

    /**
     * {@inheritdoc}
     */
    public function loadPermissions($class)
    {
        return isset($this->permissions[$class]) ? $this->permissions[$class] : null;
    }
}
