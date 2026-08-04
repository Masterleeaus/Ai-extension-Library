---
title: Titan Academy Deep Product Design
status: Designed
created: 2026-08-02
updated: 2026-08-02
source_archives:
  - Extensions.zip
parent_portfolio: workcore/workcore-business-app-ideas.md
related_designs:
  - workcore/titan-inspect-deep-design.md
  - workcore/titan-pricebook-deep-design.md
  - workcore/titan-quotes-deep-design.md
product_family: WorkCore and Titan Zero
recommended_build_order: 4
---

# Titan Academy

### Deep product design

**SOP capture, field-service training, practical assessment, competency governance and portable skills passports.**

Titan Academy turns the media, transcription, visual-document, guided-workflow, avatar and localisation engines discovered in `Extensions.zip` into a complete standalone business application. It is not merely a learning-management system, video library, quiz builder or AI SOP generator.

Its end-to-end outcome is:

> Capture how excellent work is actually performed, convert that knowledge into governed SOPs and learning, verify that workers can perform the work safely and correctly, and expose trusted competency evidence to WorkCore when jobs are assigned.

Titan Academy can operate independently, but its strongest form is a WorkCore-native specialist application. WorkCore remains authoritative for people, teams, contractors, jobs, schedules, customers and operational activity. Titan Academy becomes authoritative for SOPs, courses, lessons, learning assignments, assessments, practical evidence, competency decisions, certificates and skills passports.

---

## 1. Product thesis

Field and home service businesses often rely on knowledge that exists only in the heads of a few experienced workers.

That knowledge is usually transferred through shadowing, informal demonstrations, verbal instructions, old PDF manuals, scattered videos, checklists without explanation and trial and error on customer properties. These methods fail as the business grows.

A senior cleaner may know how to restore a particular floor without damaging its finish. A pool technician may recognise the sound of a failing pump. A roof worker may understand which visual cues require escalation. A restoration worker may know the correct sequence for containment and evidence. If that worker leaves, the business loses operational memory.

Traditional learning systems also produce weak evidence. Completing a video and passing a multiple-choice quiz does not prove that someone can safely perform a real job.

Titan Academy therefore treats training as a controlled chain:

```text
Observed expert work
→ governed SOP
→ structured learning
→ knowledge assessment
→ practical evidence
→ supervisor decision
→ competency status
→ permitted work
→ quality feedback
→ refresher or remediation
```

### Product promise

**Capture the best way to work. Teach it clearly. Verify it in practice. Assign work with confidence.**

### Primary commercial outcomes

- faster onboarding;
- less dependence on a small number of senior workers;
- consistent service delivery across teams and branches;
- fewer preventable mistakes and callbacks;
- clearer proof that workers were trained;
- practical competency evidence rather than completion-only records;
- faster deployment of new services;
- lower franchise training drift;
- multilingual training without rebuilding every course;
- targeted remediation based on real quality failures;
- better job assignment using verified skills;
- reusable operational knowledge that improves over time.

---

## 2. Application boundary

Titan Academy must not become a duplicate HR, scheduling, document-storage or compliance system.

### Titan Academy owns

- SOP families and immutable SOP versions;
- source media and source-document references used to create SOPs;
- steps, decisions, tools, materials, hazards and quality checkpoints;
- course, module and lesson definitions;
- training media and approved derivatives;
- learning pathways;
- enrolments and learning assignments;
- knowledge assessments;
- scenario assessments;
- practical assessment templates;
- evidence submissions;
- assessor observations and decisions;
- competency definitions and competency status;
- refresher and remedial assignments;
- internal training certificates;
- worker skills passports;
- training audit history and learning analytics.

### WorkCore remains authoritative for

- employees, contractors and user identity;
- teams, branches and reporting lines;
- jobs, tasks and appointments;
- customer and property records;
- operational checklists;
- timesheets and attendance;
- actual work activity;
- general HR records;
- billing and payroll.

### Titan Credential Vault will remain authoritative for

- external licences;
- trade registrations;
- police checks;
- Working With Children checks;
- insurance certificates;
- first-aid certificates issued by external providers;
- statutory expiries and document verification.

Titan Academy may reference Credential Vault records when a competency requires an external credential, but it must not silently copy or redefine their validity.

