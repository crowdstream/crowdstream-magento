<?php
class Crowdstream_Analytics_Model_Observer
{
    const CONTAINER_BLOCKNAME = 'crowdstream_analytics_before_body_end';
    
    public function addContainerBlock($observer)
    {
        #Mage::Log($_SERVER['SCRIPT_URI']);
        #Mage::Log("Start addContainerBlocks");
        $layout = Mage::getSingleton('core/layout');
        if(!$layout)
        {
            Mage::Log("No Layout Object in " . __METHOD__);
            return;
        }
        
        $before_body_end = $layout->getBlock('before_body_end');
        if(!$before_body_end)
        {
            Mage::Log("No before body end in " . __METHOD__);
            return;
        }
        
        if(!Mage::helper('crowdstream_analytics')->isEnabled())
        {
            return;
        }
        
        $container = $layout->createBlock('core/text_list', self::CONTAINER_BLOCKNAME);
        $before_body_end->append($container);
        $front = Crowdstream_Analytics_Model_Front_Controller::getInstance();           
        $blocks = $front->getBlocks();
        foreach($blocks as $block)
        {
            if(!$block) { continue; }
            $items = $block = is_array($block) ? $block : array($block);
            
            foreach($items as $block)
            {
                $container->append($block);
            }
        }
        Mage::Log("Finished addContainerBlocks");
    }

    /**
    * Adds the "always" items
    */    
    public function addFrontendScripts($observer)
    {
        $layout = $observer->getLayout();
        if(!$layout)
        {
            return;
        }   

        if(!Mage::helper('crowdstream_analytics')->isEnabled())
        {
            return;
        }        
        
        $front = Crowdstream_Analytics_Model_Front_Controller::getInstance();        
        $front->addAction('init');
        $front->addAction('page');
    }

    public function loggedIn($observer)    
    {
        $front = Crowdstream_Analytics_Model_Front_Controller::getInstance();
        $front->addDeferredAction('identity');
    }

    public function identify($observer)    
    {
        $front = Crowdstream_Analytics_Model_Front_Controller::getInstance();
        $front->addAction('identity');
    }    
    
    public function loggedOut($observer)    
    {
        $front = Crowdstream_Analytics_Model_Front_Controller::getInstance();            
        $front->addDeferredAction('customerloggedout', array(
            'customer' => $this->_getCustomerData()
        ));
    }  
    
    public function addToCart($observer)
    {
        $product = $observer->getProduct();
        $front   = Crowdstream_Analytics_Model_Front_Controller::getInstance();
        $front->addDeferredAction('addtocart', array(
            'params' => array('id' => $product->getIdBySku($product->getSku()))
        ));
    }
    
    public function removeFromCart($observer)
    {
        $product = $observer->getQuoteItem()->getProduct();
        $front   = Crowdstream_Analytics_Model_Front_Controller::getInstance();            
        $front->addDeferredAction('removefromcart', array(
            'params' => array('id' => $product->getIdBySku($product->getSku()))
        ));
    }
    
    public function customerRegistered($observer)
    {
        $customer = $observer->getCustomer();
        $front    = Crowdstream_Analytics_Model_Front_Controller::getInstance();            
        $front->addDeferredAction('customerregistered',
            array('customer_id'=>$customer->getEntityId())
        );            
    }
    
    public function loadedSearch($observer)
    {
        $o = $observer->getDataObject();
        if(!$o){return;}
        $front = Crowdstream_Analytics_Model_Front_Controller::getInstance();            
        $front->addDeferredAction('searchedproducts',
            array('query'=>$o->getQueryText())
        );                    
    }
    
    public function categoryViewForFiltering($observer)
    {
        $action = $observer->getAction();
        if(!$action){ return; }
        
        $request = $action->getRequest();
        if(!$request) { return; }
        
        $params = $request->getParams();
        
        //use presense of "dir" to flag for filtering. 
        //no need for an action handle check
        if(!array_key_exists('dir', $params))
        {
            return;
        }
        
        $front = Crowdstream_Analytics_Model_Front_Controller::getInstance();            
        $front->addDeferredAction('filteredproducts',
            array('params'=>$params)
        );                    
        
    }
    
    public function productView($observer)
    {
        $action = $observer->getAction();
        if(!$action){ return; }
        
        $request = $action->getRequest();
        if(!$request) { return; }
        
        $params = $request->getParams();
        
        if (!in_array($action->getFullActionName(), array('catalog_product_view')))
        {
            return;
        }    
        
        $front = Crowdstream_Analytics_Model_Front_Controller::getInstance();            
        $front->addDeferredAction('viewedproduct',
            array('params'=>$params)
        );          
    }
    
    public function favSaved($observer)
    {
        $front  = Crowdstream_Analytics_Model_Front_Controller::getInstance();            
        $item   = $observer->getData('data_object');
        
        if($item->getResourceName() == 'amlist/item')
        {
            $front->addDeferredAction('amlistfav',
                array('product_id'=>$item->getData('product_id'))
            );          
        }
    }
    
    public function reviewView($observer)
    {
        $action = $observer->getAction();
        if(!$action){ return; }
        
        $request = $action->getRequest();
        if(!$request) { return; }
        
        $params = $request->getParams();
        
        if (!in_array($action->getFullActionName(), array('review_product_list')))
        {
            return;
        }
        
        $front = Crowdstream_Analytics_Model_Front_Controller::getInstance();            
        $front->addDeferredAction('viewedreviews',
            array('params'=>$params)
        );  
        
    }
    
    public function newsletterSubscriber($observer)
    {
        $subscriber = $observer->getDataObject();
        if(!$subscriber->getSubscriberStatus())
        {
            return;
        }

        $front = Crowdstream_Analytics_Model_Front_Controller::getInstance();
        $front->addDeferredAction('subscribenewsletter',
            array('subscriber'=>$subscriber->getData())
        );
    }
    
    public function wishlistAddProduct($observer)
    {
        $product  = $observer->getProduct();
        $wishlist = $observer->getWishlist();
        
        $front      = Crowdstream_Analytics_Model_Front_Controller::getInstance();            
        $front->addDeferredAction('addedtowishlist',
            array('params'=>array('product_id'=>$product->getId()))
        );          

    }

    public function orderPlaced($observer)
    {
        $front      = Crowdstream_Analytics_Model_Front_Controller::getInstance();            
        $front->addDeferredAction('orderplaced',
            array('params'=>array(
                'order_id'=>$observer->getOrder()->getId(),
                'increment_id'=>$observer->getOrder()->getIncrementId(),
            ))
        );      
    }
    
    protected function _getCustomer()
    {
        $customer = Mage::getSingleton('customer/session')->getCustomer();            
        
        //pull entire customer, including eav attributes not initially populated
        $full_customer = Mage::getModel('customer/customer')->getCollection()
        ->addAttributeToSelect('*')->addFieldToFilter('entity_id', $customer->getId())
        ->getFirstItem();
                
        return $full_customer;
    }        
    
    protected function _getCustomerData()
    {
        $customer = $this->_getCustomer();
        if($customer)
        {
            $customer = Mage::helper('crowdstream_analytics')->getNormalizedCustomerInformation($customer->getData());
            return $customer;
        }
        return array();
    }
}