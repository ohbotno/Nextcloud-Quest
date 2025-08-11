<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

namespace OCA\NextcloudQuest\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\IInitialStateService;
use OCP\IUserSession;
use OCP\Util;

class PageController extends Controller {
    /** @var IInitialStateService */
    private $initialStateService;
    /** @var IUserSession */
    private $userSession;
    
    public function __construct(
        $appName,
        IRequest $request,
        IInitialStateService $initialStateService,
        IUserSession $userSession
    ) {
        parent::__construct($appName, $request);
        $this->initialStateService = $initialStateService;
        $this->userSession = $userSession;
    }
    
    /**
     * Main page
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     * @return TemplateResponse
     */
    public function index() {
        $user = $this->userSession->getUser();
        
        // Add CSS and JavaScript files
        Util::addStyle('quest', 'nextcloud-quest-unified');
        Util::addScript('quest', 'nextcloud-quest-unified');
        Util::addScript('quest', 'navigation');
        Util::addScript('quest', 'task-list-manager');
        
        // Provide initial state for the frontend
        $this->initialStateService->provideInitialState(
            'quest',
            'user',
            [
                'uid' => $user->getUID(),
                'displayName' => $user->getDisplayName()
            ]
        );
        
        // Provide app configuration as initial state
        $this->initialStateService->provideInitialState(
            'quest',
            'config',
            [
                'active_page' => 'dashboard',
                'language' => \OC::$server->getL10NFactory()->get('quest')->getLanguageCode()
            ]
        );
        
        // Pass template variables
        $templateVars = [
            'active_page' => 'dashboard',
            'user_displayname' => $user->getDisplayName(),
            'language' => \OC::$server->getL10NFactory()->get('quest')->getLanguageCode()
        ];
        
        return new TemplateResponse('quest', 'index', $templateVars);
    }
    
    /**
     * Dedicated quests page
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     * @return TemplateResponse
     */
    public function quests() {
        $user = $this->userSession->getUser();
        
        // Add CSS and JavaScript files
        Util::addStyle('quest', 'nextcloud-quest-unified');
        Util::addScript('quest', 'nextcloud-quest-unified');
        Util::addScript('quest', 'navigation');
        Util::addScript('quest', 'task-list-manager');
        
        // Provide initial state for the frontend
        $this->initialStateService->provideInitialState(
            'quest',
            'user',
            [
                'uid' => $user->getUID(),
                'displayName' => $user->getDisplayName()
            ]
        );
        
        // Provide app configuration as initial state
        $this->initialStateService->provideInitialState(
            'quest',
            'config',
            [
                'active_page' => 'quests',
                'language' => \OC::$server->getL10NFactory()->get('quest')->getLanguageCode()
            ]
        );
        
        // Pass template variables
        $templateVars = [
            'active_page' => 'quests',
            'user_displayname' => $user->getDisplayName(),
            'language' => \OC::$server->getL10NFactory()->get('quest')->getLanguageCode()
        ];
        
        return new TemplateResponse('quest', 'quests', $templateVars);
    }
    
    /**
     * Dedicated achievements page
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     * @return TemplateResponse
     */
    public function achievements() {
        $user = $this->userSession->getUser();
        
        // Add CSS and JavaScript files
        Util::addStyle('quest', 'nextcloud-quest-unified');
        Util::addScript('quest', 'nextcloud-quest-unified');
        Util::addScript('quest', 'navigation');
        Util::addScript('quest', 'task-list-manager');
        
        // Provide initial state for the frontend
        $this->initialStateService->provideInitialState(
            'quest',
            'user',
            [
                'uid' => $user->getUID(),
                'displayName' => $user->getDisplayName()
            ]
        );
        
        // Provide app configuration as initial state
        $this->initialStateService->provideInitialState(
            'quest',
            'config',
            [
                'active_page' => 'achievements',
                'language' => \OC::$server->getL10NFactory()->get('quest')->getLanguageCode()
            ]
        );
        
        // Pass template variables
        $templateVars = [
            'active_page' => 'achievements',
            'user_displayname' => $user->getDisplayName(),
            'language' => \OC::$server->getL10NFactory()->get('quest')->getLanguageCode()
        ];
        
        return new TemplateResponse('quest', 'achievements', $templateVars);
    }
    
