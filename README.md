# OZD WP E-Bülten

OZD WP E-Bülten, WordPress için geliştirilmiş; AJAX destekli, ayarlanabilir ve temel güvenlik standartlarını dikkate alan bir e-bülten abonelik eklentisidir.

## Güncel Sürüm

**1.0.1**

Bu sürüm; onay bekleyen abonelere yeniden e-posta gönderiminde sonuç kontrolü, ayar kaydetme bildirimi ve güvenli ayar sıfırlama özelliği içerir.

## Özellikler

- `[ozd_e_bulten]` kısa kodu
- Widget desteği
- AJAX destekli abonelik formu
- İki aşamalı form onayı
- E-posta bağlantısı ile çift onay
- Ad-soyad alanını açma, kapatma ve zorunlu yapma
- KVKK/onay metni sürüm kaydı
- Yönetim panelinden abone yönetimi
- Arama, durum filtresi ve sayfalama
- CSV dışa aktarma
- Abonelikten çıkma bağlantısı
- Onay bağlantısı süre kontrolü
- Hoş geldin e-postası
- Onay e-postasını yeniden gönderme
- Tema üzerinden form şablonunu değiştirme
- Geliştiriciler için action ve filter hookları

## Kurulum

1. Eklenti klasörünü `wp-content/plugins` dizinine yükleyin.
2. WordPress yönetim panelinden **OZD WP E-Bülten** eklentisini etkinleştirin.
3. **OZD E-Bülten > Ayarlar** bölümünden eklenti ayarlarını yapılandırın.
4. Formun görünmesini istediğiniz alana `[ozd_e_bulten]` kısa kodunu ekleyin.

## E-posta Gönderimi Hakkında

Eklenti e-postaları WordPress'in `wp_mail()` sistemi üzerinden gönderir. Local geliştirme ortamlarında gerçek e-posta teslimatı için SMTP yapılandırması gerekebilir.

## Sürüm 1.0.1 Değişiklikleri

### Düzeltilenler

- Onay bekleyen bir abone yeniden kayıt olduğunda gönderilen onay e-postasının sonucu artık kontrol ediliyor.
- E-posta gönderimi başarısız olduğunda başarılı işlem mesajı gösterilmesi engellendi.
- Ayarlar kaydedildikten sonra WordPress yönetim bildiriminin görünmesi sağlandı.

### Eklenenler

- Abone kayıtlarını silmeden eklenti ayarlarını varsayılan değerlere döndüren güvenli sıfırlama özelliği eklendi.
- Ayar sıfırlama işlemine yetki ve nonce doğrulaması eklendi.

Tüm sürüm geçmişi için [CHANGELOG.md](CHANGELOG.md) dosyasına bakabilirsiniz.

## Gereksinimler

- WordPress 6.2 veya üzeri
- PHP 7.4 veya üzeri

## Lisans

Bu proje GPL-2.0-or-later lisansı ile yayımlanmıştır. Ayrıntılar için [LICENSE](LICENSE) dosyasına bakabilirsiniz.

## Geliştirici

Sadık Özdoğan  
[https://www.sadikozdogan.com](https://www.sadikozdogan.com)
