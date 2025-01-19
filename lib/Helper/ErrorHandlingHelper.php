<?php

namespace OCA\DavPush\Helper;

class ErrorHandlingHelper {

    // convert php errors to exceptions to be able to catch them
    function convertErrorsToExceptions(callable $fn) {
		set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline) {
			if (!(error_reporting() & $errno)) {
				// This error code is not included in error_reporting.
				return;
			}
		
			if ($errno === E_DEPRECATED || $errno === E_USER_DEPRECATED) {
				// Do not throw an Exception for deprecation warnings as new or unexpected
				// deprecations would break the application.
				return;
			}
		
			throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
		});

        $fn();

        // return to default nextcloud error handler
		restore_error_handler();
    }
}