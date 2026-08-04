---
title: "MagicAI 11 Authentication and User Identity"
scan_number: 5
date: "2026-08-02"
magicai_version: "11.00"
status: "Completed"
scope:
  - "Magicai-Server-Files(3).zip"
  - "WorkCore-FULL-MERGED-CLEANED-PASS3-DEEP.zip"
repository_target: "Masterleeaus/Documents"
repository_path: "workcore/magicai-integration/05-magicai-authentication-and-user-identity.md"
---

# Scan 05 — MagicAI 11 Authentication and User Identity

## Executive conclusion

MagicAI 11 can remain Titan Zero's **login, SaaS-account and API-token host**, but it must not become WorkCore's operational identity authority.

The correct separation is:

> **MagicAI User = authenticated platform account**  
> **WorkCore Actor Subject = durable action identity**  
> **WorkCore Company Membership = company-scoped authority**  
> **WorkCore Worker = employment and field-delivery identity**  
> **WorkCore Customer = service-recipient identity**  
> **WorkCore Device = registered execution node**

This separation is mandatory because MagicAI currently allows identity and account operations that could expose credentials or destroy WorkCore operational history.

## Critical findings

1. Authenticated API endpoints return the raw MagicAI `User` model.
2. Social tokens, refresh tokens, user AI API keys, OTP and reset/confirmation codes are not hidden from model serialization.
3. Email OTP is a four-digit plaintext value with no expiry, attempt limit, user binding or session binding.
4. Password-reset codes do not expire and are not cleared after successful use.
5. API account deletion immediately hard-deletes the user without password, MFA, delay or administrator approval.
6. Current WorkCore foreign keys would cascade-delete company memberships and worker identities when a MagicAI user is deleted.
7. Web TOTP is not enforced during Passport/mobile token issuance.
8. User `status` is editable but not enforced during login or token use.
9. The Twitter callback route points to a missing controller method.
10. Apple API login requires `apple_token` but passes `apple_id` to `userFromToken()`.
11. MagicAI has duplicate custom and Laravel-standard verification/recovery implementations.
12. There is no first-class device registration, session inventory or per-device token revocation.

---

# 1. Authentication architecture

MagicAI uses one Eloquent provider:

```php
'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model'  => App\Models\User::class,
    ],
]
```

Guards:

```text
web → Laravel session
api → Laravel Passport
```

Sanctum is installed but is not the active API guard in the inspected routes.

The same `users.id` owns:

- Web identity
- API identity
- Social-account links
- SaaS subscription
- Team relationship
- Credits
- BYO AI keys
- Content, chats and documents
- Optional-extension records

WorkCore should accept `users.id` as the platform actor reference, but it must separately resolve company, membership, worker, capability and device.

---

# 2. Route inventory

## Web authentication

The active authentication route file exposes 22 routes, including:

```text
GET/POST  /register
GET/POST  /login
GET/POST  /verify-otp
GET/POST  /forgot-password
GET       /forgot-password/retrieve/{password_reset_code}
POST      /forgot-password/save
GET       /github/redirect
GET       /google/redirect
GET       /twitter/redirect
GET       /facebook/redirect
GET       /github/callback
GET       /google/callback
GET       /twitter/callback
GET       /facebook/callback
GET/POST  /2fa/login
PUT       /password
POST      /logout
```

User TOTP management:

```text
GET   /dashboard/user/2fa/activate
POST  /dashboard/user/2fa/activate
GET   /dashboard/user/2fa/deactivate
```

## API authentication

Public:

```text
POST /api/auth/register
POST /api/auth/forgot-password
GET  /api/auth/email/verify
POST /api/auth/email/verify/resend
GET  /api/auth/social-login
POST /api/auth/google-login
POST /api/auth/apple-login
```

Protected:

```text
GET    /api/user
POST   /api/auth/logout
GET    /api/auth/profile
PATCH  /api/auth/profile
DELETE /api/auth/profile
```

Passport supplies OAuth routes outside the application's explicit API route file.

---

# 3. User roles and status

MagicAI has three global platform roles:

```text
user
admin
super_admin
```

These are not company-scoped.

WorkCore roles must remain separate, including:

