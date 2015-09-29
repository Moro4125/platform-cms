<?php
/**
 * File for providers and services initialization.
 */
namespace Moro\Platform;
use \Silex\Provider\DoctrineServiceProvider;
use \Silex\Provider\SecurityServiceProvider;
use \Silex\Provider\UrlGeneratorServiceProvider;
use \Silex\Provider\TwigServiceProvider;
use \Silex\Provider\ValidatorServiceProvider;
use \Silex\Provider\TranslationServiceProvider;
use \Silex\Provider\HttpFragmentServiceProvider;
use \Silex\Provider\SessionServiceProvider;
use \Symfony\Component\HttpKernel\Fragment\SsiFragmentRenderer;
use \Saxulum\SaxulumBootstrapProvider\Silex\Provider\SaxulumBootstrapProvider;
use \Monolog\Logger;
use \Knp\Provider\ConsoleServiceProvider;
use \Knp\Menu\Integration\Silex\KnpMenuServiceProvider;
use \Knp\Menu\Matcher\Matcher;
use \Knp\Menu\Matcher\Voter\RouteVoter;
use \Aptoma\Twig\Extension\MarkdownEngine\MichelfMarkdownEngine;
use \Moro\Migration\Provider\TeamMigrationsServiceProvider;
use \Moro\Migration\Provider\Handler\FilesStorageHandlerProvider;
use \Moro\Migration\Provider\Handler\DoctrineDBALHandlerProvider;
use \Moro\Platform\Provider\MonologServiceProvider;
use \Moro\Platform\Provider\ImagineServiceProvider;
use \Moro\Platform\Provider\RequestProcessor;
use \Moro\Platform\Provider\FormServiceProvider;
use \Moro\Platform\Provider\HttpCacheServiceProvider;
use \Moro\Platform\Provider\Twig\ApplicationExtension;
use \Moro\Platform\Provider\Twig\MarkdownExtension;
use \Moro\Platform\Model\Accessory\Parameters\Tags\TagsServiceBehavior;
use \Moro\Platform\Model\Implementation\Routes\ServiceRoutes;
use \Moro\Platform\Model\Implementation\Options\ServiceOptions;
use \Moro\Platform\Model\Implementation\Content\ServiceContent;
use \Moro\Platform\Model\Implementation\File\ServiceFile;
use \Moro\Platform\Model\Implementation\Relink\ServiceRelink;
use \Moro\Platform\Model\Implementation\Tags\ServiceTags;
use \Moro\Platform\Tools\Relink;


