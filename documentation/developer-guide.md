# Nextcloud Developer Documentation

## Getting Started with App Development

### Development Environment Setup

#### Initial Setup
```bash
# Navigate to Nextcloud apps directory
cd /var/www/nextcloud/apps

# Or use custom apps-extra directory for development
cd /var/www/nextcloud/apps-extra

# Clone an existing app as reference
git clone https://github.com/nextcloud/cookbook.git
```

#### Configure Multiple App Paths
Add to `config.php`:
```php
'apps_paths' => array (
    0 => array (
        'path' => '/var/www/html/apps',
        'url' => '/apps',
        'writable' => false,
    ),
    1 => array (
        'path' => '/var/www/html/apps-extra',
        'url' => '/apps-extra',
        'writable' => false,
    ),
),
```

#### Set Directory Permissions
```bash
cd /var/www
sudo chown -R www-data:www-data config data apps
sudo chmod o-rw /var/www
```

## App Structure and Configuration

### Basic info.xml Structure
```xml
<?xml version="1.0"?>
<info xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:noNamespaceSchemaLocation="https://apps.nextcloud.com/schema/apps/info.xsd">
    <id>myapp</id>
    <name>My App</name>
    <summary>Short description of the app</summary>
    <description>Detailed description with Markdown support</description>
    <version>1.0.0</version>
    <licence>AGPL-3.0-or-later</licence>
    <author mail="dev@example.com">Developer Name</author>
    
    <namespace>MyApp</namespace>
    
    <category>tools</category>
    
    <bugs>https://github.com/yourname/myapp/issues</bugs>
    <repository>https://github.com/yourname/myapp</repository>
    
    <dependencies>
        <nextcloud min-version="27" max-version="32"/>
        <php min-version="8.1" max-version="8.3"/>
    </dependencies>
</info>
```

### Complete info.xml Example with All Features
```xml
<?xml version="1.0"?>
<info xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:noNamespaceSchemaLocation="https://apps.nextcloud.com/schema/apps/info.xsd">
    <id>news</id>
    <name lang="de">Nachrichten</name>
    <name>News</name>
    <summary lang="en">An RSS/Atom feed reader</summary>
    <description lang="en"># Description\nAn RSS/Atom feed reader</description>
    <description lang="de"><![CDATA[# Beschreibung\nEine Nachrichten App]]></description>
    <version>8.8.2</version>
    <licence>AGPL-3.0-or-later</licence>
    
    <author mail="mail@provider.com" homepage="http://example.com">Bernhard Posselt</author>
    <author>Alessandro Cosentino</author>
    
    <documentation>
        <user>https://github.com/nextcloud/news/wiki#user-documentation</user>
        <admin>https://github.com/nextcloud/news#readme</admin>
        <developer>https://github.com/nextcloud/news/wiki#developer-documentation</developer>
    </documentation>
    
    <category>multimedia</category>
    <category>tools</category>
    
    <website>https://github.com/nextcloud/news</website>
    <discussion>https://your.forum.com</discussion>
    <bugs>https://github.com/nextcloud/news/issues</bugs>
    <repository>https://github.com/nextcloud/news</repository>
    
    <screenshot small-thumbnail="https://example.com/1-small.png">https://example.com/1.png</screenshot>
    <screenshot>https://example.com/2.jpg</screenshot>
    
    <dependencies>
        <php min-version="8.1" min-int-size="64"/>
        <database min-version="9.4">pgsql</database>
        <database>sqlite</database>
        <database min-version="5.5">mysql</database>
        <command>grep</command>
        <command>ls</command>
        <lib min-version="2.7.8">libxml</lib>
        <lib>curl</lib>
        <lib>SimpleXML</lib>
        <nextcloud min-version="31" max-version="32"/>
    </dependencies>
    
    <background-jobs>
        <job>OCA\MyApp\BackgroundJob\CleanupJob</job>
    </background-jobs>
    
    <repair-steps>
        <install>
            <step>OCA\MyApp\Migration\InstallStep</step>
        </install>
    </repair-steps>
    
    <commands>
        <command>OCA\MyApp\Command\MyCommand</command>
    </commands>
    
    <settings>
        <admin>OCA\MyApp\Settings\Admin</admin>
        <admin-section>OCA\MyApp\Settings\Section</admin-section>
    </settings>
    
    <activity>
        <settings>
            <setting>OCA\MyApp\Activity\Settings\MySettings</setting>
        </settings>
        <filters>
            <filter>OCA\MyApp\Activity\Filter\MyFilter</filter>
        </filters>
        <providers>
            <provider>OCA\MyApp\Activity\Provider\MyProvider</provider>
        </providers>
    </activity>
</info>
```

