<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Integrations\Plugins\Wpml;

use Jtl\Connector\Core\Model\Specific;
use Jtl\Connector\Core\Model\SpecificI18n as SpecificI18nModel;
use Jtl\Connector\Core\Model\SpecificValue;
use Jtl\Connector\Core\Model\SpecificValueI18n as SpecificValueI18nModel;
use JtlWooCommerceConnector\Integrations\Plugins\AbstractComponent;
use JtlWooCommerceConnector\Integrations\Plugins\WooCommerce\WooCommerce;
use JtlWooCommerceConnector\Integrations\Plugins\WooCommerce\WooCommerceSpecificValue;
use PHPUnit\Framework\ExpectationFailedException;
use WPML\Auryn\InjectionException;

/**
 * Class WpmlSpecificValue
 *
 * @package JtlWooCommerceConnector\Integrations\Plugins\Wpml
 */
class WpmlSpecificValue extends AbstractComponent
{
    /**
     * @param SpecificValue $specificValue
     * @param int           $mainSpecificValueId
     * @param string        $elementType
     * @return void
     * @throws \Exception
     */
    public function getTranslations(SpecificValue $specificValue, int $mainSpecificValueId, string $elementType): void
    {
        /** @var Wpml $wpmlPlugin */
        $wpmlPlugin = $this->getCurrentPlugin();
        $trid       = $wpmlPlugin->getElementTrid($mainSpecificValueId, 'tax_' . $elementType);

        /** @var WpmlTermTranslation $wpmlTermTranslation */
        $wpmlTermTranslation = $wpmlPlugin->getComponent(WpmlTermTranslation::class);
        $translations        = $wpmlTermTranslation
            ->getTranslations((int)$trid, 'tax_' . $elementType, true);

        foreach ($translations as $languageCode => $translation) {
            $specificValue->addI18n((new SpecificValueI18nModel())
                ->setLanguageISO($wpmlPlugin->convertLanguageToWawi($languageCode))
                ->setValue($translation->name));
        }
    }

    /**
     * @param string                 $taxonomy
     * @param SpecificValue          $specificValue
     * @param SpecificValueI18nModel $defaultTranslation
     * @param int                    $mainSpecificValueId
     * @return void
     * @throws ExpectationFailedException
     * @throws InjectionException
     */
    public function setTranslationsDeprecated(
        string $taxonomy,
        SpecificValue $specificValue,
        SpecificValueI18nModel $defaultTranslation,
        int $mainSpecificValueId
    ): void {
        $type = 'tax_' . $taxonomy;

        /** @var Wpml $wpmlPlugin */
        $wpmlPlugin = $this->getCurrentPlugin();
        $trid       = $wpmlPlugin->getElementTrid($mainSpecificValueId, $type);

        foreach ($specificValue->getI18ns() as $specificValueI18n) {
            $languageCode = $wpmlPlugin->convertLanguageToWpml($specificValueI18n->getLanguageISO());

            if ($wpmlPlugin->getDefaultLanguage() === $languageCode) {
                continue;
            }

            $translatedName = \apply_filters(
                'wpml_translate_single_string',
                $defaultTranslation->getValue(),
                'WordPress',
                $defaultTranslation->getValue(),
                $languageCode
            );

            if ($translatedName !== $specificValueI18n->getValue()) {
                \icl_register_string(
                    'WordPress',
                    \sprintf('taxonomy singular name: %s', $defaultTranslation->getValue()),
                    $defaultTranslation->getValue(),
                    false,
                    $wpmlPlugin->getDefaultLanguage()
                );

                // Übersetzung hinzufügen
                \icl_add_string_translation(
                    \icl_get_string_id(
                        $defaultTranslation->getValue(),
                        'WordPress',
                        \sprintf('taxonomy singular name: %s', $defaultTranslation->getValue())
                    ),
                    $languageCode,
                    $specificValueI18n->getValue(),
                    \ICL_TM_COMPLETE
                );
            }

            $specificTranslation = $this->findSpecificValueTranslation((int)$trid, $taxonomy, $languageCode);
            if (isset($specificTranslation['term_taxonomy_id'])) {
                $specificValue->getId()->setEndpoint($specificTranslation['term_taxonomy_id']);
            } else {
                $specificValue->getId()->setEndpoint('0');
            }

            $slug = \wc_sanitize_taxonomy_name($specificValueI18n->getValue()) . '-' . $languageCode;

            /** @var WooCommerceSpecificValue $wooCommerceSpecificValue */
            $wooCommerceSpecificValue = $wpmlPlugin
                ->getPluginsManager()
                ->get(WooCommerce::class)
                ->getComponent(WooCommerceSpecificValue::class);

            $specificValueId = $wooCommerceSpecificValue
                ->save($taxonomy, $specificValue, $specificValueI18n, $slug);

            if (!\is_null($specificValueId) && $specificValueId !== 0) {
                $wpmlPlugin->getSitepress()->set_element_language_details(
                    $specificValueId,
                    $type,
                    (int)$trid,
                    $languageCode
                );

                \do_action('wpml_add_term_translation', [
                    'element_id'    => $specificValueId,
                    'element_type'  => $type,
                    'trid'          => $trid,
                    'language_code' => $languageCode,
                ]);
            }
        }
    }

