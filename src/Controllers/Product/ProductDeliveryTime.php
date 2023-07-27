<?php

/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers\Product;

use jtl\Connector\Model\Product as ProductModel;
use JtlWooCommerceConnector\Controllers\BaseController;
use JtlWooCommerceConnector\Logger\WpErrorLogger;
use JtlWooCommerceConnector\Utilities\Config;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use JtlWooCommerceConnector\Utilities\Util;
use WP_Error;

class ProductDeliveryTime extends BaseController
{
    /**
     * @param ProductModel $product
     * @param \WC_Product $wcProduct
     * @return void
     * @throws \Exception
     */
    public function pushData(ProductModel $product, \WC_Product $wcProduct): void
    {
        $productId                          = $product->getId()->getEndpoint();
        $time                               = $product->calculateHandlingTime();
        $germanizedDeliveryTimeTaxonomyName = 'product_delivery_time';

        $this->removeDeliveryTimeTerm($productId);
        $this->removeDeliveryTimeTerm($productId, $germanizedDeliveryTimeTaxonomyName);

        if (Config::get(Config::OPTIONS_USE_DELIVERYTIME_CALC) !== 'deactivated') {
            //FUNCTION ATTRIBUTE BY JTL
            $offset           = 0;
            $pushedAttributes = $product->getAttributes();
            foreach ($pushedAttributes as $key => $pushedAttribute) {
                foreach ($pushedAttribute->getI18ns() as $i18n) {
                    if (!Util::getInstance()->isWooCommerceLanguage($i18n->getLanguageISO())) {
                        continue;
                    }

                    if (\preg_match('/^(wc_)[a-zA-Z\_]+$/', \trim($i18n->getName()))) {
                        if (\strcmp(\trim($i18n->getName()), 'wc_dt_offset') === 0) {
                            $offset = (int)\trim($i18n->getValue());
                        }
                    }
                    unset($pushedAttributes[$key]);
                }
            }

            if (Config::get(Config::OPTIONS_CONSIDER_SUPPLIER_INFLOW_DATE, false)) {
                if (
                    $product->getStockLevel()->getStockLevel() <= 0
                    && !\is_null($product->getNextAvailableInflowDate())
                ) {
                    $inflow = new \DateTime($product->getNextAvailableInflowDate()->format('Y-m-d'));
                    $today  = new \DateTime((new \DateTime())->format('Y-m-d'));
                    if ($inflow->getTimestamp() - $today->getTimestamp() > 0) {
                        $time = $product->getAdditionalHandlingTime() + (int)$inflow->diff($today)->days;
                    }
                }
            }

            if ($offset !== 0) {
                $min  = $time - $offset <= 0 ? 1 : $time - $offset;
                $max  = $time + $offset;
                $time = \sprintf('%s-%s', $min, $max);
            }

            if (
                $time === 0
                && Config::get(Config::OPTIONS_DISABLED_ZERO_DELIVERY_TIME)
                && Config::get(Config::OPTIONS_USE_DELIVERYTIME_CALC) === 'delivery_time_calc'
            ) {
                return;
            }

            //Build Term string
            $deliveryTimeString = \trim(
                \sprintf(
                    '%s %s %s',
                    Config::get(Config::OPTIONS_PRAEFIX_DELIVERYTIME),
                    $time,
                    Config::get(Config::OPTIONS_SUFFIX_DELIVERYTIME)
                )
            );

            if (
                (Config::get(Config::OPTIONS_USE_DELIVERYTIME_CALC) === 'delivery_status')
                && (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED)
                    || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED2)
                    || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZEDPRO))
            ) {
                foreach ($product->getI18ns() as $i18n) {
                    if (Util::getInstance()->isWooCommerceLanguage($i18n->getLanguageISO())) {
                        $deliveryTimeString = $i18n->getDeliveryStatus();
                        break;
                    }
                }
            }

            $term = \get_term_by(
                'slug',
                \wc_sanitize_taxonomy_name(
                    Util::removeSpecialchars($deliveryTimeString)
                ),
                'product_delivery_times'
            );

            if ($term === false) {
                //Add term
                $newTerm = \wp_insert_term(
                    $deliveryTimeString,
                    'product_delivery_times'
                );

                if ($newTerm instanceof WP_Error) {
                    //  var_dump($newTerm);
                    // die();
                    $error = new WP_Error('invalid_taxonomy', 'Could not create delivery time.');
                    WpErrorLogger::getInstance()->logError($error);
                    WpErrorLogger::getInstance()->logError($newTerm);
                } else {
                    $termId = $newTerm['term_id'];

                    \wp_set_object_terms($productId, $termId, 'product_delivery_times', true);

                    if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_GERMAN_MARKET)) {
                        \update_post_meta($productId, '_lieferzeit', $termId);
                    }
                }
            } else {
                \wp_set_object_terms($productId, $term->term_id, $term->taxonomy, true);

                if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_GERMAN_MARKET)) {
                    \update_post_meta($productId, '_lieferzeit', $term->term_id);
                }
            }

            if (
                SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED)
                || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED2)
                || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZEDPRO)
            ) {
                $germanizedTerm = \get_term_by(
                    'slug',
                    \wc_sanitize_taxonomy_name(
                        Util::removeSpecialchars($deliveryTimeString)
                    ),
                    $germanizedDeliveryTimeTaxonomyName
                );

                $germanizedTermId = false;
                if ($germanizedTerm === false) {
                    $germanizedTermArray = \wp_insert_term(
                        $deliveryTimeString,
                        $germanizedDeliveryTimeTaxonomyName
                    );

                    if ($germanizedTermArray instanceof WP_Error) {
                        $error = new WP_Error(
                            'invalid_taxonomy',
                            'Could not create delivery time for germanized.'
                        );
                        WpErrorLogger::getInstance()->logError($error);
                        WpErrorLogger::getInstance()->logError($germanizedTermArray);
                    }

                    if (\is_array($germanizedTermArray) && isset($germanizedTermArray['term_id'])) {
                        $germanizedTermId = $germanizedTermArray['term_id'];
                    }
                } else {
                    $germanizedTermId = $germanizedTerm->term_id;
                }

                if ($germanizedTermId !== false) {
                    \wp_set_object_terms(
                        $productId,
                        $germanizedTermId,
                        $germanizedDeliveryTimeTaxonomyName,
                        true
                    );

                    if (
                        SupportedPlugins::comparePluginVersion(
                            SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED2,
                            '>=',
                            '3.7.0'
                        )
                    ) {
                        $oldDeliveryTime = Util::getInstance()->getPostMeta(
                            $productId,
                            '_default_delivery_time',
                            true
                        );
                        Util::getInstance()->updatePostMeta(
                            $productId,
                            '_default_delivery_time',
                            $germanizedTerm->slug,
                            $oldDeliveryTime
                        );
                    }
                }
            }
        }
    }

    /**
     * @param $productId
     * @param string $taxonomyName
     * @return void
     */
    private function removeDeliveryTimeTerm($productId, string $taxonomyName = 'product_delivery_times'): void
    {
        $terms = \wp_get_object_terms($productId, $taxonomyName);
        if (\is_array($terms) && !$terms instanceof WP_Error) {
            if (\count($terms) > 0) {
                /** @var \WP_Term $term */
                foreach ($terms as $key => $term) {
                    if ($term instanceof \WP_Term) {
                        if (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_GERMAN_MARKET)) {
                            \delete_post_meta($productId, '_lieferzeit', $term->term_id);
                        }
                        \wp_remove_object_terms($productId, $term->term_id, $taxonomyName);
                    }
                }
                if (
                    SupportedPlugins::comparePluginVersion(
                        SupportedPlugins::PLUGIN_WOOCOMMERCE_GERMANIZED2,
                        '>=',
                        '3.7.0'
                    )
                ) {
                    Util::getInstance()->deletePostMeta($productId, '_default_delivery_time');
                }
            }
        }
    }
}
