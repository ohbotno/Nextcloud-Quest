<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

namespace OCA\NextcloudQuest\Service;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * Adventure Map Service
 * Handles procedural map generation, node connectivity, and area progression
 */
class AdventureMapService {
    private IDBConnection $db;
    private IUserSession $userSession;
    private LoggerInterface $logger;

    private const GRID_SIZE = 7;
    private const TOTAL_NODES = 49; // 7x7
    private const NODE_TYPES = ['COMBAT', 'SHOP', 'TREASURE', 'EVENT', 'BOSS'];

    public function __construct(
        IDBConnection $db,
        IUserSession $userSession,
        LoggerInterface $logger
    ) {
        $this->db = $db;
        $this->userSession = $userSession;
        $this->logger = $logger;
    }

    /**
     * Generate a new adventure area for the user
     */
    public function generateNewArea(string $userId, string $ageKey): array {
        $this->logger->info("Generating new adventure area for user $userId with age theme $ageKey");

        // Get next area number
        $areaNumber = $this->getNextAreaNumber($userId);

        // Create area record
        $areaId = $this->createAreaRecord($userId, $areaNumber, $ageKey);

        // Generate procedural map
        $nodes = $this->generateMapNodes($areaId, $ageKey);

        // Save nodes to database
        $this->saveMapNodes($userId, $areaId, $nodes);

        // Initialize or update progress
        $this->initializeProgress($userId, $areaId);

        return [
            'area_id' => $areaId,
            'area_number' => $areaNumber,
            'age_key' => $ageKey,
            'nodes' => $nodes,
        ];
    }

    /**
     * Generate procedural node graph with guaranteed path to boss
     */
    private function generateMapNodes(int $areaId, string $ageKey): array {
        $nodes = [];

        // Step 1: Define special nodes
        $startNode = 'node_0_3'; // Middle left
        $bossNode = 'node_6_3'; // Middle right
        $shopNode = 'node_3_0'; // Top center

        // Initialize only nodes we'll actually use
        $nodes[$startNode] = [
            'node_id' => $startNode,
            'grid_x' => 0,
            'grid_y' => 3,
            'node_type' => 'START',
            'connections' => [],
            'is_unlocked' => 1,
            'is_completed' => 0,
        ];

        $nodes[$bossNode] = [
            'node_id' => $bossNode,
            'grid_x' => 6,
            'grid_y' => 3,
            'node_type' => 'BOSS',
            'connections' => [],
            'is_unlocked' => 0,
            'is_completed' => 0,
        ];

        $nodes[$shopNode] = [
            'node_id' => $shopNode,
            'grid_x' => 3,
            'grid_y' => 0,
            'node_type' => 'SHOP',
            'connections' => [],
            'is_unlocked' => 0,
            'is_completed' => 0,
        ];

        // Step 2: Generate guaranteed path from START to BOSS
        $mainPath = $this->generateMainPath($startNode, $bossNode);

        // Create nodes along main path
        foreach ($mainPath as $nodeId) {
            if (!isset($nodes[$nodeId])) {
                list($x, $y) = $this->parseNodeId($nodeId);
                $nodes[$nodeId] = [
                    'node_id' => $nodeId,
                    'grid_x' => $x,
                    'grid_y' => $y,
                    'node_type' => 'COMBAT',
                    'connections' => [],
                    'is_unlocked' => 0,
                    'is_completed' => 0,
                ];
            }
        }

        // Step 3: Add connections along main path
        for ($i = 0; $i < count($mainPath) - 1; $i++) {
            $currentNode = $mainPath[$i];
            $nextNode = $mainPath[$i + 1];

            $nodes[$currentNode]['connections'][] = $nextNode;
            $nodes[$nextNode]['connections'][] = $currentNode; // Bidirectional
        }

        // Step 4: Add branch paths with new nodes (only connected nodes)
        $this->addBranchPaths($nodes, $mainPath, $shopNode);

        // Step 5: Assign random node types to non-special nodes
        $this->assignNodeTypes($nodes, [$startNode, $bossNode, $shopNode]);

        return array_values($nodes);
    }

