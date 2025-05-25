<?php
// public/api/index.php

// Set content type to JSON for all API responses
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Or specify allowed origins
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight OPTIONS requests (for CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204); // No Content
    exit;
}

require_once '../../config/config.php';
require_once APP_ROOT . '/src/includes/db_connection.php'; // $pdo will be available
// Models will be included by specific endpoint handlers as needed.
// require_once APP_ROOT . '/src/models/User.php';
// require_once APP_ROOT . '/src/models/Claim.php';

// --- API Authentication (Conceptual) ---
function authenticate_api_request() {
    // For a real API, you'd implement token-based authentication here.
    // Example: Check for an 'Authorization: Bearer <token>' header.
    // $headers = getallheaders(); // getallheaders() might not be available on all server setups
    // if (!function_exists('getallheaders')) {
    //     function getallheaders() {
    //         $headers = [];
    //         foreach ($_SERVER as $name => $value) {
    //             if (substr($name, 0, 5) == 'HTTP_') {
    //                 $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
    //             }
    //         }
    //         return $headers;
    //     }
    // }
    // $headers = getallheaders();

    // if (isset($headers['Authorization'])) {
    //     $auth_header = $headers['Authorization'];
    //     if (preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
    //         $token = $matches[1];
    //         // Validate the token (e.g., against a database of API keys or by verifying a JWT)
    //         // global $pdo; // Assuming $pdo is available from db_connection.php
    //         // $userModel = new User($pdo); // Assuming User model has validateApiToken
    //         // $user = $userModel->validateApiToken($token); // Hypothetical method
    //         // if ($user) return $user; // Return user details if valid
    //     }
    // }
    // For this basic structure, we'll bypass actual authentication for placeholder endpoints.
    // In a real scenario, unauthenticated requests would be rejected.
    // http_response_code(401); // Unauthorized
    // echo json_encode(['error' => 'Authentication required.']);
    // exit;
    
    // For now, let's return a dummy user ID or true if we want to simulate auth success
    // return ['user_id' => 1, 'role' => 'APIUser']; // Example authenticated user
    return true; // Simulate successful authentication for now
}

// Basic request parsing using query parameters
$resource = $_GET['resource'] ?? null;
$resource_id = $_GET['id'] ?? null;
$method = $_SERVER['REQUEST_METHOD'];

// --- Authenticate all API requests (except perhaps specific public ones) ---
$authenticated_user_context = authenticate_api_request(); // Renamed to avoid conflict if it returned an array
// if (!$authenticated_user_context) {
//     // Authentication function, if it failed and didn't exit, would have set 401.
//     // We ensure that if it returns false, we exit.
//     // (Current authenticate_api_request exits on failure or returns true)
//     // This is more of a logical placeholder for a more complex auth function.
//     if (http_response_code() !== 401) { // If auth didn't set its own response code
//        http_response_code(401);
//        echo json_encode(['error' => 'Authentication failed.']);
//     }
//     exit;
// }


// --- API Endpoint Routing (Placeholders) ---
$response_data = ['error' => 'Invalid API endpoint or method.']; // Renamed to avoid conflict
http_response_code(404); // Not Found by default

switch ($resource) {
    case 'claims':
        // Conceptual:
        // require_once APP_ROOT . '/src/api/handlers/claim_handler.php';
        // $handler_response = ClaimHandler::handle($pdo, $method, $resource_id, file_get_contents('php://input'), $authenticated_user_context);
        // http_response_code($handler_response['status_code'] ?? 200);
        // unset($handler_response['status_code']); 
        // $response_data = $handler_response;
        
        // Placeholder for 'claims' endpoint
        if ($method === 'GET') {
            if ($resource_id) {
                $response_data = ['message' => "Placeholder: GET claim with ID $resource_id", 'data' => ['id' => (int)$resource_id, 'details' => 'Claim details for ID ' . (int)$resource_id]];
            } else {
                $response_data = ['message' => "Placeholder: GET all claims", 'data' => [['id' => 1, 'details' => 'Claim 1 details'], ['id' => 2, 'details' => 'Claim 2 details']]];
            }
            http_response_code(200);
        } elseif ($method === 'POST') {
            // $input_data = json_decode(file_get_contents('php://input'), true);
            $response_data = ['message' => "Placeholder: POST (create) new claim", 'data' => ['id' => rand(100,200), 'status' => 'created' /*, 'received_data' => $input_data */]];
            http_response_code(201); // Created
        } else {
             http_response_code(405); // Method Not Allowed
             $response_data = ['error' => 'Method not allowed for claims resource.'];
        }
        break;

    case 'users':
        // Conceptual:
        // require_once APP_ROOT . '/src/api/handlers/user_handler.php';
        // $handler_response = UserHandler::handle($pdo, $method, $resource_id, file_get_contents('php://input'), $authenticated_user_context);
        // http_response_code($handler_response['status_code'] ?? 200);
        // unset($handler_response['status_code']);
        // $response_data = $handler_response;

        // Placeholder for 'users' endpoint
        if ($method === 'GET') {
             if ($resource_id) {
                $response_data = ['message' => "Placeholder: GET user with ID $resource_id", 'data' => ['id' => (int)$resource_id, 'name' => 'John Doe']];
            } else {
                // In a real scenario, this would check if $authenticated_user_context has admin rights
                $response_data = ['message' => "Placeholder: GET all users (admin only)", 'data' => [['id' => 1, 'name' => 'John Doe'], ['id' => 2, 'name' => 'Jane Smith']]];
            }
            http_response_code(200);
        } else {
             http_response_code(405); // Method Not Allowed
             $response_data = ['error' => 'Method not allowed for users resource.'];
        }
        break;
    
    case 'ping': // A simple public endpoint for testing
        $response_data = ['message' => 'pong', 'timestamp' => time()];
        http_response_code(200);
        break;

    // Add more resource cases here (e.g., 'documents', 'quotas')
    default:
        // $response_data is already set to error, http_response_code to 404
        if ($resource === null) { // If no resource specified at all
             $response_data = ['error' => 'API resource not specified. Use ?resource=resource_name'];
        }
        break;
}

echo json_encode($response_data);
exit;
?>
