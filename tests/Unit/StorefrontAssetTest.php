<?php

namespace Tests\Unit;

use App\Support\StorefrontAsset;
use Tests\TestCase;

class StorefrontAssetTest extends TestCase
{
    public function test_it_resolves_public_chat_icon_assets(): void
    {
        $url = StorefrontAsset::imageUrl('assets/chat-icons/contact-chat-3d-4a0f579f63.png');

        $this->assertNotNull($url);
        $this->assertStringEndsWith('/assets/chat-icons/contact-chat-3d-4a0f579f63.png', $url);
    }

    public function test_it_rejects_unsafe_or_missing_icon_paths(): void
    {
        $this->assertNull(StorefrontAsset::imageUrl('../.env'));
        $this->assertNull(StorefrontAsset::imageUrl('https://example.com/icon.png'));
        $this->assertNull(StorefrontAsset::imageUrl('chat-icons/missing.png'));
    }

    public function test_only_storage_chat_icons_are_managed_uploads(): void
    {
        $this->assertTrue(StorefrontAsset::isManagedChatUpload('chat-icons/custom.png'));
        $this->assertFalse(StorefrontAsset::isManagedChatUpload('assets/chat-icons/contact-chat-3d-4a0f579f63.png'));
    }
}
