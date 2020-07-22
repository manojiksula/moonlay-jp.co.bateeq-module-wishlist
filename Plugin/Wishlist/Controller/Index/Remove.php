<?php

namespace Codesicle\WishlistAjax\Plugin\Wishlist\Controller\Index;

use Codesicle\WishlistAjax\Helper\Config;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Module\Manager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Wishlist\Controller\WishlistProviderInterface;
use Magento\Wishlist\Helper\Data;
use Magento\Wishlist\Model\Wishlist;
use Magento\Customer\Model\Session;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Remove
{

    /**
     * @var Config
     */
    protected $helperConfig;

    /**
     * @var WishlistProviderInterface
     */
    protected $wishlistProvider;

    /**
     * @var Data
     */
    protected $helperData;

    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * @var Manager
     */
    protected $moduleManager;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Wishlist
     */
    protected $wishlist;

    /**
     * @var Session
     */
    protected $session;

    /**
     * Remove constructor.
     * @param Config $helperConfig
     * @param WishlistProviderInterface $wishlistProvider
     * @param Data $helperData
     * @param JsonFactory $jsonFactory
     * @param Manager $moduleManager
     * @param StoreManagerInterface $storeManager
     * @param ObjectManagerInterface $objectManager
     * @param Wishlist $wishlist
     * @param Session $session
     */
    public function __construct(
        Config $helperConfig,
        WishlistProviderInterface $wishlistProvider,
        Data $helperData,
        JsonFactory $jsonFactory,
        Manager $moduleManager,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        StoreManagerInterface $storeManager,
        ObjectManagerInterface $objectManager,
        Wishlist $wishlist,
        Session $session
    ) {
        $this->helperConfig = $helperConfig;
        $this->objectManager = $objectManager;
        $this->wishlistProvider = $wishlistProvider;
        $this->helperData = $helperData;
        $this->jsonFactory = $jsonFactory;
        $this->moduleManager = $moduleManager;
        $this->eventManager = $eventManager;
        $this->storeManager = $storeManager;
        $this->wishlist = $wishlist;
        $this->session = $session;
    }

    /**
     * Remove item from wishlist
     *
     * @return \Magento\Framework\Controller\Result\Json
     * @throws NotFoundException
     */
    public function aroundExecute($subject, $proceed)
    {
        if ($this->moduleManager->isEnabled('Codesicle_WishlistAjax')) {

            /**
             * This will help us to see if the request is made from the wishlist page or not
             * @var $requestPath
             */
            $requestPath = $subject->getRequest()->getHeaders('Referer')->uri()->getPath();

            if (substr($requestPath, 0, strlen("/wishlist")) === '/wishlist') {
                return $proceed();
            } elseif ($this->helperConfig->getWishlistAjax() !== '1') {
                return $proceed();
            } else {
                $id = (int)$subject->getRequest()->getParam('item');
                $customerId = $this->session->getCustomerId();

                $wishlist = $this->wishlist->loadByCustomerId($customerId, true);

                if (!$wishlist) {
                    throw new NotFoundException(__('ページが見つかりません。'));
                }

                $item = $wishlist->getItem($id);

                if (!$item->getId()) {
                    throw new NotFoundException(__('ページが見つかりません。'));
                }

                try {
                    $item->delete();
                    $wishlist->save();

                    $response['errors'] = false;
                    $response['message'] = __('%1 はほしいものリストから削除されました', $item->getName());

                    $this->eventManager->dispatch(
                        'wishlist_remove_product',
                        ['wishlist' => $wishlist, 'product' => $item, 'item' => $item->getName()]
                    );

                } catch (\Magento\Framework\Exception\LocalizedException $e) {
                    $response['errors'] = true;
                    $response['message'] = __('エラー: %1　のために現在ウィッシュリストからこのアイテムを削除できません。', $e->getMessage());
                } catch (\Exception $e) {
                    $response['errors'] = true;
                    $response['message'] = __('このアイテムをウィッシュリストから現在削除できません。');
                }

                $this->helperData->calculate();

                $resultJson = $this->jsonFactory->create();

                return $resultJson->setData($response);
            }
        }
    }
}
