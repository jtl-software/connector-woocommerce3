<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers\GlobalData;

use jtl\Connector\Model\Identity;
use jtl\Connector\Model\ShippingClass as ShippingClassModel;
use JtlWooCommerceConnector\Controllers\Traits\PullTrait;
use JtlWooCommerceConnector\Controllers\Traits\PushTrait;
use JtlWooCommerceConnector\Logger\WpErrorLogger;

class ShippingClass
{
    use PullTrait, PushTrait;
    
    const TERM_TAXONOMY = 'product_shipping_class';
    
    public function pullData()
    {
        $shippingClasses = [];
        
        foreach (\WC()->shipping()->get_shipping_classes() as $shippingClass) {
            $shippingClasses[] = (new ShippingClassModel())
                ->setId(new Identity($shippingClass->term_id))
                ->setName($shippingClass->name);
        }
        
        return $shippingClasses;
    }
    
    public function pushData(array $shippingClasses)
    {
        foreach ($shippingClasses as $shippingClass) {
            $term = \get_term_by('name', $shippingClass->getName(), self::TERM_TAXONOMY, OBJECT);
            
            if ($term === false) {
                $result = \wp_insert_term($shippingClass->getName(), self::TERM_TAXONOMY);
                
                if ($result instanceof \WP_Error) {
                    WpErrorLogger::getInstance()->logError($result);
                    continue;
                }
                
                $shippingClass->getId()->setEndpoint($result['term_id']);
            } else {
                $shippingClass->getId()->setEndpoint($term->term_id);
            }
        }
        
        return $shippingClasses;
    }
}
