# Shared Team Authenticator — V1 Requirements

## 1. Product overview

Shared Team Authenticator is a responsive web application that allows small teams to securely share access to time-based one-time password (TOTP) authenticators.

The first release is intended for teams of approximately 2–20 people that need shared access to business accounts without passing TOTP codes through chat, email, or personal authenticator apps.

### Product goals

- Let a team create its first shared authenticator in under five minutes.
- Let an authorized member find and copy a code in a few seconds.
- Ensure members only see vaults assigned to them.
- Protect stored authenticator secrets with managed envelope encryption.
- Give administrators a clear history of access and security-sensitive changes.
- Make member removal and authenticator rotation explicit and safe.

### Product principles

- TOTP seeds and generated codes must never appear in logs, analytics, URLs, or backups in plaintext.
- Daily code access should be fast and understandable.
- Administrative power and security limitations must be visible to users.
- Removing access must never be presented as erasing secrets a member could have previously copied.
- TOTP is not phishing-resistant. The product should recommend passkeys or hardware security keys whenever the underlying service supports them.

## 2. Terminology

- **Workspace:** A team and its shared configuration.
- **Admin:** A member who can manage the workspace, all vaults, members, and authenticators.
- **Member:** A user who can access only their assigned vaults.
- **Vault:** A named collection of shared authenticators, such as Engineering, Finance, or Client A.
- **Authenticator:** An encrypted TOTP seed and its associated display and algorithm settings.
- **Rotation:** Replacing an authenticator seed at the originating third-party service.
- **Invitation link:** A single-use, expiring URL bound to an email address and used to join a workspace.

## 3. Roles and authorization

V1 has exactly two workspace roles:

| Capability | Admin | Member |
| --- | --- | --- |
| View assigned vaults | Yes | Yes |
| View all vaults | Yes | No |
| Reveal and copy codes | Yes | Assigned vaults only |
| Create and edit vaults | Yes | No |
| Add and edit authenticators | Yes | No |
| Invite and remove members | Yes | No |
| Assign members to vaults | Yes | No |
| View the workspace audit log | Yes | No |
| Manage rotation tasks | Yes | No |

All admins are peers. The workspace creator becomes its first admin. The last remaining admin cannot leave, be removed, or be changed to a member.

## 4. User stories

### 4.1 Registration, login, and session security

#### AUTH-01 — Register an account

As a new user, I want to register with my email address and password so that I can create or join a workspace.

**Acceptance criteria**

- Public registration is available without an invitation.
- Email addresses are trimmed and normalized to lowercase before lookup or storage.
- Email addresses are unique across accounts.
- Passwords require at least 12 characters and confirmation.
- Passwords are stored only through Laravel's one-way password hashing cast.
- A successful registration starts a session and regenerates the session ID.
- Registration requests are rate-limited by IP address.

#### AUTH-02 — Sign in with a password

As a registered user, I want to sign in with my email address and password so that I can access my workspace.

**Acceptance criteria**

- Login uses Laravel's session guard and password verification.
- Successful login regenerates the session ID.
- Invalid credentials receive a neutral error that does not disclose whether the account exists.
- Login attempts are limited to five per minute for each normalized email and IP combination.
- Remember-me login is not offered in V1.

#### AUTH-03 — Register from an invitation

As an invited person without an account, I want to create my password from the invitation link so that I can join the intended workspace directly.

**Acceptance criteria**

- The invitation link fixes the account email and does not trust an editable email field.
- A new account is created only after a valid, unexpired invitation and valid password submission.
- An existing account must sign in before it can accept an invitation.
- A legacy account without a password may set one through its valid invitation link.
- Successful invitation registration accepts the invitation, starts a session, and regenerates the session ID.

#### AUTH-04 — End and expire sessions

As a user, I want to sign out and have inactive sessions expire so that my account is not left accessible.

**Acceptance criteria**

- Successful login uses Laravel's session guard without remember-me behavior.
- Signing out invalidates the session and regenerates the CSRF token.
- Sessions expire after 120 minutes according to the configured session lifetime.
- Guests are redirected to the login screen when requesting protected pages.

### 4.2 Workspace and membership

#### TEAM-01 — Create a workspace

As a new user, I want to create a workspace so that I can organize my team's authenticators.

**Acceptance criteria**

- Given an account without a workspace, when the user supplies a valid workspace name, then the workspace is created and the user becomes an admin.
- A new workspace begins with one default vault whose name can be changed.
- Workspace creation is recorded in the audit log.

#### TEAM-02 — Invite a member

As an admin, I want to create an invitation link for an email address and select its vault access so that I can share access without requiring application email delivery.

**Acceptance criteria**

