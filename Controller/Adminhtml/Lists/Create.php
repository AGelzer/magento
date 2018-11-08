<?php
namespace GetResponse\GetResponseIntegration\Controller\Adminhtml\Lists;

use Exception;
use GetResponse\GetResponseIntegration\Controller\Adminhtml\AbstractController;
use GetResponse\GetResponseIntegration\Domain\GetResponse\ListValidator;
use GetResponse\GetResponseIntegration\Helper\Message;
use GetResponse\GetResponseIntegration\Domain\GetResponse\GetresponseApiClientFactory;
use GetResponse\GetResponseIntegration\Domain\Magento\Repository;
use GrShareCode\ContactList\AddContactListCommand;
use GrShareCode\ContactList\ContactListService;
use Magento\Framework\App\ResponseInterface;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Request\Http;

/**
 * Class Create
 * @package GetResponse\GetResponseIntegration\Controller\Adminhtml\Lists
 */
class Create extends AbstractController
{
    const PAGE_TITLE = 'New Contact List';

    /** @var PageFactory */
    protected $resultPageFactory;

    /** @var Repository */
    private $repository;

    /** @var GetresponseApiClientFactory */
    private $apiClientFactory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Repository $repository
     * @param GetresponseApiClientFactory $apiClientFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Repository $repository,
        GetresponseApiClientFactory $apiClientFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->repository = $repository;
        $this->apiClientFactory = $apiClientFactory;
    }

    /**
     * @return ResultInterface|ResponseInterface
     */
    public function execute()
    {
        try {
            $backUrl = $this->getRequest()->getParam('back_url');
            $resultPage = $this->resultPageFactory->create();
            $resultPage->getConfig()->getTitle()->prepend(self::PAGE_TITLE);

            /** @var Http $request */
            $request = $this->getRequest();
            $data = $request->getPostValue();

            if (empty($data)) {
                return $resultPage;
            }

            $error = ListValidator::validateNewListParams($data);

            if (!empty($error)) {
                throw new Exception($error);
            }

            $data['lang'] = substr($this->repository->getMagentoCountryCode(), 0, 2);

            $apiClient = $this->apiClientFactory->createGetResponseApiClient();
            $service = new ContactListService($apiClient);
            $service->createContactList(new AddContactListCommand(
                $data['campaign_name'],
                $data['from_field'],
                $data['reply_to_field'],
                $data['confirmation_body'],
                $data['confirmation_subject'],
                $data['lang']
            ));

            $this->messageManager->addSuccessMessage(Message::LIST_CREATED);
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath($backUrl);
            return $resultRedirect;
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * @param Exception $e
     * @return Page
     */
    private function handleException(Exception $e)
    {
        $this->messageManager->addErrorMessage($e->getMessage());
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(self::PAGE_TITLE);
        return $resultPage;
    }
}
