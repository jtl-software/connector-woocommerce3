<?php

declare(strict_types=1);

namespace JtlWooCommerceConnector\Controllers\GlobalData;

use Jtl\Connector\Core\Model\Identity;
use Jtl\Connector\Core\Model\ShippingClass as ShippingClassModel;
use JtlWooCommerceConnector\Controllers\AbstractBaseController;
use JtlWooCommerceConnector\Logger\ErrorFormatter;
use Psr\Log\InvalidArgumentException;

class ShippingClassController extends AbstractBaseController
{
    public const TERM_TAXONOMY = 'product_shipping_class';

    /**
     * @return array<int, ShippingClassModel>
     */
    public function pull(): array
    {
        $shippingClasses = [];

        foreach (\WC()->shipping()->get_shipping_classes() as $shippingClass) {
            $shippingClasses[] = (new ShippingClassModel())
                ->setId(new Identity((string)$shippingClass->term_id))
                ->setName($shippingClass->name);
        }

        return $shippingClasses;
    }

    /**
     * @param ShippingClassModel[] $shippingClasses
     * @return ShippingClassModel[]
     * @throws InvalidArgumentException
     */
    public function push(array $shippingClasses): array
    {
        foreach ($shippingClasses as $shippingClass) {
            $term = \get_term_by('name', $shippingClass->getName(), self::TERM_TAXONOMY, \OBJECT);

            if ($term === false) {
                $result = \wp_insert_term($shippingClass->getName(), self::TERM_TAXONOMY);

                if ($result instanceof \WP_Error) {
                    $this->logger->error(ErrorFormatter::formatError($result));
                    continue;
                }

                $shippingClass->getId()->setEndpoint((string)$result['term_id']);
            } else {
                if ($term instanceof \WP_Term) {
                    $shippingClass->getId()->setEndpoint((string)$term->term_id);
                }
            }
        }

        return $shippingClasses;
    }
}
