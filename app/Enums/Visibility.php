<?php

namespace App\Enums;

/**
 * Field visibility levels. Blueprint §11. Enforced by the backend, never the client.
 */
enum Visibility: string
{
    case Public = 'public';
    case MembersOnly = 'members_only';
    case AcceptedConnection = 'accepted_connection';
    case Private_ = 'private';
}
