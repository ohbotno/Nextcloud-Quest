<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

namespace OCA\NextcloudQuest\Controller;

use OCA\NextcloudQuest\Controller\Base\BasePageController;
use OCP\AppFramework\Http\TemplateResponse;

class PageController extends BasePageController {
    
    /**
     * Main page
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     * @return TemplateResponse
     */
    public function index() {
        return $this->renderPage('dashboard', 'index', ['dashboard']);
    }
    
    /**
     * Dedicated quests page
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     * @return TemplateResponse
     */
    public function quests() {
        return $this->renderPage('quests', 'quests', ['quests-page']);
    }
    
    /**
     * Dedicated achievements page
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     * @return TemplateResponse
     */
    public function achievements() {
        return $this->renderPage('achievements', 'achievements', ['achievements']);
    }
    
    /**
     * Adventure Map page
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     * @return TemplateResponse
     */
    public function adventure() {
        return $this->renderPage('adventure', 'adventure', ['adventure-map', 'adventure-navigation'], ['adventure-map']);
    }

    /**
     * Dedicated settings page
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     * @return TemplateResponse
     */
    public function settings() {
        return $this->renderPage('settings', 'settings');
    }
}