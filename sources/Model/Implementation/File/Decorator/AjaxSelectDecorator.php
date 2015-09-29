<?php
/**
 * Class AjaxSelectDecorator
 */
namespace Moro\Platform\Model\Implementation\File\Decorator;


/**
 * Class AjaxSelectDecorator
 * @package Model\Implementation\File\Decorator
 */
class AjaxSelectDecorator extends AbstractDecorator
{
	const PROP_ICON = 'icon';
	const PROP_TAGS = 'tags';

	/**
	 * @return string
	 */
	public function getIcon()
	{
		return $this->_application->url('image', ['hash' => $this->getHash(), 'width' => 96, 'height' => 96]);
	}

	/**
	 * @return array
	 */
	public function getTags()
	{
		$options = $this->getParameters();
		return isset($options[self::PROP_TAGS]) ? $options[self::PROP_TAGS] : [];
	}

	/**
	 * @return array
	 */
	public function jsonSerialize()
	{
		return [
			self::PROP_ID   => $this->getHash(),
			self::PROP_NAME => $this->getName(),
			self::PROP_ICON => $this->getIcon(),
			self::PROP_TAGS => $this->getTags(),
		];
	}
}