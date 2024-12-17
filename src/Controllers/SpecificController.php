<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Controllers;

use Jtl\Connector\Core\Controller\DeleteInterface;
use Jtl\Connector\Core\Controller\PullInterface;
use Jtl\Connector\Core\Controller\PushInterface;
use Jtl\Connector\Core\Controller\StatisticInterface;
use Jtl\Connector\Core\Model\AbstractIdentity;
use Jtl\Connector\Core\Model\AbstractModel;
use Jtl\Connector\Core\Model\Identity;
use Jtl\Connector\Core\Model\QueryFilter;
use Jtl\Connector\Core\Model\Specific;
use Jtl\Connector\Core\Model\Specific as SpecificModel;
use Jtl\Connector\Core\Model\SpecificI18n as SpecificI18nModel;
use Jtl\Connector\Core\Model\SpecificValue as SpecificValueModel;
use Jtl\Connector\Core\Model\SpecificValueI18n as SpecificValueI18nModel;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\Wpml;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\WpmlSpecific;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\WpmlSpecificValue;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\WpmlTermTranslation;
use JtlWooCommerceConnector\Logger\ErrorFormatter;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use JtlWooCommerceConnector\Utilities\Util;
use Psr\Log\InvalidArgumentException;
use WC_Product_Attribute;
use WP_Error;
use WP_Post;
use WP_Query;

