<?php

namespace Fleximize\LaravelDotdigitalMailDriver\DTOs;

class DotdigitalAttachmentDTO
{
    public function __construct(
        public string $fileName,
        public string $mimeType,
        public string $content,
    ) {
        //
    }
}
