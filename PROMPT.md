# Nextcloud Quest App - Complete Rebuild Prompt

## Overview
You need to build a complete Nextcloud app called "Quest" that gamifies the Nextcloud Tasks app by adding RPG-like progression elements. This is a comprehensive gamification system that transforms task management into an engaging, game-like experience.

## Core Concept
The Quest app integrates with Nextcloud Tasks to provide:
- **XP System**: Users gain experience points for completing tasks
- **Level Progression**: XP accumulates to unlock levels with rank titles
- **Achievement System**: 73+ achievements across 10 categories
- **Streak Tracking**: Daily completion streaks with multipliers
- **Health System**: Penalties for incomplete tasks
- **Adventure Map**: Visual world progression through different biomes
- **Dashboard**: Comprehensive stats and progress tracking

## Architecture Requirements

### Backend (PHP)
**Service-Oriented Architecture** following Nextcloud patterns:
- `lib/Service/` - Core business logic services with dependency injection
- `lib/Controller/` - API endpoints and page rendering with proper annotations
- `lib/Db/` - Database mappers using Nextcloud's query builder
- `lib/Integration/` - External app integration (Tasks via CalDAV)
- `lib/Migration/` - Database schema migrations

**Key Services Needed:**
- `XPService` - Experience point calculations with priority bonuses
- `AchievementService` - Achievement tracking and unlocking logic
- `StreakService` - Daily streak management with grace periods
- `LevelService` - Level calculations and rank title assignment
- `HealthService` - Health penalties for missed tasks
- `TasksApiIntegration` - Bridge to Nextcloud Tasks via CalDAV tables

### Frontend (JavaScript/Vue.js)
**Hybrid Architecture** combining modern Vue.js with legacy JavaScript:
- **Core Layer**: `stats-service.js`, `dom-updater.js`, `quest-app.js`
- **Application Layer**: `task-list-manager.js`, `navigation.js`
- **Vue Components**: Modern components in `src/components/` with Vuex store
- **Page Scripts**: Page-specific JavaScript for dashboard, achievements, adventure

### Database Schema
**Core Tables:**
```sql
-- Unified user data table
ncquest_users: user_id, level, current_xp, total_tasks_completed, 
               tasks_completed_today, tasks_completed_this_week, 
               current_streak, longest_streak, xp_gained_today,
               last_daily_reset, health_points

-- XP transaction history
quest_xp_history: id, user_id, task_id, xp_earned, completed_at, priority

-- Achievement tracking
ncquest_achievements: user_id, achievement_id, unlocked_at, progress

-- Settings and preferences
quest_settings: user_id, setting_key, setting_value
```

## Integration Patterns

### Tasks App Integration
**CRITICAL**: Integration is through CalDAV tables, NOT direct Tasks app APIs:
- Read from `calendars` and `calendarobjects` tables
- Parse VTODO format for task data
- Monitor task completion through CalDAV status changes
- Award XP and update streaks when tasks marked complete

### XP Calculation Formula
```
Total XP = (Base XP + Priority Bonus) × Streak Multiplier
- Base XP: 10 points per task
- Priority Bonus: 0-10 points based on task priority
- Streak Multiplier: 1.0x to 2.0x based on current streak
```

### Level System
- Level = floor(sqrt(total_xp / 100))
- Each level requires progressively more XP
- Rank titles change every 5-10 levels
- Progress percentage calculated for current level

## Frontend Architecture Patterns

### Stats Service (Critical Component)
A centralized service managing all user statistics:
```javascript
class StatsService {
    // Consumer pattern for UI updates
    registerConsumer(id, callbacks)
    // API normalization for backward compatibility
    normalizeStatsData(newData)
    // Caching with configurable timeout
    loadStats(forceRefresh = false)
}
```

### DOM Updater
Centralized DOM manipulation preventing update conflicts:
```javascript
class DomUpdater {
    updateDashboardPageStats(stats)
    updateSidebar(stats)
    updateElement(id, value)
    updateProgressBar(id, percentage)
}
```

### Task List Manager
Handles task completion workflow:
```javascript
class TaskListManager {
    completeTask(taskId, listId)
    // Integrates with XP awarding
    // Updates UI immediately
    // Handles API communication
}
```

## Page Structure

### Dashboard Page (`templates/index.php`)
**Primary Stats Tiles** with specific formats:
- Level tile: Shows current level with "X% to next level" subtitle
- Total XP tile: Shows total XP with "X XP gained today" subtitle
- Streak tile: Shows current streak with "X days longest ever" subtitle
- Tasks Today tile: Shows completed count with "of X target" subtitle

**Secondary Stats:**
- Weekly tasks completed
- Total achievements unlocked with "of X total" format
- Quick stats list (longest streak, total completed, average per day)

### Quest Page (`templates/quests.php`)
- Task list integration with completion checkboxes
- Real-time XP awards on task completion
- Priority-based task organization
- Progress tracking per task list

