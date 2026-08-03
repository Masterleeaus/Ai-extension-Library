# Titan Unified Architecture

**Status**: ✅ New architecture for multi-vertical AI extensions  
**Date**: August 3, 2026  
**Approach**: Unified Titan services + Vertical-specific extensions  

---

## 🏗️ Architecture Overview

All 78 AI extensions organized into two categories:

### 1. **Titan Unified Services** (7 Core)
Shared infrastructure accessible by AIChatPro, Chatbot, AIAgent, and all verticals

### 2. **Vertical-Specific Extensions** (71)
Extensions specific to individual verticals (Health, E-commerce, Real Estate, Field Services)

---

## 🔹 Titan Unified Services (7)

### 1. **TitanChannels** (Communication)
Multi-channel customer engagement across all verticals

**Channels Included**:
- ✅ Voice (TTS/STT, voice sessions)
- ✅ Phone (IVR, call routing, automation)
- ✅ Email (SMTP, email automation)
- ✅ SMS (SMS messaging, OTP, notifications)
- ✅ Slack (team notifications, workflows)
- ✅ WhatsApp (customer messaging, media)
- ✅ Gmail (email with OAuth)

**Unified Features**:
- Channel abstraction layer (one API for all)
- Message routing and threading
- Contact/recipient management
- Message history and analytics
- Template management
- Webhook handling and security

**Issue**: [TBD] TitanChannels: Unified multi-channel communication

---

### 2. **TitanSkills** (AI Capabilities)
Unified custom AI skills and capabilities system

**Includes**:
- Custom skill definitions and registration
- Skill discovery and marketplace
- Skill versioning and rollback
- Skill testing framework
- Skill templates (Health, E-commerce, Real Estate, Field Services)
- Skill execution and result handling
- Skill analytics and usage tracking

**Issue**: [TBD] TitanSkills: Unified AI capabilities system

---

### 3. **TitanTools** (Action Execution)
Unified tools and actions execution engine

**Includes**:
- Tool definition and registration
- Cross-extension tool invocation
- Tool result formatting and caching
- Tool error handling and retries
- Tool authorization and scoping
- Tool rate limiting and quota management
- Tool monitoring and logging

**Issue**: [TBD] TitanTools: Unified action execution engine

---

### 4. **TitanMemory** (Contextual Memory)
Unified memory system for conversation context and state

**Includes**:
- Conversation context storage
- User context and profiles
- Session memory management
- Memory lifecycle and retention
- Cross-channel context continuity
- Memory search and retrieval
- Memory privacy and isolation

**Issue**: [TBD] TitanMemory: Unified context memory system

---

### 5. **TitanKnowledgeBase** (Knowledge Management)
Unified knowledge ingestion, indexing, and retrieval

**Includes**:
- Document ingestion (PDF, DOCX, images, text)
- Chunking and embedding
- Vector and keyword indexing
- Hybrid search (BM25 + vector)
- Knowledge assignments to agents/skills
- Source attribution and citations
- Knowledge versioning and updates
- Cross-tenant knowledge isolation

**Issue**: [TBD] TitanKnowledgeBase: Unified knowledge management system

---

### 6. **TitanStorage** (Cloud Storage)
Unified cloud storage abstraction

**Providers Included**:
- ✅ Google Drive
- ✅ Dropbox
- ✅ AWS S3
- ✅ Azure Blob Storage
- ✅ Local file storage

**Unified Features**:
- Provider abstraction (one API for all)
- File upload/download/delete
- Folder management
- Sharing and permissions
- Version control
- Preview generation
- Access logging and audit

**Issue**: [TBD] TitanStorage: Unified cloud storage gateway

---

### 7. **TitanDocs** (Document Collaboration)
Unified document and spreadsheet integration

**Integrations**:
- ✅ Google Docs
- ✅ Google Sheets
- ✅ Microsoft Word (OneDrive)
- ✅ Microsoft Excel (OneDrive)
- ✅ Notion (if applicable)

**Unified Features**:
- Document creation and editing
- Spreadsheet data sync
- Real-time collaboration
- Comment and mention handling
- Export and import
- Permission management
- Document templates

**Issue**: [TBD] TitanDocs: Unified document collaboration system

---

## 📋 Vertical-Specific Extensions (71)

