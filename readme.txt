=== OZD WP E-Bülten ===
Contributors: sadikozdogan
Tags: newsletter, email subscription, ajax, subscribers, e-bulten
Requires at least: 6.2
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AJAX destekli, ayarlanabilir ve WordPress standartlarına uygun temel e-bülten abonelik eklentisi.

== Description ==

OZD WP E-Bülten; shortcode, widget, AJAX form gönderimi, çift onay, abonelikten çıkma bağlantısı, abone yönetimi ve CSV dışa aktarma özellikleri olan temel bir e-bülten abonelik eklentisidir.

Eklenti, WordPress'in temel güvenlik yaklaşımlarını dikkate alır: nonce kontrolü, yetki kontrolü, sanitize/escape işlemleri, prepared SQL sorguları ve uninstall temizliği.

== Features ==

* Shortcode: [ozd_e_bulten]
* Widget desteği
* AJAX destekli abonelik formu
* İki aşamalı form onayı
* E-posta bağlantısı ile çift onay seçeneği
* Ad soyad alanı aç/kapat ve zorunlu yapma seçeneği
* KVKK/onay metni versiyon kaydı
* Admin abone yönetimi
* WP_List_Table tabanlı abone listesi
* Abone arama, durum filtresi ve sayfalama
* CSV dışa aktarma
* Abonelikten çıkma bağlantısı
* Onay bağlantısı süre kontrolü
* Hoş geldin e-postası seçeneği
* Form deneme limiti ayarları
* Onay e-postasını admin panelinden tekrar gönderme
* Tema üzerinden form şablonu ezme desteği
* Geliştiriciler için action/filter hook sistemi

== Installation ==

1. Eklenti klasörünü `wp-content/plugins` dizinine yükleyin.
2. WordPress yönetim panelinden eklentiyi etkinleştirin.
3. `OZD E-Bülten > Ayarlar` bölümünden form ve e-posta davranışlarını düzenleyin.
4. Formu göstermek istediğiniz yere `[ozd_e_bulten]` kısa kodunu ekleyin.

== Frequently Asked Questions ==

= Formu nasıl gösteririm? =

`[ozd_e_bulten]` kısa kodunu bir yazı, sayfa veya uygun bir içerik alanına ekleyebilirsiniz.

= Eklenti verileri silinir mi? =

Varsayılan olarak silinmez. Ayarlardaki kaldırma temizliği seçeneği açılırsa eklenti silinirken tablo ve ayarlar kaldırılır.

= Form şablonunu temadan değiştirebilir miyim? =

Evet. `templates/form.php` dosyasını temanızda `ozd-wp-e-bulten/form.php` yoluna kopyalayarak özelleştirebilirsiniz.

== Developer Hooks ==

Başlıca filter hookları:

* `ozd_ebulten_default_settings`
* `ozd_ebulten_settings`
* `ozd_ebulten_sanitized_settings`
* `ozd_ebulten_form_html`
* `ozd_ebulten_template_path`
* `ozd_ebulten_request_data`
* `ozd_ebulten_subscriber_data`
* `ozd_ebulten_subscribe_result`
* `ozd_ebulten_error_result`
* `ozd_ebulten_confirmation_mail`
* `ozd_ebulten_welcome_mail`
* `ozd_ebulten_admin_notification_mail`
* `ozd_ebulten_mail_headers`
* `ozd_ebulten_replace_tags`
* `ozd_ebulten_allowed_statuses`

Başlıca action hookları:

* `ozd_ebulten_loaded`
* `ozd_ebulten_before_handle_request`
* `ozd_ebulten_before_save_subscriber`
* `ozd_ebulten_after_save_subscriber`
* `ozd_ebulten_before_send_confirmation_mail`
* `ozd_ebulten_after_send_confirmation_mail`
* `ozd_ebulten_before_send_welcome_mail`
* `ozd_ebulten_after_send_welcome_mail`
* `ozd_ebulten_before_confirm_subscriber`
* `ozd_ebulten_after_confirm_subscriber`
* `ozd_ebulten_before_unsubscribe_subscriber`
* `ozd_ebulten_after_unsubscribe_subscriber`
* `ozd_ebulten_before_admin_change_status`
* `ozd_ebulten_after_admin_change_status`
* `ozd_ebulten_before_admin_delete_subscriber`
* `ozd_ebulten_after_admin_delete_subscriber`

== Changelog ==

= 1.0.1 =
* Onay bekleyen abonelerde yeniden gönderilen e-postanın sonucu kontrol edilir hale getirildi.
* Ayarlar kaydedildi yönetim bildirimi görünür hale getirildi.
* Abone kayıtlarını silmeden eklenti ayarlarını varsayılan değerlere döndüren güvenli sıfırlama özelliği eklendi.

= 1.0.0 =
* İlk kararlı sürüm.
* AJAX destekli abonelik formu ve shortcode desteği eklendi.
* Çift onay, abonelikten çıkma ve hoş geldin e-postası akışları eklendi.
* WP_List_Table tabanlı abone yönetimi, arama, filtreleme ve CSV dışa aktarma eklendi.
* Template override, widget ve geliştirici hookları eklendi.
* Nonce, yetki kontrolü, veri temizleme ve çıktı kaçışları uygulandı.
