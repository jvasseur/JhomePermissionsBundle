<?php

namespace Jhome\PermissionsBundle\Loader;

use Symfony\Component\Yaml\Yaml;

class YamlPermissionsLoader implements PermissionsLoaderInterface
{
    private $bundles;

    private $rootDir;

    private $permissions = null;

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
     * {@inheritdoc}
     */
    public function loadPermissions($class)
    {
        if ($this->permissions === null) {
            $this->load();
        }

        return isset($this->permissions[$class]) ? $this->permissions[$class] : null;
    }

    private function load()
    {
        $this->permissions = [];

        foreach ($this->bundles as $bundle => $class) {
            $namespace = substr($class, 0, strrpos($class, '\\') + 1);

            $reflection = new \ReflectionClass($class);
            if (is_file($file = dirname($reflection->getFilename()) . '/Resources/config/permissions.yml')) {
                $this->loadFile($file, $namespace);
            }

            if (is_file($file = $this->rootDir . '/Resources/' . $bundle . '/config/permissions.yml')) {
                $this->loadFile($file, $namespace);
            }
        }

        if (is_file($dir = $this->rootDir . '/Resources/config/permissions.yml')) {
            $this->loadFile($file);
        }
    }

    private function loadFile($file, $namespace = null)
    {
        $data = Yaml::parse(file_get_contents($file));

        foreach ($data as $class => $permissions) {
            if (strpos($class, '\\') !== 0) {
                if ($namespace === null) {
                    throw new \LogicException("Class name must be absolute if defined outside a bundle");
                }

                $class = $namespace . $class;
            } else {
                $class = substr($class, 1);
            }

            $this->permissions[$class] = $permissions;
        }
    }
}
