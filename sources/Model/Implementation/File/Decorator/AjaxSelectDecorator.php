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
	const PROP_VIEW = 'view';
	const PROP_EDIT = 'edit';
	const PROP_HINT = 'hint';

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
	 * @return string
	 */
	public function getView()
	{
		$hash = $this->getHash();
		$parameters = $this->getParameters();
		$width = isset($parameters['width']) ? $parameters['width'] : 0;
		$height = isset($parameters['height']) ? $parameters['height'] : 0;

		return (!empty($width) && !empty($height))
			? $this->_application->url('image', ['hash' => $hash, 'width' => $width, 'height' => $height, 'remember' => 0])
			: '#';
	}

	/**
	 * @return string
	 */
	public function getEdit()
	{
		$closeUrl = $this->_application->url('admin-about').'#close=Y';
		return $this->_application->url('admin-content-images-update', ['id' => $this->getId(), 'back' => $closeUrl]);
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
			self::PROP_ID   => $this->getHash(),
			self::PROP_NAME => $this->getName(),
			self::PROP_ICON => $this->getIcon(),
			self::PROP_TAGS => $this->getTags(),
			self::PROP_VIEW => $this->getView(),
			self::PROP_EDIT => $this->getEdit(),
			self::PROP_HINT => $this->getHint(),
		];
	}
}