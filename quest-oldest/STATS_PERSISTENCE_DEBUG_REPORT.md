# Stats Persistence Issue - Debug Report

## Problem Description

Stats showed old values most of the time, with correct updated stats appearing momentarily after task completion before reverting to old values.

## Root Cause Analysis

### Primary Issue: API Endpoint Mismatch

The frontend and backend were using different database tables for reading vs writing user stats:

**Task Completion (Write Operation):**
- `QuestController::completeTaskFromList()` writes to `ncquest_users` table
- Updates `lifetime_xp`, `current_xp`, `level` columns in the unified table

**Stats Loading (Read Operation):**
- Frontend calls `/apps/quest/api/user-stats` endpoint  
- `QuestStatsController::getUserStats()` reads from **LEGACY** `quest_user_data` table
- **Never reads from `ncquest_users` table where task completion writes!**

### Code Evidence

**Writing (Task Completion):**
```php
// QuestController::updateSimpleUserXP() - Line 621
$qb->update('ncquest_users')
    ->set('lifetime_xp', $qb->createNamedParameter($xp))
    ->set('current_xp', $qb->createNamedParameter($xp))
```

**Reading (Stats Loading):**
```php
// QuestStatsController::getUserStats() - Line 383  
$qb->select('*')
    ->from('quest_user_data')  // WRONG TABLE!
    ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));
```

**Correct Reading (Unused endpoint):**
```php
// QuestStatsController::getStats() - Line 179
$qb->select('*')
    ->from('ncquest_users')  // CORRECT TABLE!
```

### Available Endpoints

1. `/api/user-stats` → `getUserStats()` - **LEGACY (reads wrong table)**
2. `/api/stats` → `getStats()` - **NEW UNIFIED (reads correct table)**  
3. `/api/user/stats` → `getUserStats()` - **DUPLICATE of legacy**

## Solution Implemented

### 1. Updated Frontend to Use Correct Endpoint

**Changed in `stats-service.js`:**
```javascript
// Before (WRONG):
this.apiEndpoint = '/apps/quest/api/user-stats';

// After (FIXED):
this.apiEndpoint = '/apps/quest/api/stats';
```

**Changed in `api.js`:**
```javascript
// Before (WRONG):
const response = await axios.get(`${this.baseURL}/user-stats`)

// After (FIXED):
const response = await axios.get(`${this.baseURL}/stats`)
```

### 2. Added Data Normalization Layer

The new `/api/stats` endpoint returns a different data structure than the legacy endpoint. Added a `normalizeStatsData()` method to convert new format to legacy format for backward compatibility:

**Key Field Mappings:**
- `current_xp` → `xp`
- `xp_to_next_level` → `xp_to_next`  
- `xp_progress` → `progress_percentage`
- `tasks.completed_today` → `stats.tasks_today`
- `tasks.completed_this_week` → `stats.tasks_this_week`
- `level.lifetime_xp` → `stats.total_xp`

## Files Modified

1. **D:\Nextcloud\Projects\Nextcloud Quest\quest\js\core\stats-service.js**
   - Changed API endpoint from `/user-stats` to `/stats`
   - Added `normalizeStatsData()` method for compatibility

2. **D:\Nextcloud\Projects\Nextcloud Quest\quest\src\services\api.js**
   - Changed API endpoint from `/user-stats` to `/stats`

3. **D:\Nextcloud\Projects\Nextcloud Quest\quest\js\core\quest-app.js**
   - Updated comment for auto-refresh mechanism

## Expected Result

After these changes:
1. **Task completion** writes to `ncquest_users` table
2. **Stats loading** reads from `ncquest_users` table (same table!)
3. **Data consistency** is maintained between writes and reads
4. **Auto-refresh** now shows updated stats instead of reverting to old values

## Testing

Use `debug-stats-endpoints.html` to verify:
1. All endpoints return consistent data
2. Task completion immediately reflects in stats
3. Auto-refresh maintains updated values

## Architecture Notes

The app is transitioning from legacy `quest_user_data` table to unified `ncquest_users` table. This fix completes that transition for the stats loading flow. The legacy endpoint should be marked as deprecated and eventually removed.

## Impact

This fix resolves the core stats persistence issue without breaking existing functionality, using a clean compatibility layer approach that maintains API contract while using the correct data source.