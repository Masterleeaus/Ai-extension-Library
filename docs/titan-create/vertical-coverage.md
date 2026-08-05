# Titan Zero Vertical Coverage

This document is the canonical vertical taxonomy and app-mode composition contract for Titan Zero, Titan Hub, Titan Create, WorkCore integrations, and the Chatbot extension.

## Architecture rules

- Titan Zero has **nine canonical verticals**.
- A tenant may enable one or more vertical packs across its business profiles.
- Each resolved business profile has one primary vertical and may expose approved secondary vertical capabilities.
- A vertical describes **what kind of business this is**.
- An App Mode describes **how customers discover, request, schedule, reserve, buy, hire, rent, join, subscribe, apply, or transact**.
- A business has one primary App Mode and may enable multiple supporting App Modes.
- Shared capabilities such as bookings, capacity, memberships, facilities, commerce, field work, inventory and rentals must be composed rather than duplicated.
- `Facilities` is a cross-vertical capability primarily used by Field and Home Services, Real Estate, accommodation and booking businesses; it is not a separate tenth vertical.
- General commerce capabilities are owned by E-commerce and Retail and may be reused by other verticals.
- Booking, Reservation and Capacity-Based Businesses is both a complete vertical and a reusable capacity capability for businesses in other verticals.
- Hire and Rental owns rentable-asset lifecycle capabilities; Booking and Capacity owns time-slot, seat, room, staff and constrained-resource reservation capabilities.
- User-facing vertical packs, recipes, templates, prompts, data bindings, app manifests, navigation, chatbot tools and automation rules must use these canonical identifiers.
- Do not create a new UI system for vertical packs or App Modes. Modify or duplicate the closest existing CreativeSuite, Canvas, FashionStudio, QRPay, Titan Hub, Chatbot or related donor surface and retain its established architecture and visual language.

## Dual-overlay composition

```text
Titan Core
+ one primary Vertical Profile
+ optional secondary vertical capabilities
+ one primary App Mode
+ zero or more supporting App Modes
+ business subtype configuration
+ tenant permissions, providers and branding
= resolved business experience
```

Examples:

```text
Field and Home Services
+ primary mode: service
+ supporting modes: booking, quote_first, sales
```

```text
BnB, Hotel and Rooming Services
+ primary mode: accommodation
+ supporting modes: reservation, booking, service, sales, hire
```

```text
Fitness and Membership Businesses
+ primary mode: membership
+ supporting modes: class, booking, subscription, sales
```

Vertical identity and transaction behaviour must remain independent. A plumber and a cleaner share a vertical but may activate different mode stacks. A hotel and a rooming house share a vertical but may use different combinations of accommodation, reservation, rental, application and service.

## Canonical vertical identifiers

| Display name | Stable slug |
|---|---|
| Field and Home Services | `field-home-services` |
| BnB, Hotel and Rooming Services | `accommodation-rooming` |
| Real Estate | `real-estate` |
| Salons and Personal Care | `salons-personal-care` |
| Fitness and Membership Businesses | `fitness-membership` |
| Automotive Services | `automotive-services` |
| E-commerce and Retail | `ecommerce-retail` |
| Hire and Rental | `hire-rental` |
| Booking, Reservation and Capacity-Based Businesses | `booking-capacity` |

## Legacy vertical aliases

Existing Titan Hub, WorkCore, Chatbot and extension values must be normalised without losing configuration or history.

