<?php
namespace GetResponse\GetResponseIntegration\Test\Unit\Block;

use GetResponse\GetResponseIntegration\Block\Export as ExportBlock;
use GetResponse\GetResponseIntegration\Block\Export;
use GetResponse\GetResponseIntegration\Block\Getresponse;
use GetResponse\GetResponseIntegration\Domain\GetResponse\CustomField;
use GetResponse\GetResponseIntegration\Domain\GetResponse\CustomFieldsCollection;
use GetResponse\GetResponseIntegration\Domain\GetResponse\RepositoryFactory;
use GetResponse\GetResponseIntegration\Domain\Magento\RegistrationSettings;
use GetResponse\GetResponseIntegration\Domain\Magento\Repository;
use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\TestCase;
use Magento\Framework\View\Element\Template\Context;
use PHPUnit_Framework_MockObject_MockObject;
use GetResponse\GetResponseIntegration\Domain\GetResponse\Repository as GrRepository;

/**
 * Class ExportTest
 * @package GetResponse\GetResponseIntegration\Test\Unit\Block
 */
class ExportTest extends TestCase
{
    /** @var Context|PHPUnit_Framework_MockObject_MockObject */
    private $context;

    /** @var Repository|PHPUnit_Framework_MockObject_MockObject */
    private $repository;

    /** @var RepositoryFactory|PHPUnit_Framework_MockObject_MockObject */
    private $repositoryFactory;

    /** @var ExportBlock */
    private $exportBlock;

    /** @var ObjectManagerInterface */
    private $objectManager;

    /** @var GrRepository|PHPUnit_Framework_MockObject_MockObject */
    private $grRepository;

    public function setUp()
    {
        $this->context = $this->createMock(Context::class);
        $this->repository = $this->createMock(Repository::class);
        $this->repositoryFactory = $this->createMock(RepositoryFactory::class);
        $this->objectManager = $this->createMock(ObjectManagerInterface::class);
        $this->grRepository = $this->createMock(GrRepository::class);
        $this->repositoryFactory->expects($this->atLeastOnce())->method('createRepository')->willReturn($this->grRepository);

        $getresponseBlock = new Getresponse($this->repository, $this->repositoryFactory);
        $this->exportBlock = new ExportBlock($this->context, $this->objectManager, $this->repository, $this->repositoryFactory, $getresponseBlock);
    }

    /**
     * @test
     * @param array $settings
     * @param RegistrationSettings $expectedExportSettings
     *
     * @dataProvider shouldReturnExportSettingsProvider
     */
    public function shouldReturnExportSettings(array $settings, RegistrationSettings $expectedExportSettings)
    {
        $this->repository->expects($this->atLeastOnce())->method('getRegistrationSettings')->willReturn($settings);
        $exportSettings = $this->exportBlock->getExportSettings();

        self::assertEquals($exportSettings, $expectedExportSettings);
    }

    /**
     * @return array
     */
    public function shouldReturnExportSettingsProvider()
    {
        return [
            [[], new RegistrationSettings(0, 0, '', 0)],
            [
                [
                    'status' => 1,
                    'customFieldsStatus' => 0,
                    'campaignId' => '1v4',
                    'cycleDay' => 6
                ], new RegistrationSettings(1, 0, '1v4', 6)
            ]
        ];
    }

    /**
     * @test
     *
     * @param array $rawCustoms
     * @param CustomField $expectedFirstCustom
     * @dataProvider shouldReturnCustomsProvider
     */
    public function shouldReturnCustoms(array $rawCustoms, CustomField $expectedFirstCustom)
    {
        $this->repository->expects($this->once())->method('getCustoms')->willReturn($rawCustoms);

        $customs = $this->exportBlock->getCustoms();
        self::assertInstanceOf(CustomFieldsCollection::class, $customs);

        if (count($customs->getCustoms()) > 0) {

            $custom = $customs->getCustoms()[0];
            self::assertInstanceOf(CustomField::class, $custom);
            self::assertEquals($expectedFirstCustom->getId(), $custom->getId());
            self::assertEquals($expectedFirstCustom->getCustomField(), $custom->getCustomField());
            self::assertEquals($expectedFirstCustom->getCustomField(), $custom->getCustomField());
            self::assertEquals($expectedFirstCustom->getCustomValue(), $custom->getCustomValue());
            self::assertEquals($expectedFirstCustom->getCustomName(), $custom->getCustomName());
            self::assertEquals($expectedFirstCustom->isDefault(), $custom->isDefault());
            self::assertEquals($expectedFirstCustom->isActive(), $custom->isActive());
        }
    }

    /**
     * @return array
     */
    public function shouldReturnCustomsProvider()
    {
        $id = 3;
        $customField = 'testCustomField';
        $customValue = 'testCustomValue';
        $customName = 'testCustomName';
        $isDefault = 1;
        $isActive = 0;

        $rawCustomField = new \stdClass();
        $rawCustomField->id = $id;
        $rawCustomField->customField = $customField;
        $rawCustomField->customValue = $customValue;
        $rawCustomField->customName = $customName;
        $rawCustomField->isDefault = $isDefault;
        $rawCustomField->isActive = $isActive;

        $customField = new CustomField($id, $customField, $customValue, $customName, $isDefault, $isActive);

        return [
            [[], new CustomField(0, '','','',0, 0)],
            [
                [$rawCustomField],
                $customField
            ]
        ];
    }
}
