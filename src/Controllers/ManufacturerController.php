<?php

namespace JtlWooCommerceConnector\Controllers;

use Jtl\Connector\Core\Controller\DeleteInterface;
use Jtl\Connector\Core\Controller\PullInterface;
use Jtl\Connector\Core\Controller\PushInterface;
use Jtl\Connector\Core\Controller\StatisticInterface;
use Jtl\Connector\Core\Model\AbstractModel;
use Jtl\Connector\Core\Model\Identity;
use Jtl\Connector\Core\Model\Manufacturer;
use Jtl\Connector\Core\Model\Manufacturer as ManufacturerModel;
use Jtl\Connector\Core\Model\ManufacturerI18n as ManufacturerI18nModel;
use Jtl\Connector\Core\Model\QueryFilter;
use JtlWooCommerceConnector\Integrations\Plugins\PerfectWooCommerceBrands\PerfectWooCommerceBrands;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\WpmlPerfectWooCommerceBrands;
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\WpmlTermTranslation;
use JtlWooCommerceConnector\Logger\ErrorFormatter;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use JtlWooCommerceConnector\Utilities\Util;
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
     * @throws \Exception
     */
    public function pull(QueryFilter $query): array
    {
        $manufacturers = [];

        $perfectWooCommerceBrands = $this->getPluginsManager()->get(PerfectWooCommerceBrands::class);

        if ($perfectWooCommerceBrands->canBeUsed()) {
            if ($this->wpml->canBeUsed()) {
                $manufacturerData = $this->wpml
                    ->getComponent(WpmlPerfectWooCommerceBrands::class)
                    ->getManufacturers((int)$query->getLimit());
            } else {
                $sql              = SqlHelper::manufacturerPull($query->getLimit());
                $manufacturerData = $this->db->query($sql);
            }

            foreach ($manufacturerData as $manufacturerDataSet) {
                $manufacturer = (new ManufacturerModel())
                    ->setId(new Identity($manufacturerDataSet['term_id']))
                    ->setName($manufacturerDataSet['name']);

                $i18n = $this->createManufacturerI18n(
                    $manufacturer,
                    $this->util->getWooCommerceLanguage(),
                    $manufacturerDataSet['description'],
                    (int)$manufacturerDataSet['term_id']
                );

                $manufacturer->addI18n(
                    $i18n
                );

                if ($this->wpml->canBeUsed()) {
                    $wpmlTaxonomyTranslations = $this->wpml
                        ->getComponent(WpmlTermTranslation::class);

                    $manufacturerTranslations = $wpmlTaxonomyTranslations
                        ->getTranslations((int)$manufacturerDataSet['trid'], 'tax_pwb-brand');

                    foreach ($manufacturerTranslations as $languageCode => $translation) {
                        $term = $wpmlTaxonomyTranslations->getTranslatedTerm(
                            (int)$translation->term_id,
                            'pwb-brand'
                        );

                        if (isset($term['term_id'])) {
                            $i18n = $this->createManufacturerI18n(
                                $manufacturer,
                                Util::mapLanguageIso($translation->language_code),
                                $term['description'],
                                (int)$term['term_id']
                            );

                            $manufacturer->addI18n($i18n);
                        }
                    }
                }

                $manufacturers[] = $manufacturer;
            }
        }

        return $manufacturers;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function createManufacturerI18n(
        Manufacturer $manufacturer,
        string $languageIso,
        string $description,
        string $termId
    ): \Jtl\Connector\Core\Model\AbstractI18n|ManufacturerI18nModel {
        $i18n = (new ManufacturerI18nModel())
            ->setLanguageISO($languageIso)
            ->setDescription($description);

        if (
            SupportedPlugins::isActive(SupportedPlugins::PLUGIN_YOAST_SEO)
            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_YOAST_SEO_PREMIUM)
        ) {
            $taxonomySeo = \get_option('wpseo_taxonomy_meta');
            if (isset($taxonomySeo['pwb-brand'])) {
                foreach ($taxonomySeo['pwb-brand'] as $brandKey => $seoData) {
                    if ($brandKey === $termId) {
                        $i18n->setMetaDescription($seoData['wpseo_desc'] ?? '')
                            ->setMetaKeywords(
                                $seoData['wpseo_focuskw']
                                ?? $manufacturer->getName()
                            )
                            ->setTitleTag($seoData['wpseo_title'] ?? '');
                    }
                }
            }
        } elseif (
            SupportedPlugins::isActive(SupportedPlugins::PLUGIN_RANK_MATH_SEO)
            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_RANK_MATH_SEO_AI)
        ) {
            $sql                 = SqlHelper::pullRankMathSeoTermData(
                (int)$manufacturer->getId()->getEndpoint()
            );
            $manufacturerSeoData = $this->db->query($sql);
            if (\is_array($manufacturerSeoData)) {
                $this->util->setI18nRankMathSeo($i18n, $manufacturerSeoData);
            }
        }

        return $i18n;
    }

    /**
     * @param ManufacturerModel $model
     * @return ManufacturerModel
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    public function push(AbstractModel $model): AbstractModel
    {
        if (SupportedPlugins::isPerfectWooCommerceBrandsActive()) {
            $meta = (new ManufacturerI18nModel());

            foreach ($model->getI18ns() as $i18n) {
                if ($this->wpml->canBeUsed()) {
                    if ($this->wpml->getDefaultLanguage() === Util::mapLanguageIso($i18n->getLanguageISO())) {
                        $meta = $i18n;
                        break;
                    }
                } else {
                    if ($this->util->isWooCommerceLanguage($i18n->getLanguageISO())) {
                        $meta = $i18n;
                        break;
                    }
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
                    } elseif (
                        SupportedPlugins::isActive(SupportedPlugins::PLUGIN_RANK_MATH_SEO)
                        || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_RANK_MATH_SEO_AI)
                    ) {
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

            if ($this->wpml->canBeUsed()) {
                $this->wpml
                    ->getComponent(WpmlPerfectWooCommerceBrands::class)
                    ->saveTranslations($model);
            }
        }

        return $model;
    }

    /**
     * @param AbstractModel $model
     * @return AbstractModel
     * @throws \Exception
     */
    public function delete(AbstractModel $model): AbstractModel
    {
        if (SupportedPlugins::isPerfectWooCommerceBrandsActive()) {
            $manufacturerId = (int)$model->getId()->getEndpoint();

            if (!empty($manufacturerId)) {
                unset(self::$idCache[$model->getId()->getHost()]);

                if ($this->wpml->canBeUsed()) {
                    $this->wpml
                        ->getComponent(WpmlPerfectWooCommerceBrands::class)
                        ->deleteTranslations($manufacturerId);
                }

                \wp_delete_term($manufacturerId, 'pwb-brand');
            }
        }

        return $model;
    }

    /**
     * @throws InvalidArgumentException
     * @throws \Exception
     */
    public function statistic(QueryFilter $query): int
    {
        $total = 0;
        if (SupportedPlugins::isPerfectWooCommerceBrandsActive()) {
            if ($this->wpml->canBeUsed()) {
                $total = $this->wpml->getComponent(WpmlPerfectWooCommerceBrands::class)->getStats();
            } else {
                $total = $this->db->queryOne(SqlHelper::manufacturerStats());
            }
        }
        return $total;
    }
}
