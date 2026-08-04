# Titan Vault Integration

## Secret storage model

`maps_provider_connections.credential_reference` stores only an opaque Vault reference, for example:

```text
vault://companies/{company-id}/providers/google-places
```

The API key itself must never appear in:

- extension settings
- database configuration JSON
- queue payloads
- audit records
- logs
- exceptions
- exports
- capability responses

## Resolver contract

```php
interface SecretResolver
{
    public function resolve(string $credentialReference): string;
}
```

The host resolver must authenticate the caller context, validate company ownership of the reference, decrypt the secret in memory, and return it only for the duration of the provider request.

## Rotation

Rotate the underlying secret while retaining the reference where possible. After rotation, validate the provider connection and update `last_validated_at`. Failed validation must not reveal the provider response body.
