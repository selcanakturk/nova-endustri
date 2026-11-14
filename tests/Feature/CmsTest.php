<?php

use App\CatalogImage;
use App\Models\Page;
use App\Models\Product;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Models\User;
use Database\Seeders\PageSeeder;
use Database\Seeders\ServiceSeeder;
use Database\Seeders\SiteSettingSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

/** @return array<string, mixed> */
function cmsSettingsData(array $overrides = []): array
{
    return array_replace(['company_name' => 'Örnek Mühendislik', 'email' => 'bilgi@example.com', 'phone' => '+90 212 000 00 00', 'address' => 'Örnek adres', 'working_hours' => 'Hafta içi 09.00–18.00', 'maps_url' => 'https://maps.google.com/?q=Istanbul', 'linkedin' => 'https://www.linkedin.com/company/example', 'footer_description' => 'Özenli üretim', 'short_description' => 'Teknik çözümler', 'default_meta_title' => 'Kurumsal çözümler', 'default_meta_description' => 'Şirket açıklaması'], $overrides);
}

/** @return array<string, mixed> */
function cmsPageData(Page $page, array $overrides = []): array
{
    return array_replace(collect(config('cms.pages.'.$page->key.'.fields'))->reject(fn (array $field) => $field['type'] === 'image')->map(fn (array $field) => $field['default'])->all(), $overrides);
}

/** @return array<string, mixed> */
function cmsServiceData(array $overrides = []): array
{
    return array_replace(['title' => 'Özel İmalat', 'slug' => '', 'short_description' => 'Özel parça üretimi', 'description' => 'Teknik değerlendirme ve üretim', 'icon' => 'manufacturing', 'sort_order' => 2, 'is_active' => '1'], $overrides);
}

test('CMS endpoints reject guests and ordinary users', function (string $method, string $path) {
    $page = Page::factory()->create();
    $service = Service::factory()->create();
    $path = str_replace(['{page}', '{service}'], [$page->id, $service->id], $path);
    $this->{$method}($path)->assertRedirect(route('admin.login'));
    $this->actingAs(User::factory()->create())->{$method}($path)->assertForbidden();
    expect(SiteSetting::count())->toBe(0);
    expect($service->fresh()->title)->toBe($service->title);
})->with([
    ['get', '/admin/settings'], ['put', '/admin/settings'], ['get', '/admin/pages'], ['get', '/admin/pages/{page}/edit'], ['put', '/admin/pages/{page}'],
    ['get', '/admin/services'], ['get', '/admin/services/create'], ['post', '/admin/services'], ['get', '/admin/services/{service}/edit'], ['put', '/admin/services/{service}'], ['get', '/admin/services/{service}/delete'], ['delete', '/admin/services/{service}'],
]);

test('administrators can open all CMS screens with protected forms', function () {
    $this->seed([PageSeeder::class, SiteSettingSeeder::class]);
    $service = Service::factory()->create();
    $this->actingAs(User::factory()->admin()->create());
    foreach (['/admin/settings', '/admin/pages', '/admin/pages/'.Page::first()->id.'/edit', '/admin/services', '/admin/services/create', '/admin/services/'.$service->id.'/edit', '/admin/services/'.$service->id.'/delete'] as $path) {
        $this->get($path)->assertOk()->assertSeeText('Site Ayarları');
    }
    $this->get('/admin/settings')->assertSee('name="_token"', false)->assertDontSee('SMTP')->assertDontSee('APP_KEY');
    expect(Service::count())->toBe(1);
});

test('settings updates persist one record and update shared public information without accepting secrets', function () {
    $this->actingAs(User::factory()->admin()->create())->put('/admin/settings', cmsSettingsData(['smtp_password' => 'hidden-secret', 'singleton_key' => 'other']))->assertRedirect('/admin/settings')->assertSessionHas('status');
    $this->put('/admin/settings', cmsSettingsData(['company_name' => 'İkinci Firma']))->assertRedirect();
    expect(SiteSetting::count())->toBe(1);
    expect(SiteSetting::first()->data)->not->toHaveKeys(['smtp_password', 'singleton_key']);
    $this->get('/iletisim')->assertOk()->assertSeeText('İkinci Firma')->assertSeeText('bilgi@example.com')->assertSeeText('Örnek adres')->assertSeeText('Hafta içi 09.00–18.00')->assertSee('https://www.linkedin.com/company/example')->assertDontSee('hidden-secret');
});

