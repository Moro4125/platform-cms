<?php
/**
 * Class RssContentDecorator
 */
namespace Moro\Platform\Model\Implementation\Content\Decorator;
use \Michelf\MarkdownExtra;


/**
 * Class RssContentDecorator
 * @package Model\Implementation\Content\Decorator
 */
class RssContentDecorator extends AbstractDecorator
{
	/**
	 * @var array
	 */
	protected $_urlParameters = ['from' => 'rss'];

	/**
	 * @return string HTML
	 */
	public function getDescription()
	{
		$html = '';
		$url = htmlspecialchars($this->getUrl());

		if ($hash = $this->getIcon())
		{
			$imgUri = $this->_application->url('image', ['hash' => $hash, 'width' => 384, 'height' => 240]);
			$html .= '<a href="'.$url.'"><img src="'.$imgUri.'"/></a>';
		}
		else
		{
			$code = $this->getCode();
			$message = "В RSS попал материал с кодом \"$code\", который не содержит изображения для анонса.";
			$this->_application->getServiceFlash()->error($message);
		}

		$parameters = $this->getParameters();

		if (!empty($parameters['lead']))
		{
			$html .= MarkdownExtra::defaultTransform($parameters['lead']);

		}

		return $html;
	}
}