<?php
/**
 * Trait StarDecoratorTrait
 */
namespace Moro\Platform\Model\Accessory\Parameters\Star;

/**
 * Trait StarDecoratorTrait
 * @package Moro\Platform\Model\Accessory\Parameters\Star
 */
trait StarDecoratorTrait
{
	/**
	 * @param $user
	 * @return bool|null
	 */
	public function hasStar($user)
	{
		return isset($this->_entity) ? $this->_entity->hasStar($user) : [];
	}

	/**
	 * @param $user
	 * @return $this
	 */
	public function addStar($user)
	{
		isset($this->_entity) && $this->_entity->addStar($user);

		return $this;
	}

	/**
	 * @param $user
	 * @return $this
	 */
	public function delStar($user)
	{
		isset($this->_entity) && $this->_entity->delStar($user);

		return $this;
	}
}