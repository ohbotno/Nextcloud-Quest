# Nextcloud Quest - Complete Architecture Documentation

## Overview

Nextcloud Quest is a gamification system for Nextcloud Tasks that transforms task completion into an engaging RPG-like experience. This document provides a complete understanding of the system architecture to enable instant navigation for new features, troubleshooting, and maintenance.

## Table of Contents

1. [System Architecture](#system-architecture)
2. [Directory Structure](#directory-structure)
3. [Controller Layer](#controller-layer)
4. [Service Layer](#service-layer)
5. [Database Schema](#database-schema)
6. [Frontend Architecture](#frontend-architecture)
7. [API Endpoints](#api-endpoints)
8. [Development Guidelines](#development-guidelines)
9. [Troubleshooting Guide](#troubleshooting-guide)

## System Architecture

### High-Level Overview
```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Frontend      │    │   Backend       │    │   Database      │
│                 │    │                 │    │                 │
│ • Vue.js Store  │◄──►│ • Controllers   │◄──►│ • User Data     │
│ • JavaScript    │    │ • Services      │    │ • XP History    │
│ • CSS           │    │ • Mappers       │    │ • Achievements  │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                       │                       │
         │              ┌─────────────────┐              │
         └─────────────►│ Nextcloud Tasks │◄─────────────┘
                        │   CalDAV API    │
                        └─────────────────┘
```

### Core Components

1. **Gamification Engine**: XP, levels, achievements, streaks
2. **Task Integration**: Seamless integration with Nextcloud Tasks
3. **Character System**: Avatars, progression, customization
4. **Adventure System**: Procedural worlds and levels
5. **Analytics**: Progress tracking and insights

## Directory Structure

```
quest/
├── appinfo/
│   ├── info.xml                    # App metadata and dependencies
│   └── routes.php                  # Route definitions
├── css/                           # Styling (organized by components)
│   ├── base/                      # Variables, resets
│   ├── components/                # Component-specific styles
│   ├── layout/                    # Layout and structure
│   └── themes/                    # Theme variations
├── img/                           # Images and icons
│   └── achievements/              # 100+ achievement SVG icons
├── js/                            # Compiled JavaScript
├── lib/                           # PHP backend code
│   ├── AppInfo/
│   │   └── Application.php        # Dependency injection container
│   ├── Controller/
│   │   ├── Base/
│   │   │   └── BasePageController.php  # Shared page functionality
│   │   ├── Api/                   # API controllers
│   │   │   ├── QuestStatsController.php      # User statistics
│   │   │   ├── TaskCompletionController.php  # Task completion
│   │   │   └── TaskListController.php        # Task list retrieval
│   │   ├── PageController.php     # Page rendering
│   │   ├── QuestController.php    # Main quest functionality
│   │   ├── AdventureWorldController.php  # Adventure system
│   │   ├── CharacterController.php    # Character system
│   │   └── SettingsController.php     # Settings management
│   ├── Service/                   # Business logic services
│   │   ├── XPService.php          # Experience point calculations
│   │   ├── AchievementService.php # Achievement management
│   │   ├── StreakService.php      # Streak tracking
│   │   ├── LevelService.php       # Level progression
│   │   ├── CharacterService.php   # Character management
│   │   ├── HealthService.php      # Health system
│   │   ├── WorldGenerator.php     # Adventure world generation
│   │   └── PathGenerator.php      # Adventure path creation
│   ├── Db/                        # Database mappers
│   │   ├── QuestMapper.php        # User data mapping
│   │   ├── HistoryMapper.php      # XP history mapping
│   │   └── AchievementMapper.php  # Achievement mapping
│   ├── Migration/                 # Database migrations
│   └── BackgroundJob/             # Background tasks
│       ├── StreakMaintenanceJob.php  # Daily streak maintenance
│       ├── HealthPenaltyJob.php      # Health penalties
│       └── DailySummaryJob.php       # Daily summaries
├── src/                           # Vue.js source components
│   ├── components/                # Vue components
│   │   ├── AchievementGallery.vue
│   │   ├── LevelIndicator.vue
│   │   ├── ProgressBar.vue
│   │   ├── QuestDashboard.vue
│   │   └── [8 more components]
│   ├── services/
│   │   └── api.js                 # API client
│   └── store/
│       └── modules/
│           └── quest.js           # Vuex store
└── templates/                     # PHP templates
    ├── layout.php                 # Base layout
    ├── index.php                  # Dashboard
    ├── achievements.php           # Achievements page
    ├── quests.php                 # Quests page
    ├── adventure.php              # Adventure map
    └── settings.php               # Settings page
```

## Controller Layer

### Page Controllers

#### BasePageController (`lib/Controller/Base/BasePageController.php`)
**Purpose**: Eliminates code duplication across page controllers
**Methods**: 
- `renderPage()` - Common page rendering with scripts/styles

#### PageController (`lib/Controller/PageController.php`)
**Purpose**: Renders all main application pages
**Pages**: Dashboard, Quests, Achievements, Adventure, Settings
**Pattern**: Each method calls `renderPage()` with page-specific parameters

### API Controllers

#### QuestStatsController (`lib/Controller/Api/QuestStatsController.php`)
**Purpose**: User statistics and progress tracking
**Key Endpoints**:
- `GET /api/stats` - Unified stats endpoint
- `GET /api/user-stats` - Legacy user stats
**Responsibilities**: User data retrieval, level calculation, streak management

#### TaskCompletionController (`lib/Controller/Api/TaskCompletionController.php`)
**Purpose**: Task completion workflow and XP management
**Key Endpoints**:
- `POST /api/complete-quest` - Complete task and award XP
**Responsibilities**: CalDAV integration, XP calculation, achievement processing

#### TaskListController (`lib/Controller/Api/TaskListController.php`)
**Purpose**: Task list retrieval and CalDAV integration
**Key Endpoints**:
- `GET /api/quest-lists` - Get task lists from Nextcloud Tasks
**Responsibilities**: Tasks app integration, CalDAV parsing, task filtering

### Specialized Controllers

#### QuestController (`lib/Controller/QuestController.php`)
**Purpose**: Service-based quest functionality with proper dependency injection
**Key Features**: Achievement management, history, leaderboard

#### AdventureWorldController (`lib/Controller/AdventureWorldController.php`)
**Purpose**: Adventure path system with procedural world generation
**Key Features**: World creation, level objectives, boss challenges

#### CharacterController (`lib/Controller/CharacterController.php`)
**Purpose**: Character customization and progression
**Key Features**: Appearance, equipment, character stats

## Service Layer

### Core Services

#### XPService (`lib/Service/XPService.php`)
**Purpose**: Experience point calculations and level progression
**Key Methods**:
- `calculateXP()` - Calculate XP for task completion
- `updateUserXP()` - Update user's total XP
- `getLevelFromXP()` - Determine level from XP amount

#### AchievementService (`lib/Service/AchievementService.php`)
**Purpose**: Achievement management (73+ achievements across 10 categories)
**Categories**: Task Master, Streak Keeper, Level Champion, Speed Demon, etc.
**Key Methods**:
- `checkAchievements()` - Check for unlocked achievements
- `getAchievementProgress()` - Calculate achievement progress

#### StreakService (`lib/Service/StreakService.php`)
**Purpose**: Daily streak tracking and maintenance
**Key Methods**:
- `calculateStreak()` - Calculate current streak
- `updateStreak()` - Update streak on task completion
- `maintainStreaks()` - Background job for streak maintenance

#### LevelService (`lib/Service/LevelService.php`)
**Purpose**: Level progression and rank titles
**Key Methods**:
- `calculateLevel()` - Determine level from XP
- `getRankTitle()` - Get rank title for level
- `getXPForLevel()` - Calculate XP required for level

## Database Schema

### Core Tables

#### `ncquest_users` (Unified User Data)
**Purpose**: Main user statistics table
**Key Columns**:
- `user_id` - Nextcloud user ID
- `total_xp` - Lifetime experience points
- `level` - Current level
- `current_streak` - Current daily streak
- `longest_streak` - Longest achieved streak
- `last_activity` - Last task completion date

#### `quest_xp_history` (XP History)
**Purpose**: Track all XP gains for analytics
**Key Columns**:
- `user_id` - Nextcloud user ID
- `task_title` - Completed task name
- `xp_earned` - XP gained from task
- `completed_at` - Completion timestamp
- `task_priority` - Task priority level

#### `ncquest_achievements` (Achievements)
**Purpose**: Track achievement unlocks
**Key Columns**:
- `user_id` - Nextcloud user ID
- `achievement_key` - Unique achievement identifier
- `unlocked_at` - When achievement was unlocked
- `progress` - Current progress towards achievement

### Legacy Tables

#### `quest_user_data` (Legacy - Being Phased Out)
**Purpose**: Original user data table
**Status**: Maintained for compatibility, being consolidated into `ncquest_users`

## Frontend Architecture

### JavaScript Architecture (3-Layer)

#### Core Layer
- `stats-service.js` - Centralized statistics management
- `dom-updater.js` - DOM manipulation utilities
- `quest-app.js` - Main application coordinator

#### Application Layer
- `task-list-manager.js` - Task/quest management
- `navigation.js` - Navigation and routing

#### Page Layer
- `dashboard.js` - Dashboard functionality
- `achievements.js` - Achievement gallery
- `adventure-map.js` - Adventure system UI

### Vue.js Integration

#### Vuex Store (`src/store/modules/quest.js`)
**Purpose**: State management for user stats, achievements, history
**Integration**: Works alongside legacy JavaScript via QuestApp coordinator

#### Vue Components (`src/components/`)
**Key Components**:
- `QuestDashboard.vue` - Main dashboard
- `AchievementGallery.vue` - Achievement display
- `LevelIndicator.vue` - Level progression
- `ProgressBar.vue` - XP visualization
- `StreakCounter.vue` - Streak display

### API Communication

#### API Service (`src/services/api.js`)
**Purpose**: HTTP client for backend communication
**Pattern**: Centralized API calls with error handling

## API Endpoints

### Stats Endpoints
- `GET /api/stats` - Unified user statistics
- `GET /api/user-stats` - Legacy user statistics

### Task Endpoints
- `GET /api/quest-lists` - Get task lists from Nextcloud Tasks
- `POST /api/complete-quest` - Complete task and award XP

### Achievement Endpoints
- `GET /api/achievements` - Get all achievements
- `GET /api/achievements/recent` - Get recent unlocks
- `GET /api/achievements/stats` - Achievement statistics

### Adventure Endpoints
- `GET /api/adventure/worlds` - Get adventure worlds
- `GET /api/adventure/current-path/{worldNumber}` - Get current path
- `POST /api/adventure/complete-level/{levelId}` - Complete level

### Character Endpoints
- `GET /api/character` - Get character data
- `PUT /api/character/appearance` - Update appearance
- `POST /api/character/equip/{itemKey}` - Equip item

## Development Guidelines

### Adding New Features

#### 1. New Controllers
- Place in `lib/Controller/Api/` for API endpoints
- Extend appropriate base controller
- Remove `@NoCSRFRequired` for security
- Add to `Application.php` for dependency injection

#### 2. New Services
- Place in `lib/Service/`
- Follow dependency injection pattern
- Add to `Application.php` registration

#### 3. New Frontend Components
- Vue components in `src/components/`
- JavaScript services in `js/` (compiled)
- Follow existing architecture patterns

#### 4. New Database Tables
- Create migration in `lib/Migration/`
- Add mapper in `lib/Db/`
- Update service layer accordingly

### Code Standards

1. **Security**: Always enable CSRF protection on API endpoints
2. **Dependencies**: Use dependency injection, register in Application.php
3. **Documentation**: Add PHPDoc comments for all public methods
4. **Error Handling**: Implement proper error responses
5. **Testing**: Test all new functionality thoroughly

## Troubleshooting Guide

### Common Issues

#### 1. Controller Not Found
**Symptoms**: 404 errors on API endpoints
**Check**:
- Controller registered in `Application.php`
- Route defined in `routes.php`
- Namespace and class name correct

#### 2. Database Errors
**Symptoms**: Data not saving/loading
**Check**:
- Migration files executed
- Database tables exist
- Mapper correctly configured

#### 3. Frontend Not Loading
**Symptoms**: JavaScript errors, missing UI
**Check**:
- Scripts loaded in PageController
- Assets compiled correctly
- Console for JavaScript errors

#### 4. Achievement Not Unlocking
**Symptoms**: Achievements not triggering
**Check**:
- AchievementService properly integrated
- Task completion calling achievement check
- Achievement criteria correctly defined

### Debugging Steps

1. **Check Logs**: Nextcloud logs for PHP errors
2. **Browser Console**: JavaScript errors and API responses
3. **Database**: Verify data integrity and table structure
4. **Routes**: Ensure routes point to correct controllers
5. **Dependencies**: Verify all services properly injected

### Performance Optimization

1. **Database**: Add indexes for frequently queried columns
2. **Frontend**: Lazy load components, optimize API calls
3. **Caching**: Implement caching for expensive operations
4. **Background Jobs**: Use for heavy processing

## Migration Notes

### Recent Refactoring (Completed)

1. **Controller Split**: Broke down 1,657-line SimpleQuestController into:
   - QuestStatsController (672 lines)
   - TaskCompletionController (971 lines)
   - TaskListController (276 lines)

2. **BasePageController**: Eliminated code duplication in page rendering

3. **CSRF Protection**: Enhanced security by removing @NoCSRFRequired

4. **Debug Cleanup**: Removed all debug code and console.log statements

### Database Schema Migration

The system is transitioning from `quest_user_data` to `ncquest_users` for unified user data management. Both tables are currently maintained for compatibility.

---

This architecture documentation provides a complete understanding of the Nextcloud Quest system. For specific implementation details, refer to the source code with this guide as a roadmap.