test('settings validation keeps existing data unchanged', function (array $input, string $field) {
    $this->seed(SiteSettingSeeder::class);
    $before = SiteSetting::first()->data;
    $this->actingAs(User::factory()->admin()->create())->from('/admin/settings')->put('/admin/settings', cmsSettingsData($input))->assertRedirect('/admin/settings')->assertSessionHasErrors($field);
    expect(SiteSetting::first()->data)->toBe($before);
})->with([
    [['company_name' => ''], 'company_name'], [['company_name' => str_repeat('a', 256)], 'company_name'], [['email' => 'bad-email'], 'email'], [['address' => ['array']], 'address'], [['maps_url' => 'javascript:alert(1)'], 'maps_url'], [['linkedin' => '//evil.example'], 'linkedin'], [['instagram' => 'data:text/html,evil'], 'instagram'], [['facebook' => 'https://user:pass@example.com'], 'facebook'], [['youtube' => "https://example.com\nInjected"], 'youtube'],
]);

test('each corporate page edits structured fields and preserves its fixed public address', function (string $key, string $path) {
    $this->seed(PageSeeder::class);
    $page = Page::where('key', $key)->firstOrFail();
    $this->actingAs(User::factory()->admin()->create())->put(route('admin.pages.update', $page), cmsPageData($page, [($key === 'home' ? 'hero_title_line_one' : 'heading_title') => 'Yeni kurumsal başlık', 'key' => 'unsafe', 'content' => ['arbitrary' => 'evil']]))->assertRedirect(route('admin.pages.edit', $page));
    expect($page->fresh()->key)->toBe($key);
    expect($page->fresh()->content[$key === 'home' ? 'hero_title_line_one' : 'heading_title'])->toBe('Yeni kurumsal başlık');
    expect($page->fresh()->content)->not->toHaveKey('arbitrary');
    $this->get($path)->assertOk()->assertSeeText('Yeni kurumsal başlık');
})->with([['home', '/'], ['about', '/hakkimizda'], ['services', '/hizmetler'], ['contact', '/iletisim']]);

test('page SEO and social sharing fields render escaped values and preserve canonical query strings', function () {
    $this->seed(PageSeeder::class);
    $page = Page::where('key', 'about')->firstOrFail();
    $this->actingAs(User::factory()->admin()->create())->put(route('admin.pages.update', $page), cmsPageData($page, ['meta_title' => 'Özel SEO', 'meta_description' => 'Meta açıklama', 'canonical_url' => 'https://example.com/hakkimizda?x=1&y=2', 'og_title' => 'Sosyal başlık', 'og_description' => 'Sosyal açıklama']))->assertRedirect();
    $this->get('/hakkimizda')->assertOk()->assertSee('<title>Özel SEO | Nova Endüstri</title>', false)->assertSee('name="description" content="Meta açıklama"', false)->assertSee('property="og:title" content="Sosyal başlık | Nova Endüstri"', false)->assertSee('property="og:description" content="Sosyal açıklama"', false)->assertSee('href="https://example.com/hakkimizda?x=1&amp;y=2"', false);
});

test('unsafe page links and canonical URLs are rejected without changing content', function (string $field, string $value) {
    $this->seed(PageSeeder::class);
    $page = Page::where('key', 'home')->firstOrFail();
    $before = $page->content;
    $this->actingAs(User::factory()->admin()->create())->put(route('admin.pages.update', $page), cmsPageData($page, [$field => $value]))->assertSessionHasErrors($field);
    expect($page->fresh()->content)->toBe($before);
})->with([['hero_cta_url', 'javascript:alert(1)'], ['cta_url', '//evil.example'], ['cta_url', '/\\evil.example'], ['canonical_url', '/relative'], ['canonical_url', 'data:text/html,evil']]);

