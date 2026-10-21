<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ItemTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'code' => 'LAB-TS-001',
            'name' => 'Multimeter Digital',
            'category' => 'Elektronika',
            'quantity' => 5,
            'condition' => 'baik',
            'location' => 'Lab A-101',
            'notes' => 'Untuk praktikum dasar.',
            ...$overrides,
        ];
    }

    private function actingAsUser(): void
    {
        Sanctum::actingAs(User::factory()->create());
    }

    public function test_items_endpoints_require_token(): void
    {
        $item = Item::factory()->create();

        $this->getJson('/api/items')->assertStatus(401);
        $this->getJson("/api/items/{$item->id}")->assertStatus(401);
        $this->postJson('/api/items', $this->payload())->assertStatus(401);
        $this->putJson("/api/items/{$item->id}", $this->payload())->assertStatus(401);
        $this->deleteJson("/api/items/{$item->id}")->assertStatus(401);

        $this->assertDatabaseCount('items', 1);
    }

    public function test_can_list_items_with_pagination(): void
    {
        $this->actingAsUser();
        Item::factory()->count(15)->create();

        $this->getJson('/api/items?per_page=10')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 15)
            ->assertJsonStructure(['data' => [['id', 'code', 'name', 'category', 'quantity', 'condition', 'location']], 'links', 'meta']);
    }

    public function test_can_search_items(): void
    {
        $this->actingAsUser();
        Item::factory()->create(['name' => 'Osiloskop Rigol', 'code' => 'LAB-001']);
        Item::factory()->create(['name' => 'Proyektor Epson', 'code' => 'LAB-002', 'category' => 'Multimedia']);

        $this->getJson('/api/items?q=osilo')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', 'LAB-001');
    }

    public function test_can_show_single_item(): void
    {
        $this->actingAsUser();
        $item = Item::factory()->create();

        $this->getJson("/api/items/{$item->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $item->id)
            ->assertJsonPath('data.code', $item->code);
    }

    public function test_show_returns_404_for_missing_item(): void
    {
        $this->actingAsUser();

        $this->getJson('/api/items/999')->assertStatus(404);
    }

    public function test_can_create_item_and_returns_201(): void
    {
        $this->actingAsUser();

        $this->postJson('/api/items', $this->payload())
            ->assertStatus(201)
            ->assertJsonPath('data.code', 'LAB-TS-001')
            ->assertJsonPath('message', 'Item berhasil ditambahkan.');

        $this->assertDatabaseHas('items', ['code' => 'LAB-TS-001', 'quantity' => 5]);
    }

    public function test_create_returns_422_when_validation_fails(): void
    {
        $this->actingAsUser();

        $this->postJson('/api/items', [
            'quantity' => -1,
            'condition' => 'hilang',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['code', 'name', 'category', 'quantity', 'condition', 'location']);

        $this->assertDatabaseCount('items', 0);
    }

    public function test_create_rejects_duplicate_code(): void
    {
        $this->actingAsUser();
        Item::factory()->create(['code' => 'LAB-TS-001']);

        $this->postJson('/api/items', $this->payload())
            ->assertStatus(422)
            ->assertJsonValidationErrors('code');
    }

    public function test_can_update_item(): void
    {
        $this->actingAsUser();
        $item = Item::factory()->create(['code' => 'LAB-TS-001']);

        $this->putJson("/api/items/{$item->id}", $this->payload([
            'name' => 'Multimeter Analog',
            'condition' => 'rusak_ringan',
        ]))->assertOk()
            ->assertJsonPath('data.name', 'Multimeter Analog')
            ->assertJsonPath('data.condition', 'rusak_ringan');

        $this->assertDatabaseHas('items', ['id' => $item->id, 'name' => 'Multimeter Analog']);
    }

    public function test_update_returns_422_when_validation_fails(): void
    {
        $this->actingAsUser();
        $item = Item::factory()->create();

        $this->putJson("/api/items/{$item->id}", $this->payload(['quantity' => 'banyak']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('quantity');
    }

    public function test_can_delete_item(): void
    {
        $this->actingAsUser();
        $item = Item::factory()->create();

        $this->deleteJson("/api/items/{$item->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Item berhasil dihapus.');

        $this->assertDatabaseMissing('items', ['id' => $item->id]);
    }
}
