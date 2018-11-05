<?php
namespace GetResponse\GetResponseIntegration\Domain\GetResponse\ExportOnDemand\Dto;

use GetResponse\GetResponseIntegration\Domain\GetResponse\CustomFieldsMapping\Dto\CustomFieldMappingDtoCollection;

/**
 * Class ExportOnDemandDto
 */
class ExportOnDemandDto
{
    /** @var string */
    private $contactListId;

    /** @var bool */
    private $autoresponderEnabled;

    /** @var null|int */
    private $dayOfCycle;

    /** @var bool */
    private $ecommerceEnabled;

    /** @var null|string */
    private $storeId;

    /** @var bool */
    private $updateContactCustomFieldEnabled;

    /** @var CustomFieldMappingDtoCollection */
    private $customFieldMappingDtoCollection;

    /**
     * @param string $contactListId
     * @param bool $autoresponderEnabled
     * @param int|null $dayOfCycle
     * @param bool $ecommerceEnabled
     * @param string|null $storeId
     * @param bool $updateContactCustomFieldEnabled
     * @param CustomFieldMappingDtoCollection $customFieldMappingDtoCollection
     */
    public function __construct(
        $contactListId,
        $autoresponderEnabled,
        $dayOfCycle,
        $ecommerceEnabled,
        $storeId,
        $updateContactCustomFieldEnabled,
        CustomFieldMappingDtoCollection $customFieldMappingDtoCollection
    ) {
        $this->contactListId = $contactListId;
        $this->autoresponderEnabled = $autoresponderEnabled;
        $this->dayOfCycle = $dayOfCycle;
        $this->ecommerceEnabled = $ecommerceEnabled;
        $this->storeId = $storeId;
        $this->updateContactCustomFieldEnabled = $updateContactCustomFieldEnabled;
        $this->customFieldMappingDtoCollection = $customFieldMappingDtoCollection;
    }

    /**
     * @param array $requestData
     * @return ExportOnDemandDto
     */
    public static function createFromRequest(array $requestData)
    {
        return new self(
            $requestData['campaign_id'],
            isset($requestData['gr_autoresponder']),
            (isset($requestData['gr_autoresponder']) && $requestData['cycle_day'] !== '') ? (int)$requestData['cycle_day'] : null,
            isset($requestData['ecommerce']) && !empty($requestData['ecommerce']),
            (isset($requestData['ecommerce']) && !empty($requestData['ecommerce'])) ? $requestData['store_id'] : null,
            isset($requestData['gr_sync_order_data']),
            CustomFieldMappingDtoCollection::createFromRequestData($requestData)
        );
    }
    /**
     * @return string
     */
    public function getContactListId()
    {
        return $this->contactListId;
    }

    /**
     * @return bool
     */
    public function isAutoresponderEnabled()
    {
        return $this->autoresponderEnabled;
    }

    /**
     * @return int|null
     */
    public function getDayOfCycle()
    {
        return $this->dayOfCycle;
    }

    /**
     * @return bool
     */
    public function isEcommerceEnabled()
    {
        return $this->ecommerceEnabled;
    }

    /**
     * @return string|null
     */
    public function getStoreId()
    {
        return $this->storeId;
    }

    /**
     * @return bool
     */
    public function isUpdateContactCustomFieldEnabled()
    {
        return $this->updateContactCustomFieldEnabled;
    }

    /**
     * @return CustomFieldMappingDtoCollection
     */
    public function getCustomFieldMappingDtoCollection()
    {
        return $this->customFieldMappingDtoCollection;
    }

}