```text
owner
manager
dispatcher
supervisor
worker
auditor
compliance_manager
inventory_manager
finance_user
```

The user table also has a boolean-like `status`. Administrators can set it to 0 or 1, but no login condition or authenticated-request middleware was found that blocks status 0.

## Required correction

Enforce platform account state across:

- Password login
- Social login
- Passport token issuance
- Existing API-token requests
- Web authenticated requests
- WorkCore tenant resolution
- Queued actor restoration

Recommended states:

```text
active
suspended
locked
deletion_pending
deleted
```

---

# 4. User record and secret exposure

The user record includes:

```text
email_confirmation_code
password_reset_code
otp
google2fa_secret

github_token
github_refresh_token
google_token
google_refresh_token
facebook_token
twitter_token
apple_token
apple_refresh_token

api_keys
anthropic_api_keys
gemini_api_keys
xai_api_keys
```

The `User` model hides only:

```php
password
remember_token
google2fa_secret
defi_setting
```

Both `/api/user` and `/api/auth/profile` return the raw authenticated user.

Therefore provider tokens, refresh tokens, API-key fields, OTP and reset/confirmation codes are eligible for API serialization.

## Severity

**Critical**

## Required correction

- Replace raw model responses with an allowlisted `UserProfileResource`.
- Encrypt all external tokens and API keys.
- Rotate credentials if these endpoints were exposed in production.
- Add secret-field response tests.
- Add a credential-vault service.
- Prohibit WorkCore from reading secret attributes directly.

Safe profile fields should be limited to:

```text
id
name
surname
email
phone
country
avatar_url
email_verified
two_factor_enabled
platform_role
```

---

# 5. Password login

MagicAI's `LoginRequest`:

- Requires email and password.
- Uses Laravel `Auth::attempt`.
- Rate-limits to five attempts.
- Keys the limiter by lowercase email and IP.
- Regenerates the session after success.
- Can require reCAPTCHA.
- Can require email confirmation.
- Applies web TOTP after password authentication.

The authentication attempt does not include account status.

WorkCore must add a central account-state check rather than relying on successful authentication alone.

---

# 6. Registration and team invitations

Web registration supports:

- Dynamic registration fields
- Optional reCAPTCHA
- Team invitation matching by email
- Affiliate linking
- Initial AI credits
- Email confirmation
- Optional immediate login
- Mailchimp, HubSpot and affiliate integrations

When a waiting MagicAI team invitation matches the registration email, MagicAI writes:

```text
team_id
team_manager_id
```

to the user and activates the team-member row.

This represents SaaS-seat and shared-plan membership, not WorkCore company membership.

## WorkCore onboarding hook

After MagicAI creates the user:

```text
create or resolve durable actor subject
→ match pending WorkCore company invitation
→ create company membership
→ optionally link an existing worker
→ choose active company after membership validation
```

---

# 7. Duplicate authentication implementations

MagicAI retains Laravel/Breeze-style controllers for:

- Registration
- Password broker
- Password reset
- Password confirmation
- Email verification

The routed application instead uses custom:

- `AuthenticationController`
- `AuthenticatedSessionController`
- `MailController`
- `Google2FAController`

This creates two security implementations where fixes can drift.

## Recommendation

Migrate the routed flows to Laravel's signed/expiring verification and password-broker primitives while retaining MagicAI's UI and integration hooks. Remove or explicitly quarantine inactive controllers afterward.

---

# 8. Email confirmation

MagicAI stores:

```text
email_confirmation_code
email_confirmed
```

Confirmation clears the code and sets the user confirmed and active.

Positive controls:

- Long random code
- Code cleared after use
- One-hour resend cache lock
- Login can require confirmation

Gaps:

- No expiry timestamp
- Separate `email_verified_at` and `email_confirmed` states
- Social login sets both, custom email confirmation sets only `email_confirmed`
- No routed `verified` middleware use was found
- API registration generates a code but does not send the email inside the registration method

WorkCore should consume a normalized adapter property such as:

```text
platform_email_verified
```

rather than inspecting either raw field.

---

# 9. Email OTP

When `login_with_otp` is enabled:

1. MagicAI finds the user by email.
2. Generates a value from 1000 to 9999.
3. Stores it in `users.otp`.
4. Emails it.
5. Later searches globally for the first user with that OTP.
6. Logs that user in and clears the value.

