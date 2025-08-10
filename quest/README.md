# Quest

**Transform your task management into an engaging game!**

Quest is a gamification extension for Nextcloud Tasks that helps users overcome procrastination by turning task completion into an RPG-like experience. Earn XP, level up, maintain streaks, and unlock achievements as you complete your daily tasks.

## ✨ Features

### 🎮 Core Gamification
- **XP & Levels**: Earn experience points for completing tasks with exponential level progression
- **Achievements**: Unlock 17 different badges for milestones and special accomplishments  
- **Streaks**: Build and maintain daily completion streaks with multiplier bonuses
- **Leaderboards**: Compare your progress with other users (optional)

### 📊 Progress Tracking
- **Visual Progress Bars**: See your XP progress to the next level
- **Statistics Dashboard**: Detailed completion trends and analytics
- **Activity History**: Track all your completed tasks and XP earned
- **Streak Calendar**: Visual representation of your daily activity

### 🎨 Themes & Customization
- **Professional Theme**: Clean, minimal interface for work environments
- **Game Theme**: Colorful, engaging interface with animations and effects
- **User Settings**: Customize notifications, display options, and privacy settings

### 🔔 Smart Notifications
- **Achievement Unlocks**: Celebrate your accomplishments with animated notifications
- **Level Up Alerts**: Special celebrations when you reach new levels
- **Streak Reminders**: Get notified before your streak expires
- **Daily Summaries**: Optional daily progress reports

## 🚀 Installation

### Requirements
- Nextcloud 31.0+ 
- PHP 8.3+
- Nextcloud Tasks app (recommended but not required)

### From the App Store
1. Go to your Nextcloud Apps section
2. Search for "Quest"
3. Click Install
4. Enable the app

