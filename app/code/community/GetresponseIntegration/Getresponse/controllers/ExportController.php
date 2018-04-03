<?php

require_once Mage::getModuleDir('controllers',
        'GetresponseIntegration_Getresponse') . DIRECTORY_SEPARATOR . 'BaseController.php';

use GetresponseIntegration_Getresponse_Domain_GetresponseOrderBuilder as GrOrderBuilder;
use GetresponseIntegration_Getresponse_Domain_GetresponseCartBuilder as GrCartBuilder;
use GetresponseIntegration_Getresponse_Domain_GetresponseProductBuilder as GrProductBuilder;

/**
 * Class GetresponseIntegration_Getresponse_ExportController
 */
class GetresponseIntegration_Getresponse_ExportController extends GetresponseIntegration_Getresponse_BaseController
{

    /**
     * GET getresponse/index/export
     */
    public function indexAction()
    {
        $this->_initAction();
        $this->_title($this->__('Export customers'))->_title($this->__('GetResponse'));

        $this->prepareCustomsForMapping();

        /** @var Mage_Core_Block_Abstract $autoresponderBlock */
        $autoresponderBlock = $this->getLayout()->createBlock(
            'GetresponseIntegration_Getresponse_Block_Adminhtml_Autoresponder',
            'autoresponder',
            array(
                'campaign_days' => $this->api->getCampaignDays()
            )
        );

        $this->_addContent($this->getLayout()
            ->createBlock('Mage_Core_Block_Template', 'getresponse_content')
            ->setTemplate('getresponse/export.phtml')
            ->assign('campaign_days', $this->api->getCampaignDays())
            ->assign('campaigns', $this->api->getGrCampaigns())
            ->assign('gr_shops', (array)$this->api->getShops())
            ->assign('customs', $this->prepareCustomsForMapping())
            ->assign('autoresponder_block', $autoresponderBlock->toHtml())
        );

        $this->renderLayout();
    }

    /**
     * POST getresponse/export/run
     */
    public function runAction()
    {
        $this->_initAction();

        $campaign_id = $this->getRequest()->getParam('campaign_id');

        if (empty($campaign_id)) {
            $this->_getSession()->addError('List can\'t be empty');
            $this->_redirect('*/*/index');
            return;
        }

        $this->exportCustomers($campaign_id, $this->getRequest()->getParams());
        $this->_redirect('*/*/index');
    }

    /**
     * @param $campaignId
     * @param $params
     *
     * @return bool
     */
    protected function exportCustomers($campaignId, $params)
    {
        $cycleDay = '';
        $accountCustomFields = array_flip(Mage::helper('getresponse/api')->getCustomFields());
        $grCustomFields = array_flip($accountCustomFields);
        $customFieldsToBeAdded = array_diff($params['gr_custom_field'], $accountCustomFields);
        $failedCustomFields = [];
        $export_ecommerce = false;
        $export_store_id = '';

        if (isset($params['gr_autoresponder']) && 1 == $params['gr_autoresponder']) {
            $cycleDay = (int)$params['cycle_day'];
        }

        if (isset($params['gr_export_ecommerce_details']) && 1 === (int) $params['gr_export_ecommerce_details']) {
            $export_ecommerce = true;
            $export_store_id = $params['ecommerce_store'];
        }

        $custom_fields = $this->prepareCustomFields(
            isset($params['gr_custom_field']) ? $params['gr_custom_field'] : [],
            isset($params['custom_field']) ? $params['custom_field'] : []
        );

        if (!empty($customFieldsToBeAdded)) {
            foreach ($customFieldsToBeAdded as $field_key => $field_value) {
                $custom = Mage::helper('getresponse/api')->addCustomField($field_value);
                $grCustomFields[$custom->name] = $custom->customFieldId;
                if (!isset($custom->customFieldId)) {
                    $failedCustomFields[] = $field_value;
                }
            }
            if (!empty($failedCustomFields)) {
                $this->_getSession()->addError('Incorrect field name: ' . implode(', ', $failedCustomFields) . '.');
                return false;
            }
        }

        $subscribers = Mage::helper('getresponse')->getNewsletterSubscribersCollection();
        $reports = [
            'created' => 0,
            'updated' => 0,
            'error' => 0,
        ];

        if (!empty($subscribers)) {
            foreach ($subscribers as $subscriber) {
                $customer = Mage::getResourceModel('customer/customer_collection')
                    ->addAttributeToSelect('email')
                    ->addAttributeToSelect('firstname')
                    ->addAttributeToSelect('lastname')
                    ->joinAttribute('street', 'customer_address/street', 'default_billing', null, 'left')
                    ->joinAttribute('postcode', 'customer_address/city', 'default_billing', null, 'left')
                    ->joinAttribute('city', 'customer_address/postcode', 'default_billing', null, 'left')
                    ->joinAttribute('telephone', 'customer_address/telephone', 'default_billing', null, 'left')
                    ->joinAttribute('country', 'customer_address/country_id', 'default_billing', null, 'left')
                    ->joinAttribute('company', 'customer_address/company', 'default_billing', null, 'left')
                    ->joinAttribute('birthday', 'customer/dob', 'entity_id', null, 'left')
                    ->addFieldToFilter([
                        ['attribute' => 'email', 'eq' => $subscriber->getEmail()]
                    ])->getFirstItem();

                if (!empty($customer)) {
                    $name = $customer->getName();
                } else {
                    $name = null;
                }
                $result = Mage::helper('getresponse/api')->addContact(
                    $campaignId,
                    $name,
                    $subscriber->getEmail(),
                    $cycleDay,
                    Mage::getModel('getresponse/customs')->mapExportCustoms(array_flip($custom_fields), $customer),
                    $grCustomFields
                );

                if ((GetresponseIntegration_Getresponse_Helper_Api::CONTACT_CREATED === $result
                    || GetresponseIntegration_Getresponse_Helper_Api::CONTACT_UPDATED == $result)
                && $export_ecommerce
                ) {
                    $this->exportSubscriberEcommerceDetails($subscriber, $campaignId, $export_store_id);
                }

                if (GetresponseIntegration_Getresponse_Helper_Api::CONTACT_CREATED === $result) {
                    $reports['created']++;
                } elseif (GetresponseIntegration_Getresponse_Helper_Api::CONTACT_UPDATED == $result) {
                    $reports['updated']++;
                } else {
                    $reports['error']++;
                }
            }
        }

        $flashMessage = 'Customer data exported';

        $this->_getSession()->addSuccess($flashMessage);

        return true;
    }

