<?php

namespace Amplify\Frontend\Traits\DynamicPageLoad;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

trait SingleProductTrait
{
    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws \JsonException
     */
    public function getSingleProductData($param, &$data) {}
}
