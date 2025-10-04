# Nextcloud Quest - Codebase Architecture Documentation

## Overview

This document describes the architecture of the Nextcloud Quest gamification system. It serves as a reference for developers working on the codebase and documents key design decisions, data flows, and troubleshooting information.

## System Architecture

The Nextcloud Quest app follows a modular, event-driven architecture with clear separation of concerns:

```
┌─────────────────────────────────────────────────────────────┐
│                    Frontend Architecture                    │
├─────────────────────────────────────────────────────────────┤
│  Core Services                                              │
│  ├── stats-service.js    (Single source of truth for stats)│
│  ├── dom-updater.js      (Centralized DOM manipulations)   │
│  └── quest-app.js        (Main initialization & wiring)    │
├─────────────────────────────────────────────────────────────┤
│  Application Layer                                          │
│  ├── task-list-manager.js (Task/quest management)          │
│  ├── navigation.js        (Page routing)                   │
│  └── Vue Store            (State management for Vue components)│
├─────────────────────────────────────────────────────────────┤
│  Page Layer                                                 │
│  ├── quests-page.js       (Quest-specific functionality)   │
│  ├── achievements-page.js (Achievement displays)           │
│  ├── progress-page.js     (Progress tracking)              │
│  └── adventure-map.js     (Adventure world interface)      │
└─────────────────────────────────────────────────────────────┘
```

## Core Components

### 1. Stats Service (`js/core/stats-service.js`)
**Responsibility:** Single source of truth for all user statistics

**Key Features:**
- API communication with `/apps/quest/api/user-stats`
- Consumer registration pattern for decoupled updates
- Caching with automatic refresh
- Event-driven notifications

**API Response Format:**
```json
{
  "status": "success",
  "data": {
    "user": {
      "id": "username",
      "theme_preference": "game"
    },
    "level": {
      "level": 5,
      "rank_title": "Apprentice Warrior",
      "xp": 1225,
      "xp_to_next": 275,
      "progress_percentage": 45
    },
    "streak": {
      "current_streak": 0,
      "longest_streak": 0
    },
    "stats": {
      "total_completed": 12,
      "total_xp": 1225,
      "achievements_unlocked": 0,
      "tasks_today": 0,
      "tasks_this_week": 12
    }
  }
}
```

### 2. DOM Updater (`js/core/dom-updater.js`)
**Responsibility:** Centralized DOM element updates

**Key Features:**
- Updates sidebar stats consistently
- Handles progress bars and text elements
- Prevents conflicts between multiple update sources
- Standardized field mapping

**Updated Elements:**
- `character-level` - Player level
- `current-xp` - Current experience points
- `next-level-xp` - XP needed for next level
- `level-progress-bar` - Progress bar (percentage)
- `current-health` - Current health points
- `max-health` - Maximum health points
- `streak-days` - Current streak count

### 3. Quest App (`js/core/quest-app.js`)
**Responsibility:** Main application initialization and service coordination

**Key Features:**
- Initializes all core services
- Wires up event connections between services
- Provides global access point for services
- Handles app-wide configuration

## Data Flow

```
┌─────────────────┐    loadStats()    ┌─────────────────┐
│   Stats Service │◄─────────────────►│   Backend API   │
│                 │                   │  /api/user-stats│
└─────────┬───────┘                   └─────────────────┘
          │
          │ notifyConsumers(stats)
          │
    ┌─────▼─────┬─────────┬─────────────┐
    │           │         │             │
┌───▼───┐  ┌───▼───┐ ┌───▼───┐    ┌───▼────┐
│DOM    │  │Task   │ │Vue    │    │Page    │
│Updater│  │Manager│ │Store  │    │Scripts │
└───────┘  └───────┘ └───────┘    └────────┘
```

## File Structure

```
quest/
├── CODEBASE_ARCHITECTURE.md     # This documentation
├── js/
│   ├── core/                    # Core services (new architecture)
│   │   ├── stats-service.js     # Stats management
│   │   ├── dom-updater.js       # DOM updates
│   │   └── quest-app.js         # Main initialization
│   ├── task-list-manager.js     # Task/quest management
│   ├── navigation.js            # Navigation system
│   ├── quests-page.js          # Quest page logic
│   ├── achievements-page.js    # Achievements page logic
│   ├── progress-page.js        # Progress page logic
│   └── adventure-map.js        # Adventure world interface
├── src/                        # Vue.js components
│   ├── services/
│   │   └── api.js              # API utilities
│   └── store/
│       └── modules/
│           └── quest.js        # Vue store module
├── lib/
│   └── Controller/
│       ├── PageController.php  # Page routing & script loading
│       └── SimpleQuestController.php # API endpoints
└── templates/                  # PHP templates
```

