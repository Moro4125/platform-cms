<?php
/**
 * Class ApiRssArticlesAction
 */
namespace Moro\Platform\Action\Articles;
use \Moro\Platform\Model\Implementation\Content\Decorator\RssDecorator;

/**
 * Class ApiRssArticlesAction
 * @package Moro\Platform\Action\Articles
 */
class ApiRssArticlesAction extends IndexArticlesAction
{
	public $template = '@PlatformCMS/admin/rich-site-summary.xml.twig';

	/**
	 * @return array
	 */
	protected function _prepareArgumentsForList()
	{
		$this->_headers['Content-Type'] = 'application/rss+xml; charset=utf-8';

		$result = parent::_prepareArgumentsForList();
		$result[2] = '!updated_at';

		return $result;
	}

	/**
	 * @return \Symfony\Component\Form\Form
	 */
	protected function _createForm()
	{
		$this->getService()->appendDecorator(new RssDecorator($this->getApplication()));

		return parent::_createForm();
	}
}