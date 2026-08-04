<?php

namespace App\Extensions\TitanQuattroTools\System;

/**
 * Titan Quattro Tools Registry
 * 
 * Quattro tools are reusable, technical capabilities that Trio agents use.
 * A tool performs a function but does not decide why the function is being used.
 * 
 * Organized into 16 categories with 165+ tools total.
 */
class TitanQuattroToolsRegistry
{
    /**
     * 12 Messaging Tools
     */
    public static function getMessagingTools(): array
    {
        return [
            ['id' => 'quattro.messaging.sms', 'name' => 'SMS Tool', 'description' => 'Sends and receives SMS messages through connected providers'],
            ['id' => 'quattro.messaging.email', 'name' => 'Email Tool', 'description' => 'Sends, receives and tracks email communication'],
            ['id' => 'quattro.messaging.whatsapp', 'name' => 'WhatsApp Tool', 'description' => 'Exchanges approved WhatsApp messages and attachments'],
            ['id' => 'quattro.messaging.messenger', 'name' => 'Messenger Tool', 'description' => 'Connects Titan Zero workflows to Facebook Messenger'],
            ['id' => 'quattro.messaging.instagram', 'name' => 'Instagram Messaging Tool', 'description' => 'Handles approved Instagram direct-message interactions'],
            ['id' => 'quattro.messaging.telegram', 'name' => 'Telegram Tool', 'description' => 'Sends and receives Telegram messages'],
            ['id' => 'quattro.messaging.in_app', 'name' => 'In-App Messaging Tool', 'description' => 'Delivers messages inside Titan Zero applications'],
            ['id' => 'quattro.messaging.push_notification', 'name' => 'Push Notification Tool', 'description' => 'Sends mobile and browser notifications'],
            ['id' => 'quattro.messaging.voice_call', 'name' => 'Voice Call Tool', 'description' => 'Places, receives and routes voice calls'],
            ['id' => 'quattro.messaging.voicemail', 'name' => 'Voicemail Tool', 'description' => 'Records, transcribes and routes voicemail'],
            ['id' => 'quattro.messaging.video', 'name' => 'Video Communication Tool', 'description' => 'Creates or joins supported video meetings'],
            ['id' => 'quattro.messaging.delivery', 'name' => 'Communication Delivery Tool', 'description' => 'Tracks delivery, failure, retry and channel fallback'],
        ];
    }

    /**
     * 11 Channel Tools
     */
    public static function getChannelTools(): array
    {
        return [
            ['id' => 'quattro.channel.web_chat', 'name' => 'Web Chat Tool', 'description' => 'Connects Titan Zero to embedded website chat'],
            ['id' => 'quattro.channel.customer_portal', 'name' => 'Customer Portal Tool', 'description' => 'Provides functions through the customer portal'],
            ['id' => 'quattro.channel.resident_portal', 'name' => 'Resident Portal Tool', 'description' => 'Provides resident and tenant self-service actions'],
            ['id' => 'quattro.channel.participant_portal', 'name' => 'Participant Portal Tool', 'description' => 'Provides accessible participant-facing actions'],
            ['id' => 'quattro.channel.worker_app', 'name' => 'Worker App Tool', 'description' => 'Provides mobile functions for workers and technicians'],
            ['id' => 'quattro.channel.contractor_portal', 'name' => 'Contractor Portal Tool', 'description' => 'Provides job, quote, evidence and invoice functions'],
            ['id' => 'quattro.channel.owner_portal', 'name' => 'Owner Portal Tool', 'description' => 'Provides approvals, statements and portfolio information'],
            ['id' => 'quattro.channel.provider_portal', 'name' => 'Provider Portal Tool', 'description' => 'Provides operational and compliance functions'],
            ['id' => 'quattro.channel.public_form', 'name' => 'Public Form Tool', 'description' => 'Creates and processes public forms without account requirement'],
            ['id' => 'quattro.channel.qr_code', 'name' => 'QR Code Tool', 'description' => 'Creates QR links for payments, forms, assets, rooms and jobs'],
            ['id' => 'quattro.channel.kiosk', 'name' => 'Kiosk Tool', 'description' => 'Provides shared-device check-in and service functions'],
        ];
    }

