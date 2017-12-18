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
     * @return bool
     */
    public function getLastPostedApiKey()
    {
        /** @var Http $request */
        $request = $this->getRequest();
        $data = $request->getPostValue();
        if (!empty($data)) {
            if (isset($data['getresponse_api_key'])) {
                return $data['getresponse_api_key'];
            }
        }

        return false;
    }

    /**
     * @return int
     */
    public function getLastPostedApiAccount()
    {
        /** @var Http $request */
        $request = $this->getRequest();
        $data = $request->getPostValue();
        if (!empty($data['getresponse_360_account']) && 1 == $data['getresponse_360_account']) {
            return $data['getresponse_360_account'];
        }

        return 0;
    }

    /**
     * @return bool
     */
    public function getLastPostedApiUrl()
    {
        /** @var Http $request */
        $request = $this->getRequest();
        $data = $request->getPostValue();
        if (!empty($data['getresponse_api_url'])) {
            return $data['getresponse_api_url'];
        }

        return false;
    }

    /**
     * @return bool
     */
    public function getLastPostedApiDomain()
    {
        /** @var Http $request */
        $request = $this->getRequest();
        $data = $request->getPostValue();
        if (!empty($data['getresponse_api_domain'])) {
            return $data['getresponse_api_domain'];
        }

        return false;
    }

    /**
     * @return string
     */
    public function getHiddenApiKey()
    {
        $settings = $this->repository->getConnectionSettings();

        if (empty($settings)) {
            return '';
        }

        $settings = ConnectionSettingsFactory::createFromArray($settings);

        if (empty($settings->getApiKey())) {
            return '';
        }

        return strlen($settings->getApiKey()) > 0 ? str_repeat(
                "*",
                strlen($settings->getApiKey()) - 6
            ) . substr($settings->getApiKey(), -6) : '';
    }
}
