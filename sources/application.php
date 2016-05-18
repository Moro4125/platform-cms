<?php
/**
 * Moro\Platform\Application class.
 */
namespace Moro\Platform;
use \Symfony\Component\Debug\ExceptionHandler;
use \Symfony\Component\Debug\ErrorHandler;
use \Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use \Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\StreamedResponse;
use \Silex\Application as CApplication;
use \ArrayObject;
use \DateTime;
use \RuntimeException;
use \Exception;

/**
 * Класс Moro\Platform\Application отвечает за расширение стандартного функционала приложения фрэймворка Silex.
 *
 * Реализован паттерн Singleton для доступа к объекту приложени без использования глобальной переменной.
 *
 * Добавлена загрузка параметров приложения в соответствии с текущем окружением. Параметры приложения загружаются
 * из файла application.ini, при этом их значения можно переназначить в файле <название окружения>.ini, который
 * может располагаться в любом родительском каталоге, относительно данного.
 */
class Application extends CApplication
{
	use \Silex\Application\MonologTrait;
	use \Silex\Application\FormTrait;

	const PLATFORM_VERSION = "2.0";

	const SERVICE_CONTROLLERS_FACTORY = 'controllers_factory';
	const SERVICE_DATABASE            = 'db';
	const SERVICE_LOGGER              = 'logger';
	const SERVICE_OPTIONS             = 'options';
	const SERVICE_FLASH               = 'bootstrap.flash';
	const SERVICE_FORM_FACTORY        = 'form.factory';
	const SERVICE_MENU_FACTORY        = 'knp_menu.factory';
	const SERVICE_SECURITY_ACL        = 'security.authorization_checker';
	const SERVICE_SECURITY_TOKEN      = 'security.token_storage';
	const SERVICE_ROUTES              = 'srv.routes';
	const SERVICE_CONTENT             = 'srv.content';
	const SERVICE_CONTENT_CHUNKS      = 'srv.content_chunks';
	const SERVICE_FILE                = 'srv.file';
	const SERVICE_RELINK              = 'srv.relink';
	const SERVICE_RELINK_TOOL         = 'srv.tool.relink';
	const SERVICE_TAGS                = 'srv.tags';
	const SERVICE_USERS               = 'srv.users';
	const SERVICE_USERS_AUTH          = 'srv.users_auth';
	const SERVICE_API_KEY             = 'srv.api_key';
	const SERVICE_HISTORY             = 'srv.history';
	const SERVICE_DIFF_MATCH_PATCH    = 'srv.tool.diff';
	const SERVICE_SUBSCRIBERS         = 'srv.subscribers';
	const SERVICE_MESSAGES            = 'srv.messages';
	const SERVICE_IMAGINE             = 'imagine';
	const SERVICE_SENTRY              = 'sentry';
	const SERVICE_MAILER              = 'mailer';

	const BEHAVIOR_TAGS               = 'behavior.tags';
	const BEHAVIOR_HEADINGS           = 'behavior.headings';
	const BEHAVIOR_HISTORY            = 'behavior.history';
	const BEHAVIOR_CLIENT_ROLE        = 'behavior.client_role';
	const BEHAVIOR_CONTENT_CHUNKS     = 'behavior.content_chunks';

	const TWIG_EXTENSION_MARKDOWN     = 'twig.extension.markdown';

	const HEADER_EXPERIMENTAL = 'X-Experimental-Feature';
	const HEADER_USE_FULL_URL = 'X-Use-Full-URL';
	const HEADER_DO_NOT_SAVE  = 'X-Do-Not-Save';
	const HEADER_CACHE_TAGS   = 'X-Cache-Tags';
	const HEADER_CACHE_FILE   = 'X-Cache-File';
	const HEADER_WITHOUT_BAR  = 'X-Without-Bar';
	const HEADER_SURROGATE    = 'Surrogate-Capability';
	const HEADER_ACCEPT       = 'Accept';

