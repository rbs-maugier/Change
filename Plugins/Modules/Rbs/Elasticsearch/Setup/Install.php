<?php
namespace Rbs\Elasticsearch\Setup;

/**
 * @name \Rbs\Elasticsearch\Setup\Install
 */
class Install extends \Change\Plugins\InstallBase
{
	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
	public function finalize($plugin)
	{
		$plugin->setConfigurationEntry('locked', true);
	}
}