    /**
     * 10 Google Workspace Tools
     */
    public static function getGoogleWorkspaceTools(): array
    {
        return [
            ['id' => 'quattro.google.gmail', 'name' => 'Gmail Tool', 'description' => 'Reads, drafts, sends, labels and organises authorised Gmail messages'],
            ['id' => 'quattro.google.calendar', 'name' => 'Google Calendar Tool', 'description' => 'Reads availability and creates or updates calendar events'],
            ['id' => 'quattro.google.drive', 'name' => 'Google Drive Tool', 'description' => 'Searches, stores, retrieves and organises authorised Drive files'],
            ['id' => 'quattro.google.docs', 'name' => 'Google Docs Tool', 'description' => 'Creates and updates structured documents'],
            ['id' => 'quattro.google.sheets', 'name' => 'Google Sheets Tool', 'description' => 'Reads, writes and analyses spreadsheet data'],
            ['id' => 'quattro.google.slides', 'name' => 'Google Slides Tool', 'description' => 'Creates and updates presentations'],
            ['id' => 'quattro.google.forms', 'name' => 'Google Forms Tool', 'description' => 'Creates and processes form responses'],
            ['id' => 'quattro.google.contacts', 'name' => 'Google Contacts Tool', 'description' => 'Finds authorised contact information'],
            ['id' => 'quattro.google.meet', 'name' => 'Google Meet Tool', 'description' => 'Creates meeting links and scheduled sessions'],
            ['id' => 'quattro.google.maps', 'name' => 'Google Maps Tool', 'description' => 'Provides addresses, distance, route and location functions'],
        ];
    }

    /**
     * 8 Microsoft Workspace Tools
     */
    public static function getMicrosoftWorkspaceTools(): array
    {
        return [
            ['id' => 'quattro.microsoft.outlook', 'name' => 'Outlook Mail Tool', 'description' => 'Reads, drafts, sends and organises authorised Outlook email'],
            ['id' => 'quattro.microsoft.calendar', 'name' => 'Microsoft Calendar Tool', 'description' => 'Reads availability and manages calendar events'],
            ['id' => 'quattro.microsoft.onedrive', 'name' => 'OneDrive Tool', 'description' => 'Stores, retrieves and organises files'],
            ['id' => 'quattro.microsoft.sharepoint', 'name' => 'SharePoint Tool', 'description' => 'Accesses controlled organisational documents and lists'],
            ['id' => 'quattro.microsoft.word', 'name' => 'Word Tool', 'description' => 'Creates and updates documents'],
            ['id' => 'quattro.microsoft.excel', 'name' => 'Excel Tool', 'description' => 'Reads, writes and analyses workbooks'],
            ['id' => 'quattro.microsoft.teams', 'name' => 'Teams Tool', 'description' => 'Sends messages and coordinates meetings and channels'],
            ['id' => 'quattro.microsoft.forms', 'name' => 'Microsoft Forms Tool', 'description' => 'Creates and processes forms'],
        ];
    }

    /**
     * 10 Property and Accommodation Tools
     */
    public static function getPropertyAccommodationTools(): array
    {
        return [
            ['id' => 'quattro.property.register', 'name' => 'Property Register Tool', 'description' => 'Reads and updates properties, buildings, rooms and units'],
            ['id' => 'quattro.property.occupancy', 'name' => 'Occupancy Register Tool', 'description' => 'Reads and updates residents, tenancies, rooms and vacancies'],
            ['id' => 'quattro.property.agreement', 'name' => 'Agreement Tool', 'description' => 'Generates, stores and tracks tenancy, residency and service agreements'],
            ['id' => 'quattro.property.condition', 'name' => 'Condition Report Tool', 'description' => 'Creates condition reports and stores supporting evidence'],
            ['id' => 'quattro.property.bond', 'name' => 'Bond Tool', 'description' => 'Records and coordinates authorised bond workflows'],
            ['id' => 'quattro.property.inspection', 'name' => 'Inspection Tool', 'description' => 'Creates inspections, checklists, findings and follow-up actions'],
            ['id' => 'quattro.property.access', 'name' => 'Access Control Tool', 'description' => 'Creates, updates and revokes keys, codes and digital credentials'],
            ['id' => 'quattro.property.visitor', 'name' => 'Visitor Register Tool', 'description' => 'Records visitors and authorised access periods'],
            ['id' => 'quattro.property.vacancy', 'name' => 'Vacancy Publishing Tool', 'description' => 'Publishes and removes approved vacancy listings'],
            ['id' => 'quattro.property.mapping', 'name' => 'Property Mapping Tool', 'description' => 'Maps buildings, floors, rooms, assets and service locations'],
        ];
    }