	/**
	 * @var string
	 */
	protected $NAME = 'Platform CMS';

	/**
	 * @var string
	 */
	protected $VERSION = self::PLATFORM_VERSION;

	/**
	 * @var Application
	 */
	private static $_instance;

	/**
	 * @var callable[]
	 */
	private static $_callbacks = [];

	/**
	 * @var bool
	 */
	private static $_disableCallback;

	/**
	 * @var bool
	 */
	protected static $_actionFired;

	/**
	 * @var array  Параметры приложения для текущего окружения.
	 */
	protected $_options;

	/**
	 * @var array  Список обработчиков, вызываемых перед обработки каждого запроса (основного или внутреннего).
	 */
	protected $_beforeAnyRequest;

	/**
	 * @var array  Список обработчиков, вызываемых после обработки каждого запроса (основного или внутреннего).
	 */
	protected $_afterAnyRequest;

	/**
	 * @param array $values
	 */
	public function __construct(array $values = array())
	{
		ErrorHandler::register();
		ExceptionHandler::register();
		mb_internal_encoding("UTF-8");

		parent::__construct($values);
	}

	/**
	 * Получение группы настроек по их префиксу.
	 *
	 * @param null|string $prefix
	 * @return array
	 */
	public function getOptions($prefix = null)
	{
		assert($prefix === null || is_string($prefix));

		if ($prefix === null)
		{
			return $this->_options;
		}

		$prefix = $prefix.'.';
		$length = strlen($prefix);
		$result = [];

		foreach ($this->_options as $key => $value)
		{
			if (strncmp($key, $prefix, $length) === 0)
			{
				if (is_string($value) && strpos($value, '%') !== false)
				{
					$value = preg_replace_callback('{\\%(.*?)\\%}', function($match) {
						return isset($this->_options[$match[1]]) ? $this->_options[$match[1]] : $match[0];
					}, $value);
				}

				$result[substr($key, $length)] = $value;
			}
		}

		return $result;
	}

	/**
	 * Получение значения параметра.
	 *
	 * @param string $name
	 * @return mixed|null
	 */
	public function getOption($name)
	{
		assert(is_string($name));

		$value = isset($this->_options[$name]) ? $this->_options[$name] : null;

		if (is_string($value) && strpos($value, '%') !== false)
		{
			$value = preg_replace_callback('{\\%(.*?)\\%}', function($match) {
				return isset($this->_options[$match[1]]) ? $this->_options[$match[1]] : $match[0];
			}, $value);
		}

		return $value;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->NAME;
	}

	/**
	 * @return string
	 */
	public function getVersion()
	{
		return $this->VERSION;
	}

	/**
	 * @return string
	 */
	final public function getPlatformVersion()
	{
		return self::PLATFORM_VERSION;
	}

	/**
	 * @return string
	 */
	public function getBuild()
	{
		static $build;

		if (empty($build))
		{
			$git = realpath($this->getOption('path.project').DIRECTORY_SEPARATOR.'.git');
			$head = $git ? trim(file_get_contents($git.DIRECTORY_SEPARATOR.'HEAD')) : filemtime(__FILE__);
			strncmp($head, 'ref:', 4) || $head = trim(file_get_contents($git.DIRECTORY_SEPARATOR.substr($head, 5)));
			$build = substr($head, -8);
		}

		return $build;
	}

	/**
	 * @param string $role
	 * @return bool
	 */
	public function isGranted($role)
	{
		return ($service = $this->getServiceSecurityAcl()) ? $service->isGranted($role) : true;
	}

