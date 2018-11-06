<?php
namespace GetResponse\GetResponseIntegration\Block;

use GetResponse\GetResponseIntegration\Domain\GetResponse\RepositoryFactory;
use Magento\Framework\View\Element\Template\Context;
use GetResponse\GetResponseIntegration\Domain\Magento\Repository;
use Magento\Framework\View\Element\Template;
use GetResponse\GetResponseIntegration\Domain\GetResponse\Account;

/**
 * Class Settings
 * @package GetResponse\GetResponseIntegration\Block
 */
class Settings extends Template
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
     * @param Getresponse $getResponseBlock
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
        $this->getresponseBlock = $getResponseBlock;
    }

    /**
     * @return Account
     */
    public function getAccountInfo()
    {
        return $this->getresponseBlock->getAccountInfo();
    }
}
