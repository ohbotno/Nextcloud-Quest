# Nextcloud App Development Guide

## Getting Started

### Development Environment Setup

```bash
# Navigate to Nextcloud apps directory
cd /var/www/nextcloud/apps

# Or create custom apps directory for development
cd /var/www/nextcloud/apps-extra

# Set proper permissions
sudo chown -R www-data:www-data config data apps
sudo chmod o-rw /var/www
```

### Configure Multiple App Paths
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

## App Structure & Configuration

### Basic info.xml Structure
```xml
<?xml version="1.0"?>
<info xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:noNamespaceSchemaLocation="https://apps.nextcloud.com/schema/apps/info.xsd">
    <id>myapp</id>
    <name>My App</name>
    <summary>Short description</summary>
    <description>Detailed description with Markdown support</description>
    <version>1.0.0</version>
    <licence>AGPL-3.0-or-later</licence>
    <author mail="dev@example.com">Developer Name</author>
    
    <namespace>MyApp</namespace>
    <category>tools</category>
    
    <bugs>https://github.com/user/myapp/issues</bugs>
    <repository>https://github.com/user/myapp</repository>
    
    <dependencies>
        <nextcloud min-version="27" max-version="32"/>
        <php min-version="8.1" max-version="8.3"/>
        <database>mysql</database>
        <database>pgsql</database>
        <database>sqlite</database>
    </dependencies>
</info>
```

### App Bootstrap (Application.php)

Basic Application class:
```php
<?php
declare(strict_types=1);

namespace OCA\MyApp\AppInfo;

use OCP\AppFramework\App;

class Application extends App {
    public function __construct() {
        parent::__construct('myapp');
        // Connect to legacy hooks if needed
        \OCP\Util::connectHook('OC_User', 'pre_deleteUser', 'OCA\MyApp\Hooks\User', 'deleteUser');
    }
}
```

Modern Bootstrap with IBootstrap (Nextcloud 20+):
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

## Controllers

### Basic Controller Structure
```php
<?php
namespace OCA\MyApp\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

class PageController extends Controller {

    public function __construct($appName, IRequest $request) {
        parent::__construct($appName, $request);
    }

    public function index(): TemplateResponse {
        $templateName = 'main';  // uses templates/main.php
        $parameters = array('key' => 'value');
        return new TemplateResponse($this->appName, $templateName, $parameters);
    }
}
```

### Security Attributes (Nextcloud 27+)
```php
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\Attribute\UseSession;

class PageController extends Controller {

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function publicApi(): JSONResponse {
        return new JSONResponse(['status' => 'ok']);
    }

    #[PublicPage]
    public function publicPage(): TemplateResponse {
        return new TemplateResponse($this->appName, 'public');
    }

    #[UseSession]
    public function sessionMethod(): Response {
        $this->session['key'] = 'value';
        return new JSONResponse(['stored' => true]);
    }
}
```

### Request Parameter Handling
```php
public function create(string $name, int $number = 0, array $data = []): JSONResponse {
    // Parameters automatically mapped from:
    // - URL parameters
    // - POST form data
    // - JSON request body
    return new JSONResponse(['name' => $name, 'number' => $number]);
}
```

### Different Response Types
```php
// JSON Response
public function getJson(): JSONResponse {
    return new JSONResponse(['data' => 'value']);
}

// Return array (auto-converted to JSON)
public function getArray(): array {
    return ['data' => 'value'];
}

// Template Response
public function getHtml(): TemplateResponse {
    return new TemplateResponse($this->appName, 'template', ['param' => 'value']);
}

// Data Response (for OCS API)
public function getOCS(): DataResponse {
    return new DataResponse(['data' => 'value']);
}

// File Download
public function downloadFile(): DownloadResponse {
    return new DownloadResponse('/path/to/file.xml', 'application/xml');
}

// Redirect
public function redirect(): RedirectResponse {
    return new RedirectResponse('https://nextcloud.com');
}
```

