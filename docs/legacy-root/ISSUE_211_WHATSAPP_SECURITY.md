# Issue #211: Quarantine inbound WhatsApp media and remove base64 message payloads (Critical Security)

## Overview
Implement secure media handling for WhatsApp integration with inbound media quarantine, base64 payload removal, and threat scanning to prevent malware distribution through the system.

## Problem Statement
- Base64-encoded payloads increase message size 33%
- No malware scanning before processing
- Media stored insecurely in database
- Risk of malware propagation through system
- Privacy concerns with media handling

## Requirements

### Media Quarantine System
1. **Secure Storage**
   - Media stored in separate quarantine bucket
   - Encrypted at rest (AES-256)
   - Tenant-isolated storage
   - Immutable audit trail

2. **Access Control**
   - Only authorized services access media
   - Download requires permission check
   - Audit log of all access
   - Time-limited download links

3. **Threat Scanning**
   - YARA rule scanning
   - VirusTotal integration (optional)
   - File type validation
   - Size limits per media type

4. **Cleanup Policy**
   - Media retention per tenant (configurable)
   - Automatic deletion at TTL
   - Manual deletion capability
   - Undeleted media alerts

### Payload Optimization
1. **Remove Base64 Encoding**
   - Store media reference instead
   - Media downloaded on-demand
   - Bandwidth reduction (33% smaller payloads)
   - Performance improvement

2. **Reference System**
   - MediaReference value object
   - Download URL generation
   - Expiration handling
   - Fallback media handling

### WhatsApp Adapter Changes
1. **Webhook Handler**
   - Accept media webhook
   - Validate media format
   - Scan for threats
   - Store in quarantine
   - Remove base64 from payload

2. **Message Processing**
   - Store media reference
   - Lazy-load media (download on demand)
   - Handle missing media gracefully

## Testing Requirements
- Unit tests for media quarantine
- Tests for threat scanning
- Tests for access control
- Integration tests for WhatsApp webhook
- Security tests (media access, scan bypass)
- Performance tests (payload size reduction)
- Cleanup tests (TTL enforcement)

## Acceptance Criteria
- ✅ Base64 payloads removed from messages
- ✅ Media stored in quarantine
- ✅ Threat scanning working
- ✅ Access control enforced
- ✅ Audit trail complete
- ✅ Cleanup working
- ✅ 33% bandwidth reduction
- ✅ All tests pass (20+ assertions)

## Related Issues
- Depends on: #143-146, #146 (Webhook verification)
- Works with: #61 (Connector Runtime)
- Critical security issue

## Timeline
- **Critical Security**
- Start immediately if not done
- Estimated effort: 3 days
