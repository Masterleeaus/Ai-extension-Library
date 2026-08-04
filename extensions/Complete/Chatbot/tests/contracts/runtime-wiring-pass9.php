<?php
$root = dirname(__DIR__, 2);
$required = [
 'System/TitanAI/Runtime/Skills/FieldServiceSkillRegistry.php',
 'System/TitanAI/Runtime/Tools/FieldServiceToolRegistry.php',
 'System/Http/Controllers/Api/TitanAI/FieldServiceSkillController.php',
 'System/Http/Controllers/Api/TitanAI/FieldServiceToolController.php',
 'System/TitanAI/workcore-runtime/System/Contracts/HostWorkCoreActionBus.php',
 'System/TitanAI/workcore-runtime/System/Contracts/HostWorkCoreReadBus.php',
 'resources/pwa/chatbot-pwa/byo-execution-bridge.js',
];
foreach ($required as $file) { if (!is_file($root.'/'.$file)) { fwrite(STDERR, "Missing $file\n"); exit(1); } }
$provider=file_get_contents($root.'/System/ChatbotServiceProvider.php');
foreach (["name('skills.index')", "name('tools.index')", 'SystemAIChat'] as $token) { if (!str_contains($provider,$token)) { fwrite(STDERR,"Missing provider wiring: $token\n"); exit(1); } }
$blade=file_get_contents($root.'/resources/views/frontend-ui/components/pwa.blade.php');
if (!str_contains($blade,'byo-execution-bridge.js')) { fwrite(STDERR,"BYO bridge not mounted\n"); exit(1); }
echo "Pass 9 contract checks passed\n";
