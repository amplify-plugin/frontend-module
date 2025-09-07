<?php

namespace Amplify\Frontend\Traits\DynamicPageLoad;

use Amplify\System\Backend\Facades\CenPos;

trait PaymentTrait
{
    public function processPaymentPrefix(&$data): int
    {
        $data['verifyingPost'] = CenPos::getVerifyingPost();
        $data['customerCode'] = request()->customer_code ?? \ErpApi::getCustomerDetail()->CustomerNumber;

        return 0;
    }
}
