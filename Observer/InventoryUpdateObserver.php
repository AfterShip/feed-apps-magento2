<?php

namespace AfterShip\Feed\Observer;

use AfterShip\Feed\Helper\WebhookHelper;
use Magento\Authorization\Model\UserContextInterface;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\Bundle\Model\Product\Type as Bundle;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Integration\Api\IntegrationServiceInterface;
use Psr\Log\LoggerInterface;

class InventoryUpdateObserver implements ObserverInterface
{
	/**
	 * @var ProductRepositoryInterface
	 */
	private $productRepository;
	/**
	 * @var Configurable
	 */
	private $configurable;

	public function __construct(
		ProductRepositoryInterface  $productRepository,
		UserContextInterface        $userContext,
		IntegrationServiceInterface $integrationService,
		LoggerInterface             $logger,
		WebhookHelper               $webhookHelper,
		Configurable                $configurable,
		Grouped                     $grouped,
		Bundle                      $bundle
	)
	{
		$this->userContext = $userContext;
		$this->integrationService = $integrationService;
		$this->productRepository = $productRepository;
		$this->configurable = $configurable;
		$this->grouped = $grouped;
		$this->bundle = $bundle;
		$this->webhookHelper = $webhookHelper;
		$this->logger = $logger;
	}

	/**
	 * @param string $productId
	 * @return array
	 */
	public function getParentProductIds($productId)
	{
		$configurableParentIds = $this->configurable->getParentIdsByChild($productId);
		$groupedParentIds = $this->grouped->getParentIdsByChild($productId);
		$bundleParentIds = $this->bundle->getParentIdsByChild($productId);
		return array_merge($configurableParentIds, $groupedParentIds, $bundleParentIds);
	}

	/**
	 * @return bool
	 */
	private function isRestfulApiRequest()
	{
		$userType = $this->userContext->getUserType();
		return ($userType === UserContextInterface::USER_TYPE_INTEGRATION);
	}

	public function execute(Observer $observer)
	{
		try {
			if ($this->isRestfulApiRequest()) {
				$stockItem = $observer->getItem();
				$productId = $stockItem->getProductId();
				$parentIds = $this->getParentProductIds($productId);
				foreach ($parentIds as $parentId) {
					$parentProduct = $this->productRepository->getById($parentId);
					$this->webhookHelper->makeWebhookRequest('products/update', [
						"id" => $parentId,
						"type_id" => $parentProduct->getTypeId(),
						"sku" => $parentProduct->getSku(),
					]);
				}
			}
		} catch (\Exception $e) {
			$this->logger->error(sprintf('[AfterShip Feed] Faield to update product data on InventoryUpdateObserver, %s', $e->getMessage()));
		}
	}
}