test('page hero CTA supports safe external and local destinations', function () {
    $this->seed(PageSeeder::class);
    $page = Page::where('key', 'home')->firstOrFail();
    $this->actingAs(User::factory()->admin()->create())->put(route('admin.pages.update', $page), cmsPageData($page, ['hero_cta_url' => 'https://example.com/proje', 'cta_url' => '/iletisim?konu=imalat']))->assertRedirect();
    $this->get('/')->assertSee('href="https://example.com/proje"', false)->assertSee('href="/iletisim?konu=imalat"', false);
});

test('CMS and service output escape stored markup in public and administrator screens', function () {
    $this->seed(PageSeeder::class);
    $page = Page::where('key', 'home')->firstOrFail();
    $script = '<script>alert(1)</script>';
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin)->put('/admin/settings', cmsSettingsData(['company_name' => $script]))->assertRedirect();
    $this->put(route('admin.pages.update', $page), cmsPageData($page, ['hero_title_line_one' => $script, 'meta_title' => $script]))->assertRedirect();
    $service = Service::factory()->create(['title' => $script, 'description' => $script]);
    foreach (['/', '/hizmetler', '/admin/settings', '/admin/pages/'.$page->id.'/edit', '/admin/services/'.$service->id.'/edit', '/admin/services/'.$service->id.'/delete'] as $path) {
        $this->get($path)->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee($script, false);
    }
});

test('services support creation updates visibility order and confirmed deletion', function () {
    $this->actingAs(User::factory()->admin()->create())->post('/admin/services', cmsServiceData())->assertRedirect();
    $service = Service::firstOrFail();
    expect($service->slug)->toBe('ozel-imalat');
    $this->get('/hizmetler')->assertOk()->assertSeeText('Özel İmalat');
    $this->put(route('admin.services.update', $service), cmsServiceData(['slug' => $service->slug, 'is_active' => '0', 'sort_order' => 5]))->assertRedirect();
    expect($service->fresh()->is_active)->toBeFalse();
    expect($service->fresh()->sort_order)->toBe(5);
    $this->get('/hizmetler')->assertDontSeeText('Özel İmalat');
    $this->get('/')->assertDontSeeText('Özel İmalat');
    $this->get(route('admin.services.delete', $service))->assertOk()->assertSeeText('Özel İmalat')->assertSeeText('Evet, sil');
    expect($service->fresh())->not->toBeNull();
    $this->delete(route('admin.services.destroy', $service))->assertRedirect('/admin/services');
    $this->assertModelMissing($service);
});

test('services display active records in stable sort order', function () {
    Service::factory()->create(['title' => 'Son hizmet', 'sort_order' => 9]);
    Service::factory()->create(['title' => 'İlk hizmet', 'sort_order' => 0]);
    Service::factory()->create(['title' => 'Gizli hizmet', 'is_active' => false]);
    $this->get('/hizmetler')->assertOk()->assertSeeInOrder(['İlk hizmet', 'Son hizmet'])->assertDontSeeText('Gizli hizmet');
    $this->get('/')->assertSeeInOrder(['İlk hizmet', 'Son hizmet'])->assertDontSeeText('Gizli hizmet');
});

test('service validation rejects invalid inputs', function (array $input, string $field) {
    $this->actingAs(User::factory()->admin()->create())->post('/admin/services', cmsServiceData($input))->assertSessionHasErrors($field);
    expect(Service::count())->toBe(0);
})->with([
    [['title' => ''], 'title'], [['slug' => '../unsafe'], 'slug'], [['icon' => '<svg onload="alert(1)">'], 'icon'], [['sort_order' => '-1'], 'sort_order'], [['is_active' => 'bad'], 'is_active'], [['short_description' => str_repeat('a', 1001)], 'short_description'], [['description' => str_repeat('a', 10001)], 'description'],
]);