The submitted password is syntactically required but not authenticated in OTP mode.

## Critical weaknesses

- Only 9,000 possible values
- Plaintext storage
- No expiry
- No attempt counter
- No verification throttle
- No email/user binding
- No browser-session binding
- No challenge ID
- Collision can select another user
- Any password value can request a code

## Required replacement

Use a hashed challenge record:

```text
public_id
user_id
purpose
code_hash
expires_at
attempt_count
max_attempts
session_binding_hash
consumed_at
```

Use at least six digits, five-minute expiry, request limits, attempt limits and one-time consumption.

The current OTP must not be used as WorkCore step-up authentication.

---

# 10. TOTP two-factor authentication

MagicAI supports TOTP setup, QR code generation and a web-session challenge.

Positive controls:

- Setup verifies a six-digit code before saving.
- The secret is hidden from normal serialization.
- Password-authenticated web sessions are challenged.
- Only the user ID is kept during challenge.

Gaps:

- Secret is not encrypted at rest.
- No recovery codes.
- No explicit brute-force throttle.
- Deactivation is a GET request.
- Deactivation requires neither current password nor TOTP.
- Passport/mobile token issuance bypasses the web-session challenge.

## Required correction

- Encrypt TOTP secrets.
- Add recovery codes.
- Require recent password plus TOTP/recovery for deactivation.
- Replace GET deactivation with POST/DELETE.
- Rate-limit verification.
- Record authentication assurance in the WorkCore actor context.
- Require MFA for high-consequence operational actions.

---

# 11. Social login

## Web

Configured routes exist for GitHub, Google, Twitter and Facebook.

Existing accounts are linked by email. New social users receive a random password, initial credits and confirmed-email state.

### Confirmed Twitter defect

`routes/auth.php` points to:

```text
AuthenticationController::twitterCallback
```

No such method exists.

### Storage risk

Provider tokens and refresh tokens are written to the user table without encrypted casts.

### Session concern

Password login explicitly regenerates the session; the social callbacks do not visibly do so after `Auth::login()`.

## API/mobile

Direct API endpoints exist for Google and Apple.

### Google

It requires `google_id` and `google_token`, but provider identity is resolved from the token and the submitted ID is not visibly compared with the returned provider ID.

### Apple defect

It requires `apple_id` and `apple_token` but calls:

```php
userFromToken($request->apple_id)
```

The submitted `apple_token` is not used for the provider lookup.

### Discovery mismatch

The supported-login-method endpoint can advertise GitHub, Twitter, Google and Facebook. Direct mobile endpoints implement Google and Apple. Apple is not advertised, while methods without mobile endpoints can be advertised.

---

# 12. Password reset

The active custom flow stores:

```text
users.password_reset_code
```

and emails a URL containing the code.

After submission it:

- Finds the user by code.
- Saves the new password.
- Logs the user in.

## Critical gaps

- No expiry timestamp.
- The reset code is not cleared after use.
- The same reset URL can be replayed.
- Password reset does not revoke sessions or Passport tokens.
- API forgot-password reveals whether an email exists.
- No MFA recovery policy or security-event notification.

## Required replacement

Use Laravel's password broker or equivalent:

- Hashed token
- Expiry
- One-time consumption
- Generic request response
- Session/token revocation
- Security notification
- Audit event

---

# 13. Sessions and API tokens

Session defaults:

```text
driver: file
lifetime: 120 minutes
encrypt server-side payload: false
secure cookie: true
http_only: true
same_site: none
```

File sessions are unsuitable for multi-node operation without shared storage. `SameSite=None` makes strong CSRF protection essential; Scan 02 found that `dashboard/*` is exempt from CSRF.

Passport is the active API guard and password grant is enabled.

API logout deletes **all** user Passport tokens, not only the current token.

No application-level controls were found for:

- Token expiry policy
- Per-device tokens
- Token scopes for WorkCore
- Session/token inventory
- Token last-use
- Security revision
- Company binding
- MFA assurance
- Device revocation

## WorkCore API rule

A token identifies the MagicAI account only. Every request must re-resolve:

```text
account state
actor subject
device
active company
membership
capabilities
worker status
```

