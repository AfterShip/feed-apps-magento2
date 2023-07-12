<?php

namespace AfterShip\Feed\Observer;

use AfterShip\Feed\Constants;
use AfterShip\Feed\Helper\WebhookHelper;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;

class SalesOrderUpdateObserver implements ObserverInterface
{
	/** @var LoggerInterface */
	protected $logger;
	/** @var WebhookHelper */
	protected $webhookHelper;

	public function __construct(
		LoggerInterface $logger,
		WebhookHelper   $webhookHelper
	)
	{
		$this->logger = $logger;
		$this->webhookHelper = $webhookHelper;
	}

	public function execute(Observer $observer)
	{
		try {
			/** @var Order $order */
			$order = $observer->getEvent()->getOrder();
			$orderId = $order->getId();
			$orderStatus = $order->getStatus();
			$this->webhookHelper->makeWebhookRequest(Constants::WEBHOOK_TOPIC_ORDERS_UPDATE, [
				'id' => $orderId,
				'status' => $orderStatus,
			]);
		} catch (\Exception $e) {
			$this->logger->error(sprintf('[AfterShip Feed] Faield to send order webhook on OrderUpdateObserver, %s', $e->getMessage()));
		}
	}
}