### Manual Installation
1. Download the latest release from [GitHub Releases](https://github.com/nextcloud/nextcloud-quest/releases)
2. Extract to your Nextcloud `apps/` directory
3. Run: `sudo -u www-data php occ app:enable nextcloudquest`
4. Access via the Quest icon in your Nextcloud navigation

### Development Setup
```bash
# Clone the repository
git clone https://github.com/nextcloud/nextcloud-quest.git
cd nextcloud-quest

# Install dependencies
composer install
npm install

# Build frontend assets
npm run build

# Enable the app
sudo -u www-data php occ app:enable nextcloudquest
```

## 📖 Usage

### Getting Started
1. Install and enable Quest
2. Navigate to the Quest app from your Nextcloud navigation
3. Complete your first task to start earning XP!

### Task Completion Methods

#### With Nextcloud Tasks (Recommended)
- Tasks completed in the Nextcloud Tasks app automatically award XP
- Priority levels determine bonus XP (High = +10, Medium = +5, Low = +0)

#### Manual Task Entry
- Use the "Complete Task" button in the Quest dashboard
- Enter task description and select priority
- XP is calculated and awarded instantly

### XP Calculation
```
Base XP: 10
Priority Bonus: 0-10 (based on task priority)
Streak Multiplier: 1.0x to 2.0x (10% per consecutive day, capped at 2x)
Total XP = (Base XP + Priority Bonus) × Streak Multiplier
```

### Level Progression
- **Level 1**: 0 XP
- **Level 2**: 100 XP  
- **Level 3**: 250 XP
- **Level 4**: 475 XP
- And so on... (exponential growth using `100 * 1.5^(level-1)`)

### Achievement System
Unlock achievements for various milestones:

**Getting Started**
- First Step: Complete your first task
- Task Initiator: Complete 10 tasks

**Consistency** 
- Week Warrior: 7-day streak
- Monthly Master: 30-day streak
- Century Champion: 100-day streak

**Milestones**
- Rising Star: Reach level 5
- Quest Expert: Reach level 25
- Task Legend: Complete 1000 tasks

**Special**
- Perfect Day: Complete all scheduled tasks in a day
- Early Bird: Complete task before 9 AM
- Night Owl: Complete task after 9 PM
- Speed Demon: Complete 5 tasks in one hour

## ⚙️ Configuration

### User Settings
Access via the Quest dashboard settings:

**Theme Preferences**
- Professional: Clean, minimal interface
- Game: Colorful with animations and effects

**Notifications**
- Achievement unlocks
- Level up celebrations  
- Streak reminders
- Daily summary reports

**Display Options**
- Show XP gain popups
- Show streak counter
- Show level progress
- Compact view mode

**Privacy**
- Show on leaderboard
- Anonymous leaderboard display

### Admin Configuration
No special admin configuration required. The app works out of the box with sensible defaults.

## 🛠️ Development

### Architecture
- **Frontend**: Vue.js 2 with Vuex for state management
- **Backend**: PHP using Nextcloud's app framework
- **Database**: MySQL/PostgreSQL using Nextcloud's query builder
- **Styling**: SCSS with Nextcloud design system

### Key Components
```
lib/
├── Controller/         # API endpoints
├── Service/           # Business logic (XP, Achievements, Streaks)
├── Db/               # Database entities and mappers  
├── Integration/       # Tasks app integration
├── BackgroundJob/     # Maintenance tasks
└── Notification/      # Notification system

src/
├── components/        # Vue.js components
├── store/            # Vuex state management
└── services/         # API communication
```

### Building
```bash
# Development build with watch
npm run dev

# Production build
npm run build

# Linting
npm run lint
composer run cs:check

# Testing  
composer run test:unit
```

### Database Schema
The app creates three main tables:
- `nextcloud_quest_users`: User progress (XP, level, streaks)
- `nextcloud_quest_achievements`: Unlocked achievements
- `nextcloud_quest_history`: Task completion history

## 🤝 Contributing

We welcome contributions! Please see our [Contributing Guide](CONTRIBUTING.md) for details.

### Development Workflow
1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes with tests
4. Run linting and tests (`make ci`)
5. Commit your changes (`git commit -m 'Add amazing feature'`)
6. Push to your branch (`git push origin feature/amazing-feature`)
7. Open a Pull Request

### Code Standards
- PSR-12 for PHP code
- ESLint configuration for JavaScript/Vue
- Comprehensive PHPDoc comments
- Unit tests for business logic

## 🐛 Troubleshooting

### Common Issues

**Quest app not showing XP for completed tasks**
- Ensure the Tasks app is installed and working
- Check that tasks are marked as "completed" not just "done"
- Verify the Quest app has proper database permissions

**Streaks not updating correctly**
- Streaks reset at midnight in your server's timezone
- You have until midnight the next day (grace period) to maintain streaks
- Check background jobs are running (`occ background:cron`)

**Achievements not unlocking**
- Achievement checking runs when tasks are completed
- Some achievements require specific conditions (time of day, etc.)
- Check the notification settings if you're missing unlock notifications

**Performance issues**
- The app is optimized for thousands of tasks and users
- Database queries use proper indexing
- Background jobs handle maintenance tasks efficiently

### Getting Help
- Check the [FAQ](https://github.com/nextcloud/nextcloud-quest/wiki/FAQ)
- Search [existing issues](https://github.com/nextcloud/nextcloud-quest/issues)
- Create a [new issue](https://github.com/nextcloud/nextcloud-quest/issues/new) with:
  - Nextcloud version
  - PHP version  
  - Browser and version
  - Steps to reproduce
  - Error logs (if any)

## 📄 License

This project is licensed under the GNU AGPL v3.0 License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- Nextcloud team for the excellent app framework
- Tasks app developers for integration inspiration  
- Vue.js community for frontend components
- All contributors and beta testers

## 🗺️ Roadmap

### Version 1.1 (Planned)
- [ ] Deck app integration
- [ ] Custom avatars and badges
- [ ] Team/group challenges
- [ ] Export/import progress data

### Version 1.2 (Future)
- [ ] Pomodoro timer integration
- [ ] Daily/weekly challenges
- [ ] Reward shop for spending XP
- [ ] Advanced analytics and insights

---

**Start your quest today and turn productivity into an adventure!** 🚀

---

*Made with ❤️ by the Quest team*