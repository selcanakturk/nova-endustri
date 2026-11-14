<x-layout title="İletişim" description="Nova Endüstri ile iletişime geçin ve üretim ihtiyaçlarınızı paylaşın.">
    <x-page-heading eyebrow="İletişim" title="İyi bir çözüm, bir konuşmayla başlar." description="Projenizin ihtiyaçlarını birlikte değerlendirmek için ilk adım." />
    <section class="section"><div class="container contact-grid"><div><p class="eyebrow">İLETİŞİM NOKTAMIZ</p><h2>Birlikte<br>düşünelim.</h2><p>Üretim ihtiyacınızı veya sorunuzu paylaşın. Gönderdiğiniz mesaj yönetim paneline kaydedilir.</p><dl class="contact-details"><div><dt>E-posta</dt><dd>Henüz paylaşılmadı</dd></div><div><dt>Telefon</dt><dd>Henüz paylaşılmadı</dd></div><div><dt>Adres</dt><dd>Henüz paylaşılmadı</dd></div></dl><p class="catalog-note">Nova Endüstri kurgusal bir firmadır. Gerçek iletişim bilgileri bulunmamaktadır.</p></div>
        <div class="contact-form"><h2>Projenizden bahsedin</h2><x-inquiry-feedback />
            <form method="POST" action="{{ route('contact.store') }}" aria-describedby="inquiry-privacy">@csrf
                <div class="grid gap-5 sm:grid-cols-2"><x-inquiry-field name="name" label="Ad soyad" autocomplete="name" maxlength="150" required /><x-inquiry-field name="company" label="Firma (isteğe bağlı)" autocomplete="organization" maxlength="255" /><x-inquiry-field name="email" label="E-posta" type="email" autocomplete="email" maxlength="255" required /><x-inquiry-field name="phone" label="Telefon (isteğe bağlı)" type="tel" autocomplete="tel" maxlength="40" /></div>
                <x-inquiry-field name="subject" label="Konu" maxlength="255" required placeholder="Hangi konuda görüşmek istersiniz?" />
                <x-inquiry-field name="message" label="Mesajınız" type="textarea" rows="6" maxlength="10000" required />
                <x-inquiry-notice /><button type="submit" class="button">Mesajı gönder <span aria-hidden="true">↗</span></button>
            </form>
        </div></div></section>
</x-layout>