### Titan Quality and Titan Inspect provide feedback

Titan Quality can provide nonconformances, failed audits, rework events, recurring quality defects and worker or branch quality signals. Titan Inspect can provide inspection findings, evidence of completed work, supervisor observations and practical-assessment evidence.

Academy uses those signals to recommend refresher or remedial training. It does not alter the originating quality or inspection record.

---

## 3. Target customers

### Primary verticals

- residential and commercial cleaning;
- restoration and remediation;
- HVAC and refrigeration;
- plumbing and drainage;
- electrical service;
- solar installation and maintenance;
- pool and spa servicing;
- landscaping and garden maintenance;
- pest control;
- painting and surface restoration;
- roofing and gutter services;
- facilities maintenance;
- property turnover services;
- appliance repair;
- franchise and subcontractor networks.

### Best-fit business profiles

- businesses onboarding workers frequently;
- companies operating across several branches;
- franchises requiring consistent methods;
- businesses with high rework or callback costs;
- companies introducing new services;
- multilingual workforces;
- organisations relying heavily on subcontractors;
- businesses needing evidence of practical training;
- operators whose senior experts are approaching retirement or reducing field work.

---

## 4. User roles

| Role | Responsibility |
|---|---|
| Academy Owner | Product configuration, governance and commercial authority |
| Training Manager | Courses, pathways, enrolment and reporting |
| SOP Owner | Accountable for a procedure and its approved versions |
| Subject-Matter Expert | Supplies and reviews technical knowledge |
| Content Author | Builds lessons, media and assessments |
| Assessor | Reviews practical evidence and makes competency decisions |
| Supervisor | Observes work, signs off evidence and assigns remediation |
| Worker or Learner | Completes learning and submits evidence |
| Contractor Manager | Controls subcontractor pathways and access |
| Branch Manager | Monitors branch readiness and local assignments |
| Quality Manager | Uses quality failures to initiate training action |
| Auditor | Reads versions, evidence, decisions and audit history |
| Network Administrator | Controls master content across locations |

No user should be able to author, approve and certify their own competency where separation of duties is required.

---

## 5. Academy modes

### 5.1 Expert knowledge capture

Record an experienced worker performing a job and extract a governed procedure.

### 5.2 New-worker onboarding

Assign a role-based pathway containing orientation, safety, service standards, practical demonstrations and initial competency checks.

### 5.3 New-service rollout

Create and distribute training when a business launches a new service, product, machine, chemical or customer contract.

### 5.4 Refresher training

Reassign learning when an SOP changes, a competency approaches expiry or performance data indicates knowledge decay.

### 5.5 Remedial training

Create targeted learning after a failed audit, incident, complaint, rework event or supervisor concern.

### 5.6 Franchise standardisation

Publish master SOPs and courses to branches while controlling which sections may be localised.

### 5.7 Contractor qualification

Require subcontractors to complete relevant learning and practical verification before receiving certain job types.

### 5.8 Customer-specific induction

Deliver site, contract or customer-specific instructions before a worker attends a particular location.

### 5.9 Skills portability

Generate a verified internal skills passport showing what a person has been trained and assessed to do.

---

## 6. Core domain model

### Knowledge and SOP records

- `SopFamily`
- `SopVersion`
- `SopSection`
- `ProcedureStep`
- `DecisionBranch`
- `ToolRequirement`
- `MaterialRequirement`
- `HazardReference`
- `QualityCheckpoint`
- `SourceAsset`
- `SourceExcerpt`
- `ChangeSummary`
- `ApprovalDecision`

### Learning records

- `Course`
- `CourseVersion`
- `Module`
- `Lesson`
- `LearningObject`
- `LearningPathway`
- `PathwayRequirement`
- `Enrolment`
- `LessonProgress`
- `CompletionRecord`

### Assessment records

- `AssessmentDefinition`
- `QuestionBank`
- `QuestionVersion`
- `AssessmentAttempt`
- `ScenarioAssessment`
- `PracticalAssessment`
- `EvidenceRequirement`
- `EvidenceSubmission`
- `AssessorObservation`
- `AssessmentDecision`
- `Appeal`
- `Reassessment`

### Competency records

