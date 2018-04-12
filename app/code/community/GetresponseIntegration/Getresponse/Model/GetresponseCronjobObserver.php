<?php

use GetresponseIntegration_Getresponse_Domain_SettingsRepository as SettingsRepository;
use GetresponseIntegration_Getresponse_Domain_GetresponseException as GetresponseException;
use GetresponseIntegration_Getresponse_Domain_Scheduler as Scheduler;
use GetresponseIntegration_Getresponse_Domain_GetresponseCartBuilder as GrCartBuilder;
use GetresponseIntegration_Getresponse_Domain_GetresponseProductHandler as GrProductHandler;
use GetresponseIntegration_Getresponse_Domain_GetresponseOrderBuilder as GrOrderBuilder;

/**
 * Class GetresponseIntegration_Getresponse_Model_ECommerceObserver
 */
class GetresponseIntegration_Getresponse_Model_GetresponseCronjobObserver
{
    public function exportJobsToGetresponse()
    {
        try {
            /** @var Mage_Sales_Model_Quote $quoteModel */
            $quoteModel = Mage::getModel('sales/quote');

            $api = $this->buildApiInstance();

            $scheduler = new Scheduler();
            $cartHandler = new GetresponseIntegration_Getresponse_Domain_GetresponseCartHandler(
                $api,
                $quoteModel,
                new GrCartBuilder(),
                new GrProductHandler($api)
            );
            $orderHandler = new GetresponseIntegration_Getresponse_Domain_GetresponseOrderHandler(
                $api,
                new GrProductHandler($api),
                new GrOrderBuilder()
            );

            $customerHandler = new GetresponseIntegration_Getresponse_Domain_GetresponseCustomerHandler(
                $api
            );
            /** @var array $jobs */
            $jobs = $scheduler->getAllJobs();

            /** @var GetresponseIntegration_Getresponse_Model_ScheduleJobsQueue $job */
            foreach ($jobs as $job) {
                switch ($job->getData('type')) {

                    case Scheduler::EXPORT_CUSTOMER:

                        $payload = json_decode($job->getData('payload'), true);

                        $customerHandler->sendCustomerToGetResponse(
                            $payload['campaign_id'],
                            $payload['cycle_day'],
                            $payload['gr_custom_fields'],
                            $payload['custom_fields'],
                            $payload['subscriber_email']
                        );

                        break;

                    case Scheduler::EXPORT_CART:

                        $payload = json_decode($job->getData('payload'), true);
                        Mage::app()->setCurrentStore($payload['shop_id']);

                        /** @var Mage_Sales_Model_Quote $quote */
                        $quote = $quoteModel->load($payload['quote_id']);

                        $cartHandler->sendCartToGetresponse(
                            $quote,
                            $payload['campaign_id'],
                            $payload['subscriber_email'],
                            $payload['gr_store_id']
                        );

                        break;

                    case Scheduler::EXPORT_ORDER:

                        $payload = json_decode($job->getData('payload'), true);

                        /** @var Mage_Sales_Model_Order $order */
                        $order = Mage::getModel('sales/order')->load($payload['order_id']);

                        if ($order->isEmpty()) {
                            $job->delete();
                            break;
                        }

                        $quote = $quoteModel->load(
                            $order->getQuoteId()
                        );

                        $orderHandler->sendOrderToGetresponse(
                            $order,
                            $payload['subscriber_email'],
                            $payload['campaign_id'],
                            $quote->getData('getresponse_cart_id'),
                            $payload['gr_store_id'],
                            true
                        );

                        break;
                }

                $job->delete();
            }
        } catch (Exception $e) {
            GetresponseIntegration_Getresponse_Helper_Logger::logException($e);
        }
    }

    /**
     * @return GetresponseIntegration_Getresponse_Helper_Api
     * @throws GetresponseIntegration_Getresponse_Domain_GetresponseException
     * @throws Mage_Core_Model_Store_Exception
     * @throws Varien_Exception
     */
    private function buildApiInstance()
    {
        /** @var GetresponseIntegration_Getresponse_Helper_Data $getresponseHelper */
        $getresponseHelper = Mage::helper('getresponse');
        $shopId = $getresponseHelper->getStoreId();
        $getresponseSettings = new SettingsRepository($shopId);
        $accountSettings = $getresponseSettings->getAccount();

        if (empty($accountSettings['apiKey'])) {
            throw GetresponseException::create_when_api_key_not_found();
        }

        /** @var GetresponseIntegration_Getresponse_Helper_Api $api */
        $api = Mage::helper('getresponse/api');

        $api->setApiDetails(
            $accountSettings['apiKey'],
            $accountSettings['apiUrl'],
            $accountSettings['apiDomain']
        );

        return $api;
    }
}
