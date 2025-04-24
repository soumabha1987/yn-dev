<?php

declare(strict_types=1);

namespace App\Livewire\Creditor\Traits;

use Illuminate\Validation\ValidationException;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;

trait ValidateMarkdown
{
    public function validateContent(string $content, string $key = 'form.content'): void
    {
        $trimmed = trim($content);

        $environment = new Environment;
        $environment->addExtension(new CommonMarkCoreExtension);
        $converter = new MarkdownConverter($environment);

        $htmlContent = $converter->convert($trimmed)->getContent();

        $plainText = strip_tags($htmlContent);

        if (blank(trim($plainText))) {
            throw ValidationException::withMessages([
                $key => __('validation.required', ['attribute' => 'content']),
            ]);
        }
    }
}
