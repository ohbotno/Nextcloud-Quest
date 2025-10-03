<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

namespace OCA\NextcloudQuest\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;

/**
 * Character Unlock Entity
 * 
 * @method string getUserId()
 * @method void setUserId(string $userId)
 * @method string getItemKey()
 * @method void setItemKey(string $itemKey)
 * @method \DateTime getUnlockedAt()
 * @method void setUnlockedAt(\DateTime $unlockedAt)
 * @method string getUnlockMethod()
 * @method void setUnlockMethod(string $unlockMethod)
 * @method string|null getUnlockReason()
 * @method void setUnlockReason(?string $unlockReason)
 */
class CharacterUnlock extends Entity implements JsonSerializable {

    /** @var string */
    protected $userId;

    /** @var string */
    protected $itemKey;

    /** @var \DateTime */
    protected $unlockedAt;

    /** @var string */
    protected $unlockMethod;

    /** @var string|null */
    protected $unlockReason;

    // Unlock methods
    public const METHOD_LEVEL = 'level';
    public const METHOD_ACHIEVEMENT = 'achievement';
    public const METHOD_QUEST = 'quest';
    public const METHOD_ADMIN = 'admin';

    public function __construct() {
        $this->addType('unlockedAt', 'datetime');
    }

    /**
     * Get all valid unlock methods
     *
     * @return array
     */
    public static function getValidMethods(): array {
        return [
            self::METHOD_LEVEL,
            self::METHOD_ACHIEVEMENT,
            self::METHOD_QUEST,
            self::METHOD_ADMIN
        ];
    }

    /**
     * Get unlock method icon
     *
     * @return string
     */
    public function getUnlockMethodIcon(): string {
        $icons = [
            self::METHOD_LEVEL => '📈',
            self::METHOD_ACHIEVEMENT => '🏆',
            self::METHOD_QUEST => '📋',
            self::METHOD_ADMIN => '⚙️'
        ];

        return $icons[$this->unlockMethod] ?? '🔓';
    }

    /**
     * Get human-readable unlock method
     *
     * @return string
     */
    public function getUnlockMethodName(): string {
        $names = [
            self::METHOD_LEVEL => 'Level Progression',
            self::METHOD_ACHIEVEMENT => 'Achievement Unlock',
            self::METHOD_QUEST => 'Quest Completion',
            self::METHOD_ADMIN => 'Administrative Grant'
        ];

        return $names[$this->unlockMethod] ?? 'Unknown Method';
    }

    /**
     * Check if unlock is recent (within last 24 hours)
     *
     * @return bool
     */
    public function isRecent(): bool {
        $oneDayAgo = new \DateTime('-1 day');
        return $this->unlockedAt > $oneDayAgo;
    }

    /**
     * Get time since unlock in human-readable format
     *
     * @return string
     */
    public function getTimeSinceUnlock(): string {
        $now = new \DateTime();
        $diff = $now->diff($this->unlockedAt);

        if ($diff->days > 0) {
            return $diff->days . ' day' . ($diff->days > 1 ? 's' : '') . ' ago';
        } elseif ($diff->h > 0) {
            return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
        } elseif ($diff->i > 0) {
            return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
        } else {
            return 'Just now';
        }
    }

    public function jsonSerialize(): array {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'item_key' => $this->itemKey,
            'unlocked_at' => $this->unlockedAt?->format('Y-m-d H:i:s'),
            'unlock_method' => $this->unlockMethod,
            'unlock_reason' => $this->unlockReason,
            'unlock_method_name' => $this->getUnlockMethodName(),
            'unlock_method_icon' => $this->getUnlockMethodIcon(),
            'is_recent' => $this->isRecent(),
            'time_since_unlock' => $this->getTimeSinceUnlock()
        ];
    }
}