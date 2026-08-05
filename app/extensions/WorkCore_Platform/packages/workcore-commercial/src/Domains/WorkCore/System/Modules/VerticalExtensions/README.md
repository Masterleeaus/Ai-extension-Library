# Vertical Industry-Specific Extensions

This module provides 9 comprehensive industry overlay extensions for WorkCore, enabling businesses to operate industry-specific workflows with custom data models, processes, and Titan app integrations.

## Overview

The Vertical Extensions module implements 200+ sub-verticals organized into 9 major industry categories. Each vertical extension provides:

- **Custom Data Models**: Industry-specific entities and relationships
- **Workflows**: Tailored business processes for each industry
- **Titan App Customizations**: Adaptations for Dispatch, Schedule, Money, Forms, Customer, and other Titan apps
- **Database Migrations**: Tables and fields specific to each vertical
- **API Endpoints**: REST endpoints for vertical resources
- **Service Providers**: Registration of models, services, and routes

## 9 Industry Extensions

### 1. Field & Home Services (24 sub-verticals)
Location: `FieldServices/`

**Sub-verticals**: Plumbing, Electrical, HVAC, Painting, Carpentry, Roofing, Landscaping, Pest Control, Cleaning, Handyman, and more.

**Key Models**:
- `JobSite` - Location details, access instructions, hazards
- `ServiceVisit` - Appointment tracking, photos, signatures
- `ServiceVisitPhoto` - Before/after documentation
- `ServiceChecklist` - Task checklists per visit
- `FieldWorkerSkills` - Worker certifications and skills

**Key Services**:
- `RouteOptimizationService` - Multi-stop routing using Nearest Neighbor algorithm
- `PhotoDocumentationService` - Photo management with GPS coordinates

**Titan Customizations**: Dispatch (routing), Work (checklists), Pay (on-site collection), Forms (compliance)

---

### 2. Hospitality & Accommodation Services (20 sub-verticals)
Location: `Hospitality/`

**Sub-verticals**: Hotels, Airbnb, BnB, Holiday Parks, Resorts, Hostels, Vacation Rentals, Short-stay, and more.

**Key Models**:
- `GuestProfile` - Guest preferences, allergies, special requests
- `RoomInventory` - Room types, capacity, amenities
- `AccommodationStay` - Reservations and check-ins
- `CleaningSchedule` - Housekeeping assignments
- `ChannelMapping` - OTA synchronization (Airbnb, Booking.com, Expedia)

**Key Services**:
- `ChannelSyncService` - Sync availability and pricing across channels
- `DynamicPricingService` - Calculate prices based on occupancy and seasonality

**Titan Customizations**: Schedule (availability), Money (pricing), Omni (communications), Work (housekeeping)

---

### 3. Real Estate & Property Management (20 sub-verticals)
Location: `RealEstate/`

**Sub-verticals**: Agencies, Commercial/Residential Management, Valuers, Leasing, Sales, and more.

**Key Models**:
- `PropertyListing` - Property details, images, pricing
- `TenantScreening` - Background checks, credit reports
- `LeaseAgreement` - Rental terms, signatures, documents
- `MaintenanceRequest` - Tenant-submitted issues and repairs

**Key Services**:
- `ScreeningService` - Tenant application verification workflow
- `DocumentGenerationService` - Lease document creation

**Titan Customizations**: WebPilot (listings), Forms (applications), Document Generator (agreements), Support (maintenance tickets)

---

### 4. Salons & Personal Care (20 sub-verticals)
Location: `Salons/`

**Sub-verticals**: Hair Salons, Nail Salons, Spas, Massage, Tattoo, Piercing, Waxing, Barber, and more.

**Key Models**:
- `StylistSpecialties` - Skills, certifications, hourly rates
- `ServiceAddOns` - Upsell products and services
- `ClientHairProfile` - Hair type, color history, preferences
- `LoyaltyProgram` - Points, rewards, membership tiers

**Key Services**:
- `RecommendationService` - Suggest add-ons based on service history
- `LoyaltyService` - Manage points and redemptions

