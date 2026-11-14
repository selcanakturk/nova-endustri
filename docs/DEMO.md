# Demo ve portföy teslimi

Bu belge deployment yapmaz. Hosting seçimi ve yayın onayı alınmadan canlı ortam oluşturulmaz. Proje, kurgusal firma ve temsili içeriklerle sunulur.

## Barındırma değerlendirmesi

| Seçenek | Bu proje açısından değerlendirme |
| --- | --- |
| Kalıcı diskli tek sunucu / VPS | Mevcut SQLite yapısına en az değişiklikle uyarlanabilir. PHP-FPM, web sunucusu, HTTPS, işletim sistemi güncellemeleri, yedekleme ve erişim yönetimi gerekir. SQLite dosyası release klasöründen bağımsız, public dışında kalıcı bir konumda tutulmalıdır. |
| Laravel Forge ile yönetilen sunucu | Sunucu ve deployment yönetimini kolaylaştıran bir seçenek olarak değerlendirilebilir. Seçilecek sunucuda gereken PHP sürümü ve disk sürekliliği ayrıca doğrulanmalı; yeni release’in SQLite dosyasını yeniden oluşturmasına izin verilmemelidir. [Resmî deployment belgeleri](https://forge.laravel.com/docs/sites/deployments). |
| Laravel Cloud | Dosya sistemi kalıcılığı nedeniyle mevcut SQLite kurulumu doğrudan taşınmamalıdır. [Resmî SQLite notu](https://cloud.laravel.com/docs/knowledge-base/sqlite), Cloud production ortamlarında SQLite’ın desteklenmediğini belirtir. Bu seçenek için desteklenen kalıcı veritabanına geçiş, dosya depolama planı ve yeni veritabanı üzerinde regresyon testi gerekir. |

Fiyat, ücretsiz plan veya kapasite taahhüdü verilmez. Seçimden önce sağlayıcının güncel PHP, kalıcı disk, yedekleme, e-posta ve erişim özellikleri yeniden kontrol edilmelidir. GitHub Pages yalnızca statik yayın içindir; bu uygulamanın PHP backend’ini çalıştırmaz.

## Canlı demo öncesi

- PHP 8.4.1+ bağımlılık koşullarını doğrulayın; geliştirme doğrulaması PHP 8.5.0 ile yapılmıştır.
- `APP_ENV=production`, `APP_DEBUG=false`, kalıcı ve gizli `APP_KEY`, doğru `APP_URL`, HTTPS ve secure cookie ayarlarını hazırlayın.
- Web kökünü `public/` olarak belirleyin. `.env`, SQLite, log ve yedeklere HTTP erişimi vermeyin.
- SQLite kullanılacaksa `DB_DATABASE` için public dışında mutlak ve kalıcı yol belirleyin. SQLite’ın uygulamanın aynı kalıcı diske eriştiği tek sunuculu çalışma biçimine uygunluğunu doğrulayın; yatay ölçekleme varsaymayın.
- `storage/` ve `bootstrap/cache/` yazma izinlerini uygulama kullanıcısıyla sınırlandırın. Public upload URL’lerini ve `storage:link` bağlantısını test edin.
- Canlı veri yedeği alıp normal ileri yönlü migration uygulayın. Migration reset veya demo seeder’larını deployment adımına koymayın.
- Bildirimler senkron çalışır; mevcut form bildirimleri için queue worker gerekmez. SMTP alıcısını ve sağlayıcı yapılandırmasını gizli ortam değişkenleriyle tanımlayıp gerçek teslimi test edin.
- `admin:create` ile özel bir yönetici oluşturun; kimlik bilgilerini README veya herkese açık demo sayfasında paylaşmayın.
- Formların gerçek ziyaretçi verisi toplayacağını dikkate alın. Yayın sorumlusu, aydınlatma metni, saklama/silme süresi ve panel erişimi netleşmeden formları internete açmayın. Mevcut `/aydinlatma` sayfası onaylı hukuki metin değildir.
- Rate limit ve honeypot mevcut olsa da spam izleme ve gerekirse ek önlem planlayın. Canlı ortamdan alınan kişisel kayıtları Git’e veya ekran görüntülerine taşımayın.

## Gerçek ekran görüntüleri

Tarayıcı erişimi olmadığı için bu teslimde ekran görüntüsü üretilmedi. Sahte görsel ve boş görsel bağlantısı kullanılmadı.

Erişim sağlandığında güvenli örnek veriler içeren ayrı bir ortamda:

1. 1440 px: ana sayfa, ürün kataloğu, ürün detayı ve blog.
2. 1440 px: admin dashboard, ürün yönetimi, teklif yönetimi ve CMS düzenleme.
3. 375 ve 768 px: navbar, menü, kartlar, formlar, footer, sidebar ve tablolar.

Yalnızca gerçek uygulama görüntülerini, kişisel bilgiler içermediğini kontrol ettikten sonra `docs/screenshots/` içinde web için optimize ederek saklayın. README bağlantılarını dosyalar eklendiğinde oluşturun.

## GitHub teslimi

- `main` ve remote commit’lerini yayın öncesinde yeniden karşılaştırın.
- Yalnızca incelenen proje dosyalarını stage edin; `.env`, SQLite, log, session, vendor, node_modules ve yedekleri dışarıda tutun.
- Commit ve push öncesinde değişiklik özetini kullanıcıya sunup açık onay alın.
- Force push ve geçmişi yeniden yazma uygulamayın. Remote ilerlemişse ezmek yerine farkları inceleyin.
- Geçmiş taraması bilinen anahtar biçimleri ve hassas dosya adları için bir kontroldür; sır bulunmadığının mutlak garantisi değildir. Gerçek sır tespitinde yayını durdurup erişim anahtarını iptal etme/yenileme ve geçmiş temizliği planını ayrıca değerlendirin.

## Portföy ve CV sunumu

CV için örnek proje özeti:

> Laravel 13, Blade ve SQLite ile kurumsal web uygulaması ve yönetim paneli geliştirdim. Ürün/içerik CRUD, session authentication, yapılandırılmış CMS, teklif/iletişim akışları ve SEO yönetimini otomatik testlerle doğruladım.

GitHub repository bağlantısını CV’ye ekleyin. Canlı demo adresini yalnızca gerçekten yayımlandığında belirtin. Teknik görüşmede ürün silindiğinde teklif geçmişinin korunması, yönetici kontrolünün model binding’den önce çalışması, Markdown güvenliği ve izole test veritabanı gibi somut kararları anlatabilirsiniz.
