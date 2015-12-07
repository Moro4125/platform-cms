<?php
/**
 * Class RssDecorator
 */
namespace Moro\Platform\Model\Implementation\File\Decorator;

/**
 * Class RssDecorator
 * @package Moro\Platform\Model\Implementation\File\Decorator
 */
class RssDecorator extends AbstractDecorator
{
	/**
	 * @return string
	 */
	public function getDescription()
	{
		$html = '';

		if ($icon = $this->getHash())
		{
			$iconUrl = $this->_application->url('image', ['hash' => $icon, 'width' => 96, 'height' => 96]);
			$html.= '<br/><img src="'.htmlspecialchars($iconUrl).'">';
		}

		return $html;
	}
}