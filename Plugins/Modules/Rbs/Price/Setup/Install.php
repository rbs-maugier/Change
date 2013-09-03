<?php
namespace Rbs\Price\Setup;

/**
 * @name \Rbs\Price\Setup\Install
 */
class Install
{
	/**
	 * @param \Change\Plugins\Plugin $plugin
	 * @param \Change\Application\ApplicationServices $applicationServices
	 * @param \Change\Documents\DocumentServices $documentServices
	 * @param \Change\Presentation\PresentationServices $presentationServices
	 * @throws \RuntimeException
	 */
	public function executeServices($plugin, $applicationServices, $documentServices, $presentationServices)
	{
		$this->importDefaultTaxes($documentServices);
	}

	/**
	 * @param \Change\Plugins\Plugin $plugin
	 */
	public function finalize($plugin)
	{
		$plugin->setConfigurationEntry('locked', true);
	}

	/**
	 * @param \Change\Documents\DocumentServices $documentServices
	 */
	protected function importDefaultTaxes(\Change\Documents\DocumentServices $documentServices)
	{
		$tm = $documentServices->getApplicationServices()->getTransactionManager();
		try
		{
			$tm->begin();
			$query = new \Change\Documents\Query\Query($documentServices, 'Rbs_Price_Tax');
			$query->andPredicates($query->eq('code', 'GST'));
			$doc = $query->getFirstDocument();
			if (!$doc)
			{
				$GST = $documentServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Price_Tax');
				/* @var $GST \Rbs\Price\Documents\Tax */
				$data = \Zend\Json\Json::decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'Assets' . DIRECTORY_SEPARATOR
				. 'GST.json'), \Zend\Json\Json::TYPE_ARRAY);
				$GST->setLabel('Goods and Services Tax (CANADA)');
				$GST->setCode('GST');
				$GST->setDefaultZone($data[\Rbs\Price\Documents\Tax::ZONES_KEY][0]);
				$GST->setData($data);
				$GST->save();
			}