    /**
     * @param string                 $taxonomy
     * @param SpecificValue          $specificValue
     * @param SpecificValueI18nModel $defaultTranslation
     * @param int                    $mainSpecificValueId
     * @return void
     * @throws ExpectationFailedException
     * @throws InjectionException
     */
    public function setTranslations(
        string $taxonomy,
        SpecificValue $specificValue,
        SpecificValueI18nModel $defaultTranslation,
        int $mainSpecificValueId
    ): void {
        $type = 'tax_' . $taxonomy;

        /** @var Wpml $wpmlPlugin */
        $wpmlPlugin = $this->getCurrentPlugin();
        $trid       = $wpmlPlugin->getElementTrid($mainSpecificValueId, $type);

        foreach ($specificValue->getI18ns() as $specificValueI18n) {
            $languageCode = $wpmlPlugin->convertLanguageToWpml($specificValueI18n->getLanguageISO());

            if ($wpmlPlugin->getDefaultLanguage() === $languageCode) {
                continue;
            }

            $translatedName = \apply_filters(
                'wpml_translate_single_string',
                $defaultTranslation->getValue(),
                'WordPress',
                $defaultTranslation->getValue(),
                $languageCode
            );

            if ($translatedName !== $specificValueI18n->getValue()) {
                \icl_register_string(
                    'WordPress',
                    \sprintf('taxonomy singular name: %s', $defaultTranslation->getValue()),
                    $defaultTranslation->getValue(),
                    false,
                    $wpmlPlugin->getDefaultLanguage()
                );

                // Übersetzung hinzufügen
                \icl_add_string_translation(
                    \icl_get_string_id(
                        $defaultTranslation->getValue(),
                        'WordPress',
                        \sprintf('taxonomy singular name: %s', $defaultTranslation->getValue())
                    ),
                    $languageCode,
                    $specificValueI18n->getValue(),
                    \ICL_TM_COMPLETE
                );

                $translated_term = \get_term_by('name', $specificValueI18n->getValue());

                if (!$translated_term) {
                    $term_result = \wp_insert_term($specificValueI18n->getValue(), $taxonomy);
                    if (\is_wp_error($term_result)) {
                        $this->logger->error("Error while creating term: " . $term_result->get_error_message());
                        return;
                    }
                    $translated_term_id = $term_result['term_id'];
                } else {
                    $translated_term_id = $translated_term->term_id;
                }

                \do_action('wpml_add_term_translation', [
                    'element_id'    => $translated_term_id,
                    'element_type'  => $type,
                    'trid'          => $trid,
                    'language_code' => $languageCode,
                ]);

                #sicher stellen, dass EN Produkt mit der term_id von clever verknüpft wird
                \do_action('wpml_set_element_language_details', [
                    'element_id'    => $translated_term_id,
                    'element_type'  => $type,
                    'trid'          => $trid,
                    'language_code' => $languageCode,
                    'source_language_code' => $wpmlPlugin->getDefaultLanguage(),
                ]);
            }
        }
    }

    /**
     * @param int    $trid
     * @param string $taxonomy
     * @param string $languageCode
     * @return array<string, string>
     */
    public function findSpecificValueTranslation(int $trid, string $taxonomy, string $languageCode): array
    {
        /** @var WpmlTermTranslation $wpmlTermTranslation */
        $wpmlTermTranslation = $this->getCurrentPlugin()->getComponent(WpmlTermTranslation::class);

        $specificTranslations = $wpmlTermTranslation
            ->getTranslations($trid, 'tax_' . $taxonomy, false);

        $translation = [];
        if (isset($specificTranslations[$languageCode])) {
            $translationData = $specificTranslations[$languageCode];
            $translation     = $wpmlTermTranslation
                ->getTranslatedTerm($translationData->term_id, $taxonomy);
        }
        return $translation;
    }
}
