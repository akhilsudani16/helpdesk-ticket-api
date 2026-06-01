<?php

/**
 * CRUD Operations Test Script
 * Tests all Create, Read, Update, Delete operations for the Helpdesk API
 */

$baseUrl = 'http://127.0.0.1:8000/api/v1';
$token = null;

// Helper function to make API requests
function apiRequest($method, $endpoint, $data = null, $token = null) {
    global $baseUrl;
    
    $ch = curl_init($baseUrl . $endpoint);
    
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
    ];
    
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'body' => json_decode($response, true)
    ];
}

// Test results
$results = [];
$passed = 0;
$failed = 0;

function test($name, $condition, $details = '') {
    global $results, $passed, $failed;
    
    if ($condition) {
        $results[] = "✓ PASS: $name";
        $passed++;
    } else {
        $results[] = "✗ FAIL: $name" . ($details ? " - $details" : '');
        $failed++;
    }
}

echo "=================================================\n";
echo "  HELPDESK API - CRUD OPERATIONS TEST\n";
echo "=================================================\n\n";

// 1. AUTHENTICATION - CREATE TOKEN
echo "1. Testing Authentication (CREATE)...\n";
$response = apiRequest('POST', '/auth/token', [
    'email' => 'admin@example.com',
    'password' => 'password',
    'device_name' => 'Postman'
]);

test('Auth - Create Token', $response['code'] === 200, "HTTP {$response['code']}");
test('Auth - Token Returned', isset($response['body']['data']['token']), 'No token in response');

if (isset($response['body']['data']['token'])) {
    $token = $response['body']['data']['token'];
    echo "   Token: " . substr($token, 0, 20) . "...\n";
}

echo "\n";

// 2. TICKETS - CREATE (POST)
echo "2. Testing Tickets - CREATE (POST)...\n";
$response = apiRequest('POST', '/tickets', [
    'title' => 'Test Ticket for CRUD',
    'description' => 'This is a test ticket to verify CRUD operations are working correctly.',
    'priority' => 'high'
], $token);

test('Tickets - Create', $response['code'] === 201, "HTTP {$response['code']}");
test('Tickets - Create Response Format', isset($response['body']['data']['id']), 'No ticket ID in response');

$ticketId = $response['body']['data']['id'] ?? null;
if ($ticketId) {
    echo "   Created Ticket ID: $ticketId\n";
}

echo "\n";

// 3. TICKETS - READ (GET Single)
echo "3. Testing Tickets - READ (GET Single)...\n";
if ($ticketId) {
    $response = apiRequest('GET', "/tickets/$ticketId", null, $token);
    
    test('Tickets - Read Single', $response['code'] === 200, "HTTP {$response['code']}");
    test('Tickets - Read Data Structure', isset($response['body']['data']['title']), 'Missing title in response');
    test('Tickets - Read Correct ID', $response['body']['data']['id'] === $ticketId, 'ID mismatch');
} else {
    echo "   Skipped (no ticket ID)\n";
}

echo "\n";

// 4. TICKETS - READ (GET List)
echo "4. Testing Tickets - READ (GET List)...\n";
$response = apiRequest('GET', '/tickets', null, $token);

test('Tickets - List', $response['code'] === 200, "HTTP {$response['code']}");
test('Tickets - List Has Data', isset($response['body']['data']) && is_array($response['body']['data']), 'No data array');
test('Tickets - List Has Pagination', isset($response['body']['meta']), 'No pagination meta');

echo "\n";

// 5. TICKETS - UPDATE (PATCH)
echo "5. Testing Tickets - UPDATE (PATCH)...\n";
if ($ticketId) {
    $response = apiRequest('PATCH', "/tickets/$ticketId", [
        'title' => 'Updated Test Ticket',
        'status' => 'in_progress'
    ], $token);
    
    test('Tickets - Update (PATCH)', $response['code'] === 200, "HTTP {$response['code']}");
    test('Tickets - Update Applied', $response['body']['data']['title'] === 'Updated Test Ticket', 'Title not updated');
    test('Tickets - Status Updated', $response['body']['data']['status'] === 'in_progress', 'Status not updated');
} else {
    echo "   Skipped (no ticket ID)\n";
}

echo "\n";

// 6. TICKETS - REPLACE (PUT)
echo "6. Testing Tickets - REPLACE (PUT)...\n";
if ($ticketId) {
    $response = apiRequest('PUT', "/tickets/$ticketId", [
        'title' => 'Completely Replaced Ticket',
        'description' => 'This ticket has been completely replaced using PUT method.',
        'status' => 'resolved',
        'priority' => 'low',
        'assigned_to' => null
    ], $token);
    
    test('Tickets - Replace (PUT)', $response['code'] === 200, "HTTP {$response['code']}");
    test('Tickets - Replace Applied', $response['body']['data']['title'] === 'Completely Replaced Ticket', 'Title not replaced');
} else {
    echo "   Skipped (no ticket ID)\n";
}

echo "\n";

// 7. COMMENTS - CREATE (POST)
echo "7. Testing Comments - CREATE (POST)...\n";
if ($ticketId) {
    $response = apiRequest('POST', "/tickets/$ticketId/comments", [
        'body' => 'This is a test comment for CRUD verification.',
        'is_internal' => false
    ], $token);
    
    test('Comments - Create', $response['code'] === 201, "HTTP {$response['code']}");
    test('Comments - Create Response', isset($response['body']['data']['id']), 'No comment ID in response');
    
    $commentId = $response['body']['data']['id'] ?? null;
    if ($commentId) {
        echo "   Created Comment ID: $commentId\n";
    }
} else {
    echo "   Skipped (no ticket ID)\n";
}

