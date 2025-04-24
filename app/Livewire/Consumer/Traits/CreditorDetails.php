<?php

declare(strict_types=1);

namespace App\Livewire\Consumer\Traits;

use App\Enums\CustomContentType;
use App\Models\Consumer;

trait CreditorDetails
{
    /**
     * @return array{
     *    company_name: string,
     *    contact_person_name: string,
     *    custom_content: string,
     * }
     */
    public function setCreditorDetails(Consumer $consumer): array
    {
        $consumer->loadMissing('company.customContents');

        $aboutUs = $consumer->company->customContents->firstWhere('type', CustomContentType::ABOUT_US)?->content;

        return [
            'company_name' => $consumer->company->company_name,
            'contact_person_name' => $consumer->company->company_name,
            'custom_content' => $aboutUs ?? 'N/A',
        ];
    }
}
