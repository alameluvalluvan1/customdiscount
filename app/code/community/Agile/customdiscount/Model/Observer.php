<?php
/**
 * @category   Sugarcode
 * @package    Sugarcode_Customdiscount
 * @author     pradeep.kumarrcs67@gmail.com
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Agile_Customdiscount_Model_Observer
{

 public function setDiscount($observer)
    {
       
       //echo "setDiscount" ;
       
       $cheapestEnable =  Mage::getStoreConfig("checkout/cheapest_free/isactive") ;
      
       $buyMinimum =  Mage::getStoreConfig("checkout/cheapest_free/buyminimum") ;
       
      
       $quote=$observer->getEvent()->getQuote();
       $quoteid=$quote->getId();
       
       $quoteAllItems = $observer->getEvent()->getQuote()->getAllItems();       	 
	   $totQt = 0 ; //$cnt = 0 ; 
	   $freeCnt = 0 ;
	   
					
       $totalItems = $observer->getEvent()->getQuote()->getItemsCount();
       $totalQuantity = $observer->getEvent()->getQuote()->getItemsQty();
       
       
       $freeQty = Mage::getStoreConfig("checkout/cheapest_free/getfree") ;
       $discountCategory = Mage::getStoreConfig("checkout/cheapest_free/discategory") ;
       
       
       
       $ValidCount = 0 ; $cartQty = 0 ;
       foreach($quoteAllItems as $singleItem) {
             $product = Mage::getModel('catalog/product')->load($singleItem->getProductId());								
		     $category_ids = $product->getCategoryIds();
			 if (in_array($discountCategory, $category_ids)) {
				 $ValidCount = $ValidCount + $singleItem->getQty() ;
				 
			   }
       }  
  
       $cartQty = $buyMinimum + $freeQty ;
       
       if($cartQty <= $ValidCount && $cheapestEnable == 1 ) {
						  	   
						   $disAmount = 0 ;
						   foreach($quoteAllItems as $singleItem) {
								 //echo 'ID----: '.$singleItem->getProductId().'<br />';
								 $product = Mage::getModel('catalog/product')->load($singleItem->getProductId());
								 $category_ids = $product->getCategoryIds();
								 //print_r($category_ids) ;
								  $categoryExists = true ;
								 
								 if (!in_array($discountCategory, $category_ids)) 
								       $categoryExists = false ;	
								 
								 $productPrice = $singleItem->getPrice() ;
								 $_itQty = $singleItem->getQty();
								 if($productPrice != 0) {	// added for configurable products
										 if($categoryExists == true) {
										    if($freeQty < $_itQty)
										        $disQty = $freeQty ;
										    else 
										        $disQty = $_itQty ;      
										    
										    $disAmount += $disQty * $singleItem->getPrice() ;
										    $freeCnt = $freeCnt + $disQty ; 
										    if($freeCnt == $freeQty) 
										      break ;
										    
									 }  // for category exists
								  }						 
							}
						 
						   $discountAmount=$disAmount;
	 }
	else
	   $discountAmount = 0 ;
	   
    if($quoteid) {
        if($discountAmount>0) {
        $total=$quote->getBaseSubtotal();
            $quote->setSubtotal(0);
            $quote->setBaseSubtotal(0);

            $quote->setSubtotalWithDiscount(0);
            $quote->setBaseSubtotalWithDiscount(0);

            $quote->setGrandTotal(0);
            $quote->setBaseGrandTotal(0);
        
             
            $canAddItems = $quote->isVirtual()? ('billing') : ('shipping');    
            foreach ($quote->getAllAddresses() as $address) {
                
            $address->setSubtotal(0);
            $address->setBaseSubtotal(0);

            $address->setGrandTotal(0);
            $address->setBaseGrandTotal(0);

            $address->collectTotals();

            $quote->setSubtotal((float) $quote->getSubtotal() + $address->getSubtotal());
            $quote->setBaseSubtotal((float) $quote->getBaseSubtotal() + $address->getBaseSubtotal());

            $quote->setSubtotalWithDiscount(
                (float) $quote->getSubtotalWithDiscount() + $address->getSubtotalWithDiscount()
            );
            $quote->setBaseSubtotalWithDiscount(
                (float) $quote->getBaseSubtotalWithDiscount() + $address->getBaseSubtotalWithDiscount()
            );

            $quote->setGrandTotal((float) $quote->getGrandTotal() + $address->getGrandTotal());
            $quote->setBaseGrandTotal((float) $quote->getBaseGrandTotal() + $address->getBaseGrandTotal());
    
            $quote ->save(); 
    
               $quote->setGrandTotal($quote->getBaseSubtotal()-$discountAmount)
               ->setBaseGrandTotal($quote->getBaseSubtotal()-$discountAmount)
               ->setSubtotalWithDiscount($quote->getBaseSubtotal()-$discountAmount)
               ->setBaseSubtotalWithDiscount($quote->getBaseSubtotal()-$discountAmount)
               ->save(); 
               
                
                if($address->getAddressType()==$canAddItems) {
                //echo $address->setDiscountAmount; exit;
                    $address->setSubtotalWithDiscount((float) $address->getSubtotalWithDiscount()-$discountAmount);
                    $address->setGrandTotal((float) $address->getGrandTotal()-$discountAmount);
                    $address->setBaseSubtotalWithDiscount((float) $address->getBaseSubtotalWithDiscount()-$discountAmount);
                    $address->setBaseGrandTotal((float) $address->getBaseGrandTotal()-$discountAmount);
                    
                    /*
                    if($address->getDiscountDescription()){
                    $address->setDiscountAmount(-($address->getDiscountAmount()-$discountAmount));
                    $address->setDiscountDescription($address->getDiscountDescription().', Cheapest Free');
                    $address->setBaseDiscountAmount(-($address->getBaseDiscountAmount()-$discountAmount));
                    }else {
                    
                    
                    */
                    if($address->getDiscountAmount()){
                    $address->setDiscountAmount(-($address->getDiscountAmount()-$discountAmount));
                    	if($address->getDiscountDescription())
                    		$address->setDiscountDescription($address->getDiscountDescription().', Custom Discount');
                        else
                           	$address->setDiscountDescription('Cheapest Free');	
                           	
                    $address->setBaseDiscountAmount(-($address->getBaseDiscountAmount()-$discountAmount));
                    }else {
                    
                    
                    $address->setDiscountAmount(-($discountAmount));
                    $address->setDiscountDescription('Free');
                    $address->setBaseDiscountAmount(-($discountAmount));
                    }
                    $address->save();
                }//end: if
            } //end: foreach
            //echo $quote->getGrandTotal();
        
        foreach($quote->getAllItems() as $item){
                 //We apply discount amount based on the ratio between the GrandTotal and the RowTotal
                 $rat=$item->getPriceInclTax()/$total;
                 $ratdisc=$discountAmount*$rat;
                 $item->setDiscountAmount(($item->getDiscountAmount()+$ratdisc) * $item->getQty());
                 $item->setBaseDiscountAmount(($item->getBaseDiscountAmount()+$ratdisc) * $item->getQty())->save();
                
               }
            
                
            }
            
    }
 }

}
