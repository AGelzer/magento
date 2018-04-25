<?php

/**
 * Class GetresponseIntegration_Getresponse_Domain_GetresponseCartBuilder
 */
class GetresponseIntegration_Getresponse_Domain_GetresponseCartBuilder
{
    /**
     * @param string                 $subscriberId
     * @param Mage_Sales_Model_Quote $quote
     * @param array                  $grProducts
     *
     * @return array
     * @throws Exception
     */
    public function buildGetresponseCart(
        $subscriberId,
        Mage_Sales_Model_Quote $quote,
        $grProducts
    ) {
        $grVariants = array();

        /** @var Mage_Sales_Model_Order_Item $product */
        foreach ($quote->getAllVisibleItems() as $product) {

            $grProduct = $grProducts[$product->getProduct()->getId()];
            $variant = (array)reset($grProduct['variants']);

            $grVariants[] = array(
                'variantId' => $variant['variantId'],
                'price'     => (float)$product->getData('base_price'),
                'priceTax'  => (float)$product->getData('price'),
                'quantity'  => (int)$product->getData('qty')
            );
        }

        $params = array(
            'contactId'        => $subscriberId,
            'currency'         => $quote->getQuoteCurrencyCode(),
            'totalPrice'       => (float)$quote->getGrandTotal(),
            'selectedVariants' => $grVariants,
            'externalId'       => $quote->getId(),
            'totalTaxPrice'    => (float)$quote->getGrandTotal()
        );

        return $params;
    }

    /**
     * @param string                 $subscriberId
     * @param Mage_Sales_Model_Order $order
     * @param array                  $grProducts
     *
     * @return array
     * @throws Exception
     */
    public function buildGetresponseCartFromOrder(
        $subscriberId,
        Mage_Sales_Model_Order $order,
        $grProducts
    ) {
        $grVariants = array();

        /** @var Mage_Sales_Model_Order_Item $product */
        foreach ($order->getAllVisibleItems() as $product) {

            $grProduct = $grProducts[$product->getProduct()->getId()];

            $variant = (array)reset($grProduct['variants']);

            $grVariants[] = array(
                'variantId' => $variant['variantId'],
                'price'     => (float)$product->getProduct()->getPrice(),
                'priceTax'  => (float)$product->getProduct()->getFinalPrice(),
                'quantity'  => (int)$product->getQtyOrdered()
            );
        }

        $params = array(
            'contactId'        => $subscriberId,
            'currency'         => $order->getOrderCurrencyCode(),
            'totalPrice'       => (float)$order->getGrandTotal(),
            'selectedVariants' => $grVariants,
            'externalId'       => $order->getId(),
            'totalTaxPrice'    => (float)$order->getGrandTotal()
        );

        return $params;
    }
}
