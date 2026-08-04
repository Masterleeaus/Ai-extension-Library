# Example Provider Adapter

This minimal example demonstrates the boundary. It is not a real provider implementation.

```php
final class ExamplePlacesProvider implements PlacesProvider
{
    public function __construct(
        private ProviderHttpTransport $http,
        private SecretResolver $vault,
        private string $credentialReference,
    ) {}

    public function id(): string
    {
        return 'example-places';
    }

    public function capabilities(): ProviderCapabilities
    {
        return new ProviderCapabilities(
            textSearch: true,
            nearbySearch: false,
            placeDetails: true,
            pagination: true,
            supportedFilters: ['language', 'region'],
            storageRestrictions: ['Review provider terms before retention.'],
        );
    }

    public function attribution(): string
    {
        return 'Example Places';
    }

    public function search(PlaceSearchRequest $request): PlaceSearchPage
    {
        $secret = $this->vault->resolve($this->credentialReference);
        $response = $this->http->request(
            'POST',
            'https://allowlisted.example/v1/search',
            ['Authorization' => 'Bearer '.$secret],
            ['query' => $request->query],
        );

        return new PlaceSearchPage(
            places: array_map([$this, 'normalise'], $response['results'] ?? []),
            nextPageToken: $response['next_cursor'] ?? null,
        );
    }

    // nearby() and details() either implement declared support or throw
    // a stable MAPS_UNSUPPORTED_OPERATION error.
}
```

Never register a shared global API key for multiple companies unless the platform owner explicitly operates and governs that managed service.
