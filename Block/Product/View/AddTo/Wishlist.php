<?php
/**
 * Codesicle_WishlistAjax
 *
 * @category  Codesicle
 * @copyright Copyright (c) 2019 Codesicle
 * @author    Vlad Patru <vp@codesicle.com>
 * @link      http://www.codesicle.com
 */

namespace Codesicle\WishlistAjax\Block\Product\View\AddTo;

/**
 * Class Wishlist
 * Override the extended magento class
 */
class Wishlist extends \Magento\Wishlist\Block\Catalog\Product\View\AddTo\Wishlist
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
