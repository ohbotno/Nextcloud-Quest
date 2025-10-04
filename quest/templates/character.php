<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

script('quest', 'character-page');

// Define the main content for the character page
ob_start();
?>

<div class="character-page">
    <div class="page-header">
        <h1 class="page-title">
            <span class="page-icon">🎨</span>
            Character Customization
        </h1>
        <p class="page-description">
            Customize your character's appearance, equipment, and unlock new items as you level up!
        </p>
    </div>

    <div class="character-content">
        <!-- Character Preview Section -->
        <div class="character-preview-section">
            <div class="preview-card">
                <div class="card-header">
                    <h2>Your Character</h2>
                    <div class="character-level-badge">
                        Level <span id="character-level-display">1</span>
                    </div>
                </div>

                <div class="character-display">
                    <div id="character-avatar-large" class="character-avatar-large">
                        <!-- Character will be rendered here by JavaScript -->
                    </div>

                    <div class="character-info">
                        <div class="info-row">
                            <span class="info-label">Current Age:</span>
                            <span class="info-value" id="current-age-name">Stone Age</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Next Age:</span>
                            <span class="info-value" id="next-age-name">Bronze Age (Level 10)</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Items Unlocked:</span>
                            <span class="info-value"><span id="unlocked-count">0</span> / <span id="total-count">70</span></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Equipment Section -->
        <div class="equipment-section">
            <div class="equipment-card">
                <div class="card-header">
                    <h2>Equipment</h2>
                </div>

                <div class="equipment-slots">
                    <!-- Equipment Slot: Clothing -->
                    <div class="equipment-slot" data-slot="clothing">
                        <div class="slot-icon">👕</div>
                        <div class="slot-details">
                            <div class="slot-name">Clothing</div>
                            <div class="slot-equipped" id="equipped-clothing">
                                <span class="equipped-label">None</span>
                            </div>
                        </div>
                        <button class="slot-button" id="change-clothing">Change</button>
                    </div>

                    <!-- Equipment Slot: Weapon -->
                    <div class="equipment-slot" data-slot="weapon">
                        <div class="slot-icon">⚔️</div>
                        <div class="slot-details">
                            <div class="slot-name">Weapon</div>
                            <div class="slot-equipped" id="equipped-weapon">
                                <span class="equipped-label">None</span>
                            </div>
                        </div>
                        <button class="slot-button" id="change-weapon">Change</button>
                    </div>

                    <!-- Equipment Slot: Accessory -->
                    <div class="equipment-slot" data-slot="accessory">
                        <div class="slot-icon">📿</div>
                        <div class="slot-details">
                            <div class="slot-name">Accessory</div>
                            <div class="slot-equipped" id="equipped-accessory">
                                <span class="equipped-label">None</span>
                            </div>
                        </div>
                        <button class="slot-button" id="change-accessory">Change</button>
                    </div>

                    <!-- Equipment Slot: Headgear -->
                    <div class="equipment-slot" data-slot="headgear">
                        <div class="slot-icon">👑</div>
                        <div class="slot-details">
                            <div class="slot-name">Headgear</div>
                            <div class="slot-equipped" id="equipped-headgear">
                                <span class="equipped-label">None</span>
                            </div>
                        </div>
                        <button class="slot-button" id="change-headgear">Change</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Items Grid Section -->
    <div class="items-section">
        <div class="section-header">
            <h2>Available Items</h2>
            <div class="filter-tabs">
                <button class="filter-tab active" data-filter="all">All</button>
                <button class="filter-tab" data-filter="unlocked">Unlocked</button>
                <button class="filter-tab" data-filter="locked">Locked</button>
            </div>
        </div>

        <div class="items-grid" id="items-grid">
            <!-- Items will be loaded here by JavaScript -->
            <div class="loading-placeholder">
                <div class="spinner"></div>
                <p>Loading items...</p>
            </div>
        </div>
    </div>
</div>

<?php
// Capture the content and pass it to the layout
$_['content'] = ob_get_clean();

// Include the unified layout template
include_once __DIR__ . '/layout.php';
