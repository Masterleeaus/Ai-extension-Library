# Vertical Extensions API Endpoints

This document outlines the RESTful API endpoints for all 9 vertical industry extensions.

## Base URL
```
/api/v1/workcore/verticals/{vertical-slug}
```

## Field Services API

### Job Sites
```
GET    /job-sites                    # List all job sites
POST   /job-sites                    # Create new job site
GET    /job-sites/{id}               # Get job site details
PUT    /job-sites/{id}               # Update job site
DELETE /job-sites/{id}               # Delete job site

GET    /job-sites/{id}/service-visits   # List visits for job site
```

### Service Visits
```
GET    /service-visits               # List all service visits
POST   /service-visits               # Create service visit
GET    /service-visits/{id}          # Get visit details
PUT    /service-visits/{id}          # Update visit status
POST   /service-visits/{id}/photos   # Upload documentation photos
GET    /service-visits/{id}/photos   # Get all photos
POST   /service-visits/{id}/signature # Capture customer signature
```

### Worker Skills
```
GET    /worker-skills                # List technician certifications
POST   /worker-skills                # Add skill certification
GET    /worker-skills/{id}           # Get skill details
PUT    /worker-skills/{id}           # Update/verify certification
DELETE /worker-skills/{id}           # Remove skill
```

### Route Optimization
```
POST   /routes/optimize              # Calculate optimal route
GET    /routes/{id}                  # Get route details
POST   /routes/{id}/execute          # Begin route execution
```

---

## Hospitality API

### Room Inventory
```
GET    /rooms                        # List all rooms
POST   /rooms                        # Add new room
GET    /rooms/{id}                   # Get room details
PUT    /rooms/{id}                   # Update room info
DELETE /rooms/{id}                   # Remove room
GET    /rooms/{id}/availability      # Check availability
```

### Accommodation Stays
```
GET    /stays                        # List all reservations
POST   /stays                        # Create new stay
GET    /stays/{id}                   # Get stay details
PUT    /stays/{id}                   # Modify reservation
DELETE /stays/{id}                   # Cancel reservation
POST   /stays/{id}/check-in          # Check in guest
POST   /stays/{id}/check-out         # Check out guest
```

### Channel Management
```
GET    /channels                     # List channel connections
POST   /channels                     # Add OTA integration
GET    /channels/{id}                # Get channel details
POST   /channels/{id}/sync           # Sync availability/pricing
GET    /channels/{id}/sync-status    # Check sync status
```

### Cleaning Schedule
```
GET    /cleaning-schedule            # List housekeeping tasks
POST   /cleaning-schedule            # Create cleaning task
GET    /cleaning-schedule/{id}       # Get task details
PUT    /cleaning-schedule/{id}       # Update task
POST   /cleaning-schedule/{id}/complete # Mark complete
```

### Dynamic Pricing
```
POST   /pricing/calculate            # Calculate dynamic price
GET    /pricing/analysis             # Get occupancy/demand analysis
PUT    /rooms/{id}/base-price        # Update base price
```

---

## Real Estate API

### Property Listings
```
GET    /properties                   # List properties
POST   /properties                   # Create listing
GET    /properties/{id}              # Get property details
PUT    /properties/{id}              # Update listing
DELETE /properties/{id}              # Delist property
GET    /properties/{id}/images       # Get property images
POST   /properties/{id}/images       # Upload images
```

### Tenant Screening
```
POST   /screening                    # Create screening application
GET    /screening/{id}               # Get screening results
PUT    /screening/{id}/status        # Update screening status
POST   /screening/{id}/approve       # Approve applicant
POST   /screening/{id}/reject        # Reject applicant
```

### Lease Agreements
```
POST   /leases                       # Generate lease agreement
GET    /leases/{id}                  # Get lease details
PUT    /leases/{id}                  # Modify lease
POST   /leases/{id}/sign             # Obtain signatures
GET    /leases/{id}/document         # Download lease PDF
```

### Maintenance Requests
```
GET    /maintenance                  # List all requests
POST   /maintenance                  # Submit maintenance request
GET    /maintenance/{id}             # Get request details
PUT    /maintenance/{id}             # Update request status
POST   /maintenance/{id}/complete    # Mark complete
```

---

## Salons API

### Stylist Specialties
```
GET    /stylists                     # List stylists
GET    /stylists/{id}                # Get stylist profile
GET    /stylists/{id}/specialties    # List specialties
POST   /stylists/{id}/specialties    # Add specialty
PUT    /specialties/{id}             # Update specialty
```

### Service Add-Ons
```
GET    /add-ons                      # List available add-ons
POST   /add-ons                      # Create new add-on
GET    /add-ons/{id}                 # Get add-on details
PUT    /add-ons/{id}                 # Update add-on
```

### Client Hair Profiles
```
GET    /clients/{id}/profile         # Get client profile
PUT    /clients/{id}/profile         # Update profile
POST   /clients/{id}/service-history # View service history
```

### Loyalty Programs
```
GET    /loyalty/members/{id}         # Get loyalty balance
POST   /loyalty/members/{id}/redeem  # Redeem points
GET    /loyalty/tier-benefits        # View tier benefits
```

---

## Fitness API

### Memberships
```
GET    /memberships                  # List all members
POST   /memberships                  # Enroll new member
GET    /memberships/{id}             # Get member details
PUT    /memberships/{id}             # Modify membership
POST   /memberships/{id}/upgrade     # Upgrade tier
```

