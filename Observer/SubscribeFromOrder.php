<?php

namespace GetResponse\GetResponseIntegration\Observer;

use GetResponse\GetResponseIntegration\Domain\GetResponse\Contact\ContactCustomFields;
use GetResponse\GetResponseIntegration\Domain\GetResponse\Contact\ContactService;
use GetResponse\GetResponseIntegration\Domain\GetResponse\SubscribeViaRegistration\SubscribeViaRegistrationFactory;
use GetResponse\GetResponseIntegration\Domain\GetResponse\SubscribeViaRegistration\SubscribeViaRegistrationService;
use GetResponse\GetResponseIntegration\Domain\Magento\ConnectionSettingsException;
use GetResponse\GetResponseIntegration\Domain\Magento\Repository;
use GrShareCode\Api\Authorization\ApiTypeException;
use GrShareCode\Api\Exception\GetresponseApiException;
use GrShareCode\Contact\ContactCustomField\ContactCustomFieldsCollection;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\ObjectManagerInterface;

/**
 * Class SubscribeFromOrder
 * @package GetResponse\GetResponseIntegration\Observer
 */
class SubscribeFromOrder implements ObserverInterface
{
    /** @var ObjectManagerInterface */
    protected $_objectManager;

    /** @var Repository */
    private $repository;

    /** @var ContactService */
    private $contactService;

    /** @var ContactCustomFields */
    private $contactCustomFields;

    /** @var SubscribeViaRegistrationService */
    private $subscribeViaRegistrationService;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param Repository $repository
     * @param ContactService $contactService
     * @param SubscribeViaRegistrationService $subscribeViaRegistrationService
     * @param ContactCustomFields $contactCustomFields
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        Repository $repository,
        ContactService $contactService,
        SubscribeViaRegistrationService $subscribeViaRegistrationService,
        ContactCustomFields $contactCustomFields
    ) {
        $this->_objectManager = $objectManager;
        $this->repository = $repository;
        $this->contactService = $contactService;
        $this->contactCustomFields = $contactCustomFields;
        $this->subscribeViaRegistrationService = $subscribeViaRegistrationService;
    }

    /**
     * @param EventObserver $observer
     * @return $this
     */
    public function execute(EventObserver $observer)
    {
        $registrationSettings = SubscribeViaRegistrationFactory::createFromArray(
            $this->repository->getRegistrationSettings()
        );

        if (!$registrationSettings->isEnabled()) {
            return $this;
        }

        $orderIds = $observer->getOrderIds();
        $orderId = (int)(is_array($orderIds) ? array_pop($orderIds) : $orderIds);

        if ($orderId < 1) {
            return $this;
        }

        $order = $this->repository->loadOrder($orderId);
        $customer = $this->repository->loadCustomer($order->getCustomerId());
        $subscriber = $this->repository->loadSubscriberByEmail($customer->getEmail());

        if (!$subscriber->isSubscribed()) {
            return $this;
        }

        $contactCustomFieldsCollection = $this->contactCustomFields->getFromCustomer(
            $customer,
            $this->subscribeViaRegistrationService->getCustomFieldMappingSettings(),
            $registrationSettings->isUpdateCustomFieldsEnalbed()
        );

        $this->addContact(
            $registrationSettings->getCampaignId(),
            $customer->getFirstname(),
            $customer->getLastname(),
            $customer->getEmail(),
            $registrationSettings->getCycleDay(),
            $contactCustomFieldsCollection,
            $registrationSettings->isUpdateCustomFieldsEnalbed()
        );

        return $this;
    }


    /**
     * @param string $contactListId
     * @param string $firstName
     * @param string $lastName
     * @param string $email
     * @param null|int $dayOfCycle
     * @param ContactCustomFieldsCollection $contactCustomFieldsCollection
     * @param bool $updateIfAlreadyExists
     */
    private function addContact(
        $contactListId,
        $firstName,
        $lastName,
        $email,
        $dayOfCycle,
        ContactCustomFieldsCollection $contactCustomFieldsCollection,
        $updateIfAlreadyExists
    ) {
        try {
            $this->contactService->addContact(
                $email,
                $firstName,
                $lastName,
                $contactListId,
                $dayOfCycle,
                $contactCustomFieldsCollection,
                $updateIfAlreadyExists
            );
        } catch (ApiTypeException $e) {
        } catch (GetresponseApiException $e) {
        } catch (ConnectionSettingsException $e) {
        }
    }

}