Application::getInstance(function (Application $app)
{
	$adminPrefix = (!defined('INDEX_PAGE') || INDEX_PAGE !== 'admin')
		? '^/admin'
		: '^.*';

	// Security Provider.
	$app->register(new SecurityServiceProvider(), [
		'security.firewalls' => [
			'admin' => [
				'pattern' => $adminPrefix,
				'http' => true,
				'users' => [
					'morozkin' => ['ROLE_ADMIN',  "+r7u2o9zOMbdVKxZ0eB5b1QVN9nBWO3YyUfqnrPWZJOvh6woVC+5pp2TKM81RQyGyJ3wh3hJS2jva6n9X4y/5A=="],
					'nevolin'  => ['ROLE_EDITOR', "7Cd5mhu+f/1YDBdp3rDoscAjJJoYcxdSUXYAMoCt/Yb0/yTuHt6y9L8msXmRZ9sTKpXmOYz13G9LHtMCh0Hjog=="],
					'avdey'    => ['ROLE_EDITOR', "K8CM82b0IJPAL3ffi8jnZRghe2iTRPvpnZLTQZY1jfZy+AOfW5MRdEbMcwYw3NWhYnnqX8nCeXbBniJvym65fA=="],
					'ghost'    => ['ROLE_GHOST',  "IMnO8KlIblPktymVVsl5MhGvX09+PhTiFmbnNDMSLRq+oscDRfywLbdmvEmXUajal0c2EaMsOYlZMjoXp5LmVw=="],
				],
			],
			'public' => [
				'pattern'   => '^.*$',
				'anonymous' => true,
			]
		],
		'security.role_hierarchy' => [
			'ROLE_ADMIN'  => ['ROLE_EDITOR', 'ROLE_RELINK', 'ROLE_USER'],
			'ROLE_EDITOR' => ['ROLE_USER'],
		],
		'security.access_rules' => [
			[$adminPrefix.'/panel/security', 'ROLE_ADMIN'],
			[$adminPrefix.'/.*',             ['ROLE_EDITOR', 'ROLE_GHOST']],
		],
	]);

	// Doctrine DBAL Provider.
	$app->register(new DoctrineServiceProvider(), [
		// @see http://silex.sensiolabs.org/doc/providers/doctrine.html
		'db.options' => array_merge($app->getOptions('db'), [
			'driverOptions' => [
				'userDefinedFunctions' => [
					'simplify_text' => ['callback' => 'my_sqlite_simplify_text', 'numArgs' => 1],
				],
			],
		]),
	]);

	// URL Generator Service Provider
	$app->register(new UrlGeneratorServiceProvider());

	// Symfony Console Provider.
	$app->register(new ConsoleServiceProvider(), [
		'console.name'              => $app->getName(),
		'console.version'           => $app->getVersion(),
		'console.project_directory' => $app->getOption('path.project'),
	]);

	// Team Migrations Service Provider + Handlers
	$app->register(new TeamMigrationsServiceProvider(), [
		'team-migrations.options' => [
			'validation.key' => $app->getOption('migrations.validation.key'),
			'path.storage'   => $app->getOption('path.data'),
		],
		'team-migrations.providers' => [
			new FilesStorageHandlerProvider(),
			new DoctrineDBALHandlerProvider(),
		]
	]);

	// Monolog Service Provider.
	$app->register(new MonologServiceProvider(), [
		'monolog.name'       => $app->getName(),
		'monolog.logfile'    => $app->getOption('path.logs').DIRECTORY_SEPARATOR.'app-'.date("Y-m-d").'.log',
		'monolog.level'      => $app->getOption('debug') ? Logger::DEBUG : Logger::INFO,
		'monolog.processors' => [
			new RequestProcessor($app),
		]
	]);

	// Form Service Provider.
	$app->register(new FormServiceProvider());

	// Validator Service Provider.
	$app->register(new ValidatorServiceProvider());

	// Twig Service Provider.
	$app->register(new TwigServiceProvider());

	$app['twig'] = $app->share($app->extend('twig', function($twig, $application) {
		/** @var \Twig_Environment $twig */
		/** @var Application $application */
		$twig->setCache($application->getOption('path.temp').DIRECTORY_SEPARATOR.'twig');
		$twig->addExtension(new MarkdownExtension(new MichelfMarkdownEngine()));
		$twig->addExtension(new ApplicationExtension());

		return $twig;
	}));

	$app['twig.loader.filesystem'] = $app->share($app->extend('twig.loader.filesystem',
		function (\Twig_Loader_Filesystem $twigLoaderFilesystem) use ($app) {
			$twigLoaderFilesystem->addPath(dirname(__DIR__).DIRECTORY_SEPARATOR.'views', 'PlatformCMS');

			return $twigLoaderFilesystem;
		}
	));

	// KnpMenu Service Provider.
	$app->register(new KnpMenuServiceProvider(), [
		'knp_menu.template' => '@SaxulumBootstrapProvider/Menu/bootstrap.html.twig',
		'knp_menu.matcher.configure' => $app->protect(function(Matcher $matcher) use ($app) {
			$matcher->addVoter(new RouteVoter($app->offsetGet('request')));
		})
	]);

	// Saxulum Bootstrap Provider.
	$app->register(new SaxulumBootstrapProvider());

	// Translation Service Provider.
	$app->register(new TranslationServiceProvider(), [
		'translator.domains' => [
		],
	]);

	// Http Fragment Service Provider.
	$app->register(new HttpFragmentServiceProvider(), [
		'fragment.renderer.ssi' => $app->share(function ($app) {
			$renderer = new SsiFragmentRenderer($app['http_cache.ssi'], $app['fragment.renderer.inline']);
			$renderer->setFragmentPath($app['fragment.path']);

			return $renderer;
		}),
		'fragment.renderers' => $app->share(function ($app) {
			$renders = [ $app['fragment.renderer.inline'], $app['fragment.renderer.hinclude'] ];
			$renders[] = $app['fragment.renderer.ssi'];

			return $renders;
		}),
	]);

	// HTTP Cache Service Provider.
	$app->register(new HttpCacheServiceProvider());

	// Session Service Provider.
	$app->register(new SessionServiceProvider());

	// Imagine Service Provider.
	$app->register(new ImagineServiceProvider());
});