test('service slugs remain unique and unchanged when validation fails', function () {
    $service = Service::factory()->create(['slug' => 'unique-service']);
    $this->actingAs(User::factory()->admin()->create())->post('/admin/services', cmsServiceData(['slug' => 'unique-service']))->assertSessionHasErrors(['slug' => 'Slug zaten kullanılıyor.']);
    expect(Service::count())->toBe(1);
    expect($service->fresh()->slug)->toBe('unique-service');
});

test('CMS seeders preserve administrator changes and do not duplicate existing records', function () {
    $this->seed([SiteSettingSeeder::class, PageSeeder::class, ServiceSeeder::class]);
    $settings = SiteSetting::firstOrFail();
    $settings->update(['data' => ['company_name' => 'Yönetici firma adı']]);
    $page = Page::where('key', 'home')->firstOrFail();
    $page->update(['content' => ['hero_title_line_one' => 'Yönetici başlığı']]);
    $service = Service::firstOrFail();
    $service->update(['title' => 'Yönetici hizmeti', 'slug' => 'changed-service', 'is_active' => false]);
    $this->seed([SiteSettingSeeder::class, PageSeeder::class, ServiceSeeder::class]);
    expect(SiteSetting::count())->toBe(1);
    expect(Page::count())->toBe(4);
    expect(Service::count())->toBe(3);
    expect($settings->fresh()->data['company_name'])->toBe('Yönetici firma adı');
    expect($page->fresh()->content['hero_title_line_one'])->toBe('Yönetici başlığı');
    expect($service->fresh()->title)->toBe('Yönetici hizmeti');
    expect($service->fresh()->is_active)->toBeFalse();
});

test('settings images upload replace and remove while shared references and static SVG assets survive', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->admin()->create())->put('/admin/settings', cmsSettingsData(['logo' => UploadedFile::fake()->image('logo.png')]))->assertRedirect();
    $path = SiteSetting::first()->data['logo'];
    $service = Service::factory()->create(['image' => $path]);
    $this->put('/admin/settings', cmsSettingsData(['logo' => UploadedFile::fake()->image('replacement.webp')]))->assertRedirect();
    Storage::disk('public')->assertExists($path);
    $replacement = SiteSetting::first()->data['logo'];
    $this->get('/')->assertSee(Storage::disk('public')->url($replacement));
    $this->put('/admin/settings', cmsSettingsData(['remove_logo' => '1']))->assertRedirect();
    Storage::disk('public')->assertMissing($replacement);
    expect(SiteSetting::first()->data['logo'])->toBeNull();
    $this->delete(route('admin.services.destroy', $service))->assertRedirect();
    Storage::disk('public')->assertMissing($path);
    expect(is_file(public_path('images/engineering.svg')))->toBeTrue();
});

test('page section and Open Graph images upload replace remove and retain original SVG files', function () {
    Storage::fake('public');
    $this->seed(PageSeeder::class);
    $page = Page::where('key', 'home')->firstOrFail();
    $this->actingAs(User::factory()->admin()->create())->put(route('admin.pages.update', $page), cmsPageData($page, ['hero_image' => UploadedFile::fake()->image('hero.jpg'), 'og_image' => UploadedFile::fake()->image('og.png')]))->assertRedirect();
    $hero = $page->fresh()->content['hero_image'];
    $og = $page->fresh()->seo['og_image'];
    Storage::disk('public')->assertExists([$hero, $og]);
    $this->get('/')->assertSee(Storage::disk('public')->url($hero))->assertSee('property="og:image" content="'.Storage::disk('public')->url($og).'"', false);
    $this->put(route('admin.pages.update', $page), cmsPageData($page, ['remove_hero_image' => 1, 'remove_og_image' => 1]))->assertRedirect();
    Storage::disk('public')->assertMissing([$hero, $og]);
    expect($page->fresh()->content['hero_image'])->toBeNull();
    expect(is_file(public_path('images/engineering.svg')))->toBeTrue();
});

