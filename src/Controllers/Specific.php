<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2018 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Controllers;

use jtl\Connector\Core\Utilities\Language;
use jtl\Connector\Model\Identity;
use jtl\Connector\Model\Specific as SpecificModel;
use jtl\Connector\Model\SpecificI18n as SpecificI18nModel;
use jtl\Connector\Model\SpecificValue as SpecificValueModel;
use jtl\Connector\Model\SpecificValueI18n as SpecificValueI18nModel;
use JtlWooCommerceConnector\Controllers\Traits\DeleteTrait;
use JtlWooCommerceConnector\Controllers\Traits\PullTrait;
use JtlWooCommerceConnector\Controllers\Traits\PushTrait;
use JtlWooCommerceConnector\Controllers\Traits\StatsTrait;
use JtlWooCommerceConnector\Integrations\Plugins\WooCommerce\WooCommerce;
use JtlWooCommerceConnector\Integrations\Plugins\WooCommerce\WooCommerceSpecific;
use JtlWooCommerceConnector\Integrations\Plugins\WooCommerce\WooCommerceSpecificValue;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\WpmlSpecific;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\WpmlSpecificValue;
use JtlWooCommerceConnector\Logger\WpErrorLogger;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use JtlWooCommerceConnector\Utilities\Util;
use WP_Error;
use WP_Query;

class Specific extends BaseController
{
    use PullTrait, PushTrait, DeleteTrait, StatsTrait;

    /**
     * @param $limit
     * @return array
     * @throws \Exception
     */
    protected function pullData($limit)
    {
        $specifics = [];

        $specificData = $this->database->query(SqlHelper::specificPull($limit));

        foreach ($specificData as $specificDataSet) {
            $specific = (new SpecificModel)
                ->setIsGlobal(true)
                ->setId(new Identity($specificDataSet['attribute_id']))
                ->setType('string');

            $specific->addI18n(
                (new SpecificI18nModel)
                    ->setSpecificId($specific->getId())
                    ->setLanguageISO(Util::getInstance()->getWooCommerceLanguage())
                    ->setName($specificDataSet['attribute_label'])
            );

            $specificName = sprintf('pa_%s', $specificDataSet['attribute_name']);

            if ($this->wpml->canBeUsed() && $this->wpml->getComponent(WpmlSpecific::class)->isTranslatable($specificName)) {
                $this->wpml
                    ->getComponent(WpmlSpecific::class)
                    ->getTranslations($specific, $specificDataSet['attribute_label']);

                $specificValueData = $this->wpml
                    ->getComponent(WpmlSpecific::class)
                    ->getValues($specificName);
            } else {
                $specificValueData = $this->database->query(
                    SqlHelper::specificValuePull($specificName)
                );
            }

            foreach ($specificValueData as $specificValueDataSet) {
                $specificValue = (new SpecificValueModel)
                    ->setId(new Identity($specificValueDataSet['term_taxonomy_id']))
                    ->setSpecificId($specific->getId());

                $specificValue->addI18n((new SpecificValueI18nModel)
                    ->setLanguageISO(Util::getInstance()->getWooCommerceLanguage())
                    ->setSpecificValueId($specificValue->getId())
                    ->setValue($specificValueDataSet['name']));

                if ($this->wpml->canBeUsed()) {
                    $this->wpml
                        ->getComponent(WpmlSpecificValue::class)
                        ->getTranslations($specificValue, (int)$specificValueDataSet['term_taxonomy_id'],
                            $specificValueDataSet['taxonomy']);
                }

                $specific->addValue($specificValue);
            }

            $specifics[] = $specific;
        }

        return $specifics;
    }

