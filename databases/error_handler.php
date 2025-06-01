<?php
// Set error reporting based on environment
if (getenv('ENVIRONMENT') === 'production') {
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

// Custom error handler function
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    $isProduction = getenv('ENVIRONMENT') === 'production';
    
    if ($isProduction) {
        // Log error but show generic message
        error_log("Error [$errno]: $errstr in $errfile on line $errline");
        
        if ($errno == E_USER_ERROR) {
            echo "<div style='color:red; padding:20px; margin:20px; border:1px solid #ccc; background:#f8f8f8;'>";
            echo "<h3>An error occurred</h3>";
            echo "<p>The application encountered a problem. Please try again later.</p>";
            echo "</div>";
            exit(1);
        }
    } else {
        // Show detailed error in development
        echo "<div style='color:red; padding:20px; margin:20px; border:1px solid #ccc; background:#f8f8f8;'>";
        echo "<h3>PHP Error [$errno]</h3>";
        echo "<p>$errstr in <strong>$errfile</strong> on line <strong>$errline</strong></p>";
        echo "</div>";
    }
    
    // Don't execute PHP internal error handler
    return true;
}

// Set custom error handler
set_error_handler("customErrorHandler");

// Function to handle fatal errors
function fatalErrorHandler() {
    $error = error_get_last();
    if ($error !== NULL && $error['type'] === E_ERROR) {
        customErrorHandler(E_ERROR, $error['message'], $error['file'], $error['line']);
    }
}

// Register shutdown function
register_shutdown_function("fatalErrorHandler");
?>