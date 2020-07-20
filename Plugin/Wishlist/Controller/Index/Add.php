<?php
/**
 * Codesicle_WishlistAjax
 *
 * @category  Codesicle
 * @copyright Copyright (c) 2019 Codesicle
 * @author    Vlad Patru <vp@codesicle.com>
 * @link      http://www.codesicle.com
 */

namespace Codesicle\WishlistAjax\Plugin\Wishlist\Controller\Index;

use Codesicle\WishlistAjax\Helper\Config;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Module\Manager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Wishlist\Controller\WishlistProviderInterface;
use Magento\Wishlist\Helper\Data;

/**
 * Class Add used to add to wishlist
 */
class Add
{
    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * @var Config
     */
    protected $configHelper;

    protected $response;

    /**
     * @var Validator
     */
    protected $formKeyValidator;

    /**
     * @var ResultFactory
     */
    protected $resultFactory;

    /**
     * @var WishlistProviderInterface
     */
    protected $wishlistProvider;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepo;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var Data
     */
    protected $wishlistHelper;

    /**
     * @var UrlInterface
     */
    protected $urlInterface;

    /**
     * @var SessionManagerInterface
     */
    protected $sessionManager;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Manager
     */
    protected $moduleManager;

    /**
     * @var RedirectInterface
     */
    protected $redirect;

    /**
     * Add constructor.
     *
     * @param Session $customerSession
     * @param JsonFactory $jsonFactory
     * @param Config $configHelper
     * @param Validator $formKeyValidator
     * @param ResultFactory $resultFactory
     * @param WishlistProviderInterface $wishlistProvider
     * @param ProductRepositoryInterface $productRepo
     * @param ManagerInterface $messageManager
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param ObjectManagerInterface $objectManager
     * @param Data $wishlistHelperData
     * @param UrlInterface urlInterface
     * @param SessionManagerInterface $sessionManager
     * @param Manager $moduleManager
     * @param RedirectInterface $redirect
     */
    public function __construct(
        Session $customerSession,
        JsonFactory $jsonFactory,
        Config $configHelper,
        Validator $formKeyValidator,
        WishlistProviderInterface $wishlistProvider,
        ProductRepositoryInterface $productRepo,
        ManagerInterface $messageManager,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        Data $wishlistHelperData,
        Manager $moduleManager
    ) {
        $this->customerSession = $customerSession;
        $this->jsonFactory = $jsonFactory;
        $this->configHelper = $configHelper;
        $this->formKeyValidator = $formKeyValidator;
        $this->wishlistProvider = $wishlistProvider;
        $this->productRepo = $productRepo;
        $this->messageManager = $messageManager;
        $this->eventManager = $eventManager;
        $this->wishlistHelper = $wishlistHelperData;
        $this->moduleManager = $moduleManager;
    }

    /**
     * @param $subject
     * @param $proceed
     * @return Json
     * @throws NotFoundException
     */
    public function aroundExecute($subject, $proceed)
    {
        if ($this->moduleManager->isEnabled('Codesicle_WishlistAjax')) {
            if ($this->configHelper->getWishlistAjax() !== '1') {
                return $proceed();
            } elseif ($subject->getRequest()->getParam('ajax') !== 'true') {
                return $proceed();
            } else {
                $requestParams = $subject->getRequest()->getParams();

                $productId = isset($requestParams['product']) ? (int)$requestParams['product'] : null;
                if (!$productId) {
                    $response['errors'] = true;
                    $response['message'] = __('We can\'t specify a product.');
                }

                try {
                    $product = $this->productRepo->getById($productId);
                } catch (NoSuchEntityException $exception) {
                    $product = null;
                }

                $response = [
                    'errors' => false,
                    'redirect' => false,
                    'message' => __('Product %1 was added to your wishlist.', $product->getName())
                ];

                if (!$this->customerSession->isLoggedIn()) {
                    $response['errors'] = true;
                    $response['message'] = __('You need to login before adding a product to wishlist.');
                    $response['redirect'] = true;
                } else {
                    if (!$this->formKeyValidator->validate($subject->getRequest())) {
                        $response['errors'] = true;
                        $response['message'] = __('Invalid form key. Please refresh the page.');
                    }

                    $wishlist = $this->wishlistProvider->getWishlist();
                    if (!$wishlist) {
                        throw new NotFoundException(__('Page not found.'));
                    }

                    if ($this->customerSession->getBeforeWishlistRequest()) {
                        $requestParams = $this->customerSession->getBeforeWishlistRequest();
                        $this->customerSession->unsBeforeWishlistRequest();
                    }

                    if (!$productId || !$product || !$product->isVisibleInCatalog()) {
                        $response['errors'] = true;
                        $response['message'] = __('We can\'t specify a product.');
                    }

                    try {
                        $buyRequest = new \Magento\Framework\DataObject($requestParams);

                        $result = $wishlist->addNewItem($product, $buyRequest);
                        if (is_string($result)) {
                            throw new \Magento\Framework\Exception\LocalizedException(__($result));
                        }

                        $wishlist->save();

                        $this->eventManager->dispatch(
                            'wishlist_add_product',
                            ['wishlist' => $wishlist, 'product' => $product, 'item' => $result]
                        );

                        $this->wishlistHelper->calculate();

                        $response['errors'] = false;
                        $response['message'] = __('Product %1 was added to your wishlist.', $product->getName());
                    } catch (\Exception $e) {
                        $response['errors'] = true;
                        $response['message'] = __('We can\'t add the item to Wish List right now.');
                    } catch (\Magento\Framework\Exception\LocalizedException $e) {
                        $response['errors'] = true;
                        $response['message'] = __('We can\'t add the item to Wish List right now: %1.', $e->getMessage());
                    }

                    if ($response['errors'] === true) {
                        $this->messageManager->addErrorMessage($response['message']);
                    } else {
                        $this->messageManager->addSuccessMessage($response['message']);
                    }
                }

                $resultJson = $this->jsonFactory->create();

                return $resultJson->setData($response);
            }
        }
    }
}
