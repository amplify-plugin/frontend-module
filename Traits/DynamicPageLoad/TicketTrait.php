<?php

namespace Amplify\Frontend\Traits\DynamicPageLoad;

trait TicketTrait
{
    public function getTicketData(&$data) {}

    public function getTicketListData(&$data) {}

    public function getTicketDetailsData(&$data, $id) {}
}
