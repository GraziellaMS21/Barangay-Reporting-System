<?php

namespace App\Services;

use Carbon\CarbonInterface;

class SignatoryResolver
{
    /**
     * STUB: WS2 implements full signatory resolution.
     * Return shape: [['label' => 'Prepared by', 'name' => '...', 'position' => '...', 'signature_path' => null], ...]
     * Every consumer must render a blank signature line when the array is empty.
     *
     * @return array<int, array{label: string, name: string, position: string, signature_path: ?string}>
     */
    public function forDocument(string $documentType, ?CarbonInterface $asOf = null): array
    {
        return [];
    }
}
