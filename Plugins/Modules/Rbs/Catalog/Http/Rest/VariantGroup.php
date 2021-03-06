<?php
namespace Rbs\Catalog\Http\Rest;

use Change\Http\Event;
use Change\Http\Rest\Result\CollectionResult;
use Change\Http\Rest\Result\DocumentLink;
use Change\Http\Rest\Result\Link;
use Zend\Http\Response as HttpResponse;

/**
* @name \Rbs\Catalog\Http\Rest\VariantGroup
*/
class VariantGroup
{
	/**
	 * @param Event $event
	 */
	public function getProducts(Event $event)
	{
		$document = $event->getApplicationServices()->getDocumentManager()->getDocumentInstance($event->getParam('documentId'));
		$queryData = null;
		if ($document instanceof \Rbs\Catalog\Documents\VariantGroup)
		{
			$urlManager = $event->getUrlManager();
			$result = new CollectionResult();
			$selfLink = new Link($urlManager, $event->getRequest()->getPath());
			$result->addLink($selfLink);
			$result->setOffset(0);
			$result->setLimit(null);
			$result->setSort(null);
			$ids = array();

			foreach ($document->getProductMatrixInfo() as $pmi)
			{
				if (!$pmi['variant'])
				{
					$ids[] = $pmi['id'];
				}
			}

			if (count($ids))
			{
				$query = $event->getApplicationServices()->getDocumentManager()->getNewQuery('Rbs_Catalog_Product');
				$query->andPredicates($query->eq('variantGroup', $document), $query->in('id', $ids));
				$collection = $query->getDocuments();
				foreach ($collection as $document)
				{
					$result->addResource(new DocumentLink($urlManager, $document, DocumentLink::MODE_PROPERTY, array('sku')));
				}
			}
			$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
			$event->setResult($result);
		}
	}
}