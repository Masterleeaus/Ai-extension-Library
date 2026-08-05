<?php

declare(strict_types=1);

use App\Services\Security\RemoteImageFetcher;
use RuntimeException;

function remoteImageFetcher(): RemoteImageFetcher
{
    return new RemoteImageFetcher;
}

test('remote image downloads require HTTPS', function (): void {
    expect(fn () => remoteImageFetcher()->resolveTarget('http://example.com/image.png'))
        ->toThrow(RuntimeException::class);
});

test('remote image downloads reject URL credentials', function (): void {
    expect(fn () => remoteImageFetcher()->resolveTarget('https://user:secret@example.com/image.png'))
        ->toThrow(RuntimeException::class);
});

test('remote image downloads reject non-standard ports', function (): void {
    expect(fn () => remoteImageFetcher()->resolveTarget('https://example.com:8443/image.png'))
        ->toThrow(RuntimeException::class);
});

test('remote image downloads reject private and reserved IPv4 targets', function (string $url): void {
    expect(fn () => remoteImageFetcher()->resolveTarget($url))
        ->toThrow(RuntimeException::class);
})->with([
    'loopback' => 'https://127.0.0.1/image.png',
    'private class A' => 'https://10.0.0.1/image.png',
    'private class B' => 'https://172.16.0.1/image.png',
    'private class C' => 'https://192.168.1.1/image.png',
    'carrier-grade NAT' => 'https://100.64.0.1/image.png',
    'link local metadata' => 'https://169.254.169.254/latest/meta-data',
    'documentation range' => 'https://192.0.2.10/image.png',
]);

test('remote image downloads reject local and reserved IPv6 targets', function (string $url): void {
    expect(fn () => remoteImageFetcher()->resolveTarget($url))
        ->toThrow(RuntimeException::class);
})->with([
    'loopback' => 'https://[::1]/image.png',
    'unique local' => 'https://[fc00::1]/image.png',
    'link local' => 'https://[fe80::1]/image.png',
    'documentation range' => 'https://[2001:db8::1]/image.png',
]);

test('remote image downloads reject local hostnames', function (): void {
    expect(fn () => remoteImageFetcher()->resolveTarget('https://localhost/image.png'))
        ->toThrow(RuntimeException::class);
});

test('a public HTTPS IP can pass URL policy without making a request', function (): void {
    $target = remoteImageFetcher()->resolveTarget('https://1.1.1.1/image.png');

    expect($target['host'])->toBe('1.1.1.1')
        ->and($target['ip'])->toBe('1.1.1.1');
});