    /**
     * 11 Field-Service Tools
     */
    public static function getFieldServiceTools(): array
    {
        return [
            ['id' => 'quattro.field.work_order', 'name' => 'Work Order Tool', 'description' => 'Creates and updates work orders'],
            ['id' => 'quattro.field.scheduling', 'name' => 'Job Scheduling Tool', 'description' => 'Creates and modifies service appointments'],
            ['id' => 'quattro.field.dispatch', 'name' => 'Dispatch Tool', 'description' => 'Assigns workers and contractors to jobs'],
            ['id' => 'quattro.field.route', 'name' => 'Route Optimisation Tool', 'description' => 'Calculates and adjusts efficient job routes'],
            ['id' => 'quattro.field.gps', 'name' => 'GPS Location Tool', 'description' => 'Provides authorised location and arrival information'],
            ['id' => 'quattro.field.time_tracking', 'name' => 'Time Tracking Tool', 'description' => 'Records attendance, travel and time on site'],
            ['id' => 'quattro.field.checklist', 'name' => 'Checklist Tool', 'description' => 'Presents and records dynamic job checklists'],
            ['id' => 'quattro.field.photo_evidence', 'name' => 'Photo Evidence Tool', 'description' => 'Captures and stores job and inspection images'],
            ['id' => 'quattro.field.signature', 'name' => 'Signature Tool', 'description' => 'Captures authorised digital signatures'],
            ['id' => 'quattro.field.quote', 'name' => 'Quote Tool', 'description' => 'Creates, requests and compares quotes'],
            ['id' => 'quattro.field.invoice_capture', 'name' => 'Invoice Capture Tool', 'description' => 'Receives and extracts contractor or supplier invoices'],
        ];
    }

    /**
     * 10 NDIS and Support Tools
     */
    public static function getNdisTools(): array
    {
        return [
            ['id' => 'quattro.ndis.participant_record', 'name' => 'Participant Record Tool', 'description' => 'Reads and updates authorised participant records'],
            ['id' => 'quattro.ndis.plan_information', 'name' => 'Plan Information Tool', 'description' => 'Stores structured plan dates, categories and supplied plan information'],
            ['id' => 'quattro.ndis.service_agreement', 'name' => 'Service Agreement Tool', 'description' => 'Generates and tracks participant service agreements'],
            ['id' => 'quattro.ndis.support_delivery', 'name' => 'Support Delivery Tool', 'description' => 'Records delivered supports and service evidence'],
            ['id' => 'quattro.ndis.support_roster', 'name' => 'Support Roster Tool', 'description' => 'Creates and updates support shifts'],
            ['id' => 'quattro.ndis.goal_tracking', 'name' => 'Goal Tracking Tool', 'description' => 'Records participant-stated goals and linked service outcomes'],
            ['id' => 'quattro.ndis.consent', 'name' => 'Consent Register Tool', 'description' => 'Records, expires and withdraws consent'],
            ['id' => 'quattro.ndis.behaviour_support', 'name' => 'Behaviour Support Tool', 'description' => 'Manages authorised behaviour-support information'],
            ['id' => 'quattro.ndis.assistive_tech', 'name' => 'Assistive Technology Tool', 'description' => 'Tracks equipment, maintenance, warranties and repairs'],
            ['id' => 'quattro.ndis.claim', 'name' => 'Claim Preparation Tool', 'description' => 'Builds validated claim records and batches'],
        ];
    }

