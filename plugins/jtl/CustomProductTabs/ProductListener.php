<?php

namespace jtl\CustomProductTabs;

use Jtl\Connector\Core\Event\ProductEvent;
use Jtl\Connector\Core\Model\TranslatableAttributeI18n;
use JtlWooCommerceConnector\Utilities\Util;
use Nette\Utils\RegexpException;

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
     * @var Util
     */
    protected $util;

    /**
     * @param Util $util
     */
    public function __construct(Util $util)
    {
        $this->util = $util;
    }

    /**
     * @param ProductEvent $event
     * @return void
     * @throws RegexpException
     */
    public function onProductAfterPush(ProductEvent $event): void
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
            $this->util->updatePostMeta(
                $product->getId()->getEndpoint(),
                self::PRODUCT_CUSTOM_TABS_META_KEY,
                $customProductTabs
            );
        }
    }

    /**
     * @param TranslatableAttributeI18n ...$productAttributeI18ns
     * @return CustomProductTab|null
     * @throws RegexpException
     */
    protected function findCustomProductTabAttribute(
        TranslatableAttributeI18n ...$productAttributeI18ns
    ): ?CustomProductTab {
        $customProductTab = null;
        foreach ($productAttributeI18ns as $productAttributeI18n) {
            if ($this->util->isWooCommerceLanguage($productAttributeI18n->getLanguageISO())) {
                if (
                    \str_contains($productAttributeI18n->getName(), self::PRODUCT_CUSTOM_TABS_ATTRIBUTE_NEEDLE)
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