    /**
     * @param Mage_Newsletter_Model_Subscriber $subscriber
     * @param string $campaignId
     * @param string $store_id
     */
    private function exportSubscriberEcommerceDetails(Mage_Newsletter_Model_Subscriber $subscriber, $campaignId, $store_id)
    {
        $orderBuilder = new GrOrderBuilder($this->api, $store_id);
        $cartBuilder = new GrCartBuilder($this->api, $store_id);
        $productBuilder = new GrProductBuilder($this->api, $store_id);

        /** @var Mage_Sales_Model_Resource_Order_Collection $orders */
        $orders = $this->getCustomerOrderCollection($subscriber->getId());

        if (0 === $orders->count()) {
            return;
        }

        $subscriber = $this->api->getContact(
            $subscriber->getEmail(),
            $campaignId
        );

        if (!isset($subscriber->contactId)) {
            Mage::log('Subscriber not found during export - ' . $subscriber->email);
            return;
        }

        /** @var Mage_Sales_Model_Order $order */
        foreach ($orders as $order) {

            $gr_products = [];

            /** @var Mage_Sales_Model_Order_Item $product */
            foreach ($order->getAllItems() as $product) {
                $gr_products[$product->getProduct()->getId()] = $productBuilder->createGetresponseProduct($product);
            }

            $gr_cart = $cartBuilder->buildGetresponseCart(
                $subscriber->contactId,
                $order,
                $gr_products
            );

            if (!isset($gr_cart['cartId'])) {
                Mage::log('Cart not created', 1, 'getresponse.log');
                continue;
            }

            $orderBuilder->createGetresponseOrder(
                $subscriber->contactId,
                $order,
                $gr_cart['cartId'],
                $gr_products
            );
        }
    }

    /**
     * @param int  $customerId
     *
     * @return Mage_Sales_Model_Resource_Order_Collection
     */
    public function getCustomerOrderCollection($customerId)
    {
        $orderCollection = Mage::getResourceModel('sales/order_collection')
            ->addFieldToSelect('*')
            ->addFieldToFilter('customer_id', $customerId)
            ->setOrder('created_at', 'desc');

        return $orderCollection;
    }

    private function prepareCustomFields($grCustomFields, $customFields)
    {
        $fields = [];

        foreach ($grCustomFields as $id => $name) {
            $fields[$name] = isset($customFields[$id]) ? $customFields[$id] : null;
        }

        return $fields;
    }

}