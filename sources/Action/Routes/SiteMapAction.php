<?php
/**
 * Class SiteMapAction
 */
namespace Moro\Platform\Action\Routes;
use \Moro\Platform\Application;
use \Moro\Platform\Action\AbstractContentAction;
use \Moro\Platform\Model\Implementation\Routes\RoutesInterface;
use \Silex\Application as SilexApplication;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;
use \DateTime;

/**
 * Class SiteMapAction
 * @package Moro\Platform\Action\Routes
 *
 * @method \Moro\Platform\Model\Implementation\Routes\ServiceRoutes getService();
 */
class SiteMapAction extends AbstractContentAction
{
	public $serviceCode = Application::SERVICE_ROUTES;
	public $route = 'compile-site-map';
	public $template = '@PlatformCMS/sitemap.xml.twig';

	/**
	 * @param \Moro\Platform\Application|SilexApplication $app
	 * @param Request $request
	 * @return Response
	 */
	public function __invoke(SilexApplication $app, Request $request)
	{
		assert(!empty($this->serviceCode));
		assert(!empty($this->template));
		assert(!empty($this->route));

		$this->setApplication($app);
		$this->setRequest($request);

		$items = [];
		foreach ($this->getService()->selectFileMap() as $record)
		{
			if (strncmp($record[RoutesInterface::PROP_FILE], '/inner/', 7) === 0)
			{
				continue;
			}

			if (substr($record[RoutesInterface::PROP_FILE], -5) == '.html')
			{
				$items[] = $record;
			}
		}

		return $app->render($this->template, [
			'w3c'   => DateTime::W3C,
			'host'  => $request->getHost(),
			'items' => $items,
		], new Response('', 200, [
			Application::HEADER_USE_FULL_URL => 1,
		]));
	}
}