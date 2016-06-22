<?php
/**
 * Class EntityEraseVoter
 */
namespace Moro\Platform\Security\Voter;
use \Moro\Platform\Model\EntityInterface;
use \Moro\Platform\Model\Accessory\Parameters\Tags\TagsEntityInterface;
use \Moro\Platform\Application;
use \Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use \Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use \Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Class EntityEraseVoter
 * @package Moro\Platform\Security\Voter
 */
class EntityEraseVoter extends Voter
{
	/**
	 * @var Application
	 */
	protected $_application;

	/**
	 * @param Application $application
	 */
	public function __construct(Application $application)
	{
		$this->_application = $application;
	}

	/**
	 * Determines if the attribute and subject are supported by this voter.
	 *
	 * @param string $attribute An attribute
	 * @param mixed  $subject   The subject to secure, e.g. an object the user wants to access or any other PHP type
	 *
	 * @return bool True if the attribute and subject are supported, false otherwise
	 */
	protected function supports($attribute, $subject)
	{
		return $attribute == 'ACTION_ERASE_ENTITY' && $subject instanceof EntityInterface;
	}

	/**
	 * Perform a single access check operation on a given attribute, subject and token.
	 *
	 * @param string          $attribute
	 * @param EntityInterface $subject
	 * @param TokenInterface  $token
	 *
	 * @return bool
	 */
	protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
	{
		/** @var AccessDecisionManagerInterface $decisionManager */
		$decisionManager = $this->_application->offsetGet('security.access_manager');

		if (!$decisionManager->decide($token, ['ROLE_RS_ERASE']))
		{
			return false;
		}

		if (!$subject instanceof TagsEntityInterface)
		{
			return false;
		}

		return $subject->hasTag('флаг: удалено');
	}
}