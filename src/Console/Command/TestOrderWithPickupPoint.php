<?php
/**
 * Copyright (c) Falcon Media (info@falconmedia.nl)
 *
 * @author Falcon Media
 */

declare(strict_types=1);

namespace Innosend\OrderConnector\Console\Command;

use Innosend\OrderConnector\Model\OrderMapper;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\State;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\OrderFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI command: attach sample pickup point to an order and show how the order connector fetches the data.
 *
 * Usage:
 *   bin/magento innosend:test:order-with-pickup-point <order_id> [--attach]
 *
 * Without --attach: load order and show mapped data (same as innosend:test:order-mapper).
 * With --attach: write sample pickup point (GLS The Smokey Lab) to fm_innosend_order for this order,
 * then load the order and show mapped data so you can see the connector reading pickup point from the table.
 */
class TestOrderWithPickupPoint extends Command
{
    private const SAMPLE_PICKUP_POINT = [
        'pickup_point_id' => 'GLS_NL-10120195',
        'pickup_point_carrier' => 'gls',
        'pickup_point_name' => 'The Smokey Lab',
        'pickup_point_address' => 'Barndesteeg 9-C, 1012BV, AMSTERDAM',
        'pickup_point_street' => 'Barndesteeg 9-C',
        'pickup_point_zipcode' => '1012BV',
        'pickup_point_city' => 'AMSTERDAM',
    ];

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var OrderMapper
     */
    private $orderMapper;

