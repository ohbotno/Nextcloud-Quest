<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

namespace OCA\NextcloudQuest\Service;

use OCA\NextcloudQuest\Db\Achievement;
use OCA\NextcloudQuest\Db\AchievementMapper;
use OCA\NextcloudQuest\Db\HistoryMapper;
use OCA\NextcloudQuest\Db\Quest;
use OCA\NextcloudQuest\Db\QuestMapper;
use Psr\Log\LoggerInterface;
use OCP\Notification\IManager as INotificationManager;

class AchievementService {
    /** @var AchievementMapper */
    private $achievementMapper;
    /** @var HistoryMapper */
    private $historyMapper;
    /** @var INotificationManager */
    private $notificationManager;
    /** @var LoggerInterface */
    private $logger;
    /** @var QuestMapper */
    private $questMapper;
    
    // Achievement definitions with categories and rarity levels
    private const ACHIEVEMENTS = [
        // Task Master Category - Common to Legendary
        'first_task' => [
            'name' => 'First Step',
            'description' => 'Complete your first task',
            'icon' => 'first-step.svg',
            'category' => 'Task Master',
            'rarity' => 'Common',
            'progress_type' => 'milestone',
            'milestone' => 1
        ],
        'tasks_10' => [
            'name' => 'Task Initiator',
            'description' => 'Complete 10 tasks',
            'icon' => 'tasks-10.svg',
            'category' => 'Task Master',
            'rarity' => 'Common',
            'progress_type' => 'milestone',
            'milestone' => 10
        ],
        'tasks_50' => [
            'name' => 'Task Apprentice',
            'description' => 'Complete 50 tasks',
            'icon' => 'tasks-50.svg',
            'category' => 'Task Master',
            'rarity' => 'Common',
            'progress_type' => 'milestone',
            'milestone' => 50
        ],
        'tasks_100' => [
            'name' => 'Productivity Pro',
            'description' => 'Complete 100 tasks',
            'icon' => 'tasks-100.svg',
            'category' => 'Task Master',
            'rarity' => 'Rare',
            'progress_type' => 'milestone',
            'milestone' => 100
        ],
        'tasks_250' => [
            'name' => 'Task Virtuoso',
            'description' => 'Complete 250 tasks',
            'icon' => 'tasks-250.svg',
            'category' => 'Task Master',
            'rarity' => 'Rare',
            'progress_type' => 'milestone',
            'milestone' => 250
        ],
        'tasks_500' => [
            'name' => 'Task Champion',
            'description' => 'Complete 500 tasks',
            'icon' => 'tasks-500.svg',
            'category' => 'Task Master',
            'rarity' => 'Epic',
            'progress_type' => 'milestone',
            'milestone' => 500
        ],
        'tasks_1000' => [
            'name' => 'Task Legend',
            'description' => 'Complete 1000 tasks',
            'icon' => 'tasks-1000.svg',
            'category' => 'Task Master',
            'rarity' => 'Epic',
            'progress_type' => 'milestone',
            'milestone' => 1000
        ],
        'tasks_2500' => [
            'name' => 'Task Overlord',
            'description' => 'Complete 2500 tasks',
            'icon' => 'tasks-2500.svg',
            'category' => 'Task Master',
            'rarity' => 'Legendary',
            'progress_type' => 'milestone',
            'milestone' => 2500
        ],
        'tasks_5000' => [
            'name' => 'Task Deity',
            'description' => 'Complete 5000 tasks - The ultimate achievement!',
            'icon' => 'tasks-5000.svg',
            'category' => 'Task Master',
            'rarity' => 'Legendary',
            'progress_type' => 'milestone',
            'milestone' => 5000
        ],

        // Streak Keeper Category
        'streak_3' => [
            'name' => 'Streak Starter',
            'description' => 'Maintain a 3-day streak',
            'icon' => 'streak-3.svg',
            'category' => 'Streak Keeper',
            'rarity' => 'Common',
            'progress_type' => 'streak',
            'milestone' => 3
        ],
        'streak_7' => [
            'name' => 'Week Warrior',
            'description' => 'Maintain a 7-day streak',
            'icon' => 'streak-7.svg',
            'category' => 'Streak Keeper',
            'rarity' => 'Common',
            'progress_type' => 'streak',
            'milestone' => 7
        ],
        'streak_14' => [
            'name' => 'Fortnight Fighter',
            'description' => 'Maintain a 14-day streak',
            'icon' => 'streak-14.svg',
            'category' => 'Streak Keeper',
            'rarity' => 'Rare',
            'progress_type' => 'streak',
            'milestone' => 14
        ],
        'streak_30' => [
            'name' => 'Monthly Master',
            'description' => 'Maintain a 30-day streak',
            'icon' => 'streak-30.svg',
            'category' => 'Streak Keeper',
            'rarity' => 'Rare',
            'progress_type' => 'streak',
            'milestone' => 30
        ],
        'streak_60' => [
            'name' => 'Consistency Champion',
            'description' => 'Maintain a 60-day streak',
            'icon' => 'streak-60.svg',
            'category' => 'Streak Keeper',
            'rarity' => 'Epic',
            'progress_type' => 'streak',
            'milestone' => 60
        ],
        'streak_100' => [
            'name' => 'Century Champion',
            'description' => 'Maintain a 100-day streak',
            'icon' => 'streak-100.svg',
            'category' => 'Streak Keeper',
            'rarity' => 'Epic',
            'progress_type' => 'streak',
            'milestone' => 100
        ],
        'streak_365' => [
            'name' => 'Year-long Devotee',
            'description' => 'Maintain a full year streak - incredible dedication!',
            'icon' => 'streak-365.svg',
            'category' => 'Streak Keeper',
            'rarity' => 'Legendary',
            'progress_type' => 'streak',
            'milestone' => 365
        ],

        // Level Champion Category
        'level_5' => [
            'name' => 'Rising Star',
            'description' => 'Reach level 5',
            'icon' => 'level-5.svg',
            'category' => 'Level Champion',
            'rarity' => 'Common',
            'progress_type' => 'level',
            'milestone' => 5
        ],
        'level_10' => [
            'name' => 'Dedicated Achiever',
            'description' => 'Reach level 10',
            'icon' => 'level-10.svg',
            'category' => 'Level Champion',
            'rarity' => 'Common',
            'progress_type' => 'level',
            'milestone' => 10
        ],
        'level_25' => [
            'name' => 'Quest Expert',
            'description' => 'Reach level 25',
            'icon' => 'level-25.svg',
            'category' => 'Level Champion',
            'rarity' => 'Rare',
            'progress_type' => 'level',
            'milestone' => 25
        ],
        'level_50' => [
            'name' => 'Master Quester',
            'description' => 'Reach level 50',
            'icon' => 'level-50.svg',
            'category' => 'Level Champion',
            'rarity' => 'Epic',
            'progress_type' => 'level',
            'milestone' => 50
        ],
        'level_75' => [
            'name' => 'Elite Adventurer',
            'description' => 'Reach level 75',
            'icon' => 'level-75.svg',
            'category' => 'Level Champion',
            'rarity' => 'Epic',
            'progress_type' => 'level',
            'milestone' => 75
        ],
        'level_100' => [
            'name' => 'Legendary Hero',
            'description' => 'Reach level 100 - The pinnacle of achievement!',
            'icon' => 'level-100.svg',
            'category' => 'Level Champion',
            'rarity' => 'Legendary',
            'progress_type' => 'level',
            'milestone' => 100
        ],

        // Speed Demon Category
        'speed_3_in_hour' => [
            'name' => 'Quick Starter',
            'description' => 'Complete 3 tasks in one hour',
            'icon' => 'speed-3.svg',
            'category' => 'Speed Demon',
            'rarity' => 'Common',
            'progress_type' => 'special'
        ],
        'speed_5_in_hour' => [
            'name' => 'Speed Demon',
            'description' => 'Complete 5 tasks in one hour',
            'icon' => 'speed-5.svg',
            'category' => 'Speed Demon',
            'rarity' => 'Rare',
            'progress_type' => 'special'
        ],
        'speed_10_in_hour' => [
            'name' => 'Lightning Fast',
            'description' => 'Complete 10 tasks in one hour',
            'icon' => 'speed-10.svg',
            'category' => 'Speed Demon',
            'rarity' => 'Epic',
            'progress_type' => 'special'
        ],
        'speed_15_in_hour' => [
            'name' => 'Task Hurricane',
            'description' => 'Complete 15 tasks in one hour - Incredible speed!',
            'icon' => 'speed-15.svg',
            'category' => 'Speed Demon',
            'rarity' => 'Legendary',
            'progress_type' => 'special'
        ],

        // Consistency Master Category
        'perfect_day' => [
            'name' => 'Perfect Day',
            'description' => 'Complete all tasks in a day',
            'icon' => 'perfect-day.svg',
            'category' => 'Consistency Master',
            'rarity' => 'Rare',
            'progress_type' => 'special'
        ],
        'perfect_week' => [
            'name' => 'Perfect Week',
            'description' => 'Complete all tasks every day for a week',
            'icon' => 'perfect-week.svg',
            'category' => 'Consistency Master',
            'rarity' => 'Epic',
            'progress_type' => 'special'
        ],
        'daily_dozen' => [
            'name' => 'Daily Dozen',
            'description' => 'Complete 12 or more tasks in a single day',
            'icon' => 'daily-dozen.svg',
            'category' => 'Consistency Master',
            'rarity' => 'Rare',
            'progress_type' => 'special'
        ],
        'weekly_warrior' => [
            'name' => 'Weekly Warrior',
            'description' => 'Complete tasks every day for 7 consecutive days',
            'icon' => 'weekly-warrior.svg',
            'category' => 'Consistency Master',
            'rarity' => 'Rare',
            'progress_type' => 'special'
        ],

        // Time Master Category
        'early_bird' => [
            'name' => 'Early Bird',
            'description' => 'Complete a task before 9 AM',
            'icon' => 'early-bird.svg',
            'category' => 'Time Master',
            'rarity' => 'Common',
            'progress_type' => 'special'
        ],
        'dawn_raider' => [
            'name' => 'Dawn Raider',
            'description' => 'Complete a task before 6 AM',
            'icon' => 'dawn-raider.svg',
            'category' => 'Time Master',
            'rarity' => 'Rare',
            'progress_type' => 'special'
        ],
        'night_owl' => [
            'name' => 'Night Owl',
            'description' => 'Complete a task after 9 PM',
            'icon' => 'night-owl.svg',
            'category' => 'Time Master',
            'rarity' => 'Common',
            'progress_type' => 'special'
        ],
        'midnight_warrior' => [
            'name' => 'Midnight Warrior',
            'description' => 'Complete a task after midnight',
            'icon' => 'midnight-warrior.svg',
            'category' => 'Time Master',
            'rarity' => 'Rare',
            'progress_type' => 'special'
        ],
        'weekend_warrior' => [
            'name' => 'Weekend Warrior',
            'description' => 'Complete tasks on Saturday and Sunday',
            'icon' => 'weekend-warrior.svg',
            'category' => 'Time Master',
            'rarity' => 'Rare',
            'progress_type' => 'special'
        ],

        // Special Achievements Category
        'holiday_hero' => [
            'name' => 'Holiday Hero',
            'description' => 'Complete tasks on a major holiday',
            'icon' => 'holiday-hero.svg',
            'category' => 'Special Achievements',
            'rarity' => 'Epic',
            'progress_type' => 'special'
        ],
        'birthday_bonus' => [
            'name' => 'Birthday Bonus',
            'description' => 'Complete tasks on your birthday',
            'icon' => 'birthday-bonus.svg',
            'category' => 'Special Achievements',
            'rarity' => 'Epic',
            'progress_type' => 'special'
        ],
        'new_year_resolution' => [
            'name' => 'New Year Resolution',
            'description' => 'Complete a task on January 1st',
            'icon' => 'new-year.svg',
            'category' => 'Special Achievements',
            'rarity' => 'Rare',
            'progress_type' => 'special'
        ],
        'leap_day_legend' => [
            'name' => 'Leap Day Legend',
            'description' => 'Complete a task on February 29th',
            'icon' => 'leap-day.svg',
            'category' => 'Special Achievements',
            'rarity' => 'Legendary',
            'progress_type' => 'special'
        ],

        // Priority Master Category
        'priority_perfectionist' => [
            'name' => 'Priority Perfectionist',
            'description' => 'Complete 50 high-priority tasks',
            'icon' => 'priority-perfect.svg',
            'category' => 'Priority Master',
            'rarity' => 'Rare',
            'progress_type' => 'milestone',
            'milestone' => 50
        ],
        'urgent_expert' => [
            'name' => 'Urgent Expert',
            'description' => 'Complete 25 urgent tasks within their due date',
            'icon' => 'urgent-expert.svg',
            'category' => 'Priority Master',
            'rarity' => 'Epic',
            'progress_type' => 'milestone',
            'milestone' => 25
        ],
        'deadline_destroyer' => [
            'name' => 'Deadline Destroyer',
            'description' => 'Complete 100 tasks before their due date',
            'icon' => 'deadline-destroyer.svg',
            'category' => 'Priority Master',
            'rarity' => 'Epic',
            'progress_type' => 'milestone',
            'milestone' => 100
        ],

        // ===== ENDURANCE TITAN CATEGORY - Ultra Long-term =====
        'tasks_10000' => [
            'name' => 'Task Emperor',
            'description' => 'Complete 10,000 tasks - A true productivity emperor!',
            'icon' => 'task-emperor.svg',
            'category' => 'Endurance Titan',
            'rarity' => 'Mythic',
            'progress_type' => 'milestone',
            'milestone' => 10000
        ],
        'tasks_25000' => [
            'name' => 'Task Immortal',
            'description' => 'Complete 25,000 tasks - Immortal productivity legend!',
            'icon' => 'task-immortal.svg',
            'category' => 'Endurance Titan',
            'rarity' => 'Mythic',
            'progress_type' => 'milestone',
            'milestone' => 25000
        ],
        'tasks_50000' => [
            'name' => 'Task Transcendent',
            'description' => 'Complete 50,000 tasks - Beyond mortal achievement!',
            'icon' => 'task-transcendent.svg',
            'category' => 'Endurance Titan',
            'rarity' => 'Mythic',
            'progress_type' => 'milestone',
            'milestone' => 50000
        ],
        'streak_500' => [
            'name' => 'Eternal Flame',
            'description' => 'Maintain a 500-day streak - The eternal flame of dedication!',
            'icon' => 'eternal-flame.svg',
            'category' => 'Endurance Titan',
            'rarity' => 'Mythic',
            'progress_type' => 'streak',
            'milestone' => 500
        ],
        'streak_1000' => [
            'name' => 'Millennium Master',
            'description' => 'Maintain a 1000-day streak - Master of the millennium!',
            'icon' => 'millennium-master.svg',
            'category' => 'Endurance Titan',
            'rarity' => 'Mythic',
            'progress_type' => 'streak',
            'milestone' => 1000
        ],

        // ===== WORLD CONQUEROR CATEGORY - Adventure Path =====
        'world_1_complete' => [
            'name' => 'Grassland Hero',
            'description' => 'Complete all levels in World 1: Grassland Village',
            'icon' => 'grassland-hero.svg',
            'category' => 'World Conqueror',
            'rarity' => 'Common',
            'progress_type' => 'special'
        ],
        'world_2_complete' => [
            'name' => 'Desert Champion',
            'description' => 'Conquer World 2: Desert Pyramid',
            'icon' => 'desert-champion.svg',
            'category' => 'World Conqueror',
            'rarity' => 'Rare',
            'progress_type' => 'special'
        ],
        'world_8_complete' => [
            'name' => 'Shadow Realm Master',
            'description' => 'Complete the ultimate World 8: Shadow Realm',
            'icon' => 'shadow-master.svg',
            'category' => 'World Conqueror',
            'rarity' => 'Legendary',
            'progress_type' => 'special'
        ],
        'all_bosses' => [
            'name' => 'Boss Slayer Supreme',
            'description' => 'Defeat all 8 world bosses',
            'icon' => 'boss-slayer.svg',
            'category' => 'World Conqueror',
            'rarity' => 'Epic',
            'progress_type' => 'special'
        ],
        'speedrun_world' => [
            'name' => 'Speed Runner',
            'description' => 'Complete any world in under 3 days',
            'icon' => 'speed-runner.svg',
            'category' => 'World Conqueror',
            'rarity' => 'Epic',
            'progress_type' => 'special'
        ],
        'perfect_world' => [
            'name' => 'Flawless Victory',
            'description' => 'Complete a world without losing any health',
            'icon' => 'flawless-victory.svg',
            'category' => 'World Conqueror',
            'rarity' => 'Legendary',
            'progress_type' => 'special'
        ],

        // ===== CATEGORY SPECIALIST - Master each task type =====
        'personal_master' => [
            'name' => 'Personal Growth Guru',
            'description' => 'Complete 500 personal tasks',
            'icon' => 'personal-guru.svg',
            'category' => 'Category Specialist',
            'rarity' => 'Epic',
            'progress_type' => 'milestone',
            'milestone' => 500
        ],
        'work_warrior' => [
            'name' => 'Work Warrior',
            'description' => 'Complete 500 work tasks',
            'icon' => 'work-warrior.svg',
            'category' => 'Category Specialist',
            'rarity' => 'Epic',
            'progress_type' => 'milestone',
            'milestone' => 500
        ],
        'fitness_fanatic' => [
            'name' => 'Fitness Fanatic',
            'description' => 'Complete 500 fitness tasks',
            'icon' => 'fitness-fanatic.svg',
            'category' => 'Category Specialist',
            'rarity' => 'Epic',
            'progress_type' => 'milestone',
            'milestone' => 500
        ],
        'creative_genius' => [
            'name' => 'Creative Genius',
            'description' => 'Complete 500 creative tasks',
            'icon' => 'creative-genius.svg',
            'category' => 'Category Specialist',
            'rarity' => 'Epic',
            'progress_type' => 'milestone',
            'milestone' => 500
        ],
        'social_butterfly' => [
            'name' => 'Social Butterfly',
            'description' => 'Complete 500 social tasks',
            'icon' => 'social-butterfly.svg',
            'category' => 'Category Specialist',
            'rarity' => 'Epic',
            'progress_type' => 'milestone',
            'milestone' => 500
        ],
        'all_categories_master' => [
            'name' => 'Jack of All Trades',
            'description' => 'Complete 100 tasks in every category',
            'icon' => 'jack-of-trades.svg',
            'category' => 'Category Specialist',
            'rarity' => 'Legendary',
            'progress_type' => 'special'
        ],

        // ===== TIME LORD CATEGORY - Time-based achievements =====
        'tasks_every_hour' => [
            'name' => '24/7 Achiever',
            'description' => 'Complete tasks in all 24 hours of a day',
            'icon' => '24-7-achiever.svg',
            'category' => 'Time Lord',
            'rarity' => 'Legendary',
            'progress_type' => 'special'
        ],
        'monthly_perfect' => [
            'name' => 'Monthly Perfectionist',
            'description' => 'Complete tasks every day for a full month',
            'icon' => 'monthly-perfect.svg',
            'category' => 'Time Lord',
            'rarity' => 'Epic',
            'progress_type' => 'special'
        ],
        'quarterly_champion' => [
            'name' => 'Quarterly Champion',
            'description' => 'Maintain a 90-day streak',
            'icon' => 'quarterly-champion.svg',
            'category' => 'Time Lord',
            'rarity' => 'Epic',
            'progress_type' => 'streak',
            'milestone' => 90
        ],
        'seasonal_master' => [
            'name' => 'Four Seasons Master',
            'description' => 'Complete tasks in all four seasons of a year',
            'icon' => 'four-seasons.svg',
            'category' => 'Time Lord',
            'rarity' => 'Rare',
            'progress_type' => 'special'
        ],
        'year_dominator' => [
            'name' => 'Year Dominator',
            'description' => 'Complete 1000+ tasks in a single year',
            'icon' => 'year-dominator.svg',
            'category' => 'Time Lord',
            'rarity' => 'Legendary',
            'progress_type' => 'special'
        ],

        // ===== EXTREME CHALLENGES - Pushing limits =====
        'speed_20_in_hour' => [
            'name' => 'Task Tornado',
            'description' => 'Complete 20 tasks in one hour - Tornado speed!',
            'icon' => 'task-tornado.svg',
            'category' => 'Extreme Challenges',
            'rarity' => 'Mythic',
            'progress_type' => 'special'
        ],
        'daily_50' => [
            'name' => 'Daily Dominator',
            'description' => 'Complete 50 tasks in one day',
            'icon' => 'daily-dominator.svg',
            'category' => 'Extreme Challenges',
            'rarity' => 'Mythic',
            'progress_type' => 'special'
        ],
        'weekly_200' => [
            'name' => 'Weekly Wonder',
            'description' => 'Complete 200 tasks in one week',
            'icon' => 'weekly-wonder.svg',
            'category' => 'Extreme Challenges',
            'rarity' => 'Legendary',
            'progress_type' => 'special'
        ],
        'no_overdue_30' => [
            'name' => 'Zero Tolerance',
            'description' => 'Have no overdue tasks for 30 consecutive days',
            'icon' => 'zero-tolerance.svg',
            'category' => 'Extreme Challenges',
            'rarity' => 'Epic',
            'progress_type' => 'special'
        ],
        'comeback_king' => [
            'name' => 'Comeback King',
            'description' => 'Restore your streak after a 30+ day break',
            'icon' => 'comeback-king.svg',
            'category' => 'Extreme Challenges',
            'rarity' => 'Rare',
            'progress_type' => 'special'
        ],

        // ===== HEALTH MASTER CATEGORY =====
        'full_health_week' => [
            'name' => 'Healthy Week',
            'description' => 'Maintain full health for 7 consecutive days',
            'icon' => 'healthy-week.svg',
            'category' => 'Health Master',
            'rarity' => 'Rare',
            'progress_type' => 'special'
        ],
        'full_health_month' => [
            'name' => 'Health Champion',
            'description' => 'Maintain full health for 30 consecutive days',
            'icon' => 'health-champion.svg',
            'category' => 'Health Master',
            'rarity' => 'Epic',
            'progress_type' => 'special'
        ],
        'never_zero' => [
            'name' => 'Never Give Up',
            'description' => 'Never reach 0 health in your first 100 days',
            'icon' => 'never-give-up.svg',
            'category' => 'Health Master',
            'rarity' => 'Legendary',
            'progress_type' => 'special'
        ],
        'health_recovery' => [
            'name' => 'Phoenix Rising',
            'description' => 'Recover from less than 10 health to full health',
            'icon' => 'phoenix-rising.svg',
            'category' => 'Health Master',
            'rarity' => 'Rare',
            'progress_type' => 'special'
        ],
        'xp_healer' => [
            'name' => 'XP Medic',
            'description' => 'Heal 1000 health points using XP',
            'icon' => 'xp-medic.svg',
            'category' => 'Health Master',
            'rarity' => 'Epic',
            'progress_type' => 'milestone',
            'milestone' => 1000
        ],

        // ===== XP & LEVEL LEGENDS =====
        'level_150' => [
            'name' => 'Ascended Master',
            'description' => 'Reach level 150 - Ascend beyond mortal limits!',
            'icon' => 'ascended-master.svg',
            'category' => 'XP Legends',
            'rarity' => 'Mythic',
            'progress_type' => 'level',
            'milestone' => 150
        ],
        'level_200' => [
            'name' => 'Divine Champion',
            'description' => 'Reach level 200 - Achieve divine status!',
            'icon' => 'divine-champion.svg',
            'category' => 'XP Legends',
            'rarity' => 'Mythic',
            'progress_type' => 'level',
            'milestone' => 200
        ],
        'xp_millionaire' => [
            'name' => 'XP Millionaire',
            'description' => 'Earn 1,000,000 lifetime XP',
            'icon' => 'xp-millionaire.svg',
            'category' => 'XP Legends',
            'rarity' => 'Legendary',
            'progress_type' => 'milestone',
            'milestone' => 1000000
        ],
        'daily_xp_1000' => [
            'name' => 'XP Explosion',
            'description' => 'Earn 1000 XP in a single day',
            'icon' => 'xp-explosion.svg',
            'category' => 'XP Legends',
            'rarity' => 'Epic',
            'progress_type' => 'special'
        ],
        'xp_streak' => [
            'name' => 'XP Machine',
            'description' => 'Earn 500+ XP for 7 consecutive days',
            'icon' => 'xp-machine.svg',
            'category' => 'XP Legends',
            'rarity' => 'Rare',
            'progress_type' => 'special'
        ],

        // ===== STATISTICAL MARVELS =====
        'task_variety' => [
            'name' => 'Variety King',
            'description' => 'Complete 10 different task types in one day',
            'icon' => 'variety-king.svg',
            'category' => 'Statistical Marvels',
            'rarity' => 'Rare',
            'progress_type' => 'special'
        ],
        'priority_master_500' => [
            'name' => 'Priority Prophet',
            'description' => 'Complete 500 high-priority tasks',
            'icon' => 'priority-prophet.svg',
            'category' => 'Statistical Marvels',
            'rarity' => 'Epic',
            'progress_type' => 'milestone',
            'milestone' => 500
        ],
        'deadline_ninja' => [
            'name' => 'Deadline Ninja',
            'description' => 'Complete 500 tasks before their deadline',
            'icon' => 'deadline-ninja.svg',
            'category' => 'Statistical Marvels',
            'rarity' => 'Epic',
            'progress_type' => 'milestone',
            'milestone' => 500
        ],
        'early_completer' => [
            'name' => 'Ahead of Schedule',
            'description' => 'Complete 100 tasks 3+ days before deadline',
            'icon' => 'ahead-schedule.svg',
            'category' => 'Statistical Marvels',
            'rarity' => 'Rare',
            'progress_type' => 'milestone',
            'milestone' => 100
        ],
        'overdue_recovery' => [
            'name' => 'Redemption Arc',
            'description' => 'Clear 50+ overdue tasks',
            'icon' => 'redemption-arc.svg',
            'category' => 'Statistical Marvels',
            'rarity' => 'Rare',
            'progress_type' => 'milestone',
            'milestone' => 50
        ],

        // ===== RARE & SECRET ACHIEVEMENTS =====
        'palindrome_day' => [
            'name' => 'Palindrome Power',
            'description' => 'Complete tasks on a palindrome date (like 12/21)',
            'icon' => 'palindrome-power.svg',
            'category' => 'Rare & Secret',
            'rarity' => 'Legendary',
            'progress_type' => 'special'
        ],
        'friday_13th' => [
            'name' => 'Lucky 13',
            'description' => 'Complete 13 tasks on Friday the 13th',
            'icon' => 'lucky-13.svg',
            'category' => 'Rare & Secret',
            'rarity' => 'Legendary',
            'progress_type' => 'special'
        ],
        'perfect_score' => [
            'name' => 'Perfect Score',
            'description' => 'Reach exactly 10,000 XP (no more, no less)',
            'icon' => 'perfect-score.svg',
            'category' => 'Rare & Secret',
            'rarity' => 'Mythic',
            'progress_type' => 'special'
        ],
        'binary_master' => [
            'name' => 'Binary Master',
            'description' => 'Complete exactly 1024 tasks (2^10)',
            'icon' => 'binary-master.svg',
            'category' => 'Rare & Secret',
            'rarity' => 'Epic',
            'progress_type' => 'milestone',
            'milestone' => 1024
        ],
        'anniversary_dedication' => [
            'name' => 'Anniversary Hero',
            'description' => 'Complete tasks on the app anniversary date',
            'icon' => 'anniversary-hero.svg',
            'category' => 'Rare & Secret',
            'rarity' => 'Legendary',
            'progress_type' => 'special'
        ],
        'golden_ratio' => [
            'name' => 'Golden Ratio',
            'description' => 'Complete exactly 1618 tasks (golden ratio × 1000)',
            'icon' => 'golden-ratio.svg',
            'category' => 'Rare & Secret',
            'rarity' => 'Mythic',
            'progress_type' => 'milestone',
            'milestone' => 1618
        ],

        // ===== COMMUNITY & SOCIAL (Future multiplayer) =====
        'helper' => [
            'name' => 'Helpful Hero',
            'description' => 'Help 10 other users achieve their goals',
            'icon' => 'helpful-hero.svg',
            'category' => 'Community & Social',
            'rarity' => 'Rare',
            'progress_type' => 'milestone',
            'milestone' => 10
        ],
        'team_player' => [
            'name' => 'Team Player',
            'description' => 'Complete 50 team challenges',
            'icon' => 'team-player.svg',
            'category' => 'Community & Social',
            'rarity' => 'Epic',
            'progress_type' => 'milestone',
            'milestone' => 50
        ],
        'mentor' => [
            'name' => 'Wise Mentor',
            'description' => 'Guide 5 new users to their first achievement',
            'icon' => 'wise-mentor.svg',
            'category' => 'Community & Social',
            'rarity' => 'Legendary',
            'progress_type' => 'milestone',
            'milestone' => 5
        ],
        'inspiration' => [
            'name' => 'Inspiration',
            'description' => 'Share achievements 50 times',
            'icon' => 'inspiration.svg',
            'category' => 'Community & Social',
            'rarity' => 'Rare',
            'progress_type' => 'milestone',
            'milestone' => 50
        ],
        'trendsetter' => [
            'name' => 'Trendsetter',
            'description' => 'Be the first to unlock a new achievement',
            'icon' => 'trendsetter.svg',
            'category' => 'Community & Social',
            'rarity' => 'Mythic',
            'progress_type' => 'special'
        ]
    ];
    
