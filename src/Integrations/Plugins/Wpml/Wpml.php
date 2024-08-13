<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Integrations\Plugins\Wpml;

use Exception;
use JtlWooCommerceConnector\Integrations\Plugins\AbstractPlugin;
use JtlWooCommerceConnector\Utilities\Db;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use JtlWooCommerceConnector\Utilities\Util;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LogLevel;
use SitePress;
use woocommerce_wpml;
use wpdb;

/**
 * Class Wpml
 *
 * @package JtlWooCommerceConnector\Integrations\Plugins\Wpml
 */
class Wpml extends AbstractPlugin
{
    protected Db $database;

    /**
     * @return bool
     * @throws InvalidArgumentException
     */
    public function isMultiCurrencyEnabled(): bool
    {
        if (\wcml_is_multi_currency_on() === false) {
            $this->logger->log(LogLevel::INFO, "WPML multi-currency is not enabled.");
            return false;
        }
        return true;
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function getActiveLanguages(): array
    {
        /** @var array<string, array<string, string>> $activeLanguages */
        $activeLanguages = $this->getSitepress()->get_active_languages();
        return $activeLanguages;
    }

    /**
     * @return string
     */
    public function getDefaultLanguage(): string
    {
        return \wpml_get_default_language();
    }

    /**
     * @param string $wawiLanguageIso
     * @return bool
     * @throws Exception
     */
    public function isDefaultLanguage(string $wawiLanguageIso): bool
    {
        return $this->getDefaultLanguage() === $this->convertLanguageToWpml($wawiLanguageIso);
    }

    /**
     * @param string $wpmlLanguageCode
     * @return string
     * @throws Exception
     */
    public function convertLanguageToWawi(string $wpmlLanguageCode): string
    {
        $wpmlLanguageCode = \substr($wpmlLanguageCode, 0, 2);
        $language         = Util::mapLanguageIso($wpmlLanguageCode);
        // $language         = Language::convert($wpmlLanguageCode);

        return $language;
    }

    /**
     * @param string $wawiLanguageCode
     * @return string
     * @throws Exception
     */
    public function convertLanguageToWpml(string $wawiLanguageCode): string
    {
        $language = Util::mapLanguageIso($wawiLanguageCode);
        return $language;
    }

    /**
     * @return bool
     */
    public function canWpmlMediaBeUsed(): bool
    {
        return SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WPML_MEDIA_TRANSLATION)
            && (new \WPML_Media_Dependencies())->check();
    }

    /**
     * @return bool
     * @throws InvalidArgumentException
     */
    public function canBeUsed(): bool
    {
        $canUse = $this->isWpmlEnabled();
        if ($canUse === true) {
            $isSetupCompleted = $this->isSetupCompleted();
            if ($isSetupCompleted === false) {
                $this->logger->log(LogLevel::INFO, "WPML setup is not completed cannot use WPML.");
            }
            $canUse &= $isSetupCompleted;

            $isWooCommerceSetupCompleted = !empty($this->getWcml()->get_setting('set_up_wizard_run'));
            if ($isWooCommerceSetupCompleted === false) {
                $this->logger->log(LogLevel::INFO, "WCML setup is not completed cannot use WCML.");
            }
            $canUse &= $isWooCommerceSetupCompleted;
        }

        return (bool)$canUse;
    }

    /**
     * @return wpdb
     */
    public function getWpDb(): wpdb
    {
        global $wpdb;
        return $wpdb;
    }

    /**
     * @return woocommerce_wpml
     */
    public function getWcml(): woocommerce_wpml
    {
        global $woocommerce_wpml;
        return $woocommerce_wpml;
    }

    /**
     * @return SitePress
     */
    public function getSitepress(): SitePress
    {
        global $sitepress;
        return $sitepress;
    }

    /**
     * @param int    $termId
     * @param string $elementType
     * @return bool|int|string|null
     */
    public function getElementTrid(int $termId, string $elementType): bool|int|null|string
    {
        /** @var bool|int|string|null $trid */
        $trid = $this->getSitepress()->get_element_trid($termId, $elementType);

        if ($trid === 0) {
            $this->getSitepress()->set_element_language_details(
                $termId,
                $elementType,
                $trid,
                $this->getDefaultLanguage()
            );

            /** @var bool|int|string|null $trid */
            $trid = $this->getSitepress()->get_element_trid($termId, $elementType);
        }

        return $trid;
    }

    /**
     * @return bool
     */
    protected function isSetupCompleted(): bool
    {
        return (bool)\wpml_get_setting_filter(false, 'setup_complete');
    }

    /**
     * @return bool
     */
    protected function isWpmlEnabled(): bool
    {
        $plugins = [
            SupportedPlugins::PLUGIN_WPML_MULTILINGUAL_CMS,
            SupportedPlugins::PLUGIN_WPML_STRING_TRANSLATION,
            SupportedPlugins::PLUGIN_WOOCOMMERCE_MULTILUNGUAL
        ];

        return SupportedPlugins::areAllActive(...$plugins);
    }
}
