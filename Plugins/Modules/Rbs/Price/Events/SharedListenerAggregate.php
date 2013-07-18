<?php
namespace Rbs\Price\Events;

use Rbs\Price\Http\Rest\Actions\TaxInfo;
use Zend\EventManager\SharedEventManagerInterface;

/**
 * @name \Rbs\Price\Events\SharedListenerAggregate
 */
class SharedListenerAggregate implements \Zend\EventManager\SharedListenerAggregateInterface
{
	/**
	 * Attach one or more listeners
	 * Implementors may add an optional $priority argument; the SharedEventManager
	 * implementation will pass this to the aggregate.
	 * @param SharedEventManagerInterface $events
	 */
	public function attachShared(SharedEventManagerInterface $events)
	{
		$this->registerCollections($events);
		$events->attach('Http.Rest', 'http.action', array($this, 'registerActions'));
	}

	public function registerActions(\Change\Http\Event $event)
	{

		if (!$event->getAction() && implode('/', $event->getParam('pathParts')) === 'rbs/price/taxInfo')
		{
			$event->setAction(function($event){
				(new TaxInfo())->execute($event);
			});
		}
	}

	/**
	 * Detach all previously attached listeners
	 * @param SharedEventManagerInterface $events
	 */
	public function detachShared(SharedEventManagerInterface $events)
	{
		// TODO: Implement detachShared() method.
	}

	/**
	 * @param SharedEventManagerInterface $events
	 */
	protected function registerCollections(SharedEventManagerInterface $events)
	{
		$callback = function (\Zend\EventManager\Event $event)
		{
			$collection = null;
			switch ($event->getParam('code'))
			{
				case 'Rbs_Price_Collection_BillingAreasForShop':
					$collection = new \Rbs\Price\Collection\BillingAreasForShopCollection($event->getParam('documentServices'), $event->getParam('shopId'));
					break;
				case 'Rbs_Price_Collection_Iso4217':
					$collection =  new \Rbs\Price\Collection\Iso4217Collection();
					break;
				case 'Rbs_Price_Collection_TaxRoundingStrategy':
					$collection = new \Rbs\Price\Collection\TaxRoundingStrategyCollection($event->getParam('documentServices'));
					break;
				default:
					break;
			}
			if ($collection)
			{
				$event->setParam('collection', $collection);
			}
		};
		$events->attach('CollectionManager', 'getCollection', $callback, 5);

		$callback = function (\Zend\EventManager\Event $event)
		{
			$codes = $event->getParam('codes');
			$codes[] = 'Rbs_Price_Collection_BillingAreasForShop';
			$codes[] = 'Rbs_Price_Collection_Iso4217';
			$codes[] = 'Rbs_Price_Collection_TaxRoundingStrategy';
			$event->setParam('codes', $codes);
		};
		$events->attach('CollectionManager', 'getCodes', $callback, 1);
	}
}