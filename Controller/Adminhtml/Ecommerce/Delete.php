<?php

namespace GetResponse\GetResponseIntegration\Controller\Adminhtml\Ecommerce;

use Exception;
use GetResponse\GetResponseIntegration\Controller\Adminhtml\AbstractController;
use GetResponse\GetResponseIntegration\Helper\Message;
use GetResponse\GetResponseIntegration\Domain\GetResponse\GetresponseApiClientFactory;
use GrShareCode\Shop\ShopService;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;

/**
 * Class Delete
 * @package GetResponse\GetResponseIntegration\Controller\Adminhtml\Ecommerce
 */
class Delete extends AbstractController
{
    const BACK_URL = 'getresponse/ecommerce/index';

    /** @var GetresponseApiClientFactory */
    private $apiClientFactory;

    /**
     * @param Context $context
     * @param GetresponseApiClientFactory $apiClientFactory
     */
    public function __construct(
        Context $context,
        GetresponseApiClientFactory $apiClientFactory
    ) {
        parent::__construct($context);
        $this->apiClientFactory = $apiClientFactory;
    }

    /**
     * @return ResponseInterface|Redirect
     */
    public function execute()
    {
        try {
            $id = $this->getRequest()->getParam('id');

            if (empty($id)) {
                throw new Exception(Message::INCORRECT_SHOP);
            }

            $service = new ShopService($this->apiClientFactory->createGetResponseApiClient());
            $service->deleteShop($id);
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath(self::BACK_URL);
            return $resultRedirect;
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * @param Exception $e
     * @return Redirect
     */
    private function handleException(Exception $e)
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $this->messageManager->addErrorMessage($e->getMessage());
        $resultRedirect->setPath(self::BACK_URL);
        return $resultRedirect;
    }
}