### Error Handling
```php
public function riskyOperation(): DataResponse {
    try {
        $result = $this->service->doSomething();
        return new DataResponse($result);
    } catch (NotFoundException $e) {
        return new DataResponse(['error' => 'Not found'], Http::STATUS_NOT_FOUND);
    } catch (\Exception $e) {
        return new DataResponse(['error' => $e->getMessage()], Http::STATUS_INTERNAL_SERVER_ERROR);
    }
}
```

## Database Layer

### Entity Definition
```php
<?php
namespace OCA\MyApp\Db;

use OCP\AppFramework\Db\Entity;
use OCP\DB\Types;

class Author extends Entity {
    protected $name;
    protected $email;
    protected $phoneNumber;  // Maps to phone_number column
    protected $stars;
    protected $createdAt;    // Non-database field (transient)

    public function __construct() {
        // Define data types
        $this->addType('stars', Types::INTEGER);
        $this->addType('email', Types::STRING);
        $this->addType('phoneNumber', Types::STRING);
    }

    // Transient attributes need explicit getters/setters
    public function getCreatedAt(): ?int {
        return $this->createdAt;
    }

    public function setCreatedAt(int $createdAt): void {
        $this->createdAt = $createdAt;
    }
}
```

### Mapper (Data Access Layer)
```php
<?php
namespace OCA\MyApp\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class AuthorMapper extends QBMapper {

    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'myapp_authors', Author::class);
    }

    /**
     * @throws DoesNotExistException
     * @throws MultipleObjectsReturnedException
     */
    public function find(int $id): Author {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
           ->from('myapp_authors')
           ->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));

        return $this->findEntity($qb);
    }

    public function findAll(?int $limit = null, ?int $offset = null): array {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
           ->from('myapp_authors')
           ->setMaxResults($limit)
           ->setFirstResult($offset);

        return $this->findEntities($qb);
    }

    public function countByName(string $name): int {
        $qb = $this->db->getQueryBuilder();
        $qb->select('COUNT(*) AS count')
           ->from('myapp_authors')
           ->where($qb->expr()->eq('name', $qb->createNamedParameter($name, IQueryBuilder::PARAM_STR)));

        $result = $qb->executeQuery();
        $row = $result->fetchAssociative();
        $result->closeCursor(); // Important for SQLite

        return (int)$row['count'];
    }
}
```

### Database Migrations
```php
<?php
namespace OCA\MyApp\Migration;

use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1000Date20230101120000 extends SimpleMigrationStep {

    public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options) {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        // Create new table
        if (!$schema->hasTable('myapp_authors')) {
            $table = $schema->createTable('myapp_authors');
            
            $table->addColumn('id', Types::BIGINT, [
                'autoincrement' => true,
                'notnull' => true,
                'length' => 20,
                'unsigned' => true,
            ]);
            
            $table->addColumn('name', Types::STRING, [
                'notnull' => true,
                'length' => 200,
            ]);
            
            $table->addColumn('email', Types::STRING, [
                'notnull' => false,
                'length' => 320,
            ]);
            
            $table->addColumn('phone_number', Types::STRING, [
                'notnull' => false,
                'length' => 50,
            ]);
            
            $table->addColumn('stars', Types::INTEGER, [
                'notnull' => true,
                'default' => 0,
            ]);

            $table->setPrimaryKey(['id']);
            $table->addIndex(['name'], 'myapp_authors_name');
        }

        return $schema;
    }
}
```

### Direct Database Queries (when needed)
```php
<?php
namespace OCA\MyApp\Db;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class AuthorDAO {
    private IDBConnection $db;

    public function __construct(IDBConnection $db) {
        $this->db = $db;
    }

    public function find(int $id): ?array {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
           ->from('myapp_authors')
           ->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));

        $result = $qb->executeQuery();
        $row = $result->fetchAssociative();
        $result->closeCursor();

        return $row ?: null;
    }

    public function transaction(): void {
        $this->db->beginTransaction();

        try {
            // DB operations
            $qb = $this->db->getQueryBuilder();
            $qb->insert('myapp_authors')
               ->values(['name' => $qb->createNamedParameter('Test')]);
            $qb->executeStatement();

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
```

