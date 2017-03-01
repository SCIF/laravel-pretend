<?php declare(strict_types=1);

namespace Scif\LaravelPretend\Interfaces;

interface Impersonable
{
    public function canImpersonate(): bool;
}