- `CompetencyDefinition`
- `CompetencyRequirement`
- `CompetencyAward`
- `CompetencyRestriction`
- `CompetencyExpiry`
- `RefresherAssignment`
- `RemedialPlan`
- `SkillsPassport`
- `PassportShareSession`

### Governance records

- `ContentApproval`
- `Publication`
- `BranchAdoption`
- `Localisation`
- `AuditEvent`
- `RetentionPolicy`
- `AccessPolicy`
- `AutomationRule`

Every versioned object must preserve the exact content that a learner viewed or an assessor used.

---

## 7. State models

### SOP lifecycle

```text
Draft
→ In review
→ Changes requested
→ Approved
→ Published
→ Superseded
→ Withdrawn
→ Archived
```

Published SOP versions are immutable. A correction creates a new version.

### Course lifecycle

```text
Draft
→ Internal preview
→ Review
→ Approved
→ Published
→ Retiring
→ Archived
```

A published course version remains attached to historic completion records.

### Enrolment lifecycle

```text
Assigned
→ Not started
→ In progress
→ Awaiting assessment
→ Completed
→ Overdue
→ Suspended
→ Cancelled
```

### Competency lifecycle

```text
Not assessed
→ Provisional
→ Competent
→ Restricted
→ Expiring
→ Expired
→ Suspended
→ Revoked
```

A worker may complete training without being declared competent. Completion and competency are separate facts.

### Practical assessment lifecycle

```text
Assigned
→ Evidence pending
→ Submitted
→ Assessor review
→ More evidence required
→ Competent
→ Not yet competent
→ Appealed
→ Reassessment required
```

---

## 8. Expert Capture Studio

Expert Capture Studio is the entry point for preserving operational knowledge.

### Supported inputs

- field video;
- body-camera or tripod recordings;
- screen recordings;
- voice explanation;
- photographs;
- existing SOP documents;
- manufacturer manuals;
- inspection records;
- WorkCore job notes;
- customer-specific instructions;
- quality audit evidence;
- annotated diagrams;
- existing training presentations.

### Capture session

A capture session records the subject-matter expert, service or task, property or asset context, equipment, materials, environmental conditions, job outcome, recording consent, source ownership and confidentiality classification.

### Ingestion pipeline

1. Upload into private tenant-scoped storage.
2. Validate file type and scan for malware.
3. Generate hashes and immutable source references.
4. Create lower-resolution working derivatives.
5. Isolate and enhance speech when required.
6. Transcribe with timestamps and speaker separation.
7. Detect likely procedural segments.
8. Extract candidate steps, tools, materials, hazards and decisions.
9. Link every generated statement to source timestamps or document excerpts.
10. Send the result to human review.

AI must never publish an SOP directly from raw media.

---

## 9. SOP Miner

SOP Miner is a feature within Titan Academy, not a separate app.

It converts source evidence into a reviewable procedure draft.

### Candidate extraction

The system can suggest:

- purpose;
- prerequisites;
- tools;
- consumables;
- personal protective equipment;
- setup steps;
- procedural steps;
- decision points;
- stop conditions;
- customer communication points;
- required evidence;
- quality criteria;
- cleanup and handover;
- escalation conditions.

### Source grounding

Each proposed item must reference a video timestamp, transcript segment, source photograph, document page, job record, manual excerpt or reviewer-added source.

### Confidence and disagreement

The system distinguishes extraction confidence, source coverage, model agreement and reviewer status. Where models disagree about step order, hazard meaning or required materials, the system surfaces the disagreement rather than creating false certainty.

### Missing-knowledge prompts

SOP Miner can ask:

- What conditions make this method unsafe?
- What mistake do new workers make most often?
- What would cause you to stop and call a supervisor?
- How do you verify the result?
- Which materials must never be combined?
- Which step changes for a different surface or asset?
- What customer approval is required?

---

## 10. SOP Studio

SOP Studio is the governed authoring environment.

### Document structure

A procedure can contain:

- purpose;
- scope;
- applicability;
- required competency;
- external credential prerequisites;
- tools and equipment;
- materials and consumables;
- PPE;
- hazards and controls;
- preparation;
- numbered steps;
- conditional branches;
- visual work instructions;
- quality checkpoints;
- required evidence;
- escalation;
- completion and handover;
- references;
- revision history.

