<?php
/**
 * Trait HeadingDecoratorTrait
 */
namespace Moro\Platform\Model\Accessory\Heading;

/**
 * Trait HeadingDecoratorTrait
 * @package Moro\Platform\Model\Accessory\Heading
 *
 * @method getTags()
 * @property \Moro\Platform\Application $_application
 */
trait HeadingDecoratorTrait
{
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
}