## API Endpoints

### `/apps/quest/api/user-stats` (Primary Stats Endpoint)
**Method:** GET  
**Purpose:** Returns complete user statistics  
**Response:** User level, XP, streaks, task counts, achievements  
**Used by:** StatsService for all stats operations

### `/apps/quest/api/debug-db` (Debug Endpoint)
**Method:** GET  
**Purpose:** Database debugging information  
**Used by:** Development debugging only

## Field Name Standards

**Consistent field names used throughout the application:**

| Field | Standard Name | Description |
|-------|---------------|-------------|
| Experience Points | `xp` | Current experience points |
| XP for Next Level | `xp_to_next` | XP needed to reach next level |
| Level Progress | `progress_percentage` | Percentage progress to next level |
| Health Percentage | `health_percentage` | Health as percentage of max |

**Deprecated field names (do not use):**
- `current_xp` (use `xp`)
- `xp_for_next_level` (use `xp_to_next`)
- `xp_progress` (use `progress_percentage`)

## Key Design Decisions

### 1. Single Source of Truth Pattern
All stats data flows through the StatsService. No page or component should make direct API calls for stats.

**Benefits:**
- Eliminates duplicate API calls
- Prevents data inconsistencies
- Centralized caching
- Easier to debug

### 2. Consumer Registration Pattern
Pages and components register as consumers to receive stats updates rather than polling.

**Benefits:**
- Decoupled architecture
- Automatic updates when stats change
- No manual coordination needed

### 3. Centralized DOM Updates
Only the DomUpdater modifies stats-related DOM elements.

**Benefits:**
- Prevents update conflicts
- Consistent field mapping
- Single point of failure/debugging

## Common Issues & Solutions

### Issue: Stats show wrong values initially then correct themselves
**Cause:** Multiple systems trying to update the same DOM elements  
**Solution:** Ensure only DomUpdater modifies stats elements

### Issue: XP shows 0 instead of actual value
**Cause:** Field name mismatch (using deprecated field names)  
**Solution:** Use standardized field names (`xp` not `current_xp`)

### Issue: Stats don't update after completing tasks
**Cause:** StatsService not refreshing or consumers not registered  
**Solution:** Check consumer registration and ensure API is being called

### Issue: Different stats on different pages
**Cause:** Pages using different API endpoints or cached data  
**Solution:** Ensure all pages use StatsService, check cache expiration

## Performance Considerations

### Caching Strategy
- Stats cached for 5 minutes to reduce API calls
- Cache automatically refreshed when needed
- Manual cache clearing available for debugging

### API Optimization
- Single API endpoint for all stats reduces requests
- Batch updates to DOM elements
- Event-driven updates only when data changes

## Development Workflow

### Adding New Stats Display
1. Add stats data to backend API response
2. Update DomUpdater to handle new elements
3. Register page as StatsService consumer
4. Test across all pages

### Debugging Stats Issues
1. Check browser console for API errors
2. Use `QuestDebug.getCurrentStats()` to inspect data
3. Verify DOM element IDs exist
4. Check field name consistency

### Testing Checklist
- [ ] Dashboard shows correct stats
- [ ] Quests page shows correct stats  
- [ ] Achievements page shows correct stats
- [ ] Progress page shows correct stats
- [ ] Adventure page shows correct stats
- [ ] Stats persist between page changes
- [ ] No console errors
- [ ] Performance is acceptable

## Migration Notes

### From Legacy Architecture (Pre-2025)
The previous architecture had multiple competing systems:
- nextcloud-quest-unified.js (monolithic file)
- shared-stats.js (deprecated wrapper)
- Multiple page-specific stats loaders

**Key improvements in new architecture:**
- Single responsibility principle
- No duplicate functionality
- Consistent field names
- Better error handling
- Improved performance

### Breaking Changes
- `window.QuestUnified` replaced with `window.QuestApp`
- Direct StatsManager access deprecated
- Page-specific stats loading removed

## Future Considerations

### Planned Improvements
- TypeScript migration for better type safety
- Real-time stats updates via WebSockets
- Offline functionality with service workers
- Performance monitoring and metrics

### Extension Points
- New consumer types can easily register with StatsService
- DomUpdater can be extended for new element types
- Additional core services can follow same pattern

---

**Last Updated:** January 2025  
**Architecture Version:** 2.0  
**Compatible with:** Nextcloud 25+