### Achievements Page (`templates/achievements.php`)
- Gallery view of all 73+ achievements
- Category-based organization (10 categories)
- Progress tracking for incomplete achievements
- Achievement unlock notifications

### Adventure Page (New)
- Visual world map with biome progression
- Level-based area unlocking
- Character avatar display
- Interactive progression path

### Settings Page (`templates/settings.php`)
- Auto-save settings table
- Task list filtering preferences
- Notification settings
- Theme preferences

## Achievement System

### Categories and Examples
1. **Task Completion**: first-step, tasks-10, tasks-100, tasks-1000, etc.
2. **Streaks**: streak-3, streak-7, streak-30, streak-365
3. **Speed**: speed-demon, early-bird, deadline-ninja
4. **Levels**: level-5, level-25, level-50, level-100
5. **XP Milestones**: xp-machine, xp-millionaire
6. **Consistency**: perfect-day, perfect-week, monthly-perfect
7. **Special Dates**: new-year, birthday-bonus, leap-day
8. **Health**: fitness-fanatic, health-champion
9. **Social**: team-player, helpful-hero
10. **Misc**: multitasker, overachiever, comeback-king

### Achievement Logic Patterns
```php
// Milestone achievements (tasks, XP, levels)
if ($totalTasks >= $milestone) unlock($achievement);

// Streak achievements
if ($currentStreak >= $target) unlock($achievement);

// Time-based achievements
if ($date === 'special_date') unlock($achievement);

// Percentage-based achievements
if ($completionRate >= $threshold) unlock($achievement);
```

## API Endpoints

### Stats API (`/api/stats`)
Returns normalized user statistics:
```json
{
    "status": "success",
    "data": {
        "level": {
            "level": 25,
            "current_xp": 6250,
            "xp_to_next_level": 150,
            "xp_progress": 75.5,
            "rank_title": "Task Champion",
            "xp_gained_today": 120
        },
        "tasks": {
            "completed_today": 8,
            "completed_this_week": 34,
            "total_completed": 1547,
            "daily_target": 5
        },
        "streak": {
            "current_streak": 12,
            "longest_streak": 47,
            "is_active_today": true
        },
        "achievements": {
            "unlocked": 23,
            "total": 73,
            "percentage": 31.5
        }
    }
}
```

### Task Completion API (`/api/complete-quest`)
Handles task completion workflow:
- Validates task exists and is incomplete
- Awards XP based on priority and streak
- Updates streak counters
- Checks and unlocks achievements
- Updates daily/weekly counters
- Returns updated stats

### Additional APIs
- `/api/achievements` - Achievement data and progress
- `/api/task-lists` - CalDAV task list integration
- `/api/settings` - User preferences with auto-save
- `/api/health` - Health system management

## Development Workflow

### Setup Requirements
```bash
# Dependencies
composer install
npm ci

# Development
npm run dev          # Watch mode for frontend
npm run build        # Production build
composer run cs:fix  # PHP code style
npm run lint:fix     # JavaScript linting

# Testing
make test           # All tests
composer run test:unit
vendor/bin/phpunit tests/Unit/XPServiceTest.php
```

### Controller Pattern
```php
class QuestController extends Controller {
    /** @NoAdminRequired @NoCSRFRequired */
    public function completeTaskFromList(string $taskId, string $listId): JSONResponse {
        // Dependency injection from Application.php
        // Proper error handling
        // Service layer integration
        // Achievement processing
    }
}
```

### Service Registration (`lib/AppInfo/Application.php`)
```php
$container->registerService(XPService::class, function ($c) {
    return new XPService(
        $c->get(IDBConnection::class),
        $c->get(ILogger::class)
    );
});
```

## Critical Implementation Notes

### Task Completion Flow
1. Frontend checkbox triggers AJAX to `/api/complete-quest`
2. Controller validates task and user permissions
3. XPService calculates XP with bonuses and multipliers
4. StreakService updates daily completion tracking
5. AchievementService checks all relevant achievements
6. Stats updated in unified ncquest_users table
7. Response includes updated stats for immediate UI refresh

### Stats Storage Pattern
**CRITICAL**: All dashboard tile values are stored directly in database, NOT calculated dynamically:
- `tasks_completed_today`, `tasks_completed_this_week` - stored and incremented
- `xp_gained_today` - stored and reset daily via background job
- `current_streak`, `longest_streak` - stored and maintained
- Only derived values like percentages are calculated

### Daily Reset Mechanism
Background job resets daily counters at midnight:
- `tasks_completed_today = 0`
- `xp_gained_today = 0`
- `last_daily_reset = current_date`
- Streak maintenance and health penalties

### Frontend Data Flow
1. StatsService loads from `/api/stats` with 5-minute cache
2. Normalizes new API format to legacy format for compatibility
3. Notifies registered consumers (DomUpdater, Vue components)
4. DomUpdater applies specific formatting rules for dashboard tiles
5. Task completion forces stats refresh for immediate updates

