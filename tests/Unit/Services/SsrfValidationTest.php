<?php

use Codenzia\FilamentMedia\Services\UploadService;

beforeEach(function () {
    $this->service = app(UploadService::class);
});

describe('UploadService - SSRF Validation', function () {
    it('blocks localhost', function () {
        $result = $this->service->validateUrlForSsrf('http://localhost/secret');

        expect($result)->not->toBeNull();
    });

    it('blocks 127.0.0.1', function () {
        $result = $this->service->validateUrlForSsrf('http://127.0.0.1/secret');

        expect($result)->not->toBeNull();
    });

    it('blocks IPv6 loopback ::1', function () {
        $result = $this->service->validateUrlForSsrf('http://[::1]/secret');

        expect($result)->not->toBeNull();
    });

    it('blocks 0.0.0.0', function () {
        $result = $this->service->validateUrlForSsrf('http://0.0.0.0/secret');

        expect($result)->not->toBeNull();
    });

    it('blocks ftp scheme', function () {
        $result = $this->service->validateUrlForSsrf('ftp://example.com/file.txt');

        expect($result)->not->toBeNull();
    });

    it('blocks file scheme', function () {
        $result = $this->service->validateUrlForSsrf('file:///etc/passwd');

        expect($result)->not->toBeNull();
    });

    it('blocks AWS metadata IP 169.254.169.254', function () {
        $result = $this->service->validateUrlForSsrf('http://169.254.169.254/latest/meta-data/');

        expect($result)->not->toBeNull();
    });

    it('blocks Azure metadata IP 169.254.170.2', function () {
        $result = $this->service->validateUrlForSsrf('http://169.254.170.2/metadata/');

        expect($result)->not->toBeNull();
    });

    it('blocks Alibaba metadata IP 100.100.100.200', function () {
        $result = $this->service->validateUrlForSsrf('http://100.100.100.200/latest/');

        expect($result)->not->toBeNull();
    });

    it('blocks private IP range 10.x', function () {
        $result = $this->service->validateUrlForSsrf('http://10.0.0.1/internal');

        expect($result)->not->toBeNull();
    });

    it('blocks private IP range 192.168.x', function () {
        $result = $this->service->validateUrlForSsrf('http://192.168.1.1/admin');

        expect($result)->not->toBeNull();
    });

    it('blocks private IP range 172.16.x', function () {
        $result = $this->service->validateUrlForSsrf('http://172.16.0.1/internal');

        expect($result)->not->toBeNull();
    });

    it('blocks unresolvable hostnames instead of allowing them', function () {
        $result = $this->service->validateUrlForSsrf('https://this-domain-does-not-exist-xyz123abc.invalid/path');

        expect($result)->not->toBeNull()
            ->and($result)->toBeString();
    });

    it('returns null for valid external https URL', function () {
        $result = $this->service->validateUrlForSsrf('https://cdn.example.com/image.jpg');

        // If the domain resolves to a public IP, this should pass.
        // If it doesn't resolve, it should now correctly block.
        // We test the logic, not actual DNS resolution.
        expect($result)->toBeIn([null, trans('filament-media::media.url_invalid')]);
    });

    it('blocks invalid URL format', function () {
        $result = $this->service->validateUrlForSsrf('not-a-url');

        expect($result)->not->toBeNull();
    });

    it('blocks URL without host', function () {
        $result = $this->service->validateUrlForSsrf('http:///path');

        expect($result)->not->toBeNull();
    });

    it('respects allowed_download_domains whitelist', function () {
        config(['media.allowed_download_domains' => ['trusted.com']]);

        $result = $this->service->validateUrlForSsrf('https://untrusted.com/image.jpg');

        // Should be blocked if domain doesn't resolve to public IP or isn't whitelisted
        // The domain check happens after IP validation, so unresolvable = blocked first
        expect($result)->not->toBeNull();
    });

    it('allows whitelisted domain', function () {
        config(['media.allowed_download_domains' => ['example.com']]);

        // example.com resolves to a public IP (93.184.216.34), so it should pass
        $result = $this->service->validateUrlForSsrf('https://example.com/image.jpg');

        expect($result)->toBeNull();
    });

    it('allows subdomain of whitelisted domain', function () {
        config(['media.allowed_download_domains' => ['example.com']]);

        $result = $this->service->validateUrlForSsrf('https://cdn.example.com/image.jpg');

        // cdn.example.com should match *.example.com whitelist
        // Result depends on DNS resolution of cdn.example.com
        expect($result)->toBeIn([null, trans('filament-media::media.url_invalid')]);
    });
});
