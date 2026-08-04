<?php

declare(strict_types=1);

$root = dirname(__DIR__, 3);
$required = [
    'System/TitanAI/governance/System/Council/OperationalCouncil.php',
    'System/TitanAI/governance/System/Council/ConfiguredModelReviewer.php',
    'System/TitanAI/governance/System/Tools/GovernedToolExecutor.php',
    'System/TitanAI/governance/System/Skills/SkillOrchestrator.php',
    'System/TitanAI/governance/System/Personas/OperatingPersona.php',
    'System/TitanAI/governance/System/Memory/MemoryGovernanceService.php',
    'System/TitanAI/governance/System/Booking/WorkCoreBookingCoordinator.php',
];
foreach ($required as $path) {
    if (! is_file($root.'/'.$path)) {
        fwrite(STDERR, "Missing governance file: {$path}\n");
        exit(1);
    }
}
$booking = file_get_contents($root.'/System/TitanAI/modules/agent-booking/System/BookingService.php');
foreach (['WorkCoreBookingCoordinator', 'book(', 'availability(', 'cancel('] as $needle) {
    if (! str_contains($booking, $needle)) {
        fwrite(STDERR, "Booking integration missing: {$needle}\n");
        exit(1);
    }
}
if (str_contains($booking, "uniqid('booking_')") || str_contains($booking, "uniqid('job_')")) {
    fwrite(STDERR, "Booking service still fabricates authoritative IDs.\n");
    exit(1);
}
echo "Titan AI governance static contracts passed.\n";
