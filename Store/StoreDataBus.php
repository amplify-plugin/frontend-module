<?php

namespace Amplify\Frontend\Store;

use Amplify\ErpApi\Wrappers\Quotation;
use Amplify\System\Cms\Models\Page;
use Amplify\System\Helpers\UtilityHelper;
use Amplify\System\Marketing\Models\Campaign;
use Amplify\System\Sayt\Classes\CategoriesInfo;
use Amplify\System\Sayt\Classes\RemoteResults;
use Amplify\System\Sayt\Facade\Sayt;
use App\Models\Contact;
use App\Models\ContactLogin;
use App\Models\CustomerAddress;
use App\Models\CustomerRole;
use App\Models\FaqCategory;
use App\Models\Order;
use App\Models\OrderList;
use App\Models\Product;
use App\Models\Webinar;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Traits\Macroable;
use Spatie\Honeypot\Honeypot;

/**
 * @property null|Page $dynamicPageModel
 * @property null|string $pageTitle
 * @property RemoteResults $eaProductsData
 * @property RemoteResults $eaProductDetail
 * @property Contact $contactModel
 * @property CustomerRole $contactRoleModel
 * @property CustomerAddress $addressModel
 * @property FaqCategory $faqCategoryModel
 * @property OrderList $favouriteModel
 * @property Quotation $quotationWrapper
 * @property Webinar $webinar
 * @property Campaign $campaign
 * @property LengthAwarePaginator $contactLoginPaginate
 * @property LengthAwarePaginator $webinarPaginate
 * @property Honeypot $honeyPot
 * @property Product $productModel
 * @property CategoriesInfo $eaCategory
 */
class StoreDataBus
{
    use Macroable;

    private static $instance;

    protected function __construct() {}

    protected function __clone() {}

    public static function init(): static
    {
        if (! self::$instance) {
            self::$instance = new static;
        }

        return self::$instance;
    }

    protected array $attributes = [];

    public array $map_setters = [
        'eaProductsData' => [
            Sayt::class, 'search',
        ],
        'eaProductDetail' => [
            Sayt::class, 'getProductDetailsFromUrl',
        ],
        'dynamicPageModel' => [
            Page::class, 'guessCurrentPage',
        ],
        'pageTitle' => [
            Page::class, 'guessCurrentPageTitle',
        ],
        'contactModel' => [
            Contact::class, 'guessCurrentModel',
        ],
        'contactRoleModel' => [
            CustomerRole::class, 'guessCurrentModel',
        ],
        'addressModel' => [
            CustomerAddress::class, 'guessCurrentModel',
        ],
        'faqCategoryModel' => [
            FaqCategory::class, 'guessCurrentModel',
        ],
        'favouriteModel' => [
            OrderList::class, 'guessFavouriteModel',
        ],
        'quotationWrapper' => [
            Order::class, 'guessQuotationWrapper',
        ],
        'orderWrapper' => [
            Order::class, 'guessOrderWrapper',
        ],
        'campaign' => [
            Campaign::class, 'guessCurrentCampaign',
        ],
        'webinar' => [
            Webinar::class, 'guessCurrentWebinar',
        ],
        'webinarPaginate' => [
            Webinar::class, 'fetchWebinarPagination',
        ],
        'contactLoginPaginate' => [
            ContactLogin::class, 'fetchContactLoginPagination',
        ],
        'honeyPot' => [
            UtilityHelper::class, 'honeypot',
        ],
        'productModel' => [
            Product::class, 'guessSingleProductDetail',
        ],
        'eaCategory' => [
            Sayt::class, 'getCategory',
        ],

    ];

    public function __get($name)
    {
        return $this->offsetGet($name);
    }

    public function __set($name, $value)
    {
        $this->offsetSet($name, $value);
    }

    public function offsetGet($offset)
    {
        if (array_key_exists($offset, $this->attributes)) {
            return $this->attributes[$offset];
        }

        if (array_key_exists($offset, static::$macros)) {
            $response = call_user_func(static::$macros[$offset]);
            $this->attributes[$offset] = $response;
            return $response;
        }

        return $this->offsetSet($offset, '__from_getters__');
    }

    public function offsetSet($offset, $value)
    {
        if ($value === '__from_getters__') {
            if (array_key_exists($offset, $this->attributes)) {
                return $this->attributes[$offset];
            }

            return $this->attributes[$offset] = $this->resolveDataByOffset($offset);
        }

        $this->attributes[$offset] = $value;
    }

    /**
     * @throws Exception
     */
    private function resolveDataByOffset($name)
    {
        if (array_key_exists($name, $this->map_setters)) {
            return call_user_func([
                $this->map_setters[$name][0],
                $this->map_setters[$name][1],
            ]);
        }

        throw new Exception("Can't resolve ModelDataBus::resolveDataByOffset()", 500);
    }

    public function has($key): bool
    {
        return in_array($key, [...array_keys($this->map_setters), ...array_keys(static::$macros)], true);
    }

    public function all(): array
    {
        return $this->attributes;
    }
}
