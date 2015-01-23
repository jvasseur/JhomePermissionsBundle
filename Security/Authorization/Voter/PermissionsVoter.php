<?php

namespace Jhome\PermissionsBundle\Security\Authorization\Voter;

use Jhome\PermissionsBundle\Loader\PermissionsLoaderInterface;

use Symfony\Component\Security\Core\Authorization\VoterInterface;

class PermissionsVoter implements VoterInterface
{
    /**
     * @var PermissionsLoaderInterface
     */
    private $loader;

    private $permissions = [];

    public function __construct(PermissionsLoaderInterface $loader)
    {
        $this->loader = $loader;
    }

    public function supportClass($class)
    {
        return $this->getPermissions($class) !== null;
    }

    public function supportAttribute($attribute)
    {
        //We can't know if the attribute is supported without knowing the class
        return true;
    }

    public function vote($token, $attributes, $object)
    {
        $class = get_class($object);

        if (!$this->supportClass($class)) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        foreach ($attributes as $attribute) {
            if (isset($this->permissions[$class][$attribute])) {
                if ($this->evaluate($this->getPermissions($class)[$attribute], $token) {
                    return VoterInterface::ACCESS_GRANTED;
                } else {
                    return VoterInterface::ACCESS_DENIED;
                }
            }
        }

        return VoterInterface::ACCESS_ABSTAIN;
    }

    private function getPermissions($class)
    {
        if (!array_key_exists($class, $this->permissions) {
            $this->permissions[$class] = $this->loader->loadPermissions($class);
        }

        return $this->permissions[$class];
    }
}
