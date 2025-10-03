<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

namespace OCA\NextcloudQuest\Controller\Base;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\IInitialStateService;
use OCP\IUserSession;
use OCP\Util;

abstract class BasePageController extends Controller {
    /** @var IInitialStateService */
    protected $initialStateService;
    /** @var IUserSession */
    protected $userSession;
    
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
     * Render a page with common initialization
     */
    protected function renderPage(string $pageName, string $templateName, array $additionalScripts = [], array $additionalStyles = []): TemplateResponse {
        $user = $this->userSession->getUser();
        
        // Add common CSS files
        Util::addStyle('quest', 'nextcloud-quest-unified');
        
        // Add additional styles for specific pages
        foreach ($additionalStyles as $style) {
            Util::addStyle('quest', $style);
        }
        
        // Add core architecture scripts (always needed)
        Util::addScript('quest', 'core/stats-service');
        Util::addScript('quest', 'core/dom-updater');
        Util::addScript('quest', 'core/quest-app');
        
        // Add common application layer scripts
        Util::addScript('quest', 'navigation');
        Util::addScript('quest', 'task-list-manager');
        Util::addScript('quest', 'sidebar-character');
        Util::addScript('quest', 'character-customizer');
        
        // Add page-specific scripts
        foreach ($additionalScripts as $script) {
            Util::addScript('quest', $script);
        }
        
        // Provide user state
        $this->initialStateService->provideInitialState(
            'quest',
            'user',
            [
                'uid' => $user->getUID(),
                'displayName' => $user->getDisplayName()
            ]
        );
        
        // Provide app configuration state
        $this->initialStateService->provideInitialState(
            'quest',
            'config',
            [
                'active_page' => $pageName,
                'language' => \OC::$server->getL10NFactory()->get('quest')->getLanguageCode()
            ]
        );
        
        // Prepare template variables
        $templateVars = [
            'active_page' => $pageName,
            'user_displayname' => $user->getDisplayName(),
            'language' => \OC::$server->getL10NFactory()->get('quest')->getLanguageCode()
        ];
        
        return new TemplateResponse('quest', $templateName, $templateVars);
    }
}