<?php

declare(strict_types=1);

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
use JtlWooCommerceConnector\Integrations\Plugins\Wpml\WpmlGermanized;
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
    public const TAXONOMY_PERFECT_BRANDS = 'pwb-brand';
    public const TAXONOMY_GERMANIZED     = 'product_manufacturer';

    /** @var array<int, int> */
    private static array $idCache = [];

    /**
     * @param QueryFilter $query
     * @return ManufacturerModel[]
     * @throws InvalidArgumentException
     * @throws \Exception
     */
    public function pull(QueryFilter $query): array
    {
        $manufacturerData = [];
        $manufacturers    = [];
        $taxonomy         = '';
        $elementType      = '';

        $perfectWooCommerceBrands = $this->getPluginsManager()->get(PerfectWooCommerceBrands::class);

        if ($perfectWooCommerceBrands->canBeUsed()) {
            if ($this->wpml->canBeUsed()) {
                /** @var WpmlPerfectWooCommerceBrands $wpmlPerfectWcBrands */
                $wpmlPerfectWcBrands = $this->wpml->getComponent(WpmlPerfectWooCommerceBrands::class);
                $manufacturerData    = $wpmlPerfectWcBrands->getManufacturers((int)$query->getLimit());
            } else {
                $sql              = SqlHelper::manufacturerPull($query->getLimit(), self::TAXONOMY_PERFECT_BRANDS);
                $manufacturerData = $this->db->query($sql) ?? [];
            }
            $taxonomy    = self::TAXONOMY_PERFECT_BRANDS;
            $elementType = 'tax_' . $taxonomy;
        } elseif (SupportedPlugins::isGermanizedActive()) {
            if ($this->wpml->canBeUsed()) {
                /** @var WpmlGermanized $wpmlGermanized */
                $wpmlGermanized   = $this->wpml->getComponent(WpmlGermanized::class);
                $manufacturerData = $wpmlGermanized->getManufacturers((int)$query->getLimit());
            } else {
                $sql              = SqlHelper::manufacturerPull($query->getLimit(), self::TAXONOMY_GERMANIZED);
                $manufacturerData = $this->db->query($sql) ?? [];
            }
            $taxonomy    = self::TAXONOMY_GERMANIZED;
            $elementType = 'tax_' . $taxonomy;
        }

        /** @var array<string, string> $manufacturerDataSet */
        foreach ($manufacturerData as $manufacturerDataSet) {
            $manufacturer = (new ManufacturerModel())
                ->setId(new Identity((string)$manufacturerDataSet['term_id']))
                ->setName($manufacturerDataSet['name']);

            $i18n = $this->createManufacturerI18n(
                $manufacturer,
                $this->util->getWooCommerceLanguage(),
                $manufacturerDataSet['description'],
                (string)$manufacturerDataSet['term_id']
            );

            $manufacturer->addI18n(
                $i18n
            );

            if ($this->wpml->canBeUsed()) {
                /** @var WpmlTermTranslation $wpmlTaxonomyTranslations */
                $wpmlTaxonomyTranslations = $this->wpml->getComponent(WpmlTermTranslation::class);

                $trid = $this->wpml->getElementTrid((int)$manufacturerDataSet['term_id'], $elementType);

                $manufacturerTranslations = $wpmlTaxonomyTranslations
                    ->getTranslations((int)$trid, $elementType);

                foreach ($manufacturerTranslations as $languageCode => $translation) {
                    $term = $wpmlTaxonomyTranslations->getTranslatedTerm(
                        (int)$translation->term_id,
                        $taxonomy
                    );

                    if (isset($term['term_id'])) {
                        $i18n = $this->createManufacturerI18n(
                            $manufacturer,
                            Util::mapLanguageIso($translation->language_code),
                            $term['description'],
                            (string)$term['term_id']
                        );

                        $manufacturer->addI18n($i18n);
                    }
                }
            }

            $manufacturers[] = $manufacturer;
        }

        return $manufacturers;
    }

    /**
     * @param ManufacturerModel $manufacturer
     * @param string            $languageIso
     * @param string            $description
     * @param string            $termId
     * @return ManufacturerI18nModel
     * @throws InvalidArgumentException
     */
    public function createManufacturerI18n(
        ManufacturerModel $manufacturer,
        string $languageIso,
        string $description,
        string $termId
    ): ManufacturerI18nModel {
        $i18n = (new ManufacturerI18nModel())
            ->setLanguageISO($languageIso)
            ->setDescription($description);

        if (
            SupportedPlugins::isActive(SupportedPlugins::PLUGIN_YOAST_SEO)
            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_YOAST_SEO_PREMIUM)
        ) {
            /** @var array<string, array<int|string, array<string, string|null>>> $taxonomySeo */
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
            $sql = SqlHelper::pullRankMathSeoTermData(
                (int)$manufacturer->getId()->getEndpoint()
            );
            /** @var array<int, array<string, string>> $manufacturerSeoData */
            $manufacturerSeoData = $this->db->query($sql);
            if (\is_array($manufacturerSeoData)) {
                $this->util->setI18nRankMathSeo($i18n, $manufacturerSeoData);
            }
        }

        return $i18n;
    }

    /**
     * @param AbstractModel ...$models
     * @phpstan-param Manufacturer ...$models
     *
     * @return AbstractModel[]
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    public function push(AbstractModel ...$models): array
    {
        $returnModels = [];
        $taxonomy     = '';

        if (SupportedPlugins::isPerfectWooCommerceBrandsActive()) {
            $taxonomy = self::TAXONOMY_PERFECT_BRANDS;
        } elseif (SupportedPlugins::isGermanizedActive()) {
            $taxonomy = self::TAXONOMY_GERMANIZED;
        }

        foreach ($models as $model) {
            if (!empty($taxonomy)) {
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
                $term = \get_term_by('slug', $name, $taxonomy);

                \remove_filter('pre_term_description', 'wp_filter_kses');

                if ($term === false) {
                    //Add term
                    $newTerm = \wp_insert_term(
                        $model->getName(),
                        $taxonomy,
                        [
                            'description' => $meta->getDescription(),
                            'slug' => $name,
                        ]
                    );

                    if ($newTerm instanceof WP_Error) {
                        // var_dump($newTerm);
                        // die();
                        $error = new WP_Error('invalid_taxonomy', 'Could not create manufacturer.');
                        $this->logger->error(ErrorFormatter::formatError($error));
                        $this->logger->error(ErrorFormatter::formatError($newTerm));
                    }
                    $term = $newTerm;
                } elseif ($term instanceof \WP_Term) {
                    \wp_update_term($term->term_id, $taxonomy, [
                        'name' => $model->getName(),
                        'description' => $meta->getDescription(),
                    ]);
                }

                \add_filter('pre_term_description', 'wp_filter_kses');

                if ($term instanceof \WP_Term) {
                    $model->getId()->setEndpoint((string)$term->term_id);

                    foreach ($model->getI18ns() as $i18n) {
                        if (
                            SupportedPlugins::isActive(SupportedPlugins::PLUGIN_YOAST_SEO)
                            || SupportedPlugins::isActive(SupportedPlugins::PLUGIN_YOAST_SEO_PREMIUM)
                        ) {
                            /** @var array<string, array<int, array<string, string>>>|false $taxonomySeo */
                            $taxonomySeo = \get_option('wpseo_taxonomy_meta', false);

                            if ($taxonomySeo === false) {
                                $taxonomySeo = [$taxonomy => []];
                            }

                            if (!isset($taxonomySeo[$taxonomy])) {
                                $taxonomySeo[$taxonomy] = [];
                            }
                            $exists = false;

                            foreach ($taxonomySeo[$taxonomy] as $brandKey => $seoData) {
                                if ($brandKey === (int)$term->term_id) {
                                    $exists                                             = true;
                                    $taxonomySeo[$taxonomy][$brandKey]['wpseo_desc']    = $i18n->getMetaDescription();
                                    $taxonomySeo[$taxonomy][$brandKey]['wpseo_focuskw'] = $i18n->getMetaKeywords();
                                    $taxonomySeo[$taxonomy][$brandKey]['wpseo_title']   = \strcmp(
                                        $i18n->getTitleTag(),
                                        ''
                                    ) === 0 ? $model->getName() : $i18n->getTitleTag();
                                }
                            }
                            if ($exists === false) {
                                $taxonomySeo[$taxonomy][(int)$term->term_id] = [
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
                    if (SupportedPlugins::isPerfectWooCommerceBrandsActive()) {
                        /** @var WpmlPerfectWooCommerceBrands $wpmlPerfectWcBrands */
                        $wpmlPerfectWcBrands = $this->wpml->getComponent(WpmlPerfectWooCommerceBrands::class);
                        $wpmlPerfectWcBrands->saveTranslations($model);
                    } elseif (SupportedPlugins::isGermanizedActive()) {
                        /** @var WpmlGermanized $wpmlGermanized */
                        $wpmlGermanized = $this->wpml->getComponent(WpmlGermanized::class);
                        $wpmlGermanized->saveTranslations($model);
                    }
                }
            }

            $returnModels[] = $model;
        }
        return $returnModels;
    }

    /**
     * @param AbstractModel ...$models
     * @return AbstractModel[]
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    public function delete(AbstractModel ...$models): array
    {
        $returnModels = [];
        $taxonomy     = '';

        if (SupportedPlugins::isPerfectWooCommerceBrandsActive()) {
            $taxonomy = self::TAXONOMY_PERFECT_BRANDS;
        } elseif (SupportedPlugins::isGermanizedActive()) {
            $taxonomy = self::TAXONOMY_GERMANIZED;
        }

        foreach ($models as $model) {
            if (!empty($taxonomy)) {
                /** @var Manufacturer $model */
                $manufacturerId = (int)$model->getId()->getEndpoint();

                if (!empty($manufacturerId)) {
                    unset(self::$idCache[$model->getId()->getHost()]);

                    if ($this->wpml->canBeUsed()) {
                        if (SupportedPlugins::isPerfectWooCommerceBrandsActive()) {
                            /** @var WpmlPerfectWooCommerceBrands $wpmlPerfectWcBrands */
                            $wpmlPerfectWcBrands = $this->wpml->getComponent(WpmlPerfectWooCommerceBrands::class);
                            $wpmlPerfectWcBrands->deleteTranslations($manufacturerId);
                        } elseif (SupportedPlugins::isGermanizedActive()) {
                            /** @var WpmlGermanized $wpmlGermanized */
                            $wpmlGermanized = $this->wpml->getComponent(WpmlGermanized::class);
                            $wpmlGermanized->deleteTranslations($manufacturerId);
                        }
                    }

                    \wp_delete_term($manufacturerId, $taxonomy);
                }
            }

            $returnModels[] = $model;
        }
        return $returnModels;
    }

    /**
     * @param QueryFilter $query
     * @return int
     * @throws InvalidArgumentException
     * @throws \Exception
     */
    public function statistic(QueryFilter $query): int
    {
        $total = 0;
        if (SupportedPlugins::isPerfectWooCommerceBrandsActive()) {
            if ($this->wpml->canBeUsed()) {
                /** @var WpmlPerfectWooCommerceBrands $wpmlPerfectWcBrands */
                $wpmlPerfectWcBrands = $this->wpml->getComponent(WpmlPerfectWooCommerceBrands::class);
                $total               = $wpmlPerfectWcBrands->getStats();
            } else {
                $total = $this->db->queryOne(SqlHelper::manufacturerStats(self::TAXONOMY_PERFECT_BRANDS));
            }
        } elseif (SupportedPlugins::isGermanizedActive()) {
            if ($this->wpml->canBeUsed()) {
                /** @var WpmlGermanized $wpmlGermanized */
                $wpmlGermanized = $this->wpml->getComponent(WpmlGermanized::class);
                $total          = $wpmlGermanized->getStats();
            } else {
                $total = $this->db->queryOne(SqlHelper::manufacturerStats(self::TAXONOMY_GERMANIZED));
            }
        }
        return $total ? (int)$total : 0;
    }
}
