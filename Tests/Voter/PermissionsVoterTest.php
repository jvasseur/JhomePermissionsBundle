<?php

namespace Jhome\PermissionsBundle\Tests\Voter;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

use Jhome\PermissionsBundle\Loader\ArrayPermissionsLoader;
use Jhome\PermissionsBundle\Voter\PermissionsVoter;

class PermissionsVoterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getVoteAbstainTests
     */
    public function testVoteAbstain(array $permissions, $object, array $attributes)
    {
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');

        $loader = new ArrayPermissionsLoader($permissions);

        $expressionVoter = $this->getMock('Symfony\Component\Security\Core\Authorization\Voter\VoterInterface');
        $expressionVoter->expects($this->never())
             ->method('vote')
        ;

        $voter = new PermissionsVoter($loader, $expressionVoter);
        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $voter->vote($token, $object, $attributes));
    }

    public function getVoteAbstainTests()
    {
        return [
            [[], new \stdClass(), ['edit']],
            [['stdClass' => ['view' => 'true']], new \stdClass(), ['edit']],
        ];
    }

    /**
     * @dataProvider getVoteDecideTests
     */
    public function testVoteDecide(array $permissions, $object, array $attributes, $expression)
    {
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');

        $loader = new ArrayPermissionsLoader($permissions);

        $expressionVoter = $this->getMock('Symfony\Component\Security\Core\Authorization\Voter\VoterInterface');
        $expressionVoter->expects($this->once())
            ->method('vote')
            ->with(
                $this->identicalTo($token),
                $this->identicalTo($object),
                $this->callback(function ($attributes) use ($expression) {return $attributes[0] instanceof Expression && (string) $attributes[0] === $expression;})
            )
            ->will($this->returnValue(VoterInterface::ACCESS_GRANTED))
        ;

        $voter = new PermissionsVoter($loader, $expressionVoter);
	$this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $object, $attributes));
    }

    public function getVoteDecideTests()
    {
        return [
            [['Jhome\PermissionsBundle\Tests\Voter\FixtureObject' => ['view' => 'true']], new FixtureObject(), ['view'], 'true'],
            [['Jhome\PermissionsBundle\Tests\Voter\FixtureObject' => ['view' => 'true']], new FixtureObjectChild(), ['view'], 'true'],
        ];
    }
}
class FixtureObject {}
class FixtureObjectChild extends FixtureObject {}