Mutable WorkCore permissions should not be embedded permanently in long-lived tokens.

---

# 14. Account deletion

MagicAI has two different deletion paths.

## Web

```text
password confirmation
→ deletion request
→ administrator approval
→ hard delete user
```

## API

```text
authenticated DELETE /api/auth/profile
→ immediate hard delete
```

The API path has no password, MFA, waiting period or administrator approval.

MagicAI's `User` model does not use soft deletion.

## WorkCore consequence

Current WorkCore schema uses:

```text
tz_company_memberships.user_id → users.id cascadeOnDelete
tz_workers.user_id → users.id cascadeOnDelete
```

A MagicAI account deletion would remove:

- Company memberships
- Worker identity
- Worker skills and certifications through further cascades

That would damage job, compliance, payroll and audit history.

## Required lifecycle

```text
deletion requested
→ revoke login and tokens
→ disable devices
→ suspend memberships
→ apply retention policy
→ detach/anonymise MagicAI account
→ preserve durable WorkCore actor attribution
→ purge only when legally and operationally permitted
```

---

# 15. WorkCore identity architecture

WorkCore already contains:

- `WorkCoreActor`
- `MagicAIUserCompanyAdapter`
- `MagicAITenantResolver`
- `ResolveWorkCoreTenant`
- Company records
- Company memberships
- Company roles and permissions
- Worker records
- Active-company switching
- Correlation and causation context

The tenant resolver correctly verifies that:

- The company is active.
- The user has an active membership.
- Requested company context is valid.

This is a strong base.

## Remaining gap

Membership and worker records still reference `users.id` directly and cascade on delete.

WorkCore also assumes every worker has a user account.

---

# 16. Durable actor-subject model

Add:

```text
tz_actor_subjects
```

Suggested fields:

```text
id
public_id
subject_type
magicai_user_id nullable
display_name
email_snapshot nullable
status
access_revoked_at nullable
anonymised_at nullable
created_at
updated_at
```

Subject types:

```text
human
system
agent
device
integration
support_admin
```

Reference the actor subject from:

- Company memberships
- Workers
- Business actions
- Approvals
- Audit
- Rewind
- Outbox
- Device registrations

The MagicAI user link can then be removed or anonymised without deleting operational attribution.

## Worker rule

A worker must be able to exist without an authenticated user.

## Customer rule

A customer must remain independent from MagicAI user identity. Optional portal access should link through a separate portal identity.

---

# 17. Target actor context

Every WorkCore operation should resolve:

```text
platform_user_id
actor_subject_id
active_company_id
membership_id
company_role_id
worker_id nullable
device_id nullable
assurance_level
security_revision
locale
timezone
correlation_id
causation_id
```

Suggested assurance levels:

```text
password
social
email_otp
mfa
device_key
system
support_impersonation
```

Consequential actions may require:

```text
mfa
recent password
explicit approval
```

---

# 18. Recommended middleware

## Browser

```text
MagicAI auth
→ platform account active
→ MFA assurance when required
→ actor subject
→ active WorkCore company
→ active membership
→ worker context where required
→ plan entitlement
→ operational capability
→ governed action
```

## API/device

```text
Passport
→ platform account active
→ token security revision
→ registered active device
→ actor subject
→ company
→ membership
→ capability
→ idempotency
→ governed action
```

---

# 19. Required MagicAI hardening

## Critical

1. Replace raw `User` API responses.
2. Hide and encrypt social tokens, refresh tokens and AI API keys.
3. Rotate exposed credentials where necessary.
4. Replace four-digit global OTP.
5. Replace reusable non-expiring reset codes.
6. Remove immediate API hard deletion.
7. Enforce user status everywhere.
8. Enforce MFA in API/mobile authentication.
9. Fix Apple token usage.
10. Fix or remove Twitter callback.

## High

11. Add token expiry and device controls.
12. Revoke sessions and tokens after password changes/resets.
13. Encrypt TOTP secret.
14. Add recovery codes.
15. Add step-up protection for TOTP deactivation.
16. Unify email verification.
17. Add email-confirmation expiry.
18. Align API social-method discovery.
19. Regenerate sessions after social login.
20. Add identity security events.

---

