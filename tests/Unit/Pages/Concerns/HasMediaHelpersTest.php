<?php

use Codenzia\FilamentMedia\Pages\Concerns\HasMediaHelpers;

class HasMediaHelpersTestClass
{
    use HasMediaHelpers;

    public function testSeparateItemsByType(array $items): array
    {
        return $this->separateItemsByType($items);
    }
}

describe('HasMediaHelpers Trait', function () {
    beforeEach(function () {
        $this->helper = new HasMediaHelpersTestClass();
    });

    describe('separateItemsByType', function () {
        it('separates files and folders correctly', function () {
            $items = [
                ['id' => 1, 'is_folder' => false],
                ['id' => 2, 'is_folder' => true],
                ['id' => 3, 'is_folder' => false],
            ];

            $result = $this->helper->testSeparateItemsByType($items);

            expect($result)->toHaveKeys(['fileIds', 'folderIds'])
                ->and($result['fileIds'])->toBe([1, 3])
                ->and($result['folderIds'])->toBe([2]);
        });

        it('returns empty arrays when no items given', function () {
            $result = $this->helper->testSeparateItemsByType([]);

            expect($result['fileIds'])->toBe([])
                ->and($result['folderIds'])->toBe([]);
        });

        it('handles items without is_folder key as files', function () {
            $items = [
                ['id' => 1],
                ['id' => 2],
                ['id' => 3],
            ];

            $result = $this->helper->testSeparateItemsByType($items);

            expect($result['fileIds'])->toBe([1, 2, 3])
                ->and($result['folderIds'])->toBe([]);
        });

        it('handles mixed items with some files and some folders', function () {
            $items = [
                ['id' => 10, 'is_folder' => true],
                ['id' => 20],
                ['id' => 30, 'is_folder' => false],
                ['id' => 40, 'is_folder' => true],
                ['id' => 50],
            ];

            $result = $this->helper->testSeparateItemsByType($items);

            expect($result['fileIds'])->toBe([20, 30, 50])
                ->and($result['folderIds'])->toBe([10, 40]);
        });

        it('handles all folders', function () {
            $items = [
                ['id' => 1, 'is_folder' => true],
                ['id' => 2, 'is_folder' => true],
                ['id' => 3, 'is_folder' => true],
            ];

            $result = $this->helper->testSeparateItemsByType($items);

            expect($result['fileIds'])->toBe([])
                ->and($result['folderIds'])->toBe([1, 2, 3]);
        });

        it('handles all files', function () {
            $items = [
                ['id' => 1, 'is_folder' => false],
                ['id' => 2, 'is_folder' => false],
                ['id' => 3, 'is_folder' => false],
            ];

            $result = $this->helper->testSeparateItemsByType($items);

            expect($result['fileIds'])->toBe([1, 2, 3])
                ->and($result['folderIds'])->toBe([]);
        });
    });
});