    /**
     * 13 Finance and Payment Tools
     */
    public static function getFinanceTools(): array
    {
        return [
            ['id' => 'quattro.finance.invoice', 'name' => 'Invoice Tool', 'description' => 'Generates, sends and tracks invoices'],
            ['id' => 'quattro.finance.payment', 'name' => 'Payment Tool', 'description' => 'Accepts and records supported payment methods'],
            ['id' => 'quattro.finance.zeropay', 'name' => 'ZeroPay Tool', 'description' => 'Coordinates Titan Zero payment workflows and payment status'],
            ['id' => 'quattro.finance.bank_feed', 'name' => 'Bank Feed Tool', 'description' => 'Imports authorised bank transactions'],
            ['id' => 'quattro.finance.reconciliation', 'name' => 'Reconciliation Tool', 'description' => 'Matches financial records with external transactions'],
            ['id' => 'quattro.finance.trust_ledger', 'name' => 'Trust Ledger Tool', 'description' => 'Records controlled trust-account transactions and balances'],
            ['id' => 'quattro.finance.rent_ledger', 'name' => 'Rent Ledger Tool', 'description' => 'Records rent charges, payments, adjustments and arrears'],
            ['id' => 'quattro.finance.expense', 'name' => 'Expense Tool', 'description' => 'Records and categorises business expenses'],
            ['id' => 'quattro.finance.accounts_payable', 'name' => 'Accounts Payable Tool', 'description' => 'Manages supplier bills and payment approval'],
            ['id' => 'quattro.finance.accounts_receivable', 'name' => 'Accounts Receivable Tool', 'description' => 'Manages customer balances and payment follow-up'],
            ['id' => 'quattro.finance.payroll', 'name' => 'Payroll Tool', 'description' => 'Transfers authorised timesheet and payroll information'],
            ['id' => 'quattro.finance.accounting_integration', 'name' => 'Accounting Integration Tool', 'description' => 'Connects with supported accounting systems'],
            ['id' => 'quattro.finance.subscription', 'name' => 'Subscription Tool', 'description' => 'Manages plans, trials, subscriptions and entitlements'],
        ];
    }

    /**
     * 10 CRM and Commercial Tools
     */
    public static function getCrmTools(): array
    {
        return [
            ['id' => 'quattro.crm.crm', 'name' => 'CRM Tool', 'description' => 'Creates and updates people, organisations, leads and opportunities'],
            ['id' => 'quattro.crm.lead_capture', 'name' => 'Lead Capture Tool', 'description' => 'Creates leads from forms, calls, messages and referrals'],
            ['id' => 'quattro.crm.pipeline', 'name' => 'Pipeline Tool', 'description' => 'Tracks opportunities through configurable stages'],
            ['id' => 'quattro.crm.proposal', 'name' => 'Proposal Tool', 'description' => 'Generates and tracks proposals'],
            ['id' => 'quattro.crm.campaign', 'name' => 'Campaign Tool', 'description' => 'Creates and manages marketing campaigns'],
            ['id' => 'quattro.crm.referral', 'name' => 'Referral Tool', 'description' => 'Records and tracks referrals and partner relationships'],
            ['id' => 'quattro.crm.feedback', 'name' => 'Customer Feedback Tool', 'description' => 'Collects and stores feedback'],
            ['id' => 'quattro.crm.survey', 'name' => 'Survey Tool', 'description' => 'Creates and processes surveys'],
            ['id' => 'quattro.crm.marketplace', 'name' => 'Marketplace Tool', 'description' => 'Publishes service opportunities to approved providers'],
            ['id' => 'quattro.crm.pricing', 'name' => 'Pricing Tool', 'description' => 'Calculates rates, discounts, margins and pricing rules'],
        ];
    }

