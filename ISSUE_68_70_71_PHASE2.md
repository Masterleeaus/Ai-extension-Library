# Phase 2 Production Hardening - Issues #68, #70, #71

## Issue #68: Formalize Shared Skill Runtime, Package Security and Version Lifecycle

**Status:** URGENT | Phase 2  
**Effort:** 1-2 weeks  
**Key Focus:** Package management, security scanning, dependency resolution

### Requirements
- Skill package contract definition
- Version resolution algorithm
- Dependency version constraints (semver)
- Security scanning on install
- Package compatibility matrix
- Rollback capability
- Version pinning and lock files

### Deliverables
```php
interface SkillPackage {
    public function getName(): string;
    public function getVersion(): string;
    public function getDependencies(): Collection;
    public function getSecurityProfile(): SecurityProfile;
    public function isCompatibleWith(string $platform): bool;
}

class SkillPackageManager {
    public function install(SkillPackage $package): void;
    public function update(SkillPackage $package, string $version): void;
    public function remove(SkillPackage $package): void;
    public function resolve(array $dependencies): Collection;
    public function scanSecurity(SkillPackage $package): SecurityReport;
    public function createLockFile(): void;
    public function rollback(string $version): void;
}
```

---

## Issue #70: Migrate Chatbot Tier-3 Actions to WorkCore Gateways

**Status:** URGENT | Phase 2  
**Effort:** 2-3 weeks  
**Key Focus:** Action consolidation, governance, audit trails

### Requirements
- Identify all Chatbot Tier-3 agents
- Create WorkCore gateway for each vertical
- Map actions to WorkCore operations
- Implement governance layer
- Add audit trail recording
- Maintain backward compatibility

### Action Mapping Examples
```
Chatbot Tier-3                  → WorkCore Gateway
─────────────────────────────────────────────────
Customer inquiry action         → WorkCore/CRM/LeadCapture
Booking request action          → WorkCore/Scheduling/CreateBooking
Invoice action                  → WorkCore/Commerce/CreateInvoice
Task assignment action          → WorkCore/Work/AssignTask
Knowledge update action         → WorkCore/Knowledge/IndexUpdate
```

### Deliverables
- Migration audit identifying all Tier-3 agents
- WorkCore gateway interfaces for each vertical
- Action routing logic with governance
- Fallback to legacy behavior during transition
- Comprehensive test coverage

---

## Issue #71: Add Feature Flags, Migration State & Rollback Controls

**Status:** URGENT | Phase 2  
**Effort:** 1-2 weeks  
**Key Focus:** Progressive migration, shadow validation, safe rollback

### Feature Flag Requirements
```php
class MigrationFeatureFlags {
    public bool $useTenantContextForAuth;      // #143
    public bool $useEventEnvelopeForEvents;    // #144
    public bool $useVaultForCredentials;       // #145
    public bool $useWebhookVerification;       // #146
    public bool $useDurableWorkflow;           // #67
    public bool $useWorkCoreGateways;          // #70
    public bool $useVoiceEngine;               // #60
    public bool $useConnectorRuntime;          // #61
}
```

### Shadow Read Validation
```php
// Implement dual execution during transition
class ShadowValidator {
    public function validate(): void {
        // Execute new and old paths
        $oldResult = $this->executeLegacyPath();
        $newResult = $this->executeNewPath();
        
        // Compare results
        if ($oldResult !== $newResult) {
            Log::warning("Shadow read mismatch detected", [
                'old' => $oldResult,
                'new' => $newResult
            ]);
        }
    }
}
```

### Rollback Capability
```php
// If new system fails, rollback to previous state
class RollbackManager {
    public function initiateRollback(string $toVersion): void {
        // 1. Stop new system
        // 2. Replay events to old system
        // 3. Verify data consistency
        // 4. Re-enable old system
        // 5. Alert team
    }
}
```

### Deliverables
- Feature flag system with granular control
- Shadow read implementation for validation
- Rollback procedures and testing
- Gradual cutover plan (5%-25%-50%-100%)
- Monitoring and alerting