    public function __construct(
        AchievementMapper $achievementMapper,
        HistoryMapper $historyMapper,
        INotificationManager $notificationManager,
        LoggerInterface $logger,
        QuestMapper $questMapper
    ) {
        $this->achievementMapper = $achievementMapper;
        $this->historyMapper = $historyMapper;
        $this->notificationManager = $notificationManager;
        $this->logger = $logger;
        $this->questMapper = $questMapper;
    }
    
    /**
     * Check and unlock achievements for a user (simplified wrapper)
     *
     * @param string $userId
     * @return array Newly unlocked achievements
     */
    public function checkAndUnlockAchievements(string $userId): array {
        try {
            // Get quest object for user - if it doesn't exist, user hasn't completed any tasks yet
            try {
                $quest = $this->questMapper->findByUserId($userId);
            } catch (\Exception $e) {
                // User doesn't have a quest record yet, so no achievements to unlock
                return [];
            }

            // Use current time for completion time
            $completionTime = new \DateTime();

            // Call main achievement checking logic
            return $this->checkAchievements($userId, $quest, $completionTime);
        } catch (\Exception $e) {
            $this->logger->error('Failed to check achievements', [
                'user' => $userId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Check and unlock achievements after task completion
     *
     * @param string $userId
     * @param Quest $quest
     * @param \DateTime $completionTime
     * @return array Newly unlocked achievements
     */
    public function checkAchievements(string $userId, Quest $quest, \DateTime $completionTime): array {
        $unlockedAchievements = [];

        // Get base statistics
        $stats = $this->historyMapper->getCompletionStats($userId);
        $totalTasks = $stats['total_tasks'];
        $currentStreak = $quest->getCurrentStreak();
        $level = $quest->getLevel();
        $lifetimeXP = $quest->getLifetimeXp();

        // Get already unlocked achievements to skip them
        $existingAchievements = $this->achievementMapper->findAllByUserId($userId);
        $unlockedKeys = array_map(fn($a) => $a->getAchievementKey(), $existingAchievements);

        // ===== TASK MASTER CATEGORY - All task count milestones =====
        $taskMilestones = [1 => 'first_task', 10 => 'tasks_10', 50 => 'tasks_50', 100 => 'tasks_100',
                          250 => 'tasks_250', 500 => 'tasks_500', 1000 => 'tasks_1000', 2500 => 'tasks_2500',
                          5000 => 'tasks_5000', 10000 => 'tasks_10000', 25000 => 'tasks_25000', 50000 => 'tasks_50000',
                          1024 => 'binary_master', 1618 => 'golden_ratio'];
        foreach ($taskMilestones as $milestone => $achievementKey) {
            if ($totalTasks >= $milestone && !in_array($achievementKey, $unlockedKeys)) {
                $unlockedAchievements[] = $this->unlockAchievement($userId, $achievementKey);
            }
        }

        // ===== STREAK KEEPER CATEGORY - All streak milestones =====
        $streakMilestones = [3 => 'streak_3', 7 => 'streak_7', 14 => 'streak_14', 30 => 'streak_30',
                            60 => 'streak_60', 100 => 'streak_100', 365 => 'streak_365',
                            500 => 'streak_500', 1000 => 'streak_1000'];
        foreach ($streakMilestones as $milestone => $achievementKey) {
            if ($currentStreak >= $milestone && !in_array($achievementKey, $unlockedKeys)) {
                $unlockedAchievements[] = $this->unlockAchievement($userId, $achievementKey);
            }
        }

        // ===== LEVEL CHAMPION CATEGORY - All level milestones =====
        $levelMilestones = [5 => 'level_5', 10 => 'level_10', 25 => 'level_25', 50 => 'level_50',
                           75 => 'level_75', 100 => 'level_100', 150 => 'level_150', 200 => 'level_200'];
        foreach ($levelMilestones as $milestone => $achievementKey) {
            if ($level >= $milestone && !in_array($achievementKey, $unlockedKeys)) {
                $unlockedAchievements[] = $this->unlockAchievement($userId, $achievementKey);
            }
        }

        // ===== XP LEGENDS CATEGORY =====
        if ($lifetimeXP === 10000) {
            $unlockedAchievements[] = $this->unlockAchievement($userId, 'perfect_score');
        }
        if ($lifetimeXP >= 1000000) {
            $unlockedAchievements[] = $this->unlockAchievement($userId, 'xp_millionaire');
        }

        // ===== TIME MASTER CATEGORY - Time-based achievements =====
        $hour = (int)$completionTime->format('H');
        if ($hour < 6) {
            $unlockedAchievements[] = $this->unlockAchievement($userId, 'dawn_raider');
        } elseif ($hour < 9) {
            $unlockedAchievements[] = $this->unlockAchievement($userId, 'early_bird');
        }
        if ($hour >= 21) {
            $unlockedAchievements[] = $this->unlockAchievement($userId, 'night_owl');
        }
        if ($hour >= 0 && $hour < 1) {
            $unlockedAchievements[] = $this->unlockAchievement($userId, 'midnight_warrior');
        }

        // ===== WEEKEND WARRIOR =====
        $dayOfWeek = (int)$completionTime->format('w');
        if ($dayOfWeek === 0 || $dayOfWeek === 6) {
            $startOfWeek = clone $completionTime;
            $startOfWeek->modify('last monday');
            $endOfWeek = clone $startOfWeek;
            $endOfWeek->modify('+6 days');

            $weekHistory = $this->historyMapper->findByDateRange($userId, $startOfWeek, $endOfWeek);
            $saturdayCompleted = false;
            $sundayCompleted = false;

            foreach ($weekHistory as $entry) {
                $entryDate = new \DateTime($entry->getCompletedAt());
                $entryDay = (int)$entryDate->format('w');
                if ($entryDay === 6) $saturdayCompleted = true;
                if ($entryDay === 0) $sundayCompleted = true;
            }

            if ($saturdayCompleted && $sundayCompleted) {
                $unlockedAchievements[] = $this->unlockAchievement($userId, 'weekend_warrior');
            }
        }

        // ===== SPEED DEMON CATEGORY - Tasks completed within time windows =====
        $oneHourAgo = clone $completionTime;
        $oneHourAgo->modify('-1 hour');
        $recentHistory = $this->historyMapper->findByDateRange($userId, $oneHourAgo, $completionTime);
        $tasksInLastHour = count($recentHistory);

        $speedMilestones = [3 => 'speed_3_in_hour', 5 => 'speed_5_in_hour', 10 => 'speed_10_in_hour',
                           15 => 'speed_15_in_hour', 20 => 'speed_20_in_hour'];
        foreach ($speedMilestones as $milestone => $achievementKey) {
            if ($tasksInLastHour >= $milestone) {
                $unlockedAchievements[] = $this->unlockAchievement($userId, $achievementKey);
            }
        }

        // ===== CONSISTENCY MASTER CATEGORY - Daily achievements =====
        $startOfDay = clone $completionTime;
        $startOfDay->setTime(0, 0, 0);
        $endOfDay = clone $startOfDay;
        $endOfDay->setTime(23, 59, 59);
        $todayHistory = $this->historyMapper->findByDateRange($userId, $startOfDay, $endOfDay);
        $tasksToday = count($todayHistory);

        if ($tasksToday >= 12) {
            $unlockedAchievements[] = $this->unlockAchievement($userId, 'daily_dozen');
        }
        if ($tasksToday >= 50) {
            $unlockedAchievements[] = $this->unlockAchievement($userId, 'daily_50');
        }

        // ===== SPECIAL DATE ACHIEVEMENTS =====
        $month = (int)$completionTime->format('m');
        $day = (int)$completionTime->format('d');

        // New Year Resolution
        if ($month === 1 && $day === 1) {
            $unlockedAchievements[] = $this->unlockAchievement($userId, 'new_year_resolution');
        }

        // Leap Day Legend
        if ($month === 2 && $day === 29) {
            $unlockedAchievements[] = $this->unlockAchievement($userId, 'leap_day_legend');
        }

        // Friday the 13th
        if ($day === 13 && $dayOfWeek === 5) {
            // Check if 13 tasks completed today
            if ($tasksToday >= 13) {
                $unlockedAchievements[] = $this->unlockAchievement($userId, 'friday_13th');
            }
        }

        // Palindrome dates (MM/DD format like 12/21, 10/01, etc)
        $dateStr = $completionTime->format('md');
        if ($dateStr === strrev($dateStr)) {
            $unlockedAchievements[] = $this->unlockAchievement($userId, 'palindrome_day');
        }

        // ===== TIME LORD CATEGORY - Extended streak checks =====
        if ($currentStreak === 90) {
            $unlockedAchievements[] = $this->unlockAchievement($userId, 'quarterly_champion');
        }

        // Check for 7 consecutive days with tasks (weekly_warrior already handled above as weekend_warrior)
        if ($currentStreak >= 7) {
            $unlockedAchievements[] = $this->unlockAchievement($userId, 'weekly_warrior');
        }

        // Filter out already unlocked achievements and null values
        return array_filter($unlockedAchievements);
    }
    
    /**
     * Unlock an achievement for a user
     * 
     * @param string $userId
     * @param string $achievementKey
     * @return Achievement|null
     */
    private function unlockAchievement(string $userId, string $achievementKey): ?Achievement {
        // Check if already unlocked
        if ($this->achievementMapper->hasAchievement($userId, $achievementKey)) {
            return null;
        }
        
        // Get achievement data
        $achievementData = self::ACHIEVEMENTS[$achievementKey] ?? null;
        if (!$achievementData) {
            return null;
        }
        
        // Create new achievement
        $achievement = new Achievement();
        $achievement->setUserId($userId);
        $achievement->setAchievementKey($achievementKey);
        $achievement->setUnlockedAt((new \DateTime())->format('Y-m-d H:i:s'));
        $achievement->setNotified(0);
        $achievement->setAchievementPoints($this->calculateAchievementPoints($achievementData['rarity']));
        $achievement->setAchievementCategory($achievementData['category']);
        $achievement->setProgressCurrent($achievementData['milestone'] ?? 0);
        $achievement->setProgressTarget($achievementData['milestone'] ?? 0);
        
        $achievement = $this->achievementMapper->insert($achievement);
        
        // Send notification
        $this->sendAchievementNotification($userId, $achievementKey);
        
        $this->logger->info('Achievement unlocked', [
            'user' => $userId,
            'achievement' => $achievementKey
        ]);
        
        return $achievement;
    }
    
    /**
     * Send notification for unlocked achievement
     * 
     * @param string $userId
     * @param string $achievementKey
     */
    private function sendAchievementNotification(string $userId, string $achievementKey): void {
        $achievementData = self::ACHIEVEMENTS[$achievementKey] ?? null;
        if (!$achievementData) {
            return;
        }
        
        $notification = $this->notificationManager->createNotification();
        $notification->setApp('nextcloudquest')
            ->setUser($userId)
            ->setDateTime(new \DateTime())
            ->setObject('achievement', $achievementKey)
            ->setSubject('achievement_unlocked', [
                'achievement' => $achievementData['name']
            ])
            ->setMessage('achievement_unlocked_message', [
                'achievement' => $achievementData['name'],
                'description' => $achievementData['description']
            ])
            ->setIcon('achievement');
        
        $this->notificationManager->notify($notification);
    }
    
    /**
     * Get all achievements with unlock status for a user
     * 
     * @param string $userId
     * @return array
     */
    public function getAllAchievements(string $userId): array {
        $unlockedAchievements = $this->achievementMapper->findAllByUserId($userId);
        $this->logger->info("Found " . count($unlockedAchievements) . " unlocked achievements for user: " . $userId);
        $unlockedKeys = array_map(fn($a) => $a->getAchievementKey(), $unlockedAchievements);
        $this->logger->info("Unlocked keys: " . json_encode($unlockedKeys));

        $achievements = [];
        foreach (self::ACHIEVEMENTS as $key => $data) {
            $isUnlocked = in_array($key, $unlockedKeys);
            $unlockedAt = null;
            
            if ($isUnlocked) {
                foreach ($unlockedAchievements as $achievement) {
                    if ($achievement->getAchievementKey() === $key) {
                        $unlockedAt = $achievement->getUnlockedAt();
                        break;
                    }
                }
            }
            
            // Calculate achievement points based on rarity
            $points = $this->calculateAchievementPoints($data['rarity']);
            
            // Get current progress for milestone achievements
            $progressPercentage = 0;
            $progressCurrent = 0;
            $progressTarget = $data['milestone'] ?? 0;

            if (!$isUnlocked && $data['progress_type'] === 'milestone') {
                try {
                    $progress = $this->getAchievementProgress($userId, $key);
                    $this->logger->info("Progress for $key: " . json_encode($progress));
                    if ($progress) {
                        $progressPercentage = $progress['percentage'];
                        $progressCurrent = $progress['current'];
                    }
                } catch (\Exception $e) {
                    // If progress calculation fails, just use 0
                    $this->logger->error("Progress calculation failed for $key: " . $e->getMessage());
                    $progressPercentage = 0;
                    $progressCurrent = 0;
                }
            } elseif ($isUnlocked) {
                $progressPercentage = 100;
                $progressCurrent = $progressTarget;
            }

            // Determine status: locked, in-progress, or unlocked
            $status = 'locked';
            if ($isUnlocked) {
                $status = 'unlocked';
            } elseif ($progressPercentage >= 100) {
                $status = 'unlocked';
            } elseif ($progressPercentage > 0) {
                $status = 'in-progress';
            }

            $achievements[] = [
                'key' => $key,
                'name' => $data['name'],
                'description' => $data['description'],
                'icon' => $data['icon'],
                'category' => $data['category'],
                'rarity' => $data['rarity'],
                'progress_type' => $data['progress_type'],
                'milestone' => $data['milestone'] ?? null,
                'achievement_points' => $points,
                'progress_percentage' => $progressPercentage,
                'progress_current' => $progressCurrent,
                'progress_target' => $progressTarget,
                'status' => $status,
                'unlocked' => $isUnlocked,
                'unlocked_at' => $unlockedAt
            ];
        }
        
        return $achievements;
    }

    /**
     * Calculate achievement points based on rarity
     * 
     * @param string $rarity
     * @return int
     */
    private function calculateAchievementPoints(string $rarity): int {
        $points = [
            'Common' => 10,
            'Rare' => 25,
            'Epic' => 50,
            'Legendary' => 100,
            'Mythic' => 250
        ];
        
        return $points[$rarity] ?? 10;
    }

    /**
     * Get achievements grouped by category
     * 
     * @param string $userId
     * @return array
     */
    public function getAchievementsByCategory(string $userId): array {
        $achievements = $this->getAllAchievements($userId);
        $categories = [];
        
        foreach ($achievements as $achievement) {
            $category = $achievement['category'];
            if (!isset($categories[$category])) {
                $categories[$category] = [
                    'name' => $category,
                    'achievements' => [],
                    'total' => 0,
                    'unlocked' => 0,
                    'percentage' => 0
                ];
            }
            
            $categories[$category]['achievements'][] = $achievement;
            $categories[$category]['total']++;
            if ($achievement['unlocked']) {
                $categories[$category]['unlocked']++;
            }
        }
        
        // Calculate percentages
        foreach ($categories as &$category) {
            $category['percentage'] = $category['total'] > 0 
                ? round(($category['unlocked'] / $category['total']) * 100, 1) 
                : 0;
        }
        
        return $categories;
    }

    /**
     * Get achievement progress for milestone-based achievements
     * 
     * @param string $userId
     * @param string $achievementKey
     * @return array|null
     */
    public function getAchievementProgress(string $userId, string $achievementKey): ?array {
        if (!isset(self::ACHIEVEMENTS[$achievementKey])) {
            return null;
        }

        $achievement = self::ACHIEVEMENTS[$achievementKey];

        // Only calculate progress for milestone-based achievements
        if ($achievement['progress_type'] !== 'milestone') {
            return null;
        }

        $currentValue = 0;
        $milestone = $achievement['milestone'];

        // Get user's quest data
        try {
            $quest = $this->questMapper->findByUserId($userId);
        } catch (\Exception $e) {
            // If quest data doesn't exist, return 0 progress
            return [
                'current' => 0,
                'target' => $milestone,
                'percentage' => 0
            ];
        }

        // Determine current value based on achievement type
        if (strpos($achievementKey, 'tasks_') === 0 ||
            $achievementKey === 'binary_master' ||
            $achievementKey === 'golden_ratio') {
            // Task count achievements
            try {
                $stats = $this->historyMapper->getCompletionStats($userId);
                $this->logger->info("Task stats for $userId: " . json_encode($stats));
                $currentValue = $stats['total_tasks'];
            } catch (\Exception $e) {
                $this->logger->error("Failed to get task stats for $userId: " . $e->getMessage());
                $currentValue = 0;
            }

        } elseif (strpos($achievementKey, 'streak_') === 0) {
            // Streak achievements - get current or longest streak
            $currentValue = $quest->getCurrentStreak();

        } elseif (strpos($achievementKey, 'level_') === 0) {
            // Level achievements
            $currentValue = $quest->getLevel();

        } elseif ($achievementKey === 'xp_millionaire') {
            // XP milestone
            $currentValue = $quest->getLifetimeXp();

        } elseif ($achievementKey === 'xp_healer') {
            // Health healed using XP (would need tracking)
            $currentValue = 0; // TODO: Implement health healing tracking

        } elseif (in_array($achievementKey, ['priority_perfectionist', 'priority_master_500'])) {
            // High priority tasks (would need tracking)
            $currentValue = 0; // TODO: Implement priority task tracking

        } elseif (in_array($achievementKey, ['urgent_expert', 'deadline_destroyer', 'deadline_ninja'])) {
            // Tasks completed before deadline (would need tracking)
            $currentValue = 0; // TODO: Implement deadline tracking

        } elseif ($achievementKey === 'early_completer') {
            // Tasks completed 3+ days early (would need tracking)
            $currentValue = 0; // TODO: Implement early completion tracking

        } elseif ($achievementKey === 'overdue_recovery') {
            // Overdue tasks cleared (would need tracking)
            $currentValue = 0; // TODO: Implement overdue recovery tracking

        } elseif (in_array($achievementKey, ['personal_master', 'work_warrior', 'fitness_fanatic',
                                             'creative_genius', 'social_butterfly'])) {
            // Category-specific task counts (would need category tracking)
            $currentValue = 0; // TODO: Implement category-based task tracking

        } elseif (in_array($achievementKey, ['helper', 'team_player', 'mentor', 'inspiration'])) {
            // Community achievements (future feature)
            $currentValue = 0; // TODO: Implement community features
        }

        return [
            'current' => $currentValue,
            'target' => $milestone,
            'percentage' => min(100, round(($currentValue / $milestone) * 100, 1))
        ];
    }

    /**
     * Get achievements by rarity level
     * 
     * @param string $userId
     * @param string $rarity
     * @return array
     */
    public function getAchievementsByRarity(string $userId, string $rarity): array {
        $achievements = $this->getAllAchievements($userId);
        return array_filter($achievements, fn($a) => $a['rarity'] === $rarity);
    }

    /**
     * Get recent achievements for a user
     * 
     * @param string $userId
     * @param int $limit
     * @return array
     */
    public function getRecentAchievements(string $userId, int $limit = 10): array {
        $achievements = $this->achievementMapper->findRecentByUserId($userId, $limit);
        $result = [];
        
        foreach ($achievements as $achievement) {
            $key = $achievement->getAchievementKey();
            if (isset(self::ACHIEVEMENTS[$key])) {
                $data = self::ACHIEVEMENTS[$key];
                $result[] = [
                    'key' => $key,
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'icon' => $data['icon'],
                    'category' => $data['category'],
                    'rarity' => $data['rarity'],
                    'unlocked_at' => $achievement->getUnlockedAt()
                ];
            }
        }
        
        return $result;
    }
    
    /**
     * Get achievement statistics for a user
     * 
     * @param string $userId
     * @return array
     */
    public function getAchievementStats(string $userId): array {
        $totalAchievements = count(self::ACHIEVEMENTS);
        $unlockedCount = count($this->achievementMapper->findAllByUserId($userId));
        
        return [
            'total' => $totalAchievements,
            'unlocked' => $unlockedCount,
            'percentage' => round(($unlockedCount / $totalAchievements) * 100, 1)
        ];
    }
    
    /**
     * Mark achievements as notified
     * 
     * @param string $userId
     */
    public function markAchievementsAsNotified(string $userId): void {
        $unnotified = $this->achievementMapper->findUnnotified($userId);
        foreach ($unnotified as $achievement) {
            $this->achievementMapper->markAsNotified($achievement->getId());
        }
    }
}