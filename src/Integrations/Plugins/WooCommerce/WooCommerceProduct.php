<?php

namespace JtlWooCommerceConnector\Integrations\Plugins\WooCommerce;

use jtl\Connector\Model\Product;
use jtl\Connector\Model\ProductI18n as ProductI18nModel;
use JtlWooCommerceConnector\Integrations\Plugins\AbstractComponent;
use JtlWooCommerceConnector\Integrations\Plugins\Germanized\Germanized;
use JtlWooCommerceConnector\Integrations\Plugins\YoastSeo\YoastSeo;
use JtlWooCommerceConnector\Logger\WpErrorLogger;
use DateTime;
use JtlWooCommerceConnector\Utilities\Config;

/**
 * Class WooCommerceProduct
 * @package JtlWooCommerceConnector\Integrations\Plugins\WooCommerce
 */
class WooCommerceProduct extends AbstractComponent
{
    /**
     * @param int $wcProductId
     * @param string $masterProductId
     * @param Product $product
     * @param ProductI18nModel $defaultI18n
     * @return int|null
     * @throws \Exception
     */
    public function saveProduct(int $wcProductId, string $masterProductId, Product $product, ProductI18nModel $defaultI18n): ?int
    {
        $creationDate = is_null($product->getAvailableFrom()) ? $product->getCreationDate() : $product->getAvailableFrom();

        if (!$creationDate instanceof DateTime) {
            $creationDate = new DateTime();
        }

        $isMasterProduct = empty($masterProductId);

        /** @var ProductI18nModel $defaultI18n */
        $endpoint = [
            'ID' => $wcProductId,
            'post_type' => $isMasterProduct ? 'product' : 'product_variation',
            'post_title' => $defaultI18n->getName(),
            'post_name' => $defaultI18n->getUrlPath(),
            'post_content' => $defaultI18n->getDescription(),
            'post_excerpt' => $defaultI18n->getShortDescription(),
            'post_date' => $this->getCreationDate($creationDate),
            'post_status' => is_null($product->getAvailableFrom()) ? ($product->getIsActive() ? 'publish' : 'draft') : 'future',
        ];

        if ($endpoint['ID'] !== 0) {
            // Needs to be set for existing products otherwise commenting is disabled
            $endpoint['comment_status'] = \get_post_field('comment_status', $endpoint['ID']);
        }

        // Post filtering
        remove_filter('content_save_pre', 'wp_filter_post_kses');
        remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');
        $newPostId = \wp_insert_post($endpoint, true);
        // Post filtering
        add_filter('content_save_pre', 'wp_filter_post_kses');
        add_filter('content_filtered_save_pre', 'wp_filter_post_kses');

        if ($newPostId instanceof \WP_Error) {
            WpErrorLogger::getInstance()->logError($newPostId);
            return null;
        }

        return (int)$newPostId;
    }


    /**
     * @param DateTime $creationDate
     * @param bool $gmt
     * @return string|null
     */
    private function getCreationDate(DateTime $creationDate, $gmt = false)
    {
        if (is_null($creationDate)) {
            return null;
        }

        if ($gmt) {
            $shopTimeZone = new \DateTimeZone(\wc_timezone_string());
            $creationDate->sub(date_interval_create_from_date_string($shopTimeZone->getOffset($creationDate) / 3600 . ' hours'));
        }

        return $creationDate->format('Y-m-d H:i:s');
    }

    /**
     * @param \WC_Product $wcProduct
     * @param Product $jtlProduct
     * @param string $languageIso
     * @return ProductI18nModel
     * @throws \Exception
     */
    public function getI18ns(\WC_Product $wcProduct, Product $jtlProduct, string $languageIso): ProductI18nModel
    {
        $i18n = (new ProductI18nModel())
            ->setProductId($jtlProduct->getId())
            ->setLanguageISO($languageIso)
            ->setName($this->name($wcProduct))
            ->setDescription(html_entity_decode($wcProduct->get_description()))
            ->setShortDescription(html_entity_decode($wcProduct->get_short_description()))
            ->setUrlPath($wcProduct->get_slug());

        $germanized = $this->getPluginsManager()->get(Germanized::class);
        if ($germanized->canBeUsed() && $germanized->hasUnitProduct($wcProduct)) {
            $i18n->setMeasurementUnitName($germanized->getUnit($wcProduct));
        }

        $yoastSeo = $this->getPluginsManager()->get(YoastSeo::class);
        if ($yoastSeo->canBeUsed()) {
            $tmpMeta = $yoastSeo->findProductSeoData($wcProduct);
            if (!empty($tmpMeta) && count($tmpMeta) > 0) {
                $i18n->setMetaDescription(is_array($tmpMeta['metaDesc']) ? '' : $tmpMeta['metaDesc'])
                    ->setMetaKeywords(is_array($tmpMeta['keywords']) ? '' : $tmpMeta['keywords'])
                    ->setTitleTag(is_array($tmpMeta['titleTag']) ? '' : $tmpMeta['titleTag'])
                    ->setUrlPath(is_array($tmpMeta['permlink']) ? '' : $tmpMeta['permlink']);
            }
        }

        return $i18n;
    }


    /**
     * @param \WC_Product $product
     * @return string
     */
    private function name(\WC_Product $product): string
    {
        $name = html_entity_decode($product->get_name());
        if ($product instanceof \WC_Product_Variation) {
            switch (\get_option(Config::OPTIONS_VARIATION_NAME_FORMAT, '')) {
                case 'space':
                    $name = $product->get_name() . ' ' . \wc_get_formatted_variation($product, true);
                    break;
                case 'brackets':
                    $name = sprintf('%s (%s)', $product->get_name(), \wc_get_formatted_variation($product, true));
                    break;
                case 'space_parent':
                    $parent = \wc_get_product($product->get_parent_id());
                    $name = $parent->get_title() . ' ' . \wc_get_formatted_variation($product, true);
                    break;
                case 'brackets_parent':
                    $parent = \wc_get_product($product->get_parent_id());
                    $name = sprintf('%s (%s)', $parent->get_title(), \wc_get_formatted_variation($product, true));
                    break;
            }
        }

        return $name;
    }
}