- An admin can choose the invited user's role and initial vault assignments.
- An invitation is single-use, expires, and is bound to the invited email address.
- The raw invitation token is shown only when the link is created; persistence stores only its SHA-256 hash.
- The application does not email the link. The admin copies and shares it through a channel of their choice.
- An invitation may target an email that already has an account, including an account currently attached to another workspace.
- Vault access is granted only after the invitation is accepted by the intended email address.
- Given an expired, revoked, modified, or already accepted invitation, when acceptance is attempted, then access is denied.
- Accepted, expired, and revoked invitations are recorded in the audit log.

#### TEAM-03 — Change a member's access

As an admin, I want to change a member's role and vault assignments so that their access matches their responsibilities.

**Acceptance criteria**

- Adding vault access creates an explicit authorization grant for that member.
- Removing vault access prevents the member from fetching current or future encrypted data for that vault.
- Promoting a member to admin grants access to all workspace vaults.
- Demoting an admin to member requires explicit vault assignments.
- The last-admin rule is enforced on every role change.
- Every access change records the actor, target, timestamp, and affected vault without recording plaintext vault contents.

#### TEAM-04 — Remove a member

As an admin, I want to remove a member immediately so that they cannot continue accessing team vaults.

**Acceptance criteria**

- Removal revokes the user's workspace membership and all vault authorization grants.
- Existing sessions belonging to the removed member are revoked.
- The application creates a rotation task for every authenticator the member could previously access.
- Before confirmation, the admin is warned that removal cannot erase seeds or codes previously copied by the member.
- The member removal and resulting access revocation are recorded in the audit log.

### 4.3 Vault management

#### VAULT-01 — Create and name a vault

As an admin, I want to create named vaults so that authenticators can be grouped by team, function, or client.

**Acceptance criteria**

- An admin can create, rename, and archive a vault.
- Vault names must be unique within a workspace after case and whitespace normalization.
- Vault names are encrypted as part of the vault contents.
- Archived vaults are hidden from the default dashboard but remain available to admins until deleted.

#### VAULT-02 — Assign members to a vault

As an admin, I want to assign members to a vault so that access is granted as a group rather than one authenticator at a time.

**Acceptance criteria**

- An admin can view and update a vault's member list.
- A member gains access to every current authenticator in an assigned vault.
- New authenticators added to the vault become available to all current vault members.
- Removing a vault assignment follows the same revocation and rotation-warning rules as member removal.

#### VAULT-03 — Delete a vault

As an admin, I want to delete an unused vault safely so that obsolete encrypted data is removed.

**Acceptance criteria**

- Deletion requires explicit confirmation and displays the number of affected authenticators and members.
- A non-empty vault cannot be deleted until its authenticators are moved, rotated, or explicitly confirmed for deletion.
- Deletion revokes its grants and records the operation in the audit log.

### 4.4 Adding and managing authenticators

#### TOTP-01 — Scan a QR code with the camera

As an admin using a phone or camera-equipped computer, I want to scan a setup QR code so that I can add an authenticator quickly.

**Acceptance criteria**

- The application requests camera permission only after the user starts scanning.
- Given a valid TOTP `otpauth://` QR code, when it is scanned, then the parsed issuer, account, algorithm, digits, and period are shown for confirmation.
- Given denied camera access, then QR image upload and manual entry remain available.
- Camera frames and decoded secret material are processed locally and are not uploaded.
- The camera stops when scanning completes, is cancelled, or the page loses access.

#### TOTP-02 — Import a QR image

As an admin, I want to upload or paste a QR image so that I can add an authenticator without using a camera.

**Acceptance criteria**

- Valid PNG, JPEG, and WebP images can be decoded locally.
- Given an unreadable image or unsupported QR payload, then the application shows a useful error without uploading the image.
- A successfully decoded value follows the same validation and confirmation flow as camera scanning.

#### TOTP-03 — Enter an authenticator manually

As an admin, I want to enter an `otpauth://` URI or secret manually so that I can add accounts that do not provide a scannable QR code.

**Acceptance criteria**

- Manual entry supports issuer, account name, Base32 secret, algorithm, digit count, and period.
- Supported algorithms are SHA-1, SHA-256, and SHA-512.
- Supported output lengths are six and eight digits.
- The default period is 30 seconds.
- Invalid Base32, missing required values, unsupported algorithms, and invalid periods are rejected before saving.

#### TOTP-04 — Save an authenticator

As an admin, I want to review and save an authenticator into a vault so that authorized members can use it.

**Acceptance criteria**