	/**
	 * Получение экземпляра приложения.
	 *
	 * @param null|callable $callback
	 * @return Application|static
	 */
	static public function getInstance(callable $callback = null)
	{
		if (empty(self::$_instance))
		{
			self::$_instance = new static;
			self::$_instance['debug'] = true;
			$projectPath = null;

			// Загрузка настроек приложения из INI файлов рабочего окружения.
			self::$_instance->_options = ['' => '%'];
			$path = dirname(__DIR__);

			foreach (array('platform', 'application', getenv('ENVIRONMENT') ?: 'production') as $environment)
			{
				for ($flag = $ini = null, $old = $path; strlen($path) > 3 && is_readable($path); $path = dirname($path))
				{
					if ($flag = file_exists($ini = $path.DIRECTORY_SEPARATOR.$environment.'.ini'))
					{
						if (file_exists($path.DIRECTORY_SEPARATOR.'composer.json'))
						{
							$projectPath = $path;
						}

						break;
					}
				}

				if (!$flag && $environment === 'application')
				{
					$path = $old;
					continue;
				}

				if (!$flag || !is_readable($ini))
				{
					throw new RuntimeException("INI file $ini is not exists or we have not right to read it.");
				}

				foreach (parse_ini_file($ini, true) as $name => $section)
				{
					foreach ((array)$section as $key => $value)
					{
						self::$_instance->_options[is_string($key) ? "$name.$key" : $name] = $value;
					}
				}
			}

			// Корректировка относительных значений настроек.
			foreach (self::$_instance->getOptions('path') as $key => $value)
			{
				if (strlen($value) > 2 && $value[0] != '/' && $value[1] != ':')
				{
					self::$_instance->_options["path.$key"] = realpath($projectPath.DIRECTORY_SEPARATOR.$value);
				}
			}

			self::$_instance->_options['path.project'] = $projectPath;
			self::$_instance->_options['path.platform-cms'] = dirname(__DIR__);

			// Установка флага отладки приложения.
			self::$_instance['debug'] = !empty(self::$_instance->_options['debug']);
		}

		if ($callback)
		{
			if (self::$_disableCallback)
			{
				throw new RuntimeException('You can not use callback for getInstance after call newInstance method.');
			}

			self::$_callbacks[] = $callback;
			return $callback(self::$_instance);
		}

		return self::$_instance;
	}

	/**
	 * @return Application
	 */
	public static function newInstance()
	{
		$restore = self::$_instance;
		self::$_instance = null;
		self::$_disableCallback = true;

		$application = static::getInstance();

		foreach (self::$_callbacks as $callback)
		{
			$callback($application);
		}

		self::$_instance = $restore;
		return $application;
	}


	/**
	 * @param null|callable $callback
	 * @param null|callable $middleware
	 */
	public static function action(callable $callback = null, callable $middleware = null)
	{
		if (!self::$_actionFired)
		{
			self::$_actionFired = true;
			$app = static::getInstance();
			$context = new ArrayObject();

			header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0', true);
			header('Pragma: no-cache');
			header('Date: '.strtr(date(DateTime::RFC822), ['+0000' => 'GMT']));
			header('Expires: '.strtr(date(DateTime::RFC1123, time() - 24*3660), ['+0000' => 'GMT']));

			/** @var \Silex\ControllerCollection $controllers */
			$controllers = $app['controllers'];
			$collection = $controllers->flush();

			$middleware || $middleware = function($next) {
				return $next();
			};

			$app->match('/', function(Request $request) use ($app, $callback, $middleware, $context, $collection) {
				$app['routes']->addCollection($collection);

				return $middleware(function() use ($app, $request, $callback, $context) {
					$result = $callback ? $callback($app, $request, $context) : null;

					if ($result instanceof Response)
					{
						return $result;
					}

					if (is_string($result))
					{
						return new Response($result);
					}

					if (is_array($result))
					{
						foreach ($result as $key => $value)
						{
							$$key = $value;
						}
					}

					ob_start();
					unset($result, $request, $context);

					/** @noinspection PhpIncludeInspection */
					@include $_SERVER['SCRIPT_FILENAME'];

					return new Response(ob_get_clean());
				}, $context);
			});

			$app->run();
			exit();
		}
	}

