<?php
namespace GetResponse\GetResponseIntegration\Controller\Adminhtml\Settings;

use GetResponse\GetResponseIntegration\Helper\Config;
use Magento\Backend\App\Action;
use GetResponse\GetResponseIntegration\Domain\GetResponse\RepositoryValidator;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class Webform
 * @package GetResponse\GetResponseIntegration\Controller\Adminhtml\Settings
 */
class Webform extends Action
{
    /** @var PageFactory */
    protected $resultPageFactory;
    /** @var RepositoryValidator */

    /** @var RepositoryValidator */
    private $repositoryValidator;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param RepositoryValidator $repositoryValidator
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        RepositoryValidator $repositoryValidator
    )
    {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->repositoryValidator = $repositoryValidator;

    }

    /**
     * @return ResponseInterface|Page
     */
    public function execute()
    {
        if (!$this->repositoryValidator->validate()) {
            $this->messageManager->addErrorMessage(Config::INCORRECT_API_RESOONSE_MESSAGE);
            return $this->_redirect(Config::PLUGIN_MAIN_PAGE);
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend('Add contacts via GetResponse forms');
        return $resultPage;
    }
}