### Category 1: Health Vertical (12-15)
**Pattern**: Keep original names, upgrade to use Titan services

Examples:
- HealthAssistant (stays as HealthAssistant)
- MedicalRecords (stays as MedicalRecords)
- PrescriptionManagement
- TelehealthScheduler
- PatientPortal
- SymptomChecker
- DrugInteractionChecker
- HealthDataAnalytics
- InsuranceVerification
- BillingManagement
- AppointmentReminder
- LabResultsViewer
- MedicationTracker

**How they use Titan services**:
- TitanChannels: Appointment reminders via SMS/Email/Voice
- TitanSkills: Medical assessment skills
- TitanTools: Insurance verification tools, Lab data tools
- TitanMemory: Patient context and medical history
- TitanKnowledgeBase: Drug database, condition information
- TitanStorage: Patient records, test results
- TitanDocs: Treatment plans, discharge summaries

---

### Category 2: E-commerce Vertical (12-15)
**Pattern**: Keep original names (ChatbotEcommerce stays, or rename to EcommerceAssistant), upgrade

Examples:
- **ChatbotEcommerce** (or rename to EcommerceAssistant)
- ProductCatalog
- ShoppingCart
- Checkout
- OrderManagement
- InventorySync
- PriceOptimization
- RecommendationEngine
- CustomerReviews
- LoyaltyProgram
- AbandonedCartRecovery
- ShippingIntegration
- WishlistManager

**How they use Titan services**:
- TitanChannels: Order updates via SMS/Email/WhatsApp
- TitanSkills: Product recommendation skills
- TitanTools: Price lookup, inventory check, shipping calculation
- TitanMemory: Shopping history, preferences, cart state
- TitanKnowledgeBase: Product information, FAQs
- TitanStorage: Product images, receipts, invoices
- TitanDocs: Order confirmations, shipping labels

---

### Category 3: Real Estate Vertical (12-15)
**Pattern**: Keep original names, upgrade

Examples:
- PropertyListing
- VirtualTours
- MortgageCalculator
- PropertyValuation
- NeighborhoodAnalysis
- InspectionScheduler
- DocumentManagement
- OfferManagement
- ApprovalWorkflow
- TransactionTracking
- LeadQualification
- AgenProfile
- ClientPortal

**How they use Titan services**:
- TitanChannels: Tour scheduling via Voice/Phone/SMS
- TitanSkills: Property valuation skills, lead qualification
- TitanTools: Comparable property analysis, mortgage calculations
- TitanMemory: Client preferences, viewing history
- TitanKnowledgeBase: Market data, property regulations
- TitanStorage: Property photos, documents, disclosures
- TitanDocs: Contracts, offer letters, disclosures

---

### Category 4: Field Services Vertical (12-15)
**Pattern**: Keep original names, upgrade

Examples:
- **ChatbotBooking** (stays as ChatbotBooking)
- ServiceScheduler
- TechnicianDispatch
- RouteOptimization
- ServiceAreaManagement
- WorkOrderManagement
- FieldChecklist
- CustomerSignature
- PaymentCollection
- EstimateGenerator
- TimeTracking
- TeamCoordination
- CustomerNotifications

**How they use Titan services**:
- TitanChannels: Appointment reminders & status via SMS/Voice/WhatsApp
- TitanSkills: Estimate generation, service categorization
- TitanTools: Route optimization, availability checking
- TitanMemory: Service history, customer preferences
- TitanKnowledgeBase: Service guidelines, troubleshooting
- TitanStorage: Before/after photos, receipts, certifications
- TitanDocs: Invoices, work orders, service agreements

---

### Category 5: Model & Provider Integrations (10)
**Pattern**: Keep as-is or rename to TitanModels sub-extensions

Currently:
- OpenAIRealtimeChat → OpenAI integration
- AzureOpenai → Azure integration
- Perplexity
- OpenRouter
- ModelCouncil
- NanoBanana
- MultiModel
- ElevenLabsVoiceChat
- AzureTTS

**Becomes**:
- TitanModels:OpenAI
- TitanModels:Azure
- TitanModels:Perplexity
- TitanModels:OpenRouter
- etc.

---

### Category 6: Creative AI Suite (26)
**Pattern**: Keep specific names, upgrade to use Titan services