	/**
	 * Convert some data into a JSON response.
	 *
	 * @param mixed $data    The response data
	 * @param int   $status  The response status code
	 * @param array $headers An array of response headers
	 *
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */
	public function json($data = array(), $status = 200, array $headers = array())
	{
		$response = parent::json($data, $status, $headers);

		$response->setEncodingOptions($response->getEncodingOptions() | JSON_UNESCAPED_UNICODE);

		return $response;
	}

	/**
	 * Generates an absolute URL from the given parameters.
	 *
	 * @param string $route      The name of the route
	 * @param mixed  $parameters An array of parameters
	 *
	 * @return string The generated URL
	 */
	public function url($route, $parameters = array())
	{
		if (empty($route))
		{
			return '';
		}

		if (strncmp($route, '/', 1) === 0 || strncmp($route, 'http:', 5) === 0)
		{
			return ($parameters && is_array($parameters))
				? Request::create($route, 'GET', $parameters)->getRequestUri()
				: $route;
		}

		if ($route == 'image')
		{
			$route = 'admin-image';
			empty($parameters['hash']) && $parameters['hash'] = '00000000000000000000000000000000';
			empty($parameters['format']) && $parameters['format'] = 'jpg';
			$parameters['salt'] = substr($parameters['hash'], -2);
		}

		/** @var \Moro\Platform\Model\Implementation\File\FileInterface $file */
		if ($route == 'download' && $file = isset($parameters['file']) ? $parameters['file'] : null)
		{
			$parameters['hash'] = $file->getHash();
			$parameters['salt'] = substr($parameters['hash'], -2);
			$parameters['extension'] = mb_strtolower($file->getName());
			$parameters['extension'] = substr($parameters['extension'], strrpos($parameters['extension'], '.') + 1);
			unset($parameters['file']);
		}

		try
		{
			if ($pos = strpos($route, '?'))
			{
				list($route, $query) = explode('?', $route, 2);
				parse_str($query, $temp);
				$parameters = array_merge($temp, $parameters);
			}

			if (!empty($parameters['roles']))
			{
				$user = empty($parameters['user']) ? $this->getServiceSecurityToken()->getUsername() : $parameters['user'];
				$roles = explode(',', $parameters['roles']);
				$counter = empty($parameters['counter']) ? null : (int)$parameters['counter'];
				$apiKeyEntity = $this->getServiceApiKey()->createEntityForUserAndTarget($user, $route, $roles, $counter);
				$parameters['apikey'] = $apiKeyEntity->getKey();
				unset($parameters['roles'], $parameters['user'], $parameters['counter']);
			}

			$url = $this['url_generator']->generate($route, $parameters, UrlGeneratorInterface::ABSOLUTE_URL);

			if (self::$_actionFired)
			{
				$url = preg_replace('{^(https?://[^/]+)?(/.*?\\.php)}', '$1', $url, 1);
				$url = preg_replace('{/admin/(index\\.php/)?}', '/admin/index.php/', $url, 1);
			}
		}
		catch (Exception $exception)
		{
			$message = $exception->getMessage().' in '.$exception->getFile().' ('.$exception->getLine().')';
			$this->getServiceLogger()->error(get_class($exception).': '.$message);
			return sprintf('#error: unknown route "'. $route.'" or bad arguments for it.');
		}

		if ($route == 'admin-image')
		{
			$uri = substr(substr($url, 0, strpos($url, '?') ?: strlen($url)), (strpos($url, 'index.php') ?: -9) + 9);
			$uri = preg_match('{^https?://[^/]+}', $uri, $match) ? substr($uri, strlen($match[0])) : $uri;
			$imagePath = $this->getOption('path.root').$uri;

			if (file_exists($imagePath) && intval($this->getOption('images.revision')) < $rev = filemtime($imagePath))
			{
				$url = (isset($match[0]) ? $match[0] : '').$uri.'?rev='.$rev;
			}
			elseif (!isset($parameters['remember']) || !empty($parameters['remember']))
			{
				$service = $this->getServiceRoutes();
				$entity = $service->getByRouteAndQuery($route, $parameters);
				$entity->setCompileFlag(2);
				$entity->setFile($uri);
				$service->commit($entity);
			}
		}

		if (false !== strpos($url, '/inner/') && strpos($url, '/inner/') < (strpos($url, '?') ?: strlen($url)))
		{
			$service = $this->getServiceRoutes();
			$entity = $service->getByRouteAndQuery($route, $parameters);
			$entity->setCompileFlag(2);
			$service->commit($entity);
		}

		return $url;
	}

