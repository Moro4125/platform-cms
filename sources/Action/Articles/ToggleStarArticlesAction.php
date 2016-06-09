<?php
/**
 * Class ToggleStarArticlesAction
 */
namespace Moro\Platform\Action\Articles;
use \Moro\Platform\Action\AbstractToggleStar;
use \Moro\Platform\Application;

/**
 * Class ToggleStarArticlesAction
 * @package Moro\Platform\Action\Articles
 */
class ToggleStarArticlesAction extends AbstractToggleStar
{
	public $serviceCode = Application::SERVICE_CONTENT;
}