    /**
     * Dedicated progress tracking page
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     * @return TemplateResponse
     */
    public function progress() {
        $user = $this->userSession->getUser();
        
        // Add CSS and JavaScript files
        Util::addStyle('quest', 'nextcloud-quest-unified');
        Util::addScript('quest', 'nextcloud-quest-unified');
        Util::addScript('quest', 'navigation');
        Util::addScript('quest', 'task-list-manager');
        
        // Provide initial state for the frontend
        $this->initialStateService->provideInitialState(
            'quest',
            'user',
            [
                'uid' => $user->getUID(),
                'displayName' => $user->getDisplayName()
            ]
        );
        
        // Provide app configuration as initial state
        $this->initialStateService->provideInitialState(
            'quest',
            'config',
            [
                'active_page' => 'progress',
                'language' => \OC::$server->getL10NFactory()->get('quest')->getLanguageCode()
            ]
        );
        
        // Pass template variables
        $templateVars = [
            'active_page' => 'progress',
            'user_displayname' => $user->getDisplayName(),
            'language' => \OC::$server->getL10NFactory()->get('quest')->getLanguageCode()
        ];
        
        return new TemplateResponse('quest', 'progress', $templateVars);
    }
    
    /**
     * Adventure Map page
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     * @return TemplateResponse
     */
    public function adventure() {
        $user = $this->userSession->getUser();
        
        // Add CSS and JavaScript files
        Util::addStyle('quest', 'nextcloud-quest-unified');
        Util::addStyle('quest', 'adventure-map');
        Util::addScript('quest', 'nextcloud-quest-unified');
        Util::addScript('quest', 'navigation');
        Util::addScript('quest', 'task-list-manager');
        Util::addScript('quest', 'adventure-map');
        
        // Provide initial state for the frontend
        $this->initialStateService->provideInitialState(
            'quest',
            'user',
            [
                'uid' => $user->getUID(),
                'displayName' => $user->getDisplayName()
            ]
        );
        
        // Provide app configuration as initial state
        $this->initialStateService->provideInitialState(
            'quest',
            'config',
            [
                'active_page' => 'adventure',
                'language' => \OC::$server->getL10NFactory()->get('quest')->getLanguageCode()
            ]
        );
        
        // Pass template variables
        $templateVars = [
            'active_page' => 'adventure',
            'user_displayname' => $user->getDisplayName(),
            'language' => \OC::$server->getL10NFactory()->get('quest')->getLanguageCode()
        ];
        
        return new TemplateResponse('quest', 'adventure', $templateVars);
    }

    /**
     * Dedicated settings page
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     * @return TemplateResponse
     */
    public function settings() {
        $user = $this->userSession->getUser();
        
        // Add CSS and JavaScript files
        Util::addStyle('quest', 'nextcloud-quest-unified');
        Util::addScript('quest', 'nextcloud-quest-unified');
        Util::addScript('quest', 'navigation');
        Util::addScript('quest', 'task-list-manager');
        
        // Provide initial state for the frontend
        $this->initialStateService->provideInitialState(
            'quest',
            'user',
            [
                'uid' => $user->getUID(),
                'displayName' => $user->getDisplayName()
            ]
        );
        
        // Provide app configuration as initial state
        $this->initialStateService->provideInitialState(
            'quest',
            'config',
            [
                'active_page' => 'settings',
                'language' => \OC::$server->getL10NFactory()->get('quest')->getLanguageCode()
            ]
        );
        
        // Pass template variables
        $templateVars = [
            'active_page' => 'settings',
            'user_displayname' => $user->getDisplayName(),
            'language' => \OC::$server->getL10NFactory()->get('quest')->getLanguageCode()
        ];
        
        return new TemplateResponse('quest', 'settings', $templateVars);
    }
}