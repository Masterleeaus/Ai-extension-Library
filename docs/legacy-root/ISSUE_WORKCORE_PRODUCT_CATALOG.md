# Issue: Implement WorkCore Product Catalog System

**Status:** BACKLOG | Phase 2 | Feature  
**Priority:** High  
**Effort:** 2 weeks  
**Depends on:** TenantContext, Supply domain  
**Blocks:** E-Commerce, Retail, Restaurant verticals  

## Problem Statement

WorkCore lacks a unified product catalog system needed for:
- Retail stores (physical and online)
- Restaurants/cafés (menu management)
- Service businesses (service packages)
- Salons (service offerings)
- Equipment rental (equipment listings)

## Solution Requirements

Implement comprehensive product/service catalog with categories, variants, pricing, and inventory management.

## Deliverables

### 1. Product Management
- Product creation with metadata (name, description, images)
- Product categories and hierarchies
- Product tagging and filtering
- Bulk operations (import, update)

### 2. Variants & SKUs
- Size, color, style variants
- SKU generation and management
- Variant-specific pricing and inventory

### 3. Catalog Publishing
- Publish to different channels (web, app, POS)
- Channel-specific pricing and visibility
- Scheduled availability

### 4. Service Packages
- Package bundling (services + products)
- Add-on services
- Package pricing and discounts

### 5. Media Management
- Product images and galleries
- Video support
- Document attachments (specs, manuals)

## Files to Create

```
app/Domains/WorkCore/Catalog/
├── Models/
│   ├── Product.php
│   ├── ProductVariant.php
│   ├── Category.php
│   ├── ServicePackage.php
│   └── Media.php
├── Services/
│   ├── CatalogService.php
│   ├── ProductService.php
│   └── PublishingService.php
└── CatalogServiceProvider.php
```

## Acceptance Criteria

- [ ] Product creation and management working
- [ ] Categories and hierarchies functional
- [ ] Variants and SKUs operational
- [ ] Multi-channel publishing working
- [ ] Media management complete
- [ ] Service packages functional