    /**
     * 12 Compliance and Quality Tools
     */
    public static function getComplianceTools(): array
    {
        return [
            ['id' => 'quattro.compliance.register', 'name' => 'Compliance Register Tool', 'description' => 'Stores obligations, owners, due dates and evidence'],
            ['id' => 'quattro.compliance.credential', 'name' => 'Credential Register Tool', 'description' => 'Stores licences, checks, training and expiry dates'],
            ['id' => 'quattro.compliance.incident', 'name' => 'Incident Register Tool', 'description' => 'Creates and maintains incident records'],
            ['id' => 'quattro.compliance.complaint', 'name' => 'Complaint Register Tool', 'description' => 'Creates and tracks complaints and resolutions'],
            ['id' => 'quattro.compliance.risk', 'name' => 'Risk Register Tool', 'description' => 'Creates and updates risk records and controls'],
            ['id' => 'quattro.compliance.hazard', 'name' => 'Hazard Register Tool', 'description' => 'Creates and manages hazards and safety actions'],
            ['id' => 'quattro.compliance.corrective_action', 'name' => 'Corrective Action Tool', 'description' => 'Creates, assigns and verifies corrective actions'],
            ['id' => 'quattro.compliance.audit', 'name' => 'Audit Tool', 'description' => 'Creates audits, samples, findings and evidence packs'],
            ['id' => 'quattro.compliance.policy', 'name' => 'Policy Control Tool', 'description' => 'Manages controlled policies, approvals and versions'],
            ['id' => 'quattro.compliance.training', 'name' => 'Training Register Tool', 'description' => 'Assigns and records required training'],
            ['id' => 'quattro.compliance.emergency', 'name' => 'Emergency Management Tool', 'description' => 'Maintains emergency plans, contacts, drills and responses'],
            ['id' => 'quattro.compliance.certificate', 'name' => 'Certificate Verification Tool', 'description' => 'Records and checks compliance certificates'],
        ];
    }

    /**
     * 15 Document and Media Tools
     */
    public static function getDocumentTools(): array
    {
        return [
            ['id' => 'quattro.document.generation', 'name' => 'Document Generation Tool', 'description' => 'Produces documents from controlled templates'],
            ['id' => 'quattro.document.pdf', 'name' => 'PDF Tool', 'description' => 'Creates, reads, combines and extracts PDF documents'],
            ['id' => 'quattro.document.spreadsheet', 'name' => 'Spreadsheet Tool', 'description' => 'Creates, reads and edits spreadsheet files'],
            ['id' => 'quattro.document.presentation', 'name' => 'Presentation Tool', 'description' => 'Creates and updates presentation files'],
            ['id' => 'quattro.document.template', 'name' => 'Template Tool', 'description' => 'Stores and applies approved business templates'],
            ['id' => 'quattro.document.signature', 'name' => 'Electronic Signature Tool', 'description' => 'Sends documents for signature and records completion'],
            ['id' => 'quattro.document.extraction', 'name' => 'Document Extraction Tool', 'description' => 'Extracts structured information from documents'],
            ['id' => 'quattro.document.ocr', 'name' => 'OCR Tool', 'description' => 'Reads text from scanned images when other extraction methods are unavailable'],
            ['id' => 'quattro.document.image_analysis', 'name' => 'Image Analysis Tool', 'description' => 'Analyses authorised images for operational evidence'],
            ['id' => 'quattro.document.image_generation', 'name' => 'Image Generation Tool', 'description' => 'Produces approved visual assets'],
            ['id' => 'quattro.document.audio_transcription', 'name' => 'Audio Transcription Tool', 'description' => 'Converts recorded audio into text'],
            ['id' => 'quattro.document.speech_generation', 'name' => 'Speech Generation Tool', 'description' => 'Converts approved text into voice'],
            ['id' => 'quattro.document.video_analysis', 'name' => 'Video Analysis Tool', 'description' => 'Extracts approved information from video evidence'],
            ['id' => 'quattro.document.storage', 'name' => 'File Storage Tool', 'description' => 'Stores and retrieves files'],
            ['id' => 'quattro.document.conversion', 'name' => 'File Conversion Tool', 'description' => 'Converts files between supported formats'],
        ];
    }

