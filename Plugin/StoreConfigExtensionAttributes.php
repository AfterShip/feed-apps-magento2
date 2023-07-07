<?php

namespace AfterShip\Feed\Plugin;

use Magento\Integration\Api\IntegrationServiceInterface;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Store\Api\Data\StoreConfigInterface;
use Magento\Store\Api\StoreConfigManagerInterface;
use Magento\Store\Api\Data\StoreConfigExtensionFactory;
use AfterShip\Feed\Constants;

class StoreConfigExtensionAttributes
{
	/** @var UserContextInterface  */
    private $userContext;
	/** @var IntegrationServiceInterface  */
    private $integrationService;
	/** @var StoreConfigExtensionFactory  */
    private $storeConfigExtensionFactory;

    public function __construct(
        StoreConfigExtensionFactory $storeConfigExtensionFactory,
        UserContextInterface        $userContext,
        IntegrationServiceInterface $integrationService
    )
    {

        $this->storeConfigExtensionFactory = $storeConfigExtensionFactory;
        $this->userContext = $userContext;
        $this->integrationService = $integrationService;
    }

	/**
	 * @return string
	 */
    private function getApiScopes()
    {
        $integrationId = $this->userContext->getUserId();
        $apiScopes = '';
        if ($integrationId) {
            $scopes = $this->integrationService->getSelectedResources($integrationId);
            $apiScopes = is_array($scopes) ? implode(',', $scopes) : $scopes;
        }
        return $apiScopes;
    }

	/**
	 * @return string
	 */
	private function getFeedVersion()
	{
		return Constants::AFTERSHIP_FEED_VERSION;
	}

	/**
	 * @param StoreConfigManagerInterface $subject
	 * @param $result
	 * @return mixed
	 */
    public function afterGetStoreConfigs(StoreConfigManagerInterface $subject, $result)
    {
        /** @var StoreConfigInterface $store */
        foreach ($result as $store) {
            $extensionAttributes = $store->getExtensionAttributes();
            if (!$extensionAttributes) {
                $extensionAttributes = $this->storeConfigExtensionFactory->create();
            }
            // setPermissions method is generated by extension_attributes.xml.
            if (method_exists($extensionAttributes, 'setPermissions')) {
                call_user_func_array(array($extensionAttributes, 'setPermissions'), array($this->getApiScopes()));
            }
			if (method_exists($extensionAttributes, 'setFeedVersion')) {
				call_user_func_array(array($extensionAttributes, 'setFeedVersion'), array($this->getFeedVersion()));
			}
            // Pass Upgrade compatibility tool check.
            if (method_exists($extensionAttributes, 'setData')) {
                call_user_func_array(array($extensionAttributes, 'setData'), array('permissions', $this->getApiScopes()));
                call_user_func_array(array($extensionAttributes, 'setData'), array('feed_version', $this->getFeedVersion()));
            }
            $store->setExtensionAttributes($extensionAttributes);
        }
        return $result;
    }
}
