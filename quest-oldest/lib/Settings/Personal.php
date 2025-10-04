<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

namespace OCA\NextcloudQuest\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\Settings\ISettings;

class Personal implements ISettings {
    
    private IConfig $config;
    
    public function __construct(IConfig $config) {
        $this->config = $config;
    }
    
    /**
     * @return TemplateResponse
     */
    public function getForm(): TemplateResponse {
        $parameters = [
            'notifications_enabled' => $this->config->getUserValue(
                \OC::$server->getUserSession()->getUser()->getUID(),
                'nextcloudquest',
                'notifications_enabled',
                'yes'
            ),
            'daily_goal' => $this->config->getUserValue(
                \OC::$server->getUserSession()->getUser()->getUID(),
                'nextcloudquest',
                'daily_goal',
                '3'
            ),
            'theme_preference' => $this->config->getUserValue(
                \OC::$server->getUserSession()->getUser()->getUID(),
                'nextcloudquest',
                'theme_preference',
                'auto'
            )
        ];
        
        return new TemplateResponse('nextcloudquest', 'settings/personal', $parameters);
    }
    
    /**
     * @return string
     */
    public function getSection(): string {
        return 'personal';
    }
    
    /**
     * @return int
     */
    public function getPriority(): int {
        return 50;
    }
}