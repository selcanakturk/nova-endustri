<?php

use Database\Seeders\CatalogSeeder;

test('corporate pages render their content with shared navigation', function (string $path, string $heading) {
    $this->get($path)
        ->assertOk()
        ->assertSeeText($heading)
        ->assertSee('lang="tr"', false)
        ->assertSee('aria-current="page"', false)
        ->assertSeeText('Kurgusal firma');
})->with([
    ['/', 'Güçlü fikirler.'],
    ['/hakkimizda', 'İyi mühendislik. Güçlü bir temel.'],
    ['/urunler', 'Her parça, bir çözümün başlangıcı.'],
    ['/hizmetler', 'Fikirden parçaya. Parçadan sisteme.'],
    ['/iletisim', 'İyi bir çözüm, bir konuşmayla başlar.'],
]);

test('catalog displays all example products and identifies illustrative content', function () {
    $this->seed(CatalogSeeder::class);

    $this->get('/urunler')
        ->assertOk()
        ->assertSeeText('Hassas işlenmiş parçalar')
        ->assertSeeText('Makine taşıyıcı şaseleri')
        ->assertSeeText('satışa sunulan gerçek ürünleri');
});

test('contact page offers a protected live form and honest privacy information', function () {
    $this->get('/iletisim')
        ->assertOk()
        ->assertSee('action="'.route('contact.store').'"', false)
        ->assertSee('name="_token"', false)
        ->assertSeeText('hukuken onaylanmış bir aydınlatma metni henüz hazır değildir')
        ->assertDontSee('<fieldset disabled>', false)
        ->assertSeeText('Henüz paylaşılmadı')
        ->assertDontSeeText('Mesajınız gönderildi');
});