    /**
     * @param SpecificModel $specific
     * @return SpecificModel
     * @throws \jtl\Connector\Core\Exception\LanguageException
     */
    protected function pushData(SpecificModel $specific)
    {
        //WooFix
        $specific->setType('string');
        $defaultSpecificTranslation = null;

        foreach ($specific->getI18ns() as $i18n) {
            if ($this->wpml->canBeUsed()) {
                if (Language::convert(null, $i18n->getLanguageISO()) === $this->wpml->getDefaultLanguage()) {
                    $defaultSpecificTranslation = $i18n;
                    break;
                }
            } else {
                if (Util::getInstance()->isWooCommerceLanguage($i18n->getLanguageISO())) {
                    $defaultSpecificTranslation = $i18n;
                    break;
                }
            }
        }

        if ($defaultSpecificTranslation !== null) {

            $result = $this->getPluginsManager()
                ->get(WooCommerce::class)
                ->getComponent(WooCommerceSpecific::class)
                ->save($specific, $defaultSpecificTranslation);

            if ($result === null) {
                return $specific;
            }

            $attrName = wc_sanitize_taxonomy_name(Util::removeSpecialchars($defaultSpecificTranslation->getName()));

            //Get taxonomy
            $taxonomy = $attrName ? 'pa_' . wc_sanitize_taxonomy_name(substr(trim($defaultSpecificTranslation->getName()),
                    0, 27)) : '';

            //Register taxonomy for current request
            register_taxonomy($taxonomy, null);

            if ($this->wpml->canBeUsed()) {
                $this->wpml
                    ->getComponent(WpmlSpecific::class)
                    ->setTranslations($specific, $defaultSpecificTranslation);
            }

            /** @var SpecificValueModel $value */
            foreach ($specific->getValues() as $key => $value) {
                $value->getSpecificId()->setEndpoint($specific->getId()->getEndpoint());
                $defaultSpecificValueTranslation = null;

                foreach ($value->getI18ns() as $i18n) {
                    if ($this->wpml->canBeUsed()) {
                        if (Language::convert(null, $i18n->getLanguageISO()) === $this->wpml->getDefaultLanguage()) {
                            $defaultSpecificValueTranslation = $i18n;
                            break;
                        }
                    } else {
                        if (Util::getInstance()->getWooCommerceLanguage() === $i18n->getLanguageISO()) {
                            $defaultSpecificValueTranslation = $i18n;
                            break;
                        }
                    }
                }

                if (!is_null($defaultSpecificValueTranslation)) {
                    $this->getPluginsManager()
                        ->get(WooCommerce::class)
                        ->getComponent(WooCommerceSpecificValue::class)
                        ->save($taxonomy, $value, $defaultSpecificValueTranslation);
                }

                if ($this->wpml->canBeUsed()) {
                    $this->wpml
                        ->getComponent(WpmlSpecificValue::class)
                        ->setTranslations($taxonomy, $value);
                }
            }
        }

        return $specific;
    }

    /**
     * @param SpecificModel $specific
     * @return SpecificModel|\WC_Product_Attribute
     */
    protected function deleteData(SpecificModel $specific)
    {
        $specificId = (int)$specific->getId()->getEndpoint();

        if (!empty($specificId)) {

            $this->database->query(SqlHelper::removeSpecificLinking($specificId));
            $taxonomy = wc_attribute_taxonomy_name_by_id($specificId);
            /** @var \WC_Product_Attribute $specific */
            //$specific = wc_get_attribute($specificId);

            $specificValueData = $this->database->query(
                SqlHelper::forceSpecificValuePull($taxonomy)
            );

            $terms = [];
            foreach ($specificValueData as $specificValue) {
                $terms[] = $specificValue['slug'];

                $this->database->query(SqlHelper::removeSpecificValueLinking($specificValue['term_id']));
            }

            $products = new WP_Query([
                'post_type' => ['product'],
                'posts_per_page' => -1,
                'tax_query' => [
                    [
                        'taxonomy' => $taxonomy,
                        'field' => 'slug',
                        'terms' => $terms,
                        'operator' => 'IN',
                    ],
                ],
            ]);

            $isVariation = false;

            $posts = $products->get_posts();

            /** @var \WP_Post $post */
            foreach ($posts as $post) {
                $wcProduct = \wc_get_product($post->ID);
                $productSpecifics = $wcProduct->get_attributes();

                /** @var \WC_Product_Attribute $productSpecific */
                foreach ($productSpecifics as $productSpecific) {
                    if ($productSpecific->get_variation()) {
                        $isVariation = true;
                    }
                }
            }

            if (!$isVariation) {

                foreach ($specificValueData as $value) {
                    \wp_delete_term($value['term_id'], $taxonomy);
                }

                wc_delete_attribute($specificId);

            }
        }

        return $specific;
    }

    /**
     * @return string|null
     * @throws \Exception
     */
    protected function getStats()
    {
        if ($this->wpml->canBeUsed()) {
            $total = $this->wpml->getComponent(WpmlSpecific::class)->getStats();
        } else {
            $total = $this->database->queryOne(SqlHelper::specificStats());
        }

        return $total;
    }
}
