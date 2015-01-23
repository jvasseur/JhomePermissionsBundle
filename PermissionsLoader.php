<?php

namespace Jhome\PermissionsBundle;

use Symfony\Component\Yaml\Yaml;

class PermissionsLoader
{
    private $bundles;

    private $rootDir;

    /**
     * @param array<string, string> $bundles
     * @param array                 $rootDir
     */
    public function __construct(array $bundles, $rootDir)
    {
        $this->bundles = $bundles;
        $this->rootDir = $rootDir;
    }

    /**
     * Load permissions
     *
     * @return array<string, array<string, string>>
     */
    public function loadPermissions()
    {
        $permissions = [];

        foreach ($this->bundles as $bundle => $class) {
            $namespace = substr($class, 0, strrpos($class, '\\'));

            $reflection = new \ReflectionClass($class);
            if (is_file($file = dirname($reflection->getFilename()) . '/Resources/config/permissions.yml')) {
                $permissions = array_merge($permissions, $this->loadFile($file, $namespace));
            }

            if (is_file($file = $this->rootDir . '/Resources/' . $bundle . '/config/permissions.yml') {
                $permissions = array_merge($permissions, $this->loadFile($file, $namespace));
            }
        }

        if (is_file($dir = $this->rootDir . '/Resources/config/permissions.yml')) {
            $permissions = array_merge($permissions, $this->loadFile($file));
        }
    }

    private function loadFile($file, $namespace = null)
    {
        $permissions = [];

        $data = Yaml::parse(file_get_contents($file));

        foreach ($data as $class => $permission) {
            if (strpos($class, '\\') !== 0) {
                if ($namespace === null) {
                    throw new \LogicException("Class name must be absolute if defined outside a bundle");
                }

                $class = $baseNamespace . $class;
            }

            $permissions[$class] = $permission;
        }

        return $permissions;
    }
}
