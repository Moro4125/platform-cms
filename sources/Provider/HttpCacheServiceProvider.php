<?php
/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Moro\Platform\Provider;
use \Silex\Application;
use \Silex\ServiceProviderInterface;
use \Silex\HttpCache;
use \Symfony\Component\HttpKernel\HttpCache\Ssi;
use \Symfony\Component\HttpKernel\HttpCache\Store;
use \Symfony\Component\HttpKernel\EventListener\SurrogateListener;

/**
 * Symfony HttpKernel component Provider for HTTP cache (changed from ESI to SSI).
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class HttpCacheServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['http_cache'] = $app->share(function ($app) {
            $app['http_cache.options'] = array_replace(
                array(
                    'debug' => $app['debug'],
                ), $app['http_cache.options']
            );

            return new HttpCache($app, $app['http_cache.store'], $app['http_cache.ssi'], $app['http_cache.options']);
        });

        $app['http_cache.ssi'] = $app->share(function() {
            return new Ssi();
        });

        $app['http_cache.store'] = $app->share(function ($app) {
            return new Store($app['http_cache.cache_dir']);
        });

        $app['http_cache.ssi_listener'] = $app->share(function ($app) {
            return new SurrogateListener($app['http_cache.ssi']);
        });

        $app['http_cache.options'] = array();
    }

    public function boot(Application $app)
    {
        //$app['dispatcher']->addSubscriber($app['http_cache.ssi_listener']);
    }
}