Examples:
- **AIImagePro** (stays, but uses TitanStorage)
- **AiVideoPro** (stays, but uses TitanStorage)
- **AiMusic** (stays)
- Midjourney (AI image generation)
- FluxPro (image generation)
- ProductPhotography (product images)
- UGCCreator (user-generated content)
- VideoEditor
- VideoDubbing
- AIVoiceIsolator
- AiCaptions
- AiPresentation
- etc.

**How they use Titan services**:
- TitanStorage: Store generated content (images, videos, audio)
- TitanDocs: Create presentations with generated content
- TitanChannels: Share results via social channels
- TitanMemory: User preferences for generation styles

---

## 🔄 Migration Strategy

### Phase 1: Create Titan Services (Weeks 1-4)
1. **TitanChannels** - Multi-channel abstraction
2. **TitanSkills** - Unified skills system
3. **TitanTools** - Tool execution engine
4. **TitanMemory** - Context memory system

### Phase 2: Create Titan Data Services (Weeks 4-6)
5. **TitanKnowledgeBase** - Knowledge management
6. **TitanStorage** - Cloud storage gateway
7. **TitanDocs** - Document collaboration

### Phase 3: Upgrade Vertical-Specific (Weeks 6-12)
- All 71 vertical-specific extensions
- Integration tests with Titan services
- Multi-vertical scenario testing

### Phase 4: Legacy Extension Cleanup (Weeks 12+)
- Phase out old channel-specific extensions
- Migrate to TitanChannels
- Archive or deprecate single-use extensions

---

## 📊 Extension Reorganization

### Before (78 extensions, duplicated capabilities)
```
AIChatPro → Skills + Connectors
Chatbot → Skills + Channels (Voice, Booking)
AIAgent → Actions + Channels (Email, Slack, WhatsApp, Phone)
+ 72 other extensions
```

### After (78 extensions, unified Titan services)
```
7 Titan Services (Unified)
├── TitanChannels (Voice, Phone, Email, SMS, Slack, WhatsApp, Gmail)
├── TitanSkills (All custom AI capabilities)
├── TitanTools (All action execution)
├── TitanMemory (All context/conversation memory)
├── TitanKnowledgeBase (All knowledge management)
├── TitanStorage (Google Drive, Dropbox, AWS S3, Azure, Local)
└── TitanDocs (Google Docs, Sheets, MS Word, Excel, Notion)

+ 71 Vertical-Specific Extensions
├── Health (12-15 extensions)
├── E-commerce (12-15 extensions)
├── Real Estate (12-15 extensions)
├── Field Services (12-15 extensions)
├── Model/Provider Integrations (10 extensions)
└── Creative AI Suite (26 extensions - mostly unchanged)
```

---

## ✨ Key Architectural Benefits

### 1. **No Duplication**
- One TitanChannels instead of ChatbotVoice + AIAgentGmail + AIAgentSlackChannel + etc.
- One TitanSkills instead of AIChatProSkills + ChatbotSkills (if existed)
- All three main suites use the same underlying services

### 2. **Easier Integration**
- Extensions only need to know about Titan services
- Titan services handle cross-extension communication
- Clear separation of concerns

### 3. **Better Multi-Vertical**
- Vertical-specific extensions focus on domain logic
- Titan services handle all common concerns
- Easy to add new verticals

### 4. **Simplified Management**
- 7 core services to maintain
- 71 domain-specific extensions
- Clear upgrade paths

### 5. **Consistent UX**
- All channels work the same way (unified API)
- All skills registered the same way
- All memory works the same way
- All knowledge retrieval is unified

---

## 🚀 Next Steps

1. **Create GitHub issues for 7 Titan services**
2. **Create GitHub issues for 71 vertical-specific upgrades**
3. **Deprecate/close old channel-specific issues**
4. **Set up Titan service development (parallel teams)**
5. **Begin vertical-specific upgrades (after Titan foundation)**

---

## 📞 Questions?

This architecture:
- ✅ Eliminates duplication
- ✅ Simplifies integration
- ✅ Supports all verticals equally
- ✅ Scales to new channels and capabilities
- ✅ Maintains backward compatibility during migration

Ready to create issues for this new architecture?

---

**Status**: ✅ Architecture Defined  
**Next**: Create GitHub issues for Titan services and vertical-specific upgrades