## Application Bootstrap

### Basic Application Class
```php
<?php
declare(strict_types=1);

namespace OCA\MyApp\AppInfo;

use OCP\AppFramework\App;

class Application extends App {
    public function __construct() {
        parent::__construct('myapp');
    }
}
```

### Application with Bootstrap Interface (Nextcloud 20+)
```php
<?php
declare(strict_types=1);

namespace OCA\MyApp\AppInfo;

use OCA\MyApp\Listeners\UserDeletedListener;
use OCA\MyApp\Notifications\Notifier;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Notification\IManager;
use OCP\User\Events\BeforeUserDeletedEvent;

class Application extends App implements IBootstrap {

    public function __construct() {
        parent::__construct('myapp');
    }

    public function register(IRegistrationContext $context): void {
        // Register composer autoloader if needed
        include_once __DIR__ . '/../../vendor/autoload.php';

        // Register event listeners
        $context->registerEventListener(
            BeforeUserDeletedEvent::class,
            UserDeletedListener::class
        );
    }

    public function boot(IBootContext $context): void {
        // Query services and register components
        /** @var IManager $manager */
        $manager = $context->getAppContainer()->query(IManager::class);
        $manager->registerNotifierService(Notifier::class);
    }
}
```

## Frontend Development

### Loading JavaScript in Apps
```php
namespace OCA\YourApp\AppInfo;

use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCA\YourApp\Listener\LoadAdditionalListener;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

class Application extends App implements IBootstrap {
    public const APP_ID = 'your_app';

    public function __construct(array $urlParams = []) {
        parent::__construct(self::APP_ID, $urlParams);
    }

    public function register(IRegistrationContext $context): void {
        $context->registerEventListener(
            LoadAdditionalScriptsEvent::class, 
            LoadAdditionalListener::class
        );
    }

    public function boot(IBootContext $context): void {}
}
```

### NPM Configuration for Frontend
```json
{
  "name": "myapp",
  "scripts": {
    "build": "webpack --node-env production --progress --hide-modules --config webpack.prod.js",
    "dev": "webpack --node-env development --progress --config webpack.dev.js",
    "watch": "webpack --node-env development --progress --watch --config webpack.dev.js"
  },
  "devDependencies": {
    "webpack": "^5.65.0",
    "webpack-cli": "^4.9.1"
  }
}
```

## Dependency Management

### Using Composer Bin Plugin
```bash
# Install the plugin
composer require --dev bamarni/composer-bin-plugin

# Install development tools in isolation
composer bin psalm require --dev psalm/phar
composer bin psalm require --dev nextcloud/ocp:dev-master
```

### Configure composer.json
```json
{
    "extra": {
        "bamarni-bin": {
            "bin-links": true,
            "forward-command": true
        }
    },
    "scripts": {
        "post-install-cmd": [
            "[ $COMPOSER_DEV_MODE -eq 0 ] || composer bin all install --ansi"
        ],
        "post-update-cmd": [
            "[ $COMPOSER_DEV_MODE -eq 0 ] || composer bin all update --ansi"
        ]
    }
}
```

## Database and Storage

### Using IAppData for App Storage
```php
<?php
namespace OCA\MyApp\Controller;

use OCP\AppFramework\Controller;
use OCP\Files\IAppData;
use OCP\IRequest;

class MyController extends Controller {
    /** @var IAppData */
    private $appData;

    public function __construct($appName,
                                IRequest $request,
                                IAppData $appData) {
        parent::__construct($appName, $request);
        $this->appData = $appData;
    }
}
```

