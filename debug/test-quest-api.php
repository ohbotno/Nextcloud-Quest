<?php
/**
 * Debug script to test Quest API endpoint directly
 * Run this inside the Nextcloud container
 */

// Include Nextcloud bootstrap
require_once '/var/www/html/lib/base.php';

use OCP\Server;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\IDBConnection;
use OCA\NextcloudQuest\Controller\TaskListController;

echo "=== Quest API Debug Test ===\n\n";

try {
    // Get required services
    $db = Server::get(IDBConnection::class);
    $userSession = Server::get(IUserSession::class);
    
    // Check if user is logged in
    $user = $userSession->getUser();
    if (!$user) {
        echo "❌ No user logged in. Testing with admin user...\n";
        
        // Get first available user from database
        $qb = $db->getQueryBuilder();
        $qb->select('uid')
           ->from('users')
           ->setMaxResults(1);
        
        $result = $qb->execute();
        $userId = $result->fetchOne();
        $result->closeCursor();
        
        if (!$userId) {
            throw new Exception("No users found in database");
        }
        
        echo "   Using user ID: {$userId}\n\n";
    } else {
        $userId = $user->getUID();
        echo "✓ Logged in as: {$userId}\n\n";
    }
    
    // Create mock request
    $request = new class implements IRequest {
        public function getHeader(string $name): string { return ''; }
        public function getParam(string $key, $default = null) { return $default; }
        public function getParams(): array { return []; }
        public function getMethod(): string { return 'GET'; }
        public function getUploadedFile(string $key) { return null; }
        public function getEnv(string $key): string { return ''; }
        public function getCookie(string $key) { return null; }
        public function passesCSRFCheck(): bool { return true; }
        public function passesStrictCookieCheck(): bool { return true; }
        public function passesLaxCookieCheck(): bool { return true; }
        public function getId(): string { return 'debug'; }
        public function getRemoteAddress(): string { return '127.0.0.1'; }
        public function getServerProtocol(): string { return 'HTTP/1.1'; }
        public function getInsecureServerHost(): string { return 'localhost'; }
        public function getServerHost(): string { return 'localhost'; }
        public function getRequestUri(): string { return '/debug'; }
        public function getRawPathInfo(): string { return '/debug'; }
        public function getPathInfo(): ?string { return '/debug'; }
        public function getScript(): string { return 'debug.php'; }
        public function isUserAgent(array $agent): bool { return false; }
        public function getScriptName(): string { return 'debug.php'; }
    };
    
    // Create controller instance
    $controller = new TaskListController('quest', $request, $userSession, $db);
    
    // Test the API endpoint
    echo "1. Testing TaskListController->getQuestLists()...\n";
    $response = $controller->getQuestLists();
    
    echo "2. Response details:\n";
    echo "   Status: " . $response->getStatus() . "\n";
    
    $data = $response->getData();
    echo "   Data: " . json_encode($data, JSON_PRETTY_PRINT) . "\n\n";
    
    // Direct database query test
    echo "3. Direct database query test...\n";
    
    // Check if user has calendars
    $qb = $db->getQueryBuilder();
    $qb->select('*')
       ->from('calendars')
       ->where($qb->expr()->eq('principaluri', $qb->createNamedParameter('principals/users/' . $userId, \PDO::PARAM_STR)));
    
    $result = $qb->execute();
    $userCalendars = $result->fetchAll();
    $result->closeCursor();
    
    echo "   User calendars found: " . count($userCalendars) . "\n";
    foreach ($userCalendars as $calendar) {
        echo "   - {$calendar['displayname']} (ID: {$calendar['id']}, Components: {$calendar['components']})\n";
    }
    
    // Check for VTODO calendars specifically
    $qb = $db->getQueryBuilder();
    $qb->select('*')
       ->from('calendars')
       ->where($qb->expr()->eq('principaluri', $qb->createNamedParameter('principals/users/' . $userId, \PDO::PARAM_STR)))
       ->andWhere($qb->expr()->like('components', $qb->createNamedParameter('%VTODO%', \PDO::PARAM_STR)));
    
    $result = $qb->execute();
    $vtodoCalendars = $result->fetchAll();
    $result->closeCursor();
    
    echo "   VTODO calendars found: " . count($vtodoCalendars) . "\n";
    
    if (count($vtodoCalendars) > 0) {
        foreach ($vtodoCalendars as $calendar) {
            // Check for tasks in this calendar
            $qb = $db->getQueryBuilder();
            $qb->select('COUNT(*)')
               ->from('calendarobjects')
               ->where($qb->expr()->eq('calendarid', $qb->createNamedParameter($calendar['id'], \PDO::PARAM_INT)))
               ->andWhere($qb->expr()->like('calendardata', $qb->createNamedParameter('%VTODO%', \PDO::PARAM_STR)));
            
            $result = $qb->execute();
            $taskCount = $result->fetchOne();
            $result->closeCursor();
            
            echo "   - {$calendar['displayname']}: {$taskCount} tasks\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== End API Test ===\n";
?>