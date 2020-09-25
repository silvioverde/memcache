<?php 

namespace Silvio\Memcache;

use Illuminate\Cache\Repository;
use Illuminate\Cache\CacheManager;
use Illuminate\Session\SessionManager;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;

class MemcacheServiceProvider extends ServiceProvider {

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('memcache', function($app)
        {
            $memcache = new MemcacheConnector;

            $servers = $this->app['config']['cache.stores.memcached.servers'];
            return $memcache->connect($servers);
        });

        $this->app->singleton('memcache.store', function($app)
        {
            $prefix = $this->app['config']['cache.prefix'];

            return new Repository(new MemcacheStore($app['memcache'], $prefix));
        });

        $this->app->singleton('memcache.driver', function($app)
        {
            $minutes = $this->app['config']['session.lifetime'];
            return new MemcacheHandler($app['memcache.store'], $minutes);
        });
    }

    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        /** @var \Illuminate\Contracts\Config\Repository $config */
        $config = $this->app['config'];

        // Si config cache está configurado como 'Memcache', utiliza la misma configuración que Memcached.
        if ($config['cache.default'] == 'memcache') {
            
            $config->set('cache.stores.memcache.driver', 'memcache');
            $servers = $config['cache.stores.memcached.servers'];
            $config->set('cache.stores.memcache.servers', $servers);

            $this->extendCache($this->app);
        }

        if ($config['session.driver'] == 'memcache') {
            $this->extendSession($this->app);
        }
    }

    /**
     * Agrega Memcache a cache manager.
     *
     * @param \Illuminate\Contracts\Foundation\Application  $app
     */
    public function extendCache(Application $app)
    {
        $app->resolving('cache', function(CacheManager $cache) {
            $cache->extend('memcache', function ($app) {
                return $app['memcache.store'];
            });
        });
    }

    /**
     * Agrega Memcache a session manager.
     *
     * @param \Illuminate\Contracts\Foundation\Application  $app
     */
    public function extendSession(Application $app)
    {
        $app->resolving('session', function(SessionManager $session) {
            $session->extend('memcache', function ($app) {
                return $app['memcache.driver'];
            });
        });
    }

    /**
     * @return array
     */
    public function provides()
    {
        return ['memcache', 'memcache.store', 'memcache.driver'];
    }
}
