<?php
/**
 * Interface RoutesInterface
 */
namespace Moro\Platform\Model\Implementation\Routes;
use \Moro\Platform\Model\EntityInterface;
use \Moro\Platform\Model\Accessory\Parameters\ParametersInterface;
use \Moro\Platform\Model\Accessory\Parameters\Tags\TagsEntityInterface;

/**
 * Interface RoutesInterface
 * @package Model\Routes
 */
interface RoutesInterface extends EntityInterface, ParametersInterface, TagsEntityInterface
{
	const PROP_ROUTE        = 'route';
	const PROP_QUERY        = 'query';
	const PROP_COMPILE_FLAG = 'compile_flag';
	const PROP_TITLE        = 'title';
	const PROP_FILE         = 'file';

	const DEFAULT_COMPILE_FLAG = false;

	//const FREE_CREATED_AT = EntityInterface::PROP_CREATED_AT;
	//const FREE_UPDATED_AT = EntityInterface::PROP_UPDATED_AT;

	/**
	 * @return string
	 */
	function getRoute();

	/**
	 * @param string $route
	 * @return $this
	 */
	function setRoute($route);

	/**
	 * @return string
	 */
	function getQuery();

	/**
	 * @param string $query
	 * @return $this
	 */
	function setQuery($query);

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
	 * @return integer
	 */
	function getCompileFlag();

	/**
	 * @param integer $flag
	 * @return $this
	 */
	function setCompileFlag($flag);

	/**
	 * @return string
	 */
	function getTitle();

	/**
	 * @param string $title
	 * @return $this
	 */
	function setTitle($title);

	/**
	 * @return string
	 */
	function getFile();

	/**
	 * @param string $file
	 * @return $this
	 */
	function setFile($file);
}