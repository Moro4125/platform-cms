<?php
/**
 * Class MarkdownHelpAction
 */
namespace Moro\Platform\Action\Tools;
use \Moro\Platform\Application;

/**
 * Class MarkdownHelpAction
 * @package Moro\Platform\Action\Tools
 */
class MarkdownHelpAction
{
	/**
	 * @param Application $application
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function __invoke(Application $application)
	{
		return $application->render('@PlatformCMS/admin/markdown-help.html.twig');
	}
}