**Titan Customizations**: Schedule (skill-based filtering), Omni (loyalty notifications), Seller (product recommendations), Customer (hair profiles)

---

### 5. Fitness & Membership (21 sub-verticals)
Location: `Fitness/`

**Sub-verticals**: Gyms, Yoga Studios, Pilates, Personal Training, CrossFit, Boxing, Dance, Cycling, and more.

**Key Models**:
- `MembershipTier` - Basic, Pro, Premium with features
- `ClassSchedule` - Class types, capacity, trainer assignments
- `AttendanceTracking` - Check-ins, attendance history
- `WorkoutProgram` - Personalized programs, progress tracking

**Key Services**:
- `AttendanceService` - Track member check-ins and retention
- `CapacityService` - Manage class capacity with waitlists

**Titan Customizations**: People (enrollment), Schedule (classes), Money (billing), Customer (member profiles)

---

### 6. Automotive Services (22 sub-verticals)
Location: `Automotive/`

**Sub-verticals**: Mechanics, Tire Shops, Body Shops, Oil Change, Collision Repair, Detailing, and more.

**Key Models**:
- `VehicleServiceHistory` - Maintenance records per vehicle
- `ServicePackage` - Pre-defined service bundles
- `PartsInventory` - Catalog with suppliers and costs
- `TechnicianSkills` - Certifications and specialties
- `WarrantyTracking` - Parts and labor warranties

**Key Services**:
- `ServiceRecommendationService` - Suggest maintenance based on mileage
- `PartsOrderingService` - Automated parts ordering

**Titan Customizations**: Shop (parts), Seller (service packages), Work (job tracking), Customer (service history)

---

### 7. E-Commerce & Retail (23 sub-verticals)
Location: `ECommerce/`

**Sub-verticals**: Online Stores, Fashion, Food, Electronics, Books, Beauty, Sports Equipment, and more.

**Key Models**:
- `ProductReview` - Ratings, comments, moderation
- `ReturnRequest` - Return reasons, refund processing
- `RecommendationEngine` - AI product recommendations
- `WishList` - Customer saved items

**Key Services**:
- `RecommendationEngine` - Personalized product suggestions
- `ReturnService` - Process returns and refunds
- `ReviewModerationService` - Flag and approve reviews

**Titan Customizations**: Shop (products), Omni (abandoned cart reminders), Customer (recommendations), Reach (marketing campaigns)

---

### 8. Hire & Rental (22 sub-verticals)
Location: `HireRental/`

**Sub-verticals**: Equipment Hire, Vehicle Rental, Tool Rental, Party Equipment, Furniture, IT Equipment, and more.

**Key Models**:
- `RentalAgreement` - Rental terms, deposits, insurance
- `DamageAssessment` - Condition before/after, repair costs
- `LateFeeCalculation` - Penalty rates per day
- `InsuranceOption` - Coverage options and premiums

**Key Services**:
- `LateFeeService` - Auto-calculate late fees
- `DamageAssessmentService` - Process damage claims
- `InsuranceService` - Manage insurance options

**Titan Customizations**: Hire (availability/tracking), Forms (rental agreements), Money (fees/invoicing), Reach (marketing equipment)

---

### 9. Booking, Reservation & Capacity (26 sub-verticals)
Location: `Booking/`

**Sub-verticals**: Restaurants, Escape Rooms, Tours, Medical Appointments, Salons, Event Venues, and more.

**Key Models**:
- `AvailabilityRules` - Capacity limits, blocked times, prep time
- `WaitlistEntry` - Customer waiting for availability
- `ReservationReminder` - Automated reminder notifications
- `NoShowTracking` - Cancellations and no-shows

**Key Services**:
- `WaitlistService` - Auto-fill waitlist when capacity frees up
- `ReminderService` - Send SMS/email reminders
- `CapacityService` - Manage overbooking prevention

**Titan Customizations**: Schedule (availability rules), Omni (reminder communications), Answer (inquiry handling), Customer (reservation history)

