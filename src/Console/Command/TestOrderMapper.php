<?php
/**
 * Copyright (c) Falcon Media (info@falconmedia.nl)
 *
 * @author Falcon Media
 */

declare(strict_types=1);

namespace Innosend\OrderConnector\Console\Command;

use Innosend\OrderConnector\Model\OrderMapper;
use Magento\Framework\App\State;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\OrderFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI command to test OrderMapper with pickup point data
 */
class TestOrderMapper extends Command
{
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
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderMapper $orderMapper
     * @param State $state
     * @param OrderFactory $orderFactory
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        OrderMapper $orderMapper,
        State $state,
        OrderFactory $orderFactory
    ) {
        parent::__construct();
        $this->orderRepository = $orderRepository;
        $this->orderMapper = $orderMapper;
        $this->state = $state;
        $this->orderFactory = $orderFactory;
    }

    /**
     * Configure command
     */
    protected function configure()
    {
        $this->setName('innosend:test:order-mapper')
            ->setDescription('Test OrderMapper with pickup point data')
            ->addArgument('order_id', InputArgument::REQUIRED, 'Order ID or Increment ID');
    }

    /**
     * Execute command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
        } catch (\Exception $e) {
            // Area code already set
        }

        $orderId = $input->getArgument('order_id');

        // Try to get order by ID first
        try {
            $order = $this->orderRepository->get((int)$orderId);
        } catch (\Exception $e) {
            // Try by increment ID
            $order = $this->orderFactory->create()->loadByIncrementId($orderId);

            if (!$order->getId()) {
                $output->writeln("<error>Order not found with ID or increment ID: {$orderId}</error>");
                return Command::FAILURE;
            }
        }

        $output->writeln("<info>Order found:</info>");
        $output->writeln("  Order ID: {$order->getId()}");
        $output->writeln("  Increment ID: {$order->getIncrementId()}");
        $output->writeln("  Shipping Method: {$order->getShippingMethod()}");
        $output->writeln("");

        // Map order
        $output->writeln("<info>Mapping order to Innosend format...</info>");
        $orderData = $this->orderMapper->map($order);

        // Display mapped data
        $output->writeln("<info>=== Mapped Order Data ===</info>");
        $output->writeln(json_encode($orderData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $output->writeln("");

        // Check for pickup point
        if (isset($orderData['pickup_point'])) {
            $output->writeln("<info>✓ Pickup point data found!</info>");
            $output->writeln("  ID: " . ($orderData['pickup_point']['id'] ?? 'N/A'));
            $output->writeln("  Courier: " . ($orderData['pickup_point']['courier'] ?? 'N/A'));
            $output->writeln("  Name: " . ($orderData['pickup_point']['name'] ?? 'N/A'));
            $output->writeln("  Address: " . ($orderData['pickup_point']['address'] ?? 'N/A'));
        } else {
            $output->writeln("<comment>✗ No pickup point data found</comment>");
        }

        // Check for checkout_courier
        if (isset($orderData['checkout_courier'])) {
            $output->writeln("<info>✓ checkout_courier found: {$orderData['checkout_courier']}</info>");
        } else {
            $output->writeln("<comment>✗ checkout_courier not found (expected if no pickup point)</comment>");
        }

        // Summary
        $hasPickupPoint = isset($orderData['pickup_point']);
        $hasCheckoutCourier = isset($orderData['checkout_courier']);

        $output->writeln("");
        $output->writeln("<info>=== Test Summary ===</info>");
        $output->writeln("Pickup Point in mapped data: " . ($hasPickupPoint ? "✓ YES" : "✗ NO"));
        $output->writeln("checkout_courier in mapped data: " . ($hasCheckoutCourier ? "✓ YES" : "✗ NO"));

        if ($hasPickupPoint && $hasCheckoutCourier) {
            $output->writeln("<info>✓✓✓ TEST PASSED: Pickup point data is correctly mapped! ✓✓✓</info>");
            return Command::SUCCESS;
        } elseif ($hasPickupPoint && !$hasCheckoutCourier) {
            $output->writeln("<error>⚠ WARNING: Pickup point found but checkout_courier is missing</error>");
            return Command::FAILURE;
        } else {
            $output->writeln("<comment>ℹ INFO: No pickup point data found (this is OK if order doesn't use pickup points)</comment>");
            return Command::SUCCESS;
        }
    }
}