### Rich and spatial authoring

The Tiptap-style document engine supports structured text, tables, callouts and embedded media.

The Konva-style spatial engine supports annotated photographs, equipment diagrams, room or site maps, correct and incorrect zones, numbered visual sequences, tool placement, treatment areas and before-and-after comparisons.

Structured procedural data remains separate from presentation JSON.

### Review workflow

Reviewers can comment on exact sections, request a source, mark technical risk, compare versions, approve selected sections, reject unsupported AI content, require safety or legal review and sign the final version.

---

## 11. Course and lesson builder

SOPs describe how work must be performed. Courses teach workers to understand and perform it.

### Learning-object types

- text lesson;
- short video;
- annotated image;
- interactive procedure;
- audio lesson;
- slide presentation;
- downloadable reference;
- guided scenario;
- demonstration clip;
- knowledge check;
- practical task;
- supervisor briefing.

### Course structure

```text
Course
→ modules
→ lessons
→ knowledge checks
→ practical assessment
→ competency decision
```

### Authoring from SOPs

A course can be generated from a published SOP version, but authors choose what to teach, demonstrate, test, verify practically and retain as reference material.

A later SOP revision triggers an impact analysis showing which lessons, questions and competencies may need revision.

---

## 12. Media production engine

### Video Editor engine

Reuse multi-track timelines, video, image, audio and text layers, trimming, splitting, speed, volume, overlays, queued FFmpeg rendering, render progress and reusable projects.

Extend it with:

- step markers;
- hazard callouts;
- correct/incorrect overlays;
- procedure-step links;
- source traceability;
- versioned exports;
- accessibility templates;
- brand-locked layouts.

### Clip extraction

Repurpose the viral-clip engine to identify clear demonstrations, decision explanations, hazard warnings, quality checks, common-error examples and customer communication examples.

The scoring objective becomes instructional clarity, not social-media virality.

### Voice isolation

Improve noisy field recordings while retaining the original evidence file.

### Captions

Support timestamp correction, terminology dictionaries, speaker labels, accessibility review, searchable transcripts and links from transcript segments to SOP steps.

### Avatar delivery

Optional avatars can present approved scripts, but the script remains the authority, synthetic presentation must be disclosed, likeness requires consent and avatar media cannot replace practical demonstration where physical technique matters.

---

## 13. Translation and localisation

### Translation workflow

1. Select an approved source version.
2. Lock technical terms and prohibited translations.
3. Generate a draft translation.
4. Apply company and industry glossary.
5. Human review high-risk content.
6. Generate subtitles, voiceover or dubbing.
7. Publish as a linked language variant.
8. Record which learners completed which language version.

### Requirements

- preserve measurements and units;
- retain warning severity;
- preserve product and chemical names;
- maintain source-version linkage;
- invalidate translations when the source changes materially;
- support right-to-left content where required;
- provide translated learner UI where possible.

---

## 14. Learning pathways

A pathway defines what a person must complete for a role or work type.

### Residential cleaner example

1. company onboarding;
2. customer privacy;
3. chemical handling;
4. equipment basics;
5. standard home-clean SOP;
6. bathroom and kitchen methods;
7. damage reporting;
8. photo evidence;
9. supervised practical clean;
10. initial competency decision.

### Restoration technician example

1. induction;
2. PPE and containment;
3. moisture assessment;
4. equipment setup;
5. evidence handling;
6. customer communication;
7. supervised site assessment;
8. practical remediation assessment;
9. restricted provisional competency;
10. full competency after required observations.

### Assignment rules

Assignments may depend on role, branch, employment type, contractor status, territory, service, customer contract, asset category, external credential, prior competency and quality history.

---

## 15. Enrolment and learner experience

### Learner home

The learner sees required now, due soon, practical assessments, expired or restricted skills, assigned refreshers, downloaded offline lessons, recent achievements and jobs requiring upcoming learning.

### Mobile-first principles

- large controls;
- short lessons;
- clear progress;
- minimal typing;
- audio alternatives;
- offline availability;
- resumable media;
- low-bandwidth derivatives;
- no unnecessary dashboard complexity.

