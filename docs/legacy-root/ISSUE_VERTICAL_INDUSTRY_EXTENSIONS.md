# Issue: Implement Vertical Industry-Specific Extensions

**Status:** BACKLOG | Phase 3 | Epic  
**Priority:** High  
**Effort:** 6-8 weeks  
**Depends on:** All core WorkCore domains + missing domain implementations  
**Blocks:** Production-ready vertical market coverage  

## Problem Statement

While WorkCore provides ERP backbone and Titan apps provide operational interfaces, businesses need industry-specific customizations to the apps and workflows. The 9 industry overlays require extension modules that adapt generic Titan apps to specific vertical needs.

## Overview

Each industry overlay below represents a category with multiple sub-verticals (200+ total). Each overlay needs:
1. Custom data models (industry-specific fields)
2. Custom workflows (industry-specific processes)
3. Custom validations and business rules
4. Vertical-specific AI prompts (via Titan Chatbot Builder)
5. Industry forms and UI customization

---

## 1. Field & Home Services Extension

**Sub-verticals:** 24 (Cleaners, Plumbers, Electricians, Painters, etc.)

### Custom Models
- JobSite (location-specific details, access notes, hazards)
- ServiceVisit (arrival time, completion time, photos, notes)
- FieldWorkerSkills (certifications per technician)

### Custom Workflows
- Multi-stop job routing (optimize routes across multiple homes)
- Photo documentation workflow (before/after photos required)
- On-site signature verification (customer sign-off)
- Safety inspection checklist

### Titan App Customizations
- **Titan Dispatch**: Route optimization for multiple daily stops
- **Titan Work**: Task checklists tied to specific service types
- **Titan Pay**: On-site payment collection with invoice
- **Titan Forms**: Compliance forms (safety, hazard reports)

### Files to Create
```
app/Domains/WorkCore/VerticalExtensions/FieldServices/
├── Models/JobSite.php
├── Models/FieldWorkerSkills.php
├── Services/RouteOptimizationService.php
├── Workflows/PhotoDocumentationWorkflow.php
└── FieldServicesExtensionServiceProvider.php
```

---

## 2. BnB, Hotel & Rooming Services Extension

**Sub-verticals:** 20 (Hotels, Airbnb, Holiday Parks, etc.)

### Custom Models
- GuestProfile (preferences, allergies, special requests)
- RoomInventory (room types, capacity, amenities)
- CleaningSchedule (housekeeping assignments)
- ChannelMapping (Airbnb, Booking.com, Expedia sync)

### Custom Workflows
- Channel synchronization (sync availability/pricing across platforms)
- Dynamic pricing based on demand/seasonality
- Housekeeping assignment optimization
- Guest communication automation

### Titan App Customizations
- **Titan Schedule**: Dynamic availability synced to channels
- **Titan Money**: Dynamic pricing calculations
- **Titan Omni**: Automated guest communications (confirmations, reminders)
- **Titan Work**: Housekeeping task automation

### Files to Create
```
app/Domains/WorkCore/VerticalExtensions/Hospitality/
├── Models/GuestProfile.php
├── Models/RoomInventory.php
├── Services/ChannelSyncService.php
├── Services/DynamicPricingService.php
└── HospitalityExtensionServiceProvider.php
```

---

## 3. Real Estate Extension

**Sub-verticals:** 20 (Agencies, Property Management, Valuers, etc.)

### Custom Models
- PropertyListing (bedrooms, bathrooms, features, location)
- TenantScreening (background check, credit report, references)
- LeaseAgreement (rental terms, conditions, signatures)
- MaintenanceRequest (tenant-submitted issues)

### Custom Workflows
- Tenant screening workflow (application → verification → approval)
- Lease document generation (customizable templates)
- Maintenance request workflow (submit → assign → completion)
- Virtual tour scheduling

### Titan App Customizations
- **Titan WebPilot**: Property listing portal
- **Titan Forms**: Tenant application forms with screening questions
- **Titan Document Generator**: Lease document creation
- **Titan Support**: Maintenance request ticketing

### Files to Create
```
app/Domains/WorkCore/VerticalExtensions/RealEstate/
├── Models/PropertyListing.php
├── Models/TenantScreening.php
├── Services/ScreeningService.php
├── Services/DocumentGenerationService.php
└── RealEstateExtensionServiceProvider.php
```

