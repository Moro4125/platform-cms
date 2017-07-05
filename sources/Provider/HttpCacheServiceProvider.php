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
use \Pimple\ServiceProviderInterface;
use \Pimple\Container;
use \Silex\Provider\HttpCache\HttpCache;
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
    public function register(Container $app)
    {
        $app['http_cache'] = function ($app) {
            $app['http_cache.options'] = array_replace(
                array(
                    'debug' => $app['debug'],
                ), $app['http_cache.options']
            );

            return new HttpCache($app, $app['http_cache.store'], $app['http_cache.ssi'], $app['http_cache.options']);
        };

        $app['http_cache.ssi'] = function() {
            return new Ssi();
        };

        $app['http_cache.store'] = function ($app) {
            return new Store($app['http_cache.cache_dir']);
        };

        $app['http_cache.ssi_listener'] = function ($app) {
            return new SurrogateListener($app['http_cache.ssi']);
        };

        $app['http_cache.options'] = array();
    }
}
