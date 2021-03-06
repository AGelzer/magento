<?php
namespace GetResponse\GetResponseIntegration\Domain\GetResponse\Order;

use DateTime;
use GetResponse\GetResponseIntegration\Domain\GetResponse\Order\Address\AddressFactory;
use GetResponse\GetResponseIntegration\Domain\GetResponse\Order\Exception\InvalidOrderException;
use GetResponse\GetResponseIntegration\Domain\GetResponse\Product\ProductFactory;
use GrShareCode\Order\Order as GrOrder;
use GrShareCode\Product\ProductsCollection;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item;

/**
 * Class OrderFactory
 * @package GetResponse\GetResponseIntegration\Domain\GetResponse\Order
 */
class OrderFactory
{
    /** @var ProductFactory */
    private $productFactory;

    /** @var AddressFactory */
    private $addressFactory;

    /**
     * @param ProductFactory $productFactory
     * @param AddressFactory $addressFactory
     */
    public function __construct(ProductFactory $productFactory, AddressFactory $addressFactory)
    {
        $this->productFactory = $productFactory;
        $this->addressFactory = $addressFactory;
    }

    /**
     * @param Order $order
     * @return GrOrder
     * @throws InvalidOrderException
     */
    public function fromMagentoOrder(Order $order)
    {
        $productCollection = new ProductsCollection();

        /** @var Item $orderItem */
        foreach ($order->getAllVisibleItems() as $orderItem) {

            if (!$orderItem->getProduct()) {
                throw InvalidOrderException::forItemWithEmptyProduct($orderItem);
            }

            $productCollection->add(
                $this->productFactory->fromMagentoOrderItem($orderItem)
            );
        }

        $grOrder = new GrOrder(
            $order->getId(),
            (float)$order->getBaseSubtotal(),
            $order->getOrderCurrencyCode(),
            $productCollection
        );

        $shippingAddress = $this->addressFactory->createShippingAddressFromMagentoOrder($order);
        $billingAddress = $this->addressFactory->createBillingAddressFromMagentoOrder($order);

        $grOrder
            ->setTotalPriceTax((float)$order->getTaxAmount())
            ->setStatus($order->getStatus())
            ->setExternalCartId($order->getQuoteId())
            ->setShippingPrice((float)$order->getShippingAmount())
            ->setProcessedAt(DateTime::createFromFormat('Y-m-d H:i:s', $order->getCreatedAt())->format(DateTime::ISO8601))
            ->setShippingAddress($shippingAddress)
            ->setBillingAddress($billingAddress);

        return $grOrder;
    }

}