## Testing Strategy

### Unit Tests
- XPService calculation logic
- AchievementService unlock conditions
- StreakService daily tracking
- Migration scripts

### Integration Tests
- CalDAV task integration
- Complete task-to-XP workflow
- Achievement unlock pipeline
- API endpoint responses

### Frontend Tests
- StatsService consumer pattern
- DOM update consistency
- Task completion UI flow
- Vue component integration

## Deployment Considerations

### Database Migrations
- Version-numbered migration files
- ISchemaWrapper for cross-database compatibility
- Rollback capabilities for failed deployments
- Index optimization for performance

### Caching Strategy
- 5-minute stats cache with force refresh on updates
- Webpack bundling for production
- Browser cache headers for static assets
- Database query optimization

### Background Jobs
- Daily reset job at midnight
- Health penalty calculation
- Achievement progress recalculation
- Streak maintenance with grace periods

## Security Requirements

### API Security
- `@NoAdminRequired @NoCSRFRequired` for AJAX endpoints
- Proper user authentication validation
- Input sanitization and validation
- SQL injection prevention via query builder

### Data Privacy
- User data isolation by user_id
- No sensitive information in logs
- Secure CalDAV table access
- Permission-based achievement visibility

## Performance Optimization

### Database
- Proper indexes on user_id, completed_at, achievement_id
- Query optimization for stats calculations
- Connection pooling for high loads
- Pagination for large achievement lists

### Frontend
- Lazy loading for achievement gallery
- Efficient DOM updates via DomUpdater
- Webpack code splitting
- Image optimization for achievement icons

## Error Handling

### Backend Errors
- Graceful degradation for CalDAV failures
- Rollback for failed XP awards
- Logging for debugging without sensitive data
- User-friendly error messages

### Frontend Errors
- Fallback to cached stats on API failures
- Retry mechanisms for failed requests
- Loading states for long operations
- Error boundaries for Vue components

## File Structure
```
quest/
├── appinfo/
│   ├── info.xml
│   ├── routes.php
│   └── database.xml
├── lib/
│   ├── AppInfo/Application.php
│   ├── Controller/
│   │   ├── QuestController.php
│   │   ├── QuestStatsController.php
│   │   ├── TaskCompletionController.php
│   │   └── PageController.php
│   ├── Service/
│   │   ├── XPService.php
│   │   ├── AchievementService.php
│   │   ├── StreakService.php
│   │   ├── LevelService.php
│   │   └── HealthService.php
│   ├── Db/
│   │   ├── User.php
│   │   ├── Achievement.php
│   │   └── XPHistory.php
│   ├── Integration/
│   │   └── TasksApiIntegration.php
│   └── Migration/
├── js/
│   ├── core/
│   │   ├── stats-service.js
│   │   ├── dom-updater.js
│   │   └── quest-app.js
│   ├── task-list-manager.js
│   ├── achievements.js
│   └── navigation.js
├── src/
│   ├── components/
│   │   ├── QuestDashboard.vue
│   │   ├── AchievementGallery.vue
│   │   ├── LevelIndicator.vue
│   │   └── ProgressBar.vue
│   ├── store/modules/quest.js
│   ├── services/api.js
│   └── main.js
├── templates/
│   ├── index.php (dashboard)
│   ├── quests.php
│   ├── achievements.php
│   ├── adventure.php
│   └── settings.php
├── css/
│   ├── nextcloud-quest-unified.css
│   ├── adventure-map.css
│   └── components/
└── img/achievements/
    └── [73+ achievement SVG icons]
```

## Common Pitfalls to Avoid

1. **Route Mapping**: Ensure routes.php correctly maps endpoints to controller methods
2. **Service Registration**: All services must be registered in Application.php with proper DI
3. **CalDAV Integration**: Use table-level integration, not direct Tasks app APIs
4. **Stats Storage**: Store tile values in database, don't calculate dynamically
5. **Frontend Normalization**: Maintain backward compatibility in StatsService
6. **Background Jobs**: Register all jobs in Application.php for cron execution
7. **Migration Versioning**: Use proper version numbering for database changes
8. **API Annotations**: Include `@NoAdminRequired @NoCSRFRequired` for frontend calls
9. **Bundle Management**: Rebuild webpack after JavaScript changes
10. **Cache Management**: Force refresh stats after task completion

## Success Metrics

### User Engagement
- Daily task completion rates
- Streak maintenance percentage
- Achievement unlock progression
- Return user rates

### Technical Performance
- API response times under 200ms
- Database query optimization
- Frontend load times
- Error rates below 1%

### Feature Adoption
- Dashboard tile interaction rates
- Achievement gallery visits
- Adventure map engagement
- Settings customization usage

This prompt provides the complete foundation for building a robust, engaging gamification system that seamlessly integrates with Nextcloud Tasks while providing an RPG-like progression experience for users.