test('settings favicon and default social images render on public pages', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->admin()->create())->put('/admin/settings', cmsSettingsData(['favicon' => UploadedFile::fake()->image('favicon.png'), 'social_image' => UploadedFile::fake()->image('social.jpg')]))->assertRedirect();
    $data = SiteSetting::first()->data;
    $this->get('/iletisim')->assertSee('rel="icon" href="'.Storage::disk('public')->url($data['favicon']).'"', false)->assertSee('property="og:image" content="'.Storage::disk('public')->url($data['social_image']).'"', false);
});

test('service uploads replace and remove images without deleting a product shared image', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->admin()->create())->post('/admin/services', cmsServiceData(['image' => UploadedFile::fake()->image('service.png')]))->assertRedirect();
    $service = Service::firstOrFail();
    $old = $service->image;
    Product::factory()->create(['image' => $old]);
    $this->put(route('admin.services.update', $service), cmsServiceData(['slug' => $service->slug, 'image' => UploadedFile::fake()->image('new.jpg')]))->assertRedirect();
    Storage::disk('public')->assertExists($old);
    $new = $service->fresh()->image;
    $this->put(route('admin.services.update', $service), cmsServiceData(['slug' => $service->slug, 'remove_image' => 1]))->assertRedirect();
    Storage::disk('public')->assertMissing($new);
    expect($service->fresh()->image)->toBeNull();
});

test('unsafe image files sizes dimensions and fabricated paths are rejected', function (string $kind) {
    Storage::fake('public');
    $file = match ($kind) {
        'svg' => UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"/>'),
        'extension' => UploadedFile::fake()->image('image.php'),
        'size' => UploadedFile::fake()->image('large.png')->size(4097),
        'dimensions' => UploadedFile::fake()->image('wide.png', 6001, 1),
        default => 'cms/fabricated.png',
    };
    $this->actingAs(User::factory()->admin()->create())->put('/admin/settings', cmsSettingsData(['logo' => $file]))->assertSessionHasErrors('logo');
    expect(SiteSetting::count())->toBe(0);
    expect(Storage::disk('public')->allFiles())->toBe([]);
})->with(['svg', 'extension', 'size', 'dimensions', 'path']);

test('safe image deletion rejects traversal and retains files referenced from page SEO', function () {
    Storage::fake('public');
    Storage::disk('public')->put('cms/shared.png', 'image');
    Page::factory()->create(['seo' => ['og_image' => 'cms/shared.png']]);
    CatalogImage::deleteIfUnused('cms/shared.png');
    Storage::disk('public')->assertExists('cms/shared.png');
    CatalogImage::deleteIfUnused('cms/../shared.png');
    expect(CatalogImage::url('cms/../../secret.png'))->toBe(asset('images/product-placeholder.svg'));
});

test('product SEO updates are backward compatible and do not change product content', function () {
    $product = Product::factory()->create(['slug' => 'seo-product', 'name' => 'Teknik parça']);
    $this->get('/urunler/seo-product')->assertSee('<title>Teknik parça | Nova Endüstri</title>', false);
    $this->actingAs(User::factory()->admin()->create())->put(route('admin.products.update', $product), adminProductData($product->category, ['name' => $product->name, 'slug' => $product->slug, 'meta_title' => 'Özel ürün SEO', 'meta_description' => 'Özel ürün açıklaması']))->assertRedirect();
    expect($product->fresh()->name)->toBe('Teknik parça');
    $this->get('/urunler/seo-product')->assertSee('<title>Özel ürün SEO | Nova Endüstri</title>', false)->assertSee('name="description" content="Özel ürün açıklaması"', false)->assertSee('rel="canonical" href="'.route('products.show', 'seo-product').'"', false);
});