## Services Layer

### Business Logic Service
```php
<?php
namespace OCA\MyApp\Service;

use OCA\MyApp\Db\Author;
use OCA\MyApp\Db\AuthorMapper;
use OCP\AppFramework\Db\DoesNotExistException;

class AuthorService {
    private AuthorMapper $mapper;

    public function __construct(AuthorMapper $mapper) {
        $this->mapper = $mapper;
    }

    public function findAll(): array {
        return $this->mapper->findAll();
    }

    public function find(int $id): Author {
        try {
            return $this->mapper->find($id);
        } catch (DoesNotExistException $e) {
            throw new NotFoundException('Author not found');
        }
    }

    public function create(string $name, string $email): Author {
        $author = new Author();
        $author->setName($name);
        $author->setEmail($email);
        $author->setStars(0);
        
        return $this->mapper->insert($author);
    }

    public function update(int $id, string $name, string $email): Author {
        $author = $this->find($id);
        $author->setName($name);
        $author->setEmail($email);
        
        return $this->mapper->update($author);
    }

    public function delete(int $id): void {
        $author = $this->find($id);
        $this->mapper->delete($author);
    }
}
```

## Configuration Management

### App Configuration
```php
<?php
namespace OCA\MyApp\Service;

use OCP\IConfig;

class ConfigService {
    private IConfig $config;
    private string $appName;

    public function __construct(IConfig $config, string $appName) {
        $this->config = $config;
        $this->appName = $appName;
    }

    // App-wide settings
    public function getAppValue(string $key, string $default = ''): string {
        return $this->config->getAppValue($this->appName, $key, $default);
    }

    public function setAppValue(string $key, string $value): void {
        $this->config->setAppValue($this->appName, $key, $value);
    }

    // User-specific settings
    public function getUserValue(string $userId, string $key, string $default = ''): string {
        return $this->config->getUserValue($userId, $this->appName, $key, $default);
    }

    public function setUserValue(string $userId, string $key, string $value): void {
        $this->config->setUserValue($userId, $this->appName, $key, $value);
    }
}
```

### Advanced Configuration (Nextcloud 25+)
```php
// Store sensitive values (encrypted)
$config->setValueString('myapp', 'api_key', 'secret_value', sensitive: true);
$apiKey = $config->getValueString('myapp', 'api_key', '', sensitive: true);

// Store lazy-loaded values (loaded only when accessed)
$config->setValueString('myapp', 'large_data', $largeValue, lazy: true);
$data = $config->getValueString('myapp', 'large_data', '', lazy: true);
```

## Storage & Files

### App Data Storage
```php
<?php
namespace OCA\MyApp\Controller;

use OCP\AppFramework\Controller;
use OCP\Files\IAppData;
use OCP\IRequest;

class FileController extends Controller {
    private IAppData $appData;

    public function __construct($appName, IRequest $request, IAppData $appData) {
        parent::__construct($appName, $request);
        $this->appData = $appData;
    }

    public function storeFile(): JSONResponse {
        try {
            // Get or create folder
            try {
                $folder = $this->appData->getFolder('uploads');
            } catch (NotFoundException $e) {
                $folder = $this->appData->newFolder('uploads');
            }

            // Create file
            $file = $folder->newFile('example.txt');
            $file->putContent('Hello World');

            return new JSONResponse([
                'success' => true,
                'file' => $file->getName(),
                'size' => $file->getSize()
            ]);

        } catch (\Exception $e) {
            return new JSONResponse(['error' => $e->getMessage()], 500);
        }
    }

    public function listFiles(): JSONResponse {
        try {
            $folder = $this->appData->getFolder('uploads');
            $files = [];

            foreach ($folder->getDirectoryListing() as $file) {
                $files[] = [
                    'name' => $file->getName(),
                    'size' => $file->getSize(),
                    'mtime' => $file->getMTime()
                ];
            }

            return new JSONResponse($files);
        } catch (NotFoundException $e) {
            return new JSONResponse([]);
        }
    }
}
```