- The admin must select a vault and confirm the parsed fields before saving.
- The server encrypts the seed and descriptive fields with managed envelope encryption before persistence.
- Plaintext seed material must not be written to logs, analytics, queues, caches, or backups.
- The data-encryption key must be protected by a separately managed key-encryption key.
- Potential duplicates within the unlocked workspace are flagged before saving but can be confirmed by the admin.

#### TOTP-05 — Edit, move, or archive an authenticator

As an admin, I want to maintain authenticator records so that the vault stays accurate.

**Acceptance criteria**

- An admin can edit the issuer and account label without replacing the seed.
- Moving an authenticator encrypts it under the destination vault key and updates access accordingly.
- Changing the seed or algorithm requires explicit confirmation and creates an audit event.
- Archived authenticators do not appear on the default member dashboard.
- Secret values and generated codes never appear in audit events.

### 4.5 Using TOTP codes

#### CODE-01 — Find an authenticator

As a member, I want to browse and search my assigned authenticators so that I can quickly find the account I need.

**Acceptance criteria**

- The dashboard groups authenticators by assigned vault.
- Search matches decrypted issuer and account labels locally in the browser.
- Search terms and decrypted labels are not sent to the server.
- Members never receive ciphertext or wrapped keys for unassigned vaults.

#### CODE-02 — Reveal a code

As a member, I want to reveal one current code so that I can enter it into the corresponding service.

**Acceptance criteria**

- Codes are hidden by default.
- Revealing one authenticator does not reveal other codes.
- The displayed code includes a visible countdown to its expiry.
- At the time boundary, the expired code is replaced with the newly generated code without reloading the page.
- Reveal actions create an audit event without including the code or seed.

#### CODE-03 — Copy a code

As a member, I want to copy a current code with one action so that I can complete a login quickly.

**Acceptance criteria**

- Copy places only the current numeric code on the clipboard.
- The UI confirms success without displaying the secret seed.
- Copy actions create an audit event without including clipboard contents.
- Clipboard clearing may be offered where supported, but the product must not claim that browser clipboard contents can always be erased.

#### CODE-04 — Handle device clock problems

As a user, I want to know when my device clock may be inaccurate so that I can understand why a code is being rejected.

**Acceptance criteria**

- The client periodically compares its time with a trusted server timestamp without sending secret data.
- If clock drift exceeds the supported tolerance, the UI shows a warning and instructions to enable automatic time synchronization.
- The application does not silently adjust TOTP generation using an untrusted offset.

### 4.6 Audit history

#### AUDIT-01 — Review workspace activity

As an admin, I want to review access and change history so that I can investigate account usage.

**Acceptance criteria**

- Admins can filter events by user, vault, event type, and date.
- Events include actor, action, affected resource identifier, timestamp, and result.
- Events cover reveal, copy, membership changes, vault changes, authenticator changes, invitations, access revocation, and rotation completion.
- Audit details must never contain invitation tokens, passwords, TOTP seeds, generated codes, or decrypted labels unless those labels are encrypted for authorized admins.

#### AUDIT-02 — Detect audit-log modification

As an admin, I want audit events to be tamper-evident so that unexpected modification can be detected.

**Acceptance criteria**

- Audit events are client-signed, server-sequenced, and linked through a verifiable hash chain.
- The admin interface verifies the chain and clearly reports missing or invalid links.
- Audit records cannot be edited or deleted through the product.
- Documentation states that a malicious or modified client may omit an access event and that the log is not proof that a previously decrypted seed was never retained.

### 4.7 Rotation and offboarding

#### ROT-01 — Review required rotations

As an admin, I want a checklist of authenticators exposed to a removed user so that I know which third-party services require new seeds.

**Acceptance criteria**

- Removing a member or vault assignment creates one open rotation task per affected authenticator.
- Tasks identify the authenticator, originating vault, triggering event, and removal date.
- Rotation tasks do not expose the old seed.
- Tasks remain open until completed or explicitly accepted as a documented risk.

#### ROT-02 — Complete a rotation

As an admin, I want to replace an authenticator after rotating it at the originating service so that the team uses the new seed.

**Acceptance criteria**

- The admin can scan or enter the replacement authenticator through the normal secure intake flow.
- Replacing a seed encrypts the new value for the vault and invalidates normal access to the old value.
- The related rotation task is marked complete only after the replacement is saved or the authenticator is removed.
- The replacement and task completion are recorded in the audit log.

## 5. Security and privacy requirements

### SEC-01 — Managed encrypted storage

- TOTP seeds, issuer labels, account labels, vault names, and sensitive audit details must use authenticated encryption at rest.
- Each vault must use an independent, randomly generated data-encryption key.
- Data-encryption keys must be protected through envelope encryption with a separately managed key-encryption key.
- Encryption keys and encrypted data must be logically separated and support rotation.
- The service may technically decrypt vault data, and this trust boundary must be documented accurately.