    /**
     * @var State
     */
    private $state;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        OrderMapper $orderMapper,
        State $state,
        OrderFactory $orderFactory,
        ResourceConnection $resourceConnection
    ) {
        parent::__construct();
        $this->orderRepository = $orderRepository;
        $this->orderMapper = $orderMapper;
        $this->state = $state;
        $this->orderFactory = $orderFactory;
        $this->resourceConnection = $resourceConnection;
    }

    protected function configure(): void
    {
        $this->setName('innosend:test:order-with-pickup-point')
            ->setDescription('Attach sample pickup point to an order and show how the order connector fetches the data')
            ->addArgument('order_id', InputArgument::REQUIRED, 'Order ID or Increment ID')
            ->addOption('attach', null, InputOption::VALUE_NONE, 'Write sample pickup point to fm_innosend_order for this order, then show mapped result');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
        } catch (\Exception $e) {
            // Area code already set
        }

        $orderIdArg = $input->getArgument('order_id');
        $attach = (bool) $input->getOption('attach');

        // Load order to resolve ID and verify it exists
        $order = $this->loadOrder($orderIdArg, $output);
        if ($order === null) {
            return Command::FAILURE;
        }

        $orderId = (int) $order->getId();

        if ($attach) {
            $written = $this->attachSamplePickupPoint($orderId, $output);
            if (!$written) {
                return Command::FAILURE;
            }
            $output->writeln('');
            // Reload order so the OrderRepositoryPlugin can read from fm_innosend_order and set extension attributes
            try {
                $order = $this->orderRepository->get($orderId);
            } catch (\Throwable $e) {
                $output->writeln('<error>Failed to reload order: ' . $e->getMessage() . '</error>');
                return Command::FAILURE;
            }
        }

        $output->writeln('<info>Order:</info>');
        $output->writeln('  Order ID: ' . $order->getId());
        $output->writeln('  Increment ID: ' . $order->getIncrementId());
        $output->writeln('  Shipping Method: ' . ($order->getShippingMethod() ?? 'N/A'));
        $output->writeln('');

        $output->writeln('<info>Mapping order to Innosend format (order connector)...</info>');
        $orderData = $this->orderMapper->map($order);

        $output->writeln('<info>=== Mapped order data ===</info>');
        $output->writeln(json_encode($orderData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $output->writeln('');

        if (isset($orderData['pickup_point'])) {
            $output->writeln('<info>Pickup point in connector result:</info>');
            $output->writeln('  id: ' . ($orderData['pickup_point']['id'] ?? 'N/A'));
            $output->writeln('  courier: ' . ($orderData['pickup_point']['courier'] ?? 'N/A'));
            $output->writeln('  name: ' . ($orderData['pickup_point']['name'] ?? 'N/A'));
            $output->writeln('  address: ' . ($orderData['pickup_point']['address'] ?? 'N/A'));
            $output->writeln('  pickup_point_street: ' . ($orderData['pickup_point']['pickup_point_street'] ?? 'N/A'));
            $output->writeln('  pickup_point_zipcode: ' . ($orderData['pickup_point']['pickup_point_zipcode'] ?? 'N/A'));
            $output->writeln('  pickup_point_city: ' . ($orderData['pickup_point']['pickup_point_city'] ?? 'N/A'));
            $output->writeln('  checkout_courier: ' . ($orderData['checkout_courier'] ?? 'N/A'));
        } else {
            $output->writeln('<comment>No pickup point in mapped data (order has no pickup point, or fm_innosend_order row missing)</comment>');
        }

        return Command::SUCCESS;
    }

    private function loadOrder(string $orderId, OutputInterface $output): ?object
    {
        try {
            return $this->orderRepository->get((int) $orderId);
        } catch (\Throwable $e) {
            // Try by increment ID
        }

        $order = $this->orderFactory->create()->loadByIncrementId($orderId);
        if ($order->getId()) {
            return $order;
        }

        $output->writeln('<error>Order not found with ID or increment ID: ' . $orderId . '</error>');
        return null;
    }

    /**
     * Write sample pickup point to fm_innosend_order.
     * Uses same JSON structure as PickupPoints ShippingInformation::buildShippingInformation.
     */
    private function attachSamplePickupPoint(int $orderId, OutputInterface $output): bool
    {
        $shippingInformation = [
            'shipping_method' => 'innosend_pickup_points_innosend_pickup_points',
            'shipping_carrier' => 'innosend_pickup_points',
            'pickup_point' => [
                'id' => self::SAMPLE_PICKUP_POINT['pickup_point_id'],
                'courier' => self::SAMPLE_PICKUP_POINT['pickup_point_carrier'],
                'name' => self::SAMPLE_PICKUP_POINT['pickup_point_name'],
                'address' => self::SAMPLE_PICKUP_POINT['pickup_point_address'],
                'street' => self::SAMPLE_PICKUP_POINT['pickup_point_street'],
                'zipcode' => self::SAMPLE_PICKUP_POINT['pickup_point_zipcode'],
                'city' => self::SAMPLE_PICKUP_POINT['pickup_point_city'],
            ],
        ];
        $json = json_encode($shippingInformation, JSON_UNESCAPED_UNICODE);

        try {
            $connection = $this->resourceConnection->getConnection();
            $tableName = $this->resourceConnection->getTableName('fm_innosend_order');

            if (!$connection->isTableExists($tableName)) {
                $output->writeln('<error>Table fm_innosend_order does not exist. Run setup:upgrade or install Innosend_PickupPoints.</error>');
                return false;
            }

            $select = $connection->select()
                ->from($tableName, 'order_id')
                ->where('order_id = ?', $orderId)
                ->limit(1);
            $existing = $connection->fetchOne($select);

            if ($existing) {
                $connection->update(
                    $tableName,
                    ['shipping_information' => $json],
                    ['order_id = ?' => $orderId]
                );
                $output->writeln('<info>Updated fm_innosend_order with sample pickup point for order ID ' . $orderId . '</info>');
            } else {
                $connection->insert($tableName, [
                    'order_id' => $orderId,
                    'shipping_information' => $json,
                ]);
                $output->writeln('<info>Inserted fm_innosend_order with sample pickup point for order ID ' . $orderId . '</info>');
            }

            return true;
        } catch (\Throwable $e) {
            $output->writeln('<error>Failed to write to fm_innosend_order: ' . $e->getMessage() . '</error>');
            if (strpos($e->getMessage(), 'exist') !== false || strpos($e->getMessage(), 'Unknown table') !== false) {
                $output->writeln('<comment>Ensure the table exists (e.g. run bin/magento setup:upgrade).</comment>');
            }
            return false;
        }
    }
}
