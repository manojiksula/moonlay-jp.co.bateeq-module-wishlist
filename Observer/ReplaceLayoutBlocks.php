<?php
/**
 * Codesicle_WishlistAjax
 *
 * @category  Codesicle
 * @copyright Copyright (c) 2019 Codesicle
 * @author    Vlad Patru <vp@codesicle.com>
 * @link      http://www.codesicle.com
 */

namespace Codesicle\WishlistAjax\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Store\Model\ScopeInterface;

/**
 * Class ChangeBlocks
 * Observer to remove standard "Add to wishlist" buttons if module is enabled
 * and Ajax to wishlist option is "yes"
 */
class ReplaceLayoutBlocks implements ObserverInterface
{

    const AJAX_ENABLED = 'codesicle_wishlist/wishlist/ajax_enable';

    const AJAX_IN_COMPARE = 'codesicle_wishlist/wishlist/in_compare';

    const CLASS_CHANGE = 'codesicle_wishlist/wishlist/change_class';
    
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var CookieManagerInterface CookieManagerInterface
     */
    protected $cookieManager;

    /**
     * @var CookieMetadataFactory CookieMetadataFactory
     */
    protected $cookieFactory;

    /**
     * ChangeBlocks constructor. and set cookie for changing class
     *
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieFactory
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->cookieManager = $cookieManager;
        $this->cookieFactory = $cookieFactory;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $layout = $observer->getLayout();
        $layoutArray = $layout->getUpdate()->getHandles();

        $blocks[] = $layout->getBlock('view.addto.wishlist');
        $blocks[] = $layout->getBlock('category.product.addto.wishlist');
        $blocks[] = $layout->getBlock('catalogsearch.product.addto.wishlist');
        $blocks[] = $layout->getBlock('upsell.product.addto.wishlist');
        $blocks[] = $layout->getBlock('related.product.addto.wishlist');
        $blocks[] = $layout->getBlock('crosssell.product.addto.wishlist');
        if ($this->scopeConfig->getValue(self::AJAX_IN_COMPARE, ScopeInterface::SCOPE_STORE) === '1') {
            $blocks[] = $layout->getBlock('catalog.compare.list');
        }
        if(!in_array('wishlist_index_index', $layoutArray)) {
            $blocks[] = $layout->getBlock('wishlist_sidebar');
        }

        $remove = $this->scopeConfig->getValue(self::AJAX_ENABLED, ScopeInterface::SCOPE_STORE);

        if ($remove == 1) {
            foreach ($blocks as $block) {
                if ($block != false) {
                    $layout->unsetElement($block->getNameInLayout());
                }
            }
        }

        $checkCookie = $this->cookieManager->getCookie('wishlist_class');
        $cookieValue = $this->scopeConfig->getValue(self::CLASS_CHANGE, ScopeInterface::SCOPE_STORE);

        if ($checkCookie !== $cookieValue) {
            $cookieMetadata = $this->cookieFactory->createPublicCookieMetadata();
            $cookieMetadata->setDurationOneYear();
            $cookieMetadata->setPath('/');
            $cookieMetadata->setHttpOnly(false);

            $this->cookieManager->setPublicCookie(
                'wishlist_class',
                $cookieValue,
                $cookieMetadata
            );
        }
    }
}