| Legacy value | Canonical slug | Rule |
|---|---|---|
| `field_home_services` | `field-home-services` | Direct normalisation. |
| `field_service` | `field-home-services` | Preserve service, scheduling, location and evidence configuration. |
| `bnb_hotel_rooming` | `accommodation-rooming` | Direct normalisation. |
| `hotel_bnb` | `accommodation-rooming` | Preserve accommodation and reservation configuration. |
| `rooming_house` | `accommodation-rooming` | Convert to business subtype `rooming-house`; do not retain a separate vertical. |
| `real_estate` | `real-estate` | Direct normalisation. |
| `real_estate_property` | `real-estate` | Preserve property, owner, tenant, inspection and maintenance configuration. |
| `salons_personal_care` | `salons-personal-care` | Direct normalisation. |
| `salon_personal_care` | `salons-personal-care` | Direct normalisation. |
| `fitness_membership` | `fitness-membership` | Direct normalisation. |
| `automotive_services` | `automotive-services` | Direct normalisation. |
| `automotive` | `automotive-services` | Preserve vehicle, workshop and service-history configuration. |
| `ecommerce_retail` | `ecommerce-retail` | Direct normalisation. |
| `hire_rental` | `hire-rental` | Direct normalisation. |
| `booking_reservation_capacity` | `booking-capacity` | Direct normalisation. |
| `booking_capacity` | `booking-capacity` | Preserve resource, seat, room, staff and capacity configuration. |
| `facilities` | `field-home-services` or `real-estate` | Resolve from tenant intent. Contractors default to Field and Home Services; portfolio and asset managers default to Real Estate. Record subtype and provenance. |

Alias migration must be idempotent, versioned and auditable. It must preserve records, routes, permissions, templates, provider connections, customer activity and historical reporting.

## Canonical App Modes

App Modes are reusable customer and transaction patterns. They do not own identity, tenancy, customer, invoice, payment or ledger authority.

### Service and scope modes

#### `service`

For work, treatment, maintenance, repair, assistance, inspections, professional delivery or another service outcome.

Contributes:

- service catalogue and scope intake;
- service address, delivery location or remote-delivery context;
- duration, options, add-ons, staff skills and service area;
- work status, evidence, completion, support and rebooking;
- service-aware chatbot tools.

#### `quote_first`

For work where scope or price must be assessed before commitment.

Contributes:

- structured requirements, measurements, quantities, photos, files and notes;
- site-visit requests;
- quote creation, revision, expiry, acceptance and rejection;
- deposit, milestone and variation preparation.

### Time, appointment, reservation and capacity modes

#### `booking`

For appointments or exclusive time slots involving staff, rooms, chairs, bays, courts, equipment or another resource.

Contributes:

- availability, dates, times, duration, buffers, lead times, cutoffs and timezone;
- staff and resource selection;
- waitlist, rescheduling, cancellation, no-show and rebooking.

#### `reservation`

For temporarily holding a table, room, unit, site, vehicle, asset or other resource before or during fulfilment.

Contributes:

- hold expiry and confirmation;
- arrival, collection, check-in and release rules;
- guest, participant or attendee details;
- deposit and cancellation-policy presentation.

#### `capacity_booking`

For limited places, seats, admissions, shared resources or concurrent capacity rather than one exclusive appointment.

Contributes:

- capacity pools and remaining-place projections;
- participant quantities and categories;
- group limits, waitlists and transfer rules;
- capacity-aware cancellation and overbooking policy.

#### `class`

For classes, lessons, courses, workshops, programs and recurring sessions.

Contributes:

- timetable, enrolment, term, pass, attendance, prerequisite and instructor metadata;
- single-session, pack, membership and subscription eligibility;
- guardian and dependent support where configured.

#### `event_ticketing`

For events, attractions, functions, performances, sessions and ticketed admissions.

Contributes:

- ticket types, allocations, QR entry, transfers and attendee details;
- date, venue, capacity and access rules;
- event cancellation and refund-policy presentation.

#### `transport_booking`

For tours, charters, buses, transfers, excursions and route-based bookings.

Contributes:

- route, pickup, destination, passenger, luggage and timing fields;
- vehicle or vessel capacity;
- manifests, check-in and customer-safe travel status.

#### `accommodation`

For overnight, short-stay, long-stay, rooming, serviced apartment, campsite and similar stays.

Contributes:

- property, room, bed, unit, site, occupancy, guest and stay dates;
- check-in, checkout, minimum stay, cleaning, amenities and incidentals;
- guest services, extensions, deposits, bonds and stay status;
- rooming-house and long-stay terms where configured.

### Goods and order modes

#### `sales`

For products, parts, packages, merchandise and other purchasable goods.

Contributes:

- products, SKUs, variants, quantities, inventory projections, cart, checkout and order status;
- shipping, delivery, pickup, click-and-collect, returns and exchanges;
- recommendations and reorder actions.

#### `order_ahead`