## Dependency Injection

### Registering Services
```php
<?php
namespace OCA\MyApp\AppInfo;

use OCA\MyApp\Controller\AuthorController;
use OCA\MyApp\Service\AuthorService;
use OCA\MyApp\Db\AuthorMapper;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\IDBConnection;
use Psr\Container\ContainerInterface;

class Application extends App implements IBootstrap {

    public function register(IRegistrationContext $context): void {
        // Controllers
        $context->registerService(AuthorController::class, function(ContainerInterface $c) {
            return new AuthorController(
                $c->get('appName'),
                $c->get(IRequest::class),
                $c->get(AuthorService::class)
            );
        });

        // Services
        $context->registerService(AuthorService::class, function(ContainerInterface $c) {
            return new AuthorService($c->get(AuthorMapper::class));
        });

        // Mappers
        $context->registerService(AuthorMapper::class, function(ContainerInterface $c) {
            return new AuthorMapper($c->get(IDBConnection::class));
        });
    }
}
```

### Auto-wired Dependencies (Simplified)
```php
// Controllers with auto-wired dependencies
class PageController extends Controller {
    public function __construct($appName, IRequest $request, AuthorService $service) {
        parent::__construct($appName, $request);
        $this->service = $service;
    }
}

// Method-level dependency injection
public function createAuthor(AuthorService $service): JSONResponse {
    $author = $service->create('John Doe', 'john@example.com');
    return new JSONResponse($author);
}
```

## Security Best Practices

### SQL Injection Prevention
```php
// GOOD: Using Query Builder with parameters
$qb = $this->db->getQueryBuilder();
$qb->select('*')
   ->from('users')
   ->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));

// GOOD: Using Mapper execute method
$sql = 'SELECT * FROM `users` WHERE `id` = ?';
$params = array($userId);
$result = $this->execute($sql, $params);

// BAD: Direct string concatenation (vulnerable to SQL injection)
// $sql = "SELECT * FROM users WHERE id = " . $id;
```

### CSRF Protection
```php
// CSRF protection is enabled by default
// Disable only when necessary and safe
#[NoCSRFRequired]
public function publicApi(): JSONResponse {
    return new JSONResponse(['data' => 'public']);
}

// Manual CSRF check (if not using App Framework)
\OCP\JSON::callCheck();
```

### Input Validation
```php
public function createUser(string $name, string $email): JSONResponse {
    // Validate input
    if (empty($name)) {
        return new JSONResponse(['error' => 'Name required'], 400);
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return new JSONResponse(['error' => 'Invalid email'], 400);
    }
    
    // Process validated input
    $user = $this->userService->create($name, $email);
    return new JSONResponse($user);
}
```

### Safe Redirects
```php
// GOOD: Fixed base URL
public function redirect(): RedirectResponse {
    return new RedirectResponse('https://nextcloud.com' . $_GET['path']);
}

// BAD: Arbitrary redirects
// return new RedirectResponse($_GET['url']); // Vulnerable to redirect attacks
```

## Data Types Reference

### OCP\DB\Types
- `INTEGER` - Integer values
- `FLOAT` - Floating-point values  
- `BOOLEAN` - Boolean values
- `STRING` - Text and strings
- `BLOB` - Binary data
- `JSON` - JSON data (auto-decoded)

### Date/Time Types
- `DATE_IMMUTABLE` - Date only (\DateTimeImmutable)
- `TIME_IMMUTABLE` - Time only (\DateTimeImmutable)  
- `DATETIME_IMMUTABLE` - Date and time (\DateTimeImmutable)
- `DATETIME_TZ_IMMUTABLE` - Date/time with timezone (\DateTimeImmutable)

## Routing

