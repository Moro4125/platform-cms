<?php
/**
 * Trait StarEntityTrait
 */
namespace Moro\Platform\Model\Accessory\Parameters\Star;
use \Moro\Platform\Model\Accessory\Parameters\ParametersInterface;

/**
 * Trait StarEntityTrait
 * @package Moro\Platform\Model\Accessory\Parameters\Star
 */
trait StarEntityTrait
{
	/**
	 * @param $user
	 * @return bool|null
	 */
	public function hasStar($user)
	{
		if ($this instanceof ParametersInterface)
		{
			$parameters = $this->getParameters();
			return isset($parameters['stars']) && in_array($user, $parameters['stars'], true);
		}

		return null;
	}

	/**
	 * @param $user
	 * @return $this
	 */
	public function addStar($user)
	{
		if ($this instanceof ParametersInterface)
		{
			$parameters = $this->getParameters();
			isset($parameters['stars']) || $parameters['stars'] = [];
			in_array($user, $parameters['stars'], true) || ($parameters['stars'][] = $user);
			$this->setParameters($parameters);
		}

		return $this;
	}

	/**
	 * @param $user
	 * @return $this
	 */
	public function delStar($user)
	{
		if ($this instanceof ParametersInterface && $this->hasStar($user))
		{
			$parameters = $this->getParameters();
			$parameters['stars'] = array_diff($parameters['stars'], [$user]);
			$this->setParameters($parameters);
		}

		return $this;
	}
}