Application::getInstance(function (Application $app)
{
	// Model behavior TAGS.
	$app[Application::BEHAVIOR_TAGS] = $app->share(function() use ($app) {
		$behavior = new TagsServiceBehavior();
		$behavior->setTagsService($app->getServiceTags());
		$behavior->setDbConnection($app->getServiceDataBase());
		return $behavior;
	});

	// Service ROUTES.
	$app[Application::SERVICE_ROUTES] = $app->share(function() use ($app) {
		return new ServiceRoutes($app->getServiceDataBase());
	});

	// Service OPTIONS.
	$app[Application::SERVICE_OPTIONS] = $app->share(function() use ($app) {
		$service = new ServiceOptions($app->getServiceDataBase());
		$service->setLogger($app->getServiceLogger());
		return $service;
	});

	// Service CONTENT.
	$app[Application::SERVICE_CONTENT] = $app->share(function() use ($app) {
		$service = new ServiceContent($app->getServiceDataBase());
		$service->setServiceUser($app->getServiceSecurityToken());
		$service->setLogger($app->getServiceLogger());
		$service->attach($app[Application::BEHAVIOR_TAGS]);
		return $service;
	});

	// Service FILE.
	$app[Application::SERVICE_FILE] = $app->share(function() use ($app) {
		$service = new ServiceFile($app->getServiceDataBase());
		$service->setLogger($app->getServiceLogger());
		$service->setServiceUser($app->getServiceSecurityToken());
		$service->setStoragePath($app->getOption('path.data'));
		$service->attach($app[Application::BEHAVIOR_TAGS]);
		return $service;
	});

	// Service RELINK.
	$app[Application::SERVICE_RELINK] = $app->share(function() use ($app) {
		$service = new ServiceRelink($app->getServiceDataBase());
		$service->setLogger($app->getServiceLogger());
		$service->setServiceUser($app->getServiceSecurityToken());
		return $service;
	});

	// Service RELINK TOOL.
	$app[Application::SERVICE_RELINK_TOOL] = $app->share(function() use ($app) {
		$service = new Relink();
		$service->setLinks($app->getServiceRelink()->getLinks());
		return $service;
	});

	// Service TAGS.
	$app[Application::SERVICE_TAGS] = $app->share(function() use ($app) {
		$service = new ServiceTags($app->getServiceDataBase());
		$service->setServiceUser($app->getServiceSecurityToken());
		$service->setLogger($app->getServiceLogger());
		return $service;
	});
});

Application::getInstance(function (Application $app)
{
	$app['admin_main_menu'] = function() use ($app) {
		$menu = $app->getServiceMenuFactory()->createItem('root');

		$menu->addChild('Информация',     ['route' => 'admin-about']);
		$menu->addChild('Настройки',      ['route' => 'admin-options']);

		$item = $menu->addChild('Материалы', ['route' => 'admin-content-articles']);
		$item->addChild('Статьи', [
			'route' => 'admin-content-articles',
			'display' => true
		]);
		$item->addChild('Изображения', [
			'route' => 'admin-content-images',
			'display' => true
		]);

		if ($app->getServiceSecurityAcl()->isGranted('ROLE_RELINK'))
		{
			$item->addChild('Перелинковка', [
				'route' => 'admin-content-relink',
				'display' => true
			]);

			$item->addChild('Редактирование правила перелинковки', [
				'route' => 'admin-content-relink-update',
				'routeParameters' => [
					'id' => (int)$app['request']->attributes->get('id', 0)
				],
				'display' => false
			]);
		}

		$item->addChild('Ярлыки', [
			'route' => 'admin-content-tags',
			'display' => true
		]);
		$item->addChild('Редактирование материала', [
			'route' => 'admin-content-articles-update',
			'routeParameters' => [
				'id' => (int)$app['request']->attributes->get('id', 0)
			],
			'display' => false
		]);
		$item->addChild('Редактирование изображения', [
			'route' => 'admin-content-images-update',
			'routeParameters' => [
				'id' => (int)$app['request']->attributes->get('id', 0)
			],
			'display' => false
		]);
		$item->addChild('Редактирование ярлыка', [
			'route' => 'admin-content-tags-update',
			'routeParameters' => [
				'id' => (int)$app['request']->attributes->get('id', 0)
			],
			'display' => false
		]);

		$menu->addChild('Предпросмотр',   ['uri' => '/admin/index.php/index.html']);
		$menu->addChild('Публикация',     ['route' => 'admin-compile-list']);

		return $menu;
	};

	$app['admin_content_menu'] = function() use ($app) {
		/** @var \Knp\Menu\MenuItem $menu */
		$menu = $app['admin_main_menu'];
		return $menu->getChild('Материалы');
	};

	$app['knp_menu.menus'] = array_merge(isset($app['knp_menu.menus']) ? $app['knp_menu.menus'] : [], [
		'admin_main'    => 'admin_main_menu',
		'admin_content' => 'admin_content_menu',
	]);
});
