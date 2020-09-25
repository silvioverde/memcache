<?php 

namespace Silvio\Memcache;

use Memcache;
use RuntimeException;

class MemcacheConnector {

	/**
	 * Create a new Memcache connection.
	 *
	 * @param  array  $servers
	 * @return \Memcache
	 *
	 * @throws \RuntimeException
	 */
	public function connect(array $servers)
	{
		$memcache = $this->getMemcache();

		// Agrega los servers a la conexión de Memcache.
		foreach ($servers as $server)
		{
			$memcache->addServer(
				$server['host'], $server['port'], $server['weight']
			);
		}

		if ($memcache->getVersion() === false)
		{
			throw new RuntimeException("No se puede establecer la conexión con Memcache.");
		}

		return $memcache;
	}

	/**
	 * Get a new Memcached instance.
	 *
	 * @return \Memcache
	 */
	protected function getMemcache()
	{
		return new Memcache;
	}

}
