<?php
/**
 * Class RssDecorator
 */
namespace Moro\Platform\Model\Implementation\Content\Decorator;

/**
 * Class RssDecorator
 * @package Moro\Platform\Model\Implementation\Content\Decorator
 */
class RssDecorator extends AbstractDecorator
{
	/**
	 * @return string
	 */
	public function getDescription()
	{
		$html = '';

		if ($icon = $this->getIcon())
		{
			$iconUrl = $this->_application->url('image', ['hash' => $icon, 'width' => 154, 'height' => 96]);
			$html.= '<br/><';
			$html.= 'img'.' src="'.htmlspecialchars($iconUrl).'">';
		}

		$parameters = $this->getParameters();

		if (!empty($parameters['lead']))
		{
			$html.= '<p>'.htmlspecialchars($parameters['lead']).'</p>';
		}

		return $html;
	}
}