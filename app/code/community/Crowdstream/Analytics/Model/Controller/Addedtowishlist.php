<?php
class Crowdstream_Analytics_Model_Controller_Addedtowishlist extends Crowdstream_Analytics_Model_Controller_Base
{
    public function getBlock($block)
    {
        $params = $block->getParams();
        $info   = Mage::getModel('catalog/product_api')
        ->info($params['product_id']);
        
        $info = Mage::helper('crowdstream_analytics')
        ->getNormalizedProductInformation($info); 
        
        $want = array('sku', 'price', 'name');
        foreach($info as $key=>$value)
        {
            if(!in_array($key, $want)){continue;}
            $params[$key] = $value;
        }

        $block->setParams($params);
        return $block;
    }
}