    /**
     * Generate main path from start to boss using A* pathfinding
     */
    private function generateMainPath(string $startNode, string $bossNode): array {
        list($startX, $startY) = $this->parseNodeId($startNode);
        list($endX, $endY) = $this->parseNodeId($bossNode);

        $path = [$startNode];
        $currentX = $startX;
        $currentY = $startY;

        // Simple pathfinding: move toward boss with some randomness
        while ($currentX != $endX || $currentY != $endY) {
            $moves = [];

            // Prioritize horizontal movement
            if ($currentX < $endX) {
                $moves[] = ['x' => $currentX + 1, 'y' => $currentY, 'weight' => 3];
            }
            if ($currentX > $endX) {
                $moves[] = ['x' => $currentX - 1, 'y' => $currentY, 'weight' => 3];
            }

            // Add vertical movement
            if ($currentY < $endY) {
                $moves[] = ['x' => $currentX, 'y' => $currentY + 1, 'weight' => 2];
            }
            if ($currentY > $endY) {
                $moves[] = ['x' => $currentX, 'y' => $currentY - 1, 'weight' => 2];
            }

            // Add some random adjacent moves for variety (lower weight)
            $adjacents = [
                ['x' => $currentX + 1, 'y' => $currentY, 'weight' => 1],
                ['x' => $currentX - 1, 'y' => $currentY, 'weight' => 1],
                ['x' => $currentX, 'y' => $currentY + 1, 'weight' => 1],
                ['x' => $currentX, 'y' => $currentY - 1, 'weight' => 1],
            ];

            foreach ($adjacents as $adj) {
                if ($this->isValidPosition($adj['x'], $adj['y']) && !in_array("node_{$adj['x']}_{$adj['y']}", $path)) {
                    $moves[] = $adj;
                }
            }

            // Weighted random selection
            if (empty($moves)) {
                break; // Shouldn't happen, but safety check
            }

            $totalWeight = array_sum(array_column($moves, 'weight'));
            $rand = mt_rand(1, $totalWeight);
            $cumulative = 0;

            foreach ($moves as $move) {
                $cumulative += $move['weight'];
                if ($rand <= $cumulative) {
                    $currentX = $move['x'];
                    $currentY = $move['y'];
                    $nodeId = "node_{$currentX}_{$currentY}";
                    if (!in_array($nodeId, $path)) {
                        $path[] = $nodeId;
                    }
                    break;
                }
            }
        }

        return $path;
    }