### Notifications

Notifications can be sent for new assignments, approaching due dates, overdue learning, practical evidence requests, assessor feedback, competency expiry, SOP changes and restored competency.

---

## 16. Offline learning

### Offline package

An offline learning package may include approved lesson content, compressed media, questions, procedure references, practical-evidence requirements, expiry and revocation metadata.

### Offline rules

- packages are encrypted on device;
- access is scoped to the learner;
- sensitive customer information is excluded;
- completion is signed locally and synchronised later;
- server time and integrity checks run on sync;
- revoked or superseded content cannot be newly started after a defined grace period;
- practical evidence uploads remain queued until connectivity returns;
- conflict resolution never overwrites an assessor decision.

---

## 17. Knowledge and scenario assessments

### Question types

- single choice;
- multiple choice;
- ordering;
- matching;
- image hotspot;
- annotated-image identification;
- short structured answer;
- calculation;
- branching scenario;
- spoken response;
- evidence interpretation.

Each question has a version, source, learning objective, difficulty, rationale, required score, risk classification and review date.

### Scenario assessment

Scenarios test judgement rather than memory. They can assess safety, scope control, customer communication, escalation, evidence and quality.

Scoring rules are reviewable and versioned.

---

## 18. Practical field assessments

Practical evidence is the primary differentiator from a generic LMS.

### Assessment methods

- live supervisor observation;
- recorded demonstration;
- Titan Inspect evidence pack;
- customer-site observation;
- simulated training environment;
- equipment setup demonstration;
- completed work sample;
- oral technical explanation;
- repeated successful jobs.

### Evidence requirements

A practical assessment can require specified photo angles, video sequence, voice explanation, measurement, checklist, supervisor notes, asset or surface reference and completion within permitted conditions.

### Assessor rubric

The rubric can score preparation, safety, sequence, tool use, material selection, quality, efficiency, evidence, customer interaction, cleanup and escalation judgement.

### Outcomes

- competent;
- competent with restriction;
- provisional;
- more evidence required;
- not yet competent;
- suspended pending review.

AI can organise evidence and detect missing views. It cannot independently award a high-risk competency.

---

## 19. Crew Shadow

Crew Shadow is a feature within Titan Academy and Titan Quality.

With consent and a defined purpose, selected work recordings can be compared with the governing SOP. The system may identify omitted or reordered steps, missing evidence, excellent examples, inefficient repetition and points requiring supervisor review.

### Guardrails

- no continuous covert surveillance;
- explicit policy and worker notice;
- purpose limitation;
- restricted retention;
- human review before adverse action;
- separation between coaching and disciplinary processes;
- protected customer privacy;
- local legal review before deployment.

The preferred use is targeted coaching, not constant worker monitoring.

---

## 20. Competency framework

A competency states that a person can perform a defined activity under defined conditions.

It includes:

- activity;
- scope;
- permitted equipment;
- permitted materials;
- service or asset category;
- supervision level;
- location or branch constraints;
- prerequisite learning;
- prerequisite external credentials;
- assessment evidence;
- expiry or review interval;
- renewal rules.

### Competency sources

- Academy practical assessment;
- approved external recognition;
- supervisor observation;
- recognised prior learning;
- verified repeated performance;
- migration from a trusted legacy system.

### Restrictions

Examples include supervised-only work, equipment-model limits, residential-only service, standard service without restoration, or customer-specific induction requirements.

---

## 21. Skills Matrix and Skills Passport

### Skills Matrix

Views include team readiness, branch readiness, service launch readiness, upcoming expiry, assignment blockers, single-point-of-failure skills, training demand and assessor workload.

### Skills Passport

The passport may contain:

- identity reference from WorkCore;
- active competencies;
- restrictions;
- issue and expiry dates;
- assessment method;
- issuing organisation;
- internal certificates;
- selected external credential references;
- verification link;
- revocation status.

Share sessions use hashed tokens, expiry, revocation, scope, optional recipient binding and access audit.

---

## 22. Assignment gating in WorkCore

When WorkCore considers assigning a person, Academy returns:

- allowed;
- allowed with warning;
- allowed only under supervision;
- blocked;
- unknown because data is unavailable.

### Inputs

