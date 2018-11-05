<?php
namespace GetResponse\GetResponseIntegration\Domain\GetResponse\CustomFieldsMapping;

/**
 * Class CustomsMapping
 * @package GetResponse\GetResponseIntegration\Domain\GetResponse
 */
class CustomFieldsMapping
{
    const DEFAULT_LABEL_EMAIL = 'Email';
    const DEFAULT_LABEL_FIRST_NAME = 'First Name';
    const DEFAULT_LABEL_LAST_NAME = 'Last Name';

    const DEFAULT_YES = true;
    const DEFAULT_NO = false;

    /** @var string|null */
    private $getResponseCustomId;

    /** @var string|null */
    private $magentoAttributeCode;

    /** @var bool */
    private $default;

    /** @var string|null */
    private $getResponseDefaultLabel;

    /**
     * @param string|null $getResponseCustomId
     * @param string|null $magentoAttributeCode
     * @param bool $default
     * @param string|null $getResponseDefaultLabel
     */
    public function __construct($getResponseCustomId, $magentoAttributeCode, $default, $getResponseDefaultLabel)
    {
        $this->getResponseCustomId = $getResponseCustomId;
        $this->magentoAttributeCode = $magentoAttributeCode;
        $this->default = $default;
        $this->getResponseDefaultLabel = $getResponseDefaultLabel;
    }

    /**
     * @return null|string
     */
    public function getGetResponseCustomId()
    {
        return $this->getResponseCustomId;
    }

    /**
     * @return null|string
     */
    public function getMagentoAttributeCode()
    {
        return $this->magentoAttributeCode;
    }

    /**
     * @return bool
     */
    public function isDefault()
    {
        return $this->default;
    }

    /**
     * @return null|string
     */
    public function getGetResponseDefaultLabel()
    {
        return $this->getResponseDefaultLabel;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'getResponseCustomId' => $this->getGetResponseCustomId(),
            'magentoAttributeCode' => $this->getMagentoAttributeCode(),
            'getResponseDefaultLabel' => $this->getGetResponseDefaultLabel(),
            'default' => $this->isDefault(),
        ];
    }

    /**
     * @param array $data
     * @return CustomFieldsMapping
     */
    public static function fromArray(array $data)
    {
        return new self(
            $data['getResponseCustomId'],
            $data['magentoAttributeCode'],
            $data['default'],
            $data['getResponseDefaultLabel']
        );
    }

}
