<?php
/**
 * Class ImagineServiceProvider
 */
namespace Moro\Platform\Provider;
use \Silex\ServiceProviderInterface;
use \Silex\Application as CApplication;
use \Imagine\Gd\Imagine as GdImagine;
use \Imagine\Gmagick\Imagine as GmagickImagine;
use \Imagine\Imagick\Imagine as ImagickImagine;
use \Moro\Platform\Application;
use \RuntimeException;

/**
 * Class ImagineServiceProvider
 * @package Provider
 */
class ImagineServiceProvider implements ServiceProviderInterface
{
	/**
	 * Registers services on the given app.
	 *
	 * This method should only be used to configure services and parameters.
	 * It should not get services.
	 *
	 * @param \Moro\Platform\Application|CApplication $app
	 */
	public function register(CApplication $app)
	{
		$app[Application::SERVICE_IMAGINE] = $app->share(function() {
			if (extension_loaded('gd'))
			{
				return new GdImagine();
			}

			if (extension_loaded('imagick'))
			{
				return new ImagickImagine();
			}

			if (extension_loaded('gmagick'))
			{
				return new GmagickImagine();
			}

			throw new RuntimeException('Imagine require PHP image extension (GD2 or Imagick or Gmagick).');
		});
	}

	/**
	 * Bootstraps the application.
	 *
	 * This method is called after all services are registered
	 * and should be used for "dynamic" configuration (whenever
	 * a service must be requested).
	 *
	 * @param Application|CApplication $app
	 */
	public function boot(CApplication $app)
	{
	}
}