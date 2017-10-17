<?php

require_once Mage::getModuleDir('controllers', 'GetresponseIntegration_Getresponse').DIRECTORY_SEPARATOR.'BaseController.php';

class GetresponseIntegration_Getresponse_NewsletterController extends GetresponseIntegration_Getresponse_BaseController
{

    /**
     * GET getresponse/newsletter/index
     */
    public function indexAction()
    {
        $this->_initAction();
        $this->_title($this->__('Subscription via newsletter'))->_title($this->__('GetResponse'));

        $this->_addContent($this->getLayout()
            ->createBlock('Mage_Core_Block_Template', 'getresponse_content')
            ->setTemplate('getresponse/newsletter.phtml')
            ->assign('settings', $this->settings)
            ->assign('campaign_days', $this->api->getCampaignDays())
            ->assign('campaigns', $this->api->getGrCampaigns())
        );

        $this->renderLayout();
    }

    /**
     * POST getresponse/newsletter/save
     */
    public function saveAction()
    {
        $this->_initAction();

        $newsletterSubscription = (int)$this->getRequest()->getParam('newsletter_subscription', 0);
        $newsletterCampaignId = $this->getRequest()->getParam('newsletter_campaign_id', '');
        $newsletterCycleDay = (int)$this->getRequest()->getParam('newsletter_cycle_day', 0);
        $newsletterAutoresponder = (int)$this->getRequest()->getParam('newsletter_autoresponder', 0);

        if (0 === $newsletterSubscription) {
            $newsletterCampaignId = '';
            $newsletterCycleDay = 0;
        } else {
            if (0 === $newsletterAutoresponder) {
                $newsletterCycleDay = 0;
            }
        }

        Mage::getModel('getresponse/settings')->updateSettings(
            array(
                'newsletter_subscription' => $newsletterSubscription,
                'newsletter_campaign_id' => $newsletterCampaignId,
                'newsletter_cycle_day' => $newsletterCycleDay,
            ),
            $this->currentShopId
        );

        $this->_getSession()->addSuccess('Settings saved');
        $this->_redirect('*/*/index');
    }

}