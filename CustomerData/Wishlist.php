<?php
/**
 * Codesicle_WishlistAjax
 *
 * @category    Codesicle
 * @copyright   Copyright (c) 2019 Codesicle
 * @author      Vlad Patru <vp@codesicle.com>
 * @link        http://www.codesicle.com
 */

namespace Codesicle\WishlistAjax\CustomerData;

use Magento\Catalog\Helper\ImageFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ViewInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Wishlist\Block\Customer\Sidebar;
use Magento\Wishlist\Helper\Data;

/**
 * Wishlist section
 */
class Wishlist extends \Magento\Wishlist\CustomerData\Wishlist
{
    const WISHLIST_ITEM_COUNTER = 'codesicle_wishlist/wishlist/counter';

    const SHOW_OUT_OF_STOCK = 'cataloginventory/options/show_out_of_stock';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Wishlist constructor.
     *
     * @param Data $wishlistHelper
     * @param Sidebar $block
     * @param ImageFactory $imageHelperFactory
     * @param ViewInterface $view
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Data $wishlistHelper,
        Sidebar $block,
        ImageFactory $imageFactory,
        ViewInterface $view,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
        parent::__construct($wishlistHelper, $block, $imageFactory, $view);
    }

    /**
     * @{inheritdoc}
     */
    protected function getCounter()
    {
        $wishlistCounter = $this->scopeConfig->getValue(
            self::WISHLIST_ITEM_COUNTER,
            ScopeInterface::SCOPE_STORE
        );
        if ($this->wishlistHelper->getCustomer()) {
            if ($wishlistCounter == 1) {
                return (string)$this->wishlistHelper->getItemCount();
            } else {
                return $this->createCounter($this->wishlistHelper->getItemCount());
            }
        }
    }

    /**
     * In this method we eliminate the out of stock products from the collection if
     * is set in admin in the 'Display Out of Stock Products' and we remove the
     * wishlist limit set to 3 items we need to remove the limitation for the
     * checking if the item is in wishlist
     *
     * @{inheritdoc}
     */
    protected function getItems()
    {
        $this->view->loadLayout();

        $collection = $this->wishlistHelper->getWishlistItemCollection();
        $collection->clear();

        $outOfStock = $this->scopeConfig->getValue(
            self::SHOW_OUT_OF_STOCK,
            ScopeInterface::SCOPE_STORE
        );

        if ($outOfStock == 1) {
            $collection->setInStockFilter(true);
        }

        $collection->setOrder('added_at');

        $items = [];
        foreach ($collection as $wishlistItem) {
            $items[] = $this->getItemData($wishlistItem);
        }

        return $items;
    }

    /**
     * @param \Magento\Wishlist\Model\Item $wishlistItem
     * @return array
     */
    protected function getItemData(\Magento\Wishlist\Model\Item $wishlistItem)
    {
        $itemData = parent::getItemData($wishlistItem);

        $itemData['product'] = $wishlistItem->getProduct()->getId();
        $itemData['add_to_cart_params'] = $this->wishlistHelper->getAddToCartParams($wishlistItem, true);
        $itemData['delete_item_params'] = $this->wishlistHelper->getRemoveParams($wishlistItem, false);

        return $itemData;
    }
}
