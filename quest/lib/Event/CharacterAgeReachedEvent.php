<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

namespace OCA\NextcloudQuest\Event;

use OCA\NextcloudQuest\Db\CharacterAge;
use OCA\NextcloudQuest\Db\CharacterProgression;
use OCP\EventDispatcher\Event;

/**
 * Event dispatched when a user reaches a new character age
 */
class CharacterAgeReachedEvent extends Event {
    
    /** @var string */
    private $userId;
    
    /** @var CharacterAge */
    private $age;
    
    /** @var CharacterProgression */
    private $progression;

    public function __construct(string $userId, CharacterAge $age, CharacterProgression $progression) {
        parent::__construct();
        $this->userId = $userId;
        $this->age = $age;
        $this->progression = $progression;
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
     * Get the reached age
     *
     * @return CharacterAge
     */
    public function getAge(): CharacterAge {
        return $this->age;
    }

    /**
     * Get the progression record
     *
     * @return CharacterProgression
     */
    public function getProgression(): CharacterProgression {
        return $this->progression;
    }
}