For prepared goods or services ordered for future pickup, delivery, a table, an event or scheduled collection.

Contributes:

- preparation lead time and cutoff;
- pickup or delivery windows;
- modifiers, substitutions, fulfilment status and collection instructions.

### Asset-access modes

#### `hire`

For short-term equipment, vehicle, room, clothing, event, tool, machinery or asset hire.

Contributes:

- hire period, quantity, asset allocation, collection, delivery and return;
- bond, condition evidence, extension, late return, damage and replacement rules;
- optional operator, installation or service add-ons.

#### `rental`

For recurring or longer-term property, storage, workspace, vehicle, equipment or asset rental.

Contributes:

- rental term, recurring obligations, renewal, notice, inspection and condition reports;
- deposit, bond, recurring payment, arrears, extension and return or vacate rules.

### Recurring-relationship modes

#### `membership`

For customer, club, gym, association, access, loyalty or entitlement memberships.

Contributes:

- tiers, eligibility, enrolment, benefits, access rights, usage limits, renewal, pause and cancellation;
- member QR or credential presentation.

#### `subscription`

For recurring products, services, plans, boxes, training, maintenance or scheduled fulfilment.

Contributes:

- billing cadence, renewal, pause, skip, change, cancellation, entitlement, usage and recurring fulfilment.

### Discovery, listing, intermediation and application modes

#### `listing`

For discovery of properties, vehicles, spaces, assets, opportunities or other listings not necessarily purchased through cart checkout.

Contributes:

- listing search, filters, media, documents, favourites, enquiries and status;
- inspection, expression-of-interest and agent-contact actions.

#### `marketplace`

For multiple suppliers, contractors, sellers, providers, owners, hosts or operators offering through one tenant or network.

Contributes:

- provider attribution, offers, comparison, assignment, commissions or allocations;
- fulfilment responsibility and marketplace support boundaries.

#### `application`

For rental applications, finance enquiries, assessed memberships, tenant placement, enrolment, eligibility and approval workflows.

Contributes:

- applicant details, documents, consent and declarations;
- screening status, requests for information, approval, rejection and withdrawal.

## App Mode selection rules

1. Every resolved business profile has one `primary_mode`.
2. A profile may enable multiple unique `supporting_modes`.
3. `primary_mode` controls home ordering, default navigation emphasis, onboarding emphasis and initial chatbot intent.
4. A mode is enabled only when its authoritative backend capability, permission, plan entitlement and required provider configuration are available.
5. Vertical defaults are recommendations. Onboarding proposes them and the tenant confirms them.
6. A business subtype may recommend extra modes but cannot silently activate them.
7. Laravel resolves the final mode graph. Flutter, Titan Create and Chatbot do not infer modes from the business name or vertical alone.
8. Equivalent routes are merged. `service`, `booking` and `reservation` must not produce duplicate Book, Schedule and Appointments tabs when one canonical destination can host the flows.
9. Pricing, inventory, availability, capacity, deposits, bonds, cancellation, refunds and eligibility remain backend-authoritative.
10. Disabling a mode removes new entry points but does not erase historical orders, bookings, rentals, memberships, invoices, activity, conversations or creative records.
11. Mode changes are versioned, auditable and reflected in the signed application manifest.

---

## 1. Field and Home Services

**Stable slug:** `field-home-services`

### Covered businesses

- Residential and commercial cleaners
- Carpet, upholstery and window cleaners
- Plumbers
- Electricians
- Carpenters and joiners
- Painters and decorators
- Builders and renovation contractors
- Handymen and property maintenance providers
- Landscapers and gardeners
- Lawn mowing services
- Arborists and tree removal services
- Pest control businesses
- Locksmiths
- Roofing and guttering contractors
- HVAC, heating and cooling technicians
- Appliance repair technicians
- Solar installers and maintenance providers
- Security system installers
- Pool and spa maintenance businesses
- Waste removal and rubbish collection
- Pressure washing businesses
- Mobile technicians and inspection services
- NDIS home maintenance and support providers
- Facilities maintenance contractors

### Recommended App Modes

- **Primary:** `service`
- **Default supporting:** `booking`, `quote_first`
- **Optional:** `sales`, `subscription`, `marketplace`, `reservation`

