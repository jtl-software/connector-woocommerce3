<?php
/**
 * @author    Jan Weskamp <jan.weskamp@jtl-software.com>
 * @copyright 2010-2013 JTL-Software GmbH
 */

namespace JtlWooCommerceConnector\Authentication;

use Jtl\Connector\Core\Authentication\TokenValidatorInterface;

class TokenValidator implements TokenValidatorInterface
{
    /**
     * @var string
     */
    protected $endpointToken;

    /**
     * @param string $endpointToken
     */
    public function __construct(string $endpointToken = '')
    {
        $this->endpointToken = $endpointToken;
    }

    /**
     * @throws \Exception
     */
    public function validate(string $token): bool
    {
        return $token === $this->endpointToken;
    }
}
