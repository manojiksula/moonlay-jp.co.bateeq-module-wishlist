<?php
/**
 * Codesicle_WishlistAjax
 *
 * @category    Codesicle
 * @copyright   Copyright (c) 2019 Codesicle
 * @author      Vlad Patru <vp@codesicle.com>
 * @link        http://www.codesicle.com
 */

namespace Codesicle\WishlistAjax\Observer;

use Magento\Customer\Model\SessionFactory;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Wishlist\Model\ResourceModel\Item\CollectionFactory;

/**
 * Class Listproduct
 */
class ProductListing implements ObserverInterface
{

    /**
     * @var CollectionFactory
     */
    protected $_wishlistCollection;

    /**
     * @var SessionFactory
     */
    protected $_session;

    /**
     * ListProduct constructor.
     *
     * @param CollectionFactory $wishlistCollection
     * @param SessionFactory    $session
     */
    public function __construct(
        CollectionFactory $wishlistCollection,
        SessionFactory $session
    ) {
        $this->_wishlistCollection = $wishlistCollection;
        $this->_session = $session;
    }

    /**
     * Sets InWishlist property to the product if is already in wishlist
     * dependent on admin setting "Wishlist link class"
     *
     * @param Observer $observer
     *
     * @return array
     */
    public function execute(Observer $observer)
    {
        $collection = $observer->getCollection();
        $customer = $this->_session->create();

        if ($customer->getCustomer()->getId()) {
            if ($collection && $collection->getSize()) {
                foreach ($collection as $k => $product) {
                    $wishlist = $this->_wishlistCollection->create()
                        ->addCustomerIdFilter($customer->getCustomer()->getId())
                        ->addFieldToFilter('product_id', $product->getId());

                    $product->setInWishlist($wishlist->getSize());
                }
            }
        }

        return $collection;
    }
}
