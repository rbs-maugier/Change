<?php

class TaxManagerTest extends \ChangeTests\Change\TestAssets\TestCase
{

	/**
	 * @var \Rbs\Commerce\CommerceServices;
	 */
	protected $commerceServices;

	protected function setUp()
	{
		parent::setUp();
		$this->commerceServices = new \Rbs\Commerce\CommerceServices($this->getApplication(), $this->getEventManagerFactory(), $this->getApplicationServices());
		$this->getEventManagerFactory()->addSharedService('commerceServices', $this->commerceServices);
		$this->commerceServices->getContext()->setZone('FR');
		$ba = new TestBillingArea();
		$ba->taxes[] = new TestTax('TVA', array('c1' => array('FR' => 0.2)));
		$ba->taxes[] = new TestTax('TVB', array('c1' => array('FR' => 0.1)), true);
		$ba->taxes[] = new TestTax('TVC', array('c1' => array('FR' => 0.05)));
		$this->commerceServices->getContext()->setBillingArea($ba);
	}

	public function testGetManager()
	{
		$cs = $this->commerceServices;
		$taxManager = $cs->getTaxManager();
		$this->assertInstanceOf('\Rbs\Price\Services\TaxManager', $taxManager);
	}

	public function testGetTaxByValue()
	{
		$cs = $this->commerceServices;
		$taxManager = $cs->getTaxManager();

		/* @var $array \Rbs\Price\Std\TaxApplication[] */
		$array = $taxManager->getTaxByValue(100, array('TVA' => 'c1'));
		$this->assertCount(1, $array);
		$taxApplication =  $array[0];
		$this->assertInstanceOf('\Rbs\Price\Std\TaxApplication', $taxApplication);
		$this->assertEquals(20, $taxApplication->getValue());


		$array = $taxManager->getTaxByValue(100, array('TVA' => 'c2'));
		$this->assertCount(1, $array);
		$taxApplication =  $array[0];
		$this->assertInstanceOf('\Rbs\Price\Std\TaxApplication', $taxApplication);
		$this->assertEquals(0, $taxApplication->getValue());

		$array = $taxManager->getTaxByValue(100, array('TVA' => 'c1', 'TVB' => 'c1'));
		$this->assertCount(2, $array);
		$this->assertEquals(20, $array[0]->getValue());
		$this->assertEquals(12, $array[1]->getValue());

		$array = $taxManager->getTaxByValue(100, array('TVB' => 'c1', 'TVA' => 'c1'));
		$this->assertCount(2, $array);
		$this->assertEquals(20, $array[0]->getValue());
		$this->assertEquals(12, $array[1]->getValue());

		$array = $taxManager->getTaxByValue(100, array('TVB' => 'c1', 'TVC' => 'c1', 'TVA' => 'c1'));
		$this->assertCount(3, $array);
		$this->assertEquals(20, $array[0]->getValue());
		$this->assertEquals(12, $array[1]->getValue());
		$this->assertEquals(5, $array[2]->getValue());
	}

	public function testGetTaxByValueWithTax()
	{
		$cs = $this->commerceServices;
		$taxManager = $cs->getTaxManager();
		/* @var $array \Rbs\Price\Std\TaxApplication[] */
		$array = $taxManager->getTaxByValueWithTax(120, array('TVA' => 'c1'));
		$this->assertCount(1, $array);
		$taxApplication =  $array[0];
		$this->assertInstanceOf('\Rbs\Price\Std\TaxApplication', $taxApplication);
		$this->assertEquals(20, $taxApplication->getValue());


		$array = $taxManager->getTaxByValueWithTax(132, array('TVB' => 'c1', 'TVA' => 'c1'));
		$this->assertCount(2, $array);
		$this->assertEquals(20, $array[0]->getValue());
		$this->assertEquals(12, $array[1]->getValue());

		$array = $taxManager->getTaxByValueWithTax(137, array('TVB' => 'c1', 'TVC' => 'c1', 'TVA' => 'c1'));
		$this->assertCount(3, $array);
		$this->assertEquals(20, $array[0]->getValue());
		$this->assertEquals(12, $array[1]->getValue());
		$this->assertEquals(5, $array[2]->getValue());
	}
}


class TestTax implements \Rbs\Commerce\Interfaces\Tax
{

	public $code;

	public $rates = array();

	public $cascading;

	public $rounding;

	/**
	 * @param string $code
	 * @param array $rates
	 * @param boolean $cascading
	 * @param string $rounding
	 */
	function __construct($code, $rates, $cascading = false, $rounding = 't')
	{
		$this->code = $code;
		$this->rates = $rates;
		$this->cascading = $cascading;
		$this->rounding = $rounding;
	}

	/**
	 * @return string
	 */
	public function getCode()
	{
		return $this->code;
	}

	/**
	 * @param string $category
	 * @param string $zone
	 * @return float
	 */
	public function getRate($category, $zone)
	{
		return isset($this->rates[$category][$zone]) ? $this->rates[$category][$zone] : 0;
	}

	/**
	 * @return boolean
	 */
	public function getCascading()
	{
		return $this->cascading;
	}

	/**
	 * Return t => total, l => row, u => unit
	 * @return string
	 */
	public function getRounding()
	{
		return $this->rounding;
	}
}

class TestBillingArea implements \Rbs\Commerce\Interfaces\BillingArea
{
	/**
	 * @var string
	 */
	public $currencyCode = 'EUR';

	/**
	 * @var TestTax[];
	 */
	public $taxes = array();
	/**
	 * @return string
	 */
	public function getCurrencyCode()
	{
		return $this->currencyCode;
	}

	/**
	 * @return TestTax[];
	 */
	public function getTaxes()
	{
		return $this->taxes;
	}

	/**
	 * @return string
	 */
	public function getCode()
	{
		return 'BA';
	}
}