- worker;
- service;
- task;
- asset type;
- customer contract;
- branch;
- date;
- supervision;
- required competencies;
- external credential references.

### Rules

- published competency definitions only;
- current status at assignment time;
- full explanation;
- deterministic result;
- no hidden AI decision;
- offline cache with expiry;
- manager override only with authority and reason;
- overrides are audited;
- safety-critical hard blocks require configured emergency governance.

If Academy is unavailable, WorkCore may use a signed time-limited cache. Stale data produces a warning or block based on risk policy. Failure never implies competence.

---

## 23. Refresher and remedial training

### Refresher triggers

- time-based expiry;
- SOP change;
- new equipment or material;
- customer contract change;
- long period without performing the task;
- regulatory or company-policy change.

### Remedial triggers

- failed practical assessment;
- Titan Quality nonconformance;
- Titan Inspect finding;
- customer complaint;
- rework;
- incident;
- supervisor concern;
- repeated low-confidence execution evidence.

A remedial plan can include a targeted lesson, coaching session, repeat demonstration, supervised jobs, practical reassessment, temporary restriction or competency suspension.

---

## 24. Branch and franchise governance

A network can publish mandatory SOPs, role pathways, assessment rubrics, brand standards, quality expectations and required competencies.

A master item defines which fields are locked, locally extendable, locally replaceable or subject to central approval.

Branches can see required new versions, affected learners, local differences, completion deadlines and readiness status.

Network analytics include onboarding time, course completion, practical pass rate, overdue training, quality outcomes, skill coverage, content drift and assessor consistency.

---

## 25. Analytics

### Learning analytics

- assigned;
- started;
- completed;
- overdue;
- time to completion;
- attempt count;
- question difficulty;
- content drop-off;
- learner feedback.

### Competency analytics

- competency coverage;
- expiry risk;
- pass and reassessment rates;
- assessor variance;
- provisional-to-full conversion;
- restriction duration;
- job-assignment blocks.

### Operational analytics

- onboarding time to first unsupervised job;
- rework before and after training;
- quality score before and after training;
- incident recurrence;
- complaint recurrence;
- branch readiness;
- revenue unavailable because skills are missing.

Analytics identify patterns; they do not automatically punish workers.

---

## 26. AI assistance and assurance

### Allowed AI functions

- transcription;
- speaker separation;
- source segmentation;
- candidate step extraction;
- tool and material extraction;
- missing-section suggestions;
- lesson drafting;
- question drafting;
- glossary-assisted translation;
- caption generation;
- evidence organisation;
- assessment-feedback drafting;
- content-impact analysis.

### Restricted AI functions

AI may not independently publish an SOP, approve high-risk technical content, award or revoke a high-risk competency, make disciplinary findings, claim legal compliance or alter a worker record without an authorised decision.

Every generated object records provider, model, operation version, source references, timestamp, reviewer and accepted, changed or rejected status.

---

## 27. Security, privacy and trust

### Core controls

- tenant and branch scoping;
- policies at query and action boundaries;
- private source and assessment media;
- encryption in transit and at rest;
- customer face, address, plate and document redaction;
- consent records;
- retention classes;
- legal hold;
- secure deletion;
- hashed and expiring public tokens;
- provider-secret encryption;
- payload-redacted logs;
- comprehensive audit events.

Capture sessions record consent from workers, experts, customers or property representatives where required, and anyone whose likeness or voice will be reused.

---

## 28. Donor-engine map