test('sitemap keeps corporate URLs and visible products without creating private service or CMS URLs', function () {
    $visible = Product::factory()->create(['slug' => 'public-product']);
    Product::factory()->create(['slug' => 'hidden-product', 'is_active' => false]);
    Service::factory()->create(['slug' => 'private-service', 'is_active' => false]);
    $this->get('/sitemap.xml')->assertOk()->assertSee(route('about'))->assertSee(route('services'))->assertSee(route('products.show', $visible->slug))->assertDontSee('hidden-product')->assertDontSee('private-service')->assertDontSee('/admin/');
});

test('grouped CMS forms preserve field names values and image controls', function () {
    $this->seed([PageSeeder::class, SiteSettingSeeder::class]);
    $this->actingAs(User::factory()->admin()->create());
    $this->get('/admin/settings')->assertOk()->assertSee('name="company_name"', false)->assertSee('value="Nova Endüstri"', false)->assertSee('name="logo"', false)->assertSee('name="remove_logo"', false)->assertSee('name="youtube"', false)->assertDontSee('name="0"', false);
    $page = Page::where('key', 'home')->firstOrFail();
    $this->get(route('admin.pages.edit', $page))->assertOk()->assertSee('name="hero_title_line_one"', false)->assertSee('value="Güçlü fikirler."', false)->assertSee('name="hero_image"', false)->assertSee('name="canonical_url"', false)->assertSee('name="og_image"', false);
});

test('clearing page SEO falls back to site defaults and blank setting defaults remain usable', function () {
    $this->seed(PageSeeder::class);
    $page = Page::where('key', 'home')->firstOrFail();
    $this->actingAs(User::factory()->admin()->create())->put('/admin/settings', cmsSettingsData())->assertRedirect();
    $this->put(route('admin.pages.update', $page), cmsPageData($page, ['meta_title' => '', 'meta_description' => '']))->assertRedirect();
    $this->get('/')->assertOk()->assertSee('<title>Kurumsal çözümler | Örnek Mühendislik</title>', false)->assertSee('name="description" content="Şirket açıklaması"', false);
    $this->put('/admin/settings', cmsSettingsData(['default_meta_title' => '', 'default_meta_description' => '']))->assertRedirect();
    $this->get('/')->assertOk()->assertSee('<title>Endüstriye güç veren çözümler | Örnek Mühendislik</title>', false);
});

test('service list paginates and preserves stable ordering across pages', function () {
    Service::factory()->count(16)->sequence(fn ($sequence) => ['title' => 'Hizmet '.str_pad((string) $sequence->index, 2, '0', STR_PAD_LEFT), 'sort_order' => $sequence->index])->create();
    $this->actingAs(User::factory()->admin()->create())->get('/admin/services')->assertOk()->assertSeeText('Hizmet 00')->assertDontSeeText('Hizmet 15');
    $this->get('/admin/services?page=2')->assertOk()->assertSeeText('Hizmet 15')->assertDontSeeText('Hizmet 00');
});

test('CMS changes require CSRF tokens outside the test bypass', function () {
    $this->actingAs(User::factory()->admin()->create());
    $this->app->instance('env', 'local');
    $this->put('/admin/settings', cmsSettingsData())->assertStatus(419);
    expect(SiteSetting::count())->toBe(0);
});

test('failed CMS saves remove new uploads and preserve previously saved content and files', function () {
    Storage::fake('public');
    Storage::disk('public')->put('cms/previous.png', 'previous-image');
    $page = Page::factory()->create(['content' => ['hero_image' => 'cms/previous.png']]);
    $this->actingAs(User::factory()->admin()->create());
    Event::listen('eloquent.saving: '.Page::class, function (): void {
        throw new RuntimeException('Simulated database failure');
    });

    try {
        $this->put(route('admin.pages.update', $page), cmsPageData($page, ['hero_image' => UploadedFile::fake()->image('new.png'), 'og_image' => UploadedFile::fake()->image('social.png')]))->assertStatus(500);
    } finally {
        Event::forget('eloquent.saving: '.Page::class);
    }

    expect($page->fresh()->content)->toBe(['hero_image' => 'cms/previous.png']);
    expect(Storage::disk('public')->allFiles())->toBe(['cms/previous.png']);
});
