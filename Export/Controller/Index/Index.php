<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Custom\Export\Controller\Index;
use Magento\Framework\App\Action\Context;
class Index extends \Magento\Framework\App\Action\Action
{
  public function __construct(
       Context $context,
       \Magento\Framework\Filesystem\Io\File $io,
       \Magento\Framework\App\Filesystem\DirectoryList $directory_list
  ) {
    
       $this->_file = $io;
       $this->directory_list = $directory_list;
    parent::__construct($context);
  }
  
  	/**
     * Index action
     *
     * @return $this
     */
  public function execute()
  {
       $heading = [
           __('ID'),
           __('productname'),
           __('producturl'),
           __('productimage'),
           __('price'),
           __('category'),
           __('brand'),
           __('stock_status')
       ];
      
      $outputFile = "ListProducts.csv";
      $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); 
      $csvPath = $this->directory_list->getPath('media')."/products";
      if (!is_dir($csvPath)) {
      	$ioAdapter = $this->_file;
      	$ioAdapter->mkdir($csvPath, 0775);
      }
      
      $handle = fopen($outputFile, 'w');
      fputcsv($handle, $heading);
      $productCollection = $objectManager->create('\Magento\Catalog\Model\Product')->getCollection()->addAttributeToFilter('visibility', ['neq' =>1]);
    
      foreach ($productCollection as $product) {
        	$categories = $product->getCategoryIds();
         	$product_category = '';
        	$brand_name = '';
         	foreach($categories as $category){
            	$cat = $objectManager->create('Magento\Catalog\Model\Category')->load($category);
           		$product_category = $cat->getName();
          	}
        
        	$productobj = $objectManager->create('\Magento\Catalog\Model\ProductRepository')->getById($product->getId());
            if($productobj->getBrandId()!=null){
              $brand = $objectManager->create('\Magenest\ShopByBrand\Model\Brand')->load($productobj->getBrandId());
              $brand_name = $brand->getName();
            }else{
              $brand_name = '';
            }
        
            if($productobj->isSaleable()!=null){
              $stock_status = $productobj->isSaleable();
            }else{
              $stock_status = 0;
            }
        
        	$image_path = $objectManager->get('\Magento\Store\Model\StoreManagerInterface')->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA)."catalog/product".$productobj->getImage();
  			if($productobj->getFinalPrice()!=0){
               $row = [
                 $product->getId(),
                 $productobj->getName(),
                 $product->getProductUrl(),
                 $image_path,
                 $productobj->getFinalPrice(),
                 $product_category,
                 $brand_name,
                 $stock_status
            	];
            }
            fputcsv($handle, $row);
       }

      $ioAdapter = $this->_file;
      $fileName = 'ListProducts.csv';
      $csvContent = file_get_contents($outputFile);
      $ioAdapter->open(array('path'=>$csvPath));
	  $ioAdapter->write($fileName, $csvContent, 0666);
      
      if (file_exists($csvPath.'/'.$outputFile)) {
           //set appropriate headers
           header('Content-Description: File Transfer');
           header('Content-Type: application/csv');
           header('Content-Disposition: attachment; filename='.basename($csvPath.'/'.$outputFile));
           header('Expires: 0');
           header('Cache-Control: must-revalidate');
           header('Pragma: public');
           header('Content-Length: ' . filesize($csvPath.'/'.$outputFile));
           ob_clean();flush();
           readfile($csvPath.'/'.$outputFile);
       }
  }
}