### Common mode stacks

```text
Cleaner: service + booking + subscription
Plumber: service + booking + quote_first + sales
Builder: service + quote_first + booking + subscription
Facilities contractor: service + booking + quote_first + marketplace + subscription
Mobile inspector: service + booking + quote_first
```

### Vertical contributions

- service address, access instructions, site contact, service zone and travel zone;
- emergency and priority flags;
- property and asset details;
- before, during and after evidence;
- technician skills, materials, variations, completion approval and compliance records;
- NDIS participant, plan, support-category, consent and evidence fields where authorised.

## 2. BnB, Hotel and Rooming Services

**Stable slug:** `accommodation-rooming`

### Covered businesses

- Hotels
- Motels
- Resorts
- Bed and breakfasts
- Airbnb and short-stay operators
- Holiday rental managers
- Serviced apartments
- Hostels
- Guesthouses
- Boutique accommodation providers
- Rooming houses
- Boarding houses
- Student accommodation
- Worker accommodation
- Caravan parks
- Holiday parks
- Farm stays
- Retreat centres
- Co-living properties
- Property cleaning and turnover teams
- Linen and housekeeping services
- Accommodation maintenance providers

### Recommended App Modes

- **Primary:** `accommodation`
- **Default supporting:** `reservation`, `booking`, `service`
- **Optional:** `sales`, `order_ahead`, `hire`, `rental`, `subscription`, `event_ticketing`, `marketplace`, `application`, `capacity_booking`

### Common mode stacks

```text
Hotel: accommodation + reservation + booking + service + sales + order_ahead
Airbnb operator: accommodation + reservation + service
Rooming house: accommodation + rental + application + service
Caravan park: accommodation + reservation + capacity_booking + sales
Turnover team: service + booking + subscription
```

### Vertical contributions

- property, room, bed, site, unit, occupancy, guest and stay terminology;
- check-in, checkout, access, house rules, deposits, bonds, incidentals and extensions;
- housekeeping, linen, maintenance, amenities and guest requests;
- short-stay, rooming, student, worker, co-living and park-specific fields selected by subtype.

## 3. Real Estate

**Stable slug:** `real-estate`

### Covered businesses

- Residential real estate agencies
- Commercial real estate agencies
- Property management businesses
- Owners corporation and strata managers
- Buyers’ agents
- Sales agents
- Leasing agents
- Property developers
- Building and property inspectors
- Valuers
- Conveyancing businesses
- Mortgage and finance brokers
- Real estate photographers
- Property staging businesses
- Auctioneers
- Tenant placement services
- Short-term rental managers
- Facilities and asset managers
- Maintenance coordination businesses
- Landlord and investor portfolio managers

### Recommended App Modes

- **Primary:** `listing`
- **Default supporting:** `booking`, `application`
- **Optional:** `rental`, `service`, `quote_first`, `marketplace`, `sales`, `subscription`, `accommodation`, `reservation`

### Common mode stacks

```text
Sales agency: listing + booking + application
Property manager: listing + rental + application + service + marketplace
Buyers' agent: listing + service + booking + subscription
Property inspector: service + booking + quote_first
Short-stay manager: listing + accommodation + reservation + service
Facilities and asset manager: service + marketplace + subscription + quote_first
```

### Vertical contributions

- property, listing, owner, landlord, tenant, buyer, vendor, agent, portfolio and tenancy fields;
- inspection, enquiry, application, lease, offer, auction, maintenance and compliance workflows;
- customer-safe property documents and status;
- strict separation between operational property truth in WorkCore and financial obligations in Titan Pay.

## 4. Salons and Personal Care

**Stable slug:** `salons-personal-care`

### Covered businesses

- Hair salons
- Barbers
- Beauty salons
- Nail salons
- Day spas
- Massage therapists
- Skin and facial clinics
- Cosmetic clinics
- Tattoo studios
- Piercing studios
- Makeup artists
- Eyelash and eyebrow technicians
- Tanning studios
- Waxing and hair-removal businesses
- Mobile hairdressers and beauticians
- Bridal beauty providers
- Personal stylists
- Wellness practitioners
- Cosmetic injectors
- Grooming and personal-care studios

