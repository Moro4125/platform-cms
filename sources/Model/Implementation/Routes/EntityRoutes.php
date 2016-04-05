<?php
/**
 * Class EntityRoutes
 */
namespace Moro\Platform\Model\Implementation\Routes;


/**
 * Class EntityRoutes
 * @package Model\Routes
 */
class EntityRoutes implements RoutesInterface
{
	use \Moro\Platform\Model\EntityTrait;
	use \Moro\Platform\Model\Accessory\UpdatedBy\UpdatedByEntityTrait;
	use \Moro\Platform\Model\Accessory\Parameters\ParametersEntityTrait;
	use \Moro\Platform\Model\Accessory\Parameters\Tags\TagsEntityTrait;

	/**
	 * @return string
	 */
	public function getRoute()
	{
		return $this->_properties[self::PROP_ROUTE];
	}

	/**
	 * @param string $route
	 * @return $this
	 */
	public function setRoute($route)
	{
		$this->_properties[self::PROP_ROUTE] = $route;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getQuery()
	{
		return ($this->_flags & self::FLAG_DATABASE)
			? json_encode($this->_properties[self::PROP_QUERY], JSON_UNESCAPED_UNICODE)
			: $this->_properties[self::PROP_QUERY];
	}

	/**
	 * @param string $query
	 * @return $this
	 */
	public function setQuery($query)
	{
		assert(($this->_flags & self::FLAG_DATABASE) ? is_string($query) : is_array($query));

		($this->_flags & self::FLAG_DATABASE) && $query = json_decode($query, true);
		$this->_properties[self::PROP_QUERY] = $query;

		return $this;
	}

	/**
	 * @return integer
	 */
	public function getCompileFlag()
	{
		return $this->_properties[self::PROP_COMPILE_FLAG];
	}

	/**
	 * @param integer $flag
	 * @return $this
	 */
	public function setCompileFlag($flag)
	{
		$this->_properties[self::PROP_COMPILE_FLAG] = (int)$flag;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->_properties[self::PROP_TITLE];
	}

	/**
	 * @param string $title
	 * @return $this
	 */
	public function setTitle($title)
	{
		$this->_properties[self::PROP_TITLE] = $title;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getFile()
	{
		return (string)$this->_properties[self::PROP_FILE];
	}

	/**
	 * @param string $file
	 * @return $this
	 */
	public function setFile($file)
	{
		$this->_properties[self::PROP_FILE] = (string)$file;
		return $this;
	}
}