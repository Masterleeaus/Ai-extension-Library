<?php

namespace TitanAI\Hybrid\Contracts;

interface Registrable
{
    public function key(): string;
    public function name(): string;
    public function description(): string;
    public function metadata(): array;
}
