<?php

namespace Swarming\SubscribePro\Block\Customer;

class Subscriptions extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Framework\Locale\FormatInterface
     */
    protected $localeFormat;

    /**
     * @var \Magento\Customer\Model\Address\Mapper
     */
    protected $addressMapper;

    /**
     * @var \Magento\Customer\Model\Address\Config
     */
    protected $addressConfig;
    
    /**
     * @var \Swarming\SubscribePro\Ui\ComponentProvider\AddressAttributes
     */
    protected $addressAttributes;

    /**
     * @var \Magento\Checkout\Block\Checkout\AttributeMerger
     */
    protected $merger;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Locale\FormatInterface $localeFormat
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Customer\Model\Address\Mapper $addressMapper
     * @param \Magento\Customer\Model\Address\Config $addressConfig
     * @param \Swarming\SubscribePro\Ui\ComponentProvider\AddressAttributes $addressAttributes
     * @param \Magento\Checkout\Block\Checkout\AttributeMerger $attributeMerger
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Model\Address\Mapper $addressMapper,
        \Magento\Customer\Model\Address\Config $addressConfig,
        \Swarming\SubscribePro\Ui\ComponentProvider\AddressAttributes $addressAttributes,
        \Magento\Checkout\Block\Checkout\AttributeMerger $attributeMerger,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->addressMapper = $addressMapper;
        $this->addressConfig = $addressConfig;
        $this->customerRepository = $customerRepository;
        $this->customerSession = $customerSession;
        $this->addressAttributes = $addressAttributes;
        $this->merger = $attributeMerger;
        $this->localeFormat = $localeFormat;
    }

    /**
     * @return array
     */
    public function getCustomerData()
    {
        $customer = $this->customerRepository->getById($this->customerSession->getCustomerId());
        $customerData = $customer->__toArray();
        foreach ($customer->getAddresses() as $key => $address) {
            $customerData['addresses'][$key]['inline'] = $this->getCustomerAddressInline($address);
        }

        return $customerData;
    }

    /**
     * @return string
     */
    public function getJsLayout()
    {
        $jsLayout = $this->buildJsLayout();
        return json_encode($jsLayout);
    }

    /**
     * @param \Magento\Customer\Api\Data\AddressInterface $address
     * @return string
     */
    protected function getCustomerAddressInline($address)
    {
        $builtOutputAddressData = $this->addressMapper->toFlatArray($address);
        return $this->addressConfig
            ->getFormatByCode(\Magento\Customer\Model\Address\Config::DEFAULT_ADDRESS_FORMAT)
            ->getRenderer()
            ->renderArray($builtOutputAddressData);
    }

    /**
     * @return array
     */
    protected function getDatepickerOptions()
    {
        return [
            'minDate' => 2,
            'showOn' => 'button',
            'buttonImage' => $this->getViewFileUrl('Magento_Theme::calendar.png'),
            'buttonText' => __('Click to change date'),
            'buttonImageOnly' => true,
            'dateFormat' => 'yyyy-mm-dd',
        ];
    }

    protected function buildJsLayout()
    {
        $jsLayout = [
            'components' => [
                'subscriptions-container' => [
                    'children' => [
                        'subscriptions' => [
                            'config' => [
                                'datepickerOptions' => $this->getDatepickerOptions(),
                                'priceFormat' => $this->localeFormat->getPriceFormat(),
                                'shippingAddressOptions' => [
                                    'dataScopePrefix' => 'shippingAddress',
                                    'deps' => 'spAddressProvider',
                                    'children' => [
                                        'shipping-address-fieldset' => [
                                            'children' => $this->merger->merge(
                                                $this->addressAttributes->getElements(),
                                                'spAddressProvider',
                                                'shippingAddress',
                                                [
                                                    'region' => [
                                                        'visible' => false,
                                                    ],
                                                    'region_id' => [
                                                        'component' => 'Magento_Ui/js/form/element/region',
                                                        'config' => [
                                                            'template' => 'ui/form/field',
                                                            'elementTmpl' => 'ui/form/element/select',
                                                            'customEntry' => 'shippingAddress.region',
                                                        ],
                                                        'validation' => [
                                                            'required-entry' => true,
                                                        ],
                                                        'filterBy' => [
                                                            'target' => '${ $.provider }:${ $.parentScope }.country_id',
                                                            'field' => 'country_id',
                                                        ],
                                                    ],
                                                    'country_id' => [
                                                        'sortOrder' => 115,
                                                    ],
                                                    'postcode' => [
                                                        'component' => 'Magento_Ui/js/form/element/post-code',
                                                        'validation' => [
                                                            'required-entry' => true,
                                                        ],
                                                    ],
                                                    'company' => [
                                                        'validation' => [
                                                            'min_text_length' => 0,
                                                        ],
                                                    ],
                                                    'telephone' => [
                                                        'config' => [
                                                            'tooltip' => [
                                                                'description' => __('For delivery questions.'),
                                                            ],
                                                        ],
                                                    ],
                                                ]
                                            )
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        return array_merge_recursive($this->jsLayout, $jsLayout);
    }
}
