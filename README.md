# Nextcloud Quest

A gamification app for Nextcloud that transforms task management into an RPG-like experience.

## Features

- **XP & Leveling**: Earn experience points and level up by completing tasks
- **Achievements & Streaks**: Unlock achievements and maintain daily streaks
- **Task Integration**: Works seamlessly with Nextcloud Tasks
- **Dynamic Lists**: Auto-loading tasks with smart overflow management
- **Progress Tracking**: Monitor daily, weekly, and total task completion

## Installation

1. Clone into your Nextcloud apps directory:
```bash
cd /path/to/nextcloud/apps
git clone https://github.com/ohbotno/Nextcloud-Quest.git quest
```

2. Enable the app:
```bash
sudo -u www-data php /path/to/nextcloud/occ app:enable quest
```

## Requirements

- Nextcloud 27+
- Nextcloud Tasks app
- PHP 8.0+

## Usage

1. Open Quest from your Nextcloud apps menu
2. Go to Settings to select task lists for your quest
3. Complete tasks to earn XP and level up
4. Track your progress on the dashboard

## License

AGPL-3.0

## Support

Report issues on [GitHub](https://github.com/ohbotno/Nextcloud-Quest/issues).