<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

namespace OCA\NextcloudQuest\Event;

use OCA\NextcloudQuest\Db\CharacterItem;
use OCP\EventDispatcher\Event;

/**
 * Event dispatched when a user unlocks a character item
 */
class CharacterItemUnlockedEvent extends Event {
    
    /** @var string */
    private $userId;
    
    /** @var CharacterItem */
    private $item;

    public function __construct(string $userId, CharacterItem $item) {
        parent::__construct();
        $this->userId = $userId;
        $this->item = $item;
    }

    /**
     * Get the user ID
     *
     * @return string
     */
    public function getUserId(): string {
        return $this->userId;
    }

    /**
     * Get the unlocked item
     *
     * @return CharacterItem
     */
    public function getItem(): CharacterItem {
        return $this->item;
    }
}