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
use \Moro\Platform\Provider\ApiKeyServiceProvider;
use \Moro\Platform\Provider\MonologServiceProvider;
use \Moro\Platform\Provider\ImagineServiceProvider;
use \Moro\Platform\Provider\RequestProcessor;
use \Moro\Platform\Provider\FormServiceProvider;
use \Moro\Platform\Provider\HttpCacheServiceProvider;
use \Moro\Platform\Provider\Twig\ApplicationExtension;
use \Moro\Platform\Provider\Twig\MarkdownExtension;
use \Moro\Platform\Provider\SentryProvider;
use \Moro\Platform\Security\User\ApiKeyUserProvider;
use \Moro\Platform\Security\Encoder\SaltLessPasswordEncoder;
use \Moro\Platform\Model\Accessory\EventBridgeBehavior;
use \Moro\Platform\Model\Accessory\ClientRoleBehavior;
use \Moro\Platform\Model\Accessory\HistoryBehavior;
use \Moro\Platform\Model\Accessory\Heading\HeadingBehavior;
use \Moro\Platform\Model\Accessory\Parameters\Tags\TagsServiceBehavior;
use \Moro\Platform\Model\Implementation\Routes\ServiceRoutes;
use \Moro\Platform\Model\Implementation\Options\ServiceOptions;
use \Moro\Platform\Model\Implementation\Content\ServiceContent;
use \Moro\Platform\Model\Implementation\Content\Decorator\HeadingDecorator as HeadingContentDecorator;
use \Moro\Platform\Model\Implementation\Content\Behavior\ChunksBehavior;
use \Moro\Platform\Model\Implementation\File\ServiceFile;
use \Moro\Platform\Model\Implementation\File\Decorator\HeadingDecorator as HeadingFileDecorator;
use \Moro\Platform\Model\Implementation\Relink\ServiceRelink;
use \Moro\Platform\Model\Implementation\Tags\ServiceTags;
use \Moro\Platform\Model\Implementation\ApiKey\ServiceApiKey;
use \Moro\Platform\Model\Implementation\History\ServiceHistory;
use \Moro\Platform\Tools\Relink;
use \Moro\Platform\Tools\DiffMatchPatch;


