<?php
namespace Rbs\Admin\Http\Actions;

use Change\Http\Event;
use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\Admin\Http\Actions\GetHtmlFragment
 */
class GetHtmlFragment
{
	/**
	 * Use Required Event Params: resourcePath
	 * @param Event $event
	 */
	public function execute($event)
	{
		$resourcePath = $event->getParam('resourcePath');
		$result = new \Rbs\Admin\Http\Result\Renderer();

		$filePath = $this->getFilePath($resourcePath, $event->getApplicationServices()->getPluginManager());
		if ($filePath !== null)
		{

			$fileResource = new \Change\Presentation\Themes\FileResource($filePath);
			if ($fileResource->isValid())
			{
				$md = $fileResource->getModificationDate();
				$result->setHeaderLastModified($md);
				$ifModifiedSince = $event->getRequest()->getIfModifiedSince();
				if ($ifModifiedSince && $ifModifiedSince == $md)
				{
					$result->setHttpStatusCode(HttpResponse::STATUS_CODE_304);
					$result->setRenderer(function ()
					{
						return null;
					});
				}
				else
				{
					$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);

					/* @var $manager \Rbs\Admin\Manager */
					$manager = $event->getParam('manager');

					$attributes = array('query' => $event->getRequest()->getQuery()->toArray());
					$renderer = function () use ($filePath, $manager, $attributes)
					{
						return $manager->renderTemplateFile($filePath, $attributes);
					};
					$result->setRenderer($renderer);
				}

				$event->setResult($result);
				return;
			}
		}

		$result->setHttpStatusCode(HttpResponse::STATUS_CODE_404);
		$result->setRenderer(function ()
		{
			return null;
		});
		$event->setResult($result);
	}

	/**
	 * @param string $resourcePath
	 * @param \Change\Plugins\PluginManager $pluginManager
	 * @return string
	 */
	protected function getFilePath($resourcePath, $pluginManager)
	{
		$parts = explode('/', $resourcePath);
		if (count($parts) > 2)
		{
			$vendor = array_shift($parts);
			$shortModuleName = array_shift($parts);
			$plugin = $pluginManager->getModule($vendor, $shortModuleName);
			if ($plugin && $plugin->isAvailable())
			{
				if ($vendor === 'Rbs' && $shortModuleName === 'Admin')
				{
					return $plugin->getAssetsPath() . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $parts);
				}
				else
				{
					return $plugin->getAssetsPath() . DIRECTORY_SEPARATOR . 'Admin' . DIRECTORY_SEPARATOR .
						implode(DIRECTORY_SEPARATOR, $parts);
				}
			}
		}
		return null;
	}
}