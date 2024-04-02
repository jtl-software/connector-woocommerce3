<?php

namespace JtlWooCommerceConnector\Integrations\Plugins\Wpml;

use Jtl\Connector\Core\Model\Language;//TODO
use JtlWooCommerceConnector\Integrations\Plugins\AbstractPlugin;
use JtlWooCommerceConnector\Utilities\Db;
use JtlWooCommerceConnector\Utilities\SupportedPlugins;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LogLevel;
use SitePress;
use woocommerce_wpml;
use wpdb;

/**
 * Class Wpml
 * @package JtlWooCommerceConnector\Integrations\Plugins\Wpml
 */
class Wpml extends AbstractPlugin
{
    /**
     * @var Db
     */
    protected $database;

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
     * @return array
     */
    public function getActiveLanguages(): array
    {
        return $this->getSitepress()->get_active_languages();//TODO
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
     * @throws \jtl\Connector\Core\Exception\LanguageException//TODO
     */
    public function isDefaultLanguage(string $wawiLanguageIso): bool
    {
        return $this->getDefaultLanguage() === $this->convertLanguageToWpml($wawiLanguageIso);
    }

    /**
     * @param string $wpmlLanguageCode
     * @return string
     * @throws \jtl\Connector\Core\Exception\LanguageException
     */
    public function convertLanguageToWawi(string $wpmlLanguageCode): string
    {
        $wpmlLanguageCode = \substr($wpmlLanguageCode, 0, 2);
        $language         = Language::convert($wpmlLanguageCode);
        if (\is_null($language)) {
            $this->logger->warning(
                \sprintf("Cannot find corresponding language code %s", $wpmlLanguageCode)
            );
            $language = '';
        }
        return $language;
    }

    /**
     * @param string $wawiLanguageCode
     * @return false|int|mixed|string|null
     * @throws \jtl\Connector\Core\Exception\LanguageException//TODO
     */
    public function convertLanguageToWpml(string $wawiLanguageCode): string
    {
        $language = Language::convert(null, $wawiLanguageCode);//TODO
        return $language ?? '';
    }

    /**
     * @return bool
     */
    public function canWpmlMediaBeUsed(): bool//TODO
    {
        return SupportedPlugins::isActive(SupportedPlugins::PLUGIN_WPML_MEDIA)
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
     * @param int $termId
     * @param string $elementType
     * @return int
     */
    public function getElementTrid(int $termId, string $elementType): int//TODO
    {
        $trid = (int)$this->getSitepress()->get_element_trid($termId, $elementType);

        if ($trid === 0) {
            $this->getSitepress()->set_element_language_details(
                $termId,
                $elementType,
                $trid,
                $this->getDefaultLanguage()
            );

            $trid = (int)$this->getSitepress()->get_element_trid($termId, $elementType);
        }

        return $trid;
    }

    /**
     * @return bool
     */
    protected function isSetupCompleted(): bool//TODO
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