### Class Schedules
```
GET    /classes                      # List all classes
GET    /classes/{id}                 # Get class details
POST   /classes/{id}/enroll          # Enroll member
POST   /classes/{id}/cancel          # Cancel enrollment
GET    /classes/{id}/waitlist        # View waitlist
```

### Attendance
```
POST   /attendance/check-in          # Check in member
POST   /attendance/check-out         # Check out member
GET    /attendance/member/{id}       # Get attendance history
```

### Workout Programs
```
GET    /programs                     # List programs
POST   /programs                     # Create new program
GET    /programs/{id}                # Get program details
PUT    /programs/{id}                # Update program
POST   /programs/{id}/progress       # Log progress
```

---

## Automotive API

### Vehicle Service History
```
GET    /vehicles/{vin}               # Get vehicle history
POST   /vehicles/{vin}/service       # Log service
GET    /vehicles/{vin}/services      # List all services
GET    /vehicles/{vin}/maintenance-schedule # Get schedule
```

### Service Packages
```
GET    /packages                     # List service packages
POST   /packages                     # Create package
GET    /packages/{id}                # Get package details
PUT    /packages/{id}                # Update package
```

### Parts Inventory
```
GET    /parts                        # List all parts
POST   /parts                        # Add new part
GET    /parts/{id}                   # Get part details
PUT    /parts/{id}                   # Update inventory
POST   /parts/{id}/order             # Order parts
```

### Warranty Management
```
GET    /warranties                   # List warranties
POST   /warranties                   # Create warranty
GET    /warranties/{id}              # Get warranty details
POST   /warranties/{id}/claim        # File warranty claim
```

---

## E-Commerce API

### Product Reviews
```
GET    /products/{id}/reviews        # List reviews
POST   /products/{id}/reviews        # Submit review
GET    /reviews/{id}                 # Get review details
PUT    /reviews/{id}                 # Edit review
POST   /reviews/{id}/moderate        # Moderate review
```

### Returns & Refunds
```
POST   /returns                      # Submit return request
GET    /returns/{id}                 # Get return status
PUT    /returns/{id}                 # Update return
POST   /returns/{id}/approve         # Approve return
POST   /returns/{id}/refund          # Process refund
```

### Recommendations
```
GET    /recommendations              # Get personalized recommendations
GET    /recommendations/trending     # Get trending products
POST   /recommendations/learn        # Log user interaction
```

### Wish Lists
```
GET    /wishlist                     # Get user's wishlist
POST   /wishlist                     # Add to wishlist
DELETE /wishlist/{id}                # Remove from wishlist
GET    /wishlist/share               # Share public wishlist
```

---

## Hire & Rental API

### Rental Agreements
```
POST   /agreements                   # Create rental agreement
GET    /agreements/{id}              # Get agreement details
PUT    /agreements/{id}              # Modify agreement
POST   /agreements/{id}/sign         # Obtain signatures
GET    /agreements/{id}/document     # Download agreement
```

### Damage Assessment
```
POST   /damage-assessment            # Create assessment
GET    /damage-assessment/{id}       # Get assessment results
POST   /damage-assessment/{id}/photos # Upload damage photos
PUT    /damage-assessment/{id}       # Update assessment
```

### Late Fees
```
POST   /late-fees/calculate          # Calculate late fees
GET    /late-fees/{id}               # Get fee details
POST   /late-fees/{id}/apply         # Apply fees to invoice
```

### Insurance
```
GET    /insurance-options            # List insurance plans
POST   /agreements/{id}/insurance    # Add insurance to rental
GET    /insurance/{id}               # Get insurance details
```

---

## Booking API

### Availability Rules
```
GET    /availability-rules           # List rules
POST   /availability-rules           # Create rule
PUT    /availability-rules/{id}      # Update rule
DELETE /availability-rules/{id}      # Delete rule
GET    /availability                 # Check availability
```

### Reservations
```
POST   /reservations                 # Create reservation
GET    /reservations/{id}            # Get reservation details
PUT    /reservations/{id}            # Modify reservation
DELETE /reservations/{id}            # Cancel reservation
POST   /reservations/{id}/confirm    # Send confirmation
```

### Waitlist
```
GET    /waitlist                     # View waitlist
POST   /waitlist                     # Add to waitlist
DELETE /waitlist/{id}                # Remove from waitlist
POST   /waitlist/{id}/notify         # Manual notification
```

### Reminders
```
GET    /reminders                    # List scheduled reminders
POST   /reminders                    # Create reminder
PUT    /reminders/{id}               # Update reminder
POST   /reminders/{id}/send          # Send immediately
```

### No-Show Tracking
```
GET    /no-shows                     # List no-shows
POST   /no-shows                     # Mark as no-show
GET    /customers/{id}/no-show-count # Get customer no-show history
```

---

## Common Query Parameters

All endpoints support:

```
?page=1                  # Pagination (default: 1)
?per_page=20            # Items per page (default: 20)
?sort=-created_at       # Sort by field (- for desc)
?filter[status]=active  # Filter by field
?search=keyword         # Full-text search
?include=relations      # Include relationships
```

## Response Format

All endpoints return JSON:

```json
{
  "success": true,
  "data": { /* resource data */ },
  "meta": {
    "pagination": {
      "total": 100,
      "per_page": 20,
      "current_page": 1,
      "last_page": 5
    }
  }
}
```

## Error Responses

```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field": ["Validation error message"]
  }
}
```

## Authentication

All endpoints require Bearer token authentication:

```
Authorization: Bearer {access_token}
```

## Rate Limiting

- 1000 requests per hour per API key
- Rate limit headers included in responses

## Documentation

API documentation available at `/api/v1/docs` (Swagger/OpenAPI)