### SEC-02 — TOTP generation

- TOTP generation must conform to RFC 6238 and pass its published test vectors.
- Plaintext seeds and generated codes must exist only for the shortest required processing period.
- Seeds and generated codes must never enter analytics, logs, URLs, queue payloads, or unencrypted persistence.

### SEC-03 — Authorization and revocation

- Every vault read or code-generation request must enforce current workspace and vault authorization.
- Removing access must revoke server authorization and active sessions immediately.
- Revocation must not leave partial or stale authorization grants.

### SEC-04 — Web application hardening

- Enforce HTTPS, secure cookies, CSRF protection, a restrictive Content Security Policy, and protection against clickjacking.
- Avoid third-party scripts on authenticated and unlocked application pages.
- Pin and review cryptographic dependencies.
- Prevent sensitive values from entering URLs, telemetry, crash reports, logs, browser history, or server-rendered HTML.
- Require an independent cryptographic architecture review and application security assessment before public release.

### SEC-05 — Threat-model boundaries

The managed-encryption design must protect against:

- Database or backup disclosure.
- A user attempting to fetch vaults they were never assigned.
- A removed user requesting new authenticator codes after revocation.

V1 does not claim to protect against:

- A compromised browser, operating system, or browser extension.
- Compromise of the application runtime or managed encryption keys.
- An authorized member recording a seed or code while they have access.
- Phishing or real-time relay of a valid TOTP code.
- Compromise of the originating third-party service.

## 6. Non-functional requirements

### Accessibility

- Core workflows must meet WCAG 2.2 AA.
- Every workflow must be operable by keyboard.
- Countdown state must not rely on color alone or create excessive screen-reader announcements.
- Camera scanning must always have equivalent upload and manual-entry alternatives.

### Responsive behavior

- The application must provide production-quality experiences on current desktop and mobile versions of Chrome, Safari, Firefox, and Edge.
- Core code access must work from a 320-pixel-wide viewport upward.
- Touch targets must be appropriately sized and code actions must not depend on hover.

### Performance

- After an unlocked vault has loaded, local search results should update within 100 milliseconds for up to 1,000 authenticators.
- Reveal and copy actions should respond within 200 milliseconds under normal device conditions.
- Code countdowns must remain accurate when a browser tab is suspended and resumed.

### Reliability

- Network interruption must not corrupt encrypted records or membership changes.
- Mutating operations must be idempotent or safely retryable.
- Security-sensitive partial failures must be visible to the initiating admin.
- Backups must preserve ciphertext, key references, and audit chains without introducing plaintext copies.

## 7. Release acceptance criteria

V1 is ready for release when:

- A new admin can register, create a workspace, add an authenticator, create a vault, and generate an invitation link.
- The invited member can sign in and reveal or copy codes only from assigned vaults.
- TOTP output passes RFC test vectors for all supported algorithms and digit counts.
- Automated tests demonstrate that APIs, logs, databases, analytics, and backups do not contain plaintext secrets or codes.
- Password login, invitation acceptance, role changes, revocation, audit verification, and rotation workflows pass end-to-end tests.
- Camera denial, malformed QR codes, invalid manual input, expired invitations, clock drift, and network failures have safe and understandable outcomes.
- Accessibility testing confirms the core journeys meet WCAG 2.2 AA.
- Independent security review findings classified as critical or high have been resolved.

## 8. Out of scope for V1

- HOTP, SMS codes, push authentication, passkey sharing, shared passwords, and recovery-code storage.
- Browser extensions and automatic code filling.
- Native mobile or desktop applications.
- Installable PWA and offline vault access.
- Bulk imports, exports, and migrations from other authenticator products.
- Custom roles, per-authenticator permissions, SSO, SCIM, and SIEM integrations.
- Billing and subscription management.
- Automatic authenticator rotation at third-party services.
- Claims that TOTP is phishing-resistant.

## 9. Working assumptions

- “Shared Team Authenticator” is a working title, not the final public product name.
- The initial market is small teams rather than regulated enterprises.
- All admins are trusted with potential access to all workspace vaults.
- Authenticator codes are hidden until individually revealed or copied.
- The service is online-only in V1.
- Authentication uses normalized email addresses and passwords of at least 12 characters. Login is limited to five attempts per email and IP combination each minute.
- Registration is public. Workspace invitation links are single-use, expire after seven days, and are shared manually rather than emailed.
- V1 uses managed envelope encryption; detailed vault encryption implementation remains future work.
- Rotation is completed at the originating service by an admin and tracked through an in-product checklist.
