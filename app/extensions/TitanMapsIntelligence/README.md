# Titan Maps Intelligence

Advanced mapping and geospatial intelligence extension with location services, route optimization, and geofencing capabilities.

## Features

- **Location Services**: Real-time location tracking and geocoding
- **Route Optimization**: Calculate optimal routes with traffic analysis
- **Geofencing**: Create and monitor geofences with automated triggers
- **Map Visualization**: Interactive maps with custom layers and markers
- **Analytics**: Geospatial data analysis and reporting
- **Integration**: WorkCore integration for business operations

## Installation

```bash
composer install
```

## Configuration

Configure map providers, geofencing rules, and location services in the extension configuration.

## Usage

```php
$mapsService = app(\App\Extensions\TitanMapsIntelligence\Services\MapsService::class);

// Get location details
$location = $mapsService->getLocationDetails($latitude, $longitude);

// Calculate route
$route = $mapsService->calculateRoute($start, $end);

// Set geofence
$geofence = $mapsService->createGeofence($coordinates, $radius);
```

## Testing

```bash
php artisan test
```
