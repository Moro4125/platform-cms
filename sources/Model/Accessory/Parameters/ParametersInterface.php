<?php
/**
 * Interface ParametersInterface
 */
namespace Moro\Platform\Model\Accessory\Parameters;

/**
 * Interface ParametersInterface
 * @package Model\Accessory
 */
interface ParametersInterface
{
	const PROP_PARAMETERS = 'parameters';

	/**
	 * @return array
	 */
	function getParameters();

	/**
	 * @param array $parameters
	 * @return $this
	 */
	function setParameters($parameters);
}