    /**
     * 16 Data and Intelligence Tools
     */
    public static function getDataTools(): array
    {
        return [
            ['id' => 'quattro.data.query', 'name' => 'Database Query Tool', 'description' => 'Reads authorised records from the platform database'],
            ['id' => 'quattro.data.write', 'name' => 'Database Write Tool', 'description' => 'Creates or updates authorised records'],
            ['id' => 'quattro.data.search', 'name' => 'Search Tool', 'description' => 'Searches platform records and approved knowledge sources'],
            ['id' => 'quattro.data.knowledge_base', 'name' => 'Knowledge Base Tool', 'description' => 'Retrieves approved policies, procedures and guides'],
            ['id' => 'quattro.data.vector_search', 'name' => 'Vector Search Tool', 'description' => 'Finds semantically related authorised information'],
            ['id' => 'quattro.data.reporting', 'name' => 'Reporting Tool', 'description' => 'Produces structured reports from platform data'],
            ['id' => 'quattro.data.analytics', 'name' => 'Analytics Tool', 'description' => 'Calculates metrics, trends and comparisons'],
            ['id' => 'quattro.data.forecasting', 'name' => 'Forecasting Tool', 'description' => 'Produces approved forecasts from historical data'],
            ['id' => 'quattro.data.import', 'name' => 'Data Import Tool', 'description' => 'Imports validated external data'],
            ['id' => 'quattro.data.export', 'name' => 'Data Export Tool', 'description' => 'Exports authorised records'],
            ['id' => 'quattro.data.validation', 'name' => 'Data Validation Tool', 'description' => 'Checks data types, required fields and relationships'],
            ['id' => 'quattro.data.duplicate_detection', 'name' => 'Duplicate Detection Tool', 'description' => 'Identifies possible duplicate records'],
            ['id' => 'quattro.data.merge', 'name' => 'Data Merge Tool', 'description' => 'Merges records after approval'],
            ['id' => 'quattro.data.deidentification', 'name' => 'De-Identification Tool', 'description' => 'Removes or transforms identifying information'],
            ['id' => 'quattro.data.backup', 'name' => 'Backup Tool', 'description' => 'Creates protected system and record backups'],
            ['id' => 'quattro.data.restore', 'name' => 'Restore Tool', 'description' => 'Restores authorised backup data'],
        ];
    }

    /**
     * 11 AI and Model Tools
     */
    public static function getAiModelTools(): array
    {
        return [
            ['id' => 'quattro.ai.gateway', 'name' => 'AI Model Gateway Tool', 'description' => 'Routes requests between approved AI providers and models'],
            ['id' => 'quattro.ai.byo_api', 'name' => 'BYO API Tool', 'description' => 'Uses customer-supplied AI credentials under configured policies'],
            ['id' => 'quattro.ai.managed', 'name' => 'Managed AI Tool', 'description' => 'Uses Titan-managed AI under customer consent and entitlement rules'],
            ['id' => 'quattro.ai.local', 'name' => 'Local AI Tool', 'description' => 'Runs supported models on customer-controlled infrastructure'],
            ['id' => 'quattro.ai.ollama', 'name' => 'Ollama Tool', 'description' => 'Connects to authorised Ollama models'],
            ['id' => 'quattro.ai.device', 'name' => 'Device AI Tool', 'description' => 'Runs supported AI tasks directly on user devices'],
            ['id' => 'quattro.ai.vision', 'name' => 'Vision Model Tool', 'description' => 'Provides visual document and image analysis'],
            ['id' => 'quattro.ai.voice', 'name' => 'Voice Model Tool', 'description' => 'Provides speech recognition and voice generation'],
            ['id' => 'quattro.ai.embedding', 'name' => 'Embedding Tool', 'description' => 'Creates vector representations for authorised search'],
            ['id' => 'quattro.ai.prompt_template', 'name' => 'Prompt Template Tool', 'description' => 'Stores controlled prompts and output schemas'],
            ['id' => 'quattro.ai.evaluation', 'name' => 'Model Evaluation Tool', 'description' => 'Tests model accuracy, safety and suitability'],
            ['id' => 'quattro.ai.safety', 'name' => 'Content Safety Tool', 'description' => 'Checks generated or supplied content against configured policies'],
        ];
    }

