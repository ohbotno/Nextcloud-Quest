# Adventure Path System - Implementation Plan

**Feature**: Mario-style World Map with Boss Levels for Nextcloud Quest  
**Created**: 2025-01-10  
**Status**: Planning Complete - Ready for Implementation  

## 🎯 Feature Overview

A procedurally generated 2D side-scrolling world map (Mario Bros 3 style) organized into themed worlds with boss levels. Players navigate through levels that correspond to specific task objectives, building toward epic boss challenges that are the same for all players globally.

---

## 📋 Design Decisions (From User Requirements)

### Core Mechanics
- ✅ **Path Structure**: Diamond patterns with reconvergence points, organized into 8-12 level worlds
- ✅ **Movement**: Mario Bros 3 style - forward progression only, no backtracking
- ✅ **Objectives**: Mixed complexity (simple → complex → boss levels with epic challenges)
- ✅ **Failure Handling**: Auto-regenerate objectives if tasks become unavailable
- ✅ **Visual Style**: 2D side-view scrolling map with themed levels and boss castles
- ✅ **Map Visibility**: Full visibility of current world, boss castle visible at end
- ✅ **Branching**: Smart branching (2-4 paths) based on available task types
- ✅ **Integration**: User choice between normal dashboard mode and adventure mode

### World & Boss System
- ✅ **World Length**: 8-12 levels per world (like Mario)
- ✅ **Boss Challenges**: Mixed epic challenges, more difficult than regular levels
- ✅ **Mini-Boss Placement**: Variable placement within each world
- ✅ **Boss Consistency**: Same objectives globally for all players
- ✅ **Future Feature**: Seasonal bosses (planned for later versions)

---

## 🌍 World Structure & Themes

### World Progression (8 Worlds Total)
1. **Grassland/Personal** - Village themes, personal task focus
2. **Desert/Work** - Office/pyramid themes, work task focus  
3. **Mountain/Fitness** - Athletic themes, health/gym task focus
4. **Forest/Creative** - Magical themes, creative task focus
5. **Ice/Discipline** - Castle themes, habit/routine task focus
6. **Sky/Social** - Cloud themes, social/relationship task focus
7. **Volcano/Urgent** - Fire themes, overdue/urgent task focus
8. **Dark/Master** - Final world, mixed epic challenges

### Level Types & Themes
- **Gym/Fitness Tasks**: Mountain/Athletic themed levels
- **Work Tasks**: Castle/Office building themes  
- **Personal Tasks**: Village/House themes
- **Creative Tasks**: Art studio/Magical themes
- **Overdue Tasks**: Dark/Spooky themes
- **High Priority**: Boss castle levels

---

## 🎮 Boss System Specifications

### Boss Level Examples
- **World 1 Boss**: "Complete 10 personal tasks in 5 days"
- **World 3 Boss**: "Achieve 10-day fitness streak"  
- **World 7 Boss**: "Clear all overdue tasks and maintain 0 overdue for 3 days"
- **World 8 Boss**: "Master Challenge - Complete 25 tasks across all categories in 7 days"

### Mini-Boss Examples
- "Complete 5 tasks today"
- "Finish 3 different task list categories"
- "Complete all high-priority tasks"

---

## 💾 Current Codebase Context

### Existing Key Files (for Integration)
- `quest/js/task-list-manager.js` - Main task management logic
- `quest/lib/Controller/SimpleQuestController.php` - Backend task completion
- `quest/css/nextcloud-quest-unified.css` - Main stylesheet
- `quest/templates/index.php` - Main dashboard template
- `quest/lib/Service/XPService.php` - XP calculation system

### Current Database Tables
- `*PREFIX*quest_user_data` - User XP, level, streaks
- Task data comes from Nextcloud Tasks API

### Current XP Values
- High Priority: 30 XP
- Medium Priority: 20 XP  
- Low Priority: 10 XP
- No Priority: 15 XP

---

## 🚀 Implementation Plan

### Phase 1: Core World Generation System
- [ ] Create `WorldGenerator.php` - World and boss level generation
- [ ] Create `PathGenerator.php` - Algorithm for generating paths within worlds
- [ ] Create `LevelObjective.php` - Level goal management and auto-regeneration
- [ ] Implement diamond pattern path generation with world boundaries
- [ ] Create boss level placement and global objective system
- [ ] Implement mini-boss random placement algorithm
- [ ] Set up auto-regeneration for regular levels (bosses stay fixed)

#### Database Changes - Phase 1
- [ ] Create `adventure_worlds` table for world definitions and themes
- [ ] Create `adventure_boss_levels` table for global boss challenges  
- [ ] Create `adventure_mini_bosses` table for mini-boss definitions
- [ ] Create `adventure_paths` table for generated paths with world_id references
- [ ] Create `adventure_levels` table for individual level data with boss_type field
- [ ] Create `adventure_objectives` table for level goals

### Phase 2: Visual World Map System
- [ ] Create `adventure-map.js` - Frontend map rendering and interaction  
- [ ] Create `adventure-map.css` - Styling for map interface
- [ ] Implement 2D side-scrolling canvas with world boundaries
- [ ] Create level node rendering system with themes
- [ ] Implement boss castle rendering at world end
- [ ] Create mini-boss fortress rendering
- [ ] Add path connection visualization
- [ ] Implement player position tracking
- [ ] Add smooth scrolling and navigation
- [ ] Create world transition animations

