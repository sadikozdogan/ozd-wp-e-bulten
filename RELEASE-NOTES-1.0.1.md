# OZD WP E-Bülten 1.0.1

## Türkçe sürüm notları

Bu bakım sürümü, e-posta yeniden gönderme kontrolünü ve eklenti ayarları deneyimini iyileştirir.

### Düzeltilenler

- Onay bekleyen abonelere yeniden gönderilen onay e-postasının sonucu artık kontrol ediliyor.
- E-posta gönderimi başarısız olduğunda başarılı işlem mesajı gösterilmesi engellendi.
- Ayarlar kaydedildikten sonra “Ayarlar kaydedildi.” yönetim bildirimi görünür hale getirildi.

### Eklenenler

- Abone kayıtlarına dokunmadan eklenti ayarlarını varsayılan değerlere döndüren güvenli sıfırlama özelliği eklendi.
- Ayar sıfırlama işlemine yetki kontrolü ve nonce doğrulaması eklendi.

## English release notes

This maintenance release improves confirmation email resend handling and the plugin settings experience.

### Fixed

- Added result checking when resending confirmation emails to pending subscribers.
- Prevented a success message from being displayed when email delivery fails.
- Restored the standard “Settings saved.” admin notice after saving plugin settings.

### Added

- Added a secure reset option that restores plugin settings to their defaults without deleting subscriber records.
- Added capability and nonce checks to the settings reset process.