# 20. Required WorkCore changes

## Critical

1. Remove user-delete cascades from membership and worker identity.
2. Add a durable actor-subject abstraction.
3. Make worker login linkage nullable.
4. Preserve actor identity in Audit and Rewind.
5. Intercept MagicAI deletion before WorkCore records change.
6. Enforce active MagicAI account state in tenant resolution.
7. Add registered-device identity for offline APIs.

## High

8. Add assurance level to operation context.
9. Add security revision to tokens, queues and sync payloads.
10. Revoke devices when account or membership state changes.
11. Separate customer portal, worker and user identity.
12. Audit support-admin access and company switching.

---

# 21. Required tests

- API profiles never expose tokens, API keys, OTP or reset codes.
- Status 0 blocks password, social and token access.
- OTP expires, is user-bound and cannot be replayed.
- Reset token expires, is one-time and revokes sessions/tokens.
- API token issuance honours MFA.
- TOTP deactivation requires step-up.
- Apple and Twitter routes work or are disabled.
- Social login cannot grant WorkCore permission.
- Deleting a MagicAI account preserves WorkCore worker, membership history, certifications, audit and Rewind attribution.
- Revoked devices cannot sync.
- A token cannot switch to an unauthorised company.

---

# 22. Decisions established by Scan 05

1. MagicAI remains the platform authentication authority.
2. WorkCore does not replace MagicAI login.
3. MagicAI user ID is a platform actor reference, not a tenant.
4. WorkCore membership remains independently authoritative.
5. Worker, customer and device identities remain separate.
6. WorkCore will add a durable actor subject.
7. Raw user models never enter WorkCore APIs.
8. Credentials move behind an encrypted vault.
9. OTP and reset flows require replacement.
10. API/mobile authentication must honour MFA.
11. User status must be enforced.
12. Operational records survive MagicAI account deletion.
13. Authentication assurance becomes part of WorkCore context.
14. Team relationships do not automatically grant company authority.
15. Security-sensitive identity changes trigger audit and revocation.

---

# 23. Next scan

```text
06-magicai-teams-companies-and-tenancy.md
```

It will map:

- Teams and team managers
- Team-member invitations
- Seat limits
- Team plan and shared-credit inheritance
- Team-owned records
- MagicAI company and product brand profiles
- User-owned record patterns
- Query scopes and isolation
- WorkCore companies and memberships
- Active-company switching
- Multi-company users
- Branch and territory context
- Tenant-safe CRM and AI integration

---

# Evidence files

## MagicAI

```text
config/auth.php
config/session.php
config/sanctum.php
app/Providers/AuthServiceProvider.php
app/Models/User.php
app/Enums/Roles.php
routes/auth.php
routes/api.php
routes/panel.php
app/Http/Requests/Auth/LoginRequest.php
app/Http/Controllers/Auth/AuthenticatedSessionController.php
app/Http/Controllers/Auth/AuthenticationController.php
app/Http/Controllers/Auth/Google2FAController.php
app/Http/Controllers/Api/AuthController.php
app/Http/Controllers/Api/SocialLoginController.php
app/Http/Controllers/Api/UserController.php
app/Http/Controllers/MailController.php
app/Actions/EmailConfirmation.php
app/Models/AccountDeletionReqs.php
```

## WorkCore

```text
app/Domains/WorkCore/Contracts/WorkCoreActor.php
app/Domains/WorkCore/System/Identity/MagicAIUserCompanyAdapter.php
app/Domains/WorkCore/System/Tenancy/MagicAITenantResolver.php
app/Domains/WorkCore/System/Tenancy/ResolveWorkCoreTenant.php
app/Domains/WorkCore/System/Models/Company.php
app/Domains/WorkCore/System/Models/CompanyMember.php
app/Domains/WorkCore/Database/Migrations/2026_07_23_120003_create_tz_company_memberships_table.php
app/Domains/WorkCore/Database/Migrations/2026_07_23_120015_create_tz_worker_tables.php
```

---

# Evidence limitations

This was a static scan. It did not execute live social providers, Passport token issuance, TOTP internals, multi-browser sessions or deletion cascades.

The confirmed defects are directly supported by routed source code and schema definitions. Runtime tests should follow the hardening work.