| Donor extension | Reusable engine | Titan Academy use | Required redesign |
|---|---|---|---|
| VideoEditor | Multi-track projects and FFmpeg render jobs | Training editing and step-linked clips | Tenant scoping, private media, immutable source links and render reliability |
| AiCaptions | Caption and transcript jobs | Searchable training transcripts | Terminology review, speaker correction and provenance |
| AIVoiceIsolator | Speech enhancement | Improve noisy field instruction | Preserve original evidence and control provider cost |
| AiViralClips | Segment selection | Find clear demonstrations and warnings | Replace virality scoring with instructional-value scoring |
| VideoDubbing | Language and speaker handling | Multilingual training | Glossaries, human review and linked language versions |
| AiPersona and AiAvatar | Presenter and avatar state machines | Optional avatar-led lessons | Disclosure, likeness consent and script authority |
| OnboardingPro | Guided steps and scoring | Learner onboarding and pathway flows | Tenant scope, versioning and competency separation |
| Canvas | Rich Tiptap documents | SOP and lesson authoring | Strong authorisation, sanitisation and immutable publication |
| CreativeSuiteAITemplate | Structured Konva documents | Visual work instructions | Schema validation and source linkage |
| AiPresentation | Presentation generation | Instructor and executive training decks | Derivative-only output |
| ModelCouncil | Parallel review and synthesis | SOP completeness and disagreement review | Cost policy and human approval |
| ContentManager | Media organisation | Academy source and derivative library | Private storage and retention governance |
| ChatShare | Sharing interaction pattern | Skills passport and selected shares | Complete token-security redesign |
| Migration | Driver and mapping pattern | Import legacy SOPs and completion data | Dry runs, reconciliation and trust classification |

---

## 29. Donor-code findings and remediation

The archive provides strong media and workflow concepts, but the code should not be merged blindly.

### Observed risk themes

- very limited automated test coverage;
- public-sharing patterns requiring stronger tokens, scope, expiry and revocation;
- inconsistent ownership checks in some content and media actions;
- provider credentials requiring encrypted storage;
- asynchronous jobs requiring stronger retry, idempotency and failed-credit handling;
- duplicated provider and polling patterns;
- generated output treated as final content without enough review provenance.

### Mandatory remediation

1. Build one shared private media service.
2. Build one durable provider-job orchestration layer.
3. Make source lineage mandatory.
4. Make published versions immutable.
5. Separate completion from competency.
6. Require human decisions for practical competency.
7. Enforce tenant policies on every query and write.
8. Add comprehensive tests before donor code reaches production.
9. Treat avatars and translations as derivatives.
10. Never permit public training evidence by default.

---

## 30. Integrations and events

### WorkCore consumes and publishes

Academy consumes people, contractor identities, branches, teams, job context and supervisor relationships.

Academy publishes learning status, active competencies, restrictions, assignment decisions and expiry warnings.

### Other Titan apps

- Titan Inspect: practical evidence and observations.
- Titan Quality: nonconformance and remedial triggers.
- Titan Credential Vault: current external credentials.
- Titan Safety: safety learning and incident-triggered remediation.
- Titan Assets: equipment and asset categories.
- Titan Franchise Control: master governance.

### Commands

- `CreateSopDraft`
- `SubmitSopForReview`
- `PublishSopVersion`
- `CreateCourseFromSop`
- `AssignLearningPathway`
- `SubmitPracticalEvidence`
- `RecordAssessmentDecision`
- `AwardCompetency`
- `RestrictCompetency`
- `AssignRemedialPlan`
- `RequestAssignmentDecision`
- `CreateSkillsPassportShare`

### Events

- `SopVersionPublished`
- `CourseVersionPublished`
- `LearningAssigned`
- `LearningCompleted`
- `PracticalEvidenceSubmitted`
- `CompetencyAwarded`
- `CompetencyRestricted`
- `CompetencyExpired`
- `RemedialTrainingAssigned`
- `SkillsPassportShared`

External commands require idempotency keys.

---

## 31. Pricing

| Plan | AUD/month | Included scope |
|---|---:|---|
| Core | $0 | 5 learners, 10 SOPs, basic courses, BYO AI, 2 GB storage |
| Solo | $49 | 15 learners, 50 SOPs, practical assessments, skills matrix, 25 GB |
| Team | $149 | 75 learners, 250 SOPs, pathways, multilingual content, WorkCore gating, 150 GB |
| Academy Pro | $399 | 300 learners, multi-branch, advanced assessment, AI capture, API, 750 GB |
| Network | $999 | 1,000 learners, central governance, franchise inheritance, SSO, 3 TB |

### Additional usage

- additional active learner: approximately $2–$6/month;
- additional storage: transparent monthly blocks;
- Titan-managed transcription, translation, dubbing and avatar generation: credit based;
- BYO provider keys: no AI or media-provider markup;
- private single-tenant deployment: from $499/month additional;
- migration and setup: $499–$5,000 once-off.