echo "\n";

// 8. COMMENTS - READ (GET List)
echo "8. Testing Comments - READ (GET List)...\n";
if ($ticketId) {
    $response = apiRequest('GET', "/tickets/$ticketId/comments", null, $token);
    
    test('Comments - List', $response['code'] === 200, "HTTP {$response['code']}");
    test('Comments - List Has Data', isset($response['body']['data']) && is_array($response['body']['data']), 'No data array');
    test('Comments - List Not Empty', count($response['body']['data']) > 0, 'No comments found');
} else {
    echo "   Skipped (no ticket ID)\n";
}

echo "\n";

// 9. FILTERING
echo "9. Testing Filtering...\n";
$response = apiRequest('GET', '/tickets?filter[status]=resolved', null, $token);

test('Filtering - By Status', $response['code'] === 200, "HTTP {$response['code']}");
test('Filtering - Results Filtered', isset($response['body']['data']), 'No data in response');

echo "\n";

// 10. SORTING
echo "10. Testing Sorting...\n";
$response = apiRequest('GET', '/tickets?sort=-created_at', null, $token);

test('Sorting - Descending', $response['code'] === 200, "HTTP {$response['code']}");
test('Sorting - Has Results', isset($response['body']['data']), 'No data in response');

echo "\n";

// 11. MULTI-SORT
echo "11. Testing Multi-Sort...\n";
$response = apiRequest('GET', '/tickets?sort=priority,-created_at', null, $token);

test('Multi-Sort', $response['code'] === 200, "HTTP {$response['code']}");
test('Multi-Sort - Has Results', isset($response['body']['data']), 'No data in response');

echo "\n";

// 12. INCLUDES
echo "12. Testing Conditional Includes...\n";
if ($ticketId) {
    $response = apiRequest('GET', "/tickets/$ticketId?include=customer,comments", null, $token);
    
    test('Includes - Customer', isset($response['body']['data']['customer']), 'Customer not included');
    test('Includes - Comments', isset($response['body']['data']['comments']), 'Comments not included');
} else {
    echo "   Skipped (no ticket ID)\n";
}

echo "\n";

// 13. USERS - READ
echo "13. Testing Users - READ...\n";
$response = apiRequest('GET', '/users', null, $token);

test('Users - List', $response['code'] === 200, "HTTP {$response['code']}");
test('Users - Has Data', isset($response['body']['data']) && is_array($response['body']['data']), 'No data array');

echo "\n";

// 14. ERROR HANDLING - 400
echo "14. Testing Error Handling - 400 Bad Request...\n";
$response = apiRequest('GET', '/tickets?sort=invalid_field', null, $token);

test('Error - 400 for Invalid Sort', $response['code'] === 400, "HTTP {$response['code']}");
test('Error - 400 Response Format', isset($response['body']['status']) && $response['body']['status'] === 'error', 'Wrong error format');

echo "\n";

// 15. ERROR HANDLING - 422
echo "15. Testing Error Handling - 422 Validation...\n";
$response = apiRequest('POST', '/tickets', [
    'title' => 'Too',
    'description' => 'Short',
    'priority' => 'invalid'
], $token);

test('Error - 422 for Validation', $response['code'] === 422, "HTTP {$response['code']}");
test('Error - 422 Has Errors', isset($response['body']['errors']), 'No errors field');

echo "\n";

// 16. TICKETS - DELETE
echo "16. Testing Tickets - DELETE...\n";
if ($ticketId) {
    $response = apiRequest('DELETE', "/tickets/$ticketId", null, $token);
    
    test('Tickets - Delete', $response['code'] === 200, "HTTP {$response['code']}");
    
    // Verify it's deleted (soft delete)
    $response = apiRequest('GET', "/tickets/$ticketId", null, $token);
    test('Tickets - Soft Deleted', $response['code'] === 404, "Still accessible after delete");
} else {
    echo "   Skipped (no ticket ID)\n";
}

echo "\n";

// 17. HEALTH CHECK
echo "17. Testing Health Endpoint...\n";
$response = apiRequest('GET', '/health', null, null);

test('Health - Endpoint', $response['code'] === 200, "HTTP {$response['code']}");
test('Health - Response Format', isset($response['body']['status']), 'No status in response');

echo "\n";

// 18. REVOKE TOKEN
echo "18. Testing Authentication - REVOKE TOKEN...\n";
$response = apiRequest('DELETE', '/auth/token', null, $token);

test('Auth - Revoke Token', $response['code'] === 200, "HTTP {$response['code']}");

// Verify token is revoked
$response = apiRequest('GET', '/tickets', null, $token);
test('Auth - Token Revoked', $response['code'] === 401, "Token still works after revocation");

echo "\n";

// SUMMARY
echo "=================================================\n";
echo "  TEST SUMMARY\n";
echo "=================================================\n";
echo "Total Tests: " . ($passed + $failed) . "\n";
echo "Passed: $passed ✓\n";
echo "Failed: $failed ✗\n";
echo "Success Rate: " . round(($passed / ($passed + $failed)) * 100, 2) . "%\n";
echo "\n";

if ($failed > 0) {
    echo "Failed Tests:\n";
    foreach ($results as $result) {
        if (strpos($result, '✗') !== false) {
            echo "  $result\n";
        }
    }
    echo "\n";
}

echo "=================================================\n";

exit($failed > 0 ? 1 : 0);
