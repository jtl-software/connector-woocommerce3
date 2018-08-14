<?php
namespace jtl\Connector\WooCommerce\Integrity;

use Jtl\Connector\Integrity\Models\Http\Response;

class JTLIntegrityCheckResponse extends Response
{
    public function outArray(){
        return $this->jsonSerialize();
    }
}
