<?php
/**
 * Class AjaxSelectDecorator
 */
namespace Moro\Platform\Model\Implementation\Content\Decorator;

/**
 * Class AjaxSelectDecorator
 * @package Model\Implementation\Content\Decorator
 */
class AjaxSelectDecorator extends AbstractDecorator
{
	const PROP_ICON = 'icon';
	const PROP_TAGS = 'tags';
	const PROP_EDIT = 'edit';
	const PROP_HINT = 'hint';

	/**
	 * @return string
	 */
	public function getIcon()
	{
		$hash = parent::getIcon();
		return empty($hash) ? '' : $this->_application->url('image', ['hash' => $hash, 'width' => 154, 'height' => 96]);
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
	 * @return string
	 */
	public function getEdit()
	{
		$closeUrl = $this->_application->url('admin-about').'#close=Y';
		return $this->_application->url('admin-content-articles-update', ['id' => $this->getId(), 'back' => $closeUrl]);
	}

	/**
	 * @return string
	 */
	public function getHint()
	{
		$parameters = $this->getParameters();
		return isset($parameters['lead']) ? $parameters['lead'] : '';
	}

	/**
	 * @return array
	 */
	public function jsonSerialize()
	{
		return [
			self::PROP_ID   => $this->getId(),
			self::PROP_NAME => $this->getName(),
			self::PROP_ICON => $this->getIcon(),
			self::PROP_TAGS => $this->getTags(),
			self::PROP_EDIT => $this->getEdit(),
			self::PROP_HINT => $this->getHint(),
		];
	}
}