<?php
/**
 * Codesicle_WishlistAjax
 *
 * @category    Codesicle
 * @copyright   Copyright (c) 2019 Codesicle
 * @author      Vlad Patru <vp@codesicle.com>
 * @link        http://www.codesicle.com
 */

namespace Codesicle\WishlistAjax\Helper;

use Magento\Store\Model\ScopeInterface;

/**
 * Config helper
 */
class Config extends \Magento\Framework\App\Helper\AbstractHelper
{

    const AJAX_ENABLED = 'codesicle_wishlist/wishlist/ajax_enable';

    const USE_COMPARE = 'codesicle_wishlist/wishlist/in_compare';

    const USE_WIDGET = 'codesicle_wishlist/wishlist/in_widget';

    /**
     * @return bool
     */
    public function getWishlistAjax()
    {
        return $this->scopeConfig->getValue(self::AJAX_ENABLED, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return bool
     */
    public function getUseInCompare()
    {
        return $this->scopeConfig->getValue(self::USE_COMPARE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return bool
     */
    public function getUseInWidget()
    {
        return $this->scopeConfig->getValue(self::USE_WIDGET, ScopeInterface::SCOPE_STORE);
    }
}
