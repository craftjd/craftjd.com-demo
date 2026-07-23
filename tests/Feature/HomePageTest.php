<?php

namespace Tests\Feature;

use App\Livewire\CoffeeClubForm;
use App\Livewire\ContactForm;
use App\Livewire\TakeoutOrderForm;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    public function test_homepage_renders_joes_demo_sections(): void
    {
        File::put(storage_path('app/deploy-meta.json'), json_encode([
            'git_sha' => '8c48ff2',
            'deployed_at' => '2026-06-19T21:17:30Z',
            'summary' => 'Updated contact section hours for the sample site.',
            'request_id' => 42,
            'deployments' => [
                [
                    'git_sha' => '8c48ff2',
                    'deployed_at' => '2026-06-19T21:17:30Z',
                    'summary' => 'Updated contact section hours for the sample site.',
                    'request_id' => 42,
                ],
            ],
        ], JSON_THROW_ON_ERROR));

        config([
            'client.site_name' => "Joe's",
            'client.site_description' => 'Drinkable Coffee.',
            'joes.main_site_webhook_url' => 'https://craftjd.test/api/joes/requests',
            'joes.webhook_secret' => 'test-secret',
        ]);

        Http::fake([
            'https://craftjd.test/api/joes/requests*' => Http::response([
                'data' => [
                    [
                        'number' => 42,
                        'request' => 'Please update summer hours.',
                        'fulfillment_summary' => 'Updated contact section hours for the sample site.',
                        'submitted_at' => '2026-06-19T20:00:00Z',
                        'claimed_at' => '2026-06-19T20:30:00Z',
                        'completed_at' => '2026-06-19T21:17:30Z',
                    ],
                ],
            ]),
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Joe&#039;s', false)
            ->assertSee('Drinkable Coffee.', false)
            ->assertSee('Ridge Line Roasters', false)
            ->assertSee('Find the shop', false)
            ->assertSee('id="gallery"', false)
            ->assertSee("Joe's requests", false)
            ->assertSee('Updated contact section hours for the sample site.', false)
            ->assertSee('Morning Bun', false)
            ->assertSee('Trail Mix Latte', false)
            ->assertSee('Joe Harlan', false)
            ->assertSee('Coffee of the Month Club', false)
            ->assertSee('id="coffee"', false)
            ->assertSee('id="contact"', false)
            ->assertSee('Place a takeout order', false);
    }

    public function test_contact_form_validates_and_shows_demo_success(): void
    {
        Livewire::test(ContactForm::class)
            ->set('name', 'A')
            ->set('email', 'not-an-email')
            ->set('message', 'short')
            ->call('submit')
            ->assertHasErrors(['name', 'email', 'message'])
            ->set('name', 'Alex Guest')
            ->set('email', 'alex@example.com')
            ->set('message', 'Where exactly are you located?')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('submitted', true)
            ->assertSee('Message received.');
    }

    public function test_takeout_form_validates_and_shows_demo_success(): void
    {
        Livewire::test(TakeoutOrderForm::class)
            ->call('submit')
            ->assertHasErrors(['name', 'email', 'phone', 'pickup_time', 'order_details'])
            ->set('name', 'Sam Guest')
            ->set('email', 'sam@example.com')
            ->set('phone', '416-555-0199')
            ->set('pickup_time', 'Today at 9am')
            ->set('order_details', 'Large drip and a morning bun')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('submitted', true)
            ->assertSee('Order submitted!');
    }

    public function test_coffee_club_form_validates_and_shows_demo_success(): void
    {
        Livewire::test(CoffeeClubForm::class)
            ->set('name', 'Jordan')
            ->set('email', 'jordan@example.com')
            ->set('delivery', 'pickup')
            ->set('grind', 'whole')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('submitted', true)
            ->assertSee('sort of', false);
    }

    public function test_page_includes_canonical_and_open_graph_tags(): void
    {
        config([
            'client.site_name' => "Joe's",
            'client.site_description' => 'Drinkable Coffee.',
            'client.seo.og_image' => 'https://example.com/og.jpg',
            'client.seo.twitter_handle' => '@joes',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('rel="canonical"', false)
            ->assertSee('property="og:title"', false)
            ->assertSee('property="og:image"', false)
            ->assertSee('name="twitter:site"', false);
    }
}