Application::getInstance(function (Application $app)
{
	$adminPrefix = (!defined('INDEX_PAGE') || INDEX_PAGE !== 'admin')
		? '^/admin'
		: '^.*';

	// Read users from INI file (section "access").
	$users = $app->getOptions('access');

	if (count($users) > 1)
	{
		unset($users['singleton']);
	}

	foreach ($users as &$user)
	{
		$rights = array_map('trim', explode(',', $user));
		$secret = array_pop($rights);
		$user = [$rights, $secret];
	}

	// Read groups hierarchy from INI file (section "groups").
	$groups = $app->getOptions('groups');

	foreach ($groups as &$group)
	{
		$group = array_map('trim', explode(',', $group));
	}

	// Security Provider.
	$app->register(new SecurityServiceProvider(), [
		'security.firewalls' => [
			'api' => [
				'pattern' => $adminPrefix.'/platform',
				'api_key' => true,
				'stateless' => true,
			],
			'admin' => [
				'pattern' => $adminPrefix,
				'http' => true,
				'users' => $users,
			],
			'public' => [
				'pattern'   => '^.*$',
				'anonymous' => true,
			]
		],
		'security.role_hierarchy' => $groups,
		'security.access_rules' => [
			[$adminPrefix.'/panel/options',           'ROLE_RS_OPTIONS'],
			[$adminPrefix.'/panel/content/articles?', 'ROLE_RS_ARTICLES'],
			[$adminPrefix.'/panel/content/images?',   'ROLE_RS_IMAGES'],
			[$adminPrefix.'/panel/content/relink',    'ROLE_RS_RELINK'],
			[$adminPrefix.'/panel/content/tags?',     'ROLE_RS_TAGS'],
			[$adminPrefix.'/panel/pages',             'ROLE_USER'],
			[$adminPrefix.'/panel/help',              'ROLE_USER'],
			[$adminPrefix.'/panel$',                  'ROLE_USER'],
			[$adminPrefix.'/panel',                   'ROLE_ADMIN'],
			[$adminPrefix.'/platform',                'ROLE_USER'],
		],
	]);

	// API key Provider.
	$app->register(new ApiKeyServiceProvider(), [
		'api_key.user_provider' => new ApiKeyUserProvider($app),
		'api_key.encoder'       => new SaltLessPasswordEncoder()
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
		'monolog.logfile'    => $app->getOption('path.logs').DIRECTORY_SEPARATOR.php_sapi_name().'-'.date("Y-m-d").'.log',
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

	$app->update('twig', function(\Twig_Environment $twig, Application $application) {
		$twig->setCache($application->getOption('path.temp').DIRECTORY_SEPARATOR.'twig');
		$twig->addExtension(new MarkdownExtension(new MichelfMarkdownEngine()));
		$twig->addExtension($extension = new ApplicationExtension($application));

		$extension->setTexFilePath($application->getOption('content.hyphenate'));

		return $twig;
	});

	$app->update('twig.loader.filesystem', function (\Twig_Loader_Filesystem $twigLoaderFilesystem) {
			$twigLoaderFilesystem->addPath(dirname(__DIR__).DIRECTORY_SEPARATOR.'views', 'PlatformCMS');

			return $twigLoaderFilesystem;
		}
	);

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
	/** @noinspection SpellCheckingInspection */
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

	// Sentry Provider (Raven).
	if ($app->getOption('sentry.active'))
	{
		$app->register(new SentryProvider());
	}
});


Application::getInstance(function (Application $app)
{
	$suffixClass = '.class';
	$lockTime = $app->getOption('content.lock-time');

	// Model behavior TAGS.
	$app[Application::BEHAVIOR_TAGS] = $app->share(function() use ($app, $suffixClass) {
		$class = $app->offsetGet(Application::BEHAVIOR_TAGS.$suffixClass, TagsServiceBehavior::class);

		/** @var TagsServiceBehavior $behavior */
		$behavior = new $class();
		$behavior->setTagsService($app->getServiceTags());
		$behavior->setDbConnection($app->getServiceDataBase());
		return $behavior;
	});

	// Model behavior HEADINGS.
	$app[Application::BEHAVIOR_HEADINGS] = $app->share(function() use ($app, $suffixClass) {
		$class = $app->offsetGet(Application::BEHAVIOR_HEADINGS.$suffixClass, HeadingBehavior::class);

		/** @var HeadingBehavior $behavior */
		$behavior = new $class();
		$behavior->setServiceTags($app->getServiceTags());
		return $behavior;
	});

	// Model behavior HISTORY.
	$app[Application::BEHAVIOR_HISTORY] = $app->share(function() use ($app, $suffixClass) {
		$class = $app->offsetGet(Application::BEHAVIOR_HISTORY.$suffixClass, HistoryBehavior::class);

		/** @var HistoryBehavior $behavior */
		$behavior = new $class();
		$behavior->setServiceHistory($app->getServiceHistory());
		$behavior->setServiceDiffMatchPatch($app->getServiceDiffMatchPatch());
		$behavior->setUserToken($app->getServiceSecurityToken());
		return $behavior;
	});

	// Model behavior CLIENT_ROLE.
	$app[Application::BEHAVIOR_CLIENT_ROLE] = $app->share(function() use ($app, $suffixClass) {
		$class = $app->offsetGet(Application::BEHAVIOR_CLIENT_ROLE.$suffixClass, ClientRoleBehavior::class);

		/** @var ClientRoleBehavior $behavior */
		$behavior = new $class();
		$behavior->setClient($app->getServiceSecurityToken()->getUsername());

		/** @var \Symfony\Component\HttpFoundation\Request $request */
		$request = $app['request'];
		$route   = $request->attributes->get('_route');
		$behavior->setEnabled(strncmp($route, 'admin-', 6) === 0 && $route != 'admin-compile');

		return $behavior;
	});

	// Model behavior CONTENT_CHUNKS.
	if ($app->getOption('content.multi_page'))
	{
		$app[Application::BEHAVIOR_CONTENT_CHUNKS] = $app->share(function() use ($app, $suffixClass) {
			$class = $app->offsetGet(Application::BEHAVIOR_CONTENT_CHUNKS.$suffixClass, ChunksBehavior::class);

			/** @var ChunksBehavior $behavior */
			$behavior = new $class();
			$behavior->setContentService($app->getServiceContent());

			return $behavior;
		});
	}

	// Service ROUTES.
	$app[Application::SERVICE_ROUTES] = $app->share(function() use ($app, $suffixClass) {
		$class = $app->offsetGet(Application::SERVICE_ROUTES.$suffixClass, ServiceRoutes::class);

		/** @var ServiceRoutes $service */
		$service = new $class($app->getServiceDataBase());
		$service->setServiceUser($app->getServiceSecurityToken());

		if ($app->getServiceSecurityAcl() && $app->isGranted('ROLE_CLIENT'))
		{
			$service->setClient($app->getServiceSecurityToken()->getUsername());
			$service->attach($app->getBehaviorClientRole());
		}

		return $service;
	});

	// Service DIFF_MATCH_PATCH.
	$app[Application::SERVICE_DIFF_MATCH_PATCH] = $app->share(function() use ($app, $suffixClass) {
		$class = $app->offsetGet(Application::SERVICE_DIFF_MATCH_PATCH.$suffixClass, DiffMatchPatch::class);

		return new $class();
	});

	// Service HISTORY.
	$app[Application::SERVICE_HISTORY] = $app->share(function() use ($app, $suffixClass) {
		$class = $app->offsetGet(Application::SERVICE_HISTORY.$suffixClass, ServiceHistory::class);

		/** @var ServiceHistory $service */
		$service = new $class($app->getServiceDataBase());
		$service->setServiceCode(Application::SERVICE_HISTORY);
		$service->setServiceUser($app->getServiceSecurityToken());
		return $service;
	});

	// Service OPTIONS.
	$app[Application::SERVICE_OPTIONS] = $app->share(function() use ($app, $suffixClass) {
		$class = $app->offsetGet(Application::SERVICE_OPTIONS.$suffixClass, ServiceOptions::class);

		/** @var ServiceOptions $service */
		$service = new $class($app->getServiceDataBase());
		$service->setServiceCode(Application::SERVICE_OPTIONS);
		$service->setLogger($app->getServiceLogger());
		return $service;
	});

	// Service CONTENT.
	$app[Application::SERVICE_CONTENT] = $app->share(function() use ($app, $suffixClass, $lockTime) {
		$class = $app->offsetGet(Application::SERVICE_CONTENT.$suffixClass, ServiceContent::class);

		/** @var ServiceContent $service */
		$service = new $class($app->getServiceDataBase());
		$service->setServiceCode(Application::SERVICE_CONTENT);
		$service->setServiceUser($app->getServiceSecurityToken());
		$service->setLogger($app->getServiceLogger());
		$service->setServiceFile($app->getServiceFile());
		$service->attach($app->getBehaviorTags());

		$service->setLockTime($lockTime);

		if ($app->getOption('content.headings'))
		{
			$service->attach($app->getBehaviorHeadings());
			$service->appendDecorator(new HeadingContentDecorator($app));
		}

		if ($app->getOption('content.multi_page'))
		{
			$service->attach(new EventBridgeBehavior([
				ServiceContent::STATE_DELETE_STARTED,
				EventBridgeBehavior::STATE_LAZY_INIT,
				Application::SERVICE_CONTENT_CHUNKS,
				$app,
			]));
		}

		if ($app->getServiceSecurityAcl() && $app->isGranted('ROLE_CLIENT'))
		{
			$service->attach($app->getBehaviorClientRole());
		}

		return $service;
	});

	// Service CONTENT_CHUNKS.
	if ($app->getOption('content.multi_page'))
	{
		$app[Application::SERVICE_CONTENT_CHUNKS] = $app->share(function() use ($app, $suffixClass, $lockTime) {
			$content = $app->getServiceContent();
			$service = clone $content;

			$service->setTableName($content->getTableName().'_chunks');
			$service->setServiceCode(Application::SERVICE_CONTENT_CHUNKS);
			$service->detach($app->getBehaviorTags());
			$service->attach($app->getBehaviorContentChunks());

			return $service;
		});
	}

	// Service FILE.
	$app[Application::SERVICE_FILE] = $app->share(function() use ($app, $suffixClass, $lockTime) {
		$class = $app->offsetGet(Application::SERVICE_FILE.$suffixClass, ServiceFile::class);

		/** @var ServiceFile $service */
		$service = new $class($app->getServiceDataBase());
		$service->setServiceCode(Application::SERVICE_FILE);
		$service->setServiceUser($app->getServiceSecurityToken());
		$service->setStoragePath($app->getOption('path.data'));
		$service->setLogger($app->getServiceLogger());
		$service->attach($app->getBehaviorTags());

		$service->setLockTime($lockTime);

		if ($app->getOption('content.headings'))
		{
			$service->attach($app->getBehaviorHeadings());
			$service->appendDecorator(new HeadingFileDecorator($app));
		}

		if ($app->getServiceSecurityAcl() && $app->isGranted('ROLE_CLIENT'))
		{
			$service->attach($app->getBehaviorClientRole());
		}

		return $service;
	});

	// Service RELINK.
	$app[Application::SERVICE_RELINK] = $app->share(function() use ($app, $suffixClass, $lockTime) {
		$class = $app->offsetGet(Application::SERVICE_RELINK.$suffixClass, ServiceRelink::class);

		/** @var ServiceRelink $service */
		$service = new $class($app->getServiceDataBase());
		$service->setServiceCode(Application::SERVICE_RELINK);
		$service->setServiceUser($app->getServiceSecurityToken());
		$service->setLogger($app->getServiceLogger());
		$service->attach($app->getBehaviorTags());

		$service->setLockTime($lockTime);

		return $service;
	});

	// Service RELINK TOOL.
	$app[Application::SERVICE_RELINK_TOOL] = $app->share(function() use ($app, $suffixClass) {
		$class = $app->offsetGet(Application::SERVICE_RELINK_TOOL.$suffixClass, Relink::class);

		/** @var Relink $service */
		$service = new $class();
		$service->setLinks($app->getServiceRelink()->getLinks());
		return $service;
	});

	// Service TAGS.
	$app[Application::SERVICE_TAGS] = $app->share(function() use ($app, $suffixClass, $lockTime) {
		$class = $app->offsetGet(Application::SERVICE_TAGS.$suffixClass, ServiceTags::class);

		/** @var ServiceTags $service */
		$service = new $class($app->getServiceDataBase());
		$service->setServiceCode(Application::SERVICE_TAGS);
		$service->setServiceUser($app->getServiceSecurityToken());
		$service->setLogger($app->getServiceLogger());

		$service->setLockTime($lockTime);

		return $service;
	});

	// Service API KEYS.
	$app[Application::SERVICE_API_KEY] = $app->share(function() use ($app, $suffixClass, $lockTime) {
		$class = $app->offsetGet(Application::SERVICE_API_KEY.$suffixClass, ServiceApiKey::class);

		/** @var ServiceTags $service */
		$service = new $class($app->getServiceDataBase());
		$service->setServiceCode(Application::SERVICE_API_KEY);

		return $service;
	});
});

Application::getInstance(function (Application $app)
{
	$app['admin_main_menu'] = function() use ($app) {
		$access = $app->getServiceSecurityAcl();
		$menu = $app->getServiceMenuFactory()->createItem('root');

		$menu->addChild('Информация', ['route' => 'admin-about']);

		if ($access->isGranted('ROLE_RS_OPTIONS'))
		{
			$menu->addChild('Настройки', ['route' => 'admin-options']);
		}

		$flag = $access->isGranted('ROLE_RS_ARTICLES');
		$flag|= $access->isGranted('ROLE_RS_IMAGES');
		$flag|= $access->isGranted('ROLE_RS_RELINK');
		$flag|= $access->isGranted('ROLE_RS_TAGS');

		$item = $menu->addChild('Материалы', [
			'route' => 'admin-content-articles',
			'display' => $flag,
		]);

		if ($access->isGranted('ROLE_RS_ARTICLES'))
		{
			$item->addChild('Тексты', [
				'route' => 'admin-content-articles',
				'display' => true
			]);

			$item->addChild('Редактирование текста', [
				'route' => 'admin-content-articles-update',
				'routeParameters' => [
					'id' => (int)$app['request']->attributes->get('id', 0)
				],
				'display' => false
			]);
		}

		if ($access->isGranted('ROLE_RS_IMAGES'))
		{
			$item->addChild('Изображения', [
				'route' => 'admin-content-images',
				'display' => true
			]);

			$item->addChild('Редактирование изображения', [
				'route' => 'admin-content-images-update',
				'routeParameters' => [
					'id' => (int)$app['request']->attributes->get('id', 0)
				],
				'display' => false
			]);
		}

		if ($access->isGranted('ROLE_RS_RELINK'))
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

		if ($access->isGranted('ROLE_RS_TAGS'))
		{
			$item->addChild('Ярлыки', [
				'route' => 'admin-content-tags',
				'display' => true
			]);

			$item->addChild('Редактирование ярлыка', [
				'route' => 'admin-content-tags-update',
				'routeParameters' => [
					'id' => (int)$app['request']->attributes->get('id', 0)
				],
				'display' => false
			]);
		}

		if (strncmp($url = $app->url('index'), '#error', 6))
		{
			$menu->addChild('Предпросмотр', ['uri' => $url]);
		}

		$menu->addChild('Публикация', ['route' => 'admin-compile-list']);

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