---

## 4. Salons & Personal Care Extension

**Sub-verticals:** 20 (Hair Salons, Spas, Tattoo, etc.)

### Custom Models
- StylistSpecialties (skills, certifications per stylist)
- ServiceAddOns (upsell products/services)
- ClientHairProfile (hair type, color history, preferences)
- LoyaltyProgram (points, rewards, membership tiers)

### Custom Workflows
- Stylist skill-based appointment booking
- Service add-on recommendations
- Product retail integration
- Loyalty rewards workflow

### Titan App Customizations
- **Titan Schedule**: Stylist skills and specialty filtering
- **Titan Omni**: Loyalty points notifications and reminders
- **Titan Seller**: Product recommendations based on service
- **Titan Customer**: Hair profile and service history

### Files to Create
```
app/Domains/WorkCore/VerticalExtensions/Salons/
├── Models/StylistSpecialties.php
├── Models/ServiceAddOns.php
├── Services/RecommendationService.php
└── SalonsExtensionServiceProvider.php
```

---

## 5. Fitness & Membership Extension

**Sub-verticals:** 21 (Gyms, Yoga Studios, Personal Trainers, etc.)

### Custom Models
- MembershipTier (Basic, Pro, Premium with features)
- ClassSchedule (class types, capacity, trainer assignments)
- AttendanceTracking (check-ins, attendance history)
- WorkoutProgram (personalized programs, progress tracking)

### Custom Workflows
- Membership upgrade/downgrade
- Class capacity management with waitlist
- Attendance tracking and retention analytics
- Trainer assignment workflow

### Titan App Customizations
- **Titan People**: Membership enrollment and management
- **Titan Schedule**: Class scheduling with capacity limits
- **Titan Money**: Recurring membership billing
- **Titan Customer**: Member profile with attendance history

### Files to Create
```
app/Domains/WorkCore/VerticalExtensions/Fitness/
├── Models/ClassSchedule.php
├── Models/WorkoutProgram.php
├── Services/AttendanceService.php
└── FitnessExtensionServiceProvider.php
```

---

## 6. Automotive Services Extension

**Sub-verticals:** 22 (Mechanics, Tire Shops, Body Shops, etc.)

### Custom Models
- VehicleServiceHistory (maintenance records per vehicle)
- PartsInventory (parts catalog with suppliers)
- TechnicianSkills (certifications, specialties)
- WarrantyTracking (parts and labor warranties)
- ServicePackage (pre-defined service bundles)

### Custom Workflows
- Service package selection
- Parts ordering workflow
- Technician assignment based on skills
- Warranty claim processing

### Titan App Customizations
- **Titan Shop**: Parts inventory management
- **Titan Seller**: Service package bundles
- **Titan Work**: Service job tracking with tech assignments
- **Titan Customer**: Vehicle service history and reminders

### Files to Create
```
app/Domains/WorkCore/VerticalExtensions/Automotive/
├── Models/VehicleServiceHistory.php
├── Models/ServicePackage.php
├── Models/WarrantyTracking.php
└── AutomotiveExtensionServiceProvider.php
```

---

## 7. E-Commerce & Retail Extension

**Sub-verticals:** 23 (Online Stores, Fashion Retailers, Food Retailers, etc.)

### Custom Models
- ProductReview (ratings, comments, moderation)
- ReturnRequest (return reasons, refund processing)
- RecommendationEngine (AI product recommendations)
- WishList (customer saved items)

### Custom Workflows
- Return and refund processing
- Review moderation workflow
- Recommendation algorithm
- Abandoned cart recovery

### Titan App Customizations
- **Titan Shop**: E-commerce product management
- **Titan Omni**: Abandoned cart reminder emails
- **Titan Customer**: Recommendation engine based on purchase history
- **Titan Reach**: Marketing campaigns for top products

### Files to Create
```
app/Domains/WorkCore/VerticalExtensions/ECommerce/
├── Models/ProductReview.php
├── Models/ReturnRequest.php
├── Services/RecommendationEngine.php
└── ECommerceExtensionServiceProvider.php
```