	/**
	 * Maps a GET request to a callable.
	 *
	 * @param string $pattern Matched route pattern
	 * @param mixed  $to      Callback that returns the response when matched
	 *
	 * @return \Silex\Controller
	 */
	public function get($pattern, $to = null)
	{
		$controller = parent::get($pattern, $to);
		$controller->before(function(Request $request)
		{
			if (isset($this->_beforeAnyRequest))
			{
				foreach ($this->_beforeAnyRequest as $callback)
				{
					if ($result = $callback($request))
					{
						return $result;
					}
				}
			}

			return null;
		});
		$controller->after(function(Request $request, Response $response)
		{
			if (isset($this->_afterAnyRequest))
			{
				foreach ($this->_afterAnyRequest as $callback)
				{
					if ($result = $callback($request, $response))
					{
						return $result;
					}
				}
			}

			return null;
		});
		return $controller;
	}

	/**
	 * @param callable $callback
	 * @return $this
	 */
	public function afore(callable $callback)
	{
		$this->_beforeAnyRequest[] = $callback;
		return $this;
	}

	/**
	 * @param callable $callback
	 * @return $this
	 */
	public function behind(callable $callback)
	{
		$this->_afterAnyRequest[] = $callback;
		return $this;
	}

	/**
	 * Registers a finish filter.
	 *
	 * Finish filters are run after the response has been sent.
	 *
	 * @param mixed $callback Finish filter callback
	 * @param int   $priority The higher this value, the earlier an event
	 *                        listener will be triggered in the chain (defaults to 0)
	 * @return $this
	 */
	public function finish($callback, $priority = 0)
	{
		parent::finish($callback, $priority);
		return $this;
	}

	/**
	 * @param string $id
	 * @param string $class
	 * @param null|callable $callback
	 * @return $this
	 */
	public function update($id, $class, callable $callback = null)
	{
		is_string($class) && $this->offsetSet($id.'.class', $class);
		is_object($class) && $callback === null && $callback = $class;
		$callback && $this->offsetSet($id, $this->share($this->extend($id, $callback)));

		return $this;
	}

	/**
	 * Renders a view and returns a Response.
	 *
	 * To stream a view, pass an instance of StreamedResponse as a third argument.
	 *
	 * @param string   $view       The view name
	 * @param array    $parameters An array of parameters to pass to the view
	 * @param Response $response   A Response instance
	 *
	 * @return Response A Response instance
	 */
	public function render($view, array $parameters = array(), Response $response = null)
	{
		/** @var \Twig_Environment $twig */
		$twig = $this['twig'];

		if ($response instanceof StreamedResponse) {
			$response->setCallback(function () use ($twig, $view, $parameters) {
				$twig->display($view, $parameters);
			});
		} else {
			if (null === $response) {
				$response = new Response();
			}

			try
			{
				$original = isset($this['response']) ? $this['response'] : null;
				$this['response'] = $response;
				$response->setContent($twig->render($view, $parameters));
			}
			finally
			{
				$this['response'] = $original;
			}
		}

		return $response;
	}

	/**
	 * Renders a view.
	 *
	 * @param string $view       The view name
	 * @param array  $parameters An array of parameters to pass to the view
	 *
	 * @return string The rendered view
	 */
	public function renderView($view, array $parameters = array())
	{
		return $this['twig']->render($view, $parameters);
	}