### Recommended App Modes

- **Primary:** `service`
- **Default supporting:** `booking`
- **Optional:** `sales`, `membership`, `subscription`, `class`, `quote_first`, `capacity_booking`

### Common mode stacks

```text
Hair salon: service + booking + sales + membership
Day spa: service + booking + membership + sales
Tattoo studio: service + booking + quote_first
Bridal provider: service + quote_first + booking
Training studio: service + booking + class + sales
```

### Vertical contributions

- practitioner, chair, room, treatment, duration, consultation, patch-test and consent fields;
- contraindications, aftercare, treatment history, packages, memberships and rebooking;
- product recommendations and retail add-ons;
- mobile-service location and travel configuration.

## 5. Fitness and Membership Businesses

**Stable slug:** `fitness-membership`

### Covered businesses

- Gyms
- Fitness centres
- Personal trainers
- Group fitness studios
- Yoga studios
- Pilates studios
- CrossFit and functional fitness gyms
- Martial arts schools
- Boxing gyms
- Dance schools
- Swimming schools
- Sports clubs
- Recreation centres
- Wellness clubs
- Health coaching businesses
- Physiotherapy-led exercise programs
- Outdoor boot camps
- Online fitness membership businesses
- Community and social clubs
- Membership associations
- Subscription-based training programs
- Children’s activity and sports programs

### Recommended App Modes

- **Primary:** `membership`
- **Default supporting:** `class`, `booking`
- **Optional:** `subscription`, `sales`, `service`, `event_ticketing`, `capacity_booking`, `reservation`

### Common mode stacks

```text
Gym: membership + class + booking + subscription + sales
Personal trainer: service + booking + subscription
Martial arts school: membership + class + subscription + event_ticketing
Swimming school: class + booking + capacity_booking + membership
Online fitness business: membership + subscription + class + sales
```

### Vertical contributions

- member, coach, instructor, class, program, pass, attendance, venue and entitlement fields;
- recurring timetable, term enrolment, dependent or guardian support, prerequisites and capacity;
- membership access, pauses, usage, renewals, health declarations and consent;
- program, challenge, event and merchandise support.

## 6. Automotive Services

**Stable slug:** `automotive-services`

### Covered businesses

- Mechanical workshops
- Mobile mechanics
- Auto electricians
- Tyre and wheel businesses
- Car detailing businesses
- Mobile car wash providers
- Panel beaters
- Smash repair businesses
- Windscreen repair and replacement
- Vehicle inspection services
- Roadworthy certificate providers
- Towing businesses
- Roadside assistance providers
- Car dealerships
- Used vehicle dealerships
- Motorcycle repair shops
- Truck and fleet maintenance providers
- Heavy machinery repair businesses
- Auto parts retailers
- Car audio and accessory installers
- Paint protection and vehicle wrapping
- Fleet management businesses
- Vehicle air-conditioning specialists

### Recommended App Modes

- **Primary:** `service`
- **Default supporting:** `booking`, `quote_first`
- **Optional:** `sales`, `hire`, `rental`, `subscription`, `listing`, `application`, `marketplace`, `transport_booking`

### Common mode stacks

```text
Mechanical workshop: service + booking + quote_first + sales
Mobile mechanic: service + booking + quote_first
Dealership: listing + sales + application + booking
Fleet maintenance: service + booking + subscription + quote_first
Courtesy vehicle program: service + booking + hire
```

### Vertical contributions

- vehicle, registration, VIN, odometer, make, model, engine, fleet and owner fields;
- workshop bay, technician, inspection, diagnosis, labour, parts, estimate, approval and service history;
- towing, roadside, mobile-location, courtesy vehicle and fleet scheduling;
- vehicle listing, finance enquiry and trade-in application fields where enabled.

## 7. E-commerce and Retail

**Stable slug:** `ecommerce-retail`

### Covered businesses

