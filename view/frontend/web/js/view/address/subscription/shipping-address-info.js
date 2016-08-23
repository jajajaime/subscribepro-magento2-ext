define(
    [
        'jquery',
        'ko',
        'Magento_Ui/js/modal/modal',
        'Magento_Ui/js/form/form',
        'Swarming_SubscribePro/js/model/address/address-options',
        'Magento_Customer/js/model/customer/address',
        'Magento_Customer/js/customer-data',
        'Swarming_SubscribePro/js/action/subscription/change-shipping-address',
        'mage/translate'
    ],
    function (
        $,
        ko,
        modal,
        Component,
        addressOptions,
        Address,
        customerData,
        changeAddress,
        $t
    ) {
        'use strict';

        return Component.extend({
            addressOptions: addressOptions.getOptions(),

            initObservable: function () {
                var self = this;
                self._super()
                    .observe({
                        isLoading: false,
                        shippingAddress: self.shippingAddress,
                        selectedAddress: null,
                        isCustomerLoggedIn: true,
                        isAddressFormVisible: self.addressOptions().length == 1,
                        saveInAddressBook: 1
                    });

                self.cityRegionPostcodeText = ko.pureComputed(function() {
                    var address = self.shippingAddress();
                    var cityRegionText = [address.city, address.region].filter(function (val) {return val;}).join(', ');

                    return [cityRegionText, address.postcode].filter(function (val) {return val;}).join(' ');
                });

                self.customerHasAddresses = ko.pureComputed(function() {
                    return self.addressOptions().length > 1;
                });

                self.selectedAddress.subscribe(function(address) {
                    self.isAddressFormVisible(addressOptions.isNewAddressOption(address));
                });

                return self;
            },

            initModal: function (element) {
                var options = this.modalOptions;
                options.trigger = '[data-trigger=change-shipping-address-'+this.subscriptionId+']';
                options.title = options.title ? $t(options.title) : $t('Change shipping address');
                options.buttons = [
                    {
                        text: $t('Continue'),
                        class: 'action primary action-update-shipping-address',
                        click: $.proxy(this.changeAddress, this)
                    }
                ];

                this.modal = modal(options, $(element));
            },

            /**
             * @param {Object} address
             * @return {*}
             */
            addressOptionsText: function (address) {
                return address.getAddressInline();
            },

            changeAddress: function () {
                if (this.selectedAddress() && !addressOptions.isNewAddressOption(this.selectedAddress())) {
                    changeAddress(this.subscriptionId, {}, this.selectedAddress(), this.isLoading, this.messageContainer, $.proxy(this.updateAddress, this));
                } else {
                    this.source.set('params.invalid', false);
                    this.source.trigger(this.dataScopePrefix + '.data.validate');
                    if (this.source.get(this.dataScopePrefix + '.custom_attributes')) {
                        this.source.trigger(this.dataScopePrefix + '.custom_attributes.data.validate');
                    }

                    if (!this.source.get('params.invalid')) {
                        var addressData = this.source.get(this.dataScopePrefix);

                        if (!this.customerHasAddresses()) {
                            this.saveInAddressBook(1);
                        }
                        addressData.save_in_address_book = this.saveInAddressBook() ? 1 : 0;
                        if (!addressData.region) {
                            addressData.region = {};
                        }
                        changeAddress(this.subscriptionId, addressData, new Address(addressData), this.isLoading, this.messageContainer, $.proxy(this.updateAddress, this));
                    }
                }
            },

            updateAddress: function (response, addressData, address) {
                if (!address.getAddressInline()) {
                    address = new Address(addressData);
                }
                var newAddress = $.extend({}, address);
                var street = $.extend({}, newAddress.street);
                this.reset();
                newAddress.street = street;
                if (newAddress.saveInAddressBook) {
                    this.addressOptions.push(newAddress);
                    this.selectedAddress(newAddress);
                    this.isAddressFormVisible(false);
                    newAddress.saveInAddressBook = 0;
                }
                this.shippingAddress(response);
                this.modal.closeModal();
            }
        });
    }
);
