<?php

namespace TitanAI\Hybrid\Memory\Enums;

enum MemoryScope: string
{
    case USER = 'user';
    case WORKFLOW = 'workflow';
    case CONNECTOR = 'connector';
    case GLOBAL = 'global';
    case SESSION = 'session';

    public function label(): string
    {
        return match($this) {
            self::USER => 'User Memory',
            self::WORKFLOW => 'Workflow Memory',
            self::CONNECTOR => 'Connector State',
            self::GLOBAL => 'Global Config',
            self::SESSION => 'Session Temporary',
        };
    }
}
