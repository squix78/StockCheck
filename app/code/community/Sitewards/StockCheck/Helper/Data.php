<?php
/**
 *
 * @category    Sitewards
 * @package     Sitewards_StockCheck
 * @copyright   Copyright (c) 2013 Sitewards GmbH (http://www.sitewards.com/)
 *
 * This class is used to help the Sitewards_StockCheck_Model_Product class
 */
class Sitewards_StockCheck_Helper_Data extends Mage_Core_Helper_Abstract
	implements Sitewards_StockCheck_Helper_Interface
{
	/**
	 * Green stock product status id
	 *
	 * @var int
	 */
	protected $iStockGreenId;

	/**
	 * Yellow stock product status id
	 *
	 * @var int
	 */
	protected $iStockYellowId;

	/**
	 * Red stock product status id
	 *
	 * @var int
	 */
	protected $iStockRedId;

	/**
	 * Off stock product status id
	 *
	 * @var int
	 */
	protected $iStockOffId;

	/**
	 * Define helper properties
	 */
	public function __construct() {
		$this->iStockGreenId	= Mage::getStoreConfig('stockcheck_config/stockcheck_img/stock_green_id');
		$this->iStockYellowId	= Mage::getStoreConfig('stockcheck_config/stockcheck_img/stock_yellow_id');
		$this->iStockRedId		= Mage::getStoreConfig('stockcheck_config/stockcheck_img/stock_red_id');
		$this->iStockOffId		= Mage::getStoreConfig('stockcheck_config/stockcheck_img/stock_off_id');
	}

	/**
	 *
	 * @param	int|string $mProductSku unique identifier for a product - defaults to Magento ProductId
	 * @return	int real time stock level
	 */
	public function getCustomQuantity($mProductSku) {
		$storageType = $this->getStorageType($mProductSku);
		switch($storageType) {
			case $this->iStockOffId:
				return 0;
				break;
			case $this->iStockRedId:
				return 0;
				break;
			case $this->iStockYellowId:
				return 1;
				break;
			case $this->iStockGreenId:
				return 100;
				break;
		}
	}

	/**
	 *
	 * @param	int|string $mProductSku unique identifier for a product - defaults to Magento ProductId
	 * @return	int constant related to real time stock level
	 */
	public function getStorageType($mProductSku) {
		$messages = $this->getAggregatedStockLevel($mProductSku);
		$nbInStock = 0;
		$nbRestock = 0;
		$nbSoldOut = 0;
		foreach($messages as $key => $value) {
			if (strpos($value, 'Sold out') !== false) {
				$nbSoldOut++;
			} else if (strpos($value, 'Sold out') !== false) {
				$nbRestock++;
		  } else if (strpos($value, 'In stock') !== false) {
				$nbInStock++;
			}
		}
		if ($nbSoldOut > 0) {
			return $this->iStockOffId;
		} else if ($nbRestock > 0) {
			return $this->iStockYellowId;
		} else {
			$this->iStockGreenId;
		}
		return 1;
	}

	/**
	 *
	 * @param	int|string $mProductSku unique identifier for a product - defaults to Magento ProductId
	 * @return	int current amount of stock taking into account the items on order
	 */
	public function getProductsStockOrder($mProductSku) {
		return $this->getCustomQuantity($mProductSku);
	}

	public function getAggregatedStockLevel($skuList) {
		 $skus = explode(",", $skuList);
		 $messages = array();
		 foreach($skus as $pair) {
			 $ids = explode("|", $pair);
			 $message = $this->getBanggoodStockLevelBySku($ids[0], $ids[1]);
			 $messages[$message] = $message;
		 }
		 return $messages;
	}

	public function getBanggoodStockLevelBySku($sku, $id) {
		$cacheKey = "Banggood_".$sku;
		$cache = Mage::app()->getCache();
		$value = $cache->load($cacheKey);
		if (!$value) {
			$jsonUrl = "http://www.banggood.com/index.php?com=product&t=stockMessage&sku=".$sku."&warehouse=CN&products_id=".$id."&noneShipment=undefined&getCurWarehouse=1";
	    Mage::log($jsonUrl);
			$jsonfile = file_get_contents($jsonUrl);
			$decoded = json_decode($jsonfile);
			//Mage::log($sku.": ".print_R($decoded,TRUE));
			Mage::log($sku.": ".$decoded->message);
			$value = $decoded->message;
			$cache->save($value, $cacheKey, array("banggoodStock"), 60 * 60 * 12);
		} else {
			Mage::log("From cache: ".$sku.": ".$value);
		}
		return $value;

	}
}
