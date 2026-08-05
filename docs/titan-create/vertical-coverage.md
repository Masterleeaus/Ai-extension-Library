# Titan Zero Vertical Coverage

This document is the canonical vertical taxonomy for Titan Zero and Titan Create.

## Architecture rules

- Titan Zero has **nine canonical verticals**.
- Each tenant may enable one or more verticals.
- A business may have a primary vertical and secondary vertical capabilities.
- Shared capabilities such as bookings, capacity, memberships, facilities, commerce, field work, inventory and rentals must be composed rather than duplicated.
- `Facilities` is a cross-vertical capability primarily used by Field and Home Services, Real Estate, accommodation and booking businesses; it is not a separate tenth vertical.
- General commerce capabilities are owned by E-commerce and Retail and may be reused by other verticals.
- Booking, Reservation and Capacity-Based Businesses is both a complete vertical and a reusable capacity overlay for businesses in other verticals.
- Hire and Rental owns rentable-asset lifecycle capabilities; Booking and Capacity owns time-slot, seat, room, staff and constrained-resource reservation capabilities.
- User-facing vertical packs, recipes, templates, prompts, data bindings and automation rules must use these canonical identifiers.
- Do not create a new UI system for vertical packs. Modify or duplicate the closest existing CreativeSuite, Canvas, FashionStudio or related donor surface and retain its established style.

## Canonical identifiers

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

---

## 1. Field and Home Services

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

## 2. BnB, Hotel and Rooming Services

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

## 3. Real Estate

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

## 4. Salons and Personal Care

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

## 5. Fitness and Membership Businesses

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

## 6. Automotive Services

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

## 7. E-commerce and Retail

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

## 8. Hire and Rental

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

## 9. Booking, Reservation and Capacity-Based Businesses

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

---

## Titan Create implications

Every Titan Create recipe, template or campaign must declare:

- one canonical vertical slug;
- an optional business-type key;
- optional shared capability overlays;
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

This ordering affects delivery sequence only. It does not change canonical coverage or plan entitlement rules.
