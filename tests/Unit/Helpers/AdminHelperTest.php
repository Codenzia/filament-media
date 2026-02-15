<?php

use Codenzia\FilamentMedia\Helpers\AdminHelper;

describe('AdminHelper', function () {
    it('returns true by default', function () {
        expect(AdminHelper::isInAdmin())->toBeTrue();
    });

    it('returns true with checkGuest parameter', function () {
        expect(AdminHelper::isInAdmin(checkGuest: true))->toBeTrue();
    });
});
