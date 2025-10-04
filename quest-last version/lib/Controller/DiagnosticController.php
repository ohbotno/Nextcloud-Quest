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

/**
 * Ultra-simple diagnostic controller with no dependencies
 */
class DiagnosticController extends Controller {
    
    public function __construct($appName, IRequest $request) {
        parent::__construct($appName, $request);
    }
    
    /**
     * Ultra-simple test endpoint with no dependencies
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     * @return JSONResponse
     */
    public function test() {
        return new JSONResponse([
            'status' => 'success',
            'message' => 'DiagnosticController works!',
            'timestamp' => date('Y-m-d H:i:s'),
            'php_version' => PHP_VERSION,
            'app_loaded' => true
        ]);
    }
    
    /**
     * Ultra-simple POST test endpoint
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     * @return JSONResponse
     */
    public function postTest() {
        return new JSONResponse([
            'status' => 'success',
            'message' => 'DiagnosticController POST works!',
            'method' => 'POST',
            'timestamp' => date('Y-m-d H:i:s'),
            'received_data' => $this->request->getParams()
        ]);
    }
}