#### Templates - Phase 2
- [ ] Create `adventure-map.php` - Template for adventure mode interface
- [ ] Update `index.php` to include adventure mode toggle
- [ ] Create world-specific CSS theme classes

### Phase 3: Boss Challenge System  
- [ ] Create `BossManager.php` - Global boss challenge management
- [ ] Create global boss objective database with predefined challenges
- [ ] Implement boss completion tracking across all players
- [ ] Create mini-boss challenge generation system
- [ ] Design epic reward system for boss completion
- [ ] Create boss defeat mechanics and validation
- [ ] Implement boss leaderboards/statistics (optional)

#### Database Changes - Phase 3
- [ ] Create `adventure_boss_completions` table for global boss completion stats
- [ ] Create `adventure_player_progress` table for world progression tracking
- [ ] Add boss completion timestamps and statistics

### Phase 4: World Progression & Integration
- [ ] Create `AdventureWorldController.php` - World progression and unlocking
- [ ] Implement world completion requirements
- [ ] Create world unlock progression system
- [ ] Add achievement integration for world/boss completion
- [ ] Create world completion ceremonies/animations
- [ ] Implement mode toggle with world save states
- [ ] Add user preference settings for adventure mode

#### API Endpoints - Phase 4
- [ ] `GET /apps/quest/api/adventure/worlds` - Get available worlds
- [ ] `GET /apps/quest/api/adventure/current-path` - Get current path and position
- [ ] `POST /apps/quest/api/adventure/complete-level` - Complete level objective
- [ ] `GET /apps/quest/api/adventure/boss-challenge` - Get current boss challenge
- [ ] `POST /apps/quest/api/adventure/complete-boss` - Complete boss level
- [ ] `GET /apps/quest/api/adventure/progress` - Get world progression stats

### Phase 5: Enhancement Features & Polish
- [ ] Create bonus reward system for path completion
- [ ] Add special animations for boss battles
- [ ] Implement seasonal boss framework (future expansion)
- [ ] Create path statistics and analytics
- [ ] Add sound effects and visual polish
- [ ] Performance optimization for large world maps
- [ ] Mobile responsiveness for map interface

---

## 🔧 Technical Integration Points

### Integration with Existing Systems
- [ ] Modify `task-list-manager.js` to support adventure mode completion
- [ ] Update `SimpleQuestController.php` to track adventure progress
- [ ] Integrate with existing XP system in `XPService.php`
- [ ] Connect with achievement system
- [ ] Ensure streak tracking works with adventure mode
- [ ] Update settings page to include adventure mode toggle

### User Experience Flow
1. [ ] User enables Adventure Mode from settings
2. [ ] System generates World 1 with themed levels based on current tasks
3. [ ] User sees 2D map with level nodes representing objectives
4. [ ] User chooses paths at branching points (smart branching)
5. [ ] Completing real Nextcloud tasks progresses through levels
6. [ ] Mini-boss appears randomly in world, must be defeated to continue
7. [ ] Boss castle appears at end of world with epic global challenge
8. [ ] Defeating boss unlocks next world with new theme
9. [ ] Paths auto-regenerate as tasks change (except boss levels)
10. [ ] User can switch back to normal dashboard anytime

---

## 🧪 Testing Strategy

### Unit Tests
- [ ] Test path generation algorithms
- [ ] Test objective auto-regeneration
- [ ] Test boss challenge validation  
- [ ] Test world progression logic

### Integration Tests
- [ ] Test adventure mode with existing task system
- [ ] Test XP integration with adventure completion
- [ ] Test achievement triggers from world completion
- [ ] Test mode switching (dashboard ↔ adventure)

### User Testing
- [ ] Test map navigation and usability
- [ ] Test objective clarity and motivation
- [ ] Test boss challenge difficulty and fairness
- [ ] Test overall engagement and fun factor

---

## 📊 Success Metrics

### Engagement Metrics
- [ ] Track adventure mode adoption rate
- [ ] Monitor time spent in adventure mode vs dashboard
- [ ] Measure task completion rate improvement
- [ ] Track boss level completion rates

### User Satisfaction  
- [ ] User feedback on adventure mode enjoyment
- [ ] Feature usage analytics
- [ ] Task completion streak improvements
- [ ] Overall app retention metrics

---

## 🔮 Future Enhancements (Post-Launch)

### Seasonal Boss System
- [ ] Monthly/quarterly boss rotations
- [ ] Special event bosses
- [ ] Collaborative boss challenges
- [ ] Boss difficulty scaling

### Advanced Features
- [ ] Multiplayer world exploration
- [ ] Custom world creation
- [ ] Achievement sharing
- [ ] World completion leaderboards
- [ ] Mobile app integration

---

## 📝 Implementation Notes

### Key Considerations
- Ensure adventure mode doesn't break existing functionality
- Maintain performance with large generated maps
- Keep boss challenges achievable but challenging
- Provide clear visual feedback for progress
- Allow easy switching between modes
- Consider mobile users in map design

### Development Priority
1. Core path generation (backend)
2. Basic map visualization (frontend)  
3. Level completion integration
4. Boss system implementation
5. Polish and optimization

---

**Ready to implement when development begins!**  
This plan provides complete context for picking up Adventure Path System development at any time.