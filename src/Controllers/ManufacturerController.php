<?php

namespace JtlWooCommerceConnector\Controllers;

use Jtl\Connector\Core\Controller\DeleteInterface;
use Jtl\Connector\Core\Controller\PullInterface;
use Jtl\Connector\Core\Controller\PushInterface;
use Jtl\Connector\Core\Controller\StatisticInterface;
use Jtl\Connector\Core\Model\AbstractModel;
use Jtl\Connector\Core\Model\Identity;
use Jtl\Connector\Core\Model\Manufacturer as ManufacturerModel;
use Jtl\Connector\Core\Model\ManufacturerI18n as ManufacturerI18nModel;
use Jtl\Connector\Core\Model\QueryFilter;
use JtlWooCommerceConnector\Logger\ErrorFormatter;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use Psr\Log\InvalidArgumentException;
use WP_Error;

class ManufacturerController extends AbstractBaseController implements
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
        $manufacturers = [];
        if (SupportedPlugins::isPerfectWooCommerceBrandsActive()) {
            $sql              = SqlHelper::manufacturerPull($query->getLimit());
            $manufacturerData = $this->db->query($sql);

            foreach ($manufacturerData as $manufacturerDataSet) {
                $manufacturer = (new ManufacturerModel())
                    ->setId(new Identity($manufacturerDataSet['term_id']))
                    ->setName($manufacturerDataSet['name']);

                $i18n = (new ManufacturerI18nModel())
                    ->setLanguageISO($this->util->getWooCommerceLanguage())
                    ->setDescription($manufacturerDataSet['description']);

                if (
                    SupportedPlugins::isActive(SupportedPlugins::PLUGIN_YOAST_SEO)
                    || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_YOAST_SEO_PREMIUM)
                ) {
                    $taxonomySeo = \get_option('wpseo_taxonomy_meta');
                    if (isset($taxonomySeo['pwb-brand'])) {
                        foreach ($taxonomySeo['pwb-brand'] as $brandKey => $seoData) {
                            if ($brandKey === (int)$manufacturerDataSet['term_id']) {
                                $i18n->setMetaDescription($seoData['wpseo_desc'] ?? '')
                                    ->setMetaKeywords(
                                        $seoData['wpseo_focuskw']
                                            ?? $manufacturerDataSet['name']
                                    )
                                    ->setTitleTag($seoData['wpseo_title'] ?? '');
                            }
                        }
                    }
                } elseif (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_RANK_MATH_SEO)) {
                    $sql                 = SqlHelper::pullRankMathSeoTermData(
                        (int)$manufacturer->getId()->getEndpoint()
                    );
                    $manufacturerSeoData = $this->db->query($sql);
                    if (\is_array($manufacturerSeoData)) {
                        $this->util->setI18nRankMathSeo($i18n, $manufacturerSeoData);
                    }
                }

                $manufacturer->addI18n(
                    $i18n
                );

                $manufacturers[] = $manufacturer;
            }
        }

        return $manufacturers;
    }

    /**
     * @param ManufacturerModel $model
     * @return ManufacturerModel
     * @throws \InvalidArgumentException
     */
    public function push(AbstractModel $model): AbstractModel
    {
        if (SupportedPlugins::isPerfectWooCommerceBrandsActive()) {
            $meta = (new ManufacturerI18nModel());

            foreach ($model->getI18ns() as $i18n) {
                if ($this->util->isWooCommerceLanguage($i18n->getLanguageISO())) {
                    $meta = $i18n;
                    break;
                }
            }

            $name = \wc_sanitize_taxonomy_name(\substr(\trim($model->getName()), 0, 27));
            $term = \get_term_by('slug', $name, 'pwb-brand');

            \remove_filter('pre_term_description', 'wp_filter_kses');

            if ($term === false) {
                //Add term
                /** @var \WP_Term $newTerm */
                $newTerm = \wp_insert_term(
                    $model->getName(),
                    'pwb-brand',
                    [
                        'description' => $meta->getDescription(),
                        'slug' => $name,
                    ]
                );

                if ($newTerm instanceof WP_Error) {
                    //  var_dump($newTerm);
                    // die();
                    $error = new WP_Error('invalid_taxonomy', 'Could not create manufacturer.');
                    $this->logger->error(ErrorFormatter::formatError($error));
                    $this->logger->error(ErrorFormatter::formatError($newTerm));
                }
                $term = $newTerm;

                if (!$term instanceof \WP_Term) {
                    $term = \get_term_by('id', $term['term_id'], 'pwb-brand');
                }
            } else {
                \wp_update_term($term->term_id, 'pwb-brand', [
                    'name' => $model->getName(),
                    'description' => $meta->getDescription(),
                ]);
            }

            \add_filter('pre_term_description', 'wp_filter_kses');

            if ($term instanceof \WP_Term) {
                $model->getId()->setEndpoint($term->term_id);
                foreach ($model->getI18ns() as $i18n) {
                    $i18n->getManufacturerId()->setEndpoint($term->term_id);
                    if (
                        SupportedPlugins::isActive(SupportedPlugins::PLUGIN_YOAST_SEO)
                        || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_YOAST_SEO_PREMIUM)
                    ) {
                        $taxonomySeo = \get_option('wpseo_taxonomy_meta', false);

                        if ($taxonomySeo === false) {
                            $taxonomySeo = ['pwb-brand' => []];
                        }

                        if (!isset($taxonomySeo['pwb-brand'])) {
                            $taxonomySeo['pwb-brand'] = [];
                        }
                        $exists = false;

                        foreach ($taxonomySeo['pwb-brand'] as $brandKey => $seoData) {
                            if ($brandKey === (int)$term->term_id) {
                                $exists                                               = true;
                                $taxonomySeo['pwb-brand'][$brandKey]['wpseo_desc']    = $i18n->getMetaDescription();
                                $taxonomySeo['pwb-brand'][$brandKey]['wpseo_focuskw'] = $i18n->getMetaKeywords();
                                $taxonomySeo['pwb-brand'][$brandKey]['wpseo_title']   = \strcmp(
                                    $i18n->getTitleTag(),
                                    ''
                                ) === 0 ? $model->getName() : $i18n->getTitleTag();
                            }
                        }
                        if ($exists === false) {
                            $taxonomySeo['pwb-brand'][(int)$term->term_id] = [
                                'wpseo_desc' => $i18n->getMetaDescription(),
                                'wpseo_focuskw' => $i18n->getMetaKeywords(),
                                'wpseo_title' => \strcmp(
                                    $i18n->getTitleTag(),
                                    ''
                                ) === 0 ? $model->getName() : $i18n->getTitleTag(),
                            ];
                        }

                        \update_option('wpseo_taxonomy_meta', $taxonomySeo, true);
                    } elseif (SupportedPlugins::isActive(SupportedPlugins::PLUGIN_RANK_MATH_SEO)) {
                        $updateRankMathSeoData = [
                            'rank_math_title' => $i18n->getTitleTag(),
                            'rank_math_description' => $i18n->getMetaDescription(),
                            'rank_math_focus_keyword' => $i18n->getMetaKeywords()
                        ];
                        $this->util->updateTermMeta($updateRankMathSeoData, (int)$term->term_id);
                    }

                    break;
                }
            }
        }

        return $model;
    }

    /**
     * @param AbstractModel $model
     * @return AbstractModel
     */
    public function delete(AbstractModel $model): AbstractModel
    {
        if (SupportedPlugins::isPerfectWooCommerceBrandsActive()) {
            $manufacturerId = (int)$model->getId()->getEndpoint();

            if (!empty($manufacturerId)) {
                unset(self::$idCache[$model->getId()->getHost()]);

                \wp_delete_term($manufacturerId, 'pwb-brand');
            }
        }

        return $model;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function statistic(QueryFilter $query): int
    {
        if (SupportedPlugins::isPerfectWooCommerceBrandsActive()) {
            return $this->db->queryOne(SqlHelper::manufacturerStats());
        } else {
            return 0;
        }
    }
}
