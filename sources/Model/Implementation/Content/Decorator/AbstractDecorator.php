<?php
/**
 * Class AbstractDecorator
 */
namespace Moro\Platform\Model\Implementation\Content\Decorator;
use \Moro\Platform\Model\AbstractDecorator as CDecorator;
use \Moro\Platform\Model\Implementation\Content\ContentInterface;

/**
 * Class AbstractDecorator
 * @package Model\Implementation\Content\Decorator
 */
class AbstractDecorator extends CDecorator implements ContentInterface
{
	use \Moro\Platform\Model\Accessory\UpdatedBy\UpdatedByDecoratorTrait;
	use \Moro\Platform\Model\Accessory\OrderAt\OrderAtDecoratorTrait;
	use \Moro\Platform\Model\Accessory\Parameters\ParametersDecoratorTrait;
	use \Moro\Platform\Model\Accessory\Parameters\Tags\TagsDecoratorTrait;

	/**
	 * @var \Moro\Platform\Model\Implementation\Content\ContentInterface
	 */
	protected $_entity;

	/**
	 * @var array
	 */
	protected $_urlParameters;

	/**
	 * @var string
	 */
	protected $_url;

	/**
	 * @var string
	 */
	protected $_heading;

	/**
	 * @var string
	 */
	protected $_headingName;

	/**
	 * @var array
	 */
	protected static $_cacheHeading = [];

	/**
	 * @return string
	 */
	public function getCode()
	{
		return $this->_entity->getCode();
	}

	/**
	 * @param string $code
	 * @return $this
	 */
	public function setCode($code)
	{
		$this->_entity->setCode($code);
		return $this;
	}

	/**
	 * @return string|null
	 */
	public function getName()
	{
		return $this->_entity->getName();
	}

	/**
	 * @param string|null $name
	 * @return $this
	 */
	public function setName($name)
	{
		$this->_entity->setName($name);
		return $this;
	}

	/**
	 * @return string
	 */
	public function getIcon()
	{
		return $this->_entity->getIcon();
	}

	/**
	 * @param string $hash
	 * @return $this
	 */
	public function setIcon($hash)
	{
		$this->_entity->setIcon($hash);
		return $this;
	}

	/**
	 * @return string
	 */
	public function getHeading()
	{
		if ($this->_heading === null)
		{
			$this->_heading = '';
			$prefix = 'раздел:';
			$length = strlen($prefix);

			foreach ($this->getTags() as $tag)
			{
				if (strncmp($normalizedTag = normalizeTag($tag), $prefix, $length) !== 0)
				{
					continue;
				}

				$this->_headingName = trim(substr($tag, $length));

				if (isset(self::$_cacheHeading[$normalizedTag]))
				{
					$this->_heading = self::$_cacheHeading[$normalizedTag];
					break;
				}

				if ($entity = $this->_application->getServiceTags()->getEntityByCode($normalizedTag, true))
				{
					foreach ($entity->getTags() as $tagEx)
					{
						if (strncmp($tagEx, 'heading:', 8) === 0)
						{
							self::$_cacheHeading[$normalizedTag] = substr($tagEx, 8);
							$this->_heading = self::$_cacheHeading[$normalizedTag];
							break 2;
						}
					}
				}
			}
		}

		return $this->_heading;
	}

	/**
	 * @return string
	 */
	public function getHeadingName()
	{
		if ($this->_headingName === null)
		{
			$this->getHeading();
		}

		return $this->_headingName;
	}

	/**
	 * @return string
	 */
	public function getUrl()
	{
		if (empty($this->_url))
		{
			$parameters = $this->getParameters();

			$this->_url = empty($parameters['link'])
				? $this->_application->url('article', array_merge((array)$this->_urlParameters, [
						'heading' => $this->getHeading(),
						'code'    => $this->getCode(),
					]))
				: $parameters['link'];
		}

		return $this->_url;
	}
}