class SpecificController extends AbstractBaseController implements
    PullInterface,
    PushInterface,
    DeleteInterface,
    StatisticInterface
{
    /** @var int[] */
    private static array $idCache = [];

    /**
     * @param QueryFilter $query
     * @return array<int, AbstractIdentity|Specific>
     * @throws InvalidArgumentException
     * @throws \Exception
     */
    public function pull(QueryFilter $query): array
    {
        $specifics = [];

        $specificData = $this->db->query(SqlHelper::specificPull($query->getLimit())) ?? [[]];

        /** @var array<string, string> $specificDataSet */
        foreach ($specificData as $specificDataSet) {
            $specific = (new SpecificModel())
                ->setIsGlobal(true)
                ->setId(new Identity($specificDataSet['attribute_id']))
                ->setType('string'); //$specificDataSet['attribute_type']

            $specific->addI18n(
                (new SpecificI18nModel())
                    ->setLanguageISO($this->util->getWooCommerceLanguage())
                    ->setName($specificDataSet['attribute_label'])
            );

            $specificName = \sprintf('pa_%s', $specificDataSet['attribute_name']);

            /** @var WpmlSpecific $wpmlSpecific */
            $wpmlSpecific = $this->wpml->getComponent(WpmlSpecific::class);

            if (
                $this->wpml->canBeUsed()
                && $wpmlSpecific->isTranslatable($specificName)
            ) {
                $wpmlSpecific->getTranslations($specific, $specificDataSet['attribute_label']);

                $specificValueData = $wpmlSpecific->getValues($specificName) ?? [];
            } else {
                // SpecificValues
                $specificValueData = $this->db->query(
                    SqlHelper::specificValuePull($specificName)
                ) ?? [];
            }

            /** @var array<string, string> $specificValueDataSet */
            foreach ($specificValueData as $specificValueDataSet) {
                $specificValue = (new SpecificValueModel())
                    ->setId(new Identity($specificValueDataSet['term_taxonomy_id']));

                $specificValue->addI18n((new SpecificValueI18nModel())
                    ->setLanguageISO($this->util->getWooCommerceLanguage())
                    ->setValue($specificValueDataSet['name']));

                if ($this->wpml->canBeUsed()) {
                    /** @var WpmlSpecificValue $wpmlSpecificValue */
                    $wpmlSpecificValue = $this->wpml->getComponent(WpmlSpecificValue::class);

                    $wpmlSpecificValue->getTranslations(
                        $specificValue,
                        (int)$specificValueDataSet['term_taxonomy_id'],
                        $specificValueDataSet['taxonomy']
                    );
                }

                $specific->addValue($specificValue);
            }

            $specifics[] = $specific;
        }

        return $specifics;
    }

    /**
     * @param AbstractModel $model
     *
     * @return SpecificModel
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    public function push(AbstractModel $model): AbstractModel
    {
        /** @var SpecificModel $model */
        $model->setType('string');
        $meta             = null;
        $defaultAvailable = false;

        foreach ($model->getI18ns() as $i18n) {
            if ($this->wpml->canBeUsed()) {
                if (Util::mapLanguageIso($i18n->getLanguageIso()) === $this->wpml->getDefaultLanguage()) {
                    $meta = $i18n;
                    break;
                }
            } else {
                if ($this->util->isWooCommerceLanguage($i18n->getLanguageISO())) {
                    $meta = $i18n;
                    break;
                }
            }

            if (\strcmp($i18n->getLanguageISO(), 'ger') === 0) {
                $defaultAvailable = true;
            }
        }

        //Fallback 'ger' if incorrect language code was given
        if ($meta === null && $defaultAvailable) {
            foreach ($model->getI18ns() as $i18n) {
                if (\strcmp($i18n->getLanguageISO(), 'ger') === 0) {
                    $meta = $i18n;
                }
            }
        }

        if ($meta !== null) {
            $attrName = \wc_sanitize_taxonomy_name(Util::removeSpecialchars($meta->getName()));

            //STOP here if already exists
            $existingTaxonomyId = Util::getAttributeTaxonomyIdByName($attrName);
            $endpointId         = (int)$model->getId()->getEndpoint();

            if ($existingTaxonomyId !== 0) {
                if ($existingTaxonomyId !== $endpointId) {
                    $attrId = $existingTaxonomyId;
                } else {
                    $attrId = $endpointId;
                }
            } else {
                $attrId = $endpointId;
            }

            $endpoint = [
                'id'       => $attrId,
                'name'     => $meta->getName(),
                'slug'     => \wc_sanitize_taxonomy_name(\substr(\trim($meta->getName()), 0, 27)),
                'type'     => 'select',
                'order_by' => 'menu_order',
                'has_archives' => false,
            ];

            if ($endpoint['id'] === 0) {
                $attributeId = \wc_create_attribute($endpoint);
            } else {
                $attributeData = \wc_get_attribute($endpoint['id']);
                if (!\is_null($attributeData)) {
                    $endpoint['has_archives'] = (bool)$attributeData->has_archives;
                }
                $attributeId = \wc_update_attribute($endpoint['id'], $endpoint);
            }

            if ($attributeId instanceof WP_Error) {
                //var_dump($attributeId);
                //die();
                //return $termId->get_error_message();
                $this->logger->error(ErrorFormatter::formatError($attributeId));

                return $model;
            }

            $model->getId()->setEndpoint((string)$attributeId);

            //Get taxonomy
            $taxonomy = $attrName ?
                'pa_' . \wc_sanitize_taxonomy_name(\substr(\trim($meta->getName()), 0, 27))
                : '';

            //Register taxonomy for current request
            \register_taxonomy($taxonomy, []);

            if ($this->wpml->canBeUsed()) {
                /** @var WpmlSpecific $wpmlSpecific */
                $wpmlSpecific = $this->wpml->getComponent(WpmlSpecific::class);

                $wpmlSpecific->setTranslations($model, $meta);
            }

            foreach ($model->getValues() as $key => $value) {
                $metaValue             = null;
                $defaultValueAvailable = false;

                //Get i18n
                foreach ($value->getI18ns() as $i18n) {
                    if ($this->wpml->canBeUsed()) {
                        if (Util::mapLanguageIso($i18n->getLanguageISO()) === $this->wpml->getDefaultLanguage()) {
                            $metaValue = $i18n;
                        }
                    } else {
                        if ($this->util->isWooCommerceLanguage($i18n->getLanguageISO())) {
                            $metaValue = $i18n;
                            break;
                        }
                    }

                    if (\strcmp($i18n->getLanguageISO(), 'ger') === 0) {
                        $defaultValueAvailable = true;
                    }
                }

                //Fallback 'ger' if incorrect language code was given
                if ($defaultValueAvailable) {
                    foreach ($value->getI18ns() as $i18n) {
                        if (\strcmp($i18n->getLanguageISO(), 'ger') === 0) {
                            $metaValue = $i18n;
                        }
                    }
                }

                if (\is_null($metaValue)) {
                    continue;
                }

                $slug = \wc_sanitize_taxonomy_name($metaValue->getValue());

                $endpointValue = [
                    'name' => $metaValue->getValue(),
                    'slug' => $slug,
                ];

                /** @var array<int, array<string, int|string|null>> $exValId */
                $exValId = $this->db->query(
                    SqlHelper::getSpecificValueId($taxonomy, $endpointValue['name'])
                ) ?? [];

                if (\count($exValId) >= 1) {
                    if (isset($exValId[0]['term_id'])) {
                        $exValId = $exValId[0]['term_id'];
                    } else {
                        $exValId = null;
                    }
                } else {
                    $exValId = null;
                }

                $endValId = (int)$value->getId()->getEndpoint();

                if (\is_null($exValId) && $endValId === 0) {
                    $newTerm = \wp_insert_term(
                        $endpointValue['name'],
                        $taxonomy
                    );

                    if ($newTerm instanceof WP_Error) {
                        // var_dump($newTerm);
                        // die();
                        $this->logger->error(ErrorFormatter::formatError($newTerm));
                        continue;
                    }

                    $termId = $newTerm['term_id'];
                } elseif (\is_null($exValId) && $endValId !== 0) {
                    $wpml = $this->getPluginsManager()->get(Wpml::class);

                    /** @var WpmlTermTranslation $wpmlTermTranslation */
                    $wpmlTermTranslation = $wpml->getComponent(WpmlTermTranslation::class);

                    if ($wpml->canBeUsed()) {
                        $wpmlTermTranslation->disableGetTermAdjustId();
                    }

                    $termId = \wp_update_term($endValId, $taxonomy, $endpointValue);

                    if ($wpml->canBeUsed()) {
                        $wpmlTermTranslation->enableGetTermAdjustId();
                    }
                } else {
                    $termId = $exValId;
                }

                if ($termId instanceof WP_Error) {
                    // var_dump($termId);
                    // die();
                    $this->logger->error(ErrorFormatter::formatError($termId));
                    continue;
                }

                if (\is_array($termId)) {
                    $termId = $termId['term_id'];
                }

                $value->getId()->setEndpoint((string)$termId);
            }
        }

        return $model;
    }

    /**
     * @param AbstractModel $model
     * @return AbstractModel
     * @throws InvalidArgumentException
     */
    public function delete(AbstractModel $model): AbstractModel
    {
        /** @var SpecificModel $model */
        $specificId = (int)$model->getId()->getEndpoint();

        if (!empty($specificId)) {
            unset(self::$idCache[$model->getId()->getHost()]);

            $this->db->query(SqlHelper::removeSpecificLinking($specificId));
            $taxonomy = \wc_attribute_taxonomy_name_by_id($specificId);

            $specificValueData = $this->db->query(
                SqlHelper::forceSpecificValuePull($taxonomy)
            ) ?? [];

            $terms = [];

            /** @var array<string, string> $specificValue */
            foreach ($specificValueData as $specificValue) {
                $terms[] = $specificValue['slug'];

                $this->db->query(SqlHelper::removeSpecificValueLinking((int)$specificValue['term_id']));
            }

            $products = new WP_Query([
                'post_type'      => ['product'],
                'posts_per_page' => -1,
                'tax_query'      => [
                    [
                        'taxonomy' => $taxonomy,
                        'field'    => 'slug',
                        'terms'    => $terms,
                        'operator' => 'IN',
                    ],
                ],
            ]);

            $isVariation = false;

            $posts = $products->get_posts();

            /** @var WP_Post $post */
            foreach ($posts as $post) {
                $wcProduct        = \wc_get_product($post->ID);
                $productSpecifics = $wcProduct instanceof \WC_Product ? $wcProduct->get_attributes() : [];

                /** @var WC_Product_Attribute $productSpecific */
                foreach ($productSpecifics as $productSpecific) {
                    if ($productSpecific->get_variation()) {
                        $isVariation = true;
                    }
                }
            }

            if (!$isVariation) {
                /** @var array<string, int|string> $value */
                foreach ($specificValueData as $value) {
                    \wp_delete_term((int)$value['term_id'], $taxonomy);
                }

                \wc_delete_attribute($specificId);
            }
        }

        return $model;
    }

    /**
     * @param QueryFilter $query
     * @return int
     * @throws InvalidArgumentException
     * @throws \Exception
     */
    public function statistic(QueryFilter $query): int
    {
        if ($this->wpml->canBeUsed()) {
            /** @var WpmlSpecific $wpmlSpecific */
            $wpmlSpecific = $this->wpml->getComponent(WpmlSpecific::class);
            $total        = $wpmlSpecific->getStats();
        } else {
            $total = $this->db->queryOne(SqlHelper::specificStats()) ?? 0;
        }

        return (int)$total;
    }
}