    /**
     * Add branch paths to create exploration opportunities
     */
    private function addBranchPaths(array &$nodes, array $mainPath, string $shopNode): void {
        // Connect shop to a nearby main path node via intermediate nodes
        list($shopX, $shopY) = $this->parseNodeId($shopNode);

        // Find closest main path node to shop
        $closestNode = null;
        $minDist = PHP_INT_MAX;

        foreach ($mainPath as $pathNode) {
            list($pathX, $pathY) = $this->parseNodeId($pathNode);
            $dist = abs($shopX - $pathX) + abs($shopY - $pathY);

            if ($dist < $minDist) {
                $minDist = $dist;
                $closestNode = $pathNode;
            }
        }

        // Create path from closest node to shop
        if ($closestNode) {
            $shopPath = $this->generateMainPath($closestNode, $shopNode);

            // Create any missing nodes along shop path
            foreach ($shopPath as $nodeId) {
                if (!isset($nodes[$nodeId])) {
                    list($x, $y) = $this->parseNodeId($nodeId);
                    $nodes[$nodeId] = [
                        'node_id' => $nodeId,
                        'grid_x' => $x,
                        'grid_y' => $y,
                        'node_type' => 'COMBAT',
                        'connections' => [],
                        'is_unlocked' => 0,
                        'is_completed' => 0,
                    ];
                }
            }

            // Add connections along shop path
            for ($i = 0; $i < count($shopPath) - 1; $i++) {
                $currentNode = $shopPath[$i];
                $nextNode = $shopPath[$i + 1];

                if (!in_array($nextNode, $nodes[$currentNode]['connections'])) {
                    $nodes[$currentNode]['connections'][] = $nextNode;
                    $nodes[$nextNode]['connections'][] = $currentNode;
                }
            }
        }

        // Add 8-12 branch nodes from existing nodes (treasure, events, etc.)
        $branchNodesToAdd = mt_rand(8, 12);
        $attempts = 0;
        $maxAttempts = 50;

        while ($branchNodesToAdd > 0 && $attempts < $maxAttempts) {
            $attempts++;

            // Pick a random existing node to branch from
            $existingNodes = array_keys($nodes);
            $sourceNode = $existingNodes[array_rand($existingNodes)];
            list($srcX, $srcY) = $this->parseNodeId($sourceNode);

            // Try to add an adjacent node
            $directions = [
                ['dx' => 1, 'dy' => 0],
                ['dx' => -1, 'dy' => 0],
                ['dx' => 0, 'dy' => 1],
                ['dx' => 0, 'dy' => -1],
            ];

            shuffle($directions);

            foreach ($directions as $dir) {
                $newX = $srcX + $dir['dx'];
                $newY = $srcY + $dir['dy'];
                $newNodeId = "node_{$newX}_{$newY}";

                // Check if position is valid and node doesn't exist
                if ($this->isValidPosition($newX, $newY) && !isset($nodes[$newNodeId])) {
                    // Create new node
                    $nodes[$newNodeId] = [
                        'node_id' => $newNodeId,
                        'grid_x' => $newX,
                        'grid_y' => $newY,
                        'node_type' => 'COMBAT',
                        'connections' => [],
                        'is_unlocked' => 0,
                        'is_completed' => 0,
                    ];

                    // Connect to source
                    $nodes[$sourceNode]['connections'][] = $newNodeId;
                    $nodes[$newNodeId]['connections'][] = $sourceNode;

                    $branchNodesToAdd--;
                    break;
                }
            }
        }
    }

    /**
     * Assign node types to non-special nodes
     */
    private function assignNodeTypes(array &$nodes, array $specialNodes): void {
        $availableTypes = [
            'COMBAT' => 30,   // 60% combat
            'TREASURE' => 8,  // 16% treasure
            'EVENT' => 8,     // 16% events
        ];

        $nodeList = [];
        foreach ($nodes as $nodeId => $node) {
            if (!in_array($nodeId, $specialNodes)) {
                $nodeList[] = $nodeId;
            }
        }

        shuffle($nodeList);
        $index = 0;

        foreach ($availableTypes as $type => $count) {
            for ($i = 0; $i < $count && $index < count($nodeList); $i++, $index++) {
                $nodes[$nodeList[$index]]['node_type'] = $type;
            }
        }
    }

    /**
     * Parse node ID to get X,Y coordinates
     */
    private function parseNodeId(string $nodeId): array {
        preg_match('/node_(\d+)_(\d+)/', $nodeId, $matches);
        return [(int)$matches[1], (int)$matches[2]];
    }

    /**
     * Check if position is within grid bounds
     */
    private function isValidPosition(int $x, int $y): bool {
        return $x >= 0 && $x < self::GRID_SIZE && $y >= 0 && $y < self::GRID_SIZE;
    }

    /**
     * Get next area number for user
     */
    private function getNextAreaNumber(string $userId): int {
        $qb = $this->db->getQueryBuilder();
        $qb->select($qb->func()->max('area_number'))
            ->from('ncquest_adventure_areas')
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));

        $result = $qb->executeQuery();
        $maxArea = $result->fetchOne();
        $result->closeCursor();

        return $maxArea ? (int)$maxArea + 1 : 1;
    }

    /**
     * Create area record in database
     */
    private function createAreaRecord(string $userId, int $areaNumber, string $ageKey): int {
        $qb = $this->db->getQueryBuilder();
        $qb->insert('ncquest_adventure_areas')
            ->values([
                'user_id' => $qb->createNamedParameter($userId),
                'area_number' => $qb->createNamedParameter($areaNumber, IQueryBuilder::PARAM_INT),
                'age_key' => $qb->createNamedParameter($ageKey),
                'nodes_explored' => $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT),
                'total_nodes' => $qb->createNamedParameter(self::TOTAL_NODES, IQueryBuilder::PARAM_INT),
                'is_completed' => $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT),
                'created_at' => $qb->createNamedParameter(new \DateTime(), IQueryBuilder::PARAM_DATE),
            ]);

        $qb->executeStatement();
        return (int)$qb->getLastInsertId();
    }

    /**
     * Save generated nodes to database
     */
    private function saveMapNodes(string $userId, int $areaId, array $nodes): void {
        $qb = $this->db->getQueryBuilder();

        foreach ($nodes as $node) {
            $qb->insert('ncquest_adventure_maps')
                ->values([
                    'user_id' => $qb->createNamedParameter($userId),
                    'area_id' => $qb->createNamedParameter($areaId, IQueryBuilder::PARAM_INT),
                    'node_id' => $qb->createNamedParameter($node['node_id']),
                    'node_type' => $qb->createNamedParameter($node['node_type']),
                    'grid_x' => $qb->createNamedParameter($node['grid_x'], IQueryBuilder::PARAM_INT),
                    'grid_y' => $qb->createNamedParameter($node['grid_y'], IQueryBuilder::PARAM_INT),
                    'connections' => $qb->createNamedParameter(json_encode($node['connections'])),
                    'is_unlocked' => $qb->createNamedParameter($node['is_unlocked'], IQueryBuilder::PARAM_INT),
                    'is_completed' => $qb->createNamedParameter($node['is_completed'], IQueryBuilder::PARAM_INT),
                ]);

            $qb->executeStatement();
        }
    }

    /**
     * Initialize or update player progress
     */
    private function initializeProgress(string $userId, int $areaId): void {
        // Check if progress record exists
        $qb = $this->db->getQueryBuilder();
        $qb->select('id')
            ->from('ncquest_adventure_progress')
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));

        $result = $qb->executeQuery();
        $exists = $result->fetch();
        $result->closeCursor();

        if ($exists) {
            // Update existing progress
            $qb = $this->db->getQueryBuilder();
            $qb->update('ncquest_adventure_progress')
                ->set('current_area_id', $qb->createNamedParameter($areaId, IQueryBuilder::PARAM_INT))
                ->set('current_node_id', $qb->createNamedParameter('node_0_3')) // Start node
                ->set('updated_at', $qb->createNamedParameter(new \DateTime(), IQueryBuilder::PARAM_DATE))
                ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));

            $qb->executeStatement();
        } else {
            // Create new progress record
            $qb = $this->db->getQueryBuilder();
            $qb->insert('ncquest_adventure_progress')
                ->values([
                    'user_id' => $qb->createNamedParameter($userId),
                    'current_area_id' => $qb->createNamedParameter($areaId, IQueryBuilder::PARAM_INT),
                    'current_node_id' => $qb->createNamedParameter('node_0_3'), // Start node
                    'total_areas_completed' => $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT),
                    'total_nodes_explored' => $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT),
                    'total_bosses_defeated' => $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT),
                    'updated_at' => $qb->createNamedParameter(new \DateTime(), IQueryBuilder::PARAM_DATE),
                ]);

            $qb->executeStatement();
        }
    }

    /**
     * Get current map state for user
     */
    public function getCurrentMap(string $userId): ?array {
        // Get current area ID
        $qb = $this->db->getQueryBuilder();
        $qb->select('current_area_id', 'current_node_id')
            ->from('ncquest_adventure_progress')
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));

        $result = $qb->executeQuery();
        $progress = $result->fetch();
        $result->closeCursor();

        if (!$progress || !$progress['current_area_id']) {
            return null;
        }

        $areaId = (int)$progress['current_area_id'];

        // Get area details
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from('ncquest_adventure_areas')
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($areaId, IQueryBuilder::PARAM_INT)));

        $result = $qb->executeQuery();
        $area = $result->fetch();
        $result->closeCursor();

        // Get all nodes for this area
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from('ncquest_adventure_maps')
            ->where($qb->expr()->eq('area_id', $qb->createNamedParameter($areaId, IQueryBuilder::PARAM_INT)));

        $result = $qb->executeQuery();
        $nodes = $result->fetchAll();
        $result->closeCursor();

        // Parse connections JSON
        foreach ($nodes as &$node) {
            $node['connections'] = json_decode($node['connections'], true);
        }

        return [
            'area' => $area,
            'nodes' => $nodes,
            'current_node_id' => $progress['current_node_id'],
        ];
    }

    /**
     * Move player to a new node
     */
    public function moveToNode(string $userId, string $targetNodeId): array {
        $currentMap = $this->getCurrentMap($userId);

        if (!$currentMap) {
            throw new \Exception('No active adventure area found');
        }

        $currentNodeId = $currentMap['current_node_id'];

        // Find current and target nodes
        $currentNode = null;
        $targetNode = null;

        foreach ($currentMap['nodes'] as $node) {
            if ($node['node_id'] === $currentNodeId) {
                $currentNode = $node;
            }
            if ($node['node_id'] === $targetNodeId) {
                $targetNode = $node;
            }
        }

        if (!$targetNode) {
            throw new \Exception('Target node not found');
        }

        if (!$targetNode['is_unlocked']) {
            throw new \Exception('Target node is locked');
        }

        // Check if nodes are connected
        if ($currentNode && !in_array($targetNodeId, $currentNode['connections'])) {
            throw new \Exception('Nodes are not connected');
        }

        // Update current node in progress
        $qb = $this->db->getQueryBuilder();
        $qb->update('ncquest_adventure_progress')
            ->set('current_node_id', $qb->createNamedParameter($targetNodeId))
            ->set('updated_at', $qb->createNamedParameter(new \DateTime(), IQueryBuilder::PARAM_DATE))
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));

        $qb->executeStatement();

        return [
            'success' => true,
            'node' => $targetNode,
        ];
    }

    /**
     * Complete a node and unlock connected nodes
     */
    public function completeNode(string $userId, string $nodeId): void {
        $currentMap = $this->getCurrentMap($userId);

        if (!$currentMap) {
            throw new \Exception('No active adventure area found');
        }

        $areaId = (int)$currentMap['area']['id'];

        // Mark node as completed
        $qb = $this->db->getQueryBuilder();
        $qb->update('ncquest_adventure_maps')
            ->set('is_completed', $qb->createNamedParameter(1, IQueryBuilder::PARAM_INT))
            ->where($qb->expr()->eq('area_id', $qb->createNamedParameter($areaId, IQueryBuilder::PARAM_INT)))
            ->andWhere($qb->expr()->eq('node_id', $qb->createNamedParameter($nodeId)));

        $qb->executeStatement();

        // Unlock connected nodes
        $node = null;
        foreach ($currentMap['nodes'] as $n) {
            if ($n['node_id'] === $nodeId) {
                $node = $n;
                break;
            }
        }

        if ($node) {
            foreach ($node['connections'] as $connectedNodeId) {
                $qb = $this->db->getQueryBuilder();
                $qb->update('ncquest_adventure_maps')
                    ->set('is_unlocked', $qb->createNamedParameter(1, IQueryBuilder::PARAM_INT))
                    ->where($qb->expr()->eq('area_id', $qb->createNamedParameter($areaId, IQueryBuilder::PARAM_INT)))
                    ->andWhere($qb->expr()->eq('node_id', $qb->createNamedParameter($connectedNodeId)));

                $qb->executeStatement();
            }
        }

        // Update area nodes explored count
        $qb = $this->db->getQueryBuilder();
        $qb->update('ncquest_adventure_areas')
            ->set('nodes_explored', $qb->createFunction('nodes_explored + 1'))
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($areaId, IQueryBuilder::PARAM_INT)));

        $qb->executeStatement();

        // Update progress
        $qb = $this->db->getQueryBuilder();
        $qb->update('ncquest_adventure_progress')
            ->set('total_nodes_explored', $qb->createFunction('total_nodes_explored + 1'))
            ->set('updated_at', $qb->createNamedParameter(new \DateTime(), IQueryBuilder::PARAM_DATE))
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));

        $qb->executeStatement();
    }
}
