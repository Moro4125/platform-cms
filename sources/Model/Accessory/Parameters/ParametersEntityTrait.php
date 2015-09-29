<?php
/**
 * Trait ParametersEntityTrait
 */
namespace Moro\Platform\Model\Accessory\Parameters;

use \Moro\Platform\Model\EntityInterface;

/**
 * Trait ParametersEntityTrait
 * @package Model\Accessory
 */
trait ParametersEntityTrait
{
	/**
	 * @return array
	 */
	public function getParameters()
	{
		$options = [];

		if (isset($this->_properties) && !empty($this->_properties[ParametersInterface::PROP_PARAMETERS]))
		{
			$options = $this->_properties[ParametersInterface::PROP_PARAMETERS];
		}

		if (isset($this->_flags) && ($this->_flags & EntityInterface::FLAG_DATABASE))
		{
			$options = json_encode($options, JSON_UNESCAPED_UNICODE);
		}

		return $options;
	}

	/**
	 * @param array $parameters
	 * @return $this
	 */
	public function setParameters($parameters)
	{
		if (isset($this->_flags) && ($this->_flags & EntityInterface::FLAG_DATABASE) && is_string($parameters))
		{
			$parameters = json_decode($parameters, true);
		}

		if (isset($this->_properties))
		{
			$this->_properties[ParametersInterface::PROP_PARAMETERS] = (array)$parameters;
		}

		return $this;
	}
}