	/**
	 * @param string $id
	 * @param null $default
	 * @return mixed
	 */
	public function offsetGet($id, $default = null)
	{
		if ($default === null)
		{
			return parent::offsetGet($id);
		}

		$result = null;

		if ($this->offsetExists($id))
		{
			$result = parent::offsetGet($id);
		}

		return ($result === null) ? $default : $result;
	}

	/**
	 * @return \Silex\ControllerCollection
	 */
	public function getServiceControllersFactory()
	{
		return $this->offsetGet(self::SERVICE_CONTROLLERS_FACTORY);
	}

	/**
	 * @return \Doctrine\DBAL\Connection
	 */
	public function getServiceDataBase()
	{
		return $this->offsetGet(self::SERVICE_DATABASE);
	}

	/**
	 * @return \Monolog\Logger
	 */
	public function getServiceLogger()
	{
		return $this->offsetGet(self::SERVICE_LOGGER);
	}

	/**
	 * @return \Symfony\Component\Security\Core\Authorization\AuthorizationChecker|null
	 */
	public function getServiceSecurityAcl()
	{
		if (php_sapi_name() == 'cli')
		{
			return null;
		}

		return $this->offsetGet(self::SERVICE_SECURITY_ACL);
	}

	/**
	 * @return \Symfony\Component\Security\Core\Authentication\Token\TokenInterface|null
	 */
	public function getServiceSecurityToken()
	{
		if (php_sapi_name() == 'cli')
		{
			return new AnonymousToken(self::PLATFORM_VERSION, 'cli', ['ROLE_ADMIN']);
		}

		/** @var \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage $service */
		$service = $this->offsetGet(self::SERVICE_SECURITY_TOKEN);
		return $service ? $service->getToken() : null;
	}

	/**
	 * @return \Moro\Platform\Model\Implementation\Options\ServiceOptions
	 */
	public function getServiceOptions()
	{
		return $this->offsetGet(self::SERVICE_OPTIONS);
	}

	/**
	 * @return \Saxulum\SaxulumBootstrapProvider\Session\FlashMessage
	 */
	public function getServiceFlash()
	{
		return $this->offsetGet(self::SERVICE_FLASH);
	}

	/**
	 * @return \Symfony\Component\Form\FormFactory
	 */
	public function getServiceFormFactory()
	{
		return $this->offsetGet(self::SERVICE_FORM_FACTORY);
	}

	/**
	 * @return \Knp\Menu\MenuFactory
	 */
	public function getServiceMenuFactory()
	{
		return $this->offsetGet(self::SERVICE_MENU_FACTORY);
	}

	/**
	 * @return \Moro\Platform\Model\Implementation\Routes\ServiceRoutes
	 */
	public function getServiceRoutes()
	{
		return $this->offsetGet(self::SERVICE_ROUTES);
	}

	/**
	 * @return \Moro\Platform\Model\Implementation\Content\ServiceContent
	 */
	public function getServiceContent()
	{
		return $this->offsetGet(self::SERVICE_CONTENT);
	}

	/**
	 * @return Model\Implementation\Content\ServiceContent|Model\Implementation\Content\Behavior\ChunksBehavior
	 */
	public function getServiceContentChunks()
	{
		return $this->offsetGet(self::SERVICE_CONTENT_CHUNKS);
	}

	/**
	 * @return \Moro\Platform\Model\Implementation\File\ServiceFile
	 */
	public function getServiceFile()
	{
		return $this->offsetGet(self::SERVICE_FILE);
	}

	/**
	 * @return \Imagine\Image\AbstractImagine
	 */
	public function getServiceImagine()
	{
		return $this->offsetGet(self::SERVICE_IMAGINE);
	}

	/**
	 * @return \Moro\Platform\Model\Implementation\Relink\ServiceRelink
	 */
	public function getServiceRelink()
	{
		return $this->offsetGet(self::SERVICE_RELINK);
	}