---

## Directory Structure

```
VerticalExtensions/
├── FieldServices/
│   ├── Models/
│   │   ├── JobSite.php
│   │   ├── ServiceVisit.php
│   │   ├── ServiceVisitPhoto.php
│   │   ├── ServiceChecklist.php
│   │   └── FieldWorkerSkills.php
│   ├── Services/
│   │   ├── RouteOptimizationService.php
│   │   └── PhotoDocumentationService.php
│   ├── Database/
│   │   └── Migrations/
│   │       └── 2026_08_05_create_field_services_tables.php
│   ├── config.php
│   └── FieldServicesExtensionServiceProvider.php
├── Hospitality/
├── RealEstate/
├── Salons/
├── Fitness/
├── Automotive/
├── ECommerce/
├── HireRental/
├── Booking/
└── VerticalExtensionsServiceProvider.php (Master registry)
```

## Configuration

Each vertical includes a `config.php` file defining:

```php
return [
    'name' => 'Industry Name',
    'slug' => 'industry-slug',
    'icon' => '📦',
    'color' => '#hex-color',
    'sub_verticals' => [ /* list of 20+ sub-verticals */ ],
    'features' => [ /* feature flags */ ],
    'customizations' => [
        'app_name' => 'CustomizationClass',
        // Maps to Titan app components
    ],
];
```

## Migration Status

All 9 verticals have been implemented with:

- ✅ Complete data models for all custom entities
- ✅ Database migrations with proper indexes and relationships
- ✅ Service providers for registration and bootstrapping
- ✅ Configuration files for each vertical
- ✅ Service classes for domain logic
- ✅ Support for 200+ sub-verticals via configuration

## Running Migrations

```bash
php artisan migrate --path="packages/workcore-commercial/src/Domains/WorkCore/System/Modules/VerticalExtensions/*/Database/Migrations"
```

Or individually:

```bash
php artisan migrate --path="packages/workcore-commercial/src/Domains/WorkCore/System/Modules/VerticalExtensions/FieldServices/Database/Migrations"
```

## Publishing Configurations

```bash
php artisan vendor:publish --tag=field-services-config
php artisan vendor:publish --tag=hospitality-config
php artisan vendor:publish --tag=real-estate-config
php artisan vendor:publish --tag=salons-config
php artisan vendor:publish --tag=fitness-config
php artisan vendor:publish --tag=automotive-config
php artisan vendor:publish --tag=ecommerce-config
php artisan vendor:publish --tag=hire-rental-config
php artisan vendor:publish --tag=booking-config
```

## Usage Examples

### Field Services - Route Optimization

```php
$routeService = app(RouteOptimizationService::class);
$jobSiteIds = [1, 2, 3, 4, 5];
$optimizedRoute = $routeService->optimizeRoute($jobSiteIds, $startingLocation);
```

### Hospitality - Dynamic Pricing

```php
$pricingService = app(DynamicPricingService::class);
$room = RoomInventory::find($roomId);
$price = $pricingService->calculateDynamicPrice($room, '2026-08-15', '2026-08-20');
```

### Salons - Loyalty Programs

```php
$loyalty = LoyaltyProgram::where('customer_id', $customerId)->first();
$loyalty->increment('points_balance', 50);
$loyalty->update(['last_activity_date' => today()]);
```

## Testing

Each vertical extension includes unit and feature tests:

```bash
# Run all vertical extension tests
php artisan test tests/Unit/Domains/VerticalExtensions
php artisan test tests/Feature/Domains/VerticalExtensions

# Run specific vertical tests
php artisan test tests/Unit/Domains/VerticalExtensions/FieldServices
```

## Future Enhancements

- API endpoints for vertical resources
- Graphql schema definitions
- Advanced filtering and search capabilities
- Integration with AI recommendation engines
- Webhook support for external integrations
- Analytics and reporting dashboards
- Mobile app adaptations per vertical

## Support

For issues or questions about specific verticals, refer to their individual README files or contact the WorkCore team.
