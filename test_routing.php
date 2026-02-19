<?php
// Test script for verify_cert.php with owner check

function test_verify($code)
{
    echo "Testing code: $code\n";
    $url = "http://localhost:8200/backend/verify_cert.php?code=" . urlencode($code);
    $response = file_get_contents($url);
    echo "Response: $response\n\n";
}

// Test 1: Code with Owner (based on previous observations)
// "AGISL/EHS/FA/017/W13122X4HP3KN" seems to be associated with "sunday Amadi" in the DB output
$code_with_owner = 'AGISL/EHS/FA/017/W13122X4HP3KN';
test_verify($code_with_owner);

// Test 2: Code without Owner (manually created)
$code_without_owner = 'TEST_NO_OWNER';
test_verify($code_without_owner);
