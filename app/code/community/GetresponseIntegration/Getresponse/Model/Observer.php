<?php

use GetresponseIntegration_Getresponse_Domain_SettingsRepository as SettingsRepository;
use GetresponseIntegration_Getresponse_Domain_WebformRepository as WebformRepository;
use GetresponseIntegration_Getresponse_Domain_AutomationRulesCollectionRepository as AutomationRulesCollectionRepository;
use GetresponseIntegration_Getresponse_Domain_GetresponseException as GetresponseException;

/**
 * Getresponse module observer
 *
 * @author Magento
 */
class GetresponseIntegration_Getresponse_Model_Observer
{
    /** @var string */
    private $shopId;

    /** @var Mage_Sales_Model_Resource_Order */
    private $orderModel;

    /** @var GetresponseIntegration_Getresponse_Helper_Data */
    private $getresponseHelper;

    /** @var Mage_Customer_Model_Session */
    private $customerSessionModel;

    /** @var Mage_Newsletter_Model_Subscriber */
    private $newsletterModel;

    /** @var Mage_Core_Model_Session */
    private $sessionModel;

    /** @var GetresponseIntegration_Getresponse_Model_Customs  */
    private $customsModel;


    public function __construct()
    {
        $this->sessionModel = Mage::getSingleton('core/session');
        $this->customerSessionModel = Mage::getSingleton('customer/session');
        $this->getresponseHelper = Mage::helper('getresponse');
        $this->shopId = $this->getresponseHelper->getStoreId();
        $this->orderModel = Mage::getResourceModel('sales/order');
        $this->newsletterModel = Mage::getModel('newsletter/subscriber');
        $this->customsModel = Mage::getModel('getresponse/customs');
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function addTrackingCodeToHeader(Varien_Event_Observer $observer)
    {
        if (!$this->getresponseHelper->isEnabled()) {
            return;
        }

        $settingsRepository = new SettingsRepository($this->shopId);
        $accountSettings = $settingsRepository->getAccount();

        if (empty($accountSettings['apiKey']) || 0 === (int) $accountSettings['hasGrTrafficFeatureEnabled']) {
            return;
        }

        $layout = Mage::app()->getLayout();
        /* @var $block Mage_Page_Block_Html_Head */
        $block = $observer->getEvent()->getBlock();

        if ("head" == $block->getNameInLayout()) {
            /** @var Mage_Core_Block_Text $myBlock */
            $myBlock = $layout->createBlock('core/text');
            $myBlock->setText($accountSettings['trackingCodeSnippet']);

            $block->append($myBlock);
        }

        if ("footer" == $block->getNameInLayout() && $this->customerSessionModel->isLoggedIn()) {

            $customer = $this->customerSessionModel->getCustomer();

            if (strlen($customer->email) > 0) {
                /** @var Mage_Core_Block_Text $myBlock */
                $myBlock = $layout->createBlock('core/text');
                $myBlock->setText('<script type="text/javascript">gaSetUserId("' . $customer->email . '");</script>');
                $block->append($myBlock);
            }
        }
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function addJQueryToHeader(Varien_Event_Observer $observer)
    {
        if (!$this->getresponseHelper->isEnabled()) {
            return;
        }

        /* @var $block Mage_Page_Block_Html_Head */
        $block = $observer->getEvent()->getBlock();

        if ("head" == $block->getNameInLayout()) {
            foreach ($this->getresponseHelper->getFiles() as $file) {
                $block->addJs($this->getresponseHelper->getJQueryPath($file));
            }
        }
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function set_block(Varien_Event_Observer $observer)
    {
        if (!$this->getresponseHelper->isEnabled()) {
            return;
        }

        $settingsRepository = new SettingsRepository($this->shopId);
        $accountSettings = $settingsRepository->getAccount();
        $webformRepository = new WebformRepository($this->shopId);
        $webformSettings = $webformRepository->getWebform()->toArray();

        if (empty($accountSettings['apiKey'])) {
            return;
        }

        if (!empty($webformSettings) && $webformSettings['activeSubscription'] == 1 && !empty($webformSettings['url'])) {
            $sub_position = ($webformSettings['blockPosition'] == 'before') ? 'before="-"' : 'after="-"';

            $myXml = '<reference name="' . $webformSettings['layoutPosition'] . '">';
            $myXml .= '<block type="core/text_list"
							name="' . $webformSettings['layoutPosition'] . '.content"
							as="getresponse_webform_' . $webformSettings['layoutPosition'] . '"
							translate="label" ' . $sub_position . '>';
            $myXml .= '<block type="core/template"
							name="getresponse_webform_' . $webformSettings['layoutPosition'] . '"
							template="getresponse/webform.phtml">';
            $myXml .= '<action method="setData">
							<name>getresponse_active_subscription</name>
							<value>' . $webformSettings['activeSubscription'] . '</value></action>';
            $myXml .= '<action method="setData">
							<name>getresponse_webform_title</name>
							<value>' . $webformSettings['webformTitle'] . '</value></action>';
            $myXml .= '<action method="setData">
							<name>getresponse_webform_url</name>
							<value>' . str_replace('&', '&amp;', $webformSettings['url']) . '</value></action>';
            $myXml .= '</block></block>';
            $myXml .= '</reference>';

            /** @var Mage_Core_Model_Layout $layout */
            $layout = $observer->getEvent()->getData('layout');

            $layout->getUpdate()->addUpdate($myXml);
            $layout->generateXml();
        }
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function addCssToHeader(Varien_Event_Observer $observer)
    {
        if (!$this->getresponseHelper->isEnabled()) {
            return;
        }

        /* @var $block Mage_Page_Block_Html_Head */
        $block = $observer->getEvent()->getBlock();

        if ("head" == $block->getNameInLayout()) {
            $block->addCss('css/getresponse.css');
            $block->addCss('css/getresponse-custom-field.css');
            $block->addCss('css/jquery-ui.min.css');
            $block->addCss('css/jquery.switchButton.css');
        }
    }

    /**
     * @param Varien_Event_Observer $observer
     */
    public function createAccount(Varien_Event_Observer $observer)
    {
        Mage::log('create account action', 7, 'getresponse.log');

        if (!$this->getresponseHelper->isEnabled()) {
            return;
        }

        /** @var Varien_Event $event */
        $event = $observer->getEvent();
        $customer = $event->getData('customer');
        $settingsRepository = new SettingsRepository($this->shopId);
        $accountSettings = $settingsRepository->getAccount();

        if (empty($accountSettings['apiKey']) || (int) $accountSettings['activeSubscription'] !== 1 || empty($accountSettings['campaignId'])) {
            return;
        }


        $subscriber = $this->newsletterModel->setStoreId($this->shopId)->loadByEmail($customer->getData('email'));

        if (false === $subscriber->isSubscribed()) {
            return;
        }

        try {
            $api = $this->buildApiInstance();
            $api->upsertContact(
                $accountSettings['campaignId'],
                $customer->getName(),
                $customer->getData('email'),
                $accountSettings['cycleDay'],
                array()
            );
        } catch (GetresponseException $e) {
            Mage::log($e->getMessage(), 1, 'getresponse.log');
        }
    }

    /**
     * @param                              $categories
     * @param                              $shop_id
     * @param Mage_Customer_Model_Customer $customer
     * @param                              $user_customs
     * @param                              $customs
     * @param                              $settings
     *
     * @throws GetresponseIntegration_Getresponse_Domain_GetresponseException
     */
    public function automationHandler($categories, $shop_id, Mage_Customer_Model_Customer $customer, $user_customs, $customs, $settings)
    {
        $automations = array();
        $ruleRepository = new AutomationRulesCollectionRepository($shop_id);
        $ruleCollectionDb = $ruleRepository->getCollection();

        foreach ($ruleCollectionDb as $rule) {
            if (false !== array_search($rule['categoryId'], $categories)) {
                $automations[] = $rule;
            }
        }

        if (empty($automations)) {
            return;
        }

        $api = $this->buildApiInstance();

        $delete_contact = false;

        foreach ($automations as $automation) {

            $api->upsertContact(
                $automation['campaignId'],
                $customer->getName(),
                $customer->getData('email'),
                $automation['cycleDay'],
                $this->customsModel->mapCustoms($user_customs, $customs)
            );

            if ($automation['action'] == 'move') {
                $delete_contact = true;
            }
        }

        if ($delete_contact === true) {
            $contact = $api->getContact($customer->getData('email'), $settings['campaign_id']);
            if (isset($contact->contactId)) {
                $api->deleteContact($contact->contactId);
            }
        }
    }

    public function initBeforeEventAction()
    {
        if (!$this->getresponseHelper->isEnabled()) {
            return;
        }

        $settingsRepository = new SettingsRepository($this->shopId);
        $accountSettings = $settingsRepository->getAccount();

        if (empty($accountSettings['apiKey'])) {
            return;
        }

        // display Signup to Newsletter checkbox on checkout page
        try {
            Mage::register(
                '_subscription_on_checkout',
                (bool)$accountSettings['subscriptionOnCheckout']
            );
        } catch (Mage_Core_Exception $e) {
        }
    }

    public function checkoutSaveAddress()
    {
        Mage::log('checkoutSaveAddress action', 1, 'getresponse.log');

        if (!$this->getresponseHelper->isEnabled()) {
            return;
        }

        $post = Mage::app()->getRequest()->getPost();

        if (empty($post) || empty($post['billing']) || (!isset($post['is_subscribed']))) {
            return;
        }

        if (1 === (int) $post['is_subscribed']) {
            $this->sessionModel->setData('_gr_is_subscribed', true);
            $this->sessionModel->setData('_subscriber_data', $post['billing']);
        } else {
            $this->sessionModel->setData('_gr_is_subscribed', false);
            $this->sessionModel->setData('_subscriber_data', null);
        }
    }

    public function checkoutAllAfterFormSubmitted()
    {
        $isSubscribed = (bool) $this->sessionModel->getData('_gr_is_subscribed');

        if (!$this->getresponseHelper->isEnabled() || 0 === $isSubscribed) {
            return;
        }

        $details = (array) $this->sessionModel->getData('_subscriber_data');

        // clear session
        $this->sessionModel->setData('_gr_is_subscribed', null);
        $this->sessionModel->setData('_subscriber_data', null);

        if (empty($details['email'])) {
            return;
        }

        try {
            $this->newsletterModel->subscribe($details['email']);
        } catch (Exception $e) {
            return;
        }

        $settingsRepository = new SettingsRepository($this->shopId);
        $accountSettings = $settingsRepository->getAccount();

        if (empty($accountSettings['apiKey'])) {
            return;
        }

        $customs = (array) $this->customsModel->getCustoms($this->shopId);

        $details['street'] = join(' ', (array)$details['street']);
        $details['country'] = $details['country_id'];

        try {
            $api = $this->buildApiInstance();
            $api->upsertContact(
                $accountSettings['campaignId'],
                $details['firstname'] . ' ' . $details['lastname'],
                $details['email'],
                $accountSettings['cycleDay'],
                $this->customsModel->mapCustoms($details, $customs)
            );
        } catch (GetresponseException $e) {
            return;
        }
    }

    public function initBeforeAddToNewsletterAction()
    {
        if (!$this->getresponseHelper->isEnabled()) {
            return;
        }

        $settingsRepository = new SettingsRepository($this->shopId);
        $accountSettings = $settingsRepository->getAccount();

        if (empty($accountSettings['apiKey']) || 1 !== $accountSettings['newsletterSubscription'] || empty($accountSettings['newsletterCampaignId'])) {
            return;
        }

        $name = $email = null;
        $post = Mage::app()->getRequest()->getPost();

        $customer = $this->customerSessionModel->getCustomer();

        // only, if customer is logged in.
        if (!$customer->isEmpty() && strlen($customer->email) > 0 && isset($post['is_subscribed']) && $post['is_subscribed'] === 1) {
            $name = $customer->firstname . ' ' . $customer->lastname;
            $email = $customer->email;
        } else if (isset($post['email']) && !empty($post['email'])) {
            $name = 'Friend';
            $email = $post['email'];
        }

        if (empty($email)) {
            return;
        }

        $subscriberModel = $this->newsletterModel->loadByEmail($email);

        if (false === $subscriberModel->isSubscribed()) {
            return;
        }

        try {
            $api = $this->buildApiInstance();
            $api->upsertContact(
                $accountSettings['newsletterCampaignId'],
                $name,
                $email,
                $accountSettings['newsletterCycleDay'],
                array()
            );
        } catch (GetresponseException $e) {
            return;
        }
    }

    /**
     * @return GetresponseIntegration_Getresponse_Helper_Api
     * @throws GetresponseException
     */
    private function buildApiInstance()
    {
        $settingsRepository = new SettingsRepository($this->shopId);
        $accountSettings = $settingsRepository->getAccount();

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
