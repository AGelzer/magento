<?php
namespace GetResponse\GetResponseIntegration\Block;

use GetResponse\GetResponseIntegration\Domain\GetResponse\Account as AccountBlock;
use GetResponse\GetResponseIntegration\Domain\GetResponse\RepositoryFactory;
use GetResponse\GetResponseIntegration\Domain\Magento\ConnectionSettingsFactory;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use GetResponse\GetResponseIntegration\Domain\Magento\Repository;
use Magento\Framework\App\Request\Http;

/**
 * Class Account
 * @package GetResponse\GetResponseIntegration\Block
 */
class Account extends Template
{
    /** @var Repository */
    private $repository;

    /** @var RepositoryFactory */
    private $repositoryFactory;

    /** @var Getresponse */
    private $getresponseBlock;

    /**
     * @param Context $context
     * @param Repository $repository
     * @param RepositoryFactory $repositoryFactory
     * @param Getresponse $getresponseBlock
     */
    public function __construct(
        Context $context,
        Repository $repository,
        RepositoryFactory $repositoryFactory,
        Getresponse $getresponseBlock
    ) {
        parent::__construct($context);
        $this->repository = $repository;
        $this->repositoryFactory = $repositoryFactory;
        $this->getresponseBlock = $getresponseBlock;
    }

    /**
     * @return AccountBlock
     */
    public function getAccountInfo()
    {
        return $this->getresponseBlock->getAccountInfo();
    }

    /**
     * @return bool
     */
    public function isConnectedToGetResponse()
    {
        $settings = $this->repository->getConnectionSettings();
        return !empty($settings['apiKey']);
    }

    /**
     * @return string
     */
    public function getLastPostedApiKey()
    {
        /** @var Http $request */
        $request = $this->getRequest();
        $data = $request->getPostValue();
        if (!empty($data)) {
            if (isset($data['api_key'])) {
                return $data['api_key'];
            }
        }

        return '';
    }

    /**
     * @return int
     */
    public function getLastPostedTypeOfAccountCheckboxValue()
    {
        /** @var Http $request */
        $request = $this->getRequest();
        $data = $request->getPostValue();
        if (!empty($data['is_mx']) && 1 == $data['is_mx']) {
            return 1;
        }

        return 0;
    }

    /**
     * @return string
     */
    public function getLastPostedApiUrl()
    {
        /** @var Http $request */
        $request = $this->getRequest();
        $data = $request->getPostValue();
        if (!empty($data['api_url'])) {
            return $data['api_url'];
        }

        return '';
    }

    /**
     * @return string
     */
    public function getLastPostedApiDomain()
    {
        /** @var Http $request */
        $request = $this->getRequest();
        $data = $request->getPostValue();
        if (!empty($data['getresponse_api_domain'])) {
            return $data['getresponse_api_domain'];
        }

        return '';
    }

    /**
     * @return string
     */
    public function getHiddenApiKey()
    {
        $connectionSettings = ConnectionSettingsFactory::createFromArray($this->repository->getConnectionSettings());

        if (0 === strlen($connectionSettings->getApiKey())) {
            return '';
        }

        return str_repeat("*", strlen($connectionSettings->getApiKey()) - 6) . substr($connectionSettings->getApiKey(), -6);
    }
}