    /**
     * 13 Workflow and Automation Tools
     */
    public static function getWorkflowTools(): array
    {
        return [
            ['id' => 'quattro.workflow.engine', 'name' => 'Workflow Engine Tool', 'description' => 'Starts and advances structured business workflows'],
            ['id' => 'quattro.workflow.task', 'name' => 'Task Tool', 'description' => 'Creates, assigns and updates tasks'],
            ['id' => 'quattro.workflow.approval', 'name' => 'Approval Tool', 'description' => 'Requests and records human approval'],
            ['id' => 'quattro.workflow.rules', 'name' => 'Rules Engine Tool', 'description' => 'Applies configurable business and compliance rules'],
            ['id' => 'quattro.workflow.event_bus', 'name' => 'Event Bus Tool', 'description' => 'Publishes and receives platform events'],
            ['id' => 'quattro.workflow.scheduler', 'name' => 'Scheduler Tool', 'description' => 'Runs actions at configured times'],
            ['id' => 'quattro.workflow.trigger', 'name' => 'Trigger Tool', 'description' => 'Starts workflows when defined conditions occur'],
            ['id' => 'quattro.workflow.queue', 'name' => 'Queue Tool', 'description' => 'Manages background and delayed execution'],
            ['id' => 'quattro.workflow.retry', 'name' => 'Retry Tool', 'description' => 'Repeats failed operations under controlled rules'],
            ['id' => 'quattro.workflow.escalation', 'name' => 'Escalation Tool', 'description' => 'Routes overdue or high-risk work to authorised people'],
            ['id' => 'quattro.workflow.notification', 'name' => 'Notification Tool', 'description' => 'Creates operational alerts and reminders'],
            ['id' => 'quattro.workflow.webhook', 'name' => 'Webhook Tool', 'description' => 'Exchanges supported events with external systems'],
            ['id' => 'quattro.workflow.api_connector', 'name' => 'API Connector Tool', 'description' => 'Connects to external APIs through approved credentials'],
        ];
    }

    /**
     * 13 Identity, Security and Governance Tools
     */
    public static function getSecurityTools(): array
    {
        return [
            ['id' => 'quattro.security.authentication', 'name' => 'Authentication Tool', 'description' => 'Confirms user identity'],
            ['id' => 'quattro.security.permissions', 'name' => 'Role and Permission Tool', 'description' => 'Checks and applies user permissions'],
            ['id' => 'quattro.security.company_context', 'name' => 'Company Context Tool', 'description' => 'Resolves the active company and tenancy context'],
            ['id' => 'quattro.security.relationship_context', 'name' => 'Relationship Context Tool', 'description' => 'Resolves the user\'s Uno manager relationships'],
            ['id' => 'quattro.security.consent', 'name' => 'Consent Enforcement Tool', 'description' => 'Checks consent before accessing or sharing information'],
            ['id' => 'quattro.security.secrets', 'name' => 'Secret Management Tool', 'description' => 'Protects API keys and system credentials'],
            ['id' => 'quattro.security.encryption', 'name' => 'Encryption Tool', 'description' => 'Encrypts protected information'],
            ['id' => 'quattro.security.audit_log', 'name' => 'Audit Log Tool', 'description' => 'Records actions, changes, approvals and access'],
            ['id' => 'quattro.security.policy', 'name' => 'Policy Enforcement Tool', 'description' => 'Applies platform governance and automation policies'],
            ['id' => 'quattro.security.rate_limit', 'name' => 'Rate Limit Tool', 'description' => 'Limits tool and integration usage'],
            ['id' => 'quattro.security.fraud_detection', 'name' => 'Fraud Detection Tool', 'description' => 'Flags suspicious activity or transaction patterns'],
            ['id' => 'quattro.security.agent_isolation', 'name' => 'Agent Isolation Tool', 'description' => 'Restricts an agent to its defined data and tools'],
            ['id' => 'quattro.security.emergency_disable', 'name' => 'Emergency Disable Tool', 'description' => 'Immediately disables an unsafe agent, tool or integration'],
        ];
    }

