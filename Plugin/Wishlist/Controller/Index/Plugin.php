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
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Module\Manager;

/**
 * Class Plugin
 * Plugin class to bypass some magento default plugin
 */
class Plugin
{
    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * @var Manager
     */
    protected $moduleManager;

    /**
     * Plugin constructor.
     *
     * @param Config $configHelper
     * @param Manager $moduleManager
     */
    public function __construct(
        Config $configHelper,
        Manager $moduleManager
    ) {
        $this->configHelper = $configHelper;
        $this->moduleManager = $moduleManager;
    }

    /**
     * @param  $subjectOrig
     * @param callable $proceed
     * @param  $subject
     * @param  $request
     * @return mixed
     */
    public function aroundBeforeDispatch($subjectOrig, callable $proceed, $subject, $request)
    {
        if ($this->configHelper->getWishlistAjax() !== '1') {
            return $proceed($subject, $request);
        }
        if ($this->configHelper->getWishlistAjax() === '1' && $request->getActionName() !== 'add') {
            return $proceed($subject, $request);
        }
    }
}
