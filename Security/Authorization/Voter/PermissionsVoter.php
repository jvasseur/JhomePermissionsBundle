<?php

namespace Jhome\PermissionsBundle\Security\Authorization\Voter;

use Jhome\PermissionsBundle\PermissionsLoader;

use Symfony\Component\Security\Core\Authorization\VoterInterface;

class PermissionsVoter implements VoterInterface
{
    /**
     * @var PermissionsLoader
     */
    private $loader;

    private $permissions;

    public function __construct(PermissionsLoader $loader)
    {
        $this->loader = $loader;
    }

    public function supportClass($class)
    {
        return isset($this->permissions[$class]);
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
                if ($this->evaluate($this->permissions[$class][$attribute], $token) {
                    return VoterInterface::ACCESS_GRANTED;
                } else {
                    return VoterInterface::ACCESS_DENIED;
                }
            }
        }

        return VoterInterface::ACCESS_ABSTAIN;
    }

    private function getPermissions()
    {
        if ($this->permissions === null) {
            $this->permissions = $this->loader->loadPermissions();
        }

        return $this->permissions;
    }
}