### Preventing SQL Injection
```php
<?php
// Inside a mapper class extending \OCP\AppFramework\Db\Mapper
$sql = 'SELECT * FROM `users` WHERE `id` = ?';
$params = array(1);
$result = $this->execute($sql, $params);
```

## OCC Commands

### App Management
```bash
# Enable an app
occ app:enable <app-id>

# Disable an app
occ app:disable <app-id>

# Update app to unstable release
sudo -E -u www-data php occ app:update --allow-unstable news

# Install app from store
sudo -E -u www-data php occ app:install twofactor_totp

# Install without enabling
sudo -E -u www-data php occ app:install --keep-disabled twofactor_totp

# Force install regardless of version requirements
sudo -E -u www-data php occ app:install --force twofactor_totp
```

## ExApp Development (External Apps)

### ExApp info.xml Configuration
```xml
<external-app>
    <docker-install>
        <registry>ghcr.io</registry>
        <image>nextcloud/skeleton</image>
        <image-tag>latest</image-tag>
    </docker-install>
</external-app>
```

### ExApp Lifecycle Endpoints
```
1. GET /heartbeat - Health check (response: {"status": "ok"})
2. POST /init - Optional initialization
3. PUT /enabled?enabled=1 - Enable/disable handler
```

### Frontend API Request in ExApp
```javascript
axios.get(generateUrl(`${APP_API_PROXY_URL_PREFIX}/${EX_APP_ID}/some_api_endpoint`))
```

### Manual ExApp Registration
```bash
sudo -E -u www-data php occ app_api:app:register nc_py_api manual_install --json-info \
    "{\"id\":\"nc_py_api\",\"name\":\"nc_py_api\",\"daemon_config_name\":\"manual_install\",
    \"version\":\"1.0.0\",\"secret\":\"12345\",\"port\":$APP_PORT}"
```

## Version Upgrade Guides

### Nextcloud 32 Compatibility
Update test autoloading:
```php
// In tests/bootstrap.php
require_once __DIR__ . '/../tests/autoload.php';
// Remove any calls to \OC::$loader
```

### Nextcloud 30 Compatibility
Update PHP dependency:
```xml
<dependencies>
  <php min-version="8.1" max-version="8.3" />
  <nextcloud min-version="27" max-version="30" />
</dependencies>
```

### Nextcloud 29 Compatibility
```xml
<dependencies>
    <nextcloud min-version="27" max-version="29" />
</dependencies>
```

### Nextcloud 26 Changes
- PHP 7.4 support dropped
- PHP 8.0 or higher required
- PHP 8.2 support added
- Use camelCase for DI parameters: `appName`, `userId`, `webRoot`
- Removed APIs: `OC.addTranslations`, Bootstrap library

## Release Automation

### GitHub Actions for App Release
```yaml
name: Build and publish app release

on:
  release:
      types: [published]

env:
  APP_NAME: myapp

jobs:
  build_and_publish:
    environment: release
    runs-on: ubuntu-latest
    steps:
      - name: Checkout
        uses: actions/checkout@v2

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'

      - name: Build app
        run: make appstore

      - name: Upload to Nextcloud appstore
        uses: R0Wi/nextcloud-appstore-push-action@v1
        with:
          app_name: ${{ env.APP_NAME }}
          appstore_token: ${{ secrets.APPSTORE_TOKEN }}
          download_url: ${{ steps.attach_to_release.outputs.browser_download_url }}
          app_private_key: ${{ secrets.APP_PRIVATE_KEY }}
```

### Signing App Files
```bash
# In Makefile
@if [ -f $(cert_dir)/$(app_name).key ]; then \
  echo "Signing app files…"; \
  php ../../occ integrity:sign-app \
    --privateKey=$(cert_dir)/$(app_name).key\
    --certificate=$(cert_dir)/$(app_name).crt\
    --path=$(appstore_sign_dir)/$(app_name); \
fi
```

## API Development

