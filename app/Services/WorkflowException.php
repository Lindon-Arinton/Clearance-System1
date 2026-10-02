<?php

namespace App\Services;

use RuntimeException;

/**
 * Raised when an action is not allowed by the clearance workflow.
 * The message is safe to show to the user.
 */
class WorkflowException extends RuntimeException
{
}
