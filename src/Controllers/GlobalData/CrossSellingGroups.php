<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Controllers\GlobalData;

use Jtl\Connector\Core\Model\CrossSellingGroupI18n;
use JtlWooCommerceConnector\Controllers\AbstractBaseController;
use JtlWooCommerceConnector\Models\CrossSellingGroup;
use Psr\Log\InvalidArgumentException;

/**
 * Class CrossSelling
 *
 * @package JtlWooCommerceConnector\Controllers\GlobalData
 */
class CrossSellingGroups extends AbstractBaseController
{
    /**
     * @return \Jtl\Connector\Core\Model\CrossSellingGroup[]
     * @throws InvalidArgumentException
     * @throws \Exception
     */
    public function pull(): array
    {
        $crossSellingGroups = CrossSellingGroup::all($this->util);

        if ($this->wpml->canBeUsed()) {
            $languages = $this->wpml->getActiveLanguages();
            foreach ($crossSellingGroups as $crossSellingGroup) {
                $defaultI18n = null;
                foreach ($crossSellingGroup->getI18ns() as $crossSellingGroupI18n) {
                    if (
                        $crossSellingGroupI18n->getLanguageISO()
                        === $this->wpml->convertLanguageToWawi($this->wpml->getDefaultLanguage())
                    ) {
                        $defaultI18n = $crossSellingGroupI18n;
                        break;
                    }
                }

                foreach ($languages as $languageCode => $language) {
                    $wawiLanguageCode = $this->wpml->convertLanguageToWawi((string)$languageCode);
                    if (!\is_null($defaultI18n) && $languageCode !== $this->wpml->getDefaultLanguage()) {
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