### Login Flow API
Response after successful app password conversion:
```xml
<?xml version="1.0"?>
<ocs>
    <meta>
        <status>ok</status>
        <statuscode>200</statuscode>
        <message>OK</message>
    </meta>
    <data>
        <apppassword>M1DqHwuZWwjEC3ku7gJsspR7bZXopwf01kj0XGppYVzEkGtbZBRaXlOUxFZdbgJ6</apppassword>
    </data>
</ocs>
```

## Security Best Practices

### Prevent SQL Injection
Always use prepared statements:
```php
$sql = 'SELECT * FROM `users` WHERE `id` = ?';
$params = array($userId);
$result = $this->execute($sql, $params);
```

### Check Keyboard Shortcuts Accessibility
```javascript
if (!OCP.Accessibility.disableKeyboardShortcuts()) {
    // Register keyboard shortcuts
}
```

## Useful Libraries and APIs

### Nextcloud JavaScript Libraries
- **@nextcloud/logger**: Unified logging with app context
- **@nextcloud/router**: URL generation and routing
- **@nextcloud/axios**: Pre-configured HTTP client
- **@nextcloud/dialogs**: User dialogs and notifications

## App Categories
Valid categories for apps:
- customization
- files
- games
- integration
- monitoring
- multimedia
- office
- organization
- security
- social
- tools

## License Options
For apps targeting v31+:
- AGPL-3.0-only
- AGPL-3.0-or-later
- Apache-2.0
- GPL-3.0-only
- GPL-3.0-or-later
- MIT
- MPL-2.0

## Monetization
Add donation links to info.xml:
```xml
<donation title="Donate via PayPal" type="paypal">https://paypal.com/donate</donation>
<donation type="stripe">https://stripe.com/donate</donation>
<donation>https://other.service.com/donate</donation>
```

## Testing and Development

### Clone Test Apps
```bash
cd /var/www/apps
git clone https://github.com/nextcloud/viewer.git
```

### Build Frontend Assets
```bash
# Development build
npm run dev

# Watch mode
npm run watch

# Production build
npm run build

# Build specific module
MODULE=user_status make build-js-production
```

### AppAPI Development Setup
```bash
# Clone AppAPI
git clone https://github.com/nextcloud/app_api.git && cd app_api

# Install and build
npm ci && npm run dev
```

## AI App Examples

### Recognize App
```bash
# Enable app
occ app:enable recognize

# Download models
occ recognize:download-models

# Clear background jobs
occ recognize:clear-background-jobs

# Classify existing files
occ recognize:classify
```

### Context Chat App
```bash
# Enable the app
occ app:enable context_chat
```

## Configuration Tips

### Disable User Update Notifications
```bash
occ config:app:set --type boolean --value="false" updatenotification app_updated.enabled
```

### Filter Apps from Store
```php
// In config.php
'appsallowlist' => ['files', 'calendar', 'contacts'],
```

### Self-Hosted App Store
```php
"appstoreenabled" => true,
"appstoreurl" => "https://my.appstore.instance/v1",
```

## Navigation Structure
```html
<li class="app-navigation-entry">
    <a href="#" class="icon-folder">Entry Name</a>
    <div class="app-navigation-entry-utils">
        <ul>
            <li class="app-navigation-entry-utils-menu-button">
                <button class="icon-edit"></button>
            </li>
            <li class="app-navigation-entry-utils-menu-button">
                <button class="icon-delete"></button>
            </li>
        </ul>
    </div>
</li>
```

## Changelog Management
Create `CHANGELOG.md` in project root following [Keep a Changelog](https://keepachangelog.com/) format.

For user notifications (Nextcloud 29+):
- `CHANGELOG.en.md` - English changelog
- `CHANGELOG.de.md` - German changelog
- etc.

## Deprecated Features

### Nextcloud 21
- `occ app:check-code` is now a NOOP - use static analysis tools instead

### Nextcloud 16
Deprecated JavaScript libraries:
- marked
- Clipboard (use ClipboardJS)
- escapeHTML
- formatDate
- getURLParameter
- humanFileSize
- relative_modified_date
- select2