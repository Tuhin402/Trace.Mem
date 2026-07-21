<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a user has no active workspace (e.g. removed from all workspaces).
 * Controllers catching this should redirect to a "no workspace" state page,
 * not crash with a 500.
 */
class NoWorkspaceException extends RuntimeException
{
    public function __construct(string $message = 'No active workspace found for this user.')
    {
        parent::__construct($message);
    }
}
