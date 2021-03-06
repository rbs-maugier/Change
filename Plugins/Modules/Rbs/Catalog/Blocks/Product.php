<?php
namespace Rbs\Catalog\Blocks;

use Change\Presentation\Blocks\Event;
use Change\Presentation\Blocks\Parameters;
use Change\Presentation\Blocks\Standard\Block;

/**
 * @name \Rbs\Catalog\Blocks\Product
 */
class Product extends Block
{
	/**
	 * Event Params 'website', 'document', 'page'
	 * @api
	 * Set Block Parameters on $event
	 * @param Event $event
	 * @return Parameters
	 */
	protected function parameterize($event)
	{
		$parameters = parent::parameterize($event);
		$parameters->addParameterMeta('productId');
		$parameters->addParameterMeta('webStoreId');
		$parameters->addParameterMeta('activateZoom', true);
		$parameters->addParameterMeta('attributesDisplayMode', 'table');
		$parameters->addParameterMeta('displayPrices');
		$parameters->addParameterMeta('displayPricesWithTax');

		$parameters->setLayoutParameters($event->getBlockLayout());
		if ($parameters->getParameter('productId') === null)
		{
			$document = $event->getParam('document');
			if ($document instanceof \Rbs\Catalog\Documents\Product)
			{
				$parameters->setParameterValue('productId', $document->getId());
			}
		}

		/* @var $commerceServices \Rbs\Commerce\CommerceServices */
		$commerceServices = $event->getServices('commerceServices');
		$webStore = $commerceServices->getContext()->getWebStore();
		if ($webStore)
		{
			$parameters->setParameterValue('webStoreId', $webStore->getId());
			if ($parameters->getParameter('displayPrices') === null)
			{
				$parameters->setParameterValue('displayPrices', $webStore->getDisplayPrices());
				$parameters->setParameterValue('displayPricesWithTax', $webStore->getDisplayPricesWithTax());
			}
		}
		else
		{
			$parameters->setParameterValue('webStoreId', 0);
			$parameters->setParameterValue('displayPrices', false);
			$parameters->setParameterValue('displayPricesWithTax', false);
		}

		return $parameters;
	}

	/**
	 * Set $attributes and return a twig template file name OR set HtmlCallback on result
	 * @param Event $event
	 * @param \ArrayObject $attributes
	 * @return string|null
	 */
	protected function execute($event, $attributes)
	{
		$parameters = $event->getBlockParameters();
		$productId = $parameters->getParameter('productId');
		if ($productId)
		{
			/* @var $commerceServices \Rbs\Commerce\CommerceServices */
			$commerceServices = $event->getServices('commerceServices');
			$documentManager = $event->getApplicationServices()->getDocumentManager();

			/* @var $product \Rbs\Catalog\Documents\Product */
			$product = $documentManager->getDocumentInstance($productId);
			if ($product instanceof \Rbs\Catalog\Documents\Product)
			{
				$attributes['product'] = $product;
				$attributes['canonicalUrl'] = $event->getUrlManager()->getCanonicalByDocument($product)->toString();

				// Cart line configs.
				$productPresentation = $product->getPresentation($commerceServices, $parameters->getParameter('webStoreId'));
				if ($productPresentation)
				{
					$productPresentation->evaluate();
					$attributes['productPresentation'] = $productPresentation;
				}
				$attributes['attributesConfig'] = $commerceServices->getAttributeManager()->getProductAttributesConfiguration('specifications', $product);

				if ($product->getVariantGroup())
				{
					$axesValues = $product->getVariantGroup()->getAxesValuesByParentId($product->getId());
					$attributes['axesValues'] = \Zend\Json\Json::encode($axesValues);
					$axesNames = $product->getVariantGroup()->getAxesNames();
					$attributes['axesNames'] = \Zend\Json\Json::encode($axesNames);
					$attributes['axesCount'] = count($product->getVariantGroup()->getAxesInfo());
					return 'product-variant.twig';
				}
				else
				{
					return 'product.twig';
				}
			}
		}
		return null;
	}
}