<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Codesicle\WishlistAjax\Block\Product\Compare;

/**
 * Class ListCompare
 * Override the extended magento class
 */
class ListCompare extends \Magento\Catalog\Block\Product\Compare\ListCompare
{

    /**
     * Get add to wishlist params override
     *
     * @param \Magento\Catalog\Model\Product $product
     * @param null $ajax
     * @return string
     */
    public function getAddToWishlistParams($product, $ajax = null)
    {
        if ($ajax === null) {
            return parent::getAddToWishlistParams($product);
        } else {
            $params['ajax'] = $ajax;
            return $this->_wishlistHelper->getAddParams($product, $params);
        }
    }
}