### Define Routes
```php
<?php
// appinfo/routes.php
return [
    'routes' => [
        ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
        ['name' => 'author_api#index', 'url' => '/api/authors', 'verb' => 'GET'],
        ['name' => 'author_api#create', 'url' => '/api/authors', 'verb' => 'POST'],
        ['name' => 'author_api#show', 'url' => '/api/authors/{id}', 'verb' => 'GET'],
        ['name' => 'author_api#update', 'url' => '/api/authors/{id}', 'verb' => 'PUT'],
        ['name' => 'author_api#delete', 'url' => '/api/authors/{id}', 'verb' => 'DELETE'],
    ],
    'ocs' => [
        ['name' => 'api#getShares', 'url' => '/api/v1/shares', 'verb' => 'GET'],
    ],
];
```

## OCC Commands

### App Management
```bash
# Enable app
occ app:enable myapp

# Disable app  
occ app:disable myapp

# Enable for specific groups
occ app:enable --groups admin --groups sales myapp

# Run app migrations
occ app:upgrade
```

### Database Operations
```bash
# Generate migration from old database.xml
occ migrations:generate-from-schema

# Convert database type
sudo -E -u www-data php occ db:convert-type mysql username hostname database
```

## Templates

### Template Usage
```php
// In controller
return new TemplateResponse($this->appName, 'main', ['user' => $user]);
```

```php
<!-- templates/main.php -->
<div>
    <h1>Welcome <?php p($_['user']); ?>!</h1>
    <p>App content goes here</p>
</div>
```

### Template Security
```php
// GOOD: Always escape output
<?php p($user['name']); ?>

// GOOD: For HTML content (when safe)
<?php print_unescaped($safeHtml); ?>

// BAD: Direct output (vulnerable to XSS)
<?php echo $user['name']; ?>
```

## Testing

### Unit Testing Setup
```php
<?php
// tests/bootstrap.php
require_once __DIR__ . '/../tests/autoload.php';

// Remove any calls to \OC::$loader (deprecated)
```

### Example Test
```php
<?php
namespace OCA\MyApp\Tests\Unit\Service;

use OCA\MyApp\Service\AuthorService;
use OCA\MyApp\Db\AuthorMapper;
use PHPUnit\Framework\TestCase;

class AuthorServiceTest extends TestCase {
    
    public function testCreate() {
        $mapper = $this->createMock(AuthorMapper::class);
        $service = new AuthorService($mapper);
        
        $mapper->expects($this->once())
               ->method('insert')
               ->willReturn(new Author());
        
        $result = $service->create('Test Author', 'test@example.com');
        $this->assertInstanceOf(Author::class, $result);
    }
}
```

## Common Patterns

### Error Handling Service
```php
trait ErrorHandling {
    protected function handleServiceError(\Exception $e): DataResponse {
        if ($e instanceof NotFoundException) {
            return new DataResponse(['error' => 'Not found'], 404);
        }
        
        if ($e instanceof ValidationException) {
            return new DataResponse(['error' => $e->getMessage()], 400);
        }
        
        // Log unexpected errors
        \OCP\Util::writeLog('myapp', $e->getMessage(), \OCP\Util::ERROR);
        
        return new DataResponse(['error' => 'Internal error'], 500);
    }
}
```

### Pagination Helper  
```php
class PaginationHelper {
    public static function paginate(array $data, int $page = 1, int $limit = 10): array {
        $offset = ($page - 1) * $limit;
        $total = count($data);
        
        return [
            'data' => array_slice($data, $offset, $limit),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ];
    }
}
```

## Debugging Tips

### Logging
```php
// Use Nextcloud's logging
\OCP\Util::writeLog('myapp', 'Debug message', \OCP\Util::DEBUG);
\OCP\Util::writeLog('myapp', 'Error: ' . $e->getMessage(), \OCP\Util::ERROR);

// PSR-3 logger (recommended)
$logger = \OC::$server->getLogger();
$logger->debug('Debug message', ['app' => 'myapp']);
$logger->error('Error occurred', ['exception' => $e, 'app' => 'myapp']);
```

### Development Mode
Add to `config.php`:
```php
'debug' => true,
'loglevel' => 0, // Debug level
```

This comprehensive guide covers the essential aspects of Nextcloud app development, from basic setup to advanced patterns. Use it as a reference while building your apps!