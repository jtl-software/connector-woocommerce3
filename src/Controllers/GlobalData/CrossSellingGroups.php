<?php

namespace JtlWooCommerceConnector\Controllers\GlobalData;

use jtl\Connector\Model\CrossSellingGroupI18n;
use JtlWooCommerceConnector\Controllers\BaseController;
use JtlWooCommerceConnector\Controllers\Traits\PullTrait;
use JtlWooCommerceConnector\Models\CrossSellingGroup;

/**
 * Class CrossSelling
 * @package JtlWooCommerceConnector\Controllers\GlobalData
 */
class CrossSellingGroups extends BaseController
{
    use PullTrait;

    /**
     * @return array
     * @throws \jtl\Connector\Core\Exception\LanguageException
     */
    public function pullData()
    {
        $crossSellingGroups = CrossSellingGroup::all();

        if ($this->wpml->canBeUsed()) {

            $languages = $this->wpml->getActiveLanguages();
            foreach ($crossSellingGroups as $crossSellingGroup) {
                $defaultI18n = null;
                foreach ($crossSellingGroup->getI18ns() as $crossSellingGroupI18n) {
                    if ($crossSellingGroupI18n->getLanguageISO() === $this->wpml->convertLanguageToWawi($this->wpml->getDefaultLanguage())) {
                        $defaultI18n = $crossSellingGroupI18n;
                        break;
                    }
                }

                foreach ($languages as $languageCode => $language) {
                    $wawiLanguageCode = $this->wpml->convertLanguageToWawi($languageCode);
                    if (!is_null($defaultI18n) && $languageCode !== $this->wpml->getDefaultLanguage()) {
                        $i18n = (new CrossSellingGroupI18n())
                            ->setLanguageISO($wawiLanguageCode)
                            ->setName($defaultI18n->getName());
                        $crossSellingGroup->addI18n($i18n);
                    }
                }
            }
        }

        return $crossSellingGroups;
    }
}
