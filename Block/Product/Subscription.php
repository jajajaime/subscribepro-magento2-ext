<?php

namespace Swarming\SubscribePro\Block\Product;

use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\Catalog\Pricing\Price\RegularPrice;
use Swarming\SubscribePro\Api\Data\ProductInterface;
use \Swarming\SubscribePro\Ui\DataProvider\Product\Modifier\Subscription as SubscriptionModifier;

class Subscription extends \Magento\Catalog\Block\Product\AbstractProduct
{
    /**
     * @var \Swarming\SubscribePro\Platform\Helper\Product
     */
    protected $platformProductHelper;

    /**
     * @var \Swarming\SubscribePro\Model\Config\SubscriptionDiscount
     */
    protected $subscriptionDiscountConfig;

    /**
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Magento\Framework\Locale\FormatInterface $localeFormat
     * @param \Swarming\SubscribePro\Model\Config\SubscriptionDiscount $subscriptionDiscountConfig
     * @param \Swarming\SubscribePro\Platform\Helper\Product $platformProductHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Swarming\SubscribePro\Model\Config\SubscriptionDiscount $subscriptionDiscountConfig,
        \Swarming\SubscribePro\Platform\Helper\Product $platformProductHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->platformProductHelper = $platformProductHelper;
        $this->subscriptionDiscountConfig = $subscriptionDiscountConfig;

        if (!$this->subscriptionDiscountConfig->isEnabled() || !$this->isProductSubscriptionEnabled()) {
            return;
        }

        $data = [
            'components' => [
                'subscription-container' => [
                    'config' => [
                        'oneTimePurchaseOption' => ProductInterface::SO_ONETIME_PURCHASE,
                        'subscriptionOption' => ProductInterface::SO_SUBSCRIPTION,
                        'subscriptionOnlyMode' => ProductInterface::SOM_SUBSCRIPTION_ONLY,
                        'subscriptionAndOneTimePurchaseMode' => ProductInterface::SOM_SUBSCRIPTION_AND_ONETIME_PURCHASE,
                        'priceFormat' => $localeFormat->getPriceFormat(),
                        'productData' => $this->getSubscriptionProduct()->toArray(),
                    ]
                ]
            ]
        ];

        $this->jsLayout = array_merge_recursive($data, $this->jsLayout);
    }

    /**
     * @return \Swarming\SubscribePro\Api\Data\ProductInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getSubscriptionProduct()
    {
        $subscribeProProduct = $this->platformProductHelper->getProduct($this->getProduct()->getSku());
        $finalPrice = $this->getProduct()->getPriceInfo()->getPrice(FinalPrice::PRICE_CODE)->getValue();
        $regularPrice = $this->getProduct()->getPriceInfo()->getPrice(RegularPrice::PRICE_CODE)->getValue();
        
        $subscribeProProduct->setFinalPrice($finalPrice);
        $subscribeProProduct->setPrice($regularPrice);
        $subscribeProProduct->setApplyDiscountToCatalogPrice($this->subscriptionDiscountConfig->doApplyDiscountToCatalogPrice());

        return $subscribeProProduct;
    }

    /**
     * @return bool
     */
    public function isSubscribeProEnabled()
    {
        return (bool) $this->subscriptionDiscountConfig->isEnabled();
    }

    /**
     * @return bool
     */
    public function isProductSubscriptionEnabled()
    {
        $attribute = $this->getProduct()->getCustomAttribute(SubscriptionModifier::SUBSCRIPTION_ENABLED);
        return $attribute && $attribute->getValue();
    }
}
