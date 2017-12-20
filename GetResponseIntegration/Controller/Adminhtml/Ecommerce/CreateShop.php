<?php
namespace GetResponse\GetResponseIntegration\Controller\Adminhtml\Ecommerce;

use GetResponse\GetResponseIntegration\Controller\Adminhtml\AbstractController;
use GetResponse\GetResponseIntegration\Domain\GetResponse\RepositoryException;
use GetResponse\GetResponseIntegration\Domain\GetResponse\RepositoryValidator;
use GetResponse\GetResponseIntegration\Helper\Message;
use GetResponse\GetResponseIntegration\Domain\GetResponse\RepositoryFactory;
use GetResponse\GetResponseIntegration\Domain\Magento\Repository;
use GetResponse\GetResponseIntegration\Domain\GetResponse\Repository as GrRepository;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * Class CreateShop
 * @package GetResponse\GetResponseIntegration\Controller\Adminhtml\Settings
 */
class CreateShop extends AbstractController
{
    /** @var Repository */
    private $repository;

    /** @var GrRepository */
    private $grRepository;

    /** @var JsonFactory */
    private $resultJsonFactory;

    /**
     * @param Context $context
     * @param RepositoryFactory $repositoryFactory
     * @param Repository $repository
     * @param JsonFactory $resultJsonFactory
     * @param RepositoryValidator $repositoryValidator
     * @throws RepositoryException
     */
    public function __construct(
        Context $context,
        RepositoryFactory $repositoryFactory,
        Repository $repository,
        JsonFactory $resultJsonFactory,
        RepositoryValidator $repositoryValidator
    ) {
        parent::__construct($context, $repositoryValidator);
        $this->repository = $repository;
        $this->grRepository = $repositoryFactory->createRepository();
        $this->resultJsonFactory = $resultJsonFactory;

        return $this->checkGetResponseConnection();
    }

    /**
     * @return Json
     */
    public function execute()
    {
        /** @var Http $request */
        $request = $this->getRequest();
        $data = $request->getPostValue();

        if (!isset($data['shop_name']) || strlen($data['shop_name']) === 0) {
            return $this->resultJsonFactory->create()->setData(['error' => Message::INCORRECT_SHOP_NAME]);
        }

        $countryCode = $this->repository->getMagentoCountryCode();
        $lang = substr($countryCode, 0, 2);
        $currency = $this->repository->getMagentoCurrencyCode();
        $result = $this->grRepository->createShop($data['shop_name'], $lang, $currency);

        return $this->resultJsonFactory->create()->setData($result);
    }
}
