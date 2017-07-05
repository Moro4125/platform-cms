<?php
/**
 * Class ImagineServiceProvider
 */
namespace Moro\Platform\Provider;
use \Pimple\ServiceProviderInterface;
use \Pimple\Container;
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
	 * @param \Moro\Platform\Application|Container $app
	 */
	public function register(Container $app)
	{
		$app[Application::SERVICE_IMAGINE] = function() {
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
		};
	}
}