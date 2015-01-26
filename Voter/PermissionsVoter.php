<?php

namespace Jhome\PermissionsBundle\Voter;

use Jhome\PermissionsBundle\Loader\PermissionsLoaderInterface;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

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
        true;
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
        return $this->doVote(get_class($object), $token, $object, $attributes);
    }

    private function doVote($class, TokenInterface $token, $object, array $attributes)
    {
        $denied = false;

        if ($permissions = $this->getPermissions($class)) {
            foreach ($attributes as $attribute) {
                if (isset($permissions[$attribute])) {
                    $vote = $this->expressionVoter->vote($token, $object, [new Expression($permissions[$attribute])]);

                    if ($vote === VoterInterface::ACCESS_GRANTED) {
                        return VoterInterface::ACCESS_GRANTED;
                    } else {
                        return VoterInterface::ACCESS_DENIED;
                    }
                }
            }
        }

        $reflection = new \ReflectionClass($class);

        if ($reflection->getParentClass()) {
            $vote = $this->doVote($reflection->getParentClass()->getName(), $token, $object, $attributes);

            if ($vote === VoterInterface::ACCESS_GRANTED) {
                return VoterInterface::ACCESS_GRANTED;
            } elseif ($vote === VoterInterface::ACCESS_DENIED) {
                $denied = true;
            }
        }

        foreach ($reflection->getInterfaceNames() as $interface) {
            $vote = $this->doVote($interface, $token, $object, $attributes);

            if ($vote === VoterInterface::ACCESS_GRANTED) {
                return VoterInterface::ACCESS_GRANTED;
            } elseif ($vote === VoterInterface::ACCESS_DENIED) {
                $denied = true;
            }
        }

        if ($denied) {
            return VoterInterface::ACESS_DENIED;
        } else {
            return VoterInterface::ACCESS_ABSTAIN;
        }
    }

    private function getPermissions($class)
    {
        if (!array_key_exists($class, $this->permissions)) {
            $this->permissions[$class] = $this->loader->loadPermissions($class);
        }

        return $this->permissions[$class];
    }
}
