<?php

use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Product;
use App\Models\QuoteRequest;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

test('public error pages are Turkish safe and independent of database and asset bundles', function (int $status, string $heading) {
    config(['app.debug' => false]);
    Route::get('/quality-error', function () use ($status): never {
        abort($status, 'PRIVATE internal detail');
    });
    $this->expectsDatabaseQueryCount(0);

    $this->get('/quality-error')->assertStatus($status)->assertSeeText($heading)
        ->assertSee('lang="tr"', false)->assertSee('name="robots" content="noindex, nofollow"', false)
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')->assertDontSee('PRIVATE internal detail')
        ->assertDontSee('/build/')->assertDontSee('SQLSTATE');
})->with([
    [403, 'Bu alana erişiminiz yok.'], [404, 'Aradığınız sayfa bulunamadı.'],
    [419, 'Oturumunuzun süresi doldu.'], [429, 'Biraz sonra tekrar deneyin.'],
    [500, 'İşleminizi şu anda tamamlayamadık.'],
]);

test('missing public and administrator records retain a real 404 status and offer appropriate recovery', function () {
    $this->get('/missing-page')->assertNotFound()->assertSeeText('Aradığınız sayfa bulunamadı.')->assertSee(route('home'));
    $this->actingAs(User::factory()->admin()->create())->get('/admin/products/9999/edit')->assertNotFound()->assertSeeText('Yönetim girişini aç');
});

test('production server errors do not expose exception messages or require the database to render', function () {
    config(['app.debug' => false]);
    Route::get('/quality-failure', function (): never {
        throw new RuntimeException('SECRET sql/password detail');
    });
    $this->expectsDatabaseQueryCount(0);

    $this->get('/quality-failure')->assertStatus(500)->assertSeeText('İşleminizi şu anda tamamlayamadık.')
        ->assertDontSee('SECRET')->assertDontSee('RuntimeException')->assertHeader('X-Content-Type-Options', 'nosniff');
});

test('JSON errors retain their content type and status', function () {
    $this->getJson('/not-found-json')->assertNotFound()->assertHeader('Content-Type', 'application/json')
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});

test('admin login redirects and authenticated personal records are never cached or indexed', function () {
    $this->get('/admin/login')->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow')->assertHeaderContains('Cache-Control', 'no-store');
    $this->get('/admin/messages')->assertRedirect(route('admin.login'))->assertHeaderContains('Cache-Control', 'no-store');
    $message = ContactMessage::factory()->create(['admin_note' => 'CONFIDENTIAL note']);
    $this->actingAs(User::factory()->admin()->create())->get(route('admin.messages.show', $message))->assertOk()->assertSeeText('CONFIDENTIAL note')->assertHeader('X-Robots-Tag', 'noindex, nofollow')->assertHeaderContains('Cache-Control', 'no-store');
});

test('public inquiry forms do not cache flashed personal information and quote forms are not indexed', function () {
    $product = Product::factory()->create(['slug' => 'request-product']);
    $this->get('/iletisim')->assertOk()->assertHeaderContains('Cache-Control', 'no-store')->assertHeaderMissing('X-Robots-Tag');
    $this->get('/urunler/request-product/teklif')->assertOk()->assertHeaderContains('Cache-Control', 'no-store')->assertHeader('X-Robots-Tag', 'noindex, nofollow')->assertSee('name="robots" content="noindex, nofollow"', false);
    $this->get('/urunler/request-product')->assertOk()->assertHeaderMissing('X-Robots-Tag')->assertHeader('X-Frame-Options', 'SAMEORIGIN')->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
});

test('product canonical URLs preserve category and pagination but exclude tracking parameters', function () {
    $category = Category::factory()->create(['slug' => 'precision']);
    Product::factory()->count(13)->for($category)->create();

    $canonical = route('products', ['category' => 'precision', 'page' => 2]);
    $this->get('/urunler?category=precision&page=2&utm_source=example')->assertOk()
        ->assertSee('rel="canonical" href="'.e($canonical).'"', false)
        ->assertSee('property="og:url" content="'.e($canonical).'"', false);
    $this->get('/urunler?category=precision&page=1&utm_source=example')->assertOk()
        ->assertSee('rel="canonical" href="'.route('products', ['category' => 'precision']).'"', false);
});

