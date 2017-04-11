<?php

namespace TheTribe\PermissionsBundle\Voter;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use TheTribe\PermissionsBundle\Loader\PermissionsLoaderInterface;

class PermissionsVoter implements VoterInterface
{
    /**
     * @var PermissionsLoaderInterface
     */
    private $loader;

    private $expressionVoter;

    private $permissions = [];

    public function __construct(PermissionsLoaderInterface $loader, VoterInterface $expressionVoter)
    {
        $this->loader = $loader;
        $this->expressionVoter = $expressionVoter;
    }

    public function supportsAttribute($attribute)
    {
        return true;
    }

    public function supportsClass($class)
    {
        if ($this->getPermissions($class) !== null) {
            return true;
        }

        $reflection = new \ReflectionClass($class);

        if ($reflection->getParentClass() && $this->supportsClass($reflection->getParentClass()->getName())) {
            return true;
        }

        foreach ($reflection->getInterfaceNames() as $interface) {
            if ($this->supportClass($interface)) {
                return true;
            }
        }

        return false;
    }

    public function vote(TokenInterface $token, $object, array $attributes)
    {
        $vote = self::ACCESS_ABSTAIN;

        foreach ($attributes as $attribute) {
            $voted = $this->voteAttribute($token, $object, $attribute);

            if ($voted === self::ACCESS_GRANTED) {
                return self::ACCESS_GRANTED;
            } elseif ($voted === self::ACCESS_DENIED) {
                $vote = self::ACCESS_DENIED;
            }
        }

        return $vote;
    }

    private function voteAttribute(TokenInterface $token, $object, $attribute)
    {
        if ($permissions = $this->getPermissions(get_class($object))) {
            if (isset($permissions[$attribute])) {
                return $this->expressionVoter->vote($token, $object, [new Expression($permissions[$attribute])]);
            }
        }

        $reflection = new \ReflectionClass(get_class($object));

        $parent = $reflection;
        while ($parent = $parent->getParentClass()) {
            if ($permissions = $this->getPermissions($parent->getName())) {
                if (isset($permissions[$attribute])) {
                    return $this->expressionVoter->vote($token, $object, [new Expression($permissions[$attribute])]);
                }
            }
        }

        foreach ($reflection->getInterfaceNames() as $interface) {
            if ($permissions = $this->getPermissions($interface)) {
                if (isset($permissions[$attribute])) {
                    return $this->expressionVoter->vote($token, $object, [new Expression($permissions[$attribute])]);
                }
            }
        }

        return self::ACCESS_ABSTAIN;
    }

    private function getPermissions($class)
    {
        if (!array_key_exists($class, $this->permissions)) {
            $this->permissions[$class] = $this->loader->loadPermissions($class);
        }

        return $this->permissions[$class];
    }
}