    /**
     * 11 Device, Offline and Infrastructure Tools
     */
    public static function getDeviceTools(): array
    {
        return [
            ['id' => 'quattro.device.offline_queue', 'name' => 'Offline Queue Tool', 'description' => 'Stores actions until connectivity returns'],
            ['id' => 'quattro.device.sync', 'name' => 'Sync Tool', 'description' => 'Synchronises authorised device and server records'],
            ['id' => 'quattro.device.conflict_resolution', 'name' => 'Conflict Resolution Tool', 'description' => 'Resolves competing versions of a record'],
            ['id' => 'quattro.device.identity', 'name' => 'Device Identity Tool', 'description' => 'Identifies authorised devices and nodes'],
            ['id' => 'quattro.device.node_registry', 'name' => 'Node Registry Tool', 'description' => 'Registers distributed Titan devices and services'],
            ['id' => 'quattro.device.signal', 'name' => 'Signal Tool', 'description' => 'Records runtime, business and device signals'],
            ['id' => 'quattro.device.telemetry', 'name' => 'Telemetry Tool', 'description' => 'Captures health, performance and execution information'],
            ['id' => 'quattro.device.log', 'name' => 'Log Tool', 'description' => 'Stores structured application and agent logs'],
            ['id' => 'quattro.device.health_check', 'name' => 'Health Check Tool', 'description' => 'Tests modules, integrations and services'],
            ['id' => 'quattro.device.module_registry', 'name' => 'Module Registry Tool', 'description' => 'Records installed modules and capabilities'],
            ['id' => 'quattro.device.capability_registry', 'name' => 'Capability Registry Tool', 'description' => 'Records available assistants, agents and tools'],
        ];
    }

    /**
     * Get all tools organized by category
     */
    public static function getAllTools(): array
    {
        return [
            'Messaging' => self::getMessagingTools(),
            'Channels' => self::getChannelTools(),
            'Google Workspace' => self::getGoogleWorkspaceTools(),
            'Microsoft Workspace' => self::getMicrosoftWorkspaceTools(),
            'Property & Accommodation' => self::getPropertyAccommodationTools(),
            'Field Service' => self::getFieldServiceTools(),
            'NDIS & Support' => self::getNdisTools(),
            'Finance & Payment' => self::getFinanceTools(),
            'CRM & Commercial' => self::getCrmTools(),
            'Compliance & Quality' => self::getComplianceTools(),
            'Document & Media' => self::getDocumentTools(),
            'Data & Intelligence' => self::getDataTools(),
            'AI & Models' => self::getAiModelTools(),
            'Workflow & Automation' => self::getWorkflowTools(),
            'Security & Governance' => self::getSecurityTools(),
            'Device & Infrastructure' => self::getDeviceTools(),
        ];
    }

    /**
     * Get total tool count
     */
    public static function getTotalToolCount(): int
    {
        $count = 0;
        foreach (self::getAllTools() as $category => $tools) {
            $count += count($tools);
        }
        return $count;
    }

    /**
     * Get tool by ID
     */
    public static function getToolById(string $toolId): ?array
    {
        foreach (self::getAllTools() as $tools) {
            foreach ($tools as $tool) {
                if ($tool['id'] === $toolId) {
                    return $tool;
                }
            }
        }
        return null;
    }

    /**
     * Get tools by category
     */
    public static function getToolsByCategory(string $category): array
    {
        $allTools = self::getAllTools();
        return $allTools[$category] ?? [];
    }

    /**
     * Get category list with tool count
     */
    public static function getCategoryIndex(): array
    {
        $index = [];
        foreach (self::getAllTools() as $category => $tools) {
            $index[$category] = count($tools);
        }
        return $index;
    }
}