test('empty public services provide a useful state on both pages', function () {
    Service::factory()->create(['is_active' => false, 'title' => 'Private inactive service']);
    $this->get('/hizmetler')->assertOk()->assertSeeText('Henüz yayınlanmış hizmet bulunmuyor.')->assertDontSeeText('Private inactive service');
    $this->get('/')->assertOk()->assertSeeText('Henüz yayınlanmış hizmet bulunmuyor.');
});

test('service display numbers follow visible order rather than arbitrary stored sort values', function () {
    Service::factory()->create(['title' => 'First service', 'sort_order' => 70]);
    Service::factory()->create(['title' => 'Second service', 'sort_order' => 100]);

    $response = $this->get('/hizmetler')->assertOk();
    expect($response->getContent())->toMatch('/class="service-number">01 /')->toMatch('/class="service-number">02 /')->not->toMatch('/class="service-number">71 /');
});

test('legacy catalog and editorial image uploads reject excessive dimensions without changing data', function (string $resource) {
    Storage::fake('public');
    $this->actingAs(User::factory()->admin()->create());
    $image = UploadedFile::fake()->image('wide.png', 6001, 1);
    $category = Category::factory()->create();
    $postCategory = PostCategory::factory()->create();
    [$path, $data, $field, $table] = match ($resource) {
        'product' => ['/admin/products', adminProductData($category, ['image' => $image]), 'image', 'products'],
        'category' => ['/admin/categories', adminCategoryData(['image' => $image]), 'image', 'categories'],
        'post' => ['/admin/posts', adminPostData($postCategory, ['cover_image' => $image]), 'cover_image', 'posts'],
    };
    $before = DB::table($table)->count();

    $this->post($path, $data)->assertSessionHasErrors($field);

    $this->assertDatabaseCount($table, $before);
    expect(Storage::disk('public')->allFiles())->toBe([]);
})->with(['product', 'category', 'post']);

test('CMS file errors have labels descriptions and invalid-state associations', function () {
    $this->actingAs(User::factory()->admin()->create())->followingRedirects()->from('/admin/settings')->put('/admin/settings', ['company_name' => 'Company', 'logo' => 'not-an-upload'])
        ->assertOk()->assertSee('for="logo"', false)->assertSee('aria-describedby="logo-hint logo-error"', false)->assertSee('id="logo-error"', false)->assertSee('aria-invalid="true"', false);

});

test('critical public flows render with eager loaded relations', function () {
    $product = Product::factory()->create();
    $post = Post::factory()->published()->create();
    Post::factory()->published()->for($post->category, 'category')->count(3)->create();
    Model::preventLazyLoading();
    Model::handleLazyLoadingViolationUsing(function (Model $model, string $relation): never {
        throw new RuntimeException('Unexpected lazy relation: '.$relation);
    });

    try {
        foreach (['/', '/urunler', '/urunler/'.$product->slug, '/urunler/'.$product->slug.'/teklif', '/blog', '/blog/'.$post->slug, '/haberler', '/hizmetler', '/aydinlatma'] as $path) {
            $this->get($path)->assertOk();
        }
    } finally {
        Model::preventLazyLoading(false);
        Model::handleLazyLoadingViolationUsing(null);
    }
});

test('dashboard query count stays bounded with catalog and editorial data', function () {
    Product::factory()->create();
    Post::factory()->published()->create();
    ContactMessage::factory()->create();
    QuoteRequest::factory()->create();
    $this->actingAs(User::factory()->admin()->create());
    $this->expectsDatabaseQueryCount(11);

    $this->get('/admin')->assertOk();
});

test('inquiry phone numbers require digits as well as allowed formatting', function (string $phone) {
    $product = Product::factory()->create();
    $this->post('/iletisim', contactData(['phone' => $phone]))->assertSessionHasErrors(['phone' => 'Geçerli bir telefon numarası girin.']);
    $this->post(route('quotes.store', $product->slug), quoteData(['phone' => $phone]))->assertSessionHasErrors(['phone' => 'Geçerli bir telefon numarası girin.']);

    $this->assertDatabaseCount('contact_messages', 0);
    $this->assertDatabaseCount('quote_requests', 0);
})->with(['......', '+() .-', '+1 (2) 3-4.5']);

test('non administrators cannot infer record existence through GET or HEAD model binding', function (string $method) {
    $product = Product::factory()->create();
    $this->actingAs(User::factory()->create());
    $this->expectsDatabaseQueryCount(0);

    foreach ([$product->id, 999999] as $id) {
        $this->call($method, '/admin/products/'.$id.'/edit')->assertForbidden()->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }
})->with(['GET', 'HEAD']);
