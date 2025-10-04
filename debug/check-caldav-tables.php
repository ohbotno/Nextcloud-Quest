<?php
/**
 * Debug script to check CalDAV tables and data
 * Run this inside the Nextcloud container
 */

// Include Nextcloud bootstrap
require_once '/var/www/html/lib/base.php';

use OCP\Server;
use OCP\IDBConnection;

echo "=== CalDAV Tables Debug Report ===\n\n";

try {
    $db = Server::get(IDBConnection::class);
    
    // Check if calendars table exists
    echo "1. Checking calendars table existence...\n";
    $qb = $db->getQueryBuilder();
    $qb->select('COUNT(*)')
       ->from('calendars');
    
    $result = $qb->execute();
    $count = $result->fetchOne();
    $result->closeCursor();
    
    echo "   ✓ Calendars table exists with {$count} records\n\n";
    
    // Check calendars table structure
    echo "2. Calendars table structure:\n";
    $result = $db->executeQuery("DESCRIBE oc_calendars");
    while ($row = $result->fetch()) {
        echo "   - {$row['Field']}: {$row['Type']}\n";
    }
    echo "\n";
    
    // Check for VTODO calendars
    echo "3. Checking for VTODO-enabled calendars...\n";
    $qb = $db->getQueryBuilder();
    $qb->select('id', 'displayname', 'principaluri', 'components')
       ->from('calendars')
       ->where($qb->expr()->like('components', $qb->createNamedParameter('%VTODO%')));
    
    $result = $qb->execute();
    $vtodoCalendars = $result->fetchAll();
    $result->closeCursor();
    
    echo "   Found " . count($vtodoCalendars) . " VTODO-enabled calendars:\n";
    foreach ($vtodoCalendars as $calendar) {
        echo "   - ID: {$calendar['id']}, Name: {$calendar['displayname']}\n";
        echo "     Principal: {$calendar['principaluri']}\n";
        echo "     Components: {$calendar['components']}\n\n";
    }
    
    // Check calendarobjects table
    echo "4. Checking calendarobjects table...\n";
    $qb = $db->getQueryBuilder();
    $qb->select('COUNT(*)')
       ->from('calendarobjects')
       ->where($qb->expr()->like('calendardata', $qb->createNamedParameter('%VTODO%')));
    
    $result = $qb->execute();
    $vtodoCount = $result->fetchOne();
    $result->closeCursor();
    
    echo "   ✓ Found {$vtodoCount} VTODO objects in calendarobjects table\n\n";
    
    // Show sample VTODO data
    if ($vtodoCount > 0) {
        echo "5. Sample VTODO data:\n";
        $qb = $db->getQueryBuilder();
        $qb->select('id', 'calendarid', 'calendardata')
           ->from('calendarobjects')
           ->where($qb->expr()->like('calendardata', $qb->createNamedParameter('%VTODO%')))
           ->setMaxResults(2);
        
        $result = $qb->execute();
        while ($row = $result->fetch()) {
            echo "   Object ID: {$row['id']}, Calendar ID: {$row['calendarid']}\n";
            echo "   CalDAV Data (first 200 chars):\n";
            echo "   " . substr($row['calendardata'], 0, 200) . "...\n\n";
        }
        $result->closeCursor();
    }
    
    // Check for specific user data
    echo "6. Checking for user principals...\n";
    $qb = $db->getQueryBuilder();
    $qb->selectDistinct('principaluri')
       ->from('calendars');
    
    $result = $qb->execute();
    $principals = $result->fetchAll();
    $result->closeCursor();
    
    echo "   Found " . count($principals) . " unique principals:\n";
    foreach ($principals as $principal) {
        echo "   - {$principal['principaluri']}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== End Debug Report ===\n";
?>