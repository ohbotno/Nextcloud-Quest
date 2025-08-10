<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

namespace OCA\NextcloudQuest\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

class TestController extends Controller {
    
    public function __construct($appName, IRequest $request) {
        parent::__construct($appName, $request);
    }
    
    /**
     * Minimal test endpoint with zero dependencies
     * 
     * @NoAdminRequired
     * @return JSONResponse
     */
    public function minimal() {
        return new JSONResponse([
            'status' => 'success',
            'message' => 'Minimal controller works!',
            'timestamp' => time()
        ]);
    }
}