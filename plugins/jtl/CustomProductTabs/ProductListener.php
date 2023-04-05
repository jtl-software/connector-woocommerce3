<?php

namespace jtl\CustomProductTabs;

use jtl\Connector\Event\Product\ProductAfterPushEvent;
use jtl\Connector\Model\ProductAttrI18n;
use JtlWooCommerceConnector\Utilities\Util;

/**
 * Class ProductListener
 * @package jtl\CustomProductTabs
 */
class ProductListener
{
    public const
        PRODUCT_CUSTOM_TABS_ATTRIBUTE_NEEDLE = 'product_custom_tabs',
        PRODUCT_CUSTOM_TABS_META_KEY         = 'yikes_woo_products_tabs';

    /**
     * @param ProductAfterPushEvent $event
     * @return void
     */
    public function onProductAfterPush(ProductAfterPushEvent $event): void
    {
        $product           = $event->getProduct();
        $productAttributes = $product->getAttributes();
        $customProductTabs = [];
        foreach ($productAttributes as $productAttribute) {
            if (!\is_null($customProductTab = $this->findCustomProductTabAttribute(...$productAttribute->getI18ns()))) {
                $customProductTabs[] = [
                    'title' => $customProductTab->getTitle(),
                    'id' => $customProductTab->getId(),
                    'content' => $customProductTab->getContent(),
                ];
            }
        }

        if (!empty($customProductTabs)) {
            Util::getInstance()->updatePostMeta(
                $product->getId()->getEndpoint(),
                self::PRODUCT_CUSTOM_TABS_META_KEY,
                $customProductTabs
            );
        }
    }

    /**
     * @param ProductAttrI18n ...$productAttributeI18ns
     * @return CustomProductTab|null
     */
    protected function findCustomProductTabAttribute(ProductAttrI18n ...$productAttributeI18ns): ?CustomProductTab
    {
        $customProductTab = null;
        foreach ($productAttributeI18ns as $productAttributeI18n) {
            if (Util::getInstance()->isWooCommerceLanguage($productAttributeI18n->getLanguageISO())) {
                if (
                    \strpos(
                        $productAttributeI18n->getName(),
                        self::PRODUCT_CUSTOM_TABS_ATTRIBUTE_NEEDLE
                    ) !== false
                ) {
                    list($needle, $title) = \explode(':', $productAttributeI18n->getName());
                    if (!empty($title)) {
                        $customProductTab = new CustomProductTab($title, $productAttributeI18n->getValue());
                        break;
                    }
                }
            }
        }
        return $customProductTab;
    }
}
