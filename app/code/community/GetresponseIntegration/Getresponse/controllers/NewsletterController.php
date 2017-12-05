<?php

require_once Mage::getModuleDir('controllers',
        'GetresponseIntegration_Getresponse') . DIRECTORY_SEPARATOR . 'BaseController.php';

class GetresponseIntegration_Getresponse_NewsletterController extends GetresponseIntegration_Getresponse_BaseController
{

    /**
     * GET getresponse/newsletter/index
     */
    public function indexAction()
    {
        $this->_initAction();
        $this->_title($this->__('Subscription via newsletter'))->_title($this->__('GetResponse'));

        /** @var Mage_Core_Block_Abstract $autoresponderBlock */
        $autoresponderBlock = $this->getLayout()->createBlock(
            'GetresponseIntegration_Getresponse_Block_Adminhtml_Autoresponder',
            'autoresponder',
            array(
                'campaign_days' => $this->api->getCampaignDays(),
                'selected_day' => $this->settings->api['cycle_day']
            )
        );

        $this->_addContent($this->getLayout()
            ->createBlock('Mage_Core_Block_Template', 'getresponse_content')
            ->setTemplate('getresponse/newsletter.phtml')
            ->assign('settings', $this->settings)
            ->assign('campaigns', $this->api->getGrCampaigns())
            ->assign('autoresponder_block', $autoresponderBlock->toHtml())
        );

        $this->renderLayout();
    }

    /**
     * POST getresponse/newsletter/save
     */
    public function saveAction()
    {
        $this->_initAction();

        $isEnabled = (int)$this->getRequest()->getParam('newsletter_subscription', 0);
        $newsletterCampaignId = $this->getRequest()->getParam('newsletter_campaign_id', '');
        $newsletterCycleDay = (int)$this->getRequest()->getParam('newsletter_cycle_day', 0);
        $isAutoresponderEnabled = (int)$this->getRequest()->getParam('newsletter_autoresponder', 0);

        if (0 === $isEnabled) {
            $newsletterCampaignId = '';
            $newsletterCycleDay = NULL;
        } else {
            $newsletterCycleDay = 0 === $isAutoresponderEnabled ? NULL : $newsletterCycleDay;
        }

        Mage::getModel('getresponse/settings')->updateSettings(
            [
                'newsletter_subscription' => $isEnabled,
                'newsletter_campaign_id' => $newsletterCampaignId,
                'newsletter_cycle_day' => $newsletterCycleDay,
            ],
            $this->currentShopId
        );

        $this->_getSession()->addSuccess('Settings saved');
        $this->_redirect('*/*/index');
    }

}