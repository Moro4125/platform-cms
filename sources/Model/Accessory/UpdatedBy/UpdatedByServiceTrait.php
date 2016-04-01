<?php
/**
 * Class UpdatedByServiceTrait
 */
namespace Moro\Platform\Model\Accessory\UpdatedBy;
use \Moro\Platform\Model\AbstractService;
use \Moro\Platform\Model\EntityInterface;

use \Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Class UpdatedByServiceTrait
 * @package Model\Accessory
 */
trait UpdatedByServiceTrait
{
	/**
	 * @var TokenInterface
	 */
	protected $_userToken;

	/**
	 * @return array
	 */
	protected function ___initTraitUpdatedBy()
	{
		if (isset($this->_specials))
		{
			$this->_specials[] = UpdatedByInterface::PROP_CREATED_BY;
			$this->_specials[] = UpdatedByInterface::PROP_UPDATED_BY;
		}

		return [
			AbstractService::STATE_PREPARE_COMMIT => '_prepareSpecialsUpdatedBy',
		];
	}

	/**
	 * @param null|TokenInterface $token
	 * @return $this
	 */
	public function setServiceUser(TokenInterface $token = null)
	{
		assert(empty($this->_userToken));
		$this->_userToken = $token;
		return $this;
	}

	/**
	 * @param EntityInterface $entity
	 * @param bool $insert
	 * @param \ArrayObject $params
	 * @return array
	 */
	protected function _prepareSpecialsUpdatedBy(EntityInterface $entity, $insert, $params)
	{
		$list = [];

		if ($this->_userToken && ($insert || !$entity->hasFlag(EntityInterface::FLAG_SYSTEM_CHANGES)))
		{
			$user = $this->_userToken->getUsername();

			if ($insert && $entity->hasProperty(UpdatedByInterface::PROP_CREATED_BY))
			{
				$key = ':'.UpdatedByInterface::PROP_CREATED_BY;
				$list[UpdatedByInterface::PROP_CREATED_BY] = $key;
				$params[$key] = $user;
			}

			if ($entity->hasProperty(UpdatedByInterface::PROP_UPDATED_BY))
			{
				$key = ':'.UpdatedByInterface::PROP_UPDATED_BY;
				$list[UpdatedByInterface::PROP_UPDATED_BY] = $key;
				$params[$key] = $user;
			}
		}

		unset($entity);
		return $list;
	}
}