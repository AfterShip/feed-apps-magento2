<?php

namespace AfterShip\Feed\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use AfterShip\Feed\Helper\WebhookHelper;
use Psr\Log\LoggerInterface;

class ProductSaveObserver implements ObserverInterface
{
	/** @var ProductRepositoryInterface  */
	private $productRepository;
	/** @var Configurable  */
	private $configurable;
	/** @var WebhookHelper  */
	private $webhookHelper;
	/** @var LoggerInterface  */
	private $logger;

	public function __construct(
		ProductRepositoryInterface $productRepository,
		Configurable $configurable,
		WebhookHelper $webhookHelper,
		LoggerInterface $logger
	) {
		$this->productRepository = $productRepository;
		$this->configurable = $configurable;
		$this->logger = $logger;
		$this->webhookHelper = $webhookHelper;
	}

	/**
	 * @param Observer $observer
	 * @return void
	 */
	public function execute(Observer $observer)
	{
		try {
			/* @var \Magento\Catalog\Model\Product $product */
			$product = $observer->getEvent()->getProduct();
			$productId = $product->getId();
			$parentIds = $this->configurable->getParentIdsByChild($productId);
			$topic = (count($parentIds) === 0) ? "products/update" : "variants/update";
			// Send webhook.
			$this->webhookHelper->makeWebhookRequest($topic, [
				"id" => $productId,
				"type_id" => $product->getTypeId(),
				"sku" => $product->getSku(),
			]);
			// Fix updated time for parent product.
			foreach ($parentIds as $parentId) {
				$parentProduct = $this->productRepository->getById($parentId);
				$updatedAt = $parentProduct->getUpdatedAt();
				$timeDifference = time() - ($updatedAt ? strtotime($updatedAt) : 0);
				$parentProduct->setData('updated_at', date('Y-m-d H:i:s'));
				$this->productRepository->save($parentProduct);
			}
		} catch (\Exception $e) {
			$this->logger->error(sprintf('[AfterShip Feed] Faield to update product data, %s', $e->getMessage()));
		}
	}
}