- Online stores
- Physical retail stores
- Omnichannel retailers
- Clothing and fashion stores
- Homeware and furniture retailers
- Electronics retailers
- Beauty and cosmetics stores
- Health and wellness retailers
- Pet supply stores
- Food and specialty grocery retailers
- Florists and gift shops
- Hardware and trade supply stores
- Sporting goods retailers
- Jewellery and accessory stores
- Subscription-box businesses
- Wholesale distributors
- Product manufacturers
- Marketplace sellers
- Dropshipping businesses
- Print-on-demand stores
- Social commerce businesses
- Click-and-collect retailers
- Multi-location retail chains
- Pop-up shops and market vendors

### Recommended App Modes

- **Primary:** `sales`
- **Default supporting:** none required
- **Optional:** `subscription`, `membership`, `order_ahead`, `marketplace`, `booking`, `service`, `hire`, `rental`, `quote_first`

### Common mode stacks

```text
Online store: sales
Omnichannel retailer: sales + order_ahead
Subscription box: sales + subscription
Wholesale distributor: sales + quote_first + subscription
Marketplace seller network: sales + marketplace
Furniture retailer: sales + booking + service + hire
```

### Vertical contributions

- product, SKU, variant, inventory, warehouse, store, supplier, fulfilment, shipment, pickup and return fields;
- omnichannel stock and location presentation;
- wholesale, manufacturing, dropshipping, print-on-demand, subscription-box and marketplace configuration;
- customer-safe order, delivery, return, exchange and reorder flows.

## 8. Hire and Rental

**Stable slug:** `hire-rental`

### Covered businesses

- Equipment hire businesses
- Tool hire businesses
- Vehicle rental companies
- Car and van hire
- Truck and trailer hire
- Machinery and plant hire
- Party and event equipment hire
- Furniture hire
- Marquee and staging hire
- Audio-visual equipment hire
- Costume and formalwear hire
- Bicycle and scooter rental
- Boat and watercraft hire
- Caravan and campervan hire
- Storage rental businesses
- Portable building and container hire
- Cleaning equipment rental
- Medical and mobility equipment hire
- Baby equipment hire
- Photography and camera equipment hire
- Sports equipment rental
- Short-term workspace and room hire

### Recommended App Modes

- **Primary:** `hire` or `rental`, selected by business model
- **Default supporting:** `reservation`
- **Optional:** `booking`, `sales`, `service`, `quote_first`, `subscription`, `marketplace`, `application`, `capacity_booking`

### Common mode stacks

```text
Tool hire: hire + reservation + sales
Vehicle rental: rental + reservation + application
Plant hire: hire + quote_first + reservation + service
Party equipment: hire + reservation + quote_first + service
Storage rental: rental + subscription + application
Workspace hire: hire + booking + capacity_booking + membership
```

### Vertical contributions

- asset, serial number, fleet, quantity, availability, depot, location, delivery, collection and return fields;
- bond, deposit, condition, inspection, damage, cleaning, extension, late return and replacement workflows;
- operator, installation, setup, transport and service add-ons;
- recurring rental, notice, renewal and access terms where configured.

## 9. Booking, Reservation and Capacity-Based Businesses

**Stable slug:** `booking-capacity`

### Covered businesses

- Restaurants and cafés
- Function venues
- Wedding venues
- Conference and meeting spaces
- Coworking spaces
- Photography studios
- Training rooms
- Escape rooms
- Entertainment venues
- Tours and activity operators
- Travel and excursion businesses
- Boat charters
- Bus and transport bookings
- Appointment-based professional services
- Medical and allied health clinics
- Dental practices
- Veterinary clinics
- Counselling and therapy practices
- Tutors and education providers
- Childcare and activity centres
- Classes and workshops
- Event organisers
- Ticketed attractions
- Campsites and caravan sites
- Parking space operators
- Sports court and facility bookings
- Shared equipment and resource bookings
- Any business managing appointments, seats, rooms, staff, assets or limited capacity

### Recommended App Modes

- **Primary:** one of `booking`, `reservation` or `capacity_booking`, selected by resource model
- **Default supporting:** none required
- **Optional:** `service`, `class`, `event_ticketing`, `transport_booking`, `accommodation`, `sales`, `order_ahead`, `membership`, `subscription`, `hire`, `rental`, `quote_first`, `application`

### Common mode stacks

```text
Restaurant: reservation + order_ahead + sales
Medical clinic: service + booking
Wedding venue: reservation + booking + quote_first + service
Tour operator: capacity_booking + transport_booking + event_ticketing
Coworking space: booking + reservation + membership + subscription
Tutor: service + booking + class + subscription
Campsite: accommodation + reservation + capacity_booking
Sports facility: booking + capacity_booking + membership
```

### Vertical contributions

- resource, staff, room, seat, table, court, vehicle, equipment, participant and capacity fields;
- availability rules, buffers, lead times, waitlists, group size, admissions and attendance;
- clinical, educational, childcare, venue, transport, event and facility subtype fields;
- strong privacy, consent, guardian, practitioner and clinical-record boundaries where applicable.

---

## Vertical-to-mode matrix

Legend:

- **P** — recommended primary mode
- **D** — recommended default supporting mode
- **O** — optional mode
- blank — not normally proposed, but may be enabled through an approved custom configuration

| Mode | Field & Home | Accommodation & Rooming | Real Estate | Salons & Care | Fitness & Membership | Automotive | E-commerce & Retail | Hire & Rental | Booking & Capacity |
|---|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| `service` | P | D | O | P | O | P | O | O | O |
| `quote_first` | D | O | O | O |  | D | O | O | O |
| `booking` | D | D | D | D | D | D | O | O | P |
| `reservation` | O | D | O |  | O | O |  | D | P |
| `capacity_booking` |  | O |  | O | O |  |  | O | P |
| `class` |  | O |  | O | D |  |  |  | O |
| `event_ticketing` |  | O | O |  | O |  |  |  | O |
| `transport_booking` |  | O |  |  |  | O |  | O | O |
| `accommodation` |  | P | O |  |  |  |  | O | O |
| `sales` | O | O | O | O | O | O | P | O | O |
| `order_ahead` |  | O |  |  |  |  | O |  | O |
| `hire` |  | O |  |  |  | O | O | P | O |
| `rental` |  | O | O |  |  | O | O | P | O |
| `membership` |  | O |  | O | P |  | O | O | O |
| `subscription` | O | O | O | O | O | O | O | O | O |
| `listing` |  | O | P |  |  | O | O | O |  |
| `marketplace` | O | O | O |  |  | O | O | O | O |
| `application` |  | O | D |  | O | O |  | O | O |

This matrix proposes onboarding defaults only. The signed backend manifest remains the source of truth.

## Business subtype rules

A subtype is configuration beneath a vertical, not another top-level vertical and not another app fork.

Example:

```json
{
  "vertical_profile": "field-home-services",
  "business_subtypes": ["plumber", "emergency-trade"],
  "primary_mode": "service",
  "supporting_modes": ["booking", "quote_first", "sales"]
}
```

A business may select multiple closely related subtypes inside one vertical. Subtypes contribute terminology, fields, templates, compliance, onboarding suggestions and domain tools. They do not create separate customer, order, booking, invoice, payment or ledger authorities.

A tenant operating genuinely separate business models should create separate business profiles with their own primary vertical and manifest. Secondary vertical capabilities may be exposed through explicit configuration, but they must not create an unpredictable blended authority model.

## Resolved application manifest

Laravel must publish the resolved vertical and App Modes explicitly.

```json
{
  "data": {
    "schema_version": 2,
    "business": {
      "id": "business_42",
      "name": "Titan Plumbing"
    },
    "vertical_profile": "field-home-services",
    "secondary_vertical_capabilities": [],
    "business_subtypes": ["plumber"],
    "primary_mode": "service",
    "supporting_modes": ["booking", "quote_first", "sales"],
    "enabled_modes": ["service", "booking", "quote_first", "sales"],
    "mode_priorities": {
      "service": 100,
      "booking": 80,
      "quote_first": 70,
      "sales": 40
    },
    "navigation": [],
    "routes": [],
    "home_widgets": [],
    "catalogue_types": [],
    "activity_types": [],
    "chatbot_tools": [],
    "permissions": {},
    "terminology": {},
    "branding": {}
  }
}
```

Requirements:

