# Feed extension for Magento 2

Automizely Feed extension for Magento 2. Allows connect with TikTok Shopping Feed and more.

## Prerequisites

Magento 2

### Install

  -  log into the Magento 2 server and cd into the root directory of the Magento app:
    -  Execute the following commands:
      - composer require aftership/feed-apps-magento2
      - php bin/magento module:enable AfterShip_Automizely_Feed --clear-static-content
      - php bin/magento setup:upgrade
      - php bin/magento setup:static-content:deploy -f

### Setup
  - From admin:
    - Go to stores > configuration
    - Find "TikTok Shopping Feed" in sidebar
    - Open Connect
    - Click the "Connect Now", Navigate to "TikTok Shopping Feed" and connect store
