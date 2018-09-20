<?php
namespace GetResponse\GetResponseIntegration\Block;

use GetResponse\GetResponseIntegration\Domain\GetResponse\CustomFieldsCollection;
use GetResponse\GetResponseIntegration\Domain\GetResponse\RepositoryException;
use GetResponse\GetResponseIntegration\Domain\Magento\ConnectionSettings;
use GetResponse\GetResponseIntegration\Domain\Magento\ConnectionSettingsFactory;
use GrShareCode\Api\ApiTypeException;
use GrShareCode\ContactList\ContactListCollection;
use GrShareCode\ContactList\ContactListService;
use GrShareCode\GetresponseApiClient;
use Magento\Framework\View\Element\Template;
use GetResponse\GetResponseIntegration\Domain\Magento\Repository;
use GetResponse\GetResponseIntegration\Domain\GetResponse\RepositoryFactory;
use Magento\Framework\View\Element\Template\Context;

/**
 * Class Registration
 * @package GetResponse\GetResponseIntegration\Block
 */
class Registration extends Template
{
    /** @var Repository */
    private $repository;

    /** @var RepositoryFactory */
    private $repositoryFactory;

    /** @var GetresponseApiClient */
    private $grApiClient;

    /** @var Getresponse */
    private $getresponseBlock;

    /**
     * @param Context $context
     * @param Repository $repository
     * @param RepositoryFactory $repositoryFactory
     * @param Getresponse $getResponseBlock
     * @throws RepositoryException
     * @throws ApiTypeException
     */
    public function __construct(
        Context $context,
        Repository $repository,
        RepositoryFactory $repositoryFactory,
        Getresponse $getResponseBlock
    ) {
        parent::__construct($context);
        $this->repository = $repository;
        $this->repositoryFactory = $repositoryFactory;
        $this->grApiClient = $repositoryFactory->createGetResponseApiClient();
        $this->getresponseBlock = $getResponseBlock;
    }

    /**
     * @return ContactListCollection
     * @throws \GrShareCode\GetresponseApiException
     */
    public function getCampaigns()
    {
        return (new ContactListService($this->grApiClient))->getAllContactLists();
    }

    /**
     * @return ConnectionSettings
     */
    public function getConnectionSettings()
    {
        return ConnectionSettingsFactory::createFromArray($this->repository->getConnectionSettings());
    }

    /**
     * @return array
     */
    public function getAutoResponders()
    {
       return $this->getresponseBlock->getAutoResponders();
    }

    /**
     * @return array
     */
    public function getAutoRespondersForFrontend()
    {
        return $this->getresponseBlock->getAutoRespondersForFrontend();
    }

    /**
     * @return CustomFieldsCollection
     */
    public function getCustoms()
    {
        return $this->getresponseBlock->getCustoms();
    }

    public function getRegistrationSettings()
    {
        return $this->getresponseBlock->getRegistrationSettings();
    }
}