An active learner is someone assigned or completing learning during the billing month. Archived workers should not consume a full active seat.

---

## 32. Delivery plan

### Phase 0 — Trust foundation

Tenant-scoped domain, private media, identity integration, audit events, immutable versions, durable jobs, access policies and WorkCore contracts.

### Phase 1 — Complete non-AI product

SOP Studio, course builder, pathways, enrolment, offline learning, quizzes, practical assessments, supervisor decisions, competencies, skills matrix, WorkCore assignment gating and reports.

This phase is already saleable.

### Phase 2 — Media production

Video projects, captions, transcript search, visual instructions, clip extraction, private rendering and accessible output.

### Phase 3 — Assisted knowledge capture

SOP Miner, source-grounded extraction, missing-section prompts, question drafting, impact analysis and controlled model review.

### Phase 4 — Localisation and network

Translation, dubbing, avatar delivery, franchise inheritance, branch readiness, SSO, skills-passport sharing and network APIs.

---

## 33. Testing strategy

### Unit tests

- SOP version rules;
- pathway prerequisites;
- competency state transitions;
- expiry;
- assignment decisions;
- scoring;
- override authority;
- token expiry;
- offline-package validation.

### Integration tests

- WorkCore identity and assignment;
- Titan Inspect evidence;
- Titan Quality remediation;
- Credential Vault dependencies;
- private storage;
- transcription and render jobs;
- provider failure and retry.

### Security tests

- cross-tenant access;
- insecure direct-object reference;
- public-token guessing;
- revoked-token access;
- malicious upload;
- script injection;
- credential exposure;
- redaction bypass.

### AI evaluation

- source attribution;
- procedural-step precision and recall;
- hazard extraction;
- hallucinated tools or materials;
- translation terminology;
- question correctness;
- model disagreement handling.

### Offline tests

- interrupted download;
- completion without connectivity;
- package revocation;
- clock drift;
- duplicate sync;
- evidence queue recovery;
- assessor conflict.

---

## 34. Release gates

Titan Academy must not enter production until:

- tenant isolation tests pass;
- published versions are immutable;
- practical competency cannot be awarded by AI alone;
- completion and competency are separate;
- assignment decisions are deterministic and explainable;
- public shares are hashed, scoped, expiring and revocable;
- raw media and assessment evidence are private;
- provider secrets are encrypted;
- failed asynchronous jobs retry safely;
- offline completion sync is idempotent;
- audit history covers competency-changing actions;
- accessibility checks pass;
- the non-AI workflow is complete before advanced AI is enabled.

---

## 35. Success metrics

### Commercial

- onboarding time reduced;
- supervisor training hours reduced;
- rework reduced;
- new-service deployment time reduced;
- contract readiness improved;
- retained revenue from improved skill coverage.

### Operational

- active workers with required competency;
- branch readiness;
- overdue learning;
- competency expiry exposure;
- time from assignment to competence;
- practical reassessment rate;
- assignment blocks avoided through early training.

### Quality

- quality score before and after training;
- complaint recurrence after remediation;
- nonconformance recurrence;
- assessor consistency;
- content accuracy;
- translation defects;
- unsupported AI suggestions rejected before publication.

---

## 36. Strategic role

Titan Academy converts operational knowledge into verified workforce capability.

```text
Expert performance
→ governed SOP
→ learning
→ practical evidence
→ competency
→ WorkCore assignment
→ job delivery
→ Titan Inspect and Quality evidence
→ targeted improvement
```

Its deepest value is the connection between how the business says work should be done, what a worker has learned, what they have demonstrated, what work the system permits them to receive and how actual quality feeds the next training decision.

That creates a defensible operational knowledge graph spanning services, SOPs, people, skills, equipment, customers, branches and real job outcomes.

---

## 37. Final recommendation

Build Titan Academy as a complete specialist application, with **practical competency and WorkCore assignment gating** as the commercial differentiator.

Do not lead with:

> Create courses with AI.

Lead with:

> **Capture how your best people work, prove who can do each job, and stop assigning work to people who are not ready.**

The initial release should be useful without AI. AI then reduces the cost of capturing, editing, translating and maintaining operational knowledge while human experts remain responsible for technical truth and competency decisions.
