<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\PostCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::where('is_admin', true)->orderBy('id')->first();
        if ($author === null) {
            $this->command?->warn('Örnek içerikler için önce admin:create komutuyla bir yönetici oluşturun.');

            return;
        }

        DB::transaction(function () use ($author): void {
            foreach ($this->examples() as $index => $example) {
                $categoryKey = $example['type'] === 'news' ? 'nova-editor-notes' : 'nova-production';
                $category = PostCategory::where('seed_key', $categoryKey)->first();
                if ($category === null) {
                    $category = PostCategory::firstOrNew(['slug' => Str::slug($example['category'], '-', 'tr')]);
                    if (! $category->exists) {
                        $category->fill(['name' => $example['category'], 'is_active' => true, 'sort_order' => $example['type'] === 'news' ? 1 : 0]);
                        $category->seed_key = $categoryKey;
                        $category->save();
                    }
                }
                if (Post::where('seed_key', $example['key'])->exists() || Post::where('slug', Str::slug($example['title'], '-', 'tr'))->exists()) {
                    continue;
                }
                $post = new Post;
                $post->seed_key = $example['key'];
                $post->fill([
                    'post_category_id' => $category->id, 'title' => $example['title'], 'slug' => Str::slug($example['title'], '-', 'tr'),
                    'excerpt' => $example['excerpt'], 'content' => $example['content'],
                    'type' => $example['type'], 'status' => 'published', 'published_at' => now()->subDays(5 - $index),
                    'is_featured' => $index === 0 || $index === 3, 'sort_order' => $index,
                    'cover_image' => 'images/editorial.svg', 'meta_description' => Str::limit($example['excerpt'], 160, ''),
                ]);
                $post->author()->associate($author);
                $post->save();
            }
        });
    }

    /** @return list<array{key: string, title: string, category: string, type: string, excerpt: string, content: string}> */
    private function examples(): array
    {
        return [
            [
                'key' => 'quality-control', 'title' => 'Endüstriyel Üretimde Kalite Kontrolün Önemi',
                'category' => 'Üretim ve Mühendislik', 'type' => 'article',
                'excerpt' => 'Kaliteyi yalnızca son ölçümde aramak yerine, teknik gereksinimlerden başlayan bir süreç olarak ele almak.',
                'content' => <<<'MD'
## Kaliteyi sürecin başında tanımlamak
Bir parçanın doğru üretilip üretilmediğini değerlendirebilmek için önce “doğru”nun ne olduğunu tarif etmek gerekir. Teknik resim, malzeme bilgisi ve kullanım koşulları bu tarifin temelini oluşturur. Ölçü toleransları kadar yüzey beklentileri ve montaj ilişkileri de birlikte değerlendirilmelidir.

Kalite kontrol planı, üretim tamamlandıktan sonra hazırlanan bir kontrol listesinden daha fazlasıdır. Hangi özelliğin, hangi aşamada ve hangi yöntemle inceleneceğini görünür kılar. Böylece ekipler aynı beklentiyi paylaşabilir.

### Kontrol noktalarını seçmek
- Kritik ölçüleri parçanın işleviyle ilişkilendirin.
- İlk parça değerlendirmesini üretim planına dahil edin.
- Ölçüm yöntemini ve kayıt düzenini önceden belirleyin.
- Uygunsuzluk halinde izlenecek adımları netleştirin.

## Ölçmek ve öğrenmek
Bir ölçüm sonucu tek başına bütün süreci anlatmaz. Kullanılan ekipman, ölçüm koşulları ve kayıtların tutarlılığı sonucun yorumlanmasını etkiler. Bu nedenle kontrolün amacı yalnızca kabul veya ret kararı vermek değil, süreci daha anlaşılır hale getirmektir.

> Kalite, bir kontrol noktasından çok ortak bir çalışma disiplinidir.

### Sürekli iyileştirme için geri bildirim
Tekrarlayan sapmaların nedenlerini incelemek, üretim ve kontrol ekiplerinin aynı kayıtlar üzerinden konuşmasını sağlar. Bu yaklaşım, sonraki işlerde planlama kararlarını geliştirmek için bir öğrenme zemini oluşturur.

Bu yazı kurgusal Nova Endüstri sitesi için hazırlanmış genel bir bilgilendirme içeriğidir; sertifikasyon veya belirli bir üretim kapasitesi iddiası içermez.
MD,
            ],
            [
                'key' => 'cnc-efficiency', 'title' => 'CNC İşleme Süreçlerinde Verimlilik',
                'category' => 'Üretim ve Mühendislik', 'type' => 'article',
                'excerpt' => 'Verimliliği yalnızca kesme hızında değil; hazırlık, bağlama, takım seçimi ve kontrol adımlarının bütününde düşünmek.',
                'content' => <<<'MD'
## İşleme süresinin ötesine bakmak
CNC üretimde bir işin toplam süresi, makinenin kesme yaptığı süreyle sınırlı değildir. Teknik hazırlık, bağlama düzeni, takım değişimi ve ara kontroller de iş akışının parçasıdır. İyileştirme arayışına başlamadan önce bu adımların nasıl yürüdüğünü anlamak gerekir.

### Hazırlığı standartlaştırmak
1. Teknik resimde belirsiz kalan noktaları üretim öncesinde netleştirin.
2. Malzeme ve bağlama gereksinimlerini birlikte değerlendirin.
3. Takım listesini operasyon sırasıyla ilişkilendirin.
4. İlk parça kontrolü için zaman ve sorumluluk tanımlayın.

## Dengeli bir süreç kurmak
Kesme parametreleri; malzeme, takım, parça geometrisi ve makine koşullarına bağlıdır. Tek bir parametreyi artırmak, bütün sürecin daha iyi işleyeceği anlamına gelmez. Yüzey beklentileri, takım ömrü ve ölçü tutarlılığı birlikte ele alınmalıdır.

**Verimlilik değerlendirmesi**, tekrar eden hazırlık işlerini ve bekleme noktalarını görünür kıldığında daha anlamlı hale gelir. Açık operasyon notları, ekip içi bilgi aktarımını da kolaylaştırır.

### Geri bildirimi kaydetmek
Operasyon sırasında karşılaşılan durumları kısa ve anlaşılır notlarla kaydetmek, sonraki işleme planına katkı sağlar. Kayıtlar kişiye bağlı bilgiyi ortak bir çalışma kaynağına dönüştürür.

Bu içerik genel bilgilendirme amaçlıdır. Uygulama parametreleri ilgili makine, takım ve malzeme koşulları için ayrıca değerlendirilmelidir.
MD,
            ],
            [
                'key' => 'digital-traceability', 'title' => 'Üretimde Dijital Dönüşüm ve İzlenebilirlik',
                'category' => 'Üretim ve Mühendislik', 'type' => 'article',
                'excerpt' => 'İzlenebilir bir üretim akışı için önce hangi bilginin, hangi karar için gerekli olduğunu belirlemek.',
                'content' => <<<'MD'
## Dijitalleşmeye doğru soruyla başlamak
Üretimde dijital dönüşüm, mevcut formları ekrana taşımaktan daha kapsamlıdır. Temel soru, hangi bilginin iş akışını ve kararları daha anlaşılır hale getireceğidir. Sipariş, teknik resim revizyonu, malzeme ve kontrol kayıtları arasındaki bağlantılar bu açıdan önem taşır.

### İzlenebilirlik için ortak bir dil
Bir parçayla ilgili kayıtların farklı ekipler tarafından bulunabilmesi için adlandırma ve revizyon düzeni tutarlı olmalıdır. Aynı bilgi farklı sistemlerde farklı isimlerle tutuluyorsa, dijital araçlar tek başına bu belirsizliği gidermez.

- Ürün ve parça kodlarını açık biçimde tanımlayın.
- Kullanılan teknik resim revizyonunu kayda bağlayın.
- Kontrol sonuçlarını ilgili iş adımıyla ilişkilendirin.
- Kayıtların kimler tarafından güncelleneceğini belirleyin.

## Küçük ve doğrulanabilir adımlar
Bütün süreci tek seferde değiştirmek yerine, sınırlı bir iş akışı üzerinde başlamak öğrenmeyi kolaylaştırır. Ekiplerin kullandığı alanları ve eksik kalan bilgileri gözlemlemek, sistemin günlük işe uyumunu değerlendirmeye yardımcı olur.

> İyi bir kayıt sistemi, veriyi toplamak kadar doğru bağlamda bulmayı da kolaylaştırır.

### İnsan ve süreç uyumu
Dijital kayıtların sürdürülebilmesi için sorumluluklar anlaşılır olmalı ve giriş adımları günlük işin doğal bir parçası haline gelmelidir. Kullanıcı geri bildirimleri bu düzenin iyileştirilmesinde değerlidir.

Bu yazı, kurgusal bir şirketin örnek sitesindeki genel içeriktir; Nova Endüstri'de belirli bir sistemin devreye alındığı iddiası taşımaz.
MD,
            ],
            [
                'key' => 'sample-technical-news', 'title' => 'Temsili Haber: Teknik İçeriklere Yakından Bakış',
                'category' => 'Editör Notları', 'type' => 'news',
                'excerpt' => 'Örnek haber içeriği: üretim ve mühendislik yazılarının nasıl sunulabileceğine dair bir editör notu.',
                'content' => <<<'MD'
## Bu bir örnek haber içeriğidir
Bu sayfa, kurgusal Nova Endüstri projesinin haber modülünü göstermek amacıyla hazırlanmıştır. Gerçek bir şirket duyurusu, etkinlik katılımı, sertifika veya iş ortaklığı bildirmez.

### Açık ve yararlı içerik yaklaşımı
Bir kurumsal haber sayfasında başlık, kısa açıklama, yayın tarihi ve yazar bilgisinin birlikte sunulması okuyucunun bağlamı anlamasını kolaylaştırır. Teknik kavramların sade bir dille açıklanması da bu bütünün parçasıdır.

Bu örnekte içerik; başlıklar, paragraflar ve kısa notlarla düzenlenmiştir. Yönetim panelindeki editör aynı yapıyla yeni metinler hazırlamaya imkan verir.

## Konuya devam etmek
Üretim süreçlerine ilişkin genel bilgilendirme yazılarına [blog sayfasından](/blog) ulaşabilirsiniz. Buradaki tüm örnekler kurgusal proje bağlamında değerlendirilmelidir.
MD,
            ],
            [
                'key' => 'sample-catalog-news', 'title' => 'Örnek Haber: Ürün Bilgilerini Anlaşılır Sunmak',
                'category' => 'Editör Notları', 'type' => 'news',
                'excerpt' => 'Temsili içerik: bir ürün kataloğunda kategori, açıklama ve görsel bütünlüğünün önemini ele alan örnek haber.',
                'content' => <<<'MD'
## Temsili içerik hakkında
Bu metin, haber sayfasının örnek kullanımını göstermek için yazılmıştır. Gerçekleşmiş bir ürün lansmanı, kapasite artışı veya ticari başarı duyurusu değildir.

### Ürün bilgisinde tutarlılık
Bir ürün kartındaki kısa açıklama ile detay sayfasındaki metnin birbirini tamamlaması, ziyaretçinin doğru soruları sormasını kolaylaştırır. Kategori bilgisi ise benzer ihtiyaçlara yönelik ürünlerin birlikte keşfedilmesini sağlar.

- Açık bir ürün adı kullanmak
- Görselin temsili olup olmadığını belirtmek
- Teknik kapsamı anlaşılır biçimde tarif etmek
- Sonraki iletişim adımına yer vermek

## Kataloğu keşfetmek
Nova Endüstri'nin [örnek ürün kataloğu](/urunler), kurgusal endüstriyel bileşenleri gösterir. Bu haber dahil olmak üzere örnek site içeriği gerçek bir satış veya üretim taahhüdü oluşturmaz.
MD,
            ],
        ];
    }
}