---

## 8. Hire & Rental Extension

**Sub-verticals:** 22 (Equipment Hire, Vehicle Rental, etc.)

### Custom Models
- RentalAgreement (rental terms, conditions, security deposit)
- DamageAssessment (condition before/after, damage tracking)
- LateFeeCalculation (penalty rates per day)
- InsuranceOption (coverage options, premium calculations)

### Custom Workflows
- Rental agreement generation
- Damage assessment workflow (photos and inspection)
- Late fee auto-calculation
- Equipment condition verification

### Titan App Customizations
- **Titan Hire**: Equipment availability and rental tracking
- **Titan Forms**: Rental agreement customization
- **Titan Money**: Late fee calculations and invoicing
- **Titan Reach**: Marketing available equipment

### Files to Create
```
app/Domains/WorkCore/VerticalExtensions/HireRental/
├── Models/RentalAgreement.php
├── Models/DamageAssessment.php
├── Services/LateFeeService.php
└── HireRentalExtensionServiceProvider.php
```

---

## 9. Booking, Reservation & Capacity Extension

**Sub-verticals:** 26 (Restaurants, Escape Rooms, Tours, etc.)

### Custom Models
- AvailabilityRules (capacity limits, blocked times, prep time)
- WaitlistEntry (customer waiting for availability)
- ReservationReminder (automated reminders via SMS/email)
- NoShowTracking (tracking cancellations and no-shows)

### Custom Workflows
- Capacity management with overbooking prevention
- Waitlist auto-filling when capacity frees up
- Automated reminder notifications
- No-show tracking and penalties

### Titan App Customizations
- **Titan Schedule**: Advanced availability rules and capacity management
- **Titan Omni**: Automated reminder communications
- **Titan Answer**: Booking inquiry handling
- **Titan Customer**: Reservation history

### Files to Create
```
app/Domains/WorkCore/VerticalExtensions/Booking/
├── Models/AvailabilityRules.php
├── Models/WaitlistEntry.php
├── Services/WaitlistService.php
├── Services/ReminderService.php
└── BookingExtensionServiceProvider.php
```

---

## Cross-Cutting Implementation Requirements

All industry extensions must include:

### 1. Vertical Configuration
```php
// app/Domains/WorkCore/VerticalExtensions/[Vertical]/config.php
return [
    'name' => 'Industry Name',
    'slug' => 'industry-slug',
    'icon' => '📦',
    'color' => '#hex-color',
    'features' => ['feature-1', 'feature-2'],
    'customizations' => [
        'app_name' => 'app-customization-class',
        'workflow_name' => 'workflow-class',
    ],
];
```

### 2. Service Provider
- Register custom models
- Register services
- Publish migrations
- Register API routes

### 3. Migrations
- Create vertical-specific database tables
- Add vertical-specific fields to existing tables

### 4. Tests
- Unit tests for custom models
- Feature tests for workflows
- Integration tests with WorkCore domains

### 5. API Endpoints
- REST endpoints for vertical-specific resources
- Integration with existing Titan app APIs

### 6. AI Customization
- Vertical-specific prompts via Chatbot Builder
- Industry-specific recommendations

## Phased Implementation Plan

### Phase 1 (Weeks 1-2): Hospitality + Field Services
- Priority: Highest demand, well-defined workflows
- Outcome: Airbnb-like platform, field service app

### Phase 2 (Weeks 3-4): Real Estate + Fitness
- Priority: High demand, clear UI patterns
- Outcome: Property rental platform, gym management

### Phase 3 (Weeks 5-6): Retail + Automotive + Salons
- Priority: Medium-high demand, complex workflows
- Outcome: E-commerce platform, service management

### Phase 4 (Weeks 7-8): Hiring + Booking
- Priority: Remaining, lower immediate demand
- Outcome: Complete market coverage

## Success Criteria

- [ ] All 9 industry extensions implemented
- [ ] Each vertical has custom data models
- [ ] Workflows properly implemented and tested
- [ ] Titan app customizations working
- [ ] API endpoints functional
- [ ] AI prompts customized per vertical
- [ ] Comprehensive test coverage (>80%)
- [ ] Documentation complete
- [ ] All sub-verticals supported
