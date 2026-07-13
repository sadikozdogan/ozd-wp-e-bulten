# Değişiklik Günlüğü

Bu projedeki önemli değişiklikler bu dosyada belgelenir.

Sürüm numaralandırmasında [Semantic Versioning](https://semver.org/) yaklaşımı kullanılmaktadır.

## [1.0.1] - 2026-07-14

### Eklendi

- Abone kayıtlarını silmeden eklenti ayarlarını varsayılan değerlere döndüren güvenli ayar sıfırlama özelliği.
- Ayar sıfırlama işlemi için yetki kontrolü ve nonce doğrulaması.

### Düzeltildi

- Onay bekleyen abonelerde yeniden gönderilen onay e-postasının sonucunun kontrol edilmemesi sorunu.
- E-posta gönderimi başarısız olsa bile başarılı işlem mesajı gösterilebilmesi sorunu.
- Ayarlar kaydedildikten sonra yönetim bildiriminde “Ayarlar kaydedildi.” mesajının görünmemesi sorunu.

### Değiştirildi

- Eklenti sürümü `1.0.1` olarak güncellendi.

## [1.0.0] - 2026

### Eklendi

- İlk kararlı sürüm.
- AJAX destekli abonelik formu ve kısa kod desteği.
- Çift onay, abonelikten çıkma ve hoş geldin e-postası akışları.
- WP_List_Table tabanlı abone yönetimi.
- Arama, filtreleme, sayfalama ve CSV dışa aktarma.
- Widget ve tema şablonu ezme desteği.
- Geliştiriciler için action ve filter hookları.
- Nonce, yetki kontrolü, veri temizleme ve çıktı kaçışları.
