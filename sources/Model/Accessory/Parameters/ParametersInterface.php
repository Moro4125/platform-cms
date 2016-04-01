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
	const IGNORED_COMMENT = 'comment';

	/**
	 * @return array
	 */
	function getParameters();

	/**
	 * @param array $parameters
	 * @return $this
	 */
	function setParameters($parameters);

	/**
	 * @param string $name
	 * @param mixed $default
	 * @return mixed
	 */
	function selectParameter($name, $default = null);
}