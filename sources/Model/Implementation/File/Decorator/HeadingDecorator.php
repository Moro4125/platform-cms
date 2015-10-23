<?php
/**
 * Class HeadingDecorator
 */
namespace Moro\Platform\Model\Implementation\File\Decorator;
use Moro\Platform\Model\Accessory\Heading\HeadingDecoratorTrait;

/**
 * Class HeadingDecorator
 * @package Model\Implementation\File\Decorator
 */
class HeadingDecorator extends AbstractDecorator
{
	use HeadingDecoratorTrait;

	/**
	 * @var string
	 */
	protected static $_notDraft;

	/**
	 * @var array
	 */
	protected $_normalizedTags;

	/**
	 * @return array
	 */
	protected function _getNormalizedTags()
	{
		if ($this->_normalizedTags === null)
		{
			$this->_normalizedTags = [];

			foreach (parent::getTags() as $index => $tag)
			{
				$this->_normalizedTags[$index] = normalizeTag($tag);
			}
		}

		return $this->_normalizedTags;
	}

	/**
	 * @return array
	 */
	public function getTags()
	{
		$result = [];
		$heading = normalizeTag('раздел:'.$this->getHeadingName());
		$tags = parent::getTags();

		foreach ($this->_getNormalizedTags() as $index => $tag)
		{
			if ($tag !== $heading)
			{
				$result[] = $tags[$index];
			}
		}

		return $result;
	}

	/**
	 * @return bool
	 */
	public function getIsDraft()
	{
		return false;

		/* @todo Сделать определение картинок, которые нигде не используются.
		self::$_notDraft || self::$_notDraft = normalizeTag('-флаг: черновик');
		$heading = 'раздел:';
		$length = strlen($heading);

		foreach ($this->_getNormalizedTags() as $tag)
		{
			if (strncmp($tag, $heading, $length) === 0)
			{
				return false;
			}

			if ($tag === self::$_notDraft)
			{
				return false;
			}
		}

		return true;
		*/
	}
}