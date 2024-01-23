<?php

namespace JtlWooCommerceConnector\Controllers;

use Jtl\Connector\Core\Controller\DeleteInterface;
use Jtl\Connector\Core\Controller\PullInterface;
use Jtl\Connector\Core\Controller\PushInterface;
use Jtl\Connector\Core\Controller\StatisticInterface;
use Jtl\Connector\Core\Model\AbstractModel;
use Jtl\Connector\Core\Model\Identity;
use Jtl\Connector\Core\Model\QueryFilter;
use Jtl\Connector\Core\Model\Specific as SpecificModel;
use Jtl\Connector\Core\Model\SpecificI18n as SpecificI18nModel;
use Jtl\Connector\Core\Model\SpecificValue as SpecificValueModel;
use Jtl\Connector\Core\Model\SpecificValueI18n as SpecificValueI18nModel;
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
    private static $idCache = [];

    /**
     * @param QueryFilter $query
     * @return array
     * @throws InvalidArgumentException
     */
    public function pull(QueryFilter $query): array
    {
        $specifics = [];

        $specificData = $this->db->query(SqlHelper::specificPull($query->getLimit()));

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

            // SpecificValues
            $specificValueData = $this->db->query(
                SqlHelper::specificValuePull(\sprintf(
                    'pa_%s',
                    $specificDataSet['attribute_name']
                ))
            );

            foreach ($specificValueData as $specificValueDataSet) {
                $specificValue = (new SpecificValueModel())
                    ->setId(new Identity($specificValueDataSet['term_taxonomy_id']));

                $specificValue->addI18n((new SpecificValueI18nModel())
                    ->setLanguageISO($this->util->getWooCommerceLanguage())
                    ->setValue($specificValueDataSet['name']));

                $specific->addValue($specificValue);
            }

            $specifics[] = $specific;
        }

        return $specifics;
    }

    /**
     * @param SpecificModel $model
     * @return SpecificModel
     * @throws \InvalidArgumentException
     */
    public function push(AbstractModel $model): AbstractModel
    {
        //WooFix
        $model->setType('string');
        $meta             = null;
        $defaultAvailable = false;

        foreach ($model->getI18ns() as $i18n) {
            $languageSet = $this->util->isWooCommerceLanguage($i18n->getLanguageISO());

            if (\strcmp($i18n->getLanguageISO(), 'ger') === 0) {
                $defaultAvailable = true;
            }

            if ($languageSet) {
                $meta = $i18n;
                break;
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
            $exId  = Util::getAttributeTaxonomyIdByName($attrName);
            $endId = (int)$model->getId()->getEndpoint();

            if ($exId !== 0) {
                if ($exId !== $endId) {
                    $attrId = $exId;
                } else {
                    $attrId = $endId;
                }
            } else {
                $attrId = $endId;
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

            $model->getId()->setEndpoint($attributeId);

            //Get taxonomy
            $taxonomy = $attrName ?
                'pa_' . \wc_sanitize_taxonomy_name(\substr(\trim($meta->getName()), 0, 27))
                : '';

            //Register taxonomy for current request
            \register_taxonomy($taxonomy, null);

            foreach ($model->getValues() as $key => $value) {
                $metaValue             = null;
                $defaultValueAvailable = false;

                //Get i18n
                foreach ($value->getI18ns() as $i18n) {
                    $languageValueSet = $this->util->isWooCommerceLanguage($i18n->getLanguageISO());

                    if (\strcmp($i18n->getLanguageISO(), 'ger') === 0) {
                        $defaultValueAvailable = true;
                    }

                    if ($languageValueSet) {
                        $metaValue = $i18n;
                        break;
                    }
                }

                //Fallback 'ger' if incorrect language code was given
                if ($meta === null && $defaultValueAvailable) {
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

                $exValId = $this->db->query(
                    SqlHelper::getSpecificValueId(
                        $taxonomy,
                        $endpointValue['name']
                    )
                );

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
                        //  var_dump($newTerm);
                        // die();
                        $this->logger->error(ErrorFormatter::formatError($newTerm));
                        continue;
                    }

                    $termId = $newTerm['term_id'];
                } elseif (\is_null($exValId) && $endValId !== 0) {
                    $termId = \wp_update_term($endValId, $taxonomy, $endpointValue);
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

                $value->getId()->setEndpoint($termId);
            }
        }

        return $model;
    }

    /**
     * @param SpecificModel $model
     * @throws \Exception
     */
    public function delete(AbstractModel $model): AbstractModel
    {
        $specificId = (int)$model->getId()->getEndpoint();

        if (!empty($specificId)) {
            unset(self::$idCache[$model->getId()->getHost()]);

            $this->db->query(SqlHelper::removeSpecificLinking($specificId));
            $taxonomy = \wc_attribute_taxonomy_name_by_id($specificId);
            /** @var WC_Product_Attribute $specific */
            //$specific = wc_get_attribute($specificId);

            $specificValueData = $this->db->query(
                SqlHelper::forceSpecificValuePull($taxonomy)
            );

            $terms = [];
            foreach ($specificValueData as $specificValue) {
                $terms[] = $specificValue['slug'];

                $this->db->query(SqlHelper::removeSpecificValueLinking($specificValue['term_id']));
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
                $productSpecifics = $wcProduct->get_attributes();

                /** @var WC_Product_Attribute $productSpecific */
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

                \wc_delete_attribute($specificId);
            }
        }

        return $model;
    }

    /**
     * @param QueryFilter $query
     * @return int
     * @throws InvalidArgumentException
     */
    public function statistic(QueryFilter $query): int
    {
        return $this->db->queryOne(SqlHelper::specificStats());
    }
}
