<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

namespace OCA\NextcloudQuest\Tests\Unit;

use OCA\NextcloudQuest\Service\XPService;
use OCA\NextcloudQuest\Db\QuestMapper;
use OCA\NextcloudQuest\Db\HistoryMapper;
use OCA\NextcloudQuest\Db\Quest;
use OCP\ILogger;
use PHPUnit\Framework\TestCase;

class XPServiceTest extends TestCase {
    private XPService $xpService;
    private QuestMapper $questMapper;
    private HistoryMapper $historyMapper;
    private ILogger $logger;
    
    protected function setUp(): void {
        $this->questMapper = $this->createMock(QuestMapper::class);
        $this->historyMapper = $this->createMock(HistoryMapper::class);
        $this->logger = $this->createMock(ILogger::class);
        
        $this->xpService = new XPService(
            $this->questMapper,
            $this->historyMapper,
            $this->logger
        );
    }
    
    public function testCalculateXPWithLowPriority(): void {
        $xp = $this->xpService->calculateXP('low', 0);
        $this->assertEquals(10, $xp); // Base XP only
    }
    
    public function testCalculateXPWithMediumPriority(): void {
        $xp = $this->xpService->calculateXP('medium', 0);
        $this->assertEquals(15, $xp); // Base XP + 5 bonus
    }
    
    public function testCalculateXPWithHighPriority(): void {
        $xp = $this->xpService->calculateXP('high', 0);
        $this->assertEquals(20, $xp); // Base XP + 10 bonus
    }
    
    public function testCalculateXPWithStreak(): void {
        $xp = $this->xpService->calculateXP('medium', 5);
        $expectedXP = (int)((10 + 5) * 1.5); // 5-day streak = 1.5x multiplier
        $this->assertEquals($expectedXP, $xp);
    }
    
    public function testCalculateXPWithMaxStreak(): void {
        $xp = $this->xpService->calculateXP('high', 20);
        $expectedXP = (int)((10 + 10) * 2.0); // Max 2x multiplier
        $this->assertEquals($expectedXP, $xp);
    }
    
    public function testCalculateLevel(): void {
        $this->assertEquals(1, $this->xpService->calculateLevel(0));
        $this->assertEquals(1, $this->xpService->calculateLevel(99));
        $this->assertEquals(2, $this->xpService->calculateLevel(100));
        $this->assertEquals(3, $this->xpService->calculateLevel(250));
    }
    
    public function testGetXPForLevel(): void {
        $this->assertEquals(0, $this->xpService->getXPForLevel(1));
        $this->assertEquals(100, $this->xpService->getXPForLevel(2));
        $this->assertEquals(250, $this->xpService->getXPForLevel(3));
        $this->assertEquals(475, $this->xpService->getXPForLevel(4));
    }
    
    public function testGetXPForNextLevel(): void {
        $this->assertEquals(100, $this->xpService->getXPForNextLevel(1));
        $this->assertEquals(150, $this->xpService->getXPForNextLevel(2));
        $this->assertEquals(225, $this->xpService->getXPForNextLevel(3));
    }
    
    public function testGetRankTitle(): void {
        $this->assertEquals('Task Novice', $this->xpService->getRankTitle(1));
        $this->assertEquals('Rising Star', $this->xpService->getRankTitle(5));
        $this->assertEquals('Quest Apprentice', $this->xpService->getRankTitle(10));
        $this->assertEquals('Productivity Knight', $this->xpService->getRankTitle(25));
        $this->assertEquals('Legendary Quest Master', $this->xpService->getRankTitle(100));
    }
    
    public function testGetProgressToNextLevel(): void {
        $quest = new Quest();
        $quest->setLevel(2);
        $quest->setCurrentXp(75); // 75 out of 150 needed for level 3
        
        $progress = $this->xpService->getProgressToNextLevel($quest);
        $this->assertEquals(50.0, $progress); // 75/150 * 100 = 50%
    }
}