	/**
	 * @return \Moro\Platform\Model\Implementation\Tags\ServiceTags
	 */
	public function getServiceTags()
	{
		return $this->offsetGet(self::SERVICE_TAGS);
	}

	/**
	 * @return \Moro\Platform\Model\Implementation\ApiKey\ServiceApiKey
	 */
	public function getServiceApiKey()
	{
		return $this->offsetGet(self::SERVICE_API_KEY);
	}

	/**
	 * @return \Moro\Platform\Tools\Relink
	 */
	public function getServiceRelinkTool()
	{
		return $this->offsetGet(self::SERVICE_RELINK_TOOL);
	}

	/**
	 * @return \Raven_Client|null
	 */
	public function getServiceSentry()
	{
		return $this->offsetExists(self::SERVICE_SENTRY) ? $this->offsetGet(self::SERVICE_SENTRY) : null;
	}

	/**
	 * @return \Moro\Platform\Model\Implementation\History\ServiceHistory
	 */
	public function getServiceHistory()
	{
		return $this->offsetGet(self::SERVICE_HISTORY);
	}

	/**
	 * @return \Moro\Platform\Tools\DiffMatchPatch
	 */
	public function getServiceDiffMatchPatch()
	{
		return $this->offsetGet(self::SERVICE_DIFF_MATCH_PATCH);
	}

	/**
	 * @return \Moro\Platform\Model\Implementation\Users\ServiceUsers
	 */
	public function getServiceUsers()
	{
		return $this->offsetGet(self::SERVICE_USERS);
	}

	/**
	 * @return \Moro\Platform\Model\Implementation\Users\Auth\ServiceUsersAuth
	 */
	public function getServiceUsersAuth()
	{
		return $this->offsetGet(self::SERVICE_USERS_AUTH);
	}

	/**
	 * @return \Swift_Mailer
	 */
	public function getServiceMailer()
	{
		return $this->offsetGet(self::SERVICE_MAILER);
	}

	/**
	 * @return \Moro\Platform\Model\Implementation\Subscribers\ServiceSubscribers
	 */
	public function getServiceSubscribers()
	{
		return $this->offsetGet(self::SERVICE_SUBSCRIBERS);
	}

	/**
	 * @return \Moro\Platform\Model\Implementation\Messages\ServiceMessages
	 */
	public function getServiceMessages()
	{
		return $this->offsetGet(self::SERVICE_MESSAGES);
	}

	/**
	 * @return \Moro\Platform\Model\Accessory\Parameters\Tags\TagsServiceBehavior
	 */
	public function getBehaviorTags()
	{
		return $this->offsetGet(self::BEHAVIOR_TAGS);
	}

	/**
	 * @return \Moro\Platform\Model\Accessory\Heading\HeadingBehavior
	 */
	public function getBehaviorHeadings()
	{
		return $this->offsetGet(self::BEHAVIOR_HEADINGS);
	}

	/**
	 * @return \Moro\Platform\Model\Accessory\HistoryBehavior
	 */
	public function getBehaviorHistory()
	{
		return $this->offsetGet(self::BEHAVIOR_HISTORY);
	}

	/**
	 * @return \Moro\Platform\Model\Accessory\ClientRoleBehavior
	 */
	public function getBehaviorClientRole()
	{
		return $this->offsetGet(self::BEHAVIOR_CLIENT_ROLE);
	}

	/**
	 * @return \Moro\Platform\Model\Implementation\Content\Behavior\ChunksBehavior
	 */
	public function getBehaviorContentChunks()
	{
		return $this->offsetGet(self::BEHAVIOR_CONTENT_CHUNKS);
	}

	/**
	 * @return \Moro\Platform\Provider\Twig\MarkdownExtension
	 */
	public function getTwigExtensionMarkdown()
	{
		return $this->offsetGet(self::TWIG_EXTENSION_MARKDOWN);
	}
}