			$query = new \Change\Documents\Query\Query($documentServices, 'Rbs_Price_Tax');
			$query->andPredicates($query->eq('code', 'PST'));
			$doc = $query->getFirstDocument();
			if (!$doc)
			{
				$PST = $documentServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Price_Tax');
				/* @var $PST \Rbs\Price\Documents\Tax */
				$data = \Zend\Json\Json::decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'Assets' . DIRECTORY_SEPARATOR
				. 'PST.json'), \Zend\Json\Json::TYPE_ARRAY);
				$PST->setLabel('Provincial Sales Taxes (CANADA)');
				$PST->setCode('PST');
				$PST->setDefaultZone($data[\Rbs\Price\Documents\Tax::ZONES_KEY][0]);
				$PST->setData($data);
				$PST->save();
			}

			$query = new \Change\Documents\Query\Query($documentServices, 'Rbs_Price_Tax');
			$query->andPredicates($query->eq('code', 'HST'));
			$doc = $query->getFirstDocument();
			if (!$doc)
			{
				$HST = $documentServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Price_Tax');
				/* @var $HST \Rbs\Price\Documents\Tax */
				$data = \Zend\Json\Json::decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'Assets' . DIRECTORY_SEPARATOR
				. 'HST.json'), \Zend\Json\Json::TYPE_ARRAY);
				$HST->setLabel('Harmonized Sales Tax (CANADA)');
				$HST->setCode('HST');
				$HST->setDefaultZone($data[\Rbs\Price\Documents\Tax::ZONES_KEY][0]);
				$HST->setData($data);
				$HST->save();
			}

			$query = new \Change\Documents\Query\Query($documentServices, 'Rbs_Price_Tax');
			$query->andPredicates($query->eq('code', 'QST'));
			$doc = $query->getFirstDocument();
			if (!$doc)
			{
				$QST = $documentServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Price_Tax');
				/* @var $QST \Rbs\Price\Documents\Tax */
				$data = \Zend\Json\Json::decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'Assets' . DIRECTORY_SEPARATOR
				. 'QST.json'), \Zend\Json\Json::TYPE_ARRAY);
				$QST->setLabel('Quebec Sales Tax (CANADA)');
				$QST->setCode('QST');
				$QST->setDefaultZone($data[\Rbs\Price\Documents\Tax::ZONES_KEY][0]);
				$QST->setData($data);
				$QST->save();
			}

			$query = new \Change\Documents\Query\Query($documentServices, 'Rbs_Price_Tax');
			$query->andPredicates($query->eq('code', 'TVAFR'));
			$doc = $query->getFirstDocument();
			if (!$doc)
			{
				$QST = $documentServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Price_Tax');
				/* @var $QST \Rbs\Price\Documents\Tax */
				$data = \Zend\Json\Json::decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'Assets' . DIRECTORY_SEPARATOR
				. 'FRC.json'), \Zend\Json\Json::TYPE_ARRAY);
				$QST->setLabel('Taxe sur la valeur ajoutée (FRANCE CONTINENTALE)');
				$QST->setCode('TVAFR');
				$QST->setDefaultZone($data[\Rbs\Price\Documents\Tax::ZONES_KEY][0]);
				$QST->setData($data);
				$QST->save();
			}

			$i18nManager = $documentServices->getApplicationServices()->getI18nManager();
			$cm = new \Change\Collection\CollectionManager();
			$cm->setDocumentServices($documentServices);
			$taxTitle = $cm->getCollection('Rbs_Price_Collection_TaxTitle');

			if ($taxTitle === null)
			{
				/* @var $taxTitle \Rbs\Collection\Documents\Collection */
				$taxTitle = $documentServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Collection_Collection');
				$taxTitle->setLocked(true);
				$taxTitle->setLabel('Tax Title');
				$taxTitle->setCode('Rbs_Price_Collection_TaxTitle');


				/* @var $title \Rbs\Collection\Documents\Item */
				$title = $documentServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Collection_Item');
				$title->setValue('GST');
				$title->setLabel('Goods and Services Tax');
				$title->getCurrentLocalization()->setTitle($i18nManager->trans('m.rbs.price.setup.gst'));
				$title->create();
				$taxTitle->getItems()->add($title);

				/* @var $title \Rbs\Collection\Documents\Item */
				$title = $documentServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Collection_Item');
				$title->setValue('PST');
				$title->setLabel('Provincial Sales Taxes (CANADA)');
				$title->getCurrentLocalization()->setTitle($i18nManager->trans('m.rbs.price.setup.pst'));
				$title->create();
				$taxTitle->getItems()->add($title);

				/* @var $title \Rbs\Collection\Documents\Item */
				$title = $documentServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Collection_Item');
				$title->setValue('HST');
				$title->setLabel('Harmonized Sales Tax (CANADA)');
				$title->getCurrentLocalization()->setTitle($i18nManager->trans('m.rbs.price.setup.hst'));
				$title->create();
				$taxTitle->getItems()->add($title);

				/* @var $title \Rbs\Collection\Documents\Item */
				$title = $documentServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Collection_Item');
				$title->setValue('QST');
				$title->setLabel('Quebec Sales Tax (CANADA)');
				$title->getCurrentLocalization()->setTitle($i18nManager->trans('m.rbs.price.setup.qst'));
				$title->create();
				$taxTitle->getItems()->add($title);

				/* @var $title \Rbs\Collection\Documents\Item */
				$title = $documentServices->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Collection_Item');
				$title->setValue('TVAFR');
				$title->setLabel('Taxe sur la valeur ajoutée');
				$title->getCurrentLocalization()->setTitle($i18nManager->trans('m.rbs.price.setup.tvafr'));
				$title->create();
				$taxTitle->getItems()->add($title);

				$taxTitle->create();
			}

			$tm->commit();
		}
		catch (\Exception $e)
		{
			$documentServices->getApplicationServices()->getLogging()->exception($e);
			$tm->rollBack($e);
		}
	}
}