- `vertical_profile` uses one stable hyphenated canonical slug;
- legacy values are normalised before the manifest is signed;
- `business_subtypes` uses registered subtype slugs;
- `primary_mode` must appear in `enabled_modes`;
- `supporting_modes` are unique and do not repeat `primary_mode`;
- each mode passes entitlement, permission, backend capability and provider-configuration validation;
- unknown modes, unresolved aliases, circular dependencies, route-owner conflicts and duplicate write authorities are rejected;
- the manifest exposes an ETag or version and controlled cache expiry.

## Titan Hub and Flutter requirements

- Flutter renders the backend-resolved vertical and mode graph.
- Do not create separate Flutter applications for each vertical or mode combination.
- Do not infer App Modes from a vertical name.
- Register only routes and actions present in the manifest.
- Merge equivalent destinations contributed by several modes.
- Let `primary_mode` order the home experience and initial navigation.
- Preserve shared Activity, account, payments, messages, support and search surfaces.
- Reuse the closest existing QRPay or Titan Hub screen, controller, state pattern, component, theme and validation flow before introducing a new structure.
- When a new screen is genuinely required, duplicate the closest existing implementation and adapt it.

## Chatbot and agent requirements

Titan Echo receives the same resolved context as Flutter:

```text
Core conversation tools
+ primary Vertical Profile tools
+ approved secondary vertical capabilities
+ primary App Mode tools
+ supporting App Mode tools
+ tenant permissions and approval policy
= authorised tool registry
```

Chatbot tools invoke the same application services as Flutter screens. The model does not invent pricing, availability, capacity, eligibility, deposits, bonds, cancellation, refunds or write authority.

## Backend authority requirements

- MagicAI remains identity, tenancy, entitlement and permission authority.
- The e-commerce extension remains a Laravel backend extension and supplies configured catalogue, product, service, cart, order and fulfilment capabilities.
- Booking, reservation, capacity, accommodation, hire and rental authorities are selected explicitly through adapters.
- WorkCore remains operational authority for customers, contacts, quotes, jobs, tasks, bookings where configured, inventory where configured and operational records.
- Titan Pay remains financial authority for invoices, obligations, payments, wallets, refunds, deposits, bonds, milestones, plans, reconciliation and ledger events.
- The Chatbot extension remains conversation, tool-routing, handoff and agent-orchestration authority.
- No vertical or mode may create a second customer, booking, inventory, invoice, payment or ledger write authority.

## Onboarding requirements

Vertical selection and App Mode selection are separate steps.

1. Select the closest of the nine verticals.
2. Select one or more business subtypes.
3. Review the recommended primary App Mode.
4. Add or remove supporting App Modes.
5. Connect authoritative extensions and providers required by each mode.
6. Resolve permissions, terminology, navigation, payment eligibility and chatbot tools.
7. Preview the final customer experience.
8. Publish a versioned manifest.

Onboarding must explain that a business can add another App Mode later without changing vertical.

## Titan Create implications

Every Titan Create recipe, template or campaign must declare:

- one canonical vertical slug;
- an optional business subtype key;
- optional secondary vertical capabilities;
- required and supported App Modes;
- required source entities and fields;
- allowed outputs;
- brand, compliance and approval requirements;
- data freshness and campaign stop conditions where capacity, stock or availability is involved.

Initial vertical-pack implementation order remains:

1. Fitness and Membership Businesses
2. Real Estate
3. Field and Home Services
4. BnB, Hotel and Rooming Services
5. Salons and Personal Care
6. Automotive Services
7. E-commerce and Retail
8. Hire and Rental
9. Booking, Reservation and Capacity-Based Businesses

This ordering affects delivery sequence only. It does not change canonical coverage, App Mode availability or plan entitlement rules.

## Verification requirements

Tests must verify:

- exactly nine canonical verticals;
- every listed business subtype belongs to an approved vertical;
- legacy aliases normalise idempotently;
- one business profile can enable several App Modes simultaneously;
- `primary_mode` is unique and enabled;
- route collisions resolve deterministically;
- disabled modes do not expose entry points or tools;
- historical activity remains readable after a mode is disabled;
- Flutter, Titan Create and Chatbot receive the same resolved mode set where applicable;
- tenant isolation, permissions, backend capability and entitlement checks remove unauthorised modes;
- no vertical or mode creates duplicate operational or financial authority.
