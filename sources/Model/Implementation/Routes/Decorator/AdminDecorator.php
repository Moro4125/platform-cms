<?php
/**
 * Class AdminDecorator
 */
namespace Moro\Platform\Model\Implementation\Routes\Decorator;
use \Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use \Moro\Platform\Model\AbstractDecorator;
use \Moro\Platform\Model\Implementation\Routes\RoutesInterface;
use \Exception;

/**
 * Class AdminDecorator
 * @package Model\Routes\Decorator
 */
class AdminDecorator extends AbstractDecorator implements RoutesInterface
{
	use \Moro\Platform\Model\Accessory\Parameters\ParametersDecoratorTrait;
	use \Moro\Platform\Model\Accessory\Parameters\Tags\TagsDecoratorTrait;

	/**
	 * @var string
	 */
	protected static $_adminUrlPrefix;

	/**
	 * @var \Moro\Platform\Model\Implementation\Routes\EntityRoutes;
	 */
	protected $_entity;

	/**
	 * @var string
	 */
	protected $_url;

	/**
	 * @var bool
	 */
	protected $_isInner;

	/**
	 * @param string $route
	 * @param array $parameters
	 * @return string
	 *
	 * @throws Exception
	 */
	protected function _url($route, $parameters)
	{
		try
		{
			/** @var UrlGeneratorInterface $service */
			$service = $this->_application['url_generator'];
			$url = $service->generate($route, $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
		}
		catch (Exception $exception)
		{
			return '/#'.$exception->getMessage();
		}

		return $url;
	}

	/**
	 * @return boolean
	 */
	public function isInner()
	{
		if ($this->_isInner === null)
		{
			$url = $this->_url ?: $this->_url = $this->_url($this->getRoute(), $this->getQuery());
			$flag = false !== strpos($url, '/inner/') && strpos($url, '/inner/') < (strpos($url, '?') ?: strlen($url));
			$this->_isInner = $flag;
		}

		return $this->_isInner;
	}

	/**
	 * @return string
	 */
	public function getUrl()
	{
		$url = $this->_url ?: $this->_url = $this->_url($this->getRoute(), $this->getQuery());
		$prefix = self::$_adminUrlPrefix ?: self::$_adminUrlPrefix = $this->_application->url('admin-prefix');

		return (strpos($url, $prefix) === 0)
			? preg_replace('{^([^/]*//[^/]+/).*?$}', '$1', $url).substr($url, strlen($prefix))
			: $url;
	}

	/**
	 * @return string
	 */
	public function getUri()
	{
		return preg_replace('{^[^/]*//[^/]+(/.*?)$}', '$1', $this->getUrl());
	}

	/**
	 * @return string
	 */
	public function getAdminUrl()
	{
		$url = $this->_url ?: $this->_url = $this->_url($this->getRoute(), $this->getQuery());
		$prefix = self::$_adminUrlPrefix ?: self::$_adminUrlPrefix = $this->_application->url('admin-prefix');

		(strpos($url, $prefix) !== 0) && $url = rtrim($prefix, '/').preg_replace('{^[^/]*//[^/]+/(.*?)$}', '$1', $url);

		return $url;
	}

	/**
	 * @return string
	 */
	public function getAdminUri()
	{
		return preg_replace('{^[^/]*//[^/]+(/.*?)$}', '$1', $this->getAdminUrl());
	}

	/**
	 * @return string
	 */
	public function getRoute()
	{
		return $this->_entity->getRoute();
	}

	/**
	 * @param string $route
	 * @return $this
	 */
	public function setRoute($route)
	{
		$this->_entity->setRoute($route);
		return $this;
	}

	/**
	 * @return string
	 */
	public function getQuery()
	{
		return $this->_entity->getQuery();
	}

	/**
	 * @param string $query
	 * @return $this
	 */
	public function setQuery($query)
	{
		$this->_entity->setQuery($query);
		return $this;
	}

	/**
	 * @return string
	 */
	public function getParameters()
	{
		return $this->_entity->getParameters();
	}

	/**
	 * @param array $parameters
	 * @return $this
	 */
	public function setParameters($parameters)
	{
		$this->_entity->setParameters($parameters);
		return $this;
	}

	/**
	 * @return integer
	 */
	public function getCompileFlag()
	{
		return $this->_entity->getCompileFlag();
	}

	/**
	 * @param integer $flag
	 * @return $this
	 */
	public function setCompileFlag($flag)
	{
		$this->_entity->setCompileFlag($flag);
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->_entity->getTitle();
	}

	/**
	 * @param string $title
	 * @return $this
	 */
	public function setTitle($title)
	{
		$this->_entity->setTitle($title);
		return $this;
	}

	/**
	 * @return string
	 */
	public function getFile()
	{
		return $this->_entity->getFile();
	}

	/**
	 * @param string $file
	 * @return $this
	 */
	public function setFile($file)
	{
		$this->_entity->setFile($file);
		return $this;
	}
}