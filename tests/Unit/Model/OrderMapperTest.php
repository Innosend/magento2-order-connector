<?php
/**
 * Copyright (c) Falcon Media (info@falconmedia.nl)
 *
 * @author Falcon Media
 */

declare(strict_types=1);

namespace Innosend\OrderConnector\Tests\Unit\Model;

use Innosend\OrderConnector\Model\OrderMapper;
use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for OrderMapper (pickup point data in mapped result)
 */
class OrderMapperTest extends TestCase
{
    /** @var ResourceConnection&MockObject */
    private $resourceConnection;

    /** @var LoggerInterface&MockObject */
    private $logger;

    private OrderMapper $orderMapper;

    protected function setUp(): void
    {
        $this->resourceConnection = $this->createMock(ResourceConnection::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->orderMapper = new OrderMapper(
            $this->resourceConnection,
            $this->logger
        );
    }

    /**
     * Order without pickup point: mapped data has no pickup_point key.
     */
    public function testMapOrderWithoutPickupPointReturnsNoPickupPointData(): void
    {
        $order = $this->createOrderMock(null, 'flatrate_flatrate');
        $result = $this->orderMapper->map($order);

        $this->assertArrayNotHasKey('pickup_point', $result);
        $this->assertArrayNotHasKey('checkout_courier', $result);
    }

    /**
     * Order with pickup point in extension attributes: connector maps id, name, address, zipcode, etc.
     */
    public function testMapOrderWithPickupPointInExtensionAttributesReturnsPickupPointData(): void
    {
        $pickupPoint = $this->createPickupPointMock(
            'GLS_NL-10120195',
            'gls',
            'The Smokey Lab',
            'Barndesteeg 9-C, 1012BV, AMSTERDAM',
            'Barndesteeg 9-C',
            '1012BV',
            'AMSTERDAM'
        );

        $extensionAttributes = $this->createMock(\Magento\Sales\Api\Data\OrderExtensionInterface::class);
        $extensionAttributes->method('getInnosendPickupPoint')->willReturn($pickupPoint);

        $order = $this->createOrderMock($extensionAttributes, 'innosend_pickup_points_innosend_pickup_points');
        $result = $this->orderMapper->map($order);

        $this->assertArrayHasKey('pickup_point', $result);
        $this->assertSame('GLS_NL-10120195', $result['pickup_point']['id']);
        $this->assertSame('gls', $result['pickup_point']['courier']);
        $this->assertSame('The Smokey Lab', $result['pickup_point']['name']);
        $this->assertSame('Barndesteeg 9-C, 1012BV, AMSTERDAM', $result['pickup_point']['address']);
        $this->assertSame('Barndesteeg 9-C', $result['pickup_point']['pickup_point_street']);
        $this->assertSame('1012BV', $result['pickup_point']['pickup_point_zipcode']);
        $this->assertSame('AMSTERDAM', $result['pickup_point']['pickup_point_city']);

        $this->assertArrayHasKey('checkout_courier', $result);
        $this->assertSame('gls', $result['checkout_courier']);
    }

    /**
     * Create order mock with optional extension attributes and shipping method.
     */
    private function createOrderMock($extensionAttributes, string $shippingMethod): OrderInterface
    {
        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
        $order->method('getId')->willReturn(1);
        $order->method('getIncrementId')->willReturn('000000001');
        $order->method('getCreatedAt')->willReturn('2024-01-15 10:00:00');
        $order->method('getOrderCurrencyCode')->willReturn('EUR');
        $order->method('getGrandTotal')->willReturn(50.00);
        $order->method('getSubtotal')->willReturn(45.00);
        $order->method('getShippingAmount')->willReturn(5.00);
        $order->method('getTaxAmount')->willReturn(0.00);
        $order->method('getDiscountAmount')->willReturn(0.00);
        $order->method('getCustomerEmail')->willReturn('test@example.com');
        $order->method('getCustomerFirstname')->willReturn('Test');
        $order->method('getCustomerLastname')->willReturn('User');
        $order->method('getShippingMethod')->willReturn($shippingMethod);
        $order->method('getExtensionAttributes')->willReturn($extensionAttributes);
        $order->method('getShippingAddress')->willReturn($this->createMock(OrderAddressInterface::class));
        $order->method('getBillingAddress')->willReturn($this->createMock(OrderAddressInterface::class));
        $order->method('getPayment')->willReturn(null);
        $order->method('getAllItems')->willReturn([]);

        return $order;
    }

    /**
     * Create pickup point mock (extension attribute value object).
     */
    private function createPickupPointMock(
        string $id,
        string $courier,
        string $name,
        string $address,
        string $street,
        string $zipcode,
        string $city
    ): object {
        $pickup = $this->getMockBuilder(\stdClass::class)
            ->addMethods([
                'getPickupPointId',
                'getCourierCode',
                'getPickupPointName',
                'getPickupPointAddress',
                'getPickupPointStreet',
                'getPickupPointZipcode',
                'getPickupPointCity',
            ])
            ->getMock();
        $pickup->method('getPickupPointId')->willReturn($id);
        $pickup->method('getCourierCode')->willReturn($courier);
        $pickup->method('getPickupPointName')->willReturn($name);
        $pickup->method('getPickupPointAddress')->willReturn($address);
        $pickup->method('getPickupPointStreet')->willReturn($street);
        $pickup->method('getPickupPointZipcode')->willReturn($zipcode);
        $pickup->method('getPickupPointCity')->willReturn($city);

        return $pickup;
    }
}
