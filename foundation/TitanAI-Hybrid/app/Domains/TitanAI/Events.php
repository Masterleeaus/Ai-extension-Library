<?php

declare(strict_types=1);

namespace App\Domains\TitanAI;

/**
 * Backward-compatible event manifest.
 *
 * TitanAI events now live in one PSR-4 file per class under this namespace so
 * Composer can discover them normally. This file intentionally declares no
 * classes; it is retained because earlier foundation bundles shipped it.
 *
 * @see SkillsDiscovered
 * @see ActionsDiscovered
 * @see ConnectorsDiscovered
 * @see ActionInvoked
 * @see ActionCompleted
 * @see ActionFailed
 * @see UserMemoryUpdated
 * @see WorkflowMemoryUpdated
 * @see ConnectorStateChanged
 * @see ExtensionBooted
 * @see ExtensionShuttingDown
 */
