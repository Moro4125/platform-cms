<?php
/**
 * Trait ParametersDecoratorTrait
 */
namespace Moro\Platform\Model\Accessory\Parameters;

/**
 * Trait ParametersEntityTrait
 * @package Model\Accessory
 */
trait ParametersDecoratorTrait
{
	/**
	 * @return array
	 */
	public function getParameters()
	{
		/** @noinspection PhpUndefinedMethodInspection */
		return isset($this->_entity) ? $this->_entity->getParameters() : [];
	}

	/**
	 * @param array $parameters
	 * @return $this
	 */
	public function setParameters($parameters)
	{
		if (isset($this->_entity))
		{
			/** @noinspection PhpUndefinedMethodInspection */
			$this->_entity->setParameters($parameters);
		}

		return $this;
	}

	/**
	 * @param string $name
	 * @param mixed $default
	 * @return mixed
	 */
	public function selectParameter($name, $default = null)
	{
		/** @noinspection PhpUndefinedMethodInspection */
		return isset($this->_entity) ? $this->_entity->selectParameter($name, $default) : $default;
	}
}