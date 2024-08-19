<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Integrations\Plugins\PerfectWooCommerceBrands;

use Jtl\Connector\Core\Model\Manufacturer;
use Jtl\Connector\Core\Model\ManufacturerI18n as ManufacturerI18nModel;
use JtlWooCommerceConnector\Integrations\Plugins\AbstractPlugin;
use JtlWooCommerceConnector\Integrations\Plugins\RankMathSeo\RankMathSeo;
use JtlWooCommerceConnector\Integrations\Plugins\YoastSeo\YoastSeo;
use JtlWooCommerceConnector\Logger\ErrorFormatter;
use JtlWooCommerceConnector\Utilities\SqlHelper;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use JtlWooCommerceConnector\Utilities\Util;
use Psr\Log\InvalidArgumentException;
use WP_Error;
use WP_Term;

/**
 * Class PerfectWooCommerceBrands
 *
 * @package JtlWooCommerceConnector\Integrations\Plugins\PerfectWooCommerceBrands
 */
class PerfectWooCommerceBrands extends AbstractPlugin
{
    /**
     * @return bool
     */
    public function canBeUsed(): bool
    {
        return SupportedPlugins::isPerfectWooCommerceBrandsActive();
    }

    /**
     * @param Manufacturer $manufacturer
     * @param string       $languageIso
     * @param string       $description
     * @param int          $termId
     * @return ManufacturerI18nModel
     * @throws \Exception
     */
    public function createManufacturerI18n(
        Manufacturer $manufacturer,
        string $languageIso,
        string $description,
        int $termId
    ): ManufacturerI18nModel {
        $db   = $this->getPluginsManager()->getDatabase();
        $util = new Util($db);

        $i18n = (new ManufacturerI18nModel())
            ->setLanguageISO($languageIso)
            ->setDescription($description);

        /** @var YoastSeo $yoastSeo */
        $yoastSeo = $this->getPluginsManager()->get(YoastSeo::class);
        /** @var RankMathSeo $rankMathSeo */
        $rankMathSeo = $this->getPluginsManager()->get(RankMathSeo::class);
        if ($yoastSeo->canBeUsed()) {
            $seoData = $yoastSeo->findManufacturerSeoData($termId);
            if (!empty($seoData)) {
                $i18n->setMetaDescription($seoData['wpseo_desc'] ?? '')
                    ->setMetaKeywords($seoData['wpseo_focuskw'] ?? $manufacturer->getName())
                    ->setTitleTag($seoData['wpseo_title'] ?? $manufacturer->getName());
            }
        } elseif ($rankMathSeo->canBeUsed()) {
            $seoData = $rankMathSeo->findManufacturerSeoData($termId);
            if (!empty($seoData)) {
                $util->setI18nRankMathSeo($i18n, $seoData);
            }
        }

        return $i18n;
    }

