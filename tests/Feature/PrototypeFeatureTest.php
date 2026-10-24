<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\PrototypeItem;
use App\Services\NotificationService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PrototypeFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_prototype_screen_renders_successfully(): void
    {
        $response = $this->get(route('prototype.index'));

        $response->assertStatus(200);
        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Prototype')
                ->has('items')
                ->has('systemInfo')
                ->where('systemInfo.direction', 'ltr')
                ->where('systemInfo.locale', 'en')
        );
    }

    public function test_prototype_item_can_be_stored_in_sqlite(): void
    {
        $payload = [
            'title' => 'Master TypeScript & Vue 3',
            'details' => 'Build high-performance mobile screens with NativePHP',
        ];

        $response = $this->post(route('prototype.items.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('prototype_items', [
            'title' => 'Master TypeScript & Vue 3',
            'details' => 'Build high-performance mobile screens with NativePHP',
        ]);

        $item = PrototypeItem::where('title', 'Master TypeScript & Vue 3')->first();
        $this->assertNotNull($item);
        $this->assertNotEmpty($item->id);
    }

    public function test_prototype_item_can_be_deleted_from_sqlite(): void
    {
        $item = PrototypeItem::create([
            'title' => 'Temporary Task',
            'details' => 'To be deleted',
        ]);

        $response = $this->delete(route('prototype.items.destroy', $item->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('prototype_items', [
            'id' => $item->id,
        ]);
    }

    public function test_notification_can_be_scheduled_safely(): void
    {
        $payload = [
            'seconds' => 15,
            'title' => 'Study Milestone',
            'body' => 'Review Spaced Repetition cards',
        ];

        $response = $this->post(route('prototype.notify'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $response->assertSessionHas('notification_result');

        $result = session('notification_result');
        $this->assertTrue($result['success']);
        $this->assertSame(15, $result['delay_seconds']);
        $this->assertStringStartsWith('pma_', $result['notification_id']);
    }

    public function test_locale_can_be_switched_to_arabic_rtl(): void
    {
        $response = $this->post(route('prototype.locale'), [
            'locale' => 'ar',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('locale', 'ar');

        $pageResponse = $this->get(route('prototype.index'));
        $pageResponse->assertInertia(
            fn (Assert $page) => $page
                ->component('Prototype')
                ->where('systemInfo.locale', 'ar')
                ->where('systemInfo.direction', 'rtl')
        );
    }

    public function test_mock_notification_driver_is_forbidden_in_production(): void
    {
        config([
            'app.env' => 'production',
            'services.notifications.driver' => 'mock',
        ]);

        $service = new NotificationService;

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Production Security Violation: Mock notification driver cannot silently be used in production.');

        $service->schedule('test_id', 'Test', 'Body', 10);
    }
}