    /**
     * @param Manufacturer          $jtlManufacturer
     * @param ManufacturerI18nModel $manufacturerI18n
     * @return array<string, int|string|null>|false|WP_Error|\WP_Term
     * @throws \Exception
     */
    public function saveManufacturer(
        Manufacturer $jtlManufacturer,
        ManufacturerI18nModel $manufacturerI18n
    ): \WP_Term|array|WP_Error|bool {
        $manufacturerTerm = $this->getManufacturerBySlug($jtlManufacturer->getName());

        \remove_filter('pre_term_description', 'wp_filter_kses');

        if ($manufacturerTerm === false) {
            $slug                = $this->sanitizeSlug($jtlManufacturer->getName());
            $newManufacturerTerm = $this->createManufacturer($slug, $jtlManufacturer->getName(), $manufacturerI18n);

            if ($newManufacturerTerm instanceof WP_Error) {
                $error = new WP_Error('invalid_taxonomy', 'Could not create manufacturer.');
                $this->logger->error(ErrorFormatter::formatError($error));
                $this->logger->error(ErrorFormatter::formatError($newManufacturerTerm));
            }
            $manufacturerTerm = $newManufacturerTerm;

            if (\is_array($manufacturerTerm) && \array_key_exists('term_id', $manufacturerTerm)) {
                /** @var false|WP_Term $manufacturerTerm */
                $manufacturerTerm = \get_term_by('id', $manufacturerTerm['term_id'], 'pwb-brand');
            }
        } else {
            $this->updateManufacturer((int) $manufacturerTerm->term_id, $jtlManufacturer->getName(), $manufacturerI18n);
        }

        \add_filter('pre_term_description', 'wp_filter_kses');

        if ($manufacturerTerm instanceof \WP_Term) {
            $jtlManufacturer->getId()->setEndpoint((string)$manufacturerTerm->term_id);

            /** @var RankMathSeo $rankMathSeo */
            $rankMathSeo = $this->getPluginsManager()->get(RankMathSeo::class);
            /** @var YoastSeo $yoastSeo */
            $yoastSeo = $this->getPluginsManager()->get(YoastSeo::class);
            if ($yoastSeo->canBeUsed()) {
                $yoastSeo->setManufacturerSeoData((int)$manufacturerTerm->term_id, $manufacturerI18n);
            } elseif ($rankMathSeo->canBeUsed()) {
                $rankMathSeo->updateWpSeoTaxonomyMeta((int)$manufacturerTerm->term_id, $manufacturerI18n);
            }
        }

        return $manufacturerTerm;
    }

    /**
     * @param string $manufacturerName
     * @param string $languageSuffix
     * @return string
     */
    public function sanitizeSlug(string $manufacturerName, string $languageSuffix = ''): string
    {
        $manufacturerName = \substr(\trim($manufacturerName), 0, 27);

        if (!empty($languageSuffix)) {
            if (\mb_strlen($manufacturerName) > 24) {
                $manufacturerName = \substr($manufacturerName, 0, 24);
            }

            $manufacturerName .= '-' . $languageSuffix;
        }

        return \wc_sanitize_taxonomy_name($manufacturerName);
    }

    /**
     * @param string $manufacturerName
     * @return false|\WP_Term
     */
    public function getManufacturerBySlug(string $manufacturerName): false|WP_Term
    {
        /** @var false|WP_Term $term */
        $term = \get_term_by('slug', $manufacturerName, 'pwb-brand');
        return $term;
    }

    /**
     * @param string                $slug
     * @param string                $manufacturerName
     * @param ManufacturerI18nModel $manufacturerI18n
     * @return array<string, int|string>|WP_Error
     */
    public function createManufacturer(
        string $slug,
        string $manufacturerName,
        ManufacturerI18nModel $manufacturerI18n
    ): array|WP_Error {
        return \wp_insert_term(
            $manufacturerName,
            'pwb-brand',
            [
                'description' => $manufacturerI18n->getDescription(),
                'slug' => $slug,
            ]
        );
    }

    /**
     * @param int                   $termId
     * @param string                $manufacturerName
     * @param ManufacturerI18nModel $manufacturerI18
     * @return array<string, int|string>|WP_Error
     */
    public function updateManufacturer(
        int $termId,
        string $manufacturerName,
        ManufacturerI18nModel $manufacturerI18
    ): WP_Error|array {
        return \wp_update_term($termId, 'pwb-brand', [
            'name' => $manufacturerName,
            'description' => $manufacturerI18->getDescription(),
        ]);
    }

    /**
     * @param int $limit
     * @return array<int, array<string, int|string|null>>
     * @throws InvalidArgumentException
     */
    public function getManufacturers(int $limit): array
    {
        $sql = SqlHelper::manufacturerPull($limit);

        /** @var array<int, array<string, int|string|null>> $manufacturers */
        $manufacturers =  $this->getPluginsManager()->getDatabase()->query($sql) ?? [];

        return $manufacturers;
    }

    /**
     * @return int
     * @throws InvalidArgumentException
     */
    public function getStats(): int
    {
        return (int) $this->getPluginsManager()->getDatabase()->queryOne(